<?php
namespace App\Http\Middleware;

/**
 * CSRF 令牌验证中间件类
 * 用于验证 POST 请求中的 CSRF 令牌
 */
class VerifyCsrfToken {
    public function handle($request, $next) {
        if ($request->method() === 'POST') {
            if (!isset($_POST['_token']) || !hash_equals($_SESSION['_token'], $_POST['_token'])) {
                http_response_code(419);
                die('CSRF token mismatch.');
            }
        }
        return $next($request);
    }
}