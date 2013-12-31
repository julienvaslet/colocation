<?php

namespace database;

require_once( dirname( __FILE__ )."/Object.class.php" );

final class Category extends Object
{
	protected static $schema = "colocation";
	protected static $table = "category";
	
	protected static $fields = array(
		"category_id" => array(
			"type" 			=> "integer",
			"bits"			=> 24,
			"unsigned" 		=> true,
			"autoIncrement"	=> true
		),
		"category_name" => array(
			"type"		=> "string",
			"maxlength"	=> 32
		)
	);
	
	protected static $keys = array(
		"primary" => array( "category_id" )
	);

	public $category_id;
	public $category_name;

	public function getByName( $category_name )
	{
		$category = null;
		$categories = static::get( array( "category_name" => $category_name ), null, 1, 1 );

		if( count( $categories ) > 0 )
			$category = $categories[0];

		return $category;
	}
}

?>

