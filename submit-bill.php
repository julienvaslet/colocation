<?php

require_once( "common.php" );
require_once( RootPath. "/classes/database/User.class.php" );
require_once( RootPath. "/classes/database/Bill.class.php" );

use database\User;
use database\Bill;

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

	Bill::create( array( 
		"user_id" => $user->user_id,
		"purchase_date" => $purchase_date,
		"shop_name" => strtolower( $_POST["shop"] ),
		"amount" => $amount
	) );
}

header( 'Location: /', 301 );

?>
