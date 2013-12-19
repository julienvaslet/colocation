<?php

/**
 * \file		Object.class.php
 * \author	Julien Vaslet (julien.vaslet@gmail.com)
 * \version	1.0
 * \date		2012-12-10 
 */

namespace database;

require_once( dirname( __FILE__ ). "/Database.class.php" );

/**
 * \brief		Abstract class which provides database access methods to its children.
 * \details	By extending this class, its protected members shall be filled to complete the table definition.
 */
abstract class Object
{
	private static $instances = array();
	
	protected static $schema = null; /**< The schema name of the table */
	protected static $table = null; /**< The table name */
	
	/**
	 * \brief		Table fields definition
	 * \details	This is an associative array in which key is the field name and value its definition.\n
	 *			Field definition is an array like this:
	 *			\code{php}
	 *			array(
	 *				"type"		=> "string",
	 *				"maxlength"	=> 64
	 *			)
	 *			\endcode
	 *
	 *			The only mandatory option is the \e type one which could be one of following:
	 *			\c binary, \c enum, \c double, \c decimal, \c integer or \c string.\n
	 *			Each type have its own specific options.
	 *
	 *			\c binary type provides a MySQL \c BLOB.
	 *			\code{php}
	 *			array(
	 *				"type"	=> "binary"
	 *			)
	 *			\endcode
	 *
	 *			\c enum type provides a MySQL \c ENUM.
	 *			\code{php}
	 *			array(
	 *				"type"	=> "enum",
	 *				"values"	=> array( "one", "two", "three" ), // Values of the enum, mandatory.
	 *				"null"	=> true, // Could be null or not, default value is false.
	 *				"default"	=> "two" // The default value of the field
	 *			)
	 *			\endcode
	 *
	 *			\c double and \c decimal types provides respectively MySQL \c DOUBLE and \c DECIMAL types.
	 *			They have the same options.
	 *			\code{php}
	 *			array(
	 *				"type"			=> "decimal",
	 *				"integerPart"		=> 10, // Integer part size
	 *				"fractionalPart"	=> 3, // Fractional part size
	 *				"unsigned"		=> true, // Should be an unsigned type or not, default value is false.
	 *				"null"			=> true, // Could be null or not, default value is false.
	 *				"default"			=> 0.0 // The default value of the field
	 *			)
	 *			\endcode
	 *
	 *			\c integer type provides one of MySQL types: \c TINYINT, \c SMALLINT, \c INT, \c MEDIUMINT or \c BIGINT.
	 *			\code{php}
	 *			array(
	 *				"type"			=> "integer",
	 *				"bits"			=> 24, // The number of bits of the integer
	 *				"unsigned"		=> true, // Should be an unsigned type or not, default value is false.
	 *				"null"			=> true, // Could be null or not, default value is false.
	 *				"autoIncrement"	=> true, // Should be auto incremented at each insert, default value is false.
	 *				"default"			=> 0 // The default value of the field
	 *			)
	 *			\endcode
	 *
	 *			\c string type provides MySQL \c CHAR if \c length option is used.
	 *			\code{php}
	 *			array(
	 *				"type"			=> "string",
	 *				"length"			=> 2, // The fixed length of the string
	 *				"null"			=> false, // Could be null or not, default value is false.
	 *				"default"			=> 'FR' // The default value of the field
	 *			)
	 *			\endcode
	 *
	 *			\c string type provides MySQL \c VARCHAR if \c maxlength option is used.
	 *			\code{php}
	 *			array(
	 *				"type"			=> "string",
	 *				"maxlength"		=> 32, // The maximum length of the string
	 *				"null"			=> true, // Could be null or not, default value is false.
	 *				"default"			=> '(empty)' // The default value of the field
	 *			)
	 *			\endcode
	 *
	 *			\c string type provides MySQL \c TEXT if nor \c maxlength option nor \c length are used.
	 *			\code{php}
	 *			array(
	 *				"type"			=> "string"
	 *			)
	 *			\endcode
	 */
	protected static $fields = array();
	
