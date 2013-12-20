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
	"summary" => "Total",
	"no_user" => "Il n'y a aucun utilisateur.",
	"no_bill" => "Il n'y a aucun ticket de caisse ce mois-ci.",
	"cancel" => "Annuler",
	"add" => "Ajouter",
	"date_format" => "dd/mm/YYYY",
	"date_pattern" => "[0-9]{2}/[0-9]{2}/[0-9]{4}",
	"date_pattern_decription" => "Le format de la date est jj/mm/aaaa."
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
