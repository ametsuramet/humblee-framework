<?php

namespace Amet\Humblee\Bases;
use MongoDB\BSON\ObjectID;
class BaseMongoModel
{
    protected $database = "";
    protected $collection = "";
    protected $show_relation = true;
    protected $show_column = [];
    protected $fillable = [];
    private $hasMany_attributes = [];
    private $hasOne_attributes = [];
    private $manyToMany_attributes = [];
    private $relation_attributes = [];


    private $showID = true;

    function __construct()
    {
        // if (!extension_loaded("mongo")) {
        //  throw new \Exception("MongoDB Driver not loaded");
        // }

        global $config;
        $db_config = $config['mongo']['db'][env("MONGO_CONNECTION","development")];

        $this->database = $db_config['database'];
        $class_name = get_class($this);
        $class   = new \ReflectionClass($class_name);
        $methods = $class->getMethods();
        foreach ($methods as $key => $method) {
            if ($method->name != "__construct" && 
                $method->class == $class_name && 
                preg_match("/(^relation_)/", $method->name)) {
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
    public function set_show_relation($value)
    {
        if (!$value) {
            $this->hasMany_attributes = [];
            $this->hasOne_attributes = [];
            $this->manyToMany_attributes = [];
            $this->relation_attributes = [];
            $this->show_relation = $value;
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

        return $this->mutation_data($data);
    }

    private function mutation_data($data_raw)
    {
        $new_data = $data_raw;
        if (count($this->relation_attributes)) {
            $data = [];

            foreach ($data_raw as $j => &$d_raw) {
                foreach ($d_raw as $k => $value) {
                    $data[$j][$k] = $value;
                }

                foreach ($this->relation_attributes as $l => $relation_attribute) {
                    if ($relation_attribute['type'] == "many") {
                        foreach ($this->hasMany_attributes as $key => $attribute) {
                            if ($relation_attribute['alias'] == $attribute[4]) {
                                $db = (new $attribute[0]);
                                $db->set_show_relation(false);
                                if (count($attribute[5])) {
                                    $db = $db->set_show_column($attribute[5]);
                                }
                                if ($attribute[2] == "_id") {
                                    $d_raw[$attribute[2]] = (string) new ObjectID($d_raw[$attribute[2]]);
                                }
                                $find = [$attribute[1]=>$d_raw[$attribute[2]]];
                                $find = array_merge($find,$attribute[6]);
                                $db = $db->find($find);
                                $data[$j][$relation_attribute['name']] = $db;
                            }
                        }
                    } 

                    if ($relation_attribute['type'] == "one") {
                        foreach ($this->hasOne_attributes as $key => $attribute) {
                            if ($relation_attribute['alias'] == $attribute[4]) {
                                $db = (new $attribute[0]);
                                $db->set_show_relation(false);
                                if (count($attribute[5])) {
                                    $db = $db->set_show_column($attribute[5]);
                                }
                                if ($attribute[2] == "_id") {
                                    $d_raw[$attribute[2]] = (string) new ObjectID($d_raw[$attribute[2]]);
                                }
                                $find = [$attribute[1]=>$d_raw[$attribute[2]]];
                                $find = array_merge($find,$attribute[6]);
                                $db = $db->findOne($find);
                                $data[$j][$relation_attribute['name']] = $db;
                            }
                        }
                    } 

                    if ($relation_attribute['type'] == "many_to_many") {
                        foreach ($this->manyToMany_attributes as $key => $attribute) {
                            if ($relation_attribute['alias'] == $attribute[4]) {
                               
                                if ($attribute[2] == "_id") {
                                    $d_raw[$attribute[2]] = (string) new ObjectID($d_raw[$attribute[2]]);
                                }
                                
                                $pivots = (new $attribute[5][0])->find([$attribute[5][1] => $d_raw[$attribute[1]]]);


                                if ($attribute[1] == "_id") {
                                    $d_raw[$attribute[1]] = new ObjectID($d_raw[$attribute[1]]);
                                }
                                $db = [];                                
                                foreach ($pivots as $key => $pivot) {
                                    $get_data = (new $attribute[0]);
                                    $get_data->set_show_relation(false);
                                    if (count($attribute[6])) {
                                        $get_data = $get_data->set_show_column($attribute[6]);
                                    }
                                    $find = [$attribute[2] => $pivot[$attribute[5][2]]];
                                    $find = array_merge($find,$attribute[7]);
                                    $get_data = $get_data->findOne($find);
                                    $db[] = $get_data;
                                }
                                // print_r($attribute);
                                $data[$j][$relation_attribute['name']] = $db;

                                
                            }
                        }
                    } 
                }
                
            }

            $new_data = $data;
        }
        return $new_data;
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

    protected function manyToMany($table,$column,$parent_column,$relation_name = null, $pivot_table,$show_column = [],$where = [])
    {
        if ($this->show_relation) {
            if (!$relation_name) {
                $relation_name = $table;
            }
            $table_alias = $table.'_'.$relation_name;
            $this->relation_attributes[$relation_name] = ['name' => $relation_name, 'type' => 'many_to_many', 'alias' => $table_alias, 'pivot_table' => $pivot_table, 'show_column' => $show_column, 'where' => $where];
            $this->manyToMany_attributes[$relation_name] = [$table,$column,$parent_column,$relation_name,$table_alias,$pivot_table,$show_column,$where];
        }
    }

    protected function hasMany($table,$column,$parent_column,$relation_name = null,$show_column = [],$where = [])
    {
        if ($this->show_relation) {
            if (!$relation_name) {
                $relation_name = $table;
            }
            $table_alias = $table.'_'.$relation_name;
            $this->relation_attributes[$relation_name] = ['name' => $relation_name, 'type' => 'many', 'alias' => $table_alias, 'show_column' => $show_column, 'where' => $where];
            $this->hasMany_attributes[$relation_name] = [$table,$column,$parent_column,$relation_name,$table_alias,$show_column,$where];
        }
    }

    protected function hasOne($table,$column,$parent_column,$relation_name = null,$show_column = [],$where = [])
    {
        if ($this->show_relation) {
            if (!$relation_name) {
                $relation_name = $table;
            }

            $table_alias = $table.'_'.$relation_name;

            $this->relation_attributes[$relation_name] = ['name' => $relation_name, 'type' => 'one', 'alias' => $table_alias, 'show_column' => $show_column, 'where' => $where];
            $this->hasOne_attributes[$relation_name] = [$table,$column,$parent_column,$relation_name,$table_alias,$show_column,$where];
        }
    }

    private function checkFillable($params) {
        if (count($this->fillable)) {
            if (isset($params[0])) {
                foreach ($params as $key => &$param) {
                    $flip = array_flip($this->fillable);
                    $intersect = array_intersect_key($param,$flip );

                    foreach ($flip  as $key => &$f) {
                        $f = isset($intersect[$key]) ? $intersect[$key] : NULL;
                        if(is_numeric($f))  $f = intval($f);
                        if($f == "1")  $f = 1;
                        if($f == "0")  $f = 0;
                        if($f == "")  $f = null;
                    }
                    $param = $flip;
                }
            } else {
                $flip = array_flip($this->fillable);
                $intersect = array_intersect_key($params,$flip );
                foreach ($flip  as $key => &$f) {
                    $f = isset($intersect[$key]) ? $intersect[$key] : NULL;
                    if(is_numeric($f))  $f = intval($f);
                    if($f == "1")  $f = 1;
                    if($f == "0")  $f = 0;
                    if($f == "")  $f = null;
                }
                $params = $flip;
            }
        }
        return $params;
    }
}