	/**
	 * \brief		Table keys definition
	 * \details	This is an associative array which defines the table keys.\n
	 *			Array keys are types of table keys which are \c primary or \c foreign.
	 *
	 *			Primary key (\c primary) is associated to an array of field names.\n
	 *			Foreign keys (\c foreign) is associated to an array of foreign keys definition.\n
	 *			
	 *			\code{php}
	 *			array(
	 *				"primary"	=> array( "user_id", "role_id" ),
	 *				"foreign"	=> array(
	 *					array(
	 *						"fields"		=> "user_id", // The fields of current table, it could be an array
	 *						"schema"		=> "backoffice", // The schema of the referenced table, it is not mandatory
	 *						"table" 		=> "user", // The name of the referenced table
	 *						"references" 	=> "user_id", // Referenced fields, it could be an array
	 *						"onDelete" 	=> "cascade", // Event on deletion [cascade|set null|restrict], it is not mandatory
	 *						"onUpdate" 	=> "cascade" // Event on update [cascade|set null|restrict], it is not mandatory
	 *					),
	 *					array(
	 *						"fields"		=> "role_id",
	 *						"schema"		=> "backoffice",
	 *						"table" 		=> "role",
	 *						"references" 	=> "role_id",
	 *						"onDelete" 	=> "cascade",
	 *						"onUpdate" 	=> "cascade"
	 *					)
	 *				)
	 *			)
	 *			\endcode
	 */
	protected static $keys = array( "primary" => array() );
	
	
	protected static $primaryKey = array();
	
	/**
	 * \brief	Load an object from the local cache or the database
	 * \param	$primaryKey	the unique identifier of the object
	 * \param	$load		load or not from the database if it is not found is the local cache, default value is true
	 */
	final public static function getInstance( $primaryKey, $load = true )
	{
		$class = get_called_class();

		$instanceId = array();

		// Each key is casted to string in order to always match keys.
		// Database results sometimes return a string instead of an integer.
		foreach( static::$primaryKey as $key )
			$instanceId[] = array_key_exists( $key, $primaryKey ) ? (string) $primaryKey[ $key ] : null;

		$instanceId = serialize( $instanceId );

		if( array_key_exists( $class, Object::$instances )
			&& array_key_exists( $instanceId, Object::$instances[ $class ] ) )
		{
			Object::$instances[ $class ][ $instanceId ][ 'count' ]++;
		}
		else
		{
			$reflection = new \ReflectionClass( $class );

			$parameters = array();

			foreach( static::$primaryKey as $key )
			{
				if( array_key_exists( $key, $primaryKey ) )
					$parameters[] = $primaryKey[ $key ];
			}

			$parameters[] = $load;

			Object::$instances[ $class ][ $instanceId ] = array( 'instance' => $reflection->newInstanceArgs( $parameters ), 'count' => 1 );
		}

		return Object::$instances[ $class ][ $instanceId ][ 'instance' ];
	}
	
	/**
	 * \brief	Delete an object from the local cache
	 * \param	$primaryKey	the unique identifier of the object
	 */
	final public static function deleteInstance( $primaryKey )
	{
		$class = get_called_class();

		$instanceId = array();

		// Each key is casted to string in order to always match keys.
		// Database results sometimes return a string instead of an integer.
		foreach( static::$primaryKey as $key )
			$instanceId[] = array_key_exists( $key, $primaryKey ) ? (string) $primaryKey[ $key ] : null;

		$instanceId = serialize( $instanceId );

		if( array_key_exists( $class, Object::$instances )
			&& array_key_exists( $instanceId, Object::$instances[ $class ] ) )
		{
			Object::$instances[ $class ][ $instanceId ][ 'count' ]--;

			if( Object::$instances[ $class ][ $instanceId ][ 'count' ] == 0 )
				unset( Object::$instances[ $class ][ $instanceId ] );
		}
	}

