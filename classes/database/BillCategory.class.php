<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class BillCategory extends Object
{
	protected static $schema = "colocation";
	protected static $table = "bill_category";
	
	protected static $fields = array(
		"bill_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true
		),
		"category_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true
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
		"primary" => array( "bill_id", "category_id" ),
		"foreign" => array(
			array(
				"fields" => "bill_id",
				"table" => "bill",
				"references" => "bill_id",
				"onDelete" => "cascade",
				"onUpdate" => "cascade"
			),
			array(
				"fields" => "category_id",
				"table" => "category",
				"references" => "category_id",
				"onDelete" => "cascade",
				"onUpdate" => "cascade"
			)
		)
	);

	public $bill_id;
	public $category_id;
	public $amount;
}

?>

