<?php

namespace mnhcc\ml\classes {

    /**
     * Description of URI
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class URL extends MNHcC {
	
	use \mnhcc\ml\traits\NoInstances;
	
	private $_host = false;
	private $_uri = false;
	private $_ssl = false;
	private $_port = false;
	private $_scriptName = false;
	private $_scriptDir = false;
	private $_virtualPath = false;
	private $_scheme = false;
	private $_protocol = false;
	private $_contextPrefix = false;
	private $_user = false;
	private $_pass = false;
	private $_query = false;
	private $_fragment = false;

	public function __construct($url = false) {
	    if (!$url) { //default with $_SERVER
		$this->setScriptName(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : getenv('SCRIPT_NAME'))
			->setContextPrefix(\key_exists('CONTEXT_PREFIX', $_SERVER) ? $_SERVER['CONTEXT_PREFIX'] : false)
			->setProtocol(isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : getenv('SERVER_PROTOCOL'))
			->setHost(ArrayHelper::get('HTTP_HOST', $_SERVER, function(){ return getenv('HTTP_HOST');}))
			->setScheme(isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : getenv('REQUEST_SCHEME'))
			->setUri(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI'))    
			->setQuery(ArrayHelper::get('QUERY_STRING', $_SERVER, function(){ return getenv('QUERY_STRING');}))   
			->setFragment('')
		;
	    } else {
		$server = \parse_url($url);
		$this->setScheme($server['scheme']) //http
			->setHost($server['host']) //hostname
			->setUser($server['user']) //benutzername
			->setPass($server['pass']) //passwort
			->setUri($server['path'])///pfad
			->setQuery($server['query'])//argument=wert
			->setFragment($server['fragment']) //textanker
		;
	    }
	}

	public function getUser() {
	    return $this->_user;
	}

	public function getPass() {
	    return $this->_pass;
	}

	public function getQuery() {
	    return $this->_query;
	}

	public function getFragment() {
	    return $this->_fragment;
	}

	public function setUser($user) {
	    $this->_user = $user;
	    return $this;
	}

	public function setPass($pass) {
	    $this->_pass = $pass;
	    return $this;
	}

	public function setQuery($query) {
	    $this->_query = $query;
	    return $this;
	}

	public function setFragment($fragment) {
	    $this->_fragment = $fragment;
	    return $this;
	}

	public function getContextPrefix() {
	    return $this->_contextPrefix;
	}

	public function setContextPrefix($contextPrefix) {
	    $this->_contextPrefix = $contextPrefix;
	    return $this;
	}

	public function setPort($port) {
	    $this->_port = $port;
	    return $this;
	}

	public function setScheme($scheme) {
	    $this->_scheme = $scheme;
	    return $this;
	}

	public function setProtocol($protocol) {
	    $this->_protocol = $protocol;
	    return $this;
	}

	public function getSsl() {
	    return $this->_ssl;
	}

	public function getScriptDir() {
	    $this->_scriptDir = dirname($this->getScriptName());
	    $this->_scriptDir = str_replace("\\", '/', $this->_scriptDir);
	    $this->_scriptDir = rtrim($this->_scriptDir, '/') . '/';
	    return $this->_scriptDir;
	}

	public function getVirtualPath() {
	    return $this->_virtualPath;
	}

	public function setUri($request_uri) {
	    $this->_uri = $request_uri;
	    return $this;
	}

	public function setSsl($ssl) {
	    $this->_ssl = $ssl;
	    return $this;
	}

	public function setScriptName($script_name) {
	    $this->_scriptName = $script_name;
	    return $this;
	}

	public function setScriptDir($script_dir) {
	    $this->_scriptDir = $script_dir;
	    return $this;
	}

	public function setVirtualPath($virtualPath) {
	    $this->_virtualPath = $virtualPath;
	    return $this;
	}

	public function setHost($host) {
	    $this->_host = $host;
	    return $this;
	}

	/**
	 * 
	 * @staticvar string $protocol
	 * @staticvar string $host
	 * @return string
	 */
	public function getServer() {
	    return $this->getScheme(true) . $this->getHost();
	}

	public function getHost() {
	    return $this->_host;
	}

	public function getScheme($urlstyle = false) {
	    return $this->_scheme . ($urlstyle) ? '://' : '';
	}

	public function getProtocol() {
	    return $this->_protocol;
	}

	public function getRequestUri() {
	    return $this->_uri;
	}

	public function isSSL() {
	    if (!$this->_ssl) {
		$HTTPS = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : getenv('HTTPS');
		$ssl = ($HTTPS != 'off');
	    }
	    return $this->_ssl;
	}

	public function getPort() {
	    if (!$this->_port) {
		$port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : getenv('HTTP_HOST');
	    }
	    return $this->_port;
	}

	public function getScriptName() {
	    return $this->_scriptName;
	}

	public function isDefaultPort() {
	    return ( ($this->getPort() == 80 && !$this->isSSL()) || ($this->getPort() == 443 && $this->isSSL()) );
	}

	/**
	 * 
	 * @staticvar string $script_dir the saved dir from SCRIPT_NAME 
	 * @param bool $relativ get relative path without the hostname
	 * @param bool $virtual also looking for virtual paths e.g. with apache alias
	 * @return string
	 */
	public function getBase($absolut = true, $virtual = false) {
	    $base_url = '';
	    if ($absolut) {
		$base_url = (is_string($absolut)) ? rtrim($absolut, '/') . '/' : $this->getServer();
	    }
	    $base_url .= ($this->getContextPrefix() && $virtual) ? (rtrim($this->getContextPrefix(), '/') . '/' ) : $this->getScriptDir();
	    return $base_url;
	}

	public function base($absolut = true, $virtual = false) {
	    Error::getInstance()->triggerError(" is deprecated", Error::WARNING);
	    return $this->getBase($absolut, $virtual);
	}

	public function get($key) {
	    return Filter::input_server($key);
	}

	/**
	 * 
	 * @staticvar string $virtualPath
	 * @return string
	 */
	public function virtualPath() {
	    if (!$this->_virtualPath) {
		$this->_virtualPath = ltrim(explode('?', $this->getRequestUri())[0], $this->getBase(false, true));
	    }
	    return $this->_virtualPath;
	}

	/**
	 * 
	 * @param bool $relativ
	 * @return string
	 */
	public function requestPath($relativ = false) {
	    $URI = rtrim(explode('?', $this->getRequestUri())[0], '/') . '/';
	    return ($relativ) ? $URI : $this->getServer() . $URI;
	}

	public static function __callStatic($name, $arguments) {
	    parent::__callStatic($name, $arguments);
	}

    }

}