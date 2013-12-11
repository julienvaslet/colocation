<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class UserCategoryExclusion extends Object
{
	protected static $schema = "colocation";
	protected static $table = "user_category_exclusion";
	
	protected static $fields = array(
		"user_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"category_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true
		)
	);
	
	protected static $keys = array(
		"primary" => array( "user_id", "category_id" ),
		"foreign" => array(
			array(
				"fields" => "user_id",
				"table" => "user",
				"references" => "user_id",
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

	public $user_id;
	public $category_id;
}

?>

