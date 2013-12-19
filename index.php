<?php
require_once( "common.php" );
require_once( RootPath. "/classes/database/Bill.class.php" );
require_once( RootPath. "/classes/database/User.class.php" );
require_once( RootPath. "/classes/database/Category.class.php" );
require_once( RootPath. "/classes/database/BillCategory.class.php" );
require_once( RootPath. "/classes/database/UserCategoryExclusion.class.php" );

use database\Bill;
use database\User;
use database\Category;
use database\BillCategory;
use database\UserCategoryExclusion;

// Get the selected (or current by default) month
$currentMonth = NULL;
$currentYear = NULL;

if( !empty( $_GET["month"] ) )
{
	if( preg_match( "/^[0-9]+$/", $_GET["month"] ) == 1 )
		$currentMonth = intval( $_GET["month"] );

	if( !empty( $_GET["year"] ) )
	{
		if( preg_match( "/^[0-9]+$/", $_GET["year"] ) == 1)
			$currentYear = intval( $_GET["year"] );
	}
}

if( is_null( $currentMonth ) || $currentMonth <= 0 || $currentMonth > 12 )
	$currentMonth = intval( date( "n" ) );

if( is_null( $currentYear ) || $currentYear <= 0 || $currentYear > intval( date( "Y" ) ) )
	$currentYear = intval( date( "Y" ) );


// Show the month's siblings
$monthSiblingsCount = 2; 
$navigationMonthes = range( $monthSiblingsCount * -1, $monthSiblingsCount );

$diffMonthes = ((intval( date( "Y" ) ) - $currentYear) * 12) + (intval( date( "n" ) ) - $currentMonth);

if( $diffMonthes > $monthSiblingsCount )
	$navigationMonthes[0] = $diffMonthes * -1;

else if( $diffMonthes < $monthSiblingsCount )
{
	for( $i = 0 ; $i < $monthSiblingsCount * 2 + 1 ; $i++ )
		$navigationMonthes[$i] = $i - $diffMonthes;
}

for( $i = 0 ; $i < count( $navigationMonthes ) ; $i++ )
{
	$month = $currentMonth - $navigationMonthes[$i];
	$year = $currentYear;

	if( $month <= 0 )
	{
		$year -= ceil( (( $month * -1 ) + 1) / 12.0 );

		while( $month <= 0 )
			$month += 12;
	}
	else if( $month > 12 )
	{
		$year += floor( ($month - 1) / 12.0 );

		while( $month > 12 )
			$month -= 12;
	}

	$template->addBlock( new Block( "month", array(
		"name" => $language["monthes"][$month - 1]. " " .$year,
		"month" => $month,
		"year" => $year,
		"selected" => ( $currentMonth == $month && $currentYear == $year ) ? "selected" : "",
		"far" => ( $i < count( $navigationMonthes ) - 1 && $navigationMonthes[$i+1] - $navigationMonthes[$i] > 1 ) ? "far" : ""
	) ) );
}

$template->addVariable( "MonthName", $language["monthes"][$currentMonth - 1]. " " .$currentYear );

$bills = Bill::get( array(), "purchase_date ASC" );
$users = User::get( array(), "user_name ASC" );
$billSummaries = array();

// Compute bill summaries
foreach( $bills as $bill )
{

}

// Compute each user purchases & debts
foreach( $users as $user )
{
	
}

$template->addVariable( "UsersCount", count( $users ) );
$template->addVariable( "BillsCount", count( $bills ) );
$template->addVariable( "BillSummary", "0" );

/*$posts = Post::get( array(), "creation_datetime DESC", 1, 10 );
$i = 0;

foreach( $posts as $post )
{
	$template->addBlock( new Block( "post", array(
		"id" => $post->post_id,
		"title1" => htmlentities( $post->title1 ),
		"title2" => htmlentities( $post->title2 ),
		"content" => "<p>". htmlentities( $post->content ). "</p>",
		"last" => ++$i == count( $posts )
	) ) );
}*/

$template->show( "month.html" );
?>
