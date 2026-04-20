<?php

namespace mnhcc\ml\traits {

    use \mnhcc\ml\classes,
	\mnhcc\ml\classes\Exception;
    
    /**
     * Implementation for the Instances interface 
     * <p>
     * Get a object instance from the class, and automatic overload the classe by the namespace.<br>
     * The instance is saved with id specified.
     * </p>
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    trait Instances {

	protected static $_instances = [];
	protected $_instanceID = self::DEFAULTINSTANCE;
	
	/**
	 * is init (a object createt)
	 * @var bool 
	 */
	protected static $init = false;

	/**
	 *  is a object createt also init
	 * @return bool
	 */
	public static function isInit() {
	    return self::$init;
	}
	
	public function getInstanceID() {
	    return $this->_instanceID;
	}

	public function setInstanceID($instanceID) {
	    $this->_instanceID = $instanceID;
	}
	
	public static function issetInstance($instance = self::DEFAULTINSTANCE) {
	    if(classes\ArrayHelper::keyExists($instance, self::$_instances)) {
		return ( \is_object(self::$_instances[$instance]) && (self::$_instances[$instance] instanceof self) );
	    } 
	    return false;
	}
//	
//	public function __destruct() {
//	   if(classes\ArrayHelper::keyExists(self::$this->_instanceID, self::$_instances) 
//		   && self::$_instances[self::$this->_instanceID] instanceof self){ 
//	       unset(self::$_instances[$this->_instanceID]); 
//	   }
//	}
//	
	protected static function setInstance($instance, $self) {
	    if($self instanceof self){
		self::$_instances[$instance] = $self;
	    } else {
		throw new Exception(__class_.'::setInstance() only accept objects from type '.__class_);
	    }
	}
	
	/**
	 * 
	 * @param string $instance <p>Instance id/name</p>
	 * @return static
	 */
	public static function &getInstance($instance = self::DEFAULTINSTANCE, $override = self::INSTANCE_NOT_OVERIDE) {
	    if ( (!\key_exists($instance, self::$_instances)) || ($override == self::INSTANCE_OVERIDE) ) {
		$class = classes\Bootstrap::getOverloadedClass(get_called_class());
		$args = func_get_args();
		if (classes\ArrayHelper::count($args) > 0) {
		    classes\ArrayHelper::shift($args);
		    if (classes\ArrayHelper::keyExists(0, $args)) {
			if (classes\ArrayHelper::in($args[0], [self::INSTANCE_OVERIDE, self::INSTANCE_NOT_OVERIDE])) {
			    classes\ArrayHelper::shift($args);
			} else {
			    classes\Error::triggerError('Pleas use as the secont argument the \\mnhcc\\ml\\interfaces\\Instances::INSTANCE_... constants', classes\Error::DEPRECATED);
			}
		    }
		}
		self::$_instances[$instance] = (new classes\ReflectionClass($class))->newInstanceArgs($args);
		self::$_instances[$instance]->setInstanceID($instance);
		if ($instance == self::DEFAULTINSTANCE) {
		    self::$init = true;
		}
	    }
	    return self::$_instances[$instance];
	}
	
	public static function &getInstanceArgs($instance = self::DEFAULTINSTANCE,  $args = [], $override = self::INSTANCE_NOT_OVERIDE) {
	    $args = classes\ArrayHelper::addBefore($args, $override);
	    $args = classes\ArrayHelper::addBefore($args, $instance);
	    /**
	     * @todo self keyword on ReflectionClass
	     */
//	    \Kint::dump(new classes\ReflectionClass('self'));
//	    \Kint::dump(classes\ReflectionClass::getInstance('self'));
	    
	    $call = 'self::getInstance';
	    if(\method_exists(__CLASS__, '_getInstance')){
		$call = 'self::_getInstance';
	    } 
	    $eval_args_str = '';
	    for($i =0; $i < \count($args); $i++){
		$eval_args_str .= '$args['.$i.'],';
	    }
	    $eval_args_str = rtrim($eval_args_str, ',');
	    $pointer = null;
	    eval('$pointer = &'.$call.'('.$eval_args_str.');'); // \call_user_func_array($call, $args);
	    return $pointer;    
	}
	
	/**
	 * @return array <p>array of instances from static</p>
	 */
	public static function &getInstances() {
	    return self::$_instances;
	}

    }

}