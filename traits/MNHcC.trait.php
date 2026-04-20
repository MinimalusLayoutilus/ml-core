<?php

namespace mnhcc\ml\traits {

    use mnhcc\ml\classes,
	mnhcc\ml\classes\Exception;

    /**
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    trait MNHcC {

	/** @var array Classes/extensions required by this class, populated by ___onLoaded(). */
	protected static $___require = [];

	/**
	 * @return string  Fully-qualified class name.
	 */
	public function __toString() {
	    return $this->getClass();
	}

	/** @return string */
	public function getClass() {
	    return \get_class($this);
	}

	/**
	 * replace the default Error to a Exeption
	 * @param string $name
	 * @param array $arguments
	 * @throws Exception
	 */
	public function __call($name, $arguments) {
	    throw new Exception('Call to undefined method '
	    . $this->getClass() . '::' . $name . '()', Exception::noMethodImplement);
	}

	/**
	 * @param string $name
	 * @param array  $arguments
	 * @throws Exception
	 */
	public static function __callStatic($name, $arguments) {
	    throw new Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()', Exception::noStaticMethodImplement);
	}

	public static function ___onLoaded() {
	    //classes\Error::triggerError(self::getCalledClass() . "::___onLoaded() was not explicitly implemented", E_USER_NOTICE);
	}

	/** @return string */
	public static function getCalledClass() {
	    return \get_called_class();
	}

    }

}
