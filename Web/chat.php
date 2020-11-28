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

# Game info

$gameinfo = "TF2"; // CS(Counter Strike) or TF2(Team Fortress 2)

require_once("./dbinfo.php");

$maxlog_per_page = 36;
$maxpagecount = 9;

# Database connection
try 
{ 
	$db = new PDO($dbinfo_link, $dbinfo_username, $dbinfo_password);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

catch(PDOException $e) 
{
    print "Connection failed. The table is missing or the connection data is incorrect.";
}

# Page Number: Default is 1
if(isset($_GET["page"]) && intval($_GET["page"]) >= 1)
{
	$page = intval($_GET["page"]);
}
else
{
	$page = 1;
}

$result = $db->query("SELECT COUNT(*) AS `count` FROM `{$dbinfo_tablename}`;");
$data = $result->fetch(PDO::FETCH_ASSOC);
$result->closeCursor();
unset($result);
$rowcount = $data["count"];

$begin = ($page - 1) * $maxlog_per_page;

$result = $db->query("SELECT * FROM `{$dbinfo_tablename}` ORDER BY `msg_id` DESC LIMIT {$begin}, {$maxlog_per_page};");
$data = $result->fetchAll(PDO::FETCH_ASSOC);
$result->closeCursor();
unset($result);
$count = count($data);

?>

<html lang="en">
<head>
<title>Chat Logging</title>
<meta charset="utf-8">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/brython@3.9.0/brython.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/brython@3.9.0/brython_stdlib.js"></script>
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
						<?php
						$left = $page - 1;
						$right = $page + 1;
						if($left >= 1)
							echo("						<a class=\"btn btn-default\" href=\"?page={$left}\">◀</a>");
						
						if($page < ceil($maxpagecount / 2))
						{
							$startfrom = 1;
						}
						else
						{
							$startfrom = $page - floor(($maxpagecount - 1)/ 2);
						}
						
						$i = $startfrom;
						
						while($i < $startfrom + $maxpagecount && $i <= ceil($rowcount / $maxlog_per_page))
						{
							echo("						<a class=\"btn btn-default\" href=\"?page={$i}\">{$i}</a>");
							
							$i++;
						}
						if($right <= ceil($rowcount / $maxlog_per_page))
							echo("						<a class=\"btn btn-default\" href=\"?page={$right}\">▶</a>");
						?>
					</div><br />
				<div class="panel panel-default">
					<div class="panel-heading">Chat</div>
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
						echo("<strong class='class_chatlog' id='{$msg_info["msg_id"]}'><span class=\"text-info\">[" . date("Y-m-d H:i:s", $msg_info['timestamp']) . "]</span> ");
						
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
							switch ($gameinfo)
							{
								case "CSS":
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
									
									break;
									
								case "TF2":
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
	
	<script type="text/python">
from browser import document, html, ajax, bind
from browser.widgets.dialog import Dialog
@bind("strong.class_chatlog", "click")
def onclick(ev):
	left = ev.x
	top = ev.y

	ev.target.attrs["id"]

	d = Dialog("Test", ok_cancel=True);
	pass
	</script>
</body>
</html>
