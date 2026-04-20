<?php

namespace mnhcc\ml\interfaces {

	/**
	 *
	 * @author Michael Hegenbarth (carschrotter)
	 * @package MinimalusLayoutilus	 
	 */
	interface Instances {
	    const DEFAULTINSTANCE = '{"DEFAULTINSTANCE":"default","secure":"Ay0keRT1l8"}'; //default
	    const INSTANCE_OVERIDE = '{"INSTANCE_OVERIDE":"true","secure":"Ay0keRT1l8"}'; //true
	    const INSTANCE_NOT_OVERIDE = '{"INSTANCE_OVERIDE":"false","secure":"Ay0keRT1l8"}'; //false
	    
	    /**
	     * return string <p>the id or instance name from getInstance init</p>
	     */
	    public function getInstanceID();
	    
	    /**
	     * set the id or name for the object instance
	     */
	    public function setInstanceID($instanceID);
	    
	    public static function &getInstanceArgs($instance = self::DEFAULTINSTANCE);//,  $args = [], $override = self::INSTANCE_NOT_OVERIDE);
	
            public static function &getInstance($instance = self::DEFAULTINSTANCE);//, $override = self::INSTANCE_NOT_OVERIDE);
	    
	    public static function getInstances();
	    
	    public static function issetInstance($instance = self::DEFAULTINSTANCE);
            
            /**
              * Is a object whit 'default' keyword createt also init
              * @return bool
              */
             public static function isInit();
            
	}

}