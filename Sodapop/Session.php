<?php

class Sodapop_Session {
    
    public static function get($name) {
	if (isset($_SESSION[$name])) {
	    return $_SESSION[$name];
	} else {
	    return null;
	}
    }
    
    public static function exists($name) {
	return isset($_SESSION[$name]);
    }
    
    public static function set($name, $value) {  
	$_SESSION[$name] = $value;
    }
    
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
    
    public static function delete($key) {
	unset($_SESSION[$key]);
    }
}