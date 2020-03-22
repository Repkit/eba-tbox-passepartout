<?php

namespace TBoxPassepartout\Resource;

use ZF\ApiProblem\ApiProblem;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Parameters;

// ONLY AVAILABLE FOR EBA
use ZF\Rest\ResourceEvent;
use ZF\Rest\AbstractResourceListener;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\AbstractResultSet;
use ZF\Hal\Plugin\Hal;

class ClassResource extends AbstractResource
{
	private $_class;

	protected $uriParams = [];

	public function __construct($Object)
	{
		$this->_class = $Object;
	}

	public function __call($name, $params)
	{
		if(!isset($this->_class) || empty($this->_class)){
			throw new \Exception("No service resource", 1);
		}

		if(!empty($this->uriParams) || $this->_class instanceof AbstractResourceListener){
            $event = $this->getEvent($name, $params);
            $rm = new RouteMatch($this->uriParams);
            $event->setRouteMatch($rm);
            $obj = $this->_class->dispatch($event);
            if($obj instanceof Paginator){
                $hal = new Hal();
                $hal->setLinkCollectionExtractor(new \TBoxPassepartout\Plugin\LinkCollectionExtractor);
                $collection = $hal->createCollection($obj);
                $className = 'TBoxPassepartout';
                $collection->setCollectionRoute($className);
                /*  we make this trick only for fetchAll for other where we possibly return a Collection 
                    we will get "items" instead - the default*/
                if('fetchAll' == $name){
                    $collection->setCollectionName($this->getCollectionName());
                }
                // because we do not how to set metadataMap
                $collection->setPageSize($obj->getTotalItemCount());
                $collection->setCollectionRouteParams($this->uriParams);
                $obj = $hal->renderCollection($collection);
            }
        }else{
			$obj = call_user_func_array(array($this->_class, $name), $params);
		}

		return $obj;
		
        /*if(method_exists($obj, 'toArray')){
        	$result = json_encode($obj->toArray());
        }elseif($obj instanceof \IteratorAggregate){
        	$result = json_encode(iterator_to_array($obj->getIterator()));
        }else{
        	$result = json_encode($obj);
        }*/
	}

	public function getEvent($name, $params)
	{
		$fetchAll = false;
		switch ($name) {
            case 'create':
            	$edata = ['data' => reset($params)];
                break;
            case 'delete':
            	$edata = ['id' => reset($params)];
                break;
            case 'deleteList':
                $edata = ['data' => reset($params)];
                break;
            case 'fetch':
                $edata = ['id' => reset($params)];
                break;
            case 'fetchAll':
                $edata = null;
                $fetchAll = true;
                break;
            case 'patch':
                $edata = [
                	'id' => array_shift($params),
                	'data' => array_shift($params)
                ];
                break;
            case 'patchList':
                $edata = ['data' => reset($params)];
                break;
            case 'replaceList':
                $edata = ['data' => reset($params)];
                break;
            case 'update':
                $edata = [
                	'id' => array_shift($params),
                	'data' => array_shift($params)
                ];
                break;
            default:
                throw new \RuntimeException(sprintf(
                    '%s has not been setup to handle the event "%s"',
                    __METHOD__,
                    $name
                ));
        }

		$event = new ResourceEvent($name, $this->_class, $edata);

		if($fetchAll && !empty($params)){
			$event->setQueryParams(new Parameters(reset($params)));
		}

		return $event;
	}

	public function setUriParams($Params)
	{
		$this->uriParams = $Params;
	}

    private function getCollectionName()
    {
        $str = get_class($this->_class);
        $strarr = explode('\\', $str);
        $name = strtolower(end($strarr));
        $name = str_replace('resource', '', $name);

        return $name;
    }

}