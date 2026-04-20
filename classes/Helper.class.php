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
     * Description of Helper
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     * @method static mixed debug(mixed $arg, mixed $arg) Variable to display with var_dump()  string to help debug on sucsess, or falso on error.
     * @method static string dump($toggleDump = self::toggleDump)
     */
    abstract class Helper implements interfaces\Prototype {

	use traits\Prototype;

	const toggleDump = '{toggleDump : "true", secure : "Ay0keRT1l8"}';

//	public static function dump() {
//	    return self::__callStatic('dump', func_get_args());
//	}
	protected static $_selfDetectMethodFilter = ['getInstance', 'getInstanceArgs', 'newInstanceArgs', 'newInstance'];
	
	public static function getSelfDetectMethodFilter() {
	    return self::$_selfDetectMethodFilter;
	}

	public static function setSelfDetectMethodFilter($selfDetectMethodFilter) {
	    if(!Helper::isArray($selfDetectMethodFilter)) {		
		throw new Exception\InvalidArgumentException(Exception\InvalidArgumentException::TYPE_ARRAY);
	    }
	    self::$_selfDetectMethodFilter = $selfDetectMethodFilter;
	}
	
	public static function addSelfDetectMethodFilter($method) {
	    self::$_selfDetectMethodFilter[] = $method;
	}
	
	public static function callOrGet($val) {
	    return \is_callable($val) ? $val() : $val;
	}

	protected static function __defaultDump($toggleDump = self::toggleDump) {
	    $args = func_get_args();
	    $toggle = false;
	    $str = '';
	    if ($toggleDump === self::toggleDump) {
		$toggle = true;
		\array_shift($args);
	    }
	    foreach ($args as $arg) {
		ob_start();
		var_dump($arg);
		$content = ob_get_contents();
		if ($toggle)
		    $str .= self::toggle($content);
		else
		    $str .= $content;
		ob_end_clean();
	    }
	    return $str;
	}

	/**
	 * 
	 * @param mixed $arg variable to display with var_dump()
	 * @param mixed $arg,... unlimited OPTIONAL number of additional variables to display with var_dump()
	 * @return mixed debug string on sucsess falso on error
	 */
	protected static function __defaultDebug($arg) {
	    $temp = '<div class="debug" style="border:5px solid red;">';

	    try {
		$ref = new \ReflectionMethod(__CLASS__, 'dump');
		$temp .= $ref->invokeArgs(Null, func_get_args());
		$temp .= Error::renderBacktrace(debug_backtrace());
	    } catch (\Exception $exc) {
		return FALSE;
	    }
	    return $temp . '</div>';
	}

	public static function toggle($content, $container = 'div') {
	    $str = '<' . $container . ' class="preview_toggle">';
	    $str .= $content;
	    $str .= '</' . $container . '>';
	    return $str;
	}

	/**
	 * clean a name (class or id) for CSS
	 * @param string $classname the css classname
	 * @param mixed $legitimer string or int <p>
	 * string: put a string in order not to legitimize a valid name
	 * int: the same with the number of certain randomly generated characters
	 * </p>
	 * @return string teh cleaned name
	 */
	static public function cssNameClean($classname, $legitimer = null) {
	    // DE-de entfernt alle nicht alphanumerischen zeichen und ersetzt leerschritte durch das +
	    // removes all non-alphanumeric characters and replaced by the empty steps +
	    $clean_classname = preg_replace_callback('~(\W)~', function($test) {
		return ($test[1] == ' ') ? '_' : '_';
	    }, $classname);

	    // DE-de aufeinanderfolgende "+" werden auf genau ein + reduziert
	    // consecutive "+" are reduced to exactly one +
	    $clean_classname = preg_replace('~(\_){2,}~', '_', $clean_classname);
	    //does not start with valid characters:
	    if (!preg_match('~^[a-zA-Z]~', $clean_classname)) {
		if (is_string($legitimer)) {
		    return $legitimer . $clean_classname; // with custom string
		} else {
		    return self::generateLegitimer($legitimer) . $clean_classname; //with randomly generated characters
		}
	    }
	    return $clean_classname;
	}

	static public function generateLegitimer($lenght = 3) {
	    $lenght = (is_int($lenght) && $lenght >= 1) ? $lenght : 3;
	    $legitimerChars = ['a', 'b', 'c', 'd', 'e',
		'f', 'g', 'h', 'i', 'j',
		'k', 'l', 'm', 'n', 'o',
		'p', 'q', 'r', 's', 't',
		'v', 'w', 'x', 'y', 'z'];
	    $legitimer = '';
	    for ($i = 0; $i < $lenght; $i++) {
		$legitimer .= $legitimerChars[mt_rand(0, count($legitimerChars) - 1)];
	    }
	    return $legitimer;
	}

	static public function checkPost() {
	    return (bool) count($_POST);
	}

	static public function checkGet() {
	    return (bool) count($_GET);
	}

	static public function checkLogin() {
	    return ( ((bool) Parm::getInstance()->get('S_username', false, 'SESSION')) &&
		    ((bool) Parm::getInstance()->get('S_userid', false, 'SESSION')) );
	}

	static public function checkRight($level = 'user') {
	    if ($this->checkLogin()) {
		/* ToDo implenet user rights */
		return true;
	    }
	}

	static public function isArray($val) {
	    Error::triggerError(__CLASS__ . '::isArray() is deprecated! Pleas use ' . __NAMESPACE__ . '\\ArrayHelper::isArray()', Error::DEPRECATED);
	    return ArrayHelper::isArray($val);
	}

	static public function isJson1($string) {
	    json_decode($string);
	    return (json_last_error() == JSON_ERROR_NONE);
	}

	static public function isJson2($string) {
	    return !preg_match('/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/', preg_replace('/"(\\.|[^"\\])*"/g', '', $string));
	}

	static public function getConstValue($value) {
	    // i
	}

	static public function isMLConst($value) {
	    return Bootstrap::isMLConst($value);
	}

	
	static public function classExists($class, $autoNamespace = true, $autoload = true) {
	    if ($autoNamespace) {
		$class = BootstrapHandler::addRootNamespace($class);
	    }
	    return (bool) \class_exists($class, $autoload);
	}
	
	static public function isTypeof($object, $type, $allow_string = false) {
	    if(!\is_object($object) && !$allow_string) {
		return null;
	    }
	    if($type == 'self') {
		$type = self::whereIsSelf();
	    } 
	    \is_subclass_of($object, $type, $allow_string);
	}
	
	static public function whereIsSelf() {
	    $self = false;
	    $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 10); //max 8 hops
	    ArrayHelper::shift($backtrace, 2); //remove the first stack
	    //fallback for autocreating
	    if (ArrayHelper::get('function', $backtrace[0], false) && ArrayHelper::in(self::getSelfDetectMethodFilter(), $backtrace, false, true)) {
		$i = 0;
		foreach ($backtrace as $row) {
		    $i++;
		    if (!ArrayHelper::in($row['type'], ['::', '->'])) {
			if (ArrayHelper::get('class', $row, false) 
				&& !ArrayHelper::in($row['class'], self::getSelfDetectMethodFilter())) {
			    $self = $row['class'];
			    break;
			}
		    }
		}
	    } elseif (isset($backtrace[0]['class'])) { //not be filtered method
		$self = ArrayHelper::get('class', $backtrace[0], false);
	    }
	    return $self;
	}

	/**
	 * Call a user method on an specific object
	 * @param string $method_name <p>
	 * The method name being called.
	 * </p>
	 * @param mixed $obj <p>
	 * The object or class that <i>method_name</i>
	 * is being called on.
	 * </p>
	 * @param array $params <p>
	 * An array of parameters.
	 * </p>
	 * @return mixed
	 */
	static public function callMethodArray($method_name, $obj, $params = []) {
	    return (new \ReflectionMethod($obj, $method_name))->invokeArgs($obj, $params);
	}

    }

}
    