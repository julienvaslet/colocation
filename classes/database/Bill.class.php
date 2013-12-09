<?php

	namespace database;
	
	require_once( dirname( __FILE__ ).'/Object.class.php' );

	final class Bill extends Object
	{
		protected static $schema = 'colocation';
		protected static $table = 'bills';
		protected static $primaryKey = array( 'bill_id' );

		public $bill_id;
		public $user_id;
		public $purchase_date;
		public $shop_name;
		public $amount;

		public function __construct( $bill_id, $load = true )
		{
			$this->bill_id = $bill_id;
			parent::__construct( $load );
		}

		public function __destruct()
		{
			parent::__destruct();
		}
		
	}

?>