	public function __construct()
	{
		$load = true;
		
		// Check the arguments
		if( func_num_args() < count( static::$keys["primary"] ) || func_num_args() > count( static::$keys["primary"] ) + 1 )
			die( "Invalid __contruct() call. `". static::$schema. "`.`". static::$table. "` table has a primary key with ". count( static::$keys["primary"] ). " field(s)." );

		// Override load flag
		if( func_num_args() == count( static::$keys["primary"] ) + 1 )
			$load = ( func_get_arg( func_num_args() - 1 ) === false ) ? false : true;
			
		// Fill primary key attributes
		$keyFields = (array) static::$keys["primary"];
		for( $i = 0 ; $i < count( $keyFields ) ; $i++ )
		{
			if( property_exists( $this, $keyFields[$i] ) )
				$this->{$keyFields[$i]} = func_get_arg( $i );
			else
				die( "Invalid primary key definition. Field `". $keyFields[$i]. "` is not defined as an attribute in the ". get_called_class(). " class." );
		}
		
		$db = Database::getInstance();

		if( $load )
		{
			$selectors = array();
			
			foreach( (array) static::$keys["primary"] as $key )
				$selectors[ $key ] = $this->$key;

			$query = "SELECT * FROM `".static::$schema."`.`".static::$table."` WHERE ".static::getSqlSelectors( $selectors )." LIMIT 1;";

			$result = $db->query( $query );

			if( $result && $result->num_rows == 1 )
			{
				$row = $result->fetch_assoc();

				foreach( $row as $attribute => $value )
				{
					if( property_exists( $this, $attribute ) )
						$this->$attribute = $row[ $attribute ];
				}
			}
		}
	}

	public function __destruct()
	{
		$primaryKey = array();

		foreach( static::$primaryKey as $key )
			$primaryKey[ $key ] = $this->$key;

		static::deleteInstance( $primaryKey );
	}

	final public function save()
	{
		$db = Database::getInstance();

		$data = array();

		foreach( $this as $attribute => $value )
		{
			if( !in_array( $attribute, static::$primaryKey ) )
				$data[ $attribute ] = $value;
		}

		$selectors = array();

		foreach( static::$primaryKey as $key )
			$selectors[ $key ] = $this->$key;

		$query = "UPDATE `".static::$schema."`.`".static::$table."` SET ".static::dataToQuery( $data )." WHERE ".static::getSqlSelectors( $selectors )." LIMIT 1;";
		
		if( $db->query( $query ) )
			return $db->affected_rows;
		else
			return false;
	}

	final public function erase()
	{
		$db = Database::getInstance();

		$selectors = array();

		foreach( static::$primaryKey as $key )
			$selectors[ $key ] = $this->$key;

		$query = "DELETE FROM `".static::$schema."`.`".static::$table."` WHERE ".static::getSqlSelectors( $selectors )." LIMIT 1;";

		if( $db->query( $query ) )
			return $db->affected_rows;
		else
			return false;
	}

	final public static function get( array $selectors = array(), $order = null, $page = 1, $pageSize = 0 )
	{
		$db = Database::getInstance();

		$query = "SELECT * FROM `".static::$schema."`.`".static::$table."`";

		if( count( $selectors ) > 0 )
			$query .= " WHERE ".static::getSqlSelectors( $selectors );

		if( $order !== null )
			$query .= " ORDER BY ".( is_array( $order ) && count( $order ) > 0 ? join( $order, ', ' ) : $order );

		if( $pageSize > 0 )
			$query .= " LIMIT ".( ($page - 1) * $pageSize ).",".floatval( $pageSize );

		$query .= ";";
		
		$result = $db->query( $query );
		
		var_dump( $result );

		if( $result )
		{
			$array = array();

			while( ($row = $result->fetch_assoc()) !== null )
				$array[] = static::load( $row );

			return $array;
		}
		else
			return false;
	}
	
