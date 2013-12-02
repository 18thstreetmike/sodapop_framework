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
 * Sodapop_Inflector is used to move back and forth between plural and singular 
 * object names.
 * 
 * It wraps another class, Inflect, which was compiled by Sho Kuwamoto. See more 
 * details in Inflect.php.
 */
require('Inflect.php');

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
