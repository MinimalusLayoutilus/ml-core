<?php

namespace mnhcc\ml\classes;
{
	define("SECUREKEY", "whirlpool");
	define("SALT", "Salzstangen");

	/**
	 * Nach generieren eines Passworts darf SALT und SECUREKEY nicht mehr geändert werden,
	 * sonst simmen die Passörter nicht überein!
	 *
	 * @author Michael Hegenbarth (carschrotter)
	 * @package MinimalusLayoutilus
	 * @copyright (c) 2013, Michael Hegenbarth
	 */
	class Password extends MNHcC {

		protected static $SALT = SALT;

		public static function setSalt($salt) {
			self::$SALT = $salt;
		}

		/**
		 * 
		 * @param string $email
		 * @param string $password
		 * @param int $rounds
		 * @return string
		 */
		public static function bcryptEncode($email, $password, $rounds = '08') {
			$string = hash_hmac(SECUREKEY, str_pad($password, strlen($password) * 4, sha1($email), STR_PAD_BOTH), self::$SALT, true);
			$salt = substr(str_shuffle('./0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 22);
			return crypt($string, '$2a$' . $rounds . '$' . $salt);
		}

		/**
		 * get the String of bycrypt
		 * @param string $email the email or another personification to bound on password
		 * @param string $password the original password
		 * @param string $stored the stored password
		 * @return string
		 */
		protected static function getBcryptCeckString($email, $password, $stored) {
			$string = hash_hmac(SECUREKEY, str_pad($password, strlen($password) * 4, sha1($email), STR_PAD_BOTH), self::$SALT, true);
			return crypt($string, substr($stored, 0, 30));
		}

		/**
		 * check off password returns true or false
		 * @param string $email
		 * @param string $password
		 * @param string $stored
		 * @return bool
		 */
		public static function bcryptCheck($email, $password, $stored) {
			return (self::getBcryptCeckString($email, $password, $stored) == $stored);
		}

	}

}
