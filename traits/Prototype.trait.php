<?php

namespace mnhcc\ml\traits {

    use \mnhcc\ml\classes\Exception,
	\mnhcc\ml\classes;
    
    /**
     * Prototype
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    trait Prototype {

	protected $_prototype = null;
	protected $_objFunction = [];
	protected static $_staticFunction = [];

	/**
	 * replace the default Error to a Exeption
	 * @param string $name
	 * @param array $arguments
	 * @throws Exception
	 */
	public function __call($name, $arguments) {
	    switch (true) {
		case ( key_exists($name, $this->_objFunction) ):
		    return call_user_func_array($this->_objFunction[$name], $arguments);
		    break;
		case ( method_exists($this, '__default' . ucfirst($name)) ):
		    $method = (new classes\ReflectionObjectMethod($this, '__default' . ucfirst($name)));
		    $method->setAccessible(true);
		    return $method->invokeArgs($arguments);
		    break;
		case ( method_exists(get_parent_class(), '__call') ):
		    return parent::__call($name, $arguments);
		    break;
		default:
		    break;
	    }
	}

	public function setFunction($call, $name) {
	    $arguments = func_get_args();
	    if (is_callable($arguments[1])) {
		$this->_objFunction[$arguments[0]] = $arguments[1];
		return true;
	    } else {
		throw new Exception('parm is not callable');
	    }
	    return false;
	}

	public static function setFunctionStatic($call, $name) {
	    if (is_callable($name)) {
		self::$_staticFunction[$name] = $call;
		return true;
	    } else {
		throw new Exception(gettype($call) . ' is not callable');
	    }
	    return false;
	}

	public function Prototype(){
	    return $this->_prototype;
	}
	
	public static function __callStatic($name, $arguments) {
	    switch (true) {
		case ( key_exists($name, self::$_staticFunction) ):
		    return call_user_func_array(self::$_staticFunction[$name], $arguments);
		    break;
		case ( method_exists(get_called_class(), '__default' . ucfirst($name)) ):
		    $method = new classes\ReflectionStaticMethod(get_called_class(), '__default' . ucfirst($name));
		    return \call_user_func_array($method->getClosure(), $arguments);
		    break;
		case ( method_exists(get_parent_class(), '__call') ):
		    return parent::__callStatic($name, $arguments);
		    break;
	    }
	}

    }

}
