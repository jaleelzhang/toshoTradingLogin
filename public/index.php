<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 生产环境中强制使用HTTPS
if (env('APP_ENV') === 'production' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// 根据当前环境设置secure属性
$secure = (env('APP_ENV') === 'production') || isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax',
    'lifetime' => 3600, // 会话过期时间：1小时
    'path' => '/'
]);

@session_start();

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (empty($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

// 加载路由配置
$routes = require __DIR__ . '/../routes/web.php';

// 匹配路由
$matchedRoute = null;
foreach ($routes as $route) {
    if ($route['uri'] === $uri && $route['method'] === $method) {
        $matchedRoute = $route;
        break;
    }
}

if ($matchedRoute) {

    // 解析控制器和方法
    list($controllerClass, $methodName) = explode('@', $matchedRoute['action']);
    
    // 验证 CSRF Token（对于 POST 请求）
    if ($method === 'POST') {
        if (!isset($_POST['_token']) || $_POST['_token'] !== $_SESSION['_token']) {
            die('CSRF Token 验证失败');
        }
    }
    
    // 实例化控制器并调用方法
    $controller = $app->make($controllerClass);
    if ($method === 'POST') {
        $controller->{$methodName}($_POST);
    } else {
        $controller->{$methodName}();
    }
} else {
    
    // 路由未找到
    http_response_code(404);
    echo '404 - 页面未找到';
}

