<?php

namespace Amet\Humblee\Bases;
use Illuminate\Hashing\BcryptHasher as Bcrypt;
class Authentication {
    protected $baseModel = "\App\Models\User";
    protected $mode = "db";
    protected $defaultId = "id";
    protected $idColumn = "email";
    protected $secretColumn = "password";
    protected $show_column = [];


    public function setBaseModel($value) {
        $this->baseModel = $value;
    }
    public function setMode($value) {
        $this->mode = $value;
    }

    public function setDefaultId($value) {
        $this->defaultId = $value;
    }

    public function Attempt(array $credential,$show_column = []) {
        global $config;
        $this->show_column = $show_column;
        $user = $this->processAttempt($credential);
        
        if ($this->mode == "mongo" && $this->defaultId == "_id") {
            $token = JWTFactory::generateToken((string) new \MongoDB\BSON\ObjectID($user[$this->defaultId]));
        } else {
            $token = JWTFactory::generateToken($user[$this->defaultId]);
        }
        $session_factory = new \Aura\Session\SessionFactory;
        $session = $session_factory->newInstance($_COOKIE);
        $segment = $session->getSegment('Amet\Humblee');  
        $segment->set('auth_token',$token);
        header('Location: '.url().$config['app']["redirect"]);
    }

    public function ApiAttempt(array $credential,$show_column = []) {
        $this->show_column = $show_column;
        $user = $this->processAttempt($credential);
        if ($this->mode == "mongo" && $this->defaultId == "_id") {
            $token = JWTFactory::generateToken((string) new \MongoDB\BSON\ObjectID($user[$this->defaultId]));
            $user['token'] = $token;
            unset($user['password']);
            return $user;
        } 
        $token = JWTFactory::generateToken($user[$this->defaultId]);
        $user['token'] = $token;
        unset($user['password']);
        return $user;
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
            if (count($this->show_column)) {
                $user = $user->set_show_column($this->show_column);
            }
            $user = $user->set_show_relation(false)->where([$this->idColumn,"=",$credential[$this->idColumn]])->first();
        }
        
        if ($this->mode == "mongo") {
            if (count($this->show_column)) {
                $user = $user->set_show_column($this->show_column);
            }
            $user = $user->set_show_relation(false)->findOne([$this->idColumn => $credential[$this->idColumn]]);
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