<?php

namespace mnhcc\ml\traits {

    use mnhcc\ml\classes\Exception as Exception,
	mnhcc\ml\classes;

    /**
     * Implementation for the Instances interface 
     * <p>
     * Get a object instance from the class, and automatic overload the classe by the namespace.<br>
     * The instance will not be saved!
     * </p>
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    trait NoInstances{
	use Instances;
	
//	protected $_instanceID;
//	
//	public function getInstanceID() {
//	    return $this->_instanceID;
//	}
//
//	public function setInstanceID($instanceID) {
//	    $this->_instanceID = $instanceID;
//	}
	
	/**
	 * 
	 * @param string $instance the id of 
	 * @return static
	 */
	public static function &getInstance($instance = self::DEFAULTINSTANCE, $t = null) {
	    $class = classes\Bootstrap::getOverloadedClass(get_called_class());
	    $reflection = new classes\ReflectionClass($class);
	    if ($instance == self::DEFAULTINSTANCE) {
		classes\Error::triggerError($class . '::getInstance() store no reference. Pleas use new ' . $class . '() or ' . $class . '::getInstance(NULL)');
	    }
	    $obj = $reflection->newInstanceArgs(func_get_args());
	    return $obj;
	}

	public static function isInit() {
	    throw new Exception('Call to undefined method ' . get_called_class() . '::' . __FUNCTION__ . '()', Exception::noMethodImplement);
	}

    }

}