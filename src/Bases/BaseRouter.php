<?php

namespace Amet\Humblee\Bases;
use Amet\Humblee\Providers\RouterProviders\RouteCollection;
use Amet\Humblee\Providers\RouterProviders\Router;
use Amet\Humblee\Providers\RouterProviders\Route;


class BaseRouter
{
    protected $namespace = 'App\Controllers\\';
    protected $namespaceApi = 'App\Controllers\Api\\';
    protected $ApiPrefix = '/api/v1';
    protected $ApiCollection;
    protected $WebCollection;
	function __construct()
	{
		$this->handle();
	}

	private function handle()
	{
		$this->map();
	}

	private function map()
    {
        $this->WebRoutes();
        $this->ApiRoutes();
        $this->buildRouter();
    }

    private function WebRoutes()
    {
    	require base_path('router/web.php');
    	$this->WebCollection = array_map(function($arr){
    		$arr['_controller'] = $this->namespace.$arr['_controller'];
    		return $arr;
    	}, $WebCollection);
    }

    private function ApiRoutes()
    {
    	require base_path('router/api.php');
    	$this->ApiCollection = array_map(function($arr){
    		$arr['url'] = $this->ApiPrefix.$arr['url'];
    		$arr['_controller'] = $this->namespaceApi.$arr['_controller'];
    		return $arr;
    	}, $ApiCollection);
    }

    private function buildRouter()
    {
    	$merge_collection = array_merge($this->WebCollection,$this->ApiCollection);
    	$collection = new RouteCollection();
    	foreach ($merge_collection as $key => $coll) {
    		$arr = [
				    '_controller' => $coll['_controller'],
				    'methods' => $coll['methods']
				];
			if (isset($coll['name'])) {
				$arr['name'] = $coll['name'];
			}
    		$collection->attachRoute(new Route($coll['url'], $arr));
    	}
    	// print_r($collection);
    	$router = new Router($collection);
		$router->setBasePath('/');
		$route = $router->matchCurrentRequest();
    }
}