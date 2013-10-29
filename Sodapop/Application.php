<?php

class Sodapop_Application {
    
    private $config = array();
    private $routes = array();
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
	$this->loadConfig();
	$route = $this->getRoute();
	var_dump($route);
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
            include_once('../controllers/' . $controller . '.php');
            if (!class_exists($controller) || !method_exists($controller, $action)) {
		// try first to get the 404 action of the IndexController
		if ($controller == 'IndexContoller') {
		    require_once('404.html');
		} else {
		    loadControllerAction('IndexController', 'action404', $request, $view, $baseUrl);
		}
                exit;
            }
            $controllerObj = new $controllerName($this, $request, $view);
            $controllerObj->controller = $controller;
            $controllerObj->action = $action;
            $controllerObj->setViewPath($controller . '/' . $action);
            $controllerObj->view->baseUrl = $baseUrl;
            $controllerObj->preDispatch();
            $controllerObj->$action();
            $controllerObj->preDispatch();
            $output = $controllerObj->render();
            $controllerObj->cleanup();
            echo $output;
        } catch (Exception $e) {
            loadControllerAction('IndexController', 'action500', $request, $view, $baseUrl);
        }
        exit();
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
		$controller_name = ucfirst(strtolower($data['controller'])).'Controller';
		if (is_numeric(substr($controller_name, 0 ,1))) {
		    $controller_name = 'N'.$controller_name;
		}
		return array(
		  'request' => $request,
		  'controller' => $controller_name,
		  'action' => 'action'.ucfirst(strtolower($data['action']))
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
		'controller' => 'IndexController',
		'action' => 'actionIndex'
	    );
	} else if(count($request_parts) == 1 || trim($request_parts[1]) == '') {
	    $controller_name = ucfirst(strtolower($request_parts[0])).'Controller';
	    if (is_numeric(substr($controller_name, 0 ,1))) {
		$controller_name = 'N'.$controller_name;
	    }
	    return array(
		'request' => $request,
		'controller' => $controller_name,
		'action' => 'actionIndex'
	    );
	} else if (count($request_parts) > 1) {
	    $controller_name = ucfirst(strtolower($request_parts[0])).'Controller';
	    if (is_numeric(substr($controller_name, 0 ,1))) {
		$controller_name = 'N'.$controller_name;
	    }
	    for($i = 2; $i < count($request_parts); $i++) {
		$request[$request_parts[$i]] = isset($request_parts[$i+1]) ? $request_parts[$i+1] : '';
		$i++;
	    }
	    return array(
		'request' => $request,
		'controller' => $controller_name,
		'action' => 'action'.ucfirst(strtolower($request_parts[1]))
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
    
}

/**
 * Most of Sodapop's magic goes through this function.
 *
 * @param string $className
 */
function __autoload($className) {
    global $application;
    $classNameParts = explode('_', $className);
    include_once(implode('/', $classNameParts) . '.php');
    include_once($className . '.php');
    if (!is_null($application)) {
        include_once($application->config['controller.directory'] . '/' . $className . '.php');
        include_once($application->config['controller.directory'] . '/../models/' . $className . '.php');
    }
    if (!class_exists($className)) {
        // test standard controllers
        switch ($className) {
            case 'IndexController':
                createClass('IndexController', 'Standard_Controller_Index');
                break;
            case 'AuthenticationController':
                createClass('AuthenticationController', 'Standard_Controller_Authentication');
                break;
        }
        // these only work if there is a user with a connection in the session
        if (!class_exists($className)) {
            // start looking for models in the user's table list, then their form list
            $modelName = (substr($className, 0, 14) == 'Sodapop_Model_' ? substr($className, 14) : $className);
            /*
              if ($_SESSION['user']->hasTablePermission(Sodapop_Inflector::camelCapsToUnderscores($modelName, false), 'SELECT')) {
              $_SESSION['user']->connection->defineTableClass(Sodapop_Inflector::camelCapsToUnderscores($modelName, false), $className);
              } else if ($_SESSION['user']->hasFormPermission(Sodapop_Inflector::camelCapsToUnderscores($modelName, false), null, 'VIEW')) {
              $_SESSION['user']->connection->defineFormClass(Sodapop_Inflector::camelCapsToUnderscores($modelName, false), $className);
              }
             */
            try {
                if (!is_null($application->connection)) {
                  $application->connection->defineTableClass($application->config['model.database.schema'],  Sodapop_Inflector::camelCapsToUnderscores($modelName, false), $className);
                }
            } catch(Exception $e) {
                // do nothing   
            }
        }
        if (!class_exists($className)) {
            // start looking through the available models to see if we need to instantiate a controller
            if (substr($className, -10) == 'Controller') {
                if (in_array(Sodapop_Inflector::camelCapsToUnderscores(substr($className, 0, strlen($className) - 10), false), $_SESSION['user']->availableModels)) {
                    createClass($className, 'Standard_Controller_Model');
                }
            }
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
