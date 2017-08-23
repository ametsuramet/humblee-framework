<?php

namespace Amet\Humblee\Bases;

use \Firebase\JWT\JWT;

class JWTFactory {
	public static function generateToken($id)
	{
		global $config;

		$key = $config['jwt']['key'];
		$token = array(
		    "sub" => $id,
		    "iss" => url(),
		    "aud" => url(),
		    "iat" => time(),
		    "exp" => time() + $config['jwt']['expired']
		);
		$jwt = JWT::encode($token, $key);
		return $jwt;
	}
}