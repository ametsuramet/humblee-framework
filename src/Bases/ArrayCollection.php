<?php

namespace Amet\Humblee\Bases;

class ArrayCollection
{
	private $data;


	function __construct($data = [])
	{
		if (! is_array($data)) {
			throw new \Exception("please insert array type");
			
		}
		$this->data = $data;
		return $this;
	}	

	public function map(\Closure $closure)
	{
		return collection(array_map(function ($d, $index) use ($closure) {
			    return $closure($d,$index);
			},  $this->data, array_keys($this->data)));
	}

	public function filter(\Closure $closure)
	{
		return collection(array_filter( $this->data, function ($d) use ($closure) {
					    return $closure($d);
				}));
	}

	public function except(Array $excludeKeys)
	{
		foreach($excludeKeys as $key){
	        unset($this->data[$key]);
	    }
	    return collection($this->data);
	}

	public function forget($key)
	{
	    unset($this->data[$key]);
	    return collection($this->data);
	}

	public function get($key)
	{
	    return $this->data[$key];
	}

	public function groupBy($key)
	{
		$result = array();
		foreach ($this->data as $data) {
		  $id = $data[$key];
		  if (isset($result[$id])) {
		     $result[$id][] = $data;
		  } else {
		     $result[$id] = array($data);
		  }
		}
	    return collection($result);
	}
	public function has($key)
	{
	    return isset($this->data[$key]) ? true : false;
	}

	public function implode()
	{
		$args = func_get_args();
		if (count($args) == 1) {
	    	return implode($args[0], $this->data);
		}

		if (count($args) == 2) {
	    	$result = array();
			foreach ($this->data as $data) {
			  $result[] = $data[$args[0]];
			}
			return implode($args[1], $result);
		}
	}

	public function forPage($page = 1,$limit)
	{
	    return collection(array_slice($this->data, ($page - 1) * $limit, $limit));
	}

	public function each(\Closure $closure)
	{
		return array_map(function ($d, $index) use ($closure) {
			    $closure($d,$index);
			},  $this->data, array_keys($this->data));
	}

	// public function sum(\Closure $closure = null)
	// {
	// 	return array_sum($this->data);
	// }

	public function avg()
	{
		return array_sum($this->data) / count($this->data);
	}

	public function chunk($chunk,$preserved_key = false)
	{
		return collection(array_chunk($this->data, $chunk, $preserved_key));
	}

	public function collapse()
	{
		$return = array();
	    array_walk_recursive($this->data, function($a) use (&$return) { $return[] = $a; });
	    return collection($return);
	}

	public function combine($data = [])
	{
	    return collection(array_combine($this->data, $data));
	}

	public function intersect($data = [])
	{
	    return collection(array_intersect($this->data, $data));
	}

	public function intersectKey($data = [])
	{
	    return collection(array_intersect_key($this->data, $data));
	}

	public function contains()
	{
		$args = func_get_args();
	    return count(array_intersect($this->data, $args)) ? true : false;
	}

	public function count()
	{
	    return count($this->data);
	}

	public function diff($data = [])
	{
	    return collection(array_diff($this->data,$data));
	}

	public function values()
	{
	    return collection(array_values($this->data));
	}
	public function flip()
	{
	    return collection(array_flip($this->data));
	}

	public function reverse()
	{
	    return collection(array_reverse($this->data));
	}

	public function search($key,$strict = false)
	{
	    return (array_search($key,$this->data,$strict));
	}

	public function shift()
	{
	    return collection(array_slice($this->data, 1, count($this->data)));
	}
	public function sort($reverse = false)
	{
		if ($reverse) {
	    	arsort($this->data);
		} else {
			asort($this->data);
		}
	    return collection($this->data);
	}

	public function sortBy($col, $desc = false)
	{
		$dir = SORT_ASC;
		if ($desc) {
			$dir = SORT_DESC;
		}
		$sort_col = array();
	    foreach ($this->data as $key=> $row) {
	        $sort_col[$key] = $row[$col];
	    }
	    array_multisort($sort_col, $dir, $this->data);
	    return collection($this->data);
	}

