<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class User extends Object
{
	protected static $schema = "colocation";
	protected static $table = "user";
	
	protected static $fields = array(
		"user_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"user_name" => array(
			"type"		=> "string",
			"maxlength"	=> 32
		)
	);
	
	protected static $keys = array(
		"primary" => array( "user_id" )
	);

	public $user_id;
	public $user_name;

	public function getByName( $username )
	{
		$user = null;
		$users = static::get( array( "user_name" => $username ), null, 1, 1 );

		if( count( $users ) > 0 )
			$user = $users[0];

		return $user;
	}
}

?>

