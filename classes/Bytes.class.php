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
    use mnhcc\ml\traits;

	/**
	 * A class to work with bytes as number. Enables automatic output in the correct size. <br>
	 * <b>Example:</b>
	 * <code>
	 * <?php
	 * $byte = new Bytes(11737423234);
	 * echo $byte->toMB(5); // 11193,67908
	 * echo $byte->getUfriendlySize(); // 10,93 GB
	 * echo new Bytes('42kb'); // 43008
	 * ?>
	 * </code>
	 * @author Michael Hegenbarth (carschrotter)
	 * @package MinimalusLayoutilus
	 * @copyright (c) 2012, Michael Hegenbarth
	 */
	class Bytes extends MNHcC {
            use traits\NoInstances;
		/**
		 * number of bytes which result in a kilobyte
		 */
		const kilo = 1024;

		/**
		 * number of bytes which result in a megabyte
		 */
		const mega = 1048576;

		/**
		 * number of bytes which result in a gigabyte
		 */
		const giga = 1073741824;

		/**
		 * number of bytes which result in a terabyte
		 */
		const tera = 1099511627776;

		/**
		 * number of bytes which result in a petabyte
		 */
		const peta = 1125899906842624;

		/**
		 * the saved bytenumber
		 * @var float 
		 */
		public $me;

		public function __toString() {
			return (string) (float) $this->me;
		}

		/**
		 * get the bytes as fload number
		 * @return float
		 */
		public function toFloat() {
			return (float) $this->me;
		}

		/**
		 * @param mixed $value
		 * @throws mnhcc\ml\classes\Exception
		 */
		public function __construct($value) {
			$this->me = self::convertToBytes($value);
		}

		public function getMB() {
			return self::makeMB($this->me);
		}

		public function getKB() {
			return self::makeKB($this->me);
		}

		public function getGB() {
			return self::makeGB($this->me);
		}

		public function getTB() {
			return self::makeTB($this->me);
		}

		public function getPB() {
			return self::makePB($this->me);
		}

		public static function makeMB($value, $round = null) {
			if ($round === null)
				return ($value / self::mega);
			return round($value / self::mega, $round);
		}

		public static function makeKB($value, $round = null) {
			if ($round === null)
				return ($value / self::kilo);
			return round($value / self::kilo, $round);
		}

		public static function makeGB($value, $round = null) {
			if ($round === null)
				return ($value / self::giga);
			return round($value / self::giga, $round);
		}

		public static function makeTB($value, $round = null) {
			if ($round === null)
				return ($value / self::tera);
			return round($value / self::tera, $round);
		}

		public static function makePB($value, $round = null) {
			if ($round === null)
				return ($value / self::peta);
			return round($value / self::peta, $round);
		}
		
		public static function makeTO($value, $to, $round = null) {
			if ($round === null)
				return ($value / $to);
			return round($value / $to, $round);
		}

		public function getUfriendlySize($round = 2) {
			return self::makeUfriendlySize($this->me, $round);
		}

		/**
		 * 
		 * @param mixed $value
		 * @return float
		 * @throws \Exception
		 */
		public static function convertToBytes($value) {
			if (is_numeric($value)) {
				return $value;
			} else {
				$value_length = strlen($value);
				$qty = substr($value, 0, $value_length - 1);
				$unit = strtolower(substr($value, $value_length - 1));
				switch ($unit) {
					case 'k':
						$qty *= 1024;
						break;
					case 'm':
						$qty *= 1048576;
						break;
					case 'g':
						$qty *= 1073741824;
						break;
					default :
						if (is_numeric($unit)) {
							$qty = $qty . $unit;
						} else {
							throw new mnhcc\ml\classes\Exception("Format is not a ini Byteformat");
						}
				}
				return (float) $qty;
			}
		}

		public static function makeUfriendlySize($value, $round = 2) {
			switch (true) {
				case $value >= self::peta :
					return self::makePB($value, $round) . ' PB';
					break;
				case $value >= self::tera :
					return self::makeTB($value, $round) . ' TB';
					break;
				case $value >= self::giga :
					return self::makeGB($value, $round) . ' GB';
					break;
				case $value >= self::mega :
					return self::makeMB($value, $round) . ' MB';
					break;
				case $value >= self::kilo :
					return self::makeKB($value, $round) . ' KB';
					break;
			}
		}

	}

}