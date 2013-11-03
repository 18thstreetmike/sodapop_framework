<?php
/**
 * The Sodapop Database Exception class.
 *
 * @author michaelarace
 */
class Sodapop_Database_Exception extends Exception {

    public $errors = array();

    public function __construct($message = null, $code = 0, $errors = array()) {
	parent::__construct($message, $code);
	$this->errors = $errors;
    }

    public function getErrors() {
	return $this->errors;
    }
}

