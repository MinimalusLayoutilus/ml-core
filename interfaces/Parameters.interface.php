<?php

namespace mnhcc\ml\interfaces {

    /**
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    interface Parameters {
	
        const IS_CASE_SENSETIV = '{"ISCASESENSETIV":true,"secure":"Ay0keRT1l8"}';
        const IS_ISSET = '{"ISISSET":true,"secure":"Ay0keRT1l8"}';
        const NOTFOUND = '{"NOTFOUND":true,"secure":"Ay0keRT1l8"}';
	
	public function getParms();

	public function get($key, $default = null);
	
	public function set($key, $value);

	public function is($key, $check = true, $method = false);
    }

}