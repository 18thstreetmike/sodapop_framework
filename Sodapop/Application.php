<?php
/*
 * Copyright (C) 2013 Michael Arace 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Sodapop_Application represents a Sodapop application. 
 * 
 * It is a singleton and you can get a reference to it by calling 
 * Sodapop_Application::getInstance().
 */
ini_set('zlib.output_compression','Off');
session_start();

class Sodapop_Application {
    
    private $config = array();
    private $routes = array();
    private $connections = array();
    private $table_definitions = array();
    private static $application = null;
    private $theme = null;
    private $theme_ignored_actions = array();
    private $initialized = false;
    
    /**
     * The application is a singleton, and this method gives you a reference to it.
     * 
     * @return Sodapop_Application
     */
    public static function getInstance() {
	if (is_null(Sodapop_Application::$application)) {
	    Sodapop_Application::$application = new Sodapop_Application();
	}
	return Sodapop_Application::$application;
    }
    
    public function initialize() {
        // load the config
        $this->loadConfig();
        $this->initialized = true;
    }
    
    /**
     * This method is called after the application is initialized. This happens within
     * the application's index.php file in its www directory.
     */
    public function run() {
        if (!$this->initialized) {
            $this->initialize();
        }
	// if there is a theme, check that the requested file isn't in the theme's root
	if (substr($_SERVER['REQUEST_URI'], -1) != '/' && file_exists($this->getThemeRoot().'www'.$_SERVER['REQUEST_URI'])) {
	    if (getenv('USE_CACHE') != 'false' && function_exists('apc_exists') && apc_exists('filec_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI'])) {
                ob_start("ob_gzhandler"); 
                header("Content-Type: ".apc_fetch('filet_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI']));
                header("Content-Length: " . apc_fetch('files_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI']));
                header('Content-Encoding: gzip');
                echo(apc_fetch('filec_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI']));
                ob_flush();
                exit;
            } else {
                if (getenv('USE_CACHE') != 'false' && function_exists('apc_store')) {
                    $name = $this->getThemeRoot().'www'.$_SERVER['REQUEST_URI'];
                    $gzipped_output = gzencode (file_get_contents($name), 6);
                    apc_store('filet_'.$name, determine_mime_type($name));
                    apc_store('files_'.$name, filesize($gzipped_output));
                    apc_store('filec_'.$name, $gzipped_output);
                    ob_start("ob_gzhandler"); 
                    header("Content-Type: ".apc_fetch('filet_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI']));
                    header("Content-Length: " . apc_fetch('files_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI']));
                    header('Content-Encoding: gzip');
                    echo(apc_fetch('filec_'.$this->getThemeRoot().'www'.$_SERVER['REQUEST_URI']));
                    ob_flush();
                    exit;
                } else {
                    $name = $this->getThemeRoot().'www'.$_SERVER['REQUEST_URI'];
                    $fp = fopen($name, 'rb');
                    header("Content-Type: ".determine_mime_type($name));
                    header("Content-Length: " . filesize($name));

                    fpassthru($fp);
                    exit;
                }
            }
	}
	
	// connect to the database
	if (isset($this->config['db_name'])) {
	    $db_config = array();
	    if (isset($this->config['db_config'])) {
		$db_config = $this->config['db_config'];
	    }
	    $this->connectToDatabase('default', $this->config['db_driver'], $this->config['db_host'], $this->config['db_port'], $this->config['db_user'], $this->config['db_password'], $this->config['db_name'], $db_config);
	}
	
	// get the view class
	$view_class = $this->getViewClass();
	
	// figure out where the user is trying to go
	$route = $this->getRoute();
	
	// load the controller action
	$this->loadControllerAction($route['controller'], $route['action'], new Sodapop_Request($route['request']), new $view_class, isset($this->config['base_url']) ? $this->config['base_url'] : '/');
    }
    
    /**
     * This method can be called to clear the cache if APC is in use. It should
     * be used if any database tables have changed their definitions.
     */
    public function clearCache() {
	if (function_exists('apc_clear_cache')) {
	    apc_clear_cache();
            apc_clear_cache("user");
	}
    }
    
    /**
     * 
     */
    public function getCacheInfo() {
        if (function_exists('apc_cache_info')) {
            return apc_cache_info("user");
        } else {
            return false;
        }
    }
    
    /**
     * Loads the given controller and performs the action. If the given controller
     * or action doesn't exist it loads the 404 handler in the IndexController, and if 
     * there is an exception the 500 handler.
     * 
     * This method is generally not meant to be called by users.
     * 
     * @param string $controller
     * @param string $action
     * @param Sodapop_Request $request
     * @param Sodapop_View_Abstract $view
     * @param string $baseUrl
     */
    public function loadControllerAction($controller, $action, $request, $view, $baseUrl) {
        // echo $controller."_".$action;
	try {
	    $controller_name = ucfirst($controller).'Controller';;
	    $action_name = 'action'.ucfirst($action);
	    include_once('../'.$this->config['controller_path'].'/' . $controller_name . '.php');
	    if (!class_exists($controller_name) || !method_exists($controller_name, $action_name)) {
		// try first to get the 404 action of the IndexController
		if ($controller_name == 'IndexContoller') {
		    require('404.html');
		} else {
		    $this->loadControllerAction('index', '404', $request, $view, $baseUrl);
		}
                exit;
            }
            $controllerObj = new $controller_name($request, $view);
            $controllerObj->controller = $controller;
            $controllerObj->action = $action;
	    $controllerObj->setViewPathBase($this->config['view_path']);
	    $controllerObj->setViewPath($controller . '/' . $action);
	    if ($this->config['use_layouts']) {
		$controllerObj->setLayoutPathBase($this->config['layout_path']);
		$controllerObj->setLayoutPath('');
	    }
            $controllerObj->view->baseUrl = $baseUrl;
            $controllerObj->preDispatch();
            $controllerObj->$action_name();
            $controllerObj->postDispatch();
            $output = $controllerObj->render();
            $controllerObj->cleanup();
            echo $output;
        } catch (Exception $e) {
            loadControllerAction('index', '500', $request, $view, $baseUrl);
        }
        exit();
    }
    
    /**
     * Returns all of the table definitions for the given identifier.
     * 
     * @param string $connection_identifier
     * @return array
     */
    public function getTableDefinitions($connection_identifier) {
	if (isset($this->table_definitions[$connection_identifier])) {
	    return $this->table_definitions[$connection_identifier];
	} else {
	    return array();
	}
    }
    
    /**
     * Iterates over the application's connections and looks for a matching
     * table. If one is found it will have that connection define a class
     * for the given table.
     * 
     * @param string $table_name
     * @param string $class_name
     */
    public function defineTableClass($table_name, $class_name) {
	foreach ($this->table_definitions as $connection_identifier => $tables) {
	    foreach($tables as $db_table_name => $table_definition){
		if ($db_table_name == $table_name) {
		    $this->connections[$connection_identifier]->defineTableClass($table_name, $class_name, $table_definition);
		    return;
		}
	    }
	} 
    }
    
    /**
     * Returns an item from the config
     * 
     * @param string $key
     * @return mixed
     */
    public function getConfig($key) {
	if (isset($this->config[$key])) {
	    return $this->config[$key];
	} else {
	    return null;
	}
    }
    
    /**
     * Returns the requested database connection.
     * 
     * @param string $connection_identifier
     * @return Sodapop_Database_Abstract
     */
    public function getConnection($connection_identifier = 'default') {
	if (isset($this->connections[$connection_identifier])) {
	    return $this->connections[$connection_identifier];
	} else {
	    return null;
	}
    }
    
    /**
     * Sets a theme from the application's theme directory
     * 
     * @param type $theme
     */
    public function setTheme($theme) {
	if (file_exists('../themes/'.$theme)) {
	    $this->theme = $theme;
	}
    }
    
    /**
     * Returns the root directory of the theme.
     * 
     * @return string
     */
    public function getThemeRoot($controller = null, $action = null) {
	if (is_null($this->theme) || trim($this->theme) == '') {
	    return '../';
	} else {
            if (!is_null($controller) && array_key_exists($controller, $this->theme_ignored_actions)) {
                if (count($this->theme_ignored_actions[$controller]) == 0) {
                    return '../';
                } else if (!is_null($action) && in_array($action, $this->theme_ignored_actions[$controller])) {
                    return '../';
                }
            }
	    return '../themes/'.$this->theme.'/';
	}
    }
    /**
     * Connects to a new database.
     * 
     * @param string $connection_identifier
     * @param string $driver_class
     * @param string $hostname
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $database_name
     * @param array $db_config
     */
    public function connectToDatabase($connection_identifier, $driver_class, $hostname, $port, $user, $password, $database_name, $db_config = array()) {
	try {
	    $this->connections[$connection_identifier] = $driver_class::connect($hostname, $port, $user, $password, $database_name, $db_config, $connection_identifier);
	    if (getenv('USE_CACHE') != 'false' && function_exists('apc_exists') && apc_exists('table_definitions_'.$connection_identifier)) {
		$this->table_definitions[$connection_identifier] = apc_fetch('table_definitions_'.$connection_identifier);
	    } else {
		$this->table_definitions[$connection_identifier] = $this->connections[$connection_identifier]->getTableDefinitions();
		if (getenv('USE_CACHE') != 'false' && function_exists('apc_store')) {
		    apc_store('table_definitions_'.$connection_identifier, $this->table_definitions[$connection_identifier]);
		}
	    }
	} catch (Exception $e) {
	    exit('Error: '.$e->getMessage());
	}
    }
    
    /**
     * Turns debugging mode on or off.
     * 
     * @param boolean $value
     */
    public function setDebug($value) {
	if ($value) {
	    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
	    ini_set('display_errors', '1');
	    ini_set('html_errors', '1');
	} else {
	    ini_set('display_errors', '0');
	    ini_set('html_errors', '0');
	}
    }
    
    /**
     * Add a route to the routing table.
     * 
     * @param string $route
     * @param string $destination
     */
    public function addRoute($route, $destination) {
        $routes = array($route => $destination);
        $this->processRoutes($routes, false);
    }
    
    private function getRoute() {
	$request_parts = explode('/', strpos($_SERVER["REQUEST_URI"], '?') === false ? $_SERVER["REQUEST_URI"] : substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], '?')));
	$request_uri = '';
	foreach ($request_parts as $part) {
	    if (strlen(trim($part)) > 0) {
		$request_uri .= '/'.$part;
	    }
	}
	// test against the routes table
	foreach($this->routes as $route => $data) {
	    $matches = array();
	    $request = array();
	    if (preg_match_all('/'.str_replace('/', '\/', $route).'/', $request_uri, $matches, PREG_PATTERN_ORDER) == 1) {
		for($i = 0; $i < count($data['request']); $i++) {
		    if (isset($matches[$i + 1]) && isset($matches[$i + 1][0])) {
			$request[$data['request'][$i]] = $matches[$i + 1][0];
			$_REQUEST[$data['request'][$i]] = $request[$data['request'][$i]];
		    }
		}
		$controller_name = $data['controller'];
		if (is_numeric(substr($controller_name, 0 ,1))) {
		    $controller_name = 'n'.$controller_name;
		}
		return array(
		  'request' => $request,
		  'controller' => $controller_name,
		  'action' => $data['action']
		);
	    }
	}
	// still here? do the default
	$request_parts = explode('/', $request_uri);
	if ($request_parts[0] == '') {
	    array_shift($request_parts);
	}
	$request = array();
	if (count($request_parts) == 0 || trim($request_parts[0]) == '') {
	    return array(
		'request' => $request,
		'controller' => 'index',
		'action' => 'index'
	    );
	} else if(count($request_parts) == 1 || trim($request_parts[1]) == '') {
	    $controller_name = $request_parts[0];
	    if (is_numeric(substr($controller_name, 0 ,1))) {
		$controller_name = 'n'.$controller_name;
	    }
	    return array(
		'request' => $request,
		'controller' => $controller_name,
		'action' => 'index'
	    );
	} else if (count($request_parts) > 1) {
	    $controller_name = $request_parts[0];
	    if (is_numeric(substr($controller_name, 0 ,1))) {
		$controller_name = 'n'.$controller_name;
	    }
	    for($i = 2; $i < count($request_parts); $i++) {
		$request[$request_parts[$i]] = isset($request_parts[$i+1]) ? $request_parts[$i+1] : '';
		$_REQUEST[$request_parts[$i]] = $request[$request_parts[$i]];
		$i++;
	    }
	    return array(
		'request' => $request,
		'controller' => $controller_name,
		'action' => $request_parts[1]
	    );
	}
    }
    private function loadConfig() {
	if (getenv('USE_CACHE') != 'false' && function_exists('apc_exists') && apc_exists('sodapop_config')) {
	    $this->config = apc_fetch('sodapop_config');
            if (isset($this->config['theme'])) {
		if (!file_exists('../themes/'.$this->config['theme'])) {
		    exit('Error: Specified theme does not exist.');
		}
		$this->theme = $this->config['theme'];
	    }
	} else {
	    if (!$config_file_text = file_get_contents("../conf/sodapop.json")) {
		exit('Error: Config file not found.');
	    }
	    if (trim($config_file_text) != '') {
		if (!$config = json_decode($config_file_text, true)) {
		    exit('Error: Config file not in JSON format.');
		}
	    } else {
		$config = array();
	    }
	    if (isset($config['default'])) {
		$this->config = $config['default'];
	    }
	    if (isset($config['hosts']) && isset($config['hosts'][strtolower($_SERVER['SERVER_NAME'])])) {
		foreach ($config['hosts'][strtolower($_SERVER['SERVER_NAME'])] as $key => $value) {
		    $this->config[$key] = $value;
		}
	    }
	    // add the defaults if they aren't already specified.
	    if (!isset($this->config['controller_path'])) {
		$this->config['controller_path'] = 'controllers';
	    }
	    if (!isset($this->config['view_path'])) {
		$this->config['view_path'] = 'views';
	    }
	    if (!isset($this->config['use_layouts'])) {
		$this->config['use_layouts'] = true;
	    }
	    if (!isset($this->config['layout_path'])) {
		$this->config['layout_path'] = 'layouts';
	    }
	    if (!isset($this->config['db_driver'])) {
		$this->config['db_driver'] = 'Sodapop_Database_Pdo';
	    }
	    if (!isset($this->config['debug'])) {
		$this->config['debug'] = false;
	    }
	    if (isset($this->config['theme'])) {
		if (!file_exists('../themes/'.$this->config['theme'])) {
		    exit('Error: Specified theme does not exist.');
		}
		$this->theme = $this->config['theme'];
	    }
            if (isset($this->config['theme_ignore_actions'])) {
		if (is_array($this->config['theme_ignore_actions'])) {
                    foreach($this->config['theme_ignore_actions'] as $action) {
                        $parts = explode('/', $action);
                        if(count($parts) == 1) {
                            $this->theme_ignored_actions[$parts[0]] = array();
                        } else {
                            if (!array_key_exists($parts[0], $this->theme_ignored_actions)) {
                                $this->theme_ignored_actions[$parts[0]] = array();
                            }
                            if(!in_array($parts[1], $this->theme_ignored_actions[$parts[0]])) {
                                $this->theme_ignored_actions[$parts[0]][] = $parts[1];
                            }
                        }
                    }
                }
	    }
            if (getenv('USE_CACHE') != 'false' && function_exists('apc_store')) {
		apc_store('sodapop_config', $this->config);
	    }
	}
	if (getenv('USE_CACHE') != 'false' && function_exists('apc_exists') && apc_exists('sodapop_routes')) {
	    $this->routes = apc_fetch('sodapop_routes');
	} else {
	    if ($routes_file_text = file_get_contents("../conf/routes.json")) {
		if (trim($routes_file_text) != '') {
		    if (!$routes_array = json_decode($routes_file_text, true)) {
			exit('Error: Routes file not in JSON format.');
		    }
		} else {
		    $routes_array = array();
		}
		$this->processRoutes($routes_array);
	    }
	}
	$this->setDebug($this->config['debug']);
    }
    private function processRoutes($routes_array, $exit_on_error = true) {
	foreach($routes_array as $key => $value) {
	    $path_items = explode('/', $key);
	    $route = '';
	    $request_items = array();
	    $destination = explode('/', $value);
	    if (count($destination) != 2) {
                if ($exit_on_error) {
                    exit('Error: Route "'.$key.'" has an invalid destination.');
                } else {
                    throw new Exception('Route "'.$key.'" has an invalid destination.');
                }
	    }
	    foreach($path_items as $path_item) {
		if(substr($path_item, 0, 1) == '<' && substr($path_item, strlen($path_item) - 1, 1) == '>') {
		    if ($colon_pos = strpos($path_item, ':')) {
			$request_items[] = substr($path_item, 1, $colon_pos - 1);
			$route .= '/('.substr($path_item, $colon_pos + 1, -1).')';
		    } else {
                        if ($exit_on_error) {
                            exit('Error: Route "'.$key.'" malformed.');
                        } else {
                            throw new Exception('Route "'.$key.'" malformed.');
                        }
		    }
		} else {
		    $route .= '/'.$path_item;
		}
	    }
	    $this->routes[$route] = array(
		'request' => $request_items,
		'controller' => $destination[0],
		'action' => $destination[1]
	    );
	}
	if (getenv('USE_CACHE') != 'false' && function_exists('apc_store')) {
	    apc_store('sodapop_routes', $this->routes);
	}
    }
    private function getViewClass() {
	if (isset($this->config['view_class'])) {
	    if (class_exists($this->config['view_class'])) {
		$class_name = $this->config['view_class'];
		$temp = new $class_name;
		if (!is_subclass_of($temp, 'Sodapop_View_Abstract')) {
		    exit('Error: View class '.$class_name.' is not a subclass of Sodapop_View_Abstract.');
		} 
		return $class_name; 
	    } else {
		exit('Error: View class '.$this->config['view_class'].' does not exist.');
	    }
	} else {
	    return 'Sodapop_View_Simple';
	}
    }
    
    
}

