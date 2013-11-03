<?php

/**
 * This implements a 
 *
 * @author mike
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
}
