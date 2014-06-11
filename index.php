<?php
require_once( "common.php" );
require_once( RootPath. "/classes/database/Bill.class.php" );
require_once( RootPath. "/classes/database/User.class.php" );
require_once( RootPath. "/classes/database/Category.class.php" );
require_once( RootPath. "/classes/database/UserAbsence.class.php" );
require_once( RootPath. "/classes/database/BillCategory.class.php" );
require_once( RootPath. "/classes/database/UserCategoryExclusion.class.php" );

use database\Bill;
use database\User;
use database\Category;
use database\UserAbsence;
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

$categories = Category::get( array(), "category_name ASC" );
$bills = Bill::get( array( "purchase_date" => array( array( ">=", $currentYear."-".$currentMonth."-01" ), array( "<", $currentYear."-".($currentMonth + 1)."-01" ) ) ), "purchase_date ASC" );
$users = User::get( array(), "user_name ASC" );
$usersAbsences = UserAbsence::get();
$usersCategoriesExclusions = UserCategoryExclusion::get();
$billSummary = 0.0;
$billSummaries = array();
$categoriesSummary = array();

// Remove users who are absent for the whole month
$usersToRemove = array();
$firstDayOfMonth = $currentYear."-".( $currentMonth < 10 ? "0" : "" ).$currentMonth."-01";
$lastDayOfMonth = date( "Y-m-d", mktime( 0, 0, 0, $currentMonth + 1, 0, $currentYear ) );

$monthPeriods = array(
	array(
		"start" => 1,
		"end" => 31,
		"users" => array()
	)
);

foreach( $users as $user )
	$monthPeriods[0]["users"][] = $user->user_id;

foreach( $users as $user )
{
	foreach( $usersAbsences as $absence )
	{
		if( $absence->user_id == $user->user_id )
		{
			// Absence is not in the current month
			if( ( is_null( $absence->date_start ) && $absence->date_end < $firstDayOfMonth ) || ( is_null( $absence->date_end ) && $absence->date_start <= $firstDayOfMonth ) || ( $absence->date_start <= $firstDayOfMonth && $absence->date_end >= $lastDayOfMonth ) )
			{
				$usersToRemove[] = $user->user_id;

				// Remove user from month's periods
				for( $i = 0 ; $i < count( $monthPeriods ) ; $i++ )
				{
					for( $j = 0 ; $j < count( $monthPeriods[$i]["users"] ) ; $j++ )
					{
						if( $monthPeriods[$i]["users"][$j] == $user->user_id )
						{
							array_splice( $monthPeriods[$i]["users"], $j, 1 );
							break;
						}
					}
				}

				break;
			}

			// Create period for this absence
			else if( ($absence->date_start >= $firstDayOfMonth && $absence->date_start <= $lastDayOfMonth) || ($absence->date_end >= $firstDayOfMonth && $absence->date_end <= $lastDayOfMonth ) )
			{
				// TODO: put the 31th if its end is not in this month
				$abs = array( "start" => intval( date( "d", strtotime( $absence->date_start ) ) ), "end" => $absence->date_end >= $lastDayOfMonth ? 31 : intval( date( "d", strtotime( $absence->date_end ) ) ) );
				for( $i = 0 ; $i < count( $monthPeriods ) ; $i++ )
				{
					if( $monthPeriods[$i]["start"] >= $abs["start"] && $monthPeriods[$i]["end"] > $abs["start"]
					 || $monthPeriods[$i]["end"] > $abs["start"] && $monthPeriods[$i]["end"] >= $abs["end"]
					 || $monthPeriods[$i]["start"] <= $abs["start"] && $monthPeriods[$i]["end"] <= $abs["end"]
					 || $monthPeriods[$i]["start"] >= $abs["start"] && $monthPeriods[$i]["end"] >= $abs["end"] )
					{
						$periods = array();
						$periodUsers = array();

						foreach( $monthPeriods[$i]["users"] as $user_id )
						{
							if( $user_id != $user->user_id )
								$periodUsers[] = $user_id;
						}

						if( $monthPeriods[$i]["start"] <= $abs["start"] - 1 )
						{
							$periods[] = array(
								"start" => $monthPeriods[$i]["start"],
								"end" => $abs["start"] - 1,
								"users" => $monthPeriods[$i]["users"]
							);
						}

						$periods[] = array(
							"start" => $monthPeriods[$i]["start"] > $abs["start"] ? $monthPeriods[$i]["start"] : $abs["start"],
							"end" => $monthPeriods[$i]["end"] < $abs["end"] ? $monthPeriods[$i]["end"] : $abs["end"],
							"users" => $periodUsers
						);

						if( $monthPeriods[$i]["end"] >= $abs["end"] + 1 )
						{
							$periods[] = array(
								"start" => $abs["end"] + 1,
								"end" => $monthPeriods[$i]["end"],
								"users" => $monthPeriods[$i]["users"]
							);
						}

						array_splice( $monthPeriods, $i, 1, $periods );
						$i += count( $periods ) - 1;
					}
				}	
			}
		}
	}
}

