<?php 
namespace TBoxPassepartout;

use RpkUtils\Oop\Abstracts\Entity as AbstractEntity;

class Entity extends AbstractEntity
{
	/**
     * Handle setter and getter type function calls
     * 
     * @param string $Name
     * @param array $Arguments
     */
    public function __call($Name, $Arguments) 
    {
        // setSomething
        if (strpos($Name, 'set') === 0) {
            if (count($Arguments) !== 1 || is_array($Arguments[0]) || is_object($Arguments[0])) {
                // only allow db-compatible values to be passed
                throw new \InvalidArgumentException('Invalid arguments passed');
            }
            $property = substr($Name, 3);
            if (!array_key_exists($property, $this->_data)) {
                throw new \RuntimeException('Invalid property' . $property);
            }
            $this->offsetSet($property, $Arguments[0]);
            
        // getSomething
        } elseif (strpos($Name, 'get') === 0) {
            $property = substr($Name, 3);
            if (!array_key_exists($property, $this->_data)) {
                throw new \RuntimeException('Invalid property: ' . $property);
            }
            return $this->offsetGet($property);
            
        } else {
            throw new \RuntimeException('Invalid method called: ' . $Name);
        }
    }
}