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
     * Description of Array
     *
     * @author carschrotter
     */
    abstract class ArrayHelper extends MNHcC {

	protected $_converters = [];
	protected $_convertersAliases = [];
	
	static public function setConverter($class, callable $func){
	    $_converters[$class] = $func;
	}
	
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

	static public function get($key, $array, $default = null, $call_default = false) {
	    return self::keyExists($key, $array) ? $array[$key] : ($call_default ? Helper::callOrGet($default) : $default);
	}

	static public function implode($pieces, $glue = '') {
	    return \implode($glue, $pieces);
	}
	
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

	static public function count(&$array) {
	    return \count($array);
	}

	static public function addBefore(&$arr, $value) {
	    \array_unshift($arr, $value);
	    return $arr;
	}

	static public function in($needle, $haystack, $strict = false, $recrisiv = false) {
	    if(!self::isArray($haystack)) {		
		throw new Exception\InvalidArgumentException(Exception\InvalidArgumentException::TYPE_ARRAY);
	    }
	    if ($recrisiv == true) {
		return self::inRecursive($needle, $haystack, $strict);
	    }
	    return \in_array($needle, $haystack, $strict);
	}

	static public function inRecursive($needle, &$haystack, $strict = false) {
	    $answer = false;
	    $func = function($item, $key) use($needle, $strict, $answer) {
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

	static public function isArray($val) {
	    if (\is_object($val)) {
		return ($val instanceof \ArrayAccess) ? 1 : false ;
	    }
	    return (bool) \is_array($val);
	}
	
	static public function isMNHcCArray($val) {
	    return (is_object($val) && $val instanceof MNHcCArray);
	}

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
