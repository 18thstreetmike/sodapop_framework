<?php

class Sodapop_Application {
    
    private $config = array();
    private $routes = array();
    private $connections = array();
    private $table_definitions = array();
    private static $application = null;
    
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
    
    /**
     * This method is called when the application loads.
     */
    public function run() {
	apc_clear_cache();
	// load the config
	$this->loadConfig();
	
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
     * This method can be called to clear the cache if APC is in use.
     */
    public function clearCache() {
	if (function_exists('apc_clear_cache')) {
	    apc_clear_cache();
	}
    }
    
    
    public function loadControllerAction($controller, $action, $request, $view, $baseUrl) {
        try {
	    $controller_name = ucfirst($controller).'Controller';;
	    $action_name = 'action'.ucfirst($action);
	    include_once('../controllers/' . $controller_name . '.php');
            if (!class_exists($controller_name) || !method_exists($controller_name, $action_name)) {
		// try first to get the 404 action of the IndexController
		if ($controller_name == 'IndexContoller') {
		    require_once('404.html');
		} else {
		    $this->loadControllerAction('index', '404', $request, $view, $baseUrl);
		}
                exit;
            }
            $controllerObj = new $controller_name($request, $view);
            $controllerObj->controller = $controller;
            $controllerObj->action = $action;
	    $controllerObj->setViewPath($this->config['view_path'].'/'.$controller . '/' . $action);
	    if ($this->config['use_layouts']) {
		$controllerObj->setLayoutPath($this->config['layout_path']);
	    }
            $controllerObj->view->baseUrl = $baseUrl;
            $controllerObj->preDispatch();
            $controllerObj->$action_name();
            $controllerObj->preDispatch();
            $output = $controllerObj->render();
            $controllerObj->cleanup();
            echo $output;
        } catch (Exception $e) {
            loadControllerAction('index', '500', $request, $view, $baseUrl);
        }
        exit();
    }
    
    public function getTableDefinitions($connection_identifier) {
	if (isset($this->table_definitions[$connection_identifier])) {
	    return $this->table_definitions[$connection_identifier];
	} else {
	    return array();
	}
    }
    
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
    
    public function getConnection($connection_identifier = 'default') {
	if (isset($this->connections[$connection_identifier])) {
	    return $this->connections[$connection_identifier];
	} else {
	    return null;
	}
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
	if (getenv('USE_CACHE') && function_exists('apc_exists') && apc_exists('sodapop_config')) {
	    $this->config = apc_fetch('sodapop_config');
	} else {
	    if (!$config_file_text = file_get_contents("../conf/sodapop.json")) {
		exit('Error: Config file not found.');
	    }
	    if (!$config = json_decode($config_file_text, true)) {
		exit('Error: Config file not in JSON format.');
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
		$this->config['controller_path'] = '../controllers';
	    }
	    if (!isset($this->config['view_path'])) {
		$this->config['view_path'] = '../views';
	    }
	    if (!isset($this->config['use_layouts'])) {
		$this->config['use_layouts'] = true;
	    }
	    if (!isset($this->config['layout_path'])) {
		$this->config['layout_path'] = '../layouts';
	    }
	    if (!isset($this->config['db_driver'])) {
		$this->config['db_driver'] = 'Sodapop_Database_Pdo';
	    }
	    if (!isset($this->config['db_config'])) {
		$this->config['db_config'] = array('server' => 'mysql');
	    }
	    if (getenv('USE_CACHE') && function_exists('apc_store')) {
		apc_store('sodapop_config', $this->config);
	    }
	}
	if (getenv('USE_CACHE') && function_exists('apc_exists') && apc_exists('sodapop_routes')) {
	    $this->routes = apc_fetch('sodapop_routes');
	} else {
	    if ($routes_file_text = file_get_contents("../conf/routes.json")) {
		if (!$routes_array = json_decode($routes_file_text, true)) {
		    exit('Error: Routes file not in JSON format.');
		}
		$this->processRoutes($routes_array);
	    }
	}
    }
    private function processRoutes($routes_array) {
	foreach($routes_array as $key => $value) {
	    $path_items = explode('/', $key);
	    $route = '';
	    $request_items = array();
	    $destination = explode('/', $value);
	    if (count($destination) != 2) {
		exit('Error: Route "'.$key.'" has an invalid destination.');
	    }
	    foreach($path_items as $path_item) {
		if(substr($path_item, 0, 1) == '<' && substr($path_item, strlen($path_item) - 1, 1) == '>') {
		    if ($colon_pos = strpos($path_item, ':')) {
			$request_items[] = substr($path_item, 1, $colon_pos - 1);
			$route .= '/('.substr($path_item, $colon_pos + 1, -1).')';
		    } else {
			exit('Error: Route "'.$key.'" malformed.');
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
	if (getenv('USE_CACHE') && function_exists('apc_store')) {
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
    
    private function connectToDatabase($connection_identifier, $driver_class, $hostname, $port, $user, $password, $database_name, $db_config) {
	try {
	    $this->connections[$connection_identifier] = $driver_class::connect($hostname, $port, $user, $password, $database_name, $db_config, $connection_identifier);
	    if (getenv('USE_CACHE') && function_exists('apc_exists') && apc_exists('table_definitions_'.$connection_identifier)) {
		$this->table_definitions[$connection_identifier] = apc_fetch('table_info_'.$connection_identifier);
	    } else {
		$this->table_definitions[$connection_identifier] = $this->connections[$connection_identifier]->getTableDefinitions();
		if (getenv('USE_CACHE') && function_exists('apc_store')) {
		    apc_store('table_definitions_'.$connection_identifier, $this->table_definitions);
		}
	    }
	} catch (Exception $e) {
	    exit('Error: '.$e->getMessage());
	}
    }
}

/**
 * Most of Sodapop's magic goes through this function.
 *
 * @param string $className
 */
function __autoload($className) {
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
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $retval = finfo_file($finfo, $path);
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
