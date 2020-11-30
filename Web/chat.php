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
require_once("./include/error.inc.php");

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
    echo("Connection failed. The table is missing or the connection data is incorrect.");
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
$row_count = $data["count"];
unset($data);

if(!(($page - 1) * $maxlog_per_page <= $row_count))
    $page = floor($row_count / $maxlog_per_page) + 1;

$begin = ($page - 1) * $maxlog_per_page;

$stmt = $db->prepare("SELECT * FROM `{$dbinfo_tablename}` ORDER BY `msg_id` DESC LIMIT :begin_from, :maxlog_per_page;");
$stmt->bindParam(":begin_from", $begin, PDO::PARAM_INT);
$stmt->bindParam(":maxlog_per_page", $maxlog_per_page, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
unset($stmt);
$count = count($data);

?>

<html lang="en">
<head>
<title>Chat Logging</title>
<meta charset="utf-8">
<link href="template/css/bootstrap.min.css" rel="stylesheet">
<link href="template/css/bootstrap.css" rel="stylesheet">
</head>
<body onload="brython()"><br/>
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
                        echo("<a class=\"btn btn-default\" href=\"?page={$left}\">◀</a>");
                     
                    if($page < ceil($maxpagecount / 2))
                        $startfrom = 1;
                    else
                        $startfrom = $page - floor(($maxpagecount - 1)/ 2);
                    
                    $i = $startfrom;
                    
                    while($i < $startfrom + $maxpagecount && $i <= ceil($row_count / $maxlog_per_page))
                    {
                        echo("<a class=\"btn btn-default\" href=\"?page={$i}\">{$i}</a>");
                        $i++;
                    }
                    if($right <= ceil($row_count / $maxlog_per_page))
                        echo("<a class=\"btn btn-default\" href=\"?page={$right}\">▶</a>");
                ?>
                </div><br />
                <div class="panel panel-default">
                    <div class="panel-heading">Chat</div>
                    <div class="panel-body">
                    <?php
                    
                    # If the chat log is empty
                    if ($count <= 0) echo("<p class=\"text-center\">Chat log is empty :(</p>");

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
                        if ($msg_info['team'] > 1 && !$msg_info['alive']) echo("<span style=\"color: #ffb000;\">*DEAD*</span> ");
                        
                        # Prefixes depending on the type of message (basechat)
                        if(isset($msg_info["type"]))
                        {
                            switch ($msg_info['type'])
                            {
                                case "sm_hsay":
                                    $msg_type = "[HSAY]";
                                    break;
    
                                case "sm_msay":
                                    $msg_type = "[MSAY]";
                                    break;
    
                                case "sm_psay":
                                    $msg_type = "[PRIVATE]";
                                    break;
    
                                case "sm_tsay":
                                    $msg_type = "[TSAY]";
                                    break;
    
                                case "sm_say":
                                    $msg_type = "(ALL)";
                                    break;
    
                                case "sm_csay":
                                    $msg_type = "[CSAY]";
                                    break;
                            }
                            echo("<span class=\"text-success\">{$msg_type}</span>");
                        }
                        
                        # Nickname color
                        echo("<span class=\"text-{$textcolor}\">");
                        
                        # Team chat - prefix
                        if($say_team) 
                        {
                            switch ($gameinfo)
                            {
                                case "CS":
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

                            echo($team);
                        }
                        
                        # Nickname of the player who wrote the message
                        echo(" {$msg_info['name']}:</span> ");
                        
                        # Message text (if psay - hide)
                        $message = ($msg_info['type'] == "sm_psay") ? "*PRIVATE MESSAGE*" : $msg_info['message'];
                        echo("<span style=\"color: #ffb000;\">{$message}</span></strong><br>");

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
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/brython@3.9.0/brython.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/brython@3.9.0/brython_stdlib.js"></script>
    <script type="text/python" src="./steamid.py"></script>
</body>
</html>
