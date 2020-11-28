<?php

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(-1);

require_once("./dbinfo.php");

function send_json(int $code, string $msg, array ...$element) : void
{
    $assoc = array(
        "code" => $code,
        "msg" => $msg,
    );

    if(isset($element) && !empty($element))
    {
        $assoc["data"] = array_merge(...$element);
    }

    $file = json_encode($assoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    header("{$_SERVER["SERVER_PROTOCOL"]} {$code} {$msg}");
    header("Content-Type: application/json");
    header("Content-Length: " . strlen($file));
    exit($file);
}

if($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["msg_id"]))
{
    if(!is_numeric($_GET["msg_id"]))
        send_json(400, "Bad Request", array("error_msg" => "query 'msg_id' is not valid number."));
    
    $msg_id = intval($_GET["msg_id"]);

    try 
    { 
        $db = new PDO($dbinfo_link, $dbinfo_username, $dbinfo_password);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SELECT * FROM `{$dbinfo_tablename}` WHERE `msg_id` = ? LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $msg_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        send_json(200, "OK", $result);
    
    }
    catch(PDOException $e) 
    {
        echo("Connection failed. The table is missing or the connection data is incorrect.\r\n");
        echo($e->getMessage());
    }
}