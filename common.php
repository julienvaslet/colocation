<?php
define( 'RootPath', '.' );
define( 'TemplateName', 'colocation' );

require_once( RootPath.'/classes/internet/Template.class.php' );
$template = new Template( RootPath. '/templates/'. TemplateName );
$db = new mysqli( 'localhost', 'colocation', 'colocation', 'colocation' );

$language = array(
	"days" => array( "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ),
	"monthes" => array( "Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "D&eacute;cembre" )
);

/*function date_fr( $time, $show_time = false )
{
	$days = array( "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" );
	$monthes = array( "Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "D&eacute;cembre" );
	
	$date = $days[ date( "w", $time ) ]. " ". date( "j", $time ). ( date( "j", $time ) == "1" ? "er" : "" ). " ". $monthes[ date( "n", $time ) - 1 ]. " ". date( "Y", $time );
	
	if( $show_time === true )
		$date .= " &agrave; ". date( "H", $time ). "h". date( "i", $time );
	
	return $date;
}*/

?>