	final public static function count( array $selectors = array() )
	{
		$db = Database::getInstance();

		$query = "SELECT COUNT(*) AS `count` FROM `".static::$schema."`.`".static::$table."`";

		if( count( $selectors ) > 0 )
			$query .= " WHERE ".static::getSqlSelectors( $selectors );

		$query .= ";";
		
		$result = $db->query( $query );

		if( $result )
		{
			$row = $result->fetch_assoc();
			return $row["count"];
		}
		else
			return false;
	}

	final public static function create( array $data )
	{
		$db = Database::getInstance();

		$query = "INSERT INTO `".static::$schema."`.`".static::$table."` SET ".static::dataToQuery( $data ).";";

		if( $db->query( $query ) )
		{
			return ( $db->insert_id ) ? $db->insert_id : true;
		}
		else
		{
			//var_dump( $db->error, $query );
			return false;
		}
	}

	final public static function update( array $data, array $selectors = array() )
	{
		$db = Database::getInstance();

		$query = "UPDATE `".static::$schema."`.`".static::$table."` SET ".static::dataToQuery( $data );

		if( count( $selectors ) > 0 )
			$query .= " WHERE ".static::getSqlSelectors( $selectors );

		$query .= ";";

		if( $db->query( $query ) )
		{
			return $db->affected_rows;
		}
		else
			return false;
	}

	final public static function remove( array $selectors = array() )
	{
		$db = Database::getInstance();

		$query = "DELETE FROM `".static::$schema."`.`".static::$table."` ";

		if( count( $selectors ) > 0 )
			$query .= " WHERE ".static::getSqlSelectors( $selectors );

		$query .= ";";

		if( $db->query( $query ) )
		{
			return $db->affected_rows;
		}
		else
			return false;
	}

	final public static function load( array $data )
	{
		$primaryKey = array();

		foreach( static::$primaryKey as $key )
			$primaryKey[ $key ] = $data[ $key ];

		$object = static::getInstance( $primaryKey, false );

		foreach( $data as $attribute => $value )
		{
			if( property_exists( $object, $attribute ) )
				$object->$attribute = $value;
		}

		return $object;
	}
	
	public static function escapeData( $data )
	{
		$escapedData = null;

		if( $data !== null )
		{
			if( is_string( $data ) )
			{
				$data = preg_replace( "/'/", "\\'", $data );
				$encoding = mb_detect_encoding( $data, mb_detect_order(), true );

				if( $encoding != 'UTF-8' )
					$escapedData =  "_utf8'".mb_convert_encoding( $data, 'UTF-8', $encoding )."'";
				else
					$escapedData = "_utf8'".$data."'";
			}
			else if( is_array( $data ) )
			{
				$datas = array();
				
				foreach( $data as $d )
					$datas[] = Object::escapeData( $d );
			
				$escapedData = '('. implode( ",", $datas ). ')';
			}
			else
				$escapedData = $data;
		}
		else
			$escapedData = 'NULL';

		return $escapedData;
	}

	protected static function dataToQuery( array $data )
	{
		$attributes = array();

		foreach( $data as $attribute => $value )
			$attributes[] = "`".static::$table."`.`".$attribute."` = ".Object::escapeData( $value );

		return join( $attributes, ", " );
	}
	
	/**
	 *
	 */
	final protected static function getSqlSelectors( array $selectors )
	{
		$selectorsArray = array();

		foreach( $selectors as $selector => $value )
		{
			if( is_null( $value ) )						
				$selectorsArray[] = "`".static::$table."`.`".$selector."` IS ".Object::escapeData( $value );
				
			else if( is_array( $value ) )
				$selectorsArray[] = "`".static::$table."`.`".$selector."` IN ( ". implode( ",", array_map( function( $d ){ return Object::escapeData( $d ); }, $value ) ). ")";
				
			else
				$selectorsArray[] = "`".static::$table."`.`".$selector."` = ".Object::escapeData( $value );
		}

		return join( $selectorsArray, " AND " );
	}
	
