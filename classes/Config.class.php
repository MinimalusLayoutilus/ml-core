<?php

/*
 * Copyright (C) 2013 Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace mnhcc\ml\classes {
    
    use \mnhcc\ml\interfaces,
	\mnhcc\ml\traits;
    /**
     * Description of Config
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class Config extends \ArrayObject implements interfaces\MNHcC, interfaces\MNHcCArray, \ArrayAccess, interfaces\Instances {
	
	use traits\MNHcC,
	    traits\Instances;
        
        protected static $_instanceClass = __CLASS__;
//	protected $_store = [];
        protected static $_consts = [];
       
	public function __construct($array = [], $flags = self::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator" ) {
	    parent::__construct($array, (self:: STD_PROP_LIST ), $iterator_class);
	}
//        public function __construct($config = null) {
//	    if ($config !== null) {
//		$this->add($config);
//	    }
//	}

//	public function add($var, $key = null) {
//	    if(Helper::isArray($var) && $key === null) {
//		foreach($var as $key => $value) {
//		    $this->set($key, $value);
//		}
//	    } else {
//		$this->set($key, $var);
//	    }	
//	    return false;
//	}
//	
//        public function set($key, $value) {
//            $this->_store[$key] = $value;
//        }
//
        public function get($key, $default = null) {
	    $keys = explode('.', $key);
	    $key = null;
	    if (!isset($this[$keys[0]])) return $default;
	    
	    foreach($keys as $key) {
		if(!isset($ref)) {
		    $ref  = &$this[$key];
		} else {
		    if (Helper::isArray($ref) && isset($ref[$key])) {
			$ref = &$ref[$key];
		    } else {
			return $default;
		    }
		}
	    }
	    
            return ($ref) ? $ref : $default ;
        }

        public static function __callStatic($name, $arguments) {
            return static::getInstance()->get($name);
        }
	
	public function set($name, $value) {
	    return $this->offsetSet($name, $value);
	}
	
//        
//        protected static function _setDefault($class) {
//            return self::$_instanceClass = $class;
//        }
//	public function __get($name) {
//	    return $this->get($name);
//	}
//	public function __set($name, $value) {
//	    return $this->set($name, $value);
//	}
//	public function offsetExists($offset) {
//	    return \key_exists($offset, $this->_store);
//	}
//	public function offsetGet($offset) {
//	    return $this->get($offset);
//	}
//	public function offsetSet($offset, $value) {
//	   return $this->set($offset, $value);
//	}
//	public function offsetUnset($offset) {
//	    unset($this->_store[$offset]);
//	}
//        
//        /**
//         * 
//         * @param string $instance
//         * @return static
//         */
//        public static function getInstance($instance = 'default') {
//                if (!isset(self::$instances[$instance])) {
//                        self::$instances[$instance] = new static();
//                }
//                return self::$instances[$instance];
//        }
    }

}
