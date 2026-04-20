<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace mnhcc\ml\classes\Config {
use mnhcc\ml\classes\Config;
    /**
     * ApplicationConfig
     *
     * @author MNHcC  - Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>
     * @copyright 2014, MNHcC  - Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>
     * @license default
     */
    class ApplicationConfig extends Config{
	
	public function __construct($config = null) {
	     parent::__construct($config);
	    $this->set('secure', self::secure);
	    $this->set('paths', ['root' => __DIR__]);
	    $parts = explode(NSS, static::getCalledClass());
	    $this['provider'] = $parts[0];
	    $this['application'] = $parts[1];
	   
	}
    }

}
