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

    use \mnhcc\ml\traits,
	\mnhcc\ml\interfaces;

    /**
     * Description of AutoBox
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    abstract class AutoBox extends MNHcC implements interfaces\AutoBox, interfaces\Instances {

	use traits\Instances,
	    traits\Instances {
		getInstanceArgs as _getInstanceArgs;
		getInstance as _getInstance;
	    }

	public function getRaw() {
	    return $this->__default;
	}
	
	public function __construct($val) {
	    //$this->setInstanceID($instanceID);
	    $this->__default = $val;
//	    if($id === null) {$id = self::genID($val);}
//	    $this->setInstanceID($id);
	}

	public static function genID($prefix) {
	    return md5(uniqid($prefix));
	}
	
	/**
	 * 
	 * @param string $instance
	 * @return static
	 */
	public static function &getInstance($instance = null) {
	    $pointer = &self::_getInstanceArgs(self::genID($instance),  func_get_args(), self::INSTANCE_OVERIDE);
	    //$_this->setInstanceID(self::genID($instance));
	    return $pointer;
	}
	

	public function __destruct() {
	    $instanceID = $this->getInstanceID();
	    //self::getInstance($this->getInstanceID());
	    //Can be destroyed because there is no reference.
	    if ($instanceID == self::DEFAULTINSTANCE) {
		return;
	    }
	    // Check if an object has the same type written in the reference.
	    if (self::getInstances()[$instanceID] instanceof self ) {
		if(self::getInstances()[$instanceID]->getInstanceID() == $instanceID){
		    return; //Is the instance already exists, do nothing
		}
		//Transfer new object old reference Key.
		self::getInstances()[$instanceID]->setInstanceID($instanceID);
		//Is it a scalar variable ("normal data type" string, int, bool, etc..)?
	    } else if (\is_scalar(self::getInstances()[$instanceID])) {
		//New value read from the reference.
		$value = self::getInstances()[$instanceID];
		//Create new object and write to the array of references
		self::_getInstance($instanceID, self::INSTANCE_OVERIDE, $value);
	    }
	}

    }
}