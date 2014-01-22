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
 * Sodapop_View_Abstract is the base class for Sodapop view objects.
 * 
 * Sodapop (theoretically) allows for other templating engines to be 
 * used to render views. Within a view template file, the reference $this
 * refers to an instance of a subclass of Sodapop_View_Abstract.
 *
 * @author michaelarace
 */
abstract class Sodapop_View_Abstract {
    protected $fields = array();

    protected $config = array();

    protected $layoutFile = null;

    protected $viewFile = null;
    
    protected $viewFileBase = null;
    
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
	} else if ($name == 'viewFileBase') {
	    $this->viewFileBase = $value;
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
    
    /**
     * This method is called within a view file to render a partial view.
     */
    public abstract function renderPartial($viewPath);
    
    /**
     * Generates a csrf token.
     * 
     * @param boolean If true, returns just the token. If false show the hidden input.
     * @return type
     */
    public function getCSRFToken($justToken = false) {
        if (!Sodapop_Session::exists('_csrf_token')) {
            Sodapop_Session::refreshCSRFToken();
        }
        if ($justToken) {
            return Sodapop_Session::get('_csrf_token');
        } else {
            return '<input type="hidden" id="csrf-token" name="_csrf_token" value="'.Sodapop_Session::get('_csrf_token').'" />';
        }
    }
}
