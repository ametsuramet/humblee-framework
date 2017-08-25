<?php

namespace Amet\Humblee\Bases;

class BaseModel
{
    protected $table = "";
    protected $default_key = "id";
    protected $soft_delete = false;
    protected $show_column = [];
    private static $instance;
    private $where_params = [];
    private $data_raw = [];
    public $data = [];
    private $relation_attributes = [];
    private $hasMany_attributes = [];
    private $limit_attributes = [];
    private $hasOne_attributes = [];
    private $manyToMany_attributes = [];
    private $order_attributes = [];
    private $value_default_key = null;
    private $group_attributes = null;
    public $query_string = null;
    public $enable_log = false;
    public $show_deleted = false;
    public $show_deleted_only = false;
    public $public_function = ['where','limit','deleted_only','deleted','info','orderBy','groupBy','insert','update','delete','get','first','find','last','paginate','set_show_column'];
    
    function __construct()
    {
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

    public function set_show_column($value = [])
    {
        $this->show_column = $value;
        return $this;
    }
    public function where($params)
    {
        $wheres = [];
        if (!is_array($params[0])) {
            if (count($params) == 3) {
                $wheres[] = $params;
            } else {
                $wheres[] = [$params[0],"=",$params[1]];
            }

        } else {
            foreach ($params as $key => $param) {

                if (is_array($param)) {
                    if (count($param) == 3) {
                        $wheres[] = $param;
                    } else {
                        $wheres[] = [$param[0],"=",$param[1]];
                    }
                }
            }
        }

        $this->where_params = $wheres;
        return $this;
    }

    public function limit($param1,$param2 = null)
    {
        if ($param2) {
            $this->limit_attributes = [$param1,$param2];    
        } else {
            $this->limit_attributes = [0,$param1];
        }
        return $this;
    }

    public function deleted_only()
    {
        $this->show_deleted_only = true;
        return $this;
    }

    public function deleted($value)
    {
        $this->show_deleted = $value;
        return $this;
    }

    public function info()
    {
        // $class_name = get_class($this);
        // $class   = new \ReflectionClass($class_name);
        // $methods = $class->getMethods();

        // $that = $this;
        // $this->functions = $methods;
        return $this;
    }

    public function orderBy($param1,$param2 = 'asc')
    {
        if (is_array($param1)) {
            foreach ($param1 as $key => $order) {
                $this->order_attributes[] = [$order[0],$order[1]];
            }
        } else {
            $this->order_attributes[] = [$param1,$param2];
        }
        return $this;
    }

    public function groupBy($param1)
    {

        if (is_array($param1)) {
            foreach ($param1 as $key => $group) {
                $group_attributes[] = $group;
            }
        } else {
            $group_attributes[] = $param1;
        }

        $this->group_attributes = implode(',', $group_attributes);
        return $this;
    }

    protected function manyToMany($table,$column,$parent_column,$relation_name = null, $pivot_table,$show_column = [])
    {
        if (!$relation_name) {
            $relation_name = $table;
        }
        $table_alias = $table.'_'.$relation_name;
        $this->relation_attributes[$relation_name] = ['name' => $relation_name, 'type' => 'many_to_many', 'alias' => $table_alias, 'pivot_table' => $pivot_table, 'show_column' => $show_column];
        $this->manyToMany_attributes[$relation_name] = [$table,$column,$parent_column,$relation_name,$table_alias,$pivot_table,$show_column];
    }

    protected function hasMany($table,$column,$parent_column,$relation_name = null,$show_column = [])
    {
        if (!$relation_name) {
            $relation_name = $table;
        }
        $table_alias = $table.'_'.$relation_name;
        $this->relation_attributes[$relation_name] = ['name' => $relation_name, 'type' => 'many', 'alias' => $table_alias, 'show_column' => $show_column];
        $this->hasMany_attributes[$relation_name] = [$table,$column,$parent_column,$relation_name,$table_alias,$show_column];
    }

    protected function hasOne($table,$column,$parent_column,$relation_name = null,$show_column = [])
    {
        if (!$relation_name) {
            $relation_name = $table;
        }

        $table_alias = $table.'_'.$relation_name;

        $this->relation_attributes[$relation_name] = ['name' => $relation_name, 'type' => 'one', 'alias' => $table_alias, 'show_column' => $show_column];
        $this->hasOne_attributes[$relation_name] = [$table,$column,$parent_column,$relation_name,$table_alias,$show_column];
    }

    private function getColumn($table,$relation_name = null,$show_column = [])
    {
        if (!$relation_name) {
            $relation_name = $table;
        }
        $columns = db()->select("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '".env('DB_DATABASE')."' AND `TABLE_NAME`= '".$table."'");
        
        if (count($show_column)) {
            $columns = collect($columns)->filter(function($data) use ($show_column) {
                if (in_array($data->COLUMN_NAME, $show_column))
                return $data;
            })->toArray();
        }


        $data_colums = array_map(function($data) use ($table,$relation_name) {
                return $relation_name.'.'.$data->COLUMN_NAME. ' as '.$relation_name.'_'.$data->COLUMN_NAME ; 
        }, $columns);
        return $data_colums;
    }

    private function query()
    {
        // if ($this->enable_log)
        // db()->enableQueryLog();
        $select_params = [];
 
        $db = db();

        if (count($this->show_column)) {
            $db = $db->select($this->show_column);
        } else {
            $db = $db->select();
        }

        $db = $db->from($this->table);
        foreach ($this->where_params as $key => $where) {
            $db = $db->where($where[0],$where[1],$where[2]);
        }
        
        foreach ($this->order_attributes as $key => $order_attribute) {
            $db = $db->orderBy($order_attribute[0],$order_attribute[1]);
        }

        if (count($this->limit_attributes)) {
            $db = $db->limit($this->limit_attributes[1],$this->limit_attributes[0]);
        }

        if ($this->value_default_key) {
            $db = $db->where($this->default_key,'=',$this->value_default_key)->limit(1);
        }

        if ($this->soft_delete) {
            if ($this->show_deleted_only) {
                    $db = $db->where('deleted_at','!=', 'null');
            } else {
                if (!$this->show_deleted) {
                    $db = $db->where('deleted_at','=',null);
                }
            }
        }

        if ($this->group_attributes) {
            $db = $db->groupBy(db()->raw($this->group_attributes));
        }

        


        $db = $db->execute()->fetchAll();

        $this->data_raw = $db;
        // if ($this->enable_log)
        // $this->query_string = (db()->getQueryLog());
    }

    private function sum_all_data()
    {
        $select_params = [];
        $db = db();

        
        $db = $db->select(['count('.$this->default_key.') as '.$this->default_key]);
        $db = $db->from($this->table);
        foreach ($this->where_params as $key => $where) {
            $db = $db->where($where[0],$where[1],$where[2]);
        }
        
        foreach ($this->order_attributes as $key => $order_attribute) {
            $db = $db->orderBy($order_attribute[0],$order_attribute[1]);
        }

        if (count($this->limit_attributes)) {
            $db = $db->limit($this->limit_attributes[1],$this->limit_attributes[0]);
        }

        if ($this->value_default_key) {
            $db = $db->where($this->default_key,"=",$this->value_default_key)->limit(1);
        }

        if ($this->soft_delete) {
            if ($this->show_deleted_only) {
                    $db = $db->whereNotNull('deleted_at');
            } else {
                if (!$this->show_deleted) {
                    $db = $db->where('deleted_at',null);
                }
            }
        }

        if ($this->group_attributes) {
            $db = $db->groupBy(db()->raw($this->group_attributes));
        }

            


        $db = $db->execute()->fetch();
        return current($db);
    }

    public function paginate($limit)
    {
        $sum_data = $this->sum_all_data();
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
        $data_pagination = [
            'total' => $sum_data,
            "per_page" => $limit,
            "offset" => $offset,
            "current_page" => (int) $currentPage,
            "last_page" => $last_page,
            "next_page_url" => $currentPage == $last_page ? null : url().request()->server->get('PATH_INFO')."?page=".$next_page,
            "prev_page_url" => $currentPage == 1 ? null : url().request()->server->get('PATH_INFO')."?page=".$prev_page,
            "from" => $from,
            "to" => $to,
        ];
        $this->limit_attributes = [$offset,$limit];
        $this->execute();

        // $searchResults = $this->data;
        // $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // $collection = new Collection($searchResults);

        // $perPage = $limit;

        // $currentPageSearchResults = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // $paginatedSearchResults= new LengthAwarePaginator($currentPageSearchResults, count($collection), $perPage);
        // $paginatedSearchResults->setPath(request()->url());
        // $data_paginate = $paginatedSearchResults->toArray();
        $data_pagination['data'] = $this->data;
        return $data_pagination;
    }

    public function insert($data)
    {
        $uuid = \UUID::generate();
        $data['uuid'] = (string)$uuid;
        db()->into($this->table)->values($data)->execute();
        $this->value_default_key = (string)$uuid;
        $this->execute();
        return current($this->data);
    }

    public function update($id,$data)
    {
        db()->from($this->table)
            ->update($data)
            ->where($this->default_key, $id)->execute();
        $this->value_default_key = $id;
        $this->execute();
        return current($this->data);
    }

    public function delete($id)
    {
        db()->delete()->from($this->table)->where($this->default_key, $id)->execute();
    }

    public function get()
    {
        $this->execute();
        return $this->data;
    }

    // public function getQueryLog()
    // {
    //     $this->enable_log = true;
    //     $this->execute();
    //     return $this->query_string;
        
    // }

    public function first()
    {
        $this->limit(1);
        $this->execute();
        return current($this->data);
    }

    public function find($value_default_key)
    {
        $this->value_default_key = $value_default_key;
        $this->execute();
        return current($this->data);
    }

    public function last()
    {
        $this->execute();
        return end($this->data);
    }

    private function execute()
    {
        $this->query();

        $this->mutation_data();

    }

    private function mutation_data()
    {
        $data = [];

        foreach ($this->data_raw as $j => $data_raw) {
            foreach ($data_raw as $k => $value) {
                $data[$j][$k] = $value;
                
                // foreach ($this->relation_attributes as $l => $relation_attribute) {
                //  if ($relation_attribute['type'] == "one") {
                //      $pattern = '/'.$relation_attribute['alias'].'_/';
                //      if (preg_match($pattern,$k)) {
                //          $data[$j][$relation_attribute['alias']][str_replace($relation_attribute['alias'].'_', '', $k)] = $value;
                //      }
                //  } 
                // }

            }

            foreach ($this->relation_attributes as $l => $relation_attribute) {
                if ($relation_attribute['type'] == "many") {
                    foreach ($this->hasMany_attributes as $key => $attribute) {
                        if ($relation_attribute['alias'] == $attribute[4]) {
                            $db = db()->from($attribute[0])->where($attribute[1],$data_raw->{$attribute[2]});
                            if (count($attribute[5])) {
                                $db = $db->select(db()->raw(implode(',', $attribute[5])));
                            }
                            $db = $db->get();
                            $data[$j][$relation_attribute['name']] = $db->toArray();
                        }
                    }
                } 

                if ($relation_attribute['type'] == "one") {
                    foreach ($this->hasOne_attributes as $key => $attribute) {
                        if ($relation_attribute['alias'] == $attribute[4]) {
                            $db = db()->from($attribute[0])->where($attribute[1],$data_raw->{$attribute[2]});
                            if (count($attribute[5])) {
                                $db = $db->select(db()->raw(implode(',', $attribute[5])));
                            }
                            $db = $db->first();
                            $data[$j][$relation_attribute['name']] = (array) $db;
                        }
                    }
                } 

                if ($relation_attribute['type'] == "many_to_many") {
                    foreach ($this->manyToMany_attributes as $key => $attribute) {
                        if ($relation_attribute['alias'] == $attribute[4]) {
                            // print_r($attribute);
                            // print_r($data_raw);
                            
                            // print_r(implode(',', $select));
                            $db = db()->from($attribute[5][0])->where($attribute[5][1],$data_raw->{$attribute[2]})
                                    ->leftJoin($attribute[0] .' AS '.$attribute[4],$attribute[4].'.'.$attribute[1] ,'=', $attribute[5][0].'.'.$attribute[5][2]);
                            if (count($attribute[5])) {
                                $select = $this->getColumn($attribute[0],$attribute[4],$attribute[6]);
                            } else {
                                $select = $this->getColumn($attribute[0],$attribute[4]);
                            }
                                $db = $db->select(db()->raw(implode(',', $select)));

                            $db = $db->get();
                            // print_r($db);
                            foreach ($db as $l => $dataManyToMany) {
                                foreach ($dataManyToMany as $m => $value) {
                                    $pattern = '/'.$relation_attribute['alias'].'_/';
                                    if (preg_match($pattern,$m)) {
                                        $data[$j][$attribute[3]][$l][str_replace($relation_attribute['alias'].'_', '', $m)] = $value;
                                    }
                                }
                            }
                        }
                    }
                } 
            }
            
        }
        // dd($data);   
        $this->data = $data;
    }
}
