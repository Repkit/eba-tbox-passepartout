<?php

namespace TBoxPassepartout;

use TBoxPassepartout\Entity;

class Config extends Entity
{
	public function exchangeArray(array $Input)
    {
    	if(!isset($Input['head']) || empty($Input['head'])){
        	throw new \RuntimeException("Invalid configuration", 1);
        }
        if(!isset($Input['key']) || empty($Input['key'])){
        	throw new \RuntimeException("Invalid configuration", 1);
        }
        $this->head = new Entity();
        $this->head->exchangeArray($Input['head']);
        unset($Input['head']);

        $this->key = new Entity();
        $this->key->exchangeArray($Input['key']);
        unset($Input['key']);

        return parent::exchangeArray($Input);
    }
    
	public function validate()
    {
        $head = $this->head;
        if(!isset($head) || empty($head)){
        	throw new \RuntimeException("Invalid configuration", 1);
        }
        if(!isset($head['auth']) || empty($head['auth'])){
        	throw new \RuntimeException("Invalid configuration", 1);
        }

        $key = $this->key;
        if(!isset($key) || empty($key)){
        	throw new \RuntimeException("Invalid configuration", 1);
        }
        if(!isset($key['secret']) || empty($key['secret'])){
        	throw new \RuntimeException("Invalid configuration", 1);
        }
        if(!isset($key['ttl']) || empty($key['ttl'])){
        	throw new \RuntimeException("Invalid configuration", 1);
        }

        return true;
    }
}