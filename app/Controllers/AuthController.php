<?php

namespace App\Controllers;

use App\Models\User;

/**
 * 认证控制器类
 * 用于处理用户登录、注销等认证相关请求
 */
class AuthController {
    protected $app;
    protected $db;

    public function __construct($app) {
        $this->app = $app;
        $this->db = $app->make('db');
    }

    public function handleLogin($data) {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $token = $data['_token'] ?? '';

        // 验证CSRF令牌
        if (!validate_csrf_token($token)) {
            $this->redirectWithError('/login', '无效的请求');
            return;
        }

        // 验证输入
        if (empty($email) || empty($password)) {
            $this->redirectWithError('/login', '邮箱和密码不能为空');
            return;
        }

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('/login', '邮箱格式不正确');
            return;
        }

        // 过滤输入，防止XSS攻击
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        // 检查登录尝试次数
        $attemptsKey = 'login_attempts_' . $email;
        $lastAttemptKey = 'last_login_attempt_' . $email;
        $maxAttempts = 5; // 最大尝试次数
        $lockoutTime = 300; // 锁定时间（秒）

        if (isset($_SESSION[$attemptsKey]) && isset($_SESSION[$lastAttemptKey])) {
            if ($_SESSION[$attemptsKey] >= $maxAttempts) {
                $timeSinceLastAttempt = time() - $_SESSION[$lastAttemptKey];
                if ($timeSinceLastAttempt < $lockoutTime) {
                    $remainingTime = $lockoutTime - $timeSinceLastAttempt;
                    $this->redirectWithError('/login', '登录尝试次数过多，请在' . $remainingTime . '秒后重试');
                    return;
                } else {
                    // 锁定时间已过，重置尝试次数
                    $_SESSION[$attemptsKey] = 0;
                }
            }
        }

        // 查找用户
        $user = User::findByEmail($this->db, $email);

        if (!$user || !$user->verifyPassword($password)) {
            // 登录失败，增加尝试次数
            $_SESSION[$attemptsKey] = isset($_SESSION[$attemptsKey]) ? $_SESSION[$attemptsKey] + 1 : 1;
            $_SESSION[$lastAttemptKey] = time();
            $this->redirectWithError('/login', '邮箱或密码错误');
            return;
        }

        // 登录成功，重置尝试次数
        unset($_SESSION[$attemptsKey]);
        unset($_SESSION[$lastAttemptKey]);

        // 登录成功，重新生成会话ID，防止会话固定攻击
        session_regenerate_id(true);

        // 存储用户信息到会话
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_name'] = $user->getName();

        // 重定向到首页
        header('Location: /');
        exit;
    }

    protected function redirectWithError($url, $error) {
        $_SESSION['error'] = $error;
        header('Location: ' . $url);
        exit;
    }

    public function showLoginForm() {
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $token = $_SESSION['_token'] ?? '';

        include __DIR__ . '/../../resources/views/login.blade.php';
    }

    public function handleLogout() {
        
        // 销毁会话
        session_destroy();
        
        // 重定向到登录页
        header('Location: /login');
        exit;
    }

    public function redirectToGoogle() {
        // Google OAuth 配置
        $clientId = env('GOOGLE_CLIENT_ID', '');
        
        // 根据当前环境使用正确的协议和主机名
        $protocol = (env('APP_ENV') === 'production' || isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        
        // 开发环境使用localhost，避免域名限制
        $host = env('APP_ENV') === 'production' ? $_SERVER['HTTP_HOST'] : 'trading-login.test';
        $redirectUri = $protocol . '://' . $host . '/login/google/callback';
        $scope = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';
        $state = bin2hex(random_bytes(32));

        // 存储state参数，用于验证回调
        $_SESSION['google_oauth_state'] = $state;

        // 构建授权URL
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        // 重定向到Google授权页面
        header('Location: ' . $authUrl);
        exit;
    }

    public function handleGoogleCallback() {
        // 验证state参数
        if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
            $this->redirectWithError('/login', '无效的请求');
            return;
        }

        // 验证code参数
        if (!isset($_GET['code'])) {
            $this->redirectWithError('/login', '授权失败');
            return;
        }

        // Google OAuth 配置
        $clientId = env('GOOGLE_CLIENT_ID', '');
        $clientSecret = env('GOOGLE_CLIENT_SECRET', '');
        // 根据当前环境使用正确的协议和主机名
        $protocol = (env('APP_ENV') === 'production' || isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        // 开发环境使用localhost，避免域名限制
        $host = env('APP_ENV') === 'production' ? $_SERVER['HTTP_HOST'] : 'localhost';
        $redirectUri = $protocol . '://' . $host . '/login/google/callback';

        // 交换授权码获取访问令牌
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $response = $this->httpPost($tokenUrl, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $_GET['code'],
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $tokenData = json_decode($response, true);

        // 验证响应
        if (!isset($tokenData['access_token'])) {
            $this->redirectWithError('/login', '获取访问令牌失败');
            return;
        }

        // 获取用户信息
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $userInfoResponse = $this->httpGet($userInfoUrl, [
            'Authorization' => 'Bearer ' . $tokenData['access_token'],
        ]);

        $userInfo = json_decode($userInfoResponse, true);

        // 验证用户信息
        if (!isset($userInfo['email'])) {
            $this->redirectWithError('/login', '获取用户信息失败');
            return;
        }

        // 查找或创建用户
        $user = User::findByEmail($this->db, $userInfo['email']);

        if (!$user) {
            // 创建新用户
            $name = $userInfo['name'] ?? $userInfo['email'];
            $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

            $stmt = $this->db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$name, $userInfo['email'], $password]);

            $user = User::findByEmail($this->db, $userInfo['email']);
        }

        // 登录成功，重新生成会话ID
        session_regenerate_id(true);

        // 存储用户信息到会话
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_name'] = $user->getName();

        // 重定向到首页
        header('Location: /');
        exit;
    }

    protected function httpPost($url, $data) {
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    protected function httpGet($url, $headers = []) {
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }

        $options = [
            'http' => [
                'header'  => $headerString,
                'method'  => 'GET',
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
}
