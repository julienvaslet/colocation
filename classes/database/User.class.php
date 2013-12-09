<?php

	namespace database;
	
	require_once( dirname( __FILE__ ).'/Object.class.php' );

	final class User extends Object
	{
		protected static $schema = 'colocation';
		protected static $table = 'users';
		protected static $primaryKey = array( 'user_id' );

		public $user_id;
		public $user_name;

		public function __construct( $user_id, $load = true )
		{
			$this->user_id = $user_id;
			parent::__construct( $load );
		}

		public function __destruct()
		{
			parent::__destruct();
		}
		
	}

?>