	public function slice($start,$length = 0)
	{
		if ($length) {
	    	return collection(array_slice($this->data, $start, $length));
		}
	    return collection(array_slice($this->data, $start));
	}

	public function take($length = 0)
	{
		if ($length < 0) {
			return collection(array_slice($this->data,$length,-$length));
		}
	    return collection(array_slice($this->data, 0,$length));
	}
	public function unique()
	{
	    return collection(array_unique($this->data));
	}

	public function splice($start,$length = 0)
	{
		if ($length) {
	    	return collection(array_splice($this->data, $start, $length));
		}
	    return collection(array_splice($this->data, $start));
	}

	public function zip(Array $array)
	{
		if (count($this->data) != count($array)) {
			return collection($this->data);
		}
	    return collection(array_combine($this->data,$array));
	}

	public function pop()
	{
	    return collection(array_slice($this->data, -count($this->data), count($this->data) - 1));
	}
	public function shuffle()
	{
		shuffle($this->data);
	    return collection($this->data);
	}

	public function toArray()
	{
		return $this->data;
	}

	public function isEmpty()
	{
		return count($this->data) ? true : false;
	}

	public function isNotEmpty()
	{
		return !count($this->data) ? true : false;
	}

	
	public function keyBy($key)
	{
		$result = array();
		foreach ($this->data as $data) {
			if (isset($data[$key]))
		  	$result[$data[$key]] = $data;
		}
		return collection($result);
	}

	public function median($key = null)
	{
		if (!$key) {
			return $this->count_median($this->data);
		}
		$result = array();
		foreach ($this->data as $data) {
			if (isset($data[$key]))
		  	$result[] = $data[$key];
		}
		if (count($result))
		return $this->count_median($result);
	}

	public function mode($key = null)
	{
		if (!$key) {
			return $this->count_mode($this->data);
		}
		$result = array();
		foreach ($this->data as $data) {
			if (isset($data[$key]))
		  	$result[] = $data[$key];
		}
		if (count($result))
		return $this->count_mode($result);
	}


	public function max($key = null)
	{
		if (!$key) {
			return max($this->data);
		}
		$result = array();
		foreach ($this->data as $data) {
			if (isset($data[$key]))
		  	$result[$data[$key]] = $data;
		}
		if (count($result))
		return max($result)[$key];
	}

	public function min($key = null)
	{
		if (!$key) {
			return min($this->data);
		}
		$result = array();
		foreach ($this->data as $data) {
			if (isset($data[$key]))
		  	$result[$data[$key]] = $data;
		}
		if (count($result))
		return min($result)[$key];
	}

	public function sum($key = null)
	{
		if (!$key) {
			return array_sum($this->data);
		}
		$result = array();
		foreach ($this->data as $data) {
			if (isset($data[$key]))
		  	$result[] = $data[$key];
		}
		if (count($result))
		return array_sum($result);
	}

	public function nth($key,$start = 0)
	{
		$result = array();
		foreach ($this->data as $i => $data) {
			if (($i-$start) % $key == 0)
		  	$result[] = $data;
		}
		return collection($result);
	}

	public function pluck($name,$key = null)
	{
		$result = array();
		foreach ($this->data as $i => $data) {
			if ($key)
		  		$result[$data[$key]] = $data[$name];
		  	else
		  		$result[] = $data[$name];
		}
		return collection($result);
	}

	public function only(Array $array)
	{
		$result = array();
		foreach ($this->data as $i => $data) {
			if (in_array($i, $array))
		  	$result[$i] = $data;
		}
		return collection($result);
	}

	public function serialize()
	{
		return serialize($this->data);
	}

	public function toJson()
	{
		return json_encode($this->data);
	}

	private function count_median($arr){
	    if($arr){
	        $count = count($arr);
	        sort($arr);
	        $mid = floor(($count-1)/2);
	        return ($arr[$mid]+$arr[$mid+1-$count%2])/2;
	    }
	    return 0;
	}
	private function count_mode($arr){
	    $values = array_count_values($arr); 
		return array_search(max($values), $values);
	}

}