<?php

namespace Amet\Humblee\Bases;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Formatter\LineFormatter;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;
use \Bramus\Monolog\Formatter\ColorSchemes\TrafficLight;
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
		// date_default_timezone_set("Asia/Jakarta");

		$dateFormat = "Y-m-d H:i:s";
		$output = "[".date($dateFormat)."] (%channel%) %level_name%: %message% \n";
		// $formatter = new LineFormatter($output, $dateFormat);

		$consoleHandler = new BrowserConsoleHandler();
		$stream = new StreamHandler($filename , Logger::DEBUG);
		// $stream->setFormatter($formatter);
		$stream->setFormatter(new ColoredLineFormatter(new TrafficLight(),$output));

		$log = new Logger('humblee');
		$log->pushHandler($stream);
		$log->useMicrosecondTimestamps(false);
		$log->setTimezone(new \DateTimeZone("Asia/Jakarta"));
		if ($config['app']['APP_ENV'] != "production") {
			$log->pushHandler($consoleHandler);
		}

		$log->{$this->level}($this->message);
	}
}