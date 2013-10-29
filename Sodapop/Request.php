<?php

class Sodapop_Request {
    private $method = 'get';
    private $values = array();
    private $files = array();
    private $headers = null;
    
    public function __construct($additional_values = null) {
	if (isset($_POST)) {
	    $this->method = 'post';
	}
	foreach($_REQUEST as $key => $val) {
	    $this->values[$key] = $val;
	}
	if (!is_null($additional_values) && is_array($additional_values)) {
	    foreach($additional_values as $key => $val) {
		$this->values[$key] = $val;
	    }
	}
	if (isset($_FILES)) {
	    foreach ($_FILES as $key => $values) {
		$this->files[$key] = $values;
	    }
	}
    }
    
    public function getMethod() {
	return $this->method;
    }
    public function isPost() {
	return $this->method == 'post';
    }
    public function isGet() {
	return $this->method == 'get';
    }
    public function get($value) {
	if (isset($this->values[$value])) {
	    return $this->values[$value];
	} else {
	    return null;
	}
    }
    public function getFile($value) {
	if (isset($this->files[$value])) {
	    return $this->files[$value];
	} else {
	    return null;
	}
    }
    public function getFileContents($value) {
	if (isset($this->files[$value])) {
	    return file_get_contents($this->files[$value]['tmp_name']);
	} else {
	    return null;
	}
    }
    public function moveFile($value, $location) {
	if (isset($this->files[$value])) {
	    return @move_uploaded_file($this->files[$value]['tmp_name'], $location);
	} else {
	    throw new Exception('File "'.$value.'" does not exist');
	}
    }
    public function getHeader($header) {
	if (is_null($this->headers)) {
	    $this->headers = http_get_request_headers();
	}
	if (isset($this->headers[$header])) {
	    return $this->headers[$header];
	} else {
	    return null;
	}
    }
}