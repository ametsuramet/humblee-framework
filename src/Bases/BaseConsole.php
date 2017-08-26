<?php

namespace Amet\Humblee\Bases;

class BaseConsole {
	protected $description = "";
	protected $arguments = [];

	function __construct()
    {
    	$this->arguments = func_get_args()[0];
    	

    	if (in_array("--help", $this->arguments )) {
    		$this->info($this->description);
    	}

    	call_user_func(array($this, 'boot'));
    	call_user_func(array($this, 'handle'));
    	
    }

    protected function info($message)
    {
    	echo "[32;22m".$message."[0m\n";
    }

    protected function warning($message)
    {
    	echo "[33;2m".$message."[0m\n";
    }

    protected function debug($message)
    {
    	echo "[32;2m".$message."[0m\n";
    }

    protected function alert($message)
    {
    	echo "[91;1m".$message."[0m\n";
    }

    protected function emergency($message)
    {
    	echo "[45;1;5m".$message."[0m\n";
    }

    protected function critical($message)
    {
    	echo "[31;22m".$message."[0m\n";
    }

}