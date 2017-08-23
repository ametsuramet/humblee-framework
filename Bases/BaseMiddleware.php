<?php

namespace App\Middlewares;

class BaseMiddleware {

	protected $routerParams = [];

	function __construct()
    {
        $class_name = get_class($this);
        $class   = new \ReflectionClass($class_name);
        $methods = $class->getMethods();
        foreach ($methods as $key => $method) {
            if ($method->name != "__construct" && $method->class == $class_name) {
                foreach ($this->routerParams as $key => $routerParams) {
                	if ($routerParams['method'] == request()->server->get('REQUEST_METHOD') && 
                		$routerParams['uri'] == request()->server->get('PATH_INFO')) {
                		call_user_func(array($this, 'handle'));
                    }
                    if (preg_match_all('/:([\w-%]+)/', $routerParams['uri'], $keys)) {
                        // print_r('@^' .$routerParams['uri'] . '/?$@i');
                        $keys = current($keys);
                        $merge = implode("/",$keys);
                        $prefix = str_replace($merge, "", $routerParams['uri']);
                        $pattern = [rtrim($prefix,'/')];
                        foreach ($keys as $key) {
                            $pattern[] = "(.+)";
                        }
                        $pattern = "/\\".implode("\/", $pattern)."/";
                        if (preg_match($pattern, request()->server->get('PATH_INFO'))) {
                            call_user_func(array($this, 'handle'));
                        }
                    }
                }
            }
        }
        return $this;
    }
}