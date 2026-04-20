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
    /**
     * Autoloading for the mnhcc framework
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package mnhcc
     * @copyright (c) 2012, Michael Hegenbarth
     * @license GPL  
     */
    abstract class BootstrapHandler {
	
	const TYPE = 'type';
	const TYPE_CLASS = 'class';
	const TYPE_TRAIT = 'trait';
	const TYPE_INTERFACE = 'interface';
	const TYPE_EXTENSION = 'extension';
	
        const path = __DIR__;

	protected static $_raiseExeptiononError = true;
        /**
         * alternative path in the form: ['className' => 'path']
         * @var array 
         */
        protected static $_paths = [];
	
	protected static $_librarys = [];

        /**
         * alternate include paths
         * @var array 
         */
        protected static $_includePaths = [];

        /**
         * the dependencies for functional ability
         * @var array 
         */
        protected static $_dependencies = [
            self::TYPE_EXTENSION => ['json', 'SPL', 'reflection'],
	    self::TYPE_CLASS => ['mnhcc\\ml\\classes\\Filesystem']
        ];

        /**
         * is the autoloader initialized
         * @var bool 
         */
        protected static $_isInitial = false;

        /**
         * extensions for the autoloading types (class, trait, interfaces, ... )
         * @var array
         */
        protected static $_extensions = [ 'default' => '.php', self::TYPE_CLASS => '.class.php', self::TYPE_TRAIT => '.trait.php', self::TYPE_INTERFACE => '.interface.php'];

        /**
         *
         * @var array 
         */
        protected static $_loaded = [];

        /**
         * the root namespace
         * @var string 
         */
        protected static $_rootNamespace;

        /**
         * the default namespace off all classes 
         * Example: self::$rootNamespace.NSS.self::$classNamespaceRoot
         * @var string 
         */
        protected static $_classNamespaceRoot = 'classes';

        /**
         * the default namespace off all interfaceses 
         * Example: self::$rootNamespace.NSS.self::$interfaceNamespaceRoot
         * @var string 
         */
        protected static $_interfaceNamespaceRoot = 'interfaces';

        /**
         * the default namespace off all traits 
         * Example: self::$rootNamespace.NSS.self::$traitNamespaceRoot
         * @var string 
         */
        protected static $_traitNamespaceRoot = 'traits';
        
        /**
         *
         * @var bool 
         */
        protected static $_useErrorHandler = false;

        public static function isInitial() {
            if( (func_num_args() > 0) && (get_called_class() === __CLASS__) ) {//setter is private
                self::$_isInitial = func_get_arg(0);
            }
            return self::$_isInitial;
        }

	public static function isValidType($type){
	    switch ($type) {
		case self::TYPE: case self::TYPE_CLASS: case self::TYPE_TRAIT: case self::TYPE_INTERFACE: case self::TYPE_EXTENSION:
		    return true;
		    break;
		default:
		    return false;
		    break;
	    }
	}
        public static function getPaths() {
            return self::$_paths;
        }

        /**
         * the root namespace
         * @return string
         */
        public static function getRootNamespace() {
            return self::$_rootNamespace;
        }

        /**
         * the default namespace off all classes 
         * Example: self::$rootNamespace.NSS.self::getClassNamespaceRoot(); // get the full namespace to a trait
         * @return string
         */
        public static function getClassNamespaceRoot() {
            return trim(self::$_classNamespaceRoot, NSS);
        }

        /**
         * the default namespace off all traits 
         * Example: self::$rootNamespace.NSS.self::getTraitNamespaceRoot(); // get the full namespace to a trait
         * @return string
         */
        public static function getTraitNamespaceRoot() {
            return trim(self::$_traitNamespaceRoot, NSS);
        }

        /**
         * the default namespace off all interfaces
         * Example: self::$rootNamespace.NSS.self::getInterfaceNamespaceRoot(); // get the full namespace to a trait
         * @return string
         */
        public static function getInterfaceNamespaceRoot() {
            return trim(self::$_interfaceNamespaceRoot, NSS);
        }

        /**
         * extensions for the autoloading types (class, trait and interfaces ) 
         * @return array
         */
        public static function getExtensions() {
            return self::$_extensions;
        }

        public static function setDefineLocation($name, $path) {
            self::$_paths[$name] = $path;
        }

	/**
	 * 
	 * @param string $name key or name of 
	 * @return boolean\string false or the path to type file
	 */
        public static function getDefinedLocation($name) {
             if (key_exists($name, self::$_paths)) {
                return self::$_paths[$name];
            }
            return false;
        }

        public static function setRootNamespace($rootNamespace) {
            self::$_rootNamespace = trim($rootNamespace, NSS);
        }

        public static function setClassNamespaceRoot($classNamespaceRoot) {
            self::$_classNamespaceRoot = $classNamespaceRoot;
        }

        public static function setTraitNamespaceRoot($traitNamespaceRoot) {
            self::$_traitNamespaceRoot = $traitNamespaceRoot;
        }

        public static function setInterfaceNamespaceRoot($interfaceNamespaceRoot) {
            self::$_interfaceNamespaceRoot = $interfaceNamespaceRoot;
        }

        /**
         * 
         * @param string $name the type name for example "classes", "traits" or "interfaces"
         * @param string $extension the extension e.g. ".class.php"
         */
        public static function setExtension($name, $extension) {
            self::$_extensions[$name] = $extension;
        }

        /**
         * set the autoload extensions and override the existing
         * @param array $extensions
         */
        public static function setExtensions(array $extensions) {
            self::$_extensions = $extensions;
        }

        /**
         * set the ErrorHandler for handling errors
         * @param \mnhcc\ml\classes\Error $error
         */
        public static function setErrorHandler(Error $error) {
            self::$_useErrorHandler = true;
        }

	/**
	 * 
	 * @param type $path
	 * @param type $key
	 * @param type $using_default
	 */
        public static function setIncludePath($path, $key = null, $using_default = false) {
            $path = rtrim($path, '\\/'); // clean the las slash
            if (is_null($key)) {
                self::$_includePaths[] = ['path' => $path, 'using_default' => true];
	    }
            else {
                self::$_includePaths[$key] = ['path' => $path, 'using_default' => $using_default];
	    }
        }

        public static function getIncludePaths() {
            return array_merge([ self::getRootNamespace() => [
                        'path' => (rtrim(dirname(self::path), DS)),
                        'using_default' => true
                        ]
                    ], 
                    self::$_includePaths);
        }

        public static function checkDependencies() {
            foreach (self::$_dependencies as $type => $dependencies) {
                foreach ($dependencies as $dependence) {
                    switch (\strtolower($type)) {
                        case 'extension':
                            if (!\extension_loaded($dependence)) {
                                if (!function_exists(NSS . 'dl') || !\dl($dependence . '.so')) {
                                    self::_registerMissingDependency($type, '<b>Required extension ' . $dependence . ' is missing!</b>', $log);
                                }
                            }
                            break;
			case self::TYPE: case self::TYPE_CLASS: case self::TYPE_INTERFACE: case self::TYPE_TRAIT:
			    if (!type_exists($dependence, true, \strtolower($type))) {
                                self::_registerMissingDependency($type, '<b>Required ' . $type . ': ' . $dependence . ' is missing!</b>');
                            }
			    break;
			default :
			    self::_registerMissingDependency($type, 'Required depending on the type "' . $type . '" (' . $dependence . ') is not known!');
			    break;
                    }
                }
            }
            return true;
        }

        protected static function _registerMissingDependency($type, $msg, $log) {
            if (self::$_useErrorHandler) {
                Error::getInstance()->raise(505, $msg, (($log) ? $log : $type));
            } else {
                exit($msg);
            }
        }

        protected static function _isLoad($name, $value = false) {
            if (!isset(self::$_loaded[$name])) {
                self::$_loaded[$name] = $value;
            }
            if (func_num_args() > 1) {
                self::$_loaded[$name] = $value;
            }
            return self::$_loaded[$name];
        }
	
	public  static function raiseExeptiononError($value = false) {
            if (func_num_args() > 0) {
                self::$_raiseExeptiononError = $value;
            }
            return self::$_raiseExeptiononError;
	}
	
	public static function addDependencies($dependencies) {
	   if(ArrayHelper::isArray($dependencies)) {
	       foreach ($dependencies as $key => $value) {
		   self::addDependency($key, $value);
	       }
	   }
	}
	
        public static function addDependency($type, $dependence) {
            self::$_dependencies[$type][] = $dependence;
            if (self::isInitial())
                self::checkDependencies();
        }

        public static function addExtensionDependency($extension) {
            self::$_dependencies['extension'][] = $extension;
            if (self::isInitial())
                self::checkDependencies();
        }

        public static function initial() {
            if (!self::getRootNamespace())
		self::setRootNamespace((defined('ROOTNAMESPACE') ? ROOTNAMESPACE : str_replace("\\classes", '', __NAMESPACE__) . NSS));
	    $extensions = '';
	    foreach (self::getExtensions() as $ext) {
                $extensions .= $ext . ', ';
            }
            spl_autoload_extensions($extensions);
            spl_autoload_register(__CLASS__ . '::namespaceLoader', true);
            spl_autoload_register(__CLASS__ . '::classLoader', true);
	    spl_autoload_register(__CLASS__ . '::loadCheck', true);
            self::isInitial(true);
            self::_initialLibrary();
            self::checkDependencies();
        }

        protected static function _initialLibrary() {
            try {
                foreach (self::getIncludePaths() as $i_path) {
                    if (!$i_path['using_default']) {
                        continue;
                    }
                    $path = $i_path['path'];
                    $library = new \DirectoryIterator($path . DS . 'library');
                    foreach ($library as $fileInfo) {
                        if ($fileInfo->isDot())
                            continue;
                        if ($fileInfo->isDir()) {
                            $loadfile = $fileInfo->getRealPath() . DS . 'load.php';
                            if (file_exists($loadfile)) {
                                $load = @include($loadfile);
                                foreach ($load as $library => $config) {
				    self::$_librarys[$library] = $config;
                                    foreach ($config['classes'] as $class => $path) {
                                        self::setDefineLocation($class, $config['root'] . DS . $path);
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return false;
            }
            return true;
        }
	
	/**
	 * 
	 * @param string $library the name of the Library
	 * @return boolean
	 */
	public static function loadLibrary($library) {
	    if (isset(self::$_librarys[$library])) {
		$config = self::$_librarys[$library];
		$result = false;
		
		if(isset($config['init'])){
		    $result = require_once($config['root'] . DS . $config['init']);
		} else {
		    foreach ($config['classes'] as $class => $path) {
			$result = require_once($config['root'] . DS . $path);
		   }
		}
		return $result;
	    }
	    return false;
	}
	

        public static function makeClassName($parm1, $parm2) {
            $class_name = '';
            foreach (func_get_args() as $crumbs) {
                $class_name .= NSS . trim($crumbs, NSS);
            }
            return trim($class_name, NSS);
        }

	public static function getTypeNamespaceRoot($type) {
	    switch ($type) {
		case self::TYPE_CLASS:
		    return self::getClassNamespaceRoot();
		    break;
		case self::TYPE_INTERFACE:
		    return self::getInterfaceNamespaceRoot();
		    break;
		case self::TYPE_TRAIT:
		    return self::getTraitNamespaceRoot();
		    break;
		default:
		    return false;
		    break;
	    }
	}
	
        public static function addRootNamespace($class, $namespace = self::TYPE_CLASS) {
	    $type = self::getTypeNamespaceRoot($namespace);
            $namespace = ($type != false) ?  $type : $namespace;
            $name = \preg_replace(
		    \sprintf("~^%s~", \preg_quote(
			    self::makeClassName(
				    self::getRootNamespace(), 
				    $namespace)
			    )),
		    '',
		    trim($class, NSS) );
            return self::makeClassName(self::getRootNamespace(), $namespace, $name);
        }

	/**
	 * load the class or given type
	 * @param string $name
	 * @param bool $is_dependence
	 * @param string $type var from type ClassHelper::const TYPE_
	 * @return bool
	 */
        public static function Load($name, $is_dependence = false, $type = self::TYPE) {
            $answer = false;
            if ($type !== self::TYPE && self::isValidType($type)) {
		$name = self::addRootNamespace($name);
		$answer = self::namespaceLoader($name);
            } else {
                $answer = self::namespaceLoader($name);
            }
            if ($is_dependence) {
                self::addDependency($type, $name);
            }
            return $answer;
        }
        
        public static function cutRootNamespace($name) {
            return  preg_replace("~^" . str_replace(NSS, NSS . NSS, self::getRootNamespace() . NSS) . "~", '', $name);
        }

        public static function namespaceLoader($name) {
            if (self::_isLoad($name)){
                return true;
	    }
            $name = trim($name, NSS);

            $key = self::cutRootNamespace($name);

            $parts = explode(NSS, $key);
            $class = array_pop($parts);

            $classfile = self::getDefinedLocation($name);
            if (!$classfile) {
                $classfile = self::getDefinedLocation($key);
            }
            reset($parts);
            $type = self::getTypeIdentifierNamespacePart($name);
            if ($classfile) {
                self::_isLoad($name, ( self::__require($classfile, $name, $type) 
			&& type_exists($name, false, $type)) );
                return self::_isLoad($name);
            } else {
                $namespacePath = '';
                foreach ($parts as $folder) {
                    $namespacePath .= $folder . DS;
                }
                $ext = self::_getExtensionFN($name, [], $type);

                foreach (self::getIncludePaths() as $namespace => $setting) {
                    if (!$setting['using_default']) {
                        continue;
                    }
                    $path = $setting['path'];
                    $classfile = $path . DS . $namespacePath . $class . $ext;
                    $classfile = str_replace(['\\', '/'], DS, $classfile);
		    self::_isLoad($name, ( self::__require($classfile, $name, $type) 
			&& type_exists($name, false, $type)) );
                    if (self::_isLoad($name)) {
                        return true;
                    }
                }
            }
            return false;
            /**
             * @todo throw new Exception('Class '.$name.' not found!');
             */
        }

        protected static function __require($classfile, $name, $typeNamespace) {
	    static $interface;
	    if(!$interface) $interface = self::addRootNamespace('MNHcC', self::getInterfaceNamespaceRoot());
            if (file_exists($classfile)) {
                require_once($classfile);
		try {
		    if ($typeNamespace == self::getClassNamespaceRoot()) {
			$class = new \ReflectionClass($name);
			if ($class->implementsInterface($interface)) {
			    (new \ReflectionMethod($name, '___onLoaded'))->invoke($name);
			    self::addDependencies((new \ReflectionMethod($name, '___require'))->invoke($name));
			}
		    }
		} catch (\Exception $exc) {
		    
		}
		return true;
            } else {
                return false;
            }
        }
        
        public static function getNamespaceTypeIdentifiers(){
           return [self::getClassNamespaceRoot() => self::TYPE_CLASS, 
	       self::getTraitNamespaceRoot() => self::TYPE_TRAIT,
	       self::getInterfaceNamespaceRoot() => self::TYPE_INTERFACE];
        }

        public static function classLoader($name) {
            $key = str_replace(self::getRootNamespace(), '', $name);
            $parts = explode(NSS, $key);
            $class = array_pop($parts);
	    $classfiles = [];
            $classfiles[] = self::getRootNamespace() . DS . $class . self::getExtensions()['class'];
	    $classfiles[] = self::getRootNamespace() . DS . $class . php;
	    foreach ($classfiles as $classfile) {
		if (file_exists($classfile)) {
		    require_once $classfile;
		    return true;
		}
	    }
	    return false;
        }
	
	public static function loadCheck($name) {
	    if (self::raiseExeptiononError() && !type_exists($name, false, self::TYPE)) {
		$backtrace= debug_backtrace (DEBUG_BACKTRACE_PROVIDE_OBJECT, 0);
		foreach ($backtrace as $trace) {
		    if( array_key_exists('function', $trace) 
			    && preg_match("~(class_exists)|(trait_exists)|(interface_exists)~i", $trace['function'])
			    && (array_key_exists('args', $trace) && $trace['args'][0] == $name)) {
			return false;
		    }
		}
//		throw new Exception('Class ' . $name . ' not found!');
	    }  
	}

	/**
         * Get the Extension from Type-Identifier in the Namespace
         * @param string $name the namespace
         * @param array $keys overides for self::getNamespaceTypeIdentifiers()
         * @return string the extension .php, .class.php, ...
         */
        protected static function _getExtensionFN($name, array $keys = [], $type_identifier = false) {
            $keys = array_merge(self::getNamespaceTypeIdentifiers(), $keys);
            $type_identifier = ($type_identifier) ? $type_identifier : self::getTypeIdentifierNamespacePart($name, $keys);
            if ($type_identifier && key_exists($type_identifier, $keys)) {
                return self::getExtensions()[$keys[$type_identifier]];
            }
            return self::getExtensions()['default'];
        }
	
        /**
         * get the type vor the namespace defined in the self::getNamespaceTypeIdentifiers()
         * @param string $name the namespace
         * @param array $identifiers overides for self::getNamespaceTypeIdentifiers()
         * @return string|boolean|array
         */
        public static function getTypeIdentifierNamespacePart($name, array $identifiers = [], $asRow = false){
            $identifiers = array_merge(self::getNamespaceTypeIdentifiers(), $identifiers);
            $matches = [];
            foreach ($identifiers as $namespace => $_type) {
                preg_match("~" . str_replace(NSS, NSS . NSS, "($namespace)" . NSS). "~", $name, $matches);
                if(count($matches) >= 2)  {
		    if($asRow) {
			return ['namespace' => $namespace, 'type' => $_type];
		    }
		    else {
			return $namespace;
		    }
                }
            }
            return false;
        }
	
	public static function getTypeIdentifier($name, array $identifiers = []){
	    self::getTypeIdentifierNamespacePart($name, $identifiers, true)['type'];
	}

        /**
         * get the type vor the namespace defined in the self::getNamespaceTypeIdentifiers()
         * @param string $name the namespace
         * @param array $keys overides for self::getNamespaceTypeIdentifiers()
         * @return string|boolean
         */
        public static function getTypeIdentifierFromNamespace($name, array $keys = []){
	    $TypeIdentNsPart = self::getTypeIdentifierNamespacePart($name, $keys, true);
	    if($TypeIdentNsPart !== false) return $TypeIdentNsPart[1];
        }

        public static function getObjectInstance($class, $args) {
            return (new ReflectionStaticMethod($class, 'getInstance'))->invoke(DEFAULTINSTANCE, $args);
        }

    }
    /**
     * check named type class, interface or trait exists
     * @param string $class_name the name of Type (class, interface or trait)
     * @param bool $autoload (true) disabele autoload set to FALSE
     * @return type
     */
    function type_exists($class_name, $autoload = true, $type = BootstrapHandler::TYPE) {
	switch (strtolower($type)) {
	    case BootstrapHandler::TYPE_CLASS:
		return \class_exists($class_name, $autoload);
		break;
	    case BootstrapHandler::TYPE_INTERFACE:
		return \interface_exists($class_name, $autoload);
		break;
	    case BootstrapHandler::TYPE_TRAIT:
		return \trait_exists($class_name, $autoload);
		break;
	    case BootstrapHandler::TYPE: default:
		return (\class_exists($class_name, $autoload) 
		    || \interface_exists($class_name, $autoload) 
		    || \trait_exists($class_name, $autoload));
		break;
	}
	return 0;
    }
}