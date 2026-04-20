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

    //use \mnhcc\ml;

    /**
     * Description of Filter
     *
     * @author carschrotter
     * 
     */
    abstract class Filter extends MNHcC {

	public static function __callStatic($name, $arguments) {
	    switch (true) {
		case (strpos($name, 'input') === 0):
		    $filter = preg_replace("~^input~", '', $name);
		    $filter = preg_replace("~^_~", '', strtolower($filter));

		    \array_unshift($arguments, $filter);
		    return (new ReflectionStaticMethod(self::getCalledClass(), 'input'))->invokeArgs($arguments);
		    break;
		case ($name == 'var') :
		    return (new ReflectionStaticMethod(self::getCalledClass(), 'var_'))->invokeArgs($arguments);
		default:
		    break;
	    }
	}

	public static function getInputConstant() {
	    
	}

	public static function input($type, $variable_name, $filter = FILTER_DEFAULT, $options = NULL) {
	    if (function_exists('\\filter_input')) {
		return \filter_input(constant('INPUT_' . strtoupper($type)), $variable_name, $filter, $options);
	    } else {
		
	    }
	}

	public static function html($value, $filter = 'specialchars') {
	    switch ($filter) {
		case 'specialchars':
		    return \htmlspecialchars($value);
		    break;
	    }
	}

	/**
	 * 
	 * @param mixed $variable
	 * @param int $filter default FILTER_DEFAULT
	 * @param array $options deafult null
	 * @return mixed
	 */
	public static function var_($variable, $filter = FILTER_DEFAULT, $options = null) {
	    return \filter_var($variable, $filter, $options);
	}

	/**
	 * 
	 * @param mixed $variable
	 * @param int $filter default FILTER_DEFAULT
	 * @param array $options deafult null
	 * @return bool
	 */
	public static function varIs($variable, $filter = FILTER_DEFAULT, $options = null) {
	    $filter_var = self::var_($variable, $filter, $options);
	    return (bool) !empty($filter_var);
	}

    }

}