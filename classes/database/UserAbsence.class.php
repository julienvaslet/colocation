<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class UserAbsence extends Object
{
	protected static $schema = "colocation";
	protected static $table = "user_absence";
	
	protected static $fields = array(
		"user_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 	    => true
		),
		"date_start" => array(
			"type" 			=> "date"
		),
		"date_end" => array(
			"type" 			=> "date"
		)
	);
	
	protected static $keys = array(
		"primary" => array( "user_id", "date_start", "date_end" ),
		"foreign" => array(
			array(
				"fields" => "user_id",
				"table" => "user",
				"references" => "user_id",
				"onDelete" => "cascade",
				"onUpdate" => "cascade"
			)
		)
	);

	public $user_id;
	public $date_start;
	public $date_end;
}

?>

