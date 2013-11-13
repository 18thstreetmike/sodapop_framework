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
 * Sodapop_Session is a wrapper for PHP's session functionality.
 * 
 * The methods are called statically.
 */
class Sodapop_Session {
    
    /**
     * Returns the given variable from the session, and null if it doesn't exist.
     * @param string $name
     * @return mixed
     */
    public static function get($name) {
	if (isset($_SESSION[$name])) {
	    return $_SESSION[$name];
	} else {
	    return null;
	}
    }
    
    /**
     * Returns true if the given variable exists.
     * 
     * @param string $name
     * @return boolean
     */
    public static function exists($name) {
	return isset($_SESSION[$name]);
    }
    
    /**
     * Adds the given variable to the session.
     * 
     * @param string $name
     * @param mixed $value
     */
    public static function set($name, $value) {  
	$_SESSION[$name] = $value;
    }
    
    /**
     * Destroys the session.
     */
    public static function destroy() {
	if (ini_get("session.use_cookies")) {
	    $params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 42000,
		$params["path"], $params["domain"],
		$params["secure"], $params["httponly"]
	    );
	}
	session_destroy();
    }
    
    /**
     * Deletes the given variable from the session.
     * 
     * @param string $key
     */
    public static function delete($key) {
	unset($_SESSION[$key]);
    }
}