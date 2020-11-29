<?php
require_once("./include/error.inc.php");
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
        //$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SELECT * FROM `{$dbinfo_tablename}` WHERE `msg_id` = ? LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $msg_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!isset($result) || $result === false)
        {
            send_json(404, "Not Found", array("error_msg" => "No Result Found"));
        }

        $array = array(
            "name" => $result["name"],
            "auth" => $result["auth"],
        );

        send_json(200, "OK", $array);
    }
    catch(PDOException $e) 
    {
        send_json(500, "Internal Server Error", array(
            "error_msg" => "Connection failed. The table is missing or the connection data is incorrect.\r\n"
            . $e->getMessage(),
        ));
    }
}

if($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["live"]) && isset($_GET["live_msg_id"]))
{
    if(!is_numeric($_GET["live_msg_id"]))
        send_json(400, "Bad Request", array("error_msg" => "query 'live_msg_id' is not valid number."));
    
    $msg_id = intval($_GET["live_msg_id"]);

    try 
    { 
        $db = new PDO($dbinfo_link, $dbinfo_username, $dbinfo_password);
        //$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SELECT * FROM `{$dbinfo_tablename}` WHERE `msg_id` > :msg_id ORDER BY `msg_id` ASC LIMIT 256";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":msg_id", $msg_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchALL(PDO::FETCH_ASSOC);

        if(!isset($result) || $result === false)
        {
            send_json(404, "Not Found", array("error_msg" => "No Result Found"));
        }

        foreach($result as $value)
        {
            $array[] = array(
                "name" => $value["name"],
                "auth" => $value["auth"],
            );
        }

        send_json(200, "OK", $array);
    }
    catch(PDOException $e) 
    {
        send_json(500, "Internal Server Error", array(
            "error_msg" => "Connection failed. The table is missing or the connection data is incorrect.\r\n"
            . $e->getMessage(),
        ));
    }
}