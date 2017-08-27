<?php

namespace Amet\Humblee\Bases;
use Illuminate\Hashing\BcryptHasher as Bcrypt;
class Authentication {
    protected $baseModel = "\App\Models\User";
    protected $mode = "db";
    protected $defaultId = "id";
    protected $idColumn = "email";
    protected $secretColumn = "password";


    public function setBaseModel($value) {
        $this->baseModel = $value;
    }
    public function setMode($value) {
        $this->mode = $value;
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
        if ($this->mode == "db") {
            $user = $user->where([$this->idColumn,"=",$credential[$this->idColumn]])->first();
        }
        
        if ($this->mode == "mongo") {
            $user = $user->findOne([$this->idColumn => $credential[$this->idColumn]]);
        }
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