CREATE DATABASE IF NOT EXISTS trading_login CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE trading_login;

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 插入测试用户（密码：123456）
INSERT INTO users (name, email, password) VALUES (
    '测试用户',
    'test@example.com',
    '$2y$12$mvgIq.6DcJZyNCf.ujxNU.ZEWp4X70Na9q4TYsamtg9ejeZINMGqK'
) ON DUPLICATE KEY UPDATE name = VALUES(name);
