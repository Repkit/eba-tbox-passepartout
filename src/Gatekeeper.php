<?php

namespace TBoxPassepartout;

// use TBoxPassepartout\Storage\ContainerInterface;
// use TBoxPassepartout\Storage\Container;

use Psr\Http\Message\RequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use GuzzleHttp\Psr7\Request;

use TBoxPassepartout\Service\Passepartout;
use TBoxPassepartout\Resource;

class Gatekeeper
{
	const CONFIG_KEY = 'passepartout_config';

	private $_logger;
	private $_container;
	private $_config;
	private $_originator;

	//only target
	private static $_request = [];

	public function __construct($Originator, ContainerInterface $Container = null, LoggerInterface $Logger = null)
	{
		if(!$this->allowOriginator($Originator)){
			throw new \InvalidArgumentException("$Originator is not allowed", 1);
		}

		$this->_originator = $Originator;
		$this->_container = $Container;

		if(empty($Logger)){
			$this->_logger = new NullLogger();
		}

		$this->init();
	}

	/*
	 * @Request instanceof Psr\Http\Message\RequestInterface or Zend\Stdlib\RequestInterface
	 * return boolean
	 */
	public function validate($ServiceName, $Request = null)
	{
		try 
		{
			$isLocalCall = false;

			// get headers
			if(!empty($Request)){

				$headers = $Request->getHeaders();
				if(is_object($headers)){
					$headers = $headers->toArray();
				}

			}elseif(!empty( static::$_request[$ServiceName] )){

				$headers = static::$_request[$ServiceName]->getHeaders();
				// mark as local call
				$isLocalCall = true;

			}else{

				$headers = getallheaders();

			}

			if (!is_array($headers) ){
				if( !$headers instanceof of \ArrayAccess ) {
					throw new \Exception("Error Processing Request", 1);
				}
			}

			if(!isset($headers[$this->_config->head['auth']])){
				throw new \Exception("Error Processing Request", 1);
			}

			$header = $headers[$this->_config->head['auth']];

			$rhash = $header[0];
			
	        $ttl = (int)$this->_config->key['ttl'];
	        
	        //validate ip only if not local call
	        if(!$isLocalCall){
	        	$allowedIps = $this->_config->whitelist;
		        if (!empty($allowedIps) ) {
		            $remoteIp = \RpkUtils\Sysinfo\Client::ip();
		            if (!preg_match('/'.$remoteIp.'/', $allowedIps ) ) {
		                return false;
		            }
		        }
	        }
	        
	        
	        $isValid = false;
	        
	        // calculate hash for the past TTL minutes
	        $minute = round(time() / 60) * 60;
	        for ($i = 0; $i < $ttl; $i++) {
	            $hash = $this->hash($minute);
	            if ($hash === $rhash) {
	                $isValid = true;
	                break;
	            }
	            $minute--;
	        }
	        
	        return $isValid;

		} 
		catch (\Exception $e) 
		{
			$this->_logger->warning($e->getMessage());
			return false;
		}
	}

	/*
	 * INFO: services must be registered in consul with the same name as in container
	 */
	public function get($ServiceName, $ServiceArgs = [])
	{
		$method = 'options';

		$isLocal = false;
		if(is_object($ServiceName)){
			$isLocal = true;
			$resource = $ServiceName;
			$ServiceName = get_class($ServiceName);
		}else{
			if(!empty($this->_container) && $this->_container->has($ServiceName)){

				// ex: MicroIceTbs\V1\Rest\Accounts\AccountsResource
				$resource = new Resource\ClassResource($this->_container->get($ServiceName));
				// $resource->blockedMethods[] = 'delete';
				$isLocal = true;

			}elseif(class_exists($ServiceName)){

				$resource = new $ServiceName();
				$isLocal = true;
				if(count($ServiceArgs) == 0)
				   $resource = new $ServiceName;
				else {
				   $r = new \ReflectionClass($ServiceName);
				   $resource = $r->newInstanceArgs([$ServiceArgs]);
				}
			}else{
				/*$service->Name = $ServiceName;
				$service->IsLocal = false;

				// get form consul
				$cs = $consul->get($ServiceName);

				$headers = $this->headers($ServiceName);

				$resource = new Resource\RestResource($headers);
				$resource->Address = $cs->Address;
				$resource->Port = $cs->Port;
				$resource->Meta = $cs->Meta;*/

			}
		}

		if($isLocal){
			$headers = $this->headers($ServiceName);
			$request = new Request($method, '//localhost', $headers);
			// static::$_request[$this->_originator] = $request;
			static::$_request[$ServiceName] = $request;
		}
			
		if(!isset($resource)){
			$msg = "No service was found for $ServiceName identifier.";
			$this->_logger->critical($msg);
			throw new ServiceNotFoundException($msg, 1);
		}

		return $resource;
	}

	protected function headers($Target)
	{
		/*$identityId = $this->_authService::isAuthenticated();
		if ($identityId){
			$identity = $this->get('auth')->fetch($identityId);
		}else{
			$identity = null;
		}*/
		// we need to add on req the allowed services
		$minute = round(time() / 60) * 60;
        $hash = $this->hash($minute);
        $header = [
                'User-Agent' => $this->_config->head['user_agent'],
                'Accept'     => 'application/json',
                $this->_config->head['auth'] => $hash,
                $this->_config->head['originator'] => $this->_originator,
                $this->_config->head['target'] => $Target,
                // $this->_config->head['identity'] => $identity,
        ];

        return $header;
	}

	protected function hash($value)
	{
		$key = $this->_config->key['secret'];
        $hash = hash_hmac('sha256', $value, $key);

        return $hash;
	}

	protected function init()
	{
		$config = $this->loadConfig();

		return true;
	}

	protected function loadConfig()
	{

		if(!empty($this->_container)){
			if($this->_container->has('config')){
				$gconfig = $this->_container->get('config');
				if(!empty($gconfig[self::CONFIG_KEY])){
					$config = $gconfig[self::CONFIG_KEY];
				}
			}
		}

		if(empty($config)){
			$mconfig = require __DIR__.'/../config/module.config.php';
			if(!empty($mconfig[self::CONFIG_KEY])){
				$config = $mconfig[self::CONFIG_KEY];
			}else{
				$msg = "Error reading gatekeeper config";
				$this->_logger->critical($msg);
				throw new \RuntimeException($msg, 1);
			}
		}

		$this->_config = new Config();
		$this->_config->exchangeArray($config);
		$this->_config->validate();

		return $this->_config;
	}

	protected function allowOriginator($Originator)
	{
		/*$r = [
			'tbs\mapper\hotel' => [
				'accountsResource' => ['fetch', 'fetchAll']
			]
		];*/
		return true;
	}

	public function trust($ServiceName, $Request = null)
	{
		return $this->validate($ServiceName, $Request);
	}

	public function getOriginator()
	{
		return $this->_originator;
	}

}