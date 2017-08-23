<?php

namespace Amet\Humblee\Providers\RouterProviders;

use Fig\Http\Message\RequestMethodInterface;

class Route
{
    /**
     * URL of this Route
     * @var string
     */
    private $url;

    /**
     * Accepted HTTP methods for this route.
     * @var string[]
     */
    private $methods = array(
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_DELETE,
    );

    /**
     * Target for this route, can be anything.
     * @var mixed
     */
    private $target;

    /**
     * The name of this route, used for reversed routing
     * @var string
     */
    private $name;

    /**
     * Custom parameter filters for this route
     * @var array
     */
    private $filters = array();

    /**
     * Array containing parameters passed through request URL
     * @var array
     */
    private $parameters = array();

    /**
     * Set named parameters to target method
     * @example [ [0] => [ ["link_id"] => "12312" ] ]
     * @var bool
     */
    private $parametersByName;

    /**
     * @var array
     */
    private $config;

    /**
     * @param       $resource
     * @param array $config
     */
    public function __construct($resource, array $config)
    {
        $this->url     = $resource;
        $this->config  = $config;
        $this->methods = isset($config['methods']) ? (array) $config['methods'] : array();
        $this->target  = isset($config['target']) ? $config['target'] : null;
        $this->name    = isset($config['name']) ? $config['name'] : null;
        $this->parameters = isset($config['parameters']) ? $config['parameters'] : array();
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $url = (string)$url;

        // make sure that the URL is suffixed with a forward slash
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        $this->url = $url;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function setMethods(array $methods)
    {
        $this->methods = $methods;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = (string)$name;
    }

    public function setFilters(array $filters, $parametersByName = false)
    {
        $this->filters          = $filters;
        $this->parametersByName = $parametersByName;
    }

    public function getRegex()
    {
        return preg_replace_callback('/(:\w+)/', array(&$this, 'substituteFilter'), $this->url);
    }

    private function substituteFilter($matches)
    {
        if (isset($matches[1], $this->filters[$matches[1]])) {
            return $this->filters[$matches[1]];
        }

        return '([\w-%]+)';
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    public function dispatch()
    {
        $action = explode('::', $this->config['_controller']);

        if ($this->parametersByName) {
            $this->parameters = array($this->parameters);
        }

        $this->action = !empty($action[1]) && trim($action[1]) !== '' ? $action[1] : null;

        if (!is_null($this->action)) {
            $instance = new $action[0];
            call_user_func_array(array($instance, $this->action), $this->parameters);
        } else {
            $instance = new $action[0]($this->parameters);
        }
    }

    public function getAction()
    {
        return $this->action;
    }
}
