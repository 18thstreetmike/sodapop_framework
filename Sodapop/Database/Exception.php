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
 * Sodapop_Database_Exception is the class of exceptons thrown by Sodapop
 * database code. It allows for multiple error messages to be returned.
 *
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

