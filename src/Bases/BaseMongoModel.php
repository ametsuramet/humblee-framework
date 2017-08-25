<?php

namespace Amet\Humblee\Bases;

class BaseMongoModel
{
	protected $database = "";
	protected $collection = "";
    protected $show_column = [];

	private $showID = true;

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

    public function createCollection($name,$collation)
    {
    	$database = mongo()->{$this->database};
    	$database->createCollection($name, [
		    'collation' => $collation,
		]);


    }


    public function set_show_column($values = [])
    {
    	foreach ($values as $key => $value) {
        	$this->show_column[$value] = 1;
    	}
        return $this;
    }

    public function createIndex($array_index,$options = [])
    {
    	return $this->collection()->createIndex($array_index,$options);
    }

    private function collection()
    {
    	$db = mongo()->{$this->database}->{$this->collection};
    	return $db;
    }

    public function find($params = [], $options = [])
    {
    	if (count($this->show_column)) {
    		$show_column = [];
    		$options['projection'] = $this->show_column;
    	}
        return $this->query($params,$options);
    }

    private function query($params = [], $options = [])
    {
    	$collection = $this->collection()->find($params, $options);
    	$data = [];
		foreach ($collection as $key => $document) {
			if (!$this->showID) {
				unset($document['_id']);
			}
		    $data[] =  $document;
		}

        return $data;
    }

   	public function replaceOne($params,$value)
   	{
   		$updateResult = $this->collection()->replaceOne(
		    $params,
		    $value
		);

		return [
			"Matched" => $updateResult->getMatchedCount(),
			"Modified" => $updateResult->getModifiedCount()
		];
   	}
   	public function updateOne($params,$value)
   	{
   		$updateResult = $this->collection()->updateOne(
		    $params,
		    ['$set' => $value]
		);

		return [
			"Matched" => $updateResult->getMatchedCount(),
			"Modified" => $updateResult->getModifiedCount()
		];
   	}

   	public function updateMany($params,$value)
   	{
   		$updateResult = $this->collection()->updateMany(
		    $params,
		    ['$set' => $value]
		);

		return [
			"Matched" => $updateResult->getMatchedCount(),
			"Modified" => $updateResult->getModifiedCount()
		];
   	}

    public function deleteOne($params)
    {
    	$deleteResult = $this->collection()->deleteOne($params);
        return ["Deleted" => $deleteResult->getDeletedCount()];
    }
    
    public function deleteMany($params)
    {
    	$deleteResult = $this->collection()->deleteMany($params);
        return ["Deleted" => $deleteResult->getDeletedCount()];
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

    public function setShowId($value)
    {
    	$this->showID = $value;
    }
}