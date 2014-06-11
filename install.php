<?php

require_once( "common.php" );
use database\Database;

$classes = array();

if( is_dir( RootPath."/classes/database" ) )
{
	$handle = opendir( RootPath."/classes/database" );
	
	if( $handle )
	{
		while( ( $file = readdir( $handle ) ) !== false )
		{
			$path = RootPath. "/classes/database/". $file;
			
			if( is_file( $path ) && preg_match( "#\.class\.php$#", $file ) )
				require_once( $path );
		}
		
		closedir( $handle );
		
		foreach( get_declared_classes() as $class )
		{
			if( preg_match( "#^database\\\\#", $class ) )
			{
				if( $class != "database\\Object" && $class != "database\\Database" )
				{
					if( $class::checkClassName() )
						$classes[] = $class;
					else
						die( "$class class has an invalid table name." );
				}
			}
		}
	}
	else
		die( "Can not open \"". RootPath. "/classes/database\" directory." );
}
else
	die( "\"". RootPath. "/classes/database\" is not a directory" );
	
if( count( $classes ) > 0 )
{
	try
	{
		Database::getInstance()->query( "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;" );
	
		foreach( $classes as $class )
		{
			try
			{
				$class::createTable();
			}
			catch( Exception $e )
			{
				echo "An exception has occured:\n".$e->getMessage()."\n";
			}
		}
		
		Database::getInstance()->query( "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;" );
	
		echo "End of installation. If there is no error below, all is OK!";
	}
	catch( Exception $e )
	{
		echo "An exception has occured:\n" . $e->getMessage();
	}
}
else
	echo "# There is no table.";
	
?>

