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

Class MSG
{
    public int$msg_id;
    public int $server_id;
    public string $auth;
    public string $ip;
    public string $name;
    public int $team;
    public int $alive;
    public int $timestamp;
    public string $message;
    public string $type;
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

if($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["live"]))
{
    if(($is_msg_id_set = is_numeric($_GET["live_msg_id"])))
        $msg_id = intval($_GET["live_msg_id"]);

    try 
    { 
        $db = new PDO($dbinfo_link, $dbinfo_username, $dbinfo_password);
        //$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($is_msg_id_set)
        {
            $query = "SELECT * FROM `{$dbinfo_tablename}` WHERE `msg_id` > :msg_id ORDER BY `msg_id` ASC LIMIT 256";
        }
        else
        {
            $query = "SELECT * FROM (SELECT * FROM `{$dbinfo_tablename}` ORDER BY `msg_id` DESC LIMIT 256) as `logs` ORDER BY `msg_id` ASC";
        }

        $stmt = $db->prepare($query);
        if ($is_msg_id_set)
        {
            $stmt->bindParam(":msg_id", $msg_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetchALL(PDO::FETCH_CLASS, "MSG");

        if(!isset($result) || $result === false)
        {
            send_json(404, "Not Found", array("error_msg" => "No Result Found"));
        }

        foreach($result as $value)
        {
            # Team colors (bootstrap css class text-*)
            $ingame = true;
            switch((int)$value->team)
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
            $say_team = (bool)($value->type == "say_team");
            
            # time that message wrote
            $html = "<span class=\"text-info\">[" . date("Y-m-d H:i:s", $value->timestamp) . "]</span> ";
            
            # in spectator/in team - true/false
            $ingame = (bool)($value->team == 0);
            
            # Player is alive / dead
            if ($value->team > 1 && !$value->alive)
                $html .= "<span style=\"color: #ffb000;\">*DEAD*</span> ";
            
            # Prefixes depending on the type of message (basechat)
            if(isset($value["type"]))
            {
                switch ($value->type)
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
                $html .= "<span class=\"text-success\">{$msg_type}</span>";
            }
            
            # Nickname color
            $html .= "<span class=\"text-{$textcolor}\">";
            
            # Team chat - prefix
            if($say_team) 
            {
                switch ($gameinfo)
                {
                    case "CS":
                        switch((int)$value->team)
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
                        switch((int)$value->team)
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
            
                $html .= $team;
            }
            
            # Nickname of the player who wrote the message
            $html .= " {$value->name}:</span> ";
            
            # Message text (if psay - hide)
            $message = ($value->type == "sm_psay") ? "*PRIVATE MESSAGE*" : $value->message;
            $html .= "<span style=\"color: #ffb000;\">{$message}</span>";

            $array[] = array(
                "msg_id" => $value->msg_id,
                "html" => $html,
            );
        }

        send_json(200, "OK", array("last_msg_id" => end($result)->msg_id, "rows" => $array));
    }
    catch(PDOException $e) 
    {
        send_json(500, "Internal Server Error", array(
            "error_msg" => "Connection failed. The table is missing or the connection data is incorrect.\r\n"
            . $e->getMessage(),
        ));
    }
}