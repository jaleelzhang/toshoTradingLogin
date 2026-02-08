<?php

namespace App\Models;

/**
 * 用户模型类
 * 用于操作数据库中的 users 表
 */
class User {
    protected $db;
    protected $id;
    protected $email;
    protected $password;
    protected $name;
    protected $created_at;

    public function __construct($db) {
        $this->db = $db;
    }

    public static function findByEmail($db, $email) {
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $userData = $stmt->fetch();

        if (!$userData) {
            return null;
        }

        $user = new self($db);
        $user->id = $userData['id'];
        $user->email = $userData['email'];
        $user->password = $userData['password'];
        $user->name = $userData['name'];
        $user->created_at = $userData['created_at'];

        return $user;
    }

    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }

    public function getId() {
        return $this->id;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getName() {
        return $this->name;
    }
}