	/**
	 * \brief		Check if the class is well named
	 * \return	true if it is a success, false in other cases
	 */
	final public static function checkClassName()
	{
		return get_called_class() == static::getClassName( static::$table );
	}
	
	/**
	 * \brief		Convert a table name to its class one.
	 * \param		$table	the name of the table
	 * \return	The class name of the table
	 */
	final protected static function getClassName( $table )
	{
		return "database\\". strtoupper( $table[0] ). preg_replace_callback( "#_(.)#", function( $data ){ return strtoupper( $data[1] ); }, substr( $table, 1 ) ); 
	}
	
	/**
	 * \brief		Generate the SQL type for the specified field.
	 * \details	If the field is not well defined, this function stops the execution of the full script.
	 * \param		$field	the name of the field
	 * \param		$options	the options defining the field
	 * \param		$onlyType	if set to true, do not add table specific options such as "AUTO_INCREMENT", "DEFAULT", etc.
	 * \return	The SQL type
	 */
	final protected static function getSqlFieldDefinition( $field, array $options, $onlyType = false )
	{
		if( array_key_exists( "type", $options ) )
		{
			$query = "`". $field. "` ";
			
			switch( $options["type"] )
			{
				case "binary":
				{
					$query .= "BLOB";
					break;
				}
				
				case "enum":
				{
					if( array_key_exists( "values", $options ) && is_array( $options["values"] ) && count( $options["values"] ) > 0 )
						$query .= "ENUM('". implode( "','", array_map( function( $value ){ return preg_replace( "#'#", "\\'", $value ); }, $options["values"] ) ). "')";
					else
						die( "Field `". $field. "`, of `". static::$schema. "`.`". static::$table. "` table, is an enumeration without any declared value." );
					
					if( array_key_exists( "null", $options ) && $options["null"] === true )
						$query .= " NULL";
					else
						$query .= " NOT NULL";
					
					if( $onlyType === false && array_key_exists( "default", $options ) )
						$query .= " DEFAULT '". preg_replace( "#'#", "\\'", $options["default"] ). "'";
					
					break;
				}
				
				case "decimal":
				case "double":
				{
					$query .= strtoupper( $options["type"] );
					
					if( array_key_exists( "integerPart", $options ) && array_key_exists( "fractionalPart", $options ) && intval( $options["integerPart"] ) > 0 && intval( $options["fractionalPart"] ) >= 0 )
						$query .= "(". intval( $options["integerPart"] ). ",". intval( $options["fractionalPart"] ). ")";
					
					if( array_key_exists( "unsigned", $options ) && $options["unsigned"] === true )
						$query .= " UNSIGNED";
					
					if( array_key_exists( "null", $options ) && $options["null"] === true )
						$query .= " NULL";
					else
						$query .= " NOT NULL";
					
					if( $onlyType === false && array_key_exists( "default", $options ) )
						$query .= " DEFAULT ". floatval( $options["default"] );
					
					break;
				}
				
				case "time":
				case "date":
				case "datetime":
				{
					$query .= strtoupper( $options["type"] );
					
					if( array_key_exists( "null", $options ) && $options["null"] === true )
						$query .= " NULL";
					else
						$query .= " NOT NULL";
					
					if( $onlyType === false && array_key_exists( "default", $options ) )
						$query .= " DEFAULT '". preg_replace( "#'#", "\\'", $options["default"] ). "'";
					break;
				}
								
				case "integer":
				{
					if( array_key_exists( "bits", $options ) && intval( $options["bits"] ) % 8 == 0 )
					{
						switch( intval( $options["bits"] ) )
						{
							case 8:
							{
								$query .= "TINYINT";
								break;
							}
							
							case 16:
							{
								$query .= "SMALLINT";
								break;
							}
							
							case 24:
							default:
							{
								$query .= "MEDIUMINT";
								break;
							}
							
							case 32:
							{
								$query .= "INT";
								break;
							}
							
							case 64:
							{
								$query .= "BIGINT";
								break;
							}
						}
					}
					else
						$query .= "INT";
					
					if( array_key_exists( "unsigned", $options ) && $options["unsigned"] === true )
						$query .= " UNSIGNED";
						
					if( array_key_exists( "null", $options ) && $options["null"] === true )
						$query .= " NULL";
					else
						$query .= " NOT NULL";
					
					if( $onlyType === false && array_key_exists( "default", $options ) )
						$query .= " DEFAULT ". intval( $options["default"] );
			
					if( $onlyType === false && array_key_exists( "autoIncrement", $options ) && $options["autoIncrement"] === true )
						$query .= " AUTO_INCREMENT";
						
					break;
				}
				
				case "string":
				default:
				{
					if( array_key_exists( "length", $options ) && intval( $options["length"] ) > 0 )
					{
						$query .= "CHAR(". intval( $options["length"] ). ")";
						
						if( array_key_exists( "null", $options ) && $options["null"] === true )
							$query .= " NULL";
						else
							$query .= " NOT NULL";
					
						if( $onlyType === false && array_key_exists( "default", $options ) )
							$query .= " DEFAULT '". preg_replace( "#'#", "\\'", $options["default"] ). "'";
					}
					else if( array_key_exists( "maxlength", $options ) && intval( $options["maxlength"] ) > 0 )
					{
						$query .= "VARCHAR(". intval( $options["maxlength"] ). ")";
						
						if( array_key_exists( "null", $options ) && $options["null"] === true )
							$query .= " NULL";
						else
							$query .= " NOT NULL";
					
						if( $onlyType === false && array_key_exists( "default", $options ) )
							$query .= " DEFAULT '". preg_replace( "#'#", "\\'", $options["default"] ). "'";
					}
					else
					{
						$query .= "TEXT";
					}
					
					break;
				}
			}
				
			return $query;
		}
		else
			die( "Field `". $field. "`, of `". static::$schema. "`.`". static::$table. "` table, has no declared type." );
	}
	
