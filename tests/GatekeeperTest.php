<?php

namespace TBoxTest\Passepartout;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

use TBoxPassepartout\Gatekeeper;
use Psr\Container\NotFoundExceptionInterface;

class TBoxPassepartout extends TestCase
{

	/**
     * Set up
     */
    public function setUp()
    {
        // $this->services = $this->prophesize(ContainerInterface::class);
        
        $gk = new Gatekeeper(__CLASS__);
        $this->gk = $gk;
    }

    /**
     * @expectedException \ArgumentCountError
     */
    public function test_constructFail()
    {
    	$gk = new Gatekeeper();
    }

    public function test_construct()
    {
    	$this->assertInstanceOf(Gatekeeper::class, $this->gk);
    }

    public function test_getClassName()
    {
    	$this->assertInstanceOf(\StdClass::class, $this->gk->get('StdClass'));
    }

    public function test_getClassNameWithArguments()
    {
    	$args = ['Id' => 1, 'Name'=> 'Passepartout'];
    	$resource = $this->gk->get('ArrayObject', $args);
    	$this->assertInstanceOf(\ArrayObject::class, $resource);
    	foreach ($args as $prop => $value) {
    		$this->assertEquals($resource[$prop], $value);
    	}
    }

    public function test_getInstantiatedObject()
    {
    	$a = new \ArrayObject(['Id' => 1, 'Name'=> 'Passepartout']);
    	$resource = $this->gk->get($a);

    	$this->assertInstanceOf(\ArrayObject::class, $resource);
    	foreach ($a as $prop => $value) {
    		$this->assertEquals($resource[$prop], $value);
    	}
    }

    /**
     * @expectedException TBoxPassepartout\ServiceNotFoundException
     */
    public function test_getServiceNotFound()
    {
    	$this->gk->get('SomeRandomeService');
    }

    public function test_validate()
    {
    	$this->assertTrue($this->gk->validate('ArrayObject'));
    }

    public function test_validateFail()
    {
    	$this->assertFalse($this->gk->validate(__CLASS__));
    }
}
