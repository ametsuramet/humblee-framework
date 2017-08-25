<?php

namespace Amet\Humblee\Bases;

class BaseMongoModel
{
	protected $database = "";
	protected $collection = "";

	function __construct()
    {
    	// if (!extension_loaded("mongo")) {
    	// 	throw new \Exception("MongoDB Driver not loaded");
    	// }

    	global $config;
		$db_config = $config['mongo']['db'][$config['app']['APP_ENV']];

    	$this->database = $db_config['database'];
        $class_name = get_class($this);
        $class   = new \ReflectionClass($class_name);
        $methods = $class->getMethods();
        foreach ($methods as $key => $method) {
            if ($method->name != "__construct" && $method->class == $class_name) {
                call_user_func(array($this, $method->name));
            }
        }
        return $this;
    }

    private function collection()
    {
    	$db = mongo()->{$this->database}->{$this->collection};
    	return $db;
    }

    public function find($params = [], $options = [])
    {
        return $this->collection()->find($params, $options);
    }

   

    public function insertOne($params)
    {
    	$insertOneResult = $this->collection()->insertOne($params);
        return $insertOneResult->getInsertedId();
    }

    public function insertMany($params)
    {
    	$insertManyResult = $this->collection()->insertMany($params);
        return $insertManyResult->getInsertedIds();
    }
}