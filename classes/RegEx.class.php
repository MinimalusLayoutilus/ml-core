<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace mnhcc\ml\classes {

    use \mnhcc\ml\traits, 
	\mnhcc\ml\interfaces;

    /**
     * RegEx
     *
     * @author MNHcC  - Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>
     * @copyright 2014, MNHcC  - Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>
     * @license default
     * @method RegEx getInstance(string $regex = '', string $delimiter = self::DEFAULT_DELIMITER, array $modifier = [], bool $automask = false) get a Instance from type RegEx. The arguments are the same as the constructor.
     */
    class RegEx extends MNHcC implements interfaces\Instances{
	use traits\NoInstances;
	
	const DEFAULT_DELIMITER = '~';
	
	protected $_regex = [], 
		$_delimiter = self::DEFAULT_DELIMITER, 
		$_modifier = [], 
		$_automask = false;
	
	public function getRawRegex() {
	    return $this->_regex;
	}

	public function getDelimiter() {
	    return $this->_delimiter;
	}
	public function getModifier() {
	    return $this->_modifier;
	}

	public function setModifier($modifier) {
	    $this->_modifier = $modifier;
	    return $this;
	}

	protected function setRegex($regex) {
	    $this->_regex = $regex;
	    return $this;
	}

	public function setDelimiter($delimiter) {
	    $this->_delimiter = $delimiter;
	    return $this;
	}
	
	public function isAutoMask($automask = null) {
	   if(func_num_args() == 0 && $automask = null) {
	       return $this->_automask;
	   } else{
	       $this->_automask = (bool) $automask;
	   }
	   return $this;
	}
		
	public function __construct($regex = '', $delimiter = self::DEFAULT_DELIMITER, $modifier = [], $automask = false) {
	    $this->setRegex($regex)
		    ->setDelimiter($delimiter)
		    ->setModifier($modifier)
		    ->isAutoMask($automask);
	}
	
	public function filter($replacement, $subject, $limit = -1, &$count = null) {
	    return \preg_filter($this->getRegex(), $replacement, $subject, $limit, $count);
	}
	
	public function replaceCallback(callable $callback, $subject, $limit = -1, &$count = null) {
	    return \preg_replace_callback($this->getRegex(), $callback, $subject, $limit, $count);
	}
	
	public function replace($replacement, $subject) {
	    return \preg_replace($this->getRegex(), $replacement, $subject);
	}
	
	public function masked() {
	    $temp_regex = ArrayHelper::toArray($this->getRawRegex());
	    reset($temp_regex);
	    $regexs = [];

	    foreach ($temp_regex as $reg) {
		if ($this->isAutoMask() && !Helper::isTypeof($reg, 'self')) {
		    $regexs[] = self::quote($reg, $this->getDelimiter());
		} else { //if($this->isAutoMask()) {
		    $regexs[] = $reg;
		}
	    }
	    if (count($regexs) == 1) {
		return $regexs[0];
	    }
	    return $regexs;
	}
	
	public static function quote($str, $delimiter = self::DEFAULT_DELIMITER) {
	    return \preg_quote($str, $delimiter);
	}
	

	/**
	 * get the Pattern for preg
	 * @return array
	 */
	public function getRegex(){
	    $pattern = [];
	    $masked = ArrayHelper::toArray($this->masked());
	    foreach ($masked as $pat) {
		$pattern[] = $this->makePatter($pat);
	    }
	    if (ArrayHelper::count($pattern) == 1) {
		return ArrayHelper::get(0, $pattern, '');
	    }
	    return $pattern;
	}
	
	protected function makePatter($pat) {
	    if(Helper::isTypeof($pat, 'self')) {
		return $pat->toString();
	    }
	    return $this->getDelimiter() .
		    $pat .
		    $this->getDelimiter() .
		    ArrayHelper::implode($this->getModifier(), '');
	}
	
	public function toString() {
	    $toString = '';
	    $masked = ArrayHelper::toArray($this->masked());
	    if(ArrayHelper::count($masked)> 1) {
		foreach($masked as $pat){
		    $toString .= \sprintf('(%s)|', $pat);
		}
		$toString = \rtrim($toString, '|');
	    } else {
		$toString = ArrayHelper::get(0, $masked, '');
	    }
	    if('' != $toString) {
		return (string) $this->makePatter($toString);
	    }
	    return '';
	}
	
	public function __toString() {
	    $this->toString();
	}
    }

}