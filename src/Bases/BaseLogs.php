<?php

namespace Amet\Humblee\Bases;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Formatter\LineFormatter;

class BaseLogs
{
	private $file_name = "logs/Humblee-Logs.log";
	private $level;
	private $message;
	function __construct($message,$level = "info")
	{
		global $config;
		if ($config['app']['log'] == "daily") {
			$this->file_name = "logs/Humblee-Logs-".date('Y-m-d').".log";
		}

		$this->level = $level;
		$this->message = $message;


		$this->handle();
	}


	private function handle()
	{
		global $config;
		$filename = storage_path($this->file_name);
		if (!file_exists($filename )) {
			file_put_contents($filename ,"");
		}
		$dateFormat = "Y-m-d G:i:s";
		$output = "[%datetime%] %channel%.%level_name%: %message% \n";
		$formatter = new LineFormatter($output, $dateFormat);

		$consoleHandler = new BrowserConsoleHandler();
		$stream = new StreamHandler($filename , Logger::DEBUG);
		$stream->setFormatter($formatter);

		$log = new Logger('humblee');
		$log->pushHandler($stream);
		if ($config['app']['APP_ENV'] != "production") {
			$log->pushHandler($consoleHandler);
		}

		$log->{$this->level}($this->message);
	}
}