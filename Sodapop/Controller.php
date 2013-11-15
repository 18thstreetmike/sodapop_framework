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
 * Sodapop_Controller is the base class for Sodapop controllers.
 * 
 * 
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
	
	protected $viewPathBase = '';
	
	protected $layoutPathBase = '';

	public function __construct($request, $view) {
		$this->request = $request;
		$this->view = $view;
	}

	/**
	 * Allows users to override the default view.
	 * 
	 * @param string $viewPath
	 */
	public function setViewPath($viewPath) {
		$this->viewPath = $this->viewPathBase.$viewPath;
	}

	/**
	 * Allows users to override the default layout.
	 * 
	 * @param string $layoutPath
	 */
	public function setLayoutPath($layoutPath) {
		$this->layoutPath = $this->layoutPathBase.$layoutPath;
	}
	
	/**
	 * Allows users to override the root directory for views.
	 * 
	 * @param string $viewPath
	 */
	public function setViewPathBase($viewPathBase) {
	    $this->viewPathBase = Sodapop_Application::getInstance()->getThemeRoot().$viewPathBase.'/';
	}

	/**
	 * Allows users to override the default layout directory.
	 * 
	 * @param string $layoutPath
	 */
	public function setLayoutPathBase($layoutPathBase) {
		$this->layoutPathBase = Sodapop_Application::getInstance()->getThemeRoot().$layoutPathBase.'/';
	}
	

	/**
	 * Called by the application before the action.
	 */
	public function preDispatch() {

	}

	/**
	 * Called by the application after the action is called but before
	 * the view renders.
	 */
	public function postDispatch() {

	}

	/**
	 * Outputs the page. It can either render the supplied text directly,
	 * or invoke the view's render method.
	 * 
	 * @param string $text
	 * @return string
	 */
	public function render ($text = null) {
	    if (!is_null($this->viewPath) && !$this->already_rendered && is_null($text)) {
		$this->already_rendered = true;
		$this->view->controller = $this->controller;
		$this->view->action = $this->action;
		$this->view->request = $this->request;
		$this->view->viewFile = $this->viewPath;
		if (!is_null($this->layoutPath)) {
		    $this->view->layoutFile = $this->layoutPathBase.$this->layoutFile;
		}
		return $this->view->render();
	    } else if(!$this->already_rendered && !is_null($text)) {
		$this->already_rendered = true;
		echo $text;
	    } else {
		return "";
	    }
	}

	/**
	 * Performs any final cleanup needed. Called by the application after the view
	 * renders.
	 */
	public function cleanup() {

	}

	/**
	 * Forwards execution to the specified controller and action.
	 * 
	 * @param string $controller
	 * @param string $action
	 */
	public function forward($controller, $action) {
		Sodapop_Application::getInstance()->loadControllerAction($controller, $action, $this->request, $this->view, $this->view->baseUrl);
	}

	/**
	 * Redirects the user to the specified location
	 * 
	 * @param string $url
	 */
	public function redirect($url) {
		header('Location: '.$url);
		exit;
	}
	
	/**
	 * Sets an HTTP header
	 * 
	 * @param string $name
	 * @param string $value
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
