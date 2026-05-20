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

namespace mnhcc\ml\classes{
    use \mnhcc\ml\traits, 
	\mnhcc\ml\interfaces,
	\mnhcc\ml\classes\BootstrapHandler as BH;


    /**
     * Static helper base for constants and application-namespace class overloading.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    abstract class Bootstrap extends MNHcC{

	/**
	 * @return bool
	 */
	public static function isDebug() {
	    return ( self::defined('DEBUG') && self::constant('DEBUG') == true);
	}
	
	/**
         * set a constant on the root namespace
         * @param string $name
         * @param mixed $value
         * @return boolean
         */
        public static function setConst($name, $value) {
	    if(!\defined(BH::getRootNamespace(). NSS. $name)) {
		\define(BH::getRootNamespace(). NSS. $name, $value);
		return true;
	    } else {return false;}
        }
	
	/**
	 * Checks whether a namespaced constant is defined.
	 * @param string $name
	 * @return bool
	 */
	public static function defined($name) {
	    return \defined(BH::getRootNamespace(). NSS. $name);
	}
	
	/**
	 * Returns the value of a namespaced constant.
	 * @param string $name
	 * @return mixed
	 */
	public static function constant($name) {
	    return \constant(BH::getRootNamespace(). NSS. $name);
	}
	
	/**
	 * Returns true if the constant exists and was set via setMLConst().
	 * @param string $name
	 * @return bool
	 */
	public static function definedML($name) {
	    if(self::defined($name)) {
		return self::isMLConst(self::constant($name));
	    }
	    return false;
	}
	
	/**
	 * Stores a value as a JSON-encoded, tamper-detectable namespaced constant.
	 * @param string $name
	 * @param mixed  $value
	 * @return bool
	 */
	public static function setMLConst($name, $value) {
	    $_value = new \stdClass();
	    $_value->{$name} = $value;
	    $_value->secure = interfaces\MNHcC::sekure;
	    $_value = \json_encode($_value);
	    return self::setConst($name, $_value);
        }
	
	/**
	 * 
	 * @param string $value json-string
	 * @return bool
	 */
	static public function isMLConst($value) {
	    $content = json_decode($value);
	    return (bool) is_object($content);
	}
	
	/**
	 * Decodes and returns the value stored by setMLConst().
	 * @param string     $name
	 * @param mixed|null $content  Pre-fetched raw constant value; fetched automatically when null.
	 * @return mixed|null
	 */
	static public function valueMLConst($name, $content = null) {
	    if($content === null) {
		$content = self::constant($name);
	    }
	    if(self::isMLConst($content)) {
		$content = json_decode($content);
		return $content->{$name};
	    }
	    return null;
	}
	
	/**
	 * Returns the application-namespace override of $class if one exists, otherwise $class itself.
	 * @param string $class  Fully-qualified framework class name.
	 * @return string
	 */
	public static function getOverloadedClass($class) {
	    if (self::constant('APPLICATIONNAMESPACE')) {
		$new_class = BH::makeClassName(
				self::constant('APPLICATIONNAMESPACE'), BH::cutRootNamespace($class)
		);
		if (Helper::classExists($new_class, false, true)) {
		    $class = $new_class;
		}
	    }
	    return $class;
	}
	
	public static function ___onLoaded() {
	    self::setConst('APPLICATIONNAMESPACE', false);
	    //parent::___onLoaded();
	}
    }
}