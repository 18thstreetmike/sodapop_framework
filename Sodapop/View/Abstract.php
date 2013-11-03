<?php

/**
 * This class is the abstract class for views in the Sodapop Framework.
 *
 * @author michaelarace
 */
abstract class Sodapop_View_Abstract {
    protected $fields = array();

    protected $config = array();

    protected $layoutFile = null;

    protected $viewFile = null;
    
    protected $viewContent = null;

    public function __construct($config = null) {
	$this->config = $config;
    }

    public function __get($name) {
	return $this->fields[$name];
    }

    public function __set($name, $value) {
	if ($name == 'viewFile') {
	    $this->viewFile = $value;
	} else if ($name == 'layoutFile') {
	    $this->layoutFile = $value;
	} else if ($name == 'viewContent') {
	    $this->viewContent = $value;
	} else {
	    $this->fields[$name] = $value;
	}
    }

    /*
     * This function initializes the Sodapop_View and is called immedately after the view is constructed.
     */
    public abstract function init();

    public function prerender($layoutPath, $viewPath) {
	if (file_exists($layoutPath)) {
	    $this->layoutFile = $layoutPath;
	}

	if (file_exists($viewPath)) {
	    $this->viewFile = $viewPath;
	}
    }

    /**
     * This function is called after the controller has returned and the layout and view are assigned to the view.
     * It should return a string that represents the rendered output so that the application can echo it appropriately.
     */
    public abstract function render();
}
