<?php

/**
 * Description of Sodapop_Database_Mysql
 *
 * @author michaelarace
 */
class Sodapop_Database_Mysql extends Sodapop_Database_Abstract {

    protected $connection;
    
    public function __construct($connection = null) {
        $this->connection = $connection;
    }
    
    public static function connect($hostname, $port = '3306', $username, $password, $database) {
        try {
            // echo $hostname." ".$port." ".$username. ' '.$password. ' '. $database; die;
            $connection = mysql_connect($hostname . ':' . $port, $username, $password);
            mysql_select_db($database, $connection);
            // var_dump($connection);

            $error = mysql_error();
            if ($error != '') {
                throw new Sodapop_Database_Exception($error, 1);
            }
            return new Sodapop_Database_Mysql($connection);
            
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 1);
        }
    }

    public static function getUser($hostname, $port, $username, $password, $schema, $environment, $group, $availableModels) {
        // connect to the database and set the application
        $connection = new Sodapop_Database_Mysql();
        try {
            $connection->connect($hostname, $port, $username, $password, $schema);
        } catch (Exception $e) {
            // echo $e->getMessage();
            throw new Sodapop_Database_Exception($e->getMessage(), 2);
        }

        // create the user
        $user = new Sodapop_User($connection);

        // assign the username
        $user->username = $username;

        $user->properties = array();

        // load the permissions
        $user->permissions = array();

        // load the roles
        $user->roles = array();

        // load the table permissions
        $tablePermissionsResult = $connection->runQuery('SHOW TABLES');
        $tablePermissions = array();
        foreach ($tablePermissionsResult['data'] as $row) {
            $tablePermissions[strtoupper($row[0])] = array('SELECT', 'INSERT', 'UPDATE', 'DELETE');
        }
        $user->tablePermissions = $tablePermissions;

        $user->formPermissions = array();

        $user->procedurePermissions = array();

        $user->serverPermissions = array();

        $user->applicationPermissions = array();

        $validAvailableModels = array();
        if (is_array($availableModels)) {
            foreach ($availableModels as $model) {
                if ($user->hasTablePermission($model, 'SELECT') || $user->hasFormPermission($model, false, 'VIEW')) {
                    $validAvailableModels[] = $model;
                }
            }
        }

        $user->availableModels = $validAvailableModels;


        return $user;
    }

    public function destroy() {
        mysql_close($this->connection);
        return $this;
    }

    public function runParameterizedQuery($query, $params) {

        foreach ($params as $key => $value) {
            $query = str_replace(':' . $key, str_replace("'", '\\\'', $value), $query);
        }
        try {
            $result = mysql_query($query);
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }

        $error = mysql_error();
        if ($error) {
            throw new Sodapop_Database_Exception($error, 3);
        } else {
           $retval = array();
            $retval['data'] = array();
            $retval['columns'] = array();
            while ($row = mysql_fetch_array($result)) {
                $retval['data'][] = $row;
                if (count($retval['columns']) == 0) {
                    $keys = array_keys($row);
                    foreach($keys as $key) {
                        if (!is_numeric($key)) {
                            $retval['columns'][] = array('columnname' => $key);
                        }
                    }
                }
            }
            return $retval;
        }
    }

    public function runParameterizedUpdate($statement, $params) {
        foreach ($params as $key => $value) {
            $statement = str_replace(':{' . $key .'}', str_replace("'", '\\\'', $value), $statement);
        }
        try {
            mysql_query($statement);
            $error = mysql_error();
            if ($error != '') {
                throw new Sodapop_Database_Exception($error, 3);
            } else {
                if (substr(strtoupper(trim($statement)), 0, 6) == 'INSERT') {
                    return mysql_insert_id();
                } else {
                    return true;
                }
            }
        } catch (Exception $e) {
            // echo $statement;
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }
    }

    public function runQuery($query) {
        try {
            $result = mysql_query($query);
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }

        $error = mysql_error();
        if ($error) {
            throw new Sodapop_Database_Exception($error, 3);
        } else {
            $retval = array();
            $retval['data'] = array();
            $retval['columns'] = array();
            while ($row = mysql_fetch_array($result)) {
                $retval['data'][] = $row;
                if (count($retval['columns']) == 0) {
                    $keys = array_keys($row);
                    foreach($keys as $key) {
                        if (!is_numeric($key)) {
                            $retval['columns'][] = array('columnname' => $key);
                        }
                    }
                }
            }
            return $retval;
        }
    }

    public function runUpdate($statement) {
        try {
            mysql_query($statement);
            $error = mysql_error();
            if ($error != '') {
                throw new Sodapop_Database_Exception($error, 3);
            } else {
                if (substr(strtoupper(trim($statement)), 0, 6) == 'INSERT') {
                    return mysql_insert_id();
                } else {
                    return true;
                }
            }
        } catch (Exception $e) {
            throw new Sodapop_Database_Exception($e->getMessage(), 3);
        }
    }

    public function defineTableClass($schema, $tableName, $className) {
        // verify the table exists
        $result = mysql_query("SELECT count(*) as table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".  addslashes($schema)."' AND TABLE_NAME = '".addslashes($tableName)."'");
        $row = mysql_fetch_assoc($result);
        if ($row['table_count'] == 0) {
            throw new Sodapop_Database_Exception("Table not found.", 4);
        }
        
        if (!isset($className) || is_null($className)) {
            $className = Sodapop_Inflector::underscoresToCamelCaps($tableName, false);
        }
        $columnString = $this->getColumnDefinitionString($tableName);
        $overriddenFunctions = '
                        protected $tableName = \''.$tableName.'\';

			public function loadData() {
				$result = $_SESSION[\'user\']->connection->runQuery("SELECT * FROM ' . $tableName . ' WHERE id = \'".$this->id."\' ");
                                if (count($result) > 0) {
					for($i = 0; $i < count($result[\'columns\']); $i++) {
					$this->fields[Sodapop_Inflector::underscoresToCamelCaps($result[\'columns\'][$i][\'columnname\'], true, false)] = $result[\'data\'][0][$i];
					$this->oldFields[Sodapop_Inflector::underscoresToCamelCaps($result[\'columns\'][$i][\'columnname\'], true, false)] = $result[\'data\'][0][$i];
					}
				}
				$this->lazyLoaded = true;
			}

			public function getSubtableChildIds($subtableName, $parentRowId) {
				return $_SESSION[\'user\']->connection->runUpdate("SELECT id FROM ".$subtableName." WHERE parent_table_id = \'".$parentRowId."\'");
			}

			protected function save($action = \'UPDATE\') {
				if (strtoupper($action) == \'DELETE\' && $this->id) {
					$_SESSION[\'user\']->connection->runQuery("DELETE FROM ' . $tableName . ' WHERE id = \'".$this->id."\' ");
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
                                                        $result = $_SESSION[\'user\']->connection->runParameterizedUpdate($statement, $this->fields);
							$this->id = mysql_insert_id();
						} else if (strtoupper($action) == \'UPDATE\') {
                                                        $_SESSION[\'user\']->connection->runParameterizedUpdate("UPDATE ' . strtolower($tableName) . ' SET ".$setClause." WHERE ID = \'".$this->id."\' ", $this->fields);
						}
					}
				}
			}';
        $classDef = "class " . $className . " extends Sodapop_Database_Table_Abstract {\n" . $columnString . "\n" . $overriddenFunctions . "\n}";
        eval($classDef);
    }

    public function defineFormClass($schema, $formName, $className) {
        $this->defineTableClass($schema, $tableName, $className);
    }

    protected function getColumnDefinitionString($tableName, $formFlag = false) {
        $columns = array();
        $column_result = mysql_query("SHOW COLUMNS FROM " . mysql_real_escape_string($tableName));
        //echo mysql_error();
        while ($row = mysql_fetch_assoc($column_result)) {
            $columns[] = $row;
        }

        $columnString = 'protected $fieldDefinitions = array(';
        foreach ($columns as $column) {
            if ($columnString != 'protected $fieldDefinitions = array(') {
                $columnString .= ',';
            }
            $columnString .= "'" . Sodapop_Inflector::underscoresToCamelCaps(strtolower($column['Field']), true, false) . "' => array(";
            $columnString .= "'type_name' => '" . addslashes($column['Type']) . "','null' => '" . ($column['Null'] == 'YES' ? 'true' : 'false') . "','array_flag' => false";
            $columnString .= ")";
        }
        $columnString .= ');';
        return $columnString;
    }

}

