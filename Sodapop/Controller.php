<?php
/**
 * This is the class that is extended to make application controllers.
 *
 * @author michaelarace
 */
class Sodapop_Controller {

	protected $request;

	public $view;

	public $controller;

	public $action;
        
        public $mimeType = 'text/html';

	protected $viewPath = 'index/index';

	protected $layoutPath = null;
	
	protected $layoutFile = 'layout';
	
	protected $already_rendered = false;

	public function __construct($request, $view) {
		$this->request = $request;
		$this->view = $view;
	}

	public function setViewPath($viewPath) {
		$this->viewPath = $viewPath;
	}

	public function setLayoutPath($layoutPath) {
		$this->layoutPath = $layoutPath;
	}

	public function preDispatch() {

	}

	public function postDispatch() {

	}

	public function render ($text = null) {
	    if (!is_null($this->viewPath) && !$this->already_rendered && is_null($text)) {
		$this->already_rendered = true;
		$this->view->controller = $this->controller;
		$this->view->action = $this->action;
		$this->view->request = $this->request;
		$this->view->viewFile = $this->viewPath;
		if (!is_null($this->layoutPath)) {
		    $this->view->layoutFile = $this->layoutPath.'/'.$this->layoutFile;
		}
		return $this->view->render();
	    } else if(!$this->already_rendered && !is_null($text)) {
		$this->already_rendered = true;
		echo $text;
	    } else {
		return "";
	    }
	}

	public function cleanup() {

	}

	/**
	 * Forwards execution to the specified controller and action.
	 * @param type $controller
	 * @param type $action
	 */
	public function forward($controller, $action) {
		Sodapop_Application::getInstance()->loadControllerAction($controller, $action, $this->request, $this->view, $this->view->baseUrl);
	}

	/**
	 * Redirects the user to the specified location
	 * 
	 * @param string $url: The URL to redirect to
	 */
	public function redirect($url) {
		header('Location: '.$url);
		exit;
	}
	
	/**
	 * Sets an HTTP header
	 * 
	 * @param type $name: the name of the header
	 * @param type $value: the value
	 */
	public function setHeader($name, $value){
	    header($name.": ".$value);
	}
	
	/**
	 * A default for the 404 handler
	 */
	public function action404(){
	    
	}
	
	/**
	 * A default for the 500 handler
	 */
	public function action500() {
	    
	}

}
