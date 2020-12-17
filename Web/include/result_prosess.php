<?php

function result_process(array $row, string $gameinfo = 'CS') : string
{
    # Team colors (bootstrap css class text-*)
    switch((int)$row["team"])
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
            break;
    }
    
    # time that message wrote
    $html = '<span class="text-info">[' . date("Y-m-d H:i:s", $row['timestamp']) . ']</span> ';
    
    # Player is alive / dead
    if ($row['team'] > 1 && !$row['alive'])
        $html .= '<span style="color: #ffb000;">*DEAD*</span> ';
    
    # Prefixes depending on the type of message (basechat)
    if(isset($row['type']))
    {
        switch ($row['type'])
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

        if(isset($msg_type))
        {
            $html .= "<span class=\"text-success\">{$msg_type}</span>";
        }
    }
    
    # Nickname color
    $html .= "<span class=\"text-{$textcolor}\">";
    
    # Team chat - prefix
    if($row['type'] == 'say_team')
    {
        $team = "";
        switch ($gameinfo)
        {
            case "CS":
                switch((int)$row['team'])
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
                switch((int)$row['team'])
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
    $html .= " {$row['name']}:</span> ";
    
    # Message text (if psay - hide)
    $message = ($row['type'] == "sm_psay") ? "*PRIVATE MESSAGE*" : $row['message'];
    $html .= "<span style=\"color: #ffb000;\">{$message}</span>";

    return $html;
}