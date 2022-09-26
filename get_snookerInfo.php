<?php
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);

/* for testing */
if ($_GET['action'] == "next") { snooker_next(); }
if ($_GET['action'] == "upcoming") { snooker_upcoming(); }
if ($_GET['action'] == "update") { snooker_update(); }
if ($_GET['action'] == "topic") { snooker_topic(); }
if ($_GET['action'] == "cat") { snooker_cat(); }

function snooker_update() {
	$currentFile = './snooker_schedule.txt';
	$newFile = file_get_contents('http://api.snooker.org/?t=5&s=2022');
	file_put_contents('./snooker_schedule.txt', $newFile);
}		

function snooker_upcoming() {
	$sn_info = json_decode(file_get_contents('./snooker_schedule.txt'), true);
	$output = Array();

	$x = 1;
	foreach ($sn_info as $sn_tournament) {
		if (strtotime($sn_tournament['EndDate']) > time()) {
			$output[] = $x . ": " . $sn_tournament['Name'] . " | " . mod_date($sn_tournament['StartDate'],$sn_tournament['EndDate']) . " | Type: " . $sn_tournament['Type'] . "\n";
			$x++;
		}
		
		if ($x == 6) { return $output; }
	}
}

function snooker_next() {
	$sn_info = json_decode(file_get_contents('./snooker_schedule.txt'), true);
	$output = Array();

	foreach ($sn_info as $sn_tournament) {
		if (($sn_tournament['Type'] == "Ranking") || (in_array($sn_tournament['Name'], array("Masters", "Shanghai Masters", "Champion of Champions", "Paul Hunter Classic"))))  {

			if ( (strtotime($sn_tournament['StartDate']) <= time() ) && ((strtotime($sn_tournament['EndDate']) + 76400) > time()) ) {
				$sn_current['date'] = mod_date($sn_tournament['StartDate'],$sn_tournament['EndDate']);
				$sn_current['tournament'] = $sn_tournament['Name'];
				$sn_current['players'] = $sn_tournament['NumCompetitors'];
			}

			if (strtotime($sn_tournament['StartDate']) > time()) {
				$sn_current = $sn_current ? $sn_current : "None";
				$sn_next['date'] = mod_date($sn_tournament['StartDate'],$sn_tournament['EndDate']);
				$sn_next['tournament'] = $sn_tournament['Name'];
				$sn_next['players'] = $sn_tournament['NumCompetitors'];

				$output[] = $sn_current;
				$output[] = $sn_next;

				return $output;	
			}
		}
	}
}

function snooker_topic() {
	$matches = snooker_next();
	$sn_current = $matches[0] != 'none' ? $matches[0]['tournament'] . ' - ' . $matches[0]['date'] : 'none'; 
	$sn_next = $matches[1]['tournament'] . ' - ' . $matches[1]['date'];
	$sn_extra = 'This channel uses cookies';

	return 'Current: ' . $sn_current . ' | Next: ' . $sn_next . ' | ' . $sn_extra;
}

function snooker_cat() {
	$sn_cat = json_decode(file_get_contents('https://api.thecatapi.com/v1/images/search'), true);
	return $sn_cat[0]['url'];		
}

function mod_date($start,$end) {
	$start = strtotime($start);
	$end  = strtotime($end);
	
	if (date("M",$start) == date("M",$end)) {
		return date("j",$start) . "-" . date("j",$end) . " " . date("M",$end);
	}
	else {
		return date("j M",$start) . " - " . date("j M",$end);
	}
}

/* Data fields for the tournament
[Name] => Masters
[StartDate] => 2019-01-13
[EndDate] => 2019-01-20
[Type] => Invitational
[NumCompetitors] => 16	
*/

?>