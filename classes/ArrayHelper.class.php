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
    
    use \mnhcc\ml\interfaces\MNHcCArray;

    /**
     * Static utility methods for array operations and type-safe array checks.
     *
     * @author carschrotter
     */
    abstract class ArrayHelper extends MNHcC {

	/** @var array Registered value converters keyed by class name. */
	protected static $_converters = [];
	/** @var array Alias map for converter class names. */
	protected static $_convertersAliases = [];

	/**
	 * Registers a converter callable for a given class.
	 * @param string   $class
	 * @param callable $func
	 */
	static public function setConverter($class, callable $func){
	    self::$_converters[$class] = $func;
	}

	/**
	 * Read-only access to the converter registry.  Returned array maps
	 * each registered class name (e.g. 'ArrayObject') to its converter
	 * callable.  Keys mirror what `setConverter()` wrote — no transform.
	 *
	 * @return array
	 */
	static public function getConverters(){
	    return self::$_converters;
	}
	
	/**
	 * @param mixed $key
	 * @param array $array
	 * @return bool
	 */
	static public function keyExists($key, &$array) {
	    return \array_key_exists($key, $array);
	}
	
	/**
	 * Pop the element off the end of array
	 * @link http://php.net/manual/en/function.array-pop.php
	 * @param array $array <p>
	 * The array to get the value from.
	 * </p>
	 * @return mixed the last value of <i>array</i>.
	 * If <i>array</i> is empty (or is not an array),
	 * <b>NULL</b> will be returned.
	 */
	static public function pop(&$array) {
	    return \array_pop($array);
	}

	/**
	 * Returns the value at $key or $default; calls $default if $call_default is true.
	 * @param mixed $key
	 * @param array $array
	 * @param mixed $default
	 * @param bool  $call_default  Invoke $default as a callable when true.
	 * @return mixed
	 */
	static public function get($key, $array, $default = null, $call_default = false) {
	    return self::keyExists($key, $array) ? $array[$key] : ($call_default ? Helper::callOrGet($default) : $default);
	}

	/**
	 * @param array  $pieces
	 * @param string $glue
	 * @return string
	 */
	static public function implode($pieces, $glue = '') {
	    return \implode($glue, $pieces);
	}
	
	/**
	 * @param string   $delimiter
	 * @param string   $string
	 * @param int|null $limit
	 * @return array
	 */
	static public function explode($delimiter, $string, $limit = null) {
	    if(null === $limit){return \explode($delimiter, $string);}
	    return \explode($delimiter, $string, $limit);
	}
	
	/**
	 * (PHP 4, PHP 5)<br/>
	 * Shift an element off the beginning of array
	 * @param array $array <p>
	 * The input array.
	 * </p>
	 * @param int $repetition <p>
	 * The count of repetition.
	 * </p>
	 * @return mixed the shifted value, or <b>NULL</b> if <i>array</i> is
	 * empty or is not an array.
	 */
	static public function shift(&$array, $repetition = null) {
	    if($repetition == null) {
		return \array_shift($array);
	    } elseif(self::count($array) >= $repetition) {
		$shift = [];
		for($i = 0; $i < $repetition; $i++){
		    $shift[] = self::shift($array);
		}
		return $shift;
	    }
	}

	/**
	 * @param array $array
	 * @return int
	 */
	static public function count(&$array) {
	    return \count($array);
	}

	/**
	 * Prepends $value to $arr.
	 * @param array $arr
	 * @param mixed $value
	 * @return array
	 */
	static public function addBefore(&$arr, $value) {
	    \array_unshift($arr, $value);
	    return $arr;
	}

	/**
	 * Checks whether $needle exists in $haystack.
	 * @param mixed $needle
	 * @param array|\ArrayAccess $haystack
	 * @param bool  $strict
	 * @param bool  $recrisiv  Search recursively when true.
	 * @return bool
	 * @throws Exception\InvalidArgumentException
	 */
	static public function in($needle, $haystack, $strict = false, $recrisiv = false) {
	    if(!self::isArray($haystack)) {		
		throw new Exception\InvalidArgumentException(Exception\InvalidArgumentException::TYPE_ARRAY);
	    }
	    if ($recrisiv == true) {
		return self::inRecursive($needle, $haystack, $strict);
	    }
	    return \in_array($needle, $haystack, $strict);
	}

	/**
	 * @param mixed $needle
	 * @param array $haystack
	 * @param bool  $strict
	 * @return bool
	 */
	static public function inRecursive($needle, &$haystack, $strict = false) {
	    $answer = false;
	    // $answer captured by reference — array_walk_recursive's callback
	    // can only signal "found" by mutating an outer variable.  Without
	    // the &, the closure's local copy was thrown away on every leaf
	    // and the function reported `false` even for present needles.
	    $func = function($item, $key) use($needle, $strict, &$answer) {
		$check = false;
		if (self::isArray($needle)) {
		    $check = (bool) self::in($item, $needle, $strict);
		} elseif ($strict) {
		    $check = (bool) ($item === $needle);
		} else {
		    $check = (bool) ($item == $needle);
		}
		$answer = ($answer || $check);
	    };
	    \array_walk_recursive($haystack, $func);
	    return $answer;
	}

	/**
	 * Returns true for real arrays or ArrayAccess objects (returns 1 for ArrayAccess).
	 * @param mixed $val
	 * @return bool|int
	 */
	static public function isArray($val) {
	    if (\is_object($val)) {
		return ($val instanceof \ArrayAccess) ? 1 : false ;
	    }
	    return (bool) \is_array($val);
	}
	
	/**
	 * @param mixed $val
	 * @return bool
	 */
	static public function isMNHcCArray($val) {
	    return (is_object($val) && $val instanceof MNHcCArray);
	}

	/**
	 * Converts $val to a plain PHP array.
	 * @param mixed $val
	 * @param bool  $recrusiv  Convert nested values recursively.
	 * @return array
	 */
	static public function toArray($val, $recrusiv = false) {
	    if (self::isArray($val)) {
		if (self::isArray($val) == 1) {
		    if (self::isMNHcCArray($val) || ($val instanceof \ArrayObject)) {
			$val = $val->getArrayCopy();
		    } else {
			$val = (array) $val;
		    }
		}
		if (!$recrusiv) {
		    return $val;
		} else {
		    return self::each($val, function($key, $val, $array) use($recrusiv) {
				return self::toArray($val, $recrusiv);
			    });
		}
	    } else {
		return [$val];
	    }
	}

	/**
	 * Iterates over $array, passing each (key, value, array) to $func and collecting results.
	 * The callback may return an associative array with 'key'/'value' to remap the output key.
	 * @param array    $array
	 * @param callable $func
	 * @param array    $return  Collected results (passed by reference).
	 * @return array
	 */
	static public function each(&$array, callable $func, &$return = null) {
	    $return = [];
	    foreach ($array as $key => &$val) {
		$result = $func($key, $val, $array);
		if (self::isArray($result) &&
			isset($result['key']) &&
			isset($result['value']) &&
			\is_scalar($result['key'])) {
		    $return[$result['key']] = $result['value'];
		} else {
		    $return[$key] = $result;
		}
	    }
	    return $return;
	}

	public static function ___onLoaded() {
	    $call = function($obj){
		    return $obj->getArrayCopy();
		};
	    self::setConverter('ArrayObject', $call);
	    self::setConverter('\\mnhcc\\ml\\interfaces\\MNHcCArray', $call);
	    parent::___onLoaded();
	}
    }

}
