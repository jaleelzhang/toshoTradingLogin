<?php

/**
 * 容器类，用于依赖注入
 */
class Container {
    protected $bindings = [];

    /**
     * 绑定服务到容器
     * 
     * @param string $name 服务名称
     * @param callable $resolver 服务解析器
     */
    public function bind($name, $resolver) {
        $this->bindings[$name] = $resolver;
    }
    
    /**
     * 从容器中获取服务
     * 
     * @param string $name 服务名称
     * @return mixed 服务实例
     * @throws Exception
     */
    public function make($name) {
        if (!isset($this->bindings[$name])) {
            if (class_exists($name)) {
                return new $name($this);
            }
            throw new Exception("Class {$name} not found and not bound in container");
        }
        return $this->bindings[$name]($this);
    }
}
