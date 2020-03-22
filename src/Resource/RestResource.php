<?php

namespace TBoxPassepartout\Resource;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

use TBoxPassepartout\Entity;
use ZF\ApiProblem\ApiProblem;


class RestResource extends AbstractResource
{

	protected $uriParmas = [];

	public $Address;
	public $Port;
	public $Protocol = 'HTTP';
	public $Meta;

	private $_headers;

	private $_methodMapper = [
		'create'   => 'POST',
		'fetch'    => 'GET',
		'fetchAll' => 'GET',
		'delete'   => 'DELETE',
		'update'   => 'PUT',
		'patch'    => 'PATCH',
	];

	public function __construct($Headers)
	{
		$this->_headers = $Headers;
	}

	public function getMethod($name)
	{
		return $this->_methodMapper[$name];
	}

	public function __call($name, $params)
	{

		if(!isset($this->_headers) || empty($this->_headers)){
			throw new \Exception("No headers", 1);
		}

		// init client
		$client = new Client();
		// make request
		$body = $params; //maybe we will need to alter params for body
		$request = new Request($this->getMethod($name), $this->getAddress(), $this->_headers, $body);
		$response = $client->send($request, $this->getCallOptions());
		// validate code
		$code = $response->getStatusCode();
        if($code >= 200 && <= 299){
            return new ApiProblem($code, $response->getReasonPhrase());
        }
        // get response body
        $body = $response->getBody();
        // $content = (string) $body;
        $content = $body->getContents();
        $obj = json_decode($content, true);

        // check if collection according to HAL
        if(isset($obj['__embedded'])){
        	$entities = $obj['__embedded'];
        	foreach ($entities as $key => $value) {
	        	$entity = new Entity();
        		$entity->exchangeArray($value);
        		$entities[$key] = $entity;
	        }
	    // else assuming entity
        }else{
        	$entities = new Entity();
        	$entities->exchangeArray($obj);
        }

        return $entities;

	}

	public function setUriParams($Params)
	{
		// here consul will retrive regex for preg_replace in address
	}

	public function getAddress()
	{
		if(!empty($this->Port)){
			throw new \Exception("Not supported, put into the address directly", 1);
		}

		return $this->Address;
	}

	public function getCallOptions()
	{
		$opt = [];
		if(isset($this->Meta['CallOptions'])){
			$opt = $this->Meta['CallOptions'];
		}

		return $opt;
	}
}