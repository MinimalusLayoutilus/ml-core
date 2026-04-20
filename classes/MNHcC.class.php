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

    use mnhcc\ml\interfaces,
	mnhcc\ml\traits;

    /**
     * Default class for classes in mnhcc namespace. 
     * <p>Implement this magic functions:
     * <ul>
     * <li><b>___onLoaded()</b> fire after the class is loaded</li>
     * <li><b>___require() fire after loaded and get array of require classes and extension for main functionality </b></li>
     * </ul>
     * </p>
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2012, Michael Hegenbarth
     * @license GPL  
     */
    abstract class MNHcC implements interfaces\MNHcC, interfaces\Instances {

	use traits\MNHcC;

	public static function ___onLoaded() {
	    /**
	     * @todo set a alias for static calls
	     */
	    //class_alias('\\mnhcc\\ml\\classes\\SERVER', 'SERVER');
	}

	public static function ___require() {
	    return self::$___require;
	}

    }

}
