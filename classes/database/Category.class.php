<?php

	namespace database;
	
	require_once( dirname( __FILE__ ).'/Object.class.php' );

	final class Category extends Object
	{
		protected static $schema = 'colocation';
		protected static $table = 'categories';
		protected static $primaryKey = array( 'category_id' );

		public $category_id;
		public $category_name;

		public function __construct( $category_id, $load = true )
		{
			$this->category_id = $category_id;
			parent::__construct( $load );
		}

		public function __destruct()
		{
			parent::__destruct();
		}
		
	}

?>
