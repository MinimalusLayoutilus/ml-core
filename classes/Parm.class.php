<?php

namespace mnhcc\ml\classes;

use mnhcc\ml\traits as traits;
use mnhcc\ml\interfaces as interfaces;
{

    /**
     * Description of Parm
     * @todo Description
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class Parm extends MNHcC implements interfaces\Parameters {

	use traits\Instances;

	const STORE_TYPE_SESSION = 'SESSION';
	const STORE_TYPE_REQUEST = 'REQUEST';
	const STORE_TYPE_POST = 'POST';
	const STORE_TYPE_GET = 'GET';
	const STORE_TYPE_COOKIE = 'COOKIE';
	const STORE_TYPE_FILES = 'FILES';
	const STORE_TYPE_PARMS = 'PARMS';
	const STORE_TYPE_PARMS_URI = 'PARMS_URI';
	const STORE_TYPE_ALL = 'ALL';

	/**
	 * splittet uri path
	 * @var array 
	 */
	protected $_path = [];

	/**
	 * local stored parameter
	 * @var array 
	 */
	protected $_parms = [];

	/**
	 * params from uri and Routing
	 * @var array 
	 */
	protected $_parmsURI = [];
	
	public function getPath() {
	    return $this->_path;
	}

	public function getParmsURI() {
	    return $this->_parmsURI;
	}

	public function setPath($path) {
	    $this->_path = $path;
	    return $this;
	}

	public function setParmsURI($parmsURI) {
	    $this->_parmsURI = $parmsURI;
	    return $this;
	}
	
	public function setParms($parms) {
	    $this->_parms = $parms;
	    return $this;
	}
	
	public function set($key, $value){
	    $this->_parms[$key] = $value;
	    return $this;
	}
		
	public function __construct($path, $parms_URI = [], $parms = []) {
	    $this->setPath($path)
		    ->setParmsURI($parms_URI)
		    ->setParms($parms);
	}

//        public function __construct($parms = []) {
//            $this->parms = $parms;
//            $this->path = explode('/', rtrim(SERVER::virtualPath(), '/'));
//            $last = &$this->path[count($this->path) - 1];
//            $lastsplit = explode('.', $last);
//            $last = \array_shift($lastsplit);
//            $this->extension = $lastsplit;
//            foreach ($this->parmsURI as $value) {
//                list($key, $arg) = explode("|", $value);
//                if ($key && $arg) {
//                    $this->parmsURI[$key] = $arg;
//                } else {
//                    $this->parmsURI[$value] = true;
//                }
//            }
//            if (isset($this->path[0]) && $this->path[0] != '')
//                $this->parmsURI['control'] = $this->path[0];
//            if (isset($this->path[1]) && $this->path[1] != '')
//                $this->parmsURI['action'] = $this->path[1];
//        }

	public function getExtension($default = '') {
	    $ext = (isset($this->_parmsURI['extension'])) ? end($this->_parmsURI['extension']) : null;
	    return ($ext) ? $ext : $default;
	}

	public function getControl($default = 'index', $storeDefault = false) {
	    $parms = $this->getParms();
	    $control = (isset($parms['control']) && $parms['control'] != '') ? $parms['control'] : $default;
	    $control = strtolower($control);
	    if ($storeDefault)
		$this->_parms['control'] = $control;
	    return ucfirst($control);
	}

	public function getAction($default = 'index', $storeDefault = false) {
	    $parms = $this->getParms();
	    $action = (isset($parms['action']) && $parms['action'] != '') ? $parms['action'] : $default;
	    $action = strtolower($action);
	    if ($storeDefault)
		$this->_parms['action'] = $action;
	    return ucfirst($action);
	}

	public function getParms($stores = self::STORE_TYPE_ALL) {
	    if (ArrayHelper::isArray($stores)) {
		$return = [];
		foreach ($stores as $store) {
		    $return = \array_merge($return, $this->getStore($store));
		}
		return $return;
	    } else {
		return $this->getStore($stores);
	    }
	    
	}

	public function get($key, $default = null, $type = 'ALL') {
	    $parms = [];
	    if (!ArrayHelper::isArray($type)) {
		$type = [$type];
	    }
	    foreach ($type as $etype) {
		$parms = array_merge($parms, $this->getStore($etype));
	    }
	    return htmlspecialchars((isset($parms[$key])) ? $parms[$key] : $default );
	}

	public function getStore($type = self::STORE_TYPE_ALL) {
	    $parms = [];
	    switch (strtoupper($type)) {
		case self::STORE_TYPE_PARMS:
		    $parms = $this->_parms;
		    break;
		case self::STORE_TYPE_PARMS_URI:
		    $parms = $this->_parmsURI;
		    break;
		case self::STORE_TYPE_SESSION:
		    $parms = $_SESSION;
		    break;
		case self::STORE_TYPE_REQUEST:
		    $parms = $_REQUEST;
		    break;
		case self::STORE_TYPE_POST:
		    $parms = $_POST;
		    break;
		case self::STORE_TYPE_GET:
		    $parms = $_GET;
		    break;
		case self::STORE_TYPE_COOKIE:
		    $parms = $_COOKIE;
		    break;
		case self::STORE_TYPE_FILES:
		    $parms = $_FILES;
		    break;
		case self::STORE_TYPE_ALL:
		    foreach ($this->getStoreTypes() as $etype) {
			if ($etype != self::STORE_TYPE_ALL) {
			    $parms = \array_merge($parms, $this->getStore($etype));
			}
		    }
		    break;
		default:
		    throw new Exception('unknown type "' . $type . '"');
		    break;
	    }
	    return $parms;
	}

	/**
	 * 
	 */
	public function getStoreTypes() {
	    return \array_values(
		    (( new ReflectionClass(self::getCalledClass()))->getConstants(false, 'STORE_TYPE_'))
	    );
	    //array_merge($_COOKIE, $this->_parmsURI, $this->_parms, $_REQUEST, $_FILES, $_SESSION);
	}

	/**
	 * 
	 * @param string $key
	 * @param mixed $check number or string
	 * @return bool
	 */
	public function is($key, $check = true, $method = false) {
	    switch ($method) {
		case self::IS_CASE_SENSETIV:
		    if (is_string($this->get($key, false)) && is_string($check)) {
			return (bool) (strtolower($this->get($key)) === strtolower($check));
		    }
		    break;
		case self::IS_ISSET:
		    return ($this->get($key, self::NOTFOUND) !== self::NOTFOUND);
		default:
		    return (bool) ( $this->get($key) == $check);
		    break;
	    }
	    return false;
	}

    }

}