	/**
	 * \brief		Generate the SQL query to create the table of the static called class.
	 * \details	If the table is not well defined, this function stops the execution of the full script.
	 * \return	The SQL query
	 */
	final public static function getSqlCreateTable()
	{
		$createDefinitions = array();
		
		foreach( static::$fields as $field => $options )
			$createDefinitions[] = static::getSqlFieldDefinition( $field, $options );
		
		if( count( (array) static::$keys["primary"] ) > 0 )
			$createDefinitions[] = "CONSTRAINT `pk_". static::$table. "` PRIMARY KEY ( `". implode( "`, `", (array) static::$keys["primary"] ). "` )";
			
		if( array_key_exists( "foreign", static::$keys ) )
		{
			// This array is used to handle multiple foreign keys that reference the same table
			// If tables or schemas have too long name or if there is more than ten foreign keys
			// that reference the same table, the generated key name may be invalid.
			$fkCount = array();
			
			foreach( static::$keys["foreign"] as $foreign )
			{
				if( !array_key_exists( "table", $foreign ) && !is_null( $foreign["table"] ) )
					die( "Invalid foreign key for ". get_called_class(). " class. No reference table has been specified." );
				
				// Generating foreign key and its index name
				$fkName = "fk_". static::$table. "_". ( array_key_exists( "schema", $foreign ) && !is_null( $foreign["schema"] ) && $foreign["schema"] != static::$schema ? $foreign["schema"]. "_" : "" ). $foreign["table"];
				
				if( strlen( $fkName ) > 63 )
					$fkName = substr( $fkName, 0, 63 );
					
				// Handle multiple foreign key on the same table
				if( array_key_exists( $fkName, $fkCount ) )
					$fkName .= $fkCount[$fkName]++;
				else
					$fkCount[$fkName] = 1;
				
				// Check for origin fields definition
				if( !array_key_exists( "fields", $foreign ) && !is_null( $foreign["fields"] ) )
					die( "Invalid foreign key `". $fkName. "` for ". get_called_class(). " class. No origin fields have been specified." );
					
				// Check for referenced fields definition
				if( !array_key_exists( "references", $foreign ) && !is_null( $foreign["references"] ) )
					die( "Invalid foreign key `". $fkName. "` for ". get_called_class(). " class. No reference fields have been specified." );
				
				// Check for fields count egality
				if( count( (array) $foreign["fields"] ) != count( (array) $foreign["references"] ) )
					die( "Invalid foreign key `". $fkName. "` for ". get_called_class(). " class. Origin and referenced fields count does not match." );
				
				// Check for fields types
				$foreignFields = (array) $foreign["fields"];
				$foreignReferences = (array) $foreign["references"];
				for( $i = 0 ; $i < count( $foreignFields ) ; $i++ )
				{
					$referencedClass = static::getClassName( $foreign["table"] );

					if( static::getSqlFieldDefinition( "", static::$fields[$foreignFields[$i]], true ) != static::getSqlFieldDefinition( "", $referencedClass::$fields[$foreignReferences[$i]], true ) )
						die( "Invalid foreign key `". $fkName. "` for ". get_called_class(). " class. Fields `". static::$table. "`.`". $foreignFields[$i]. "` and `". $foreign["table"]. "`.`". $foreignReferences[$i]. "` do not have the same type." );
				}
				
				// Constraint
				$fk = "CONSTRAINT `". $fkName. "` FOREIGN KEY ( `". implode( "`, `", (array) $foreign["fields"] ). "` ) REFERENCES `". ( array_key_exists( "schema", $foreign ) && !is_null( $foreign["schema"] ) ? $foreign["schema"] : static::$schema ) . "`.`". $foreign["table"]. "` ( `". implode( "`, `", (array) $foreign["references"] ). "` )";
				
				// Foreign key update & delete events
				if( array_key_exists( "onUpdate", $foreign ) && in_array( $foreign["onUpdate"], array( "cascade", "set null", "restrict" ) ) )
					$fk .= " ON UPDATE ". strtoupper( $foreign["onUpdate"] );
				else
					$fk .= " ON UPDATE NO ACTION";
				
				if( array_key_exists( "onDelete", $foreign ) && in_array( $foreign["onDelete"], array( "cascade", "set null", "restrict" ) ) )
					$fk .= " ON DELETE ". strtoupper( $foreign["onDelete"] );
				else
					$fk .= " ON DELETE NO ACTION";
					
				$createDefinitions[] = $fk;
			}
		}
		
		return "CREATE TABLE `". static::$schema. "`.`". static::$table. "` ( ". implode( ", ", $createDefinitions ). " ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";
	}
	
	/**
	 * \brief		Create the table of the static called class.
	 * \details	If the table is not well defined, this function stops the execution of the full script.
	 * \param		$dropIfExists	if this flag is set to true, the table is dropped if it already exists
	 */
	final public static function createTable( $dropIfExists = false )
	{
		if( $dropIfExists === true )
			static::dropTable( true );
		
		Database::getInstance()->query( static::getSqlCreateTable( $dropIfExists ) );
	}
	
	/**
	 * \brief		Generate the SQL query to drop the table of the static called class.
	 * \param		$ifExistsFlag	use "IF EXISTS" flag or not
	 * \return	The SQL query
	 */
	final public static function getSqlDropTable( $ifExistsFlag = true )
	{
		return "DROP TABLE ". ($ifExistsFlag === true ? "IF EXISTS " : ""). "`". static::$schema. "`.`". static::$table. "`;";
	}
	
	/**
	 * \brief	Drop the table of the static called class.
	 * \param	$ifExistsFlag	if this flag is set to true, no exception is raised if the table does not exist
	 */
	final public static function dropTable( $ifExistsFlag = true )
	{
		Database::getInstance()->query( static::getSqlDropTable( $ifExistsFlag ) );
	}
}

?>

