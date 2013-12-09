<?php

	/*
	 * Todo: create a Join class in order to provide joins on static get function selectors.
	 */

	namespace database;

	abstract class Object
	{
		private static $instances = array();
		protected static $schema = null;
		protected static $table = null;
		protected static $primaryKey = array();

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

		public function __construct( $load = true )
		{
			global $db;

			if( $load )
			{
				$selectors = array();
				
				foreach( static::$primaryKey as $key )
					$selectors[ $key ] = $this->$key;

				$query = "SELECT * FROM `".static::$schema."`.`".static::$table."` WHERE ".static::selectorsToQuery( $selectors )." LIMIT 1;";

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
			global $db;

			$data = array();

			foreach( $this as $attribute => $value )
			{
				if( !in_array( $attribute, static::$primaryKey ) )
					$data[ $attribute ] = $value;
			}

			$selectors = array();

			foreach( static::$primaryKey as $key )
				$selectors[ $key ] = $this->$key;

			$query = "UPDATE `".static::$schema."`.`".static::$table."` SET ".static::dataToQuery( $data )." WHERE ".static::selectorsToQuery( $selectors )." LIMIT 1;";
			
			if( $db->query( $query ) )
				return $db->affected_rows;
			else
				return false;
		}

		final public function erase()
		{
			global $db;

			$selectors = array();

			foreach( static::$primaryKey as $key )
				$selectors[ $key ] = $this->$key;

			$query = "DELETE FROM `".static::$schema."`.`".static::$table."` WHERE ".static::selectorsToQuery( $selectors )." LIMIT 1;";

			if( $db->query( $query ) )
				return $db->affected_rows;
			else
				return false;
		}

		final public static function get( array $selectors = array(), $order = null, $page = 1, $pageSize = 0 )
		{
			global $db;

			$query = "SELECT * FROM `".static::$schema."`.`".static::$table."`";

			if( count( $selectors ) > 0 )
				$query .= " WHERE ".static::selectorsToQuery( $selectors );

			if( $order !== null )
				$query .= " ORDER BY ".( is_array( $order ) && count( $order ) > 0 ? join( $order, ', ' ) : $order );

			if( $pageSize > 0 )
				$query .= " LIMIT ".( ($page - 1) * $pageSize ).",".floatval( $pageSize );

			$query .= ";";
			
			$result = $db->query( $query );

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
			global $db;

			$query = "SELECT COUNT(*) AS `count` FROM `".static::$schema."`.`".static::$table."`";

			if( count( $selectors ) > 0 )
				$query .= " WHERE ".static::selectorsToQuery( $selectors );

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
			global $db;

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
			global $db;

			$query = "UPDATE `".static::$schema."`.`".static::$table."` SET ".static::dataToQuery( $data );

			if( count( $selectors ) > 0 )
				$query .= " WHERE ".static::selectorsToQuery( $selectors );

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
			global $db;

			$query = "DELETE FROM `".static::$schema."`.`".static::$table."` ";

			if( count( $selectors ) > 0 )
				$query .= " WHERE ".static::selectorsToQuery( $selectors );

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
				
					$escapedData = '('.join( $datas, ',' ).')';
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

		protected static function selectorsToQuery( array $selectors )
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
	}

?>
