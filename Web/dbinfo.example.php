<?php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']))
{
    exit("You're not allowed to be here.");
}

# Information for connecting to database
$dbinfo_hostname = "";      // Host
$dbinfo_port = "";          // Port
$dbinfo_username = "";      // Username
$dbinfo_password = "";      // Password
$dbinfo_charset = "utf8";   // Charset
$dbinfo_dbname = "";        // Database Name
$dbinfo_tablename = "chatlog"; // Table name (cvar sm_chat_log_table)

$dbinfo_link = "mysql:host={$dbinfo_hostname};port={$dbinfo_port};dbname={$dbinfo_dbname};charset={$dbinfo_charset}";