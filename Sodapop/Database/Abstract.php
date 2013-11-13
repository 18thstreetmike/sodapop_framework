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
 * Sodapop_Database_Abstract is the base class for Sodapop database drivers.
 * 
 * If a driver does not already exist for your database, you can implement
 * a subclass of Sodapop_Database_Abstract to make one.
 */
abstract class Sodapop_Database_Abstract {
    
    /**
     * This function connects to a database.
     *
     * @param string $username
     * @param string $password
     * @param string $schema
     * @param string $environment
     */
    public static function connect($hostname, $port, $username, $password, $database, $config = array(), $connection_identifier = 'default') {
	return;
    }
    
    /**
     * This function returns the schema name for this connection.
     *
     */
    public abstract function getSchemaName();
    
    /**
     * This function returns the connection identifier for this connection.
     * 
     * This value is user-defined; the standard connection is called 'default'.
     */
    public abstract function getConnectionIdentifier();
    
    /**
     * This function returns a resultset matching the query specified.
     *
     * @param string $query
     */
    public abstract function runQuery($query);

    /**
     * This function returns a resultset matching the query specified.
     *
     * @param string $query
     * @param array $params
     */
    public abstract function runParameterizedQuery($query, $params);

    /**
     * This function returns true on success and a Sodapop_Database_Exception on failure.
     *
     * @param string $statement
     */
    public abstract function runUpdate($statement);

    /**
     * This function returns true on success and a Sodapop_Database_Exception on failure.
     * 
     * @param string $statement
     * @param array $params
     */
    public abstract function runParameterizedUpdate($statement, $params);

    /**
     * Closes the database connection.
     */
    public abstract function destroy();

    /**
     * This is used by the autoloader to define a class for the given table/class name.
     * After this method runs, PHP should have a class in memory with the given classname
     * that is a subclass of Sodapop_Database_Table_Abstract.
     * 
     * $table_definition is an array with the following structure:
     * 
     * array(
     *  'metadata'=>array('primary_key'=> array('column1',...)), 
     *  'columns' => 
     *	array(
     *	  array(
     *	    'column_name' => ...,
     *	    'column_type'=>...,
     *      'nullable'=>...,
     *      'scale'=>...,
     *      'precision'=>...
     *      'length'=>...
     *    ),...
     *  )
     * )
     */
    public abstract function defineTableClass($table_name, $class_name, $table_definition);

    /**
     * Returns an array of the table definitions for the tables available in this connection.
     * 
     * The return value is an array with the structure:
     * 
     * array('tablename1' => array(
     *  'metadata'=>array('primary_key'=> array('column1',...)), 
     *  'columns' => 
     *	array(
     *	  array(
     *	    'column_name' => ...,
     *	    'column_type'=>...,
     *      'nullable'=>...,
     *      'scale'=>...,
     *      'precision'=>...
     *      'length'=>...
     *    ),...
     *  )
     * ), 'tablename2' => ...);
     * 
     */
    public abstract function getTableDefinitions();
}
