<?php

require_once( "common.php" );
require_once( RootPath. "/classes/database/User.class.php" );
require_once( RootPath. "/classes/database/Category.class.php" );
require_once( RootPath. "/classes/database/UserCategoryExclusion.class.php" );

use database\User;
use database\Category;
use database\UserCategoryExclusion;

if( isset( $_POST["user"] ) )
{
	$categories = Category::get();
	$userId = intval( $_POST["user"] );
	UserCategoryExclusion::remove( array( "user_id" => $userId ) );

	$exclusions = array();

	foreach( $categories as $category )
		$exclusions[] = $category->category_id;

	if( !empty( $_POST["exclusion"] ) && is_array( $_POST["exclusion"] ) )
	{
		foreach( $_POST["exclusion"] as $category => $checked )
		{
			$index = array_search( $category, $exclusions );

			if( $index !== false )
				array_splice( $exclusions, $index, 1 );
		}
	}

	foreach( $exclusions as $exclusion )
	{
		UserCategoryExclusion::create( array(
			"user_id" => $userId,
			"category_id" => $exclusion
		) );
	}
}

header( "Location: ". $_SERVER["HTTP_REFERER"], 301 );

?>
