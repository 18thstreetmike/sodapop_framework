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
    protected $connectionIdentifier = 'default';

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
    public function loadData() {
        if($this->isPrimaryKeySet()) {
            $result = Sodapop_Application::getInstance()->getConnection($this->connectionIdentifier)->runQuery("SELECT * FROM ".$this->tableName." WHERE ".$this->getPrimaryKeyWhereClause());
            if (count($result) > 0) {
                for($i = 0; $i < count($result); $i++) {
                    foreach ($result[$i] as $key => $value) {
                        $this->fields[Sodapop_Inflector::underscoresToCamelCaps($key, true, false)] = $value;
                        $this->oldFields[Sodapop_Inflector::underscoresToCamelCaps($key, true, false)] = $value;
                    }
                }
            }
            $this->lazyLoaded = true;
        }
        return true;
    }

    /**
     * Saves the row to the database.
     */
    protected function save($action = 'UPDATE') {
            if (strtoupper($action) == 'DELETE' && $this->isPrimaryKeySet()) {
                    Sodapop_Application::getInstance()->getConnection($this->connectionIdentifier)->runQuery("DELETE FROM ".$this->tableName." WHERE ".$this->getPrimaryKeyWhereClause());
                    return true;
            } else {
                    $setClause = '';
                    foreach($this->fields as $fieldName => $newValue) {
                        if ($setClause != '') {
                                $setClause .= ',';
                        }
                        if (is_null($newValue) || $newValue === "null") {
                            $setClause .= " ".Sodapop_Inflector::camelCapsToUnderscores($fieldName)." = null ";
                        } else {
                            // $setClause .= " ".Sodapop_Inflector::camelCapsToUnderscores($fieldName)." = :".$fieldName;
                            $setClause .= " ".Sodapop_Inflector::camelCapsToUnderscores($fieldName)." = '".addslashes($newValue)."' ";
                        }
                    }
                    if (trim($setClause) != "") {
                            if (strtoupper($action) == 'INSERT') {
                                    $statement = "INSERT INTO ".$this->tableName." SET ".$setClause;
                                    // var_dump($this->fields);
                                    Sodapop_Application::getInstance()->getConnection($this->connectionIdentifier)->runParameterizedUpdate($statement, $this->fields);
                                    if (count($this->primaryKey) == 1) {
                                        $this->fields[$this->primaryKey[0]] = mysql_insert_id();
                                    }
                            } else if (strtoupper($action) == 'UPDATE') {
                                    // echo "UPDATE ".$this->tableName." SET ".$setClause." WHERE ".$this->getPrimaryKeyWhereClause();
                                    Sodapop_Application::getInstance()->getConnection($this->connectionIdentifier)->runUpdate("UPDATE ".$this->tableName." SET ".$setClause." WHERE ".$this->getPrimaryKeyWhereClause());
                                    // Sodapop_Application::getInstance()->getConnection($this->connectionIdentifier)->runParameterizedUpdate("UPDATE ".$this->tableName." SET ".$setClause." WHERE ".$this->getPrimaryKeyWhereClause(), $this->fields);
                            }
                            $this->oldFields = $this->fields;
                    }
                    return true;
            }
            return false;
    }

    /**
     * Sets the connection identifier for this model.
     * 
     * @param string $connectionIdentifier
     */
    public function setConnectionIdentifier($connectionIdentifier) {
        $this->connectionIdentifier = $connectionIdentifier;
    }
    
    /**
     * Returns the database connection used to create this model.
     * 
     * @return Sodapop_Database_Abstract
     */
    public function getConnection() {
        return Sodapop_Application::getInstance()->getConnection($this->connectionIdentifier);
    }
    
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
    
    /**
     * Returns true if the primary key is set for this object. 
     * 
     * @return boolean
     */
    protected function isPrimaryKeySet() {
        foreach($this->primaryKey as $column) {
            if (!isset($this->fields[$column])) {
                return false;
            }
        }
        return true;
    }
			
    /**
     * Returns the WHERE clause needed to address this row based on the table's
     * primary key.
     * 
     * @return string
     */
    protected function getPrimaryKeyWhereClause() {
        $retval = '';
        foreach($this->primaryKey as $column) {
            if ($retval != "") {
                $retval .= " AND ";
            }
            $retval .= $column ." = '".addslashes($this->fields[$column])."'"; 
        }
        return $retval;
    }
}
