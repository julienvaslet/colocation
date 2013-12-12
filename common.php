<?php
define( "RootPath", dirname( __FILE__ ) );
define( 'TemplateName', 'colocation' );

require_once( RootPath. "/classes/database/Database.class.php" );
require_once( RootPath. "/classes/database/Object.class.php" );

use database\Database;
use database\Object;

new Database( "127.0.0.1", 3306, "colocation", "colocation", "colocation" );

require_once( RootPath.'/classes/internet/Template.class.php' );
$template = new Template( RootPath. '/templates/'. TemplateName );

$language = array(
	"days" => array( "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ),
	"monthes" => array( "Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "D&eacute;cembre" ),
	"username" => "Personne",
	"purchases" => "Achats",
	"target" => "Cible",
	"balance" => "Balance",
	"date" => "Date",
	"shop" => "Magasin",
	"amount" => "Montant",
	"summary" => "Total"
);

function addLanguageVariables( $variables, $basename )
{
	global $template;
	
	foreach( $variables as $name => $value )
	{
		if( is_array( $value ) )
			addLanguageVariables( $value, $basename.".".$name );
		else
			$template->addVariable( $basename.".".$name, $value );
	}
}

addLanguageVariables( $language, "language" );

?>
