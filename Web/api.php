<?php

require_once("./dbinfo.php");

if($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["msg_id"]))
{
    if(is_numeric($_GET["msg_id"]))
        exit("msg_id is not valid integer.");
    
    $msg_id = intval($_GET["msg_id"]);

    try 
    { 
        $db = new PDO($dbinfo_link, $dbinfo_username, $dbinfo_password);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET NAMES 'utf8'");

        $query = "SELECT * FROM ? WHERE `msg_id` = ?";

        $stmt = $db->prepare($query);
        $stmt->execute(array($dbinfo_tablename, $msg_id));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        print_r($result);
    
    }
    catch(PDOException $e) 
    {
        echo("Connection failed. The table is missing or the connection data is incorrect.\r\n");
        echo($e->getMessage());
    }
}