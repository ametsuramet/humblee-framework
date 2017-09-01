<?php

namespace Amet\Humblee\Bases;

class BaseMiddleware {

    protected $routerParams = [];
    protected $namespace = 'App\Controllers\\';
    protected $namespaceApi = 'App\Controllers\Api\\';
    protected $ApiPrefix = '/api/v1';

    function __construct()
    {
        $class_name = get_class($this);
        $class   = new \ReflectionClass($class_name);
        $methods = $class->getMethods();
        
        $routes = $this->getRoutes();
        foreach ($methods as $key => $method) {
            $simple_class_name = str_replace("App\Middlewares\\", "", $class_name);
            if ($method->name != "__construct" && $method->class == $class_name) {
                if (!count($this->routerParams)) {
                    foreach ($routes as $key => $route) {
                        if(isset($route['middleware'])) {
                            if (in_array($simple_class_name, $route['middleware'])) {
                                if ($route['methods'] == request()->server->get('REQUEST_METHOD') && 
                                    ($route['url'] == request()->server->get('PATH_INFO') || $route['url'] == request()->server->get('REDIRECT_URL'))) {
                                    $GLOBALS['middleware_class'] = $class_name;
                                    call_user_func(array($this, 'handle'));
                                    // echo $route['url']."=>".$route['methods']."=>".PHP_EOL;
                                } else
                                if (preg_match_all('/:([\w-%]+)/', $route['url'], $keys)) {
                                    $keys = current($keys);
                                    $uri = $route['url'];
                                    foreach ($keys as $key) {
                                        $uri = str_replace($key, "([^/]+)", $uri);
                                    }
                                    $merge = implode("/",$keys);
                                    $pattern = rtrim($uri,'/');
                                    $pattern = "/".str_replace("/","\/", $pattern)."$/";
                                    if (
                                        preg_match($pattern, request()->server->get('PATH_INFO')) ||
                                        preg_match($pattern, request()->server->get('REDIRECT_URL')) 
                                        ) {
                                        $GLOBALS['middleware_class'] = $class_name;
                                        call_user_func(array($this, 'handle'));
                                    }
                                } else {
                                    
                                }
                            }
                        }
                    }
                }
                foreach ($this->routerParams as $key => $routerParams) {
                    
                    if ($routerParams['uri'] == "*") {
                        $GLOBALS['middleware_class'] = $class_name;
                        call_user_func(array($this, 'handle'));
                    } else
                    if ($routerParams['method'] == request()->server->get('REQUEST_METHOD') && 
                        ($routerParams['uri'] == request()->server->get('PATH_INFO') || $routerParams['uri'] == request()->server->get('REDIRECT_URL'))) {
                        $GLOBALS['middleware_class'] = $class_name;
                        call_user_func(array($this, 'handle'));
                    } else
                    if (preg_match_all('/:([\w-%]+)/', $routerParams['uri'], $keys)) {
                        $keys = current($keys);
                        $uri = $routerParams['uri'];
                        foreach ($keys as $key) {
                            $uri = str_replace($key, "([^/]+)", $uri);
                        }
                        $merge = implode("/",$keys);
                        $pattern = rtrim($uri,'/');
                        $pattern = "/".str_replace("/","\/", $pattern)."$/";
                        if (
                            preg_match($pattern, request()->server->get('PATH_INFO')) ||
                            preg_match($pattern, request()->server->get('REDIRECT_URL')) 
                            ) {
                            $GLOBALS['middleware_class'] = $class_name;
                            call_user_func(array($this, 'handle'));
                        }
                    } else {
                        
                    }
                }
            }
        }
        return $this;
    }

    private function getRoutes()
    {
        require base_path('router/web.php');
        $WebCollection = array_map(function($arr){
            $arr['_controller'] = $this->namespace.$arr['_controller'];
            return $arr;
        }, $WebCollection);
        require base_path('router/api.php');
        $ApiCollection = array_map(function($arr){
            $arr['url'] = $this->ApiPrefix.$arr['url'];
            $arr['_controller'] = $this->namespaceApi.$arr['_controller'];
            return $arr;
        }, $ApiCollection);

        return array_merge($WebCollection,$ApiCollection);
        
    }
}