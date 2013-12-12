<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class Bill extends Object
{
	protected static $schema = "colocation";
	protected static $table = "bill";
	
	protected static $fields = array(
		"bill_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"user_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true
		),
		"purchase_date" => array(
			"type"		=> "date"
		),
		"shop_name" => array(
			"type"		=> "string",
			"maxlength"	=> 64
		),
		"amount" => array(
			"type"				=> "decimal",
			"integerPart"		=> 5,
			"fractionalPart"	=> 2,
			"unsigned"			=> true,
			"null"				=> false,
			"default"			=> 0.0
		)
	);
	
	protected static $keys = array(
		"primary" => array( "bill_id" ),
		"foreign" => array(
			array(
				"fields" => "user_id",
				"table" => "user",
				"references" => "user_id",
				"onDelete" => "cascade",
				"onUpdate" => "cascade"
			),
		)
	);

	public $bill_id;
	public $user_id;
	public $purchase_date;
	public $shop_name;
	public $amount;
}

?>

