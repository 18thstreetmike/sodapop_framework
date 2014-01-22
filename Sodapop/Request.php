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
 * Sodapop_Request represents the request.
 * 
 * It is meant to be used in place of PHP's various superglobals.
 */
class Sodapop_Request {
    private $method = 'get';
    private $values = array();
    private $files = array();
    private $headers = null;
    
    public function __construct($additional_values = null) {
	$this->method = strtolower($_SERVER['REQUEST_METHOD']);
	foreach($_REQUEST as $key => $val) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $this->values[$key] = urldecode($val);
            } else {
                $this->values[$key] = $val;
            }
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
    
    /**
     * Returns the method.
     * 
     * @return string
     */
    public function getMethod() {
	return $this->method;
    }
    
    /**
     * Returns true if the method is a post.
     * 
     * @return boolean
     */
    public function isPost() {
	return $this->method == 'post';
    }
    
    /**
     * Returns true if the method is a get
     * 
     * @return boolean
     */
    public function isGet() {
	return $this->method == 'get';
    }
    
    /**
     * Returns values from the request, or that were parsed from the
     * URL according to the routes.
     * 
     * @param string $value
     * @return mixed
     */
    public function __get($value) {
	if (isset($this->values[$value])) {
	    return $this->values[$value];
	} else {
	    return null;
	}
    }
    
    /**
     * Returns true of the requested key is in the request
     * 
     * @param string $value
     * @return boolean
     */
    public function exists($value) {
	return isset($this->values[$value]);
    }
    
    /**
     * Returns data on the requested file. Available if there
     * was a file upload control on the posted page.
     * 
     * @param string $value
     * @return array
     */
    public function getFile($value) {
	if (isset($this->files[$value])) {
	    return $this->files[$value];
	} else {
	    return null;
	}
    }
    
    /**
     * Returns the requested file's contents.
     * 
     * @param string $value
     * @return string
     */
    public function getFileContents($value) {
	if (isset($this->files[$value])) {
	    return file_get_contents($this->files[$value]['tmp_name']);
	} else {
	    return null;
	}
    }
    
    /**
     * Moves the requested file to the given path location.
     * 
     * @param string $value
     * @param string $location
     * @return boolean
     * @throws Exception
     */
    public function moveFile($value, $location) {
	if (isset($this->files[$value])) {
	    return @move_uploaded_file($this->files[$value]['tmp_name'], $location);
	} else {
	    throw new Exception('File "'.$value.'" does not exist');
	}
    }
    
    public function validateCSRF() {
        if ($this->exists('_csrf_token') && $this->values['_csrf_token'] == Sodapop_Session::get('_csrf_token')) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Returns the given request header
     * 
     * @param string $header
     * @return string
     */
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
    
    /**
     * Returns the values on the request as an array.
     * 
     * @return array
     */
    public function asArray() {
	return $this->values;
    }
}