<?php

require_once( "common.php" );
require_once( RootPath. "/classes/database/User.class.php" );

use database\User;

if( isset( $_POST["username"] ) && !empty( $_POST["username"] ) )
{
	$username = strtolower( $_POST["username"] );
	if( User::count( array( "user_name" => $username ) ) == 0 )
	{
		$userId = User::create( array( "user_name" => $username ) );
	}
	else
	{
		// TODO: Delete infinite absence of the user.
	}
}

header( "Location: ". $_SERVER["HTTP_REFERER"], 301 );

?>
