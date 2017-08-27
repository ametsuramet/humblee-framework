<?php

namespace Amet\Humblee\Bases;
use MongoDB\BSON\ObjectID;
class BaseMongoModel
{
	protected $database = "";
	protected $collection = "";
    protected $show_column = [];
    protected $fillable = [];
    private $hasMany_attributes = [];
    private $hasOne_attributes = [];
    private $manyToMany_attributes = [];


	private $showID = true;

	function __construct()
    {
    	// if (!extension_loaded("mongo")) {
    	// 	throw new \Exception("MongoDB Driver not loaded");
    	// }

    	global $config;
		$db_config = $config['mongo']['db'][env("MONGO_CONNECTION","development")];

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
        if (array_key_exists("_id", $params)) {
            $params['_id'] = new ObjectID(  $params['_id'] );
        }
        // print_r($params);die();
    	if (count($this->show_column)) {
    		$show_column = [];
    		$options['projection'] = $this->show_column;
    	}
        return $this->query($params,$options);
    }

    public function paginate($params = [], $options = [],$limit = 20)
    {
        if (array_key_exists("_id", $params)) {
            $params['_id'] = new ObjectID(  $params['_id'] );
        }

        if (count($this->show_column)) {
            $show_column = [];
            $options['projection'] = $this->show_column;
        }


        $sum_data = $this->count($params,$options);
        $currentPage = request()->get('page',1);
        if (!$currentPage) {
            $currentPage = 1;
        }

        $last_page = ceil($sum_data/$limit);
        $prev_page = null;
        $next_page = null;
        if ($currentPage !=1) {
            $prev_page = $currentPage - 1;
        }
        if ($currentPage != $last_page) {
            $next_page = $currentPage + 1;
        }

        $from = ($currentPage - 1) * $limit + 1;
        $to = $currentPage == $last_page ? (($currentPage - 1) * $limit)+($sum_data%$limit) : $currentPage * $limit;
        $offset = ($currentPage - 1) * $limit;
        // print_r(request()->server);
        $options['skip'] = $offset;
        $options['limit'] = $limit;

        $data_pagination = [
            'total' => $sum_data,
            "per_page" => $limit,
            // "offset" => $offset,
            "current_page" => (int) $currentPage,
            "last_page" => $last_page,
            "next_page_url" => $currentPage == $last_page ? null : url().request()->server->get('PATH_INFO')."?page=".$next_page,
            "prev_page_url" => $currentPage == 1 ? null : url().request()->server->get('PATH_INFO')."?page=".$prev_page,
            "from" => $from,
            "to" => $to,
            "data" => $this->query($params,$options),
        ];
        return $data_pagination;
        
    }

    public function findOne($params = [], $options = [])
    {
        if (array_key_exists("_id", $params)) {
            $params['_id'] = new ObjectID(  $params['_id'] );
        }
        // print_r($params);die();
        if (count($this->show_column)) {
            $show_column = [];
            $options['projection'] = $this->show_column;
        }
        $options["limit"] = 1;
        return current($this->query($params,$options));
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

    private function count($params = [], $options = [])
    {
        return $this->collection()->count($params, $options);
    }

   	public function replaceOne($params,$value)
   	{
        $params = $this->checkFillable($params);
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
        $params = $this->checkFillable($params);
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
        $params = $this->checkFillable($params);
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
        $params = $this->checkFillable($params);
    	$insertOneResult = $this->collection()->insertOne($params);
        return $insertOneResult->getInsertedId();
    }

    public function insertMany($params)
    {
        $params = $this->checkFillable($params);
    	$insertManyResult = $this->collection()->insertMany($params);
        return $insertManyResult->getInsertedIds();
    }

    public function setShowId($value)
    {
    	$this->showID = $value;
    }

    private function checkFillable($params) {
        if (count($this->fillable)) {
            if (isset($params[0])) {
                foreach ($params as $key => &$param) {
                    $flip = array_flip($this->fillable);
                    $intersect = array_intersect_key($param,$flip );
                    foreach ($flip  as $key => &$f) {
                        $f = isset($intersect[$key]) ? $intersect[$key] : NULL;
                    }
                    $param = $flip;
                }
            } else {
                $flip = array_flip($this->fillable);
                $intersect = array_intersect_key($params,$flip );
                foreach ($flip  as $key => &$f) {
                    $f = isset($intersect[$key]) ? $intersect[$key] : NULL;
                }
                $params = $flip;
            }
        }
        return $params;
    }
}