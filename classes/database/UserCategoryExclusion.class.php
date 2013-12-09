<?php

	namespace database;
	
	require_once( dirname( __FILE__ ).'/Object.class.php' );

	final class UserCategoryExclusion extends Object
	{
		protected static $schema = 'colocation';
		protected static $table = 'user_categories_exclusion';
		protected static $primaryKey = array( 'user_id', 'category_id' );

		public $user_id;
		public $category_id;

		public function __construct( $user_id, $category_id, $load = true )
		{
			$this->user_id = $user_id;
			$this->category_id = $category_id;
			parent::__construct( $load );
		}

		public function __destruct()
		{
			parent::__destruct();
		}
		
	}

?>
