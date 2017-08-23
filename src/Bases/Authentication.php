<?php

namespace Amet\Humblee\Bases;
use Illuminate\Hashing\BcryptHasher as Bcrypt;
class Authentication {
    protected $baseModel = "\App\Models\User";
    protected $defaultId = "id";
    protected $idColumn = "email";
    protected $secretColumn = "password";


    public function setModel($value) {
        $this->baseModel = $value;
    }

    public function Attempt(array $credential) {
        global $config;

        $user = $this->processAttempt($credential);
        $token = JWTFactory::generateToken($user[$this->defaultId]);
        $session_factory = new \Aura\Session\SessionFactory;
        $session = $session_factory->newInstance($_COOKIE);
        $segment = $session->getSegment('Amet\Humblee');  
        $segment->set('auth_token',$token);
        header('Location: '.url().$config['app']["redirect"]);
    }

    public function ApiAttempt(array $credential) {
        $user = $this->processAttempt($credential);
        return JWTFactory::generateToken($user[$this->defaultId]);
    }

    private function processAttempt($credential)
    {
        if (!in_array($this->idColumn, array_keys($credential))) {
            throw new \Exception($this->idColumn." key not exist");
        }

        if (!in_array($this->secretColumn, array_keys($credential))) {
            throw new \Exception($this->secretColumn." key not exist");
        }

        $user = new $this->baseModel();
        //check user exists
        $user = $user->where([$this->idColumn,"=",$credential[$this->idColumn]])->first();
        if (!$user) {
            throw new \Exception("User with ".$this->idColumn.": ".$credential[$this->idColumn]." not exist");
        }

        $hash = new Bcrypt;
        if (!$hash->check($credential[$this->secretColumn],$user[$this->secretColumn])) {
            throw new \Exception($this->secretColumn." not matched");
        }

        return $user;
    }

	
}