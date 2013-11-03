<?php

/**
 * The base class for a table or form.
 *
 */
abstract class Sodapop_Database_Table_Abstract {

    protected $tableName = null;
    public $id = null;
    public $primaryKey = array('id');
    protected $fields = array();
    protected $oldFields = array();
    protected $fieldDefinitions = array();
    protected $lazyLoaded = false;

    public function __construct($id = null) {
        $this->id = $id;
    }

    public function __get($name) {
        if (!$this->lazyLoaded && $this->id) {
            $this->loadData();
        }
        if (array_key_exists($name, $this->fieldDefinitions) && isset($this->fields[$name])) {
            // this is a regular field
            return $this->fields[$name];
        } else {
            return null;
        }
    }

    public function __set($name, $value) {
        if (!$this->lazyLoaded && $this->id) {
            $this->loadData();
        }
        if (array_key_exists($name, $this->fieldDefinitions) && isset($this->fields[$name])) {
            $this->fields[$name] = $value;
	}
	return $this;
    }

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

    public abstract function loadData();

    protected abstract function save($action = 'UPDATE');

    public function getFieldDefinitions() {
        return $this->fieldDefinitions;
    }

    public function getFieldDefinition($field) {
        if (isset($this->fieldDefinitions[$field])) {
            return $this->fieldDefinitions[$field];
        } else {
            return null;
        }
    }

    public function getChildTableDefinitions() {
        return $this->childTableDefinitions;
    }

}
