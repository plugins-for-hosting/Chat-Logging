<?php

/*
* Chat Logging v 1.0
*
* Author web-side: Webman
* Copyright @ 2014
*
* Changelog:
* v1.0 - First release.
*/

header('Content-Type: text/html; charset=utf-8');

# Information for connecting to database
$dbinfo_hostname = "";     // Host
$dbinfo_username = ""; // Username
$dbinfo_password = "";      // Password
$dbinfo_dbtable = "";  // Database Name
$dbinfo_tablename = "chatlog"; // Table name (cvar sm_chat_log_table)

$dbinfo_link = "mysql:host=" . $dbinfo_hostname . ";dbname=" . $dbinfo_dbtable . "";

# Database connection
try 
{ 
	$db = new PDO($dbinfo_link, $dbinfo_username, $dbinfo_password);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES 'utf8mb4'");
}

catch(PDOException $e) 
{
    print "Connection failed. The table is missing or the connection data is incorrect.";
}

# Number of recent messages displayed. Default: 25
if (isset($_GET['num']))
{
	$limit = (int)$_GET['num'];
	if (!$limit)
	{
		$limit = 25;
	}
}
else $limit = 25;


$result = $db->query("SELECT * FROM `".$dbinfo_tablename."` ORDER BY `msg_id` DESC LIMIT 0, ".$limit.";");
$data = $result->fetchAll(PDO::FETCH_ASSOC);
$result->closeCursor();
unset($result);
$count = count($data);

?>

<html lang="en">
<head>
<title>Chat Logging</title>
<link href="template/css/bootstrap.min.css" rel="stylesheet">
<link href="template/css/bootstrap.css" rel="stylesheet">
</head>
<body><br/>
	<div class="container">
		<nav class="navbar navbar-default" role="navigation">
			<div class="navbar-header">
				<a class="navbar-brand">Chat Logging</a>
				<p class="navbar-text pull-right">Chat Log</p>
			</div>
		</nav>
		<div class="row">
			<div class="col-md-12">
				Show Records:
					<div class="btn-group btn-group-sm">
						<a class="btn btn-default" href="?num=25">25</a>
						<a class="btn btn-default" href="?num=50">50</a>
						<a class="btn btn-default" href="?num=100">100</a>
					</div><br />
				<div class="panel panel-default">
					<div class="panel-heading">Чат</div>
					<div class="panel-body">
					<?php
					
					# If the chat log is empty
					if ($count <= 0) print "<p class=\"text-center\">Chat log is empty :(</p>";

					foreach ($data as $msg_info)
					{
						# Team colors (bootstrap css class text-*)
						$ingame = true;
						switch((int)$msg_info['team'])
						{
							case 1:
								$textcolor = "muted";
								break;
							case 2:
								$textcolor = "danger";
								break;
							case 3:
								$textcolor = "primary";
								break;
							default:
								$textcolor = "muted";
								$ingame = false;
								break;
						}

						# Team chat - true/false
						$say_team = (bool)($msg_info['type'] == "say_team");
 					
						# time that message wrote
						print "<strong><span class=\"text-info\">[" . date("Y-m-d H:i:s", $msg_info['timestamp']) . "]</span> ";
						
						# in spectator/in team - true/false
						$ingame = (bool)($msg_info['team'] == 0);
						
						# Player is alive / dead
						if ($msg_info['team'] > 1 && !$msg_info['alive']) print "<span style=\"color: #ffb000;\">*DEAD*</span> ";
						
						# Prefixes depending on the type of message (basechat)
						if ($msg_info['type'] == "sm_hsay") print "<span class=\"text-success\">[HSAY]</span>";
						if ($msg_info['type'] == "sm_msay") print "<span class=\"text-success\">[MSAY]</span>";
						if ($msg_info['type'] == "sm_psay") print "<span class=\"text-success\">[PRIVATE]</span>";
						if ($msg_info['type'] == "sm_tsay") print "<span class=\"text-success\">[TSAY]</span>";
						if ($msg_info['type'] == "sm_say") print "<span class=\"text-success\">(ALL)</span>";
						if ($msg_info['type'] == "sm_csay") print "<span class=\"text-success\">[CSAY]</span>";
						
						# Nickname color
						print "<span class=\"text-" .$textcolor. "\">";
						
						# Team chat - prefix
						if($say_team) 
						{
							#Counter Strike
							/*
							switch((int)$msg_info['team'])
							{
								case 2:
									$team = "(TERRORISTS)";
									break;
								case 3:
									$team = "(COUNTER-TERRORISTS)";
									break;
								default:
									$team = "(SPECTATOR)";
									break;
							}
							*/
							# TF2
							switch((int)$msg_info['team'])
							{
								case 2:
									$team = "(RED)";
									break;
								case 3:
									$team = "(BLUE)";
									break;
								default:
									$team = "(SPECTATOR)";
									break;
							}

							print $team;
						}
						
						# Nickname of the player who wrote the message
						print " " . $msg_info['name'] . ":</span> ";
						
						# Message text (if psay - hide)
						if ($msg_info['type'] == "sm_psay") print "<span style=\"color: #ffb000;\">*PRIVATE MESSAGE*</span></strong><br>";
						else print "<span style=\"color: #ffb000;\">" . $msg_info['message'] . "</span></strong><br>";

					}

					# Closing the database connection
					$db = null;
					?>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script src="//code.jquery.com/jquery.js"></script>
    <script src="template/js/bootstrap.min.js"></script>
	<script src="template/js/bootstrap-scrollspy.js"></script>
	
</body>
</html>
