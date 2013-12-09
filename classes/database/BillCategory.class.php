<?php

	namespace database;
	
	require_once( dirname( __FILE__ ).'/Object.class.php' );

	final class BillCategory extends Object
	{
		protected static $schema = 'colocation';
		protected static $table = 'bills_categories';
		protected static $primaryKey = array( 'bill_id', 'category_id' );

		public $bill_id;
		public $category_id;

		public function __construct( $bill_id, $category_id, $load = true )
		{
			$this->bill_id = $bill_id;
			$this->category_id = $category_id;
			parent::__construct( $load );
		}

		public function __destruct()
		{
			parent::__destruct();
		}
		
	}

?>
