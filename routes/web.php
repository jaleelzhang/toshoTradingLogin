<?php
/**
 * 路由配置文件
 * 定义应用的路由规则
 */
return [
    // 认证路由
    ['uri' => '/login', 'method' => 'GET', 'action' => 'App\Controllers\AuthController@showLoginForm'],
    ['uri' => '/login', 'method' => 'POST', 'action' => 'App\Controllers\AuthController@handleLogin'],
    ['uri' => '/logout', 'method' => 'GET', 'action' => 'App\Controllers\AuthController@handleLogout'],
    
    // Google登录路由
    ['uri' => '/login/google', 'method' => 'GET', 'action' => 'App\Controllers\AuthController@redirectToGoogle'],
    ['uri' => '/login/google/callback', 'method' => 'GET', 'action' => 'App\Controllers\AuthController@handleGoogleCallback'],
    
    // 首页路由
    ['uri' => '/', 'method' => 'GET', 'action' => 'App\Controllers\HomeController@index'],
];

