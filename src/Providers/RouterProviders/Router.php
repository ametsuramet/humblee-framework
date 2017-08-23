<?php

namespace Amet\Humblee\Providers\RouterProviders;

use Exception;
use Fig\Http\Message\RequestMethodInterface;

class Router
{
    /**
     * RouteCollection that holds all Route objects
     *
     * @var RouteCollection
     */
    private $routes = array();

    /**
     * Array to store named routes in, used for reverse routing.
     * @var array
     */
    private $namedRoutes = array();

    /**
     * The base REQUEST_URI. Gets prepended to all route url's.
     * @var string
     */
    private $basePath = '';

    /**
     * @param RouteCollection $collection
     */
    public function __construct(RouteCollection $collection)
    {
        $this->routes = $collection;

        foreach ($this->routes->all() as $route) {
            $name = $route->getName();
            if (null !== $name) {
                $this->namedRoutes[$name] = $route;
            }
        }
    }

    /**
     * Set the base _url - gets prepended to all route _url's.
     * @param $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Matches the current request against mapped routes
     */
    public function matchCurrentRequest()
    {
        $requestMethod = (
            isset($_POST['_method'])
            && ($_method = strtoupper($_POST['_method']))
            && in_array($_method, array(RequestMethodInterface::METHOD_PUT, RequestMethodInterface::METHOD_DELETE), true)
        ) ? $_method : $_SERVER['REQUEST_METHOD'];

        $requestUrl = $_SERVER['REQUEST_URI'];

        // strip GET variables from URL
        if (($pos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $pos);
        }

        return $this->match($requestUrl, $requestMethod);
    }

    /**
     * Match given request _url and request method and see if a route has been defined for it
     * If so, return route's target
     * If called multiple times
     *
     * @param string $requestUrl
     * @param string $requestMethod
     *
     * @return bool|Route
     */
    public function match($requestUrl, $requestMethod = RequestMethodInterface::METHOD_GET)
    {
        $currentDir = dirname($_SERVER['SCRIPT_NAME']);

        foreach ($this->routes->all() as $routes) {
            // compare server request method with route's allowed http methods
            if (! in_array($requestMethod, (array)$routes->getMethods(), true)) {
                continue;
            }

            if ('/' !== $currentDir) {
                $requestUrl = str_replace($currentDir, '', $requestUrl);
            }

            $route = rtrim($routes->getRegex(), '/');
            $pattern = '@^' . preg_quote($this->basePath) . $route . '/?$@i';
            if (!preg_match($pattern, $requestUrl, $matches)) {
                continue;
            }

            $params = array();

            if (preg_match_all('/:([\w-%]+)/', $routes->getUrl(), $argument_keys)) {
                // grab array with matches
                $argument_keys = $argument_keys[1];

                // check arguments number

                if(count($argument_keys) !== (count($matches) -1)) {
                    continue;
                }

                // loop trough parameter names, store matching value in $params array
                // fixing set params
                foreach ($argument_keys as $key => $name) {
                    if (isset($matches[$key])) {
                        $params[$name] = $matches[$key + 1];
                    }
                }
            }
            
            $routes->setParameters($params);
            $routes->dispatch();

            return $routes;
        }
        // throw new Exception("No route with the name $requestUrl has been found.");
        $message = "No route with the name $requestUrl has been found.";
        return view_exception('404',compact('message'));
        // return false;
    }

    /**
     * Reverse route a named route
     *
     * @param $routeName
     * @param array $params Optional array of parameters to use in URL
     *
     * @throws Exception
     *
     * @return string The url to the route
     */
    public function generate($routeName, array $params = array())
    {
        // Check if route exists
        if (!isset($this->namedRoutes[$routeName])) {
            throw new Exception("No route with the name $routeName has been found.");
        }

        /** @var \PHPRouter\Route $route */
        $route = $this->namedRoutes[$routeName];
        $url = $route->getUrl();

        // replace route url with given parameters
        if ($params && preg_match_all('/:(\w+)/', $url, $param_keys)) {
            // grab array with matches
            $param_keys = $param_keys[1];

            // loop trough parameter names, store matching value in $params array
            foreach ($param_keys as $key) {
                if (isset($params[$key])) {
                    $url = preg_replace('/:(\w+)/', $params[$key], $url, 1);
                }
            }
        }

        return $url;
    }

    /**
     * Create routes by array, and return a Router object
     *
     * @param array $config provide by Config::loadFromFile()
     * @return Router
     */
    public static function parseConfig(array $config)
    {
        $collection = new RouteCollection();
        foreach ($config['routes'] as $name => $route) {
            $collection->attachRoute(new Route($route[0], array(
                '_controller' => str_replace('.', '::', $route[1]),
                'methods' => $route[2],
                'name' => $name
            )));
        }

        $router = new Router($collection);
        if (isset($config['base_path'])) {
            $router->setBasePath($config['base_path']);
        }

        return $router;
    }
}
