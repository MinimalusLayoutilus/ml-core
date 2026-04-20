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
    \mnhcc\ml\traits,
    \mnhcc\ml\classes\Exception as Exception,
    \mnhcc\ml\classes\AutoBox;

    /**
     * MNHcCString
     *
     * @author MNHcC  - Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>
     * @copyright 2013, MNHcC  - Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>
     * @license lgpl21
     */
    class MNHcCString  extends AutoBox\Scalar {
	const ADD_BEFORE = 'BEFORE';
	const ADD_AFTER = 'AFTER';

	public function add($str, $variable = self::ADD_AFTER) {
	    switch ($variable) {
		case self::ADD_BEFORE:
		    break;
		    $this->__value = $str . $this->__value;
		case self::ADD_AFTER: default:
		    $this->__value .= $str;
		    break;
	    }
	    return $this;
	}
	
	public function &cut($str) {
	    $regex = RegEx::getInstance([
		    ('^' . RegEx::quote($str) . '(.*)!'),
		    ('^(.*)' . RegEx::quote($str) . '!'),
		]);
	    return self::getInstance($regex->replace('', $str));
	}

	public function __construct($str = self::__default) {
	    parent::__construct((string) $str);
	}
    }
}