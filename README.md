# Chat Logging

The plugin records the entire server chat (and all admin messages) in the mysql database for further displaying messages on the site.

***Authors:***
* [R1KO](http://hlmod.ru/members/r1ko.35068/)
* [Webman](http://hlmod.ru/members/webman.43023/)

[Example of the WEB part](http://m4us.ru/chat.php)

***Game Server side installation***
- Download the latest version.
- Fill `chat_logging.smx` at *`addons/sourcemod/plugins/`*
- Register in *`addons/sourcemod/configs/databases.cfg`*
    ```
    "chatlog"
    {
    	"driver"		"mysql"
    	"host"			"db_address"
    	"database"		"database_name"
    	"user"			"Username"
    	"pass"			"password"
    }
    ```

***Settings(CVAR)***
- `sm_chat_log_table "chatlog"` - Database table to save chat log
- `sm_chat_log_triggers "0"` - Logging chat triggers
- `sm_chat_log_say "1"` - General chat logging
- `sm_chat_log_say_team "1"` - Writing to the team chat log
- `sm_chat_log_sm_say "1"` - Logging the sm_say command
- `sm_chat_log_chat "1"` - Logging the sm_chat command
- `sm_chat_log_csay "1"` - Logging the sm_csay command
- `sm_chat_log_tsay "1"` - Logging the sm_tsay command
- `sm_chat_log_msay "1"` - Logging the sm_msay command
- `sm_chat_log_hsay "1"` - Logging the sm_hsay command
- `sm_chat_log_psay "1"` - Logging the sm_psay command

***Web Server side installation***
- Download the latest version.
- Upload files from the * `Web` * folder to the WEB server (ftp)
- Open the file * `chat.php` *, find the following lines:
    ```php
    # Data to connect to the database
    $dbinfo_hostname = "";     // Host
    $dbinfo_username = ""; // Username
    $dbinfo_password = "";      // Password
    $dbinfo_dbtable = "";  // Database name
    ```
- In quotes, enter the corresponding database data - those that you specified in the `databases.cfg` file on your server.