foreach( $usersToRemove as $id )
{
	// Removing user
	$index = -1;

	for( $i = 0 ; $i < count( $users ) ; $i++ )
	{
		if( $users[$i]->user_id == $id )
		{
			$index = $i;
			break;
		}
	}

	if( $index > -1 )
		array_splice( $users, $index, 1 );

	// Removing user's absences
	$index = -1;

	while( $index != -1 )
	{
		for( $i = 0 ; $i < count( $usersAbsences ) ; $i++ )
		{
			if( $usersAbsences[$i]->user_id == $id )
			{
				$index = $i;
				break;
			}
		}

		if( $index > -1 )
			array_splice( $usersAbsences, $index, 1 );
	}
}

// Initialising summaries
foreach( $monthPeriods as $period => $mPeriod )
	$billSummaries[$period] = 0.0;

$categoriesName = array();
$bills_id = array( "set", NULL );

foreach( $bills as $bill )
	$bills_id[] = $bill->bill_id;

$billCategories = BillCategory::get( array( "bill_id" => $bills_id ) );

foreach( $categories as $category )
{
	$categoriesName[$category->category_id] = ucfirst( $category->category_name );
	$template->addBlock( new Block( "category", array(
		"id" => $category->category_id,
		"name" => ucfirst( $category->category_name )
	) ) );

	$categoriesSummary[$category->category_id] = array();
	foreach( $monthPeriods as $period => $v )
		$categoriesSummary[$category->category_id][$period] = 0.0;
}

// Compute bill summaries
$odd = true;
foreach( $bills as $bill )
{
	$user = new User( $bill->user_id );
	$day = intval( date( "d", strtotime( $bill->purchase_date ) ) );
	$billSummary += $bill->amount;

	foreach( $monthPeriods as $period => $mPeriod )
	{
		if( $mPeriod["start"] <= $day && $mPeriod["end"] >= $day )
		{
			$billSummaries[$period] += $bill->amount;
			break;
		}
	}

	$billBlock = new Block( "bill", array(
		"odd" => $odd ? "odd" : "",
		"id" => $bill->bill_id,
		"date" => strftime( $language["date_format"], strtotime( $bill->purchase_date ) ),
		"user" => ucfirst( $user->user_name ),
		"shop" => ucfirst( $bill->shop_name ),
		"amount" => number_format( $bill->amount, 2, ".", " " ). "&nbsp;". $language["currency"]
	) );

	foreach( $billCategories as $billCategory )
	{
		if( $billCategory->bill_id == $bill->bill_id )
		{
			$billBlock->addBlock( new Block( "category", array(
				"name" => $categoriesName[$billCategory->category_id],
				"amount" => number_format( $billCategory->amount, 2, ".", " " ). "&nbsp;". $language["currency"]
			) ) );

			foreach( $monthPeriods as $period => $mPeriod )
			{
				if( $mPeriod["start"] <= $day && $mPeriod["end"] >= $day )
				{
					$categoriesSummary[$billCategory->category_id][$period] += $billCategory->amount;
					break;
				}
			}
		}
	}

	$template->addBlock( $billBlock );

	$odd = !$odd;
}

