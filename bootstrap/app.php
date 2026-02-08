<?php

// 加载辅助函数
require_once __DIR__ . '/helpers.php';

// 加载容器类
require_once __DIR__ . '/Container.php';

// 加载Db类
require_once __DIR__ . '/Db.php';

// 创建应用容器
$app = new Container();

// 加载配置
$app->bind('config', function() {
    return [
        'db' => require __DIR__ . '/../config/database.php',
        'auth' => require __DIR__ . '/../config/auth.php',
    ];
});

// 数据库连接
$app->bind('db', function($app) {
    $config = $app->make('config')['db'];
    $driver = $config['driver'];
    
    try {
        switch ($driver) {
            case 'mysql':
                return $app->make('db.mysql');
            case 'sqlite':
                return $app->make('db.sqlite');
            default:
                throw new Exception("不支持的数据库驱动: {$driver}");
        }
    } catch (Exception $e) {
        die('数据库连接失败: ' . $e->getMessage() . '<br>请检查数据库配置是否正确。');
    }
});

// MySQL数据库连接
$app->bind('db.mysql', function($app) {
    $config = $app->make('config')['db']['mysql'];
    
    // 先连接到MySQL服务器（不指定数据库）
    $dsn = "mysql:host={$config['host']};port={$config['port']}";
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 尝试创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['database']} CHARACTER SET {$config['charset']} COLLATE {$config['collation']}");
    
    // 断开连接，重新连接到指定的数据库
    unset($pdo);
    
    // 连接到指定的数据库
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 创建用户表（如果不存在）
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 生成正确的密码哈希值（对应密码"123456"）
    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
    
    // 插入测试用户（如果不存在）
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (
        '测试用户',
        'test@example.com',
        ?
    ) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $stmt->execute([$passwordHash]);
    
    // 返回Db类的实例
    return new Db($pdo);
});

// SQLite数据库连接
$app->bind('db.sqlite', function($app) {
    $config = $app->make('config')['db']['sqlite'];
    $database = $config['database'];
    
    // 确保存储目录存在
    $directory = dirname($database);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // 连接到SQLite数据库
    $dsn = "sqlite:{$database}";
    $pdo = new PDO(
        $dsn,
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 创建用户表（如果不存在）
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 生成正确的密码哈希值（对应密码"123456"）
    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
    
    // 插入测试用户（如果不存在）
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (name, email, password) VALUES (
        '测试用户',
        'test@example.com',
        ?
    )");
    $stmt->execute([$passwordHash]);
    
    // 返回Db类的实例
    return new Db($pdo);
});

// 自动加载控制器
$controllersDir = __DIR__ . '/../app/Controllers';
$controllerFiles = glob($controllersDir . '/*.php');

foreach ($controllerFiles as $file) {
    require_once $file;
    
    // 提取控制器类名
    $fileName = basename($file, '.php');
    $className = "App\Controllers\{$fileName}";
    
    // 绑定到容器
    $app->bind($className, function($app) use ($className) {
        return new $className($app);
    });
}

return $app;
