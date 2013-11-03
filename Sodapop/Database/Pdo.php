<?php

/**
 * Description of Sodapop_Database_Mysql
 *
 * @author michaelarace
 */
class Sodapop_Database_Pdo extends Sodapop_Database_Abstract {

    protected $connection = null;
    protected $schema_name = null;
    protected $connection_identifier = null;
    protected $server_type = null;
    
    public function __construct($connection, $schema_name, $connection_identifier, $server_type) {
	$this->connection = $connection;
	$this->schema_name = $schema_name;
	$this->connection_identifier = $connection_identifier;
	$this->server_type = $server_type;
    }
				    
    public static function connect($hostname, $port, $username, $password, $database, $config = array(), $connection_identifier = 'default') {
        $server_type = array_key_exists('server', $config) ? $config['server'] : 'mysql';
	try {
            // echo $hostname." ".$port." ".$username. ' '.$password. ' '. $database; die;
	    $connection = new PDO($server_type.':host='.$hostname.';port='.$port.';dbname='.$database, $username, $password);
            
	    return new Sodapop_Database_Pdo($connection, $database, $connection_identifier, $server_type);
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 1);
        }
    }

    public function getSchemaName() {
	return $this->schema_name;
    }
    
    public function destroy() {
        $this->connection = null;
    }

    public function runParameterizedQuery($query, $params) {
	$stmt = $this->connection->prepare($query);
	foreach ($params as $key => $value) {
	    $stmt->bindParam(':'.$key, $value);
        }
        try {
	    $stmt->execute();
	    return $this->resultsetToArray($stmt);
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }
    }

    public function runParameterizedUpdate($statement, $params) {
        $stmt = $this->connection->prepare($statement);
	foreach ($params as $key => $value) {
	    $stmt->bindParam(':'.$key, $value);
        }
        try {
            $stmt->execute();
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }
    }

    public function runQuery($query) {
        try {
	    $stmt = $this->connection->prepare($query);
	    $stmt->execute();
	    return $this->resultsetToArray($stmt);
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }
    }

    public function runUpdate($statement) {
        try {
            $stmt = $this->connection->prepare($statement);
	    $stmt->execute();
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }
    }

    public function defineTableClass($table_name, $class_name, $table_definition) {
        if (!isset($className) || is_null($className)) {
            $className = Sodapop_Inflector::underscoresToCamelCaps($tableName, false);
        }
         $columnString = 'protected $fieldDefinitions = array(';
        foreach ($table_definition['columns'] as $column) {
            if ($columnString != 'protected $fieldDefinitions = array(') {
                $columnString .= ',';
            }
            $columnString .= "'" . Sodapop_Inflector::underscoresToCamelCaps(strtolower($column['column_name']), true, false) . "' => array(";
            $columnString .= "'type_name' => '" . addslashes($column['column_type']) . "','null' => '" . ($column['nullable'] ? 'true' : 'false') . "','array_flag' => false";
            $columnString .= ")";
        }
        $columnString .= ');';
        $overriddenFunctions = '
                        protected $tableName = \''.addslashes($tableName).'\';

			public function loadData() {
			    if($this->isPrimaryKeySet()) {
				$result = Application::getInstance()->getConnection(\''.addslashes($this->connection_identifier).'\')->runQuery("SELECT * FROM ' . $tableName . ' WHERE ".$this->getPrimaryKeyWhereClause());
                                if (count($result) > 0) {
				    for($i = 0; $i < count($results); $i++) {
					foreach ($result[$i] as $key => $value) {
					    $this->fields[Sodapop_Inflector::underscoresToCamelCaps($key, true, false)] = $value;
					    $this->oldFields[Sodapop_Inflector::underscoresToCamelCaps($key, true, false)] = $value;
					}
				    }
				}
				$this->lazyLoaded = true;
			    }
			}
			
			protected function save($action = \'UPDATE\') {
				if (strtoupper($action) == \'DELETE\' && $this->isPrimaryKeySet()) {
					Application::getInstance()->getConnection(\''.addslashes($this->connection_identifier).'\')->runQuery("DELETE FROM ' . $tableName . ' WHERE ".$this->getPrimaryKeyWhereClause());
				} else {
					$setClause = \'\';
					foreach($this->fields as $fieldName => $newValue) {
						if (!isset($this->oldFields[$fieldName]) || $newValue != $this->oldFields[$fieldName]) {
							if ($setClause != \'\') {
								$setClause .= \',\';
							}
                                                        if (is_null($newValue) || $newValue === "null") {
                                                            $setClause .= Sodapop_Inflector::camelCapsToUnderscores($fieldName)." = null ";
                                                        } else {
                                                            $setClause .= Sodapop_Inflector::camelCapsToUnderscores($fieldName)." = \':{".$fieldName."}\'";
                                                        }
						}
					}
					if (trim($setClause) != "") {
						if (strtoupper($action) == \'INSERT\') {
							$statement = "INSERT INTO ' . strtolower($tableName) . ' SET ".$setClause;
                                                        $result = Application::getInstance()->getConnection(\''.addslashes($this->connection_identifier).'\')->runParameterizedUpdate($statement, $this->fields);
							if (count($this->primaryKey) == 1) {
							    $this->fields[$this->primaryKey[0]] = mysql_insert_id();
							}
						} else if (strtoupper($action) == \'UPDATE\') {
                                                        Application::getInstance()->getConnection(\''.addslashes($this->connection_identifier).'\')->runParameterizedUpdate("UPDATE ' . strtolower($tableName) . ' SET ".$setClause." WHERE ".$this->getPrimaryKeyWhereClause()." ", $this->fields);
						}
						$this->oldFields = $this->fields;
					}
				}
			}
			
			public function isPrimaryKeySet() {
			    foreach($this->primaryKey as $column) {
				if (!isset($this->fields[$column])) {
				    return false;
				}
			    }
			    return true;
			}
			
			public function getPrimaryKeyWhereClause() {
			    $retval = \'\';
			    foreach($this->primaryKey as $column) {
				if ($retval != "") {
				    $retval .= " AND ";
				}
				$retval .= $column ." = \'".addslashes($this->fields[$column])."\'"; 
			    }
			    return $retval;
			}
			';
        $classDef = "class " . $class_name . " extends Sodapop_Database_Table_Abstract {\n" . $columnString . "\n" . $overriddenFunctions . "\n}";
        // echo $classDef; die;
	eval($classDef);
    }
    
    public function getTableDefinitions() {
	$retval = array();
	if ($this->server_type == 'mysql') {
	    $result = Sodapop_Application::getInstance()->getConnection($this->connection_identifier)->runParameterizedQuery("SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema_name ORDER BY TABLE_NAME ASC, ORDINAL_POSITION ASC", array('schema_name' => $this->schema_name));
	    $current_table = null;
	    $current_data = array('metadata' => array('primary_key' => array('id')),'columns' => array());
	    foreach($result as $row) {
		if ($row['TABLE_NAME'] != $current_table) {
		    if (!is_null($current_table)) {
			$retval[$current_table] = $current_data;
		    }
		    $current_data = array('metadata' => array('primary_key' => array('id')), 'columns' => array());
		    $current_table = $row['TABLE_NAME'];
		}
		$current_data['columns'][] = array(
		    'column_name' => $row['COLUMN_NAME'],
		    'column_type' => $row['DATA_TYPE'],
		    'nullable' => $row['IS_NULLABLE'] == 'YES',
		    'scale' => $row['NUMERIC_SCALE'],
		    'precision' => $row['NUMERIC_PRECISION'],
		    'length' => $row['CHARACTER_MAXIMUM_LENGTH']
		);
	    }
	    if (!is_null($current_table)) {
		$retval[$current_table] = $current_data;
	    }
	    
	    // get the keys
	    $result = Sodapop_Application::getInstance()->getConnection($this->connection_identifier)->runParameterizedQuery("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = :schema_name AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY TABLE_NAME ASC, ORDINAL_POSITION ASC", array('schema_name' => $this->schema_name));
	    $current_table = null;
	    $current_key = array();
	    foreach($result as $row) {
		if ($row['TABLE_NAME'] != $current_table) {
		    if (!is_null($current_table)) {
			$retval[$current_table]['metadata']['primary_key'] = $current_key;
		    }
		    $current_key = array();
		    $current_table = $row['TABLE_NAME'];
		}
		$current_key[] = $row['COLUMN_NAME'];
	    }
	    if (!is_null($current_table)) {
		$retval[$current_table]['metadata']['primary_key'] = $current_key;
	    }
	}
	return $retval;
    }

    private function resultsetToArray($stmt) {
	$retval = array();
	while ($row = $stmt->fetch()) {
	   $retval[] = $row;
	}
	return $retval;
    }

    public function getConnectionIdentifier() {
	return $this->connection_identifier;
    }
}