$template->addVariable( "billsSummary", number_format( $billSummary, 2, ".", " " ). "&nbsp;". $language["currency"] );

foreach( $categoriesSummary as $category_id => $summaries )
{
	$summary = 0.0;

	foreach( $summaries as $sum )
		$summary += $sum;

	$template->addBlock( new Block( "summary", array(
		"name" => $categoriesName[$category_id],
		"amount" => number_format( $summary, 2, ".", " " ). "&nbsp;". $language["currency"]
	) ) );
}

$categoriesCount = array();

foreach( $monthPeriods as $period => $mPeriod )
{
	$categoriesCount[$period] = array();

	foreach( $categories as $category )
	{
		$categoriesCount[$period][$category->category_id] = count( $users );

		foreach( $usersCategoriesExclusions as $exclusion )
		{
			if( $exclusion->category_id == $category->category_id )
				$categoriesCount[$period][$category->category_id]--;
		}
	}
}

// Compute each user purchases & debts
$odd = true;
foreach( $users as $user )
{
	$target = 0.0;

	foreach( $categoriesCount as $period => $counts )
	{
		$countUsers = count( $monthPeriods[$period]["users"] );
		if( in_array( $user->user_id, $monthPeriods[$period]["users"] ) )
		{
			$periodTarget = $billSummaries[$period] / $countUsers;

			foreach( $counts as $category => $count )
			{
				$excluded = false;

				foreach( $usersCategoriesExclusions as $exclusion )
				{
					if( $category == $exclusion->category_id && $exclusion->user_id == $user->user_id )
					{
						$periodTarget -= $categoriesSummary[$category][$period] / $countUsers;
						$excluded = true;
						break;
					}
				}

				if( !$excluded && $count < count( $users ) )
					$periodTarget += $categoriesSummary[$category][$period] / $countUsers * ($countUsers - $count) / $count;
			}

			$target += $periodTarget;
		}
	}

	$purchases = 0.0;

	foreach( $bills as $bill )
	{
		if( $bill->user_id == $user->user_id )
			$purchases += $bill->amount;
	}

	$balance = $purchases - $target;

	$userBlock = new Block( "user", array(
		"odd" => $odd ? "odd" : "",
		"id" => $user->user_id,
		"name" => ucfirst( $user->user_name ),
		"target" => number_format( $target, 2, ".", " " ). "&nbsp;". $language["currency"],
		"purchases" => number_format( $purchases, 2, ".", " " ). "&nbsp;". $language["currency"],
		"balance" => number_format( $balance, 2, ".", " " ). "&nbsp;". $language["currency"],
		"positive" => ( $balance > 0 ) ? "positive" : "",
		"negative" => ( $balance < 0 ) ? "negative" : ""
	) );

	foreach( $categoriesName as $idCategory => $name )
	{
		$excluded = false;

		foreach( $usersCategoriesExclusions as $exclusion )
		{
			if( $exclusion->user_id == $user->user_id && $exclusion->category_id == $idCategory )
			{
				$excluded = true;
				break;
			}
		}

		$userBlock->addBlock( new Block( "exclusion", array(
			"id" => $idCategory,
			"name" => $name,
			"excluded" => $excluded ? "true" : "false"
		) ) );
	}

	$template->addBlock( $userBlock );

	$odd = !$odd;
}

$template->addVariable( "UsersCount", count( $users ) );
$template->addVariable( "BillsCount", count( $bills ) );

// Get the count of day in the current month
$lastDay = date( "d", mktime( 0, 0, 0, $currentMonth + 1, 0, $currentYear ) );

for( $i = 1 ; $i <= $lastDay ; $i++ )
{
	$template->addBlock( new Block( "day", array(
		"value" => $i < 10 ? "0".$i : $i
	) ) );
}

$template->addVariable( "CurrentYear", $currentYear );
$template->addVariable( "CurrentMonth", $currentMonth < 10 ? "0". $currentMonth : $currentMonth );

$template->show( "month.html" );
?>
