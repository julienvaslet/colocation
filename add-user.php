<?php

require_once( "common.php" );
require_once( RootPath. "/classes/database/User.class.php" );

use database\User;

if( isset( $_POST['username'] ) && !empty( $_POST['username'] ) )
{
	if( User::count( array( 'username' => $_POST['username'] ) ) == 0 )
	{
		User::create( array( 'username' => $_POST['username'] ) );
	}
	else
	{
		// TODO: Delete infinite absence of the user.
	}
}

//header( 'Location: /', 301 );

?>
