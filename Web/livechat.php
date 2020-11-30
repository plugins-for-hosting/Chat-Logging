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

/*
require_once("./dbinfo.php");

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
*/
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
                Live Chat:
                <div class="panel panel-default">
                    <div class="panel-heading">Chat</div>
                    <div class="panel-body">

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
    <script type="text/python" src="./livechat.py" id="<?php echo($gameinfo); ?>"></script>
</body>
</html>
