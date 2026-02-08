<?php

// 自动加载函数
spl_autoload_register(function ($class) {
    
    // 将命名空间转换为文件路径
    $file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