/**
 * Most of Sodapop's magic goes through this function.
 *
 * @param string $className
 */
function sodapop_autoloader($className) {
        // print "autoloading $className\n";
    $application = Sodapop_Application::getInstance();
    $classNameParts = explode('_', $className);
    include_once(implode('/', $classNameParts) . '.php');
    include_once($className . '.php');
    if (!class_exists($className)) {
	// start looking for models in the application's table list
	$modelName = (substr($className, 0, 14) == 'Sodapop_Model_' ? substr($className, 14) : $className);
	try {
	    $application->defineTableClass(Sodapop_Inflector::camelCapsToUnderscores($modelName, false), $className);
	} catch(Exception $e) {
	    // do nothing   
	}
    }
}

spl_autoload_register('sodapop_autoloader');

function __unserialize($className) {
    __autoload($className);
}

function createClass($className, $extends, $fields = array()) {
    $classDef = 'class ' . $className . ' extends ' . $extends . ' { ';
    foreach ($fields as $name => $value) {
        $classDef . ' $' . $name . ' = "' . $value . '"; ';
    }
    $classDef .= '}';
    eval($classDef);
}

function determine_mime_type($filePath, $mimeFile = 'mime.ini') {
    $standard_types = array('css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json', 'html' => 'text/html', 'htm' => 'text/html');
    $extension = strtolower(substr($filePath, strrpos($filePath, '.') + 1));
    if (array_key_exists($extension, $standard_types)) {
	return $standard_types[$extension];
    } else if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $retval = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $retval;
    } else {
        $types = parse_ini_file($mimeFile);
        $extension = substr($filePath, strrpos($filePath, '.') + 1);
        if (isset($types[$extension])) {
            return $types[$extension];
        } else {
            return 'application/octet-stream';
        }
    }
}
