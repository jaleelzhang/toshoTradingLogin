<?php

/**
 * 加载环境变量
 * 
 * @param string $key 环境变量键名
 * @param mixed $default 默认值
 * @return mixed 环境变量值或默认值
 */
function env($key, $default = null) {
    static $envLoaded = false;
    static $envVars = [];
    
    if (!$envLoaded) {
        $envFile = __DIR__ . '/../storage/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }
                list($keyPart, $valuePart) = explode('=', $line, 2);
                $envVars[trim($keyPart)] = trim($valuePart);
            }
        }
        $envLoaded = true;
    }
    
    return $envVars[$key] ?? $default;
}

/**
 * 生成存储路径
 * 
 * @param string $path 相对路径
 * @return string 完整存储路径
 */
function storage_path($path = '') {
    return __DIR__ . '/../storage' . ($path ? '/' . $path : '');
}

/**
 * 生成CSRF令牌
 * 
 * @return string CSRF令牌
 */
function csrf_token() {
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

/**
 * 生成CSRF令牌字段
 * 
 * @return string HTML隐藏输入字段
 */
function csrf_field() {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

/**
 * 验证CSRF令牌
 * 
 * @param string $token 要验证的令牌
 * @return bool 令牌是否有效
 */
function validate_csrf_token($token) {
    return isset($_SESSION['_token']) && hash_equals($_SESSION['_token'], $token);
}

