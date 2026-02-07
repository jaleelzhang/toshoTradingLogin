<?php

// 项目容器
class Container {
    protected $bindings = [];
    public function bind($name, $resolver) {
        $this->bindings[$name] = $resolver;
    }
    public function make($name) {
        return $this->bindings[$name]($this);
    }
}

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
    return new PDO(
        "mysql:host={$config['host']};dbname={$config['name']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
});

return $app;