<?php
/**
 * The Sodapop Inflector is used to move back and forth between plural and singular object names.
 *
 * It consists (almost) entirely of code compiled by Sho Kuwamoto at
 * http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
 * and was released under the MIT License.  As such, it still belongs in that license.
 *
 */
require_once('Inflect.php');

class Sodapop_Inflector {
    public static function pluralize( $string ) {
        return Inflect::pluralize($string);
    }

    public static function singularize( $string ) {
	return Inflect::singularize($string);
    }

    public static function underscoresToCamelCaps( $string, $keepCase = true, $initcaps = true ){
	$parts = explode('_', $string);
	$retval = '';
	for($i = 0; $i < count($parts); $i++) {
	    if (!$keepCase && $i == count($parts) - 1) {
			if ($i == 0 && !$initcaps) {
				$retval .= strtolower(Sodapop_Inflector::singularize($parts[$i]));
			} else {
				$retval .= ucfirst(Sodapop_Inflector::singularize($parts[$i]));
			}
	    } else {
			if ($i == 0 && !$initcaps) {
				$retval .= strtolower($parts[$i]);
			} else {
				$retval .= ucfirst($parts[$i]);
			}
	    }
	}
	return $retval;
    }

    public static function camelCapsToUnderscores( $string, $keepCase = true){
	$retval = '';
	for ($i = 0; $i < strlen($string); $i++) {
	    $char = substr($string, $i, 1);
	    if ($i == 0) {
		$retval .= strtolower($char);
	    } else if ($char == strtoupper($char)) {
		$retval .= '_'.strtolower($char);
	    } else {
		$retval .= $char;
	    }
	}
	$lastUnderscore = strrpos($retval, '_');
	if ($lastUnderscore == false) {
	    $retval = ($keepCase ? $retval : Sodapop_Inflector::pluralize($retval));
	} else {
	    $lastWord = substr($retval, $lastUnderscore + 1);
	    $retval = substr($retval, 0, $lastUnderscore + 1).($keepCase ? $lastWord : Sodapop_Inflector::pluralize($lastWord));
	}
	return $retval;
    }
}
