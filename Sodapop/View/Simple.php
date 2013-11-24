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
 * Sodapop_View_Simple is the default PHP template view. 
 */
class Sodapop_View_Simple extends Sodapop_View_Abstract {
    public function  __construct($config ) {
	parent::__construct($config);
    }

    public function init() {
	
    }

    public function render() {
	// render the view portion of the template to a string
	ob_start();
	if (file_exists($this->viewFile.'.php')) {
	    require_once($this->viewFile.'.php');
	} else {
	    exit('Error: View template "'.$this->viewFile.'.php" does not exist.');
	}
	$viewContent = ob_get_clean();
        
	// if a layout is to be used, save the view content to the appropriate viewContent variable and render the view.
        if ($this->layoutFile) {
	    $this->viewContent = $viewContent;
	    ob_start();
	    if (file_exists($this->layoutFile.'.php')) {
		require_once($this->layoutFile.'.php');
	    } else {
		exit('Error: Layout template "'.$this->layoutFile.'.php" does not exist.');
	    }
	    $viewContent = ob_get_clean();
	    
	}
        
	return $viewContent;
    }
    
    public function renderPartial($viewPath) {
	require $this->viewFileBase.$viewPath.'.php';
    }
}
