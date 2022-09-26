<?php
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
set_time_limit(0);

require 'get_snookerInfo.php';

$CONFIG = array();
$CONFIG['server'] = 'irc.quakenet.org';
$CONFIG['nick'] = 'AlanMcManus';
$CONFIG['port'] = 6667;
$CONFIG['channel'] = '#snooker';
$CONFIG['name'] = 'AlanMcManus';
$CONFIG['admin_pass'] = 'paff';

$con = array();

init();

function init()
{
	global $con, $CONFIG, $old_buffer;

	$firstTime = true;

	$con['socket'] = fsockopen($CONFIG['server'], $CONFIG['port']);
	
	if (!$con['socket']) {
		print ("Could not connect to: ". $CONFIG['server'] ." on port ". $CONFIG['port']);
	} else {
		cmd_send("USER Cail 0 * :Cailbot");
		cmd_send("NICK ". $CONFIG['nick']);
		
		while (!feof($con['socket'])) {
			$con['buffer']['all'] = trim(fgets($con['socket'], 4096));
			print date("[H:i]")."<- ".$con['buffer']['all'] ."\n";
			
			//PING PONG
			if(substr($con['buffer']['all'], 0, 6) == 'PING :') {
				cmd_send('PONG :'.substr($con['buffer']['all'], 6));
				if ($firstTime == true){
					cmd_send("JOIN ". $CONFIG['channel']);				
					$firstTime = false;
				}
			
			//DATA
			} elseif ($old_buffer != $con['buffer']['all']) {
				parse_buffer();
				process_commands();
			}
			$old_buffer = $con['buffer']['all'];
		}
	}
}

function process_commands() {
	global $con, $CONFIG, $nick;

	$tok = explode(" ", $con['buffer']['text']);
			
	if ($nick == "Cail") {
		/* Close connection */
		if ($con['buffer']['text'] == '.quit')  {
			die();
		}					
	}
	
	/************ SNOOKER COMMANDS **************/

	switch ($con['buffer']['text']) {

		/* !NEXT */
		case '!next':
			$next = snooker_next();
			cmd_send(prep_text('Next is: ' . $next[1]['date'] . " | " . $next[1]['tournament'] . ' | Players: ' . $next[1]['players']));
			break;

		/* ALTERNATIVE !NEXT "WHEN NEXT TOURNAMENT" */	
		case (preg_match('/when.*next.*tournament/', $con['buffer']['text']) ? true : false):		
			$next = snooker_next();
			cmd_send(prep_text('Next is: ' . $next[1]['date'] . " | " . $next[1]['tournament'] . ' | Players: ' . $next[1]['players']));
			break;

		/* !UPCOMING */
		case '!upcoming':
			if ($con['buffer']['text'] == '!upcoming')  {
				$upcomingMatches = snooker_upcoming();

				foreach ($upcomingMatches as $upcoming) {		
					cmd_send(prep_text($upcoming));
				}
			}
			break;

		/* !CAT */
		case '!cat':
			$cat = snooker_cat();

			cmd_send(prep_text('Have a random catpic, LOOK AT IT! ' . $cat));
			break;

		/* !TOPIC */
		case '!topic':
			$sn_topic = snooker_topic();

			cmd_send(prep_text($sn_topic, 'TOPIC'));
			break;
	}
}

function cmd_send($command) {
	global $con, $time, $CONFIG;

	fputs($con['socket'], $command."\n\r");
	print (date("[H:i]") ."-> ". $command. "\n\r");	
}

function parse_buffer() {	
	global $con, $CONFIG, $nick, $nicks;
		
	$buffer = $con['buffer']['all'];
	$buffer = explode(" ", $buffer, 4);
	
	/* Get username */
	$buffer['username'] = substr($buffer[0], 1, strpos($buffer['0'], "!")-1);
	$nick = $buffer['username'];
	
	/* Get identd */
	$posExcl = strpos($buffer[0], "!");
	$posAt = strpos($buffer[0], "@");
	$buffer['identd'] = substr($buffer[0], $posExcl+1, $posAt-$posExcl-1); 
	$buffer['hostname'] = substr($buffer[0], strpos($buffer[0], "@")+1);
	
	/* The user and the host */
	$buffer['user_host'] = substr($buffer[0],1);

	switch (strtoupper($buffer[1]))
	{
		case "JOIN":
		   	$buffer['text'] = "*JOINS: ". $buffer['username']." ( ".$buffer['user_host']." )";
			$buffer['command'] = "JOIN";
			$buffer['channel'] = $CONFIG['channel'];
		   	break;
		case "QUIT":
		   	$buffer['text'] = "*QUITS: ". $buffer['username']." ( ".$buffer['user_host']." )";
			$buffer['command'] = "QUIT";
			$buffer['channel'] = $CONFIG['channel'];
		   	break;
		case "NOTICE":
		   	$buffer['text'] = "*NOTICE: ". $buffer['username'];
			$buffer['command'] = "NOTICE";
			$buffer['channel'] = substr($buffer[2], 1);
		   	break;
		case "PART":
		  	$buffer['text'] = "*PARTS: ". $buffer['username']." ( ".$buffer['user_host']." )";
			$buffer['command'] = "PART";
			$buffer['channel'] = $CONFIG['channel'];
		  	break;
		case "MODE":
		  	$buffer['text'] = $buffer['username']." sets mode: ".$buffer[3];
			$buffer['command'] = "MODE";
			$buffer['channel'] = $buffer[2];
		break;
		case "NICK":
			$buffer['text'] = "*NICK: ".$buffer['username']." => ".substr($buffer[2], 1)." ( ".$buffer['user_host']." )";
			$buffer['command'] = "NICK";
			$buffer['channel'] = $CONFIG['channel'];
		break;
		case "TOPIC":
			/* leave empty for now */
		break;

		default:
			// it is probably a PRIVMSG
			$buffer['command'] = $buffer[1];
			$buffer['channel'] = $buffer[2];
			$buffer['text'] = substr($buffer[3], 1);	
		break;	
	}
	$con['buffer'] = $buffer;
}

function prep_text($message, $type = 'PRIVMSG') {
	global $con;
	return ($type . ' '. $con['buffer']['channel'] .' :'.$message);
}

?>