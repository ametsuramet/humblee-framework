<?php

namespace Amet\Humblee\Bases;

class Mail {

	private $subject;
	private $host;
	private $port;
	private $username;
	private $password;
	private $transport;
	private $from;
	private $type = "text/html";
	private $to;
	private $attach;
	private $message;
	function __construct($subject = "HUMBLEE MAILER")
	{
		global $config;
		$this->subject = $subject;
 		$this->host = $config['mail']['host'];
		$this->port = $config['mail']['port'];
		$this->username = $config['mail']['username'];
		$this->password = $config['mail']['password'];
  		$this->from();
	}

	public function from()
	{
		$this->from = [env('MAIL_FROM_ADDRESS') => env('MAIL_FROM_NAME')];
		return $this;
	}
	public function type($type)
	{
		$this->type = $type;
		return $this;
	}
	public function to($email, $name = "")
	{
		$this->to = [$email => $name];
		return $this;
	}
	public function attach($attach)
	{
		$this->attach = $attach;
		return $this;
	}
	public function message($message)
	{
		$this->message = $message;
		return $this;
	}

	public function send()
	{
		$transport =  (new \Swift_SmtpTransport($this->host, $this->port))->setUsername($this->username)
 		->setPassword($this->password);
		$mailer = new \Swift_Mailer($transport);
		$message = (new \Swift_Message($this->subject))->setFrom($this->from)->setTo($this->to)->setBody($this->message,$this->type);
		if ($this->attach) {
			$message = $message->attach(\Swift_Attachment::fromPath($this->attach));
		}
		$result = $mailer->send($message);
	}


}