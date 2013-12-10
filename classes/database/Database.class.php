<?php

/**
 * \file		Database.class.php
 * \author	Julien Vaslet (julien.vaslet@gmail.com)
 * \version	1.0
 * \date		2012-12-10 
 */

namespace database;

/**
 * \brief		It provides a connection to a MySQL database
 * \details	This class extends the mysqli one to implement a singleton
 *			pattern and provide exception. 
 */
class Database extends \mysqli
{
	private static $instance = null;
	protected $db;
	
	/**
	 * \brief		Create a new database connection.
	 * \details	It creates a new database connection and register this instance.
	 *			If an instance is already registered, it will be destroyed.
	 * \param		$host	host address of the database
	 * \param		$port	port of the database
	 * \param		$user	user of the connection
	 * \param		$password	password of the user
	 * \param		$database	database to access
	 *
	 */
	public function __construct( $host, $port, $user, $password, $database )
	{
		@parent::__construct( $host, $user, $password, $database, intval( $port ) );
		
		if( $this->connect_errno != 0 )
			throw new \Exception( "Could not connect database (". $this->connect_errno. "): ". $this->connect_error );
		
		static::$instance = $this;
	}
	
	/**
	 * \brief Destroy the instance, close its connection and unregister it.
	 */
	public function __destruct()
	{
		$this->close();
		
		if( static::$instance == $this )
			static::$instance = null;
	}
	
	/**
	 * \brief 	Get the current registered database instance.
	 * \return	The current registered database instance or \c null if there is no instance.
	 */
	public static function getInstance()
	{
		return static::$instance;
	}
	
	/**
	 * \brief		Execute a SQL query.
	 * \details	In error case, an exception is thrown with the description of the error.
	 * \param		$query	the SQL query to execute
	 */
	public function query( $query )
	{
		if( !parent::query( $query ) )
			throw new \Exception( "SQL Error (". $this->errno. "): ". $this->error );
	}
}

?>
