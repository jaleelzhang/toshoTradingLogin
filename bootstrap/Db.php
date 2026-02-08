<?php

/**
 * Db类，用于封装PDO对象
 */
class Db {
    protected $pdo;
    
    /**
     * 构造函数
     * 
     * @param PDO $pdo PDO实例
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 代理PDO的方法
     * 
     * @param string $method 方法名
     * @param array $args 参数数组
     * @return mixed 方法返回值
     */
    public function __call($method, $args) {
        return call_user_func_array([$this->pdo, $method], $args);
    }
    
    /**
     * 显式实现prepare方法，确保正确代理
     * 
     * @param string $statement SQL语句
     * @param array $driver_options 驱动选项
     * @return PDOStatement PDOStatement实例
     */
    public function prepare($statement, $driver_options = []) {
        return $this->pdo->prepare($statement, $driver_options);
    }
    
    /**
     * 直接访问PDO对象
     * 
     * @return PDO PDO实例
     */
    public function getPdo() {
        return $this->pdo;
    }
}
