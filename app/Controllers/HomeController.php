<?php

namespace App\Controllers;

/**
 * 首页控制器类
 * 用于处理首页相关请求
 */
class HomeController {
    public function index() {

        // 检查是否已登录
        if (isset($_SESSION['user_id'])) {
            echo '欢迎回来，' . htmlspecialchars($_SESSION['user_name']) . '！';
            echo '<br><a href="/logout">退出登录</a>';
        } else {
            echo '请先 <a href="/login">登录</a>';
        }
    }
}
