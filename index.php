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
$billSummary = 0.0;

// Compute bill summaries
$odd = true;
foreach( $bills as $bill )
{
	$user = new User( $bill->user_id );
	$billSummary += $bill->amount;

	$template->addBlock( new Block( "bill", array(
		"odd" => $odd,
		"id" => $bill->bill_id,
		"date" => strftime( $language["date_format"], strtotime( $bill->purchase_date ) ),
		"user" => ucfirst( $user->user_name ),
		"shop" => ucfirst( $bill->shop_name ),
		"amount" => number_format( $bill->amount, 2, ".", " " ). "&nbsp;". $language["currency"]
	) ) );

	$odd = !$odd;
}

$template->addVariable( "billsSummary", number_format( $billSummary, 2, ".", " " ). "&nbsp;". $language["currency"] );

// Compute each user purchases & debts
$odd = true;
foreach( $users as $user )
{
	$target = $billSummary / count( $users );
	$purchases = 0.0;

	foreach( $bills as $bill )
	{
		if( $bill->user_id == $user->user_id )
			$purchases += $bill->amount;
	}

	$balance = $purchases - $target;

	$template->addBlock( new Block( "user", array(
		"odd" => $odd,
		"id" => $user->user_id,
		"name" => ucfirst( $user->user_name ),
		"target" => number_format( $target, 2, ".", " " ). "&nbsp;". $language["currency"],
		"purchases" => number_format( $purchases, 2, ".", " " ). "&nbsp;". $language["currency"],
		"balance" => number_format( $balance, 2, ".", " " ). "&nbsp;". $language["currency"],
		"positive" => ( $balance > 0 ) ? "positive" : "",
		"negative" => ( $balance < 0 ) ? "negative" : ""
	) ) );

	$odd = !$odd;
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
