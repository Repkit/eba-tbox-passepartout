<?php

namespace TBoxPassepartout\Resource;

abstract class AbstractResource
{
	
	public $blockedMethods = [];

	abstract public function setUriParams($Params);

}