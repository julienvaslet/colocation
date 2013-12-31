<?php

require_once( "common.php" );
require_once( RootPath. "/classes/database/User.class.php" );
require_once( RootPath. "/classes/database/Bill.class.php" );
require_once( RootPath. "/classes/database/Category.class.php" );
require_once( RootPath. "/classes/database/BillCategory.class.php" );

use database\User;
use database\Bill;
use database\Category;
use database\BillCategory;

if( isset( $_POST["username"] ) && isset( $_POST["amount"] ) && !empty( $_POST["username"] ) && !empty( $_POST["amount"] ) && preg_match( "/^[0-9]+([,\.][0-9]+)?$/", $_POST["amount"] ) )
{
	$username = strtolower( $_POST["username"] );
	$amount = str_replace( ",", ".", $_POST["amount"] );

	$user = User::getByName( $username );

	if( $user == null )
	{
		$user_id = User::create( array( "user_name" => $username ) );
		$user = new User( $user_id );
	}

	$parsedDate = strptime( $_POST["date"], $language["date_format"] );

	if( $parsedDate != false )
		$purchase_date = ( $parsedDate["tm_year"] + 1900 )."-".( $parsedDate["tm_mon"] + 1)."-".$parsedDate["tm_mday"];
	else
	{
		$purchase_date = null;
		//TODO: Set the first of the current month or one pointed by the referer.
	}

	$bill_id = Bill::create( array( 
		"user_id" => $user->user_id,
		"purchase_date" => $purchase_date,
		"shop_name" => strtolower( $_POST["shop"] ),
		"amount" => $amount
	) );

	if( isset( $_POST["category"] ) && is_array( $_POST["category"] ) )
	{
		foreach( $_POST["category"] as $category_name => $category_amount )
		{
			$category_name = trim( strtolower( $category_name ) );
			$category_amount = str_replace( ",", ".", $category_amount );

			$category = Category::getByName( $category_name );

			if( is_null( $category ) )
				$category_id = Category::create( array( "category_name" => $category_name ) );
			else
				$category_id = $category->category_id;

			BillCategory::create( array( 
				"bill_id" => $bill_id,
				"category_id" => $category_id,
				"amount" => $category_amount
			) );
		}
	}
}

header( 'Location: /', 301 );

?>
