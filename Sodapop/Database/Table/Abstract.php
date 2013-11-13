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
 * Sodapop_Database_Table_Abstract is the base class for models in Sodapop.
 * 
 * Each instance represents one row in the given table.
 * 
 * Subclasses of it are created automatically when a user tries to access
 * a table-based model. Users can subclass it directly or subclass the 
 * automatically-generated class files with Sodapop_Model_[TableName].
 */
abstract class Sodapop_Database_Table_Abstract {

    protected $tableName = null;
    public $primaryKey = array('id');
    protected $fields = array();
    protected $oldFields = array();
    protected $fieldDefinitions = array();
    protected $lazyLoaded = false;

    /**
     * The constructor. Takes a primary key scalar or an associative array for 
     * multi-column primary keys.
     * 
     * @param int or array $id a scalar or array representing the primary key for the row.
     */
    public function __construct($id = null) {
        if (!is_null($id)) {
	    if (is_array($id)) {
		foreach($id as $key => $value) {
		    $this->$key = $value;
		}
	    } else {
		if (count($this->primaryKey) == 1) {
		    $keyname = $this->primaryKey[0];
		    $this->$keyname = $id;
		}
	    }
	}
    }

    /**
     * The magic getter.
     * 
     * @param string $name the name of the column in camelcaps.
     * @return scalar
     */
    public function __get($name) {
        if (!$this->lazyLoaded) {
	    $canLookup = true;
	    foreach($this->primaryKey as $key) {
		if (is_null($this->$key)) {
		    $canLookup = false;
		}
	    }
	    if ($canLookup) {
		$this->loadData();
	    }
        }
        if (array_key_exists($name, $this->fieldDefinitions) && isset($this->fields[$name])) {
            // this is a regular field
            return $this->fields[$name];
        } else {
            return null;
        }
    }

    /**
     * The magic setter
     * 
     * @param string $name
     * @param scalar $value
     * @return object
     */
    public function __set($name, $value) {
        if (!$this->lazyLoaded) {
	    $canLookup = true;
	    foreach($this->primaryKey as $key) {
		if (is_null($this->$key)) {
		    $canLookup = false;
		}
	    }
	    if ($canLookup) {
		$this->loadData();
	    }
        }
        if (array_key_exists($name, $this->fieldDefinitions)) {
            $this->fields[$name] = $value;
	} 
	return $this;
    }

    /**
     * The magic call function
     * 
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            foreach ($arguments[0] as $key => $value) {
                if (array_key_exists($key, $this->fieldDefinitions)) {
                    $this->fields[$name] = $value;
                }
            }
        }
        switch ($name) {
            case 'update':
            case 'insert':
            case 'delete':
                $this->save(strtoupper($name));
                break;
        }
    }

    /**
     * Populates the data in the object which already has a primary
     * key set.
     */
    public abstract function loadData();

    /**
     * Saves the row to the database.
     */
    protected abstract function save($action = 'UPDATE');

    /**
     * Returns this object's data as an array.
     * 
     * @param boolean $switch_to_underscores
     * @return array
     */
    public function asArray($switch_to_underscores = false) {
	if (!$this->lazyLoaded) {
	    $canLookup = true;
	    foreach($this->primaryKey as $temp) {
		if (is_null($this->$temp)) {
		    $canLookup = false;
		}
	    }
	    if ($canLookup) {
		$this->loadData();
	    }
        }
	if ($switch_to_underscores) {
	    $retval = array();
	    foreach($this->fields as $field => $value) {
		$retval[Sodapop_Inflector::camelCapsToUnderscores($field)] = $value;
	    }
	    return $retval;
	} else {
	    return $this->fields;
	}
    }
}
