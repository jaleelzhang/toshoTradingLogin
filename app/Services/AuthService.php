<?php
namespace App\Services;

class AuthService {
    public function __construct(private $db) {}

    public function attempt(string $email, string $password): bool {
        
        // 查找用户
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 密码验证：使用 password_verify 进行安全比对
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 登录成功：设置 Session 变量
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_login_at'] = time();
            return true;
        }

        return false;
    }
}