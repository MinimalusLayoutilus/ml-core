<?php

namespace mnhcc\ml\classes\AutoBox {

    use \mnhcc\ml\classes, 
	\mnhcc\ml\traits,
	\mnhcc\ml\interfaces,
	\mnhcc\ml\classes\AutoBox;

    /**
     * Description of AutoBox
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    abstract class Scalar extends AutoBox {
	
	public function __toString() {
	    return $this->toString();
	}
	
	public function toString() {
	    return (string) $this->getRaw();
	}
	
	public function toBool() {
	    return (bool) $this->getRaw();
	}
	
	public function toInt() {
	    return (int) $this->getRaw();
	}
	
	public function toFloat() {
	    return (float) $this->getRaw();
	}
    }
}