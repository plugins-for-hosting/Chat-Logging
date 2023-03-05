#pragma semicolon 1

#include <sourcemod>
#include <sdktools>
#include <basecomm>

#pragma newdecls required

//#define _DEBUG

#if defined _DEBUG
#define LOGMSG(%1) LogMessage(%1)
#else
#define LOGMSG(%1);
#endif

static const char g_sCreateTable[] = \
"CREATE TABLE IF NOT EXISTS `%s` (\
	`msg_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, \
	`server_id` INT UNSIGNED NOT NULL, \
	`auth` VARCHAR(65) NOT NULL, \
	`ip` VARCHAR(65) NOT NULL, \
	`name` VARCHAR(65) NOT NULL, \
	`team` TINYINT NOT NULL, \
	`alive` TINYINT NOT NULL, \
	`timestamp` INT UNSIGNED NOT NULL, \
	`message` VARCHAR(255) NOT NULL, \
	`type` VARCHAR(16) NOT NULL\
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

static const char g_sQuery[] = \
"INSERT INTO `%s` (`server_id`, `auth`, `ip`, `name`, `team`, `alive`, `timestamp`, `type`, `message`) \
	VALUES (%d, '%s', '%s', '%s', %d, %d, %d, '%s', '%s');";

Database g_hDatabase;

ConVar g_hServerID;
ConVar g_hTable;

int g_iServerID = 1;
char g_sTable[256] = "chatlog";

ConVar g_hLogSay;
ConVar g_hLogSayTeam;
ConVar g_hLogSMSay;
ConVar g_hLogSMChat;
ConVar g_hLogCSay;
ConVar g_hLogTSay;
ConVar g_hLogMSay;
ConVar g_hLogHSay;
ConVar g_hLogPSay;
ConVar g_hLogTriggers;

bool g_bLogSay = true;
bool g_bLogSayTeam = true;
bool g_bLogSMSay = true;
bool g_bLogSMChat = true;
bool g_bLogCSay = true;
bool g_bLogTSay = true;
bool g_bLogMSay = true;
bool g_bLogHSay = true;
bool g_bLogPSay = true;
bool g_bLogTriggers = false;


public Plugin myinfo = 
{
	name = "Chat Logging",
	author = "R1KO, Monera",
	version = "3.0.1"
}

public void OnPluginStart()
{
	// Create ConVars
	g_hServerID = CreateConVar("sm_chat_log_server_id", "1", "Chat Log Plugin server identifier");
	g_hTable = CreateConVar("sm_chat_log_table", "chatlog", "Chat Log Plugin database table");
	g_hLogTriggers = CreateConVar("sm_chat_log_triggers", "0", "Whether chat log plugin logs chat triggers", _, true, 0.0, true, 1.0);

	g_hLogSay = CreateConVar("sm_chat_log_say", "1", "Whether chat log plugin logs normal chat", _, true, 0.0, true, 1.0);
	g_hLogSayTeam = CreateConVar("sm_chat_log_say_team", "1", "Whether chat log plugin logs team chat", _, true, 0.0, true, 1.0);
	g_hLogSMSay = CreateConVar("sm_chat_log_sm_say", "1", "Whether chat log plugin logs sm_say", _, true, 0.0, true, 1.0);
	g_hLogSMChat = CreateConVar("sm_chat_log_sm_chat", "1", "Whether chat log plugin logs sm_chat", _, true, 0.0, true, 1.0);
	g_hLogCSay = CreateConVar("sm_chat_log_sm_csay", "1", "Whether chat log plugin logs sm_csay", _, true, 0.0, true, 1.0);
	g_hLogTSay = CreateConVar("sm_chat_log_sm_tsay", "1", "Whether chat log plugin logs sm_tsay", _, true, 0.0, true, 1.0);
	g_hLogMSay = CreateConVar("sm_chat_log_sm_msay", "1", "Whether chat log plugin logs sm_msay", _, true, 0.0, true, 1.0);
	g_hLogHSay = CreateConVar("sm_chat_log_sm_hsay", "1", "Whether chat log plugin logs sm_hsay", _, true, 0.0, true, 1.0);
	g_hLogPSay = CreateConVar("sm_chat_log_sm_psay", "1", "Whether chat log plugin logs sm_psay", _, true, 0.0, true, 1.0);


	// Add ConVar change hooks
	g_hServerID.AddChangeHook(OnChatLogServerIDChange);
	g_hTable.AddChangeHook(OnChatLogTableChange);
	g_hLogTriggers.AddChangeHook(OnLogTriggersChanged);

	g_hLogSay.AddChangeHook(OnLogSayChanged);
	g_hLogSayTeam.AddChangeHook(OnLogSayTeamChanged);
	g_hLogSMSay.AddChangeHook(OnLogSMSayChanged);
	g_hLogSMChat.AddChangeHook(OnLogSMChatChanged);
	g_hLogCSay.AddChangeHook(OnLogCSayChanged);
	g_hLogTSay.AddChangeHook(OnLogTSayChanged);
	g_hLogMSay.AddChangeHook(OnLogMSayChanged);
	g_hLogHSay.AddChangeHook(OnLogHSayChanged);
	g_hLogPSay.AddChangeHook(OnLogPSayChanged);


	AutoExecConfig(true, "chat_logging");


	// update variable
	g_iServerID = g_hServerID.IntValue;
	g_hTable.GetString(g_sTable, sizeof(g_sTable));
	g_bLogTriggers = g_hLogTriggers.BoolValue;

	g_bLogSay = g_hLogSay.BoolValue;
	g_bLogSayTeam = g_hLogSayTeam.BoolValue;
	g_bLogSMSay = g_hLogSMSay.BoolValue;
	g_bLogSMChat = g_hLogSMChat.BoolValue;
	g_bLogCSay = g_hLogCSay.BoolValue;
	g_bLogTSay = g_hLogTSay.BoolValue;
	g_bLogMSay = g_hLogMSay.BoolValue;
	g_bLogHSay = g_hLogHSay.BoolValue;
	g_bLogPSay = g_hLogPSay.BoolValue;

	// Register command listener
	if(g_bLogSay)
		AddCommandListener(Say_Callback, "say");
	if(g_bLogSayTeam)
		AddCommandListener(Say_Callback, "say_team");
	if(g_bLogSMSay)
		AddCommandListener(SMSay_Callback, "sm_say");
	if(g_bLogSMChat)
		AddCommandListener(SMSay_Callback, "sm_chat");
	if(g_bLogCSay)
		AddCommandListener(SMSay_Callback, "sm_csay");
	if(g_bLogTSay)
		AddCommandListener(SMSay_Callback, "sm_tsay");
	if(g_bLogMSay)
		AddCommandListener(SMSay_Callback, "sm_msay");
	if(g_bLogHSay)
		AddCommandListener(SMSay_Callback, "sm_hsay");
	if(g_bLogPSay)
		AddCommandListener(SMSay_Callback, "sm_psay");
}

public void OnMapStart()
{
	if(!SQL_CheckConfig("chatlog"))
	{
		LogError("[CHAT LOG] Database failure: Could not find Database conf 'chatlog'");
		return;
	}
	Database.Connect(SQL_OnConnect, "chatlog");
}
public void OnMapEnd()
{
	delete g_hDatabase;
}

void OnChatLogServerIDChange(ConVar convar, const char[] oldValue, const char[] newValue)
{
	LOGMSG("[CHAT LOG] g_iServerID = %d", convar.IntValue);
	g_iServerID = convar.IntValue;
}
void OnChatLogTableChange(ConVar convar, const char[] oldValue, const char[] newValue)
{
	LOGMSG("[CHAT LOG] g_sTable = %s", newValue);
	convar.GetString(g_sTable, sizeof(g_sTable));
}
void OnLogTriggersChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	LOGMSG("[CHAT LOG] g_bLogTriggers = %d", convar.BoolValue);
	g_bLogTriggers = convar.BoolValue;
}

void OnLogSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for say");
		RemoveCommandListener(Say_Callback, "say");
	}
	else if(!g_bLogSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for say");
		AddCommandListener(Say_Callback, "say");
	}
	g_bLogSay = convar.BoolValue;
}
void OnLogSayTeamChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogSayTeam && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for say_team");
		RemoveCommandListener(Say_Callback, "say_team");
	}
	else if(!g_bLogSayTeam && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for say_team");
		AddCommandListener(Say_Callback, "say_team");
	}
	g_bLogSayTeam = convar.BoolValue;
}

void OnLogSMSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogSMSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_say");
		RemoveCommandListener(SMSay_Callback, "sm_say");
	}
	else if(!g_bLogSMSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_say");
		AddCommandListener(SMSay_Callback, "sm_say");
	}
	g_bLogSMSay = convar.BoolValue;
}
void OnLogSMChatChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogSMChat && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_chat");
		RemoveCommandListener(SMSay_Callback, "sm_chat");
	}
	else if(!g_bLogSMChat && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_chat");
		AddCommandListener(SMSay_Callback, "sm_chat");
	}
	g_bLogSMChat = convar.BoolValue;
}
void OnLogCSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogCSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_csay");
		RemoveCommandListener(SMSay_Callback, "sm_csay");
	}
	else if(!g_bLogCSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_csay");
		AddCommandListener(SMSay_Callback, "sm_csay");
	}
	g_bLogCSay = convar.BoolValue;
}
void OnLogTSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogTSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_tsay");
		RemoveCommandListener(SMSay_Callback, "sm_tsay");
	}
	else if(!g_bLogTSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_tsay");
		AddCommandListener(SMSay_Callback, "sm_tsay");
	}
	g_bLogTSay = convar.BoolValue;
}
void OnLogMSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogMSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_msay");
		RemoveCommandListener(SMSay_Callback, "sm_msay");
	}
	else if(!g_bLogMSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_msay");
		AddCommandListener(SMSay_Callback, "sm_msay");
	}
	g_bLogMSay = convar.BoolValue;
}
void OnLogHSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogHSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_hsay");
		RemoveCommandListener(SMSay_Callback, "sm_hsay");
	}
	else if(!g_bLogHSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_hsay");
		AddCommandListener(SMSay_Callback, "sm_hsay");
	}
	g_bLogHSay = convar.BoolValue;
}
void OnLogPSayChanged(ConVar convar, const char[] oldValue, const char[] newValue)
{
	if(g_bLogPSay && !convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] removed command listener for sm_psay");
		RemoveCommandListener(SMSay_Callback, "sm_psay");
	}
	else if(!g_bLogPSay && convar.BoolValue)
	{
		LOGMSG("[CHAT LOG] added command listener for sm_psay");
		AddCommandListener(SMSay_Callback, "sm_psay");
	}
	g_bLogPSay = convar.BoolValue;
}


Action Say_Callback(int client, const char[] command, int argc)
{
	if(!g_hDatabase)
	{
		LOGMSG("[CHAT LOG] db handle is null");
		return Plugin_Continue;
	}
	if(client <= 0 || !IsClientInGame(client))
	{
		LOGMSG("[CHAT LOG] not valid client");
		return Plugin_Continue;
	}

	if(!g_bLogTriggers && IsChatTrigger())
	{
		LOGMSG("[CHAT LOG] chat trigger is disabled");
		return Plugin_Continue;
	}
	if(BaseComm_IsClientGagged(client))
	{
		LOGMSG("[CHAT LOG] gagged client");
		return Plugin_Continue;
	}

	char query[1024];

	char msg[256];
	char name[MAX_NAME_LENGTH];
	char auth[32];
	char ip[16];

	GetCmdArgString(msg, sizeof(msg));
	GetClientName(client, name, sizeof(name));
	GetClientAuthId(client, AuthId_Steam2, auth, sizeof(auth));
	GetClientIP(client, ip, sizeof(ip));

	int team = GetClientTeam(client);
	bool isAlive = IsPlayerAlive(client);
	int timestamp = GetTime();

	TrimString(msg);
	StripQuotes(msg);

	SQL_FormatQuery(g_hDatabase, query, sizeof(query), g_sQuery, g_sTable, g_iServerID, auth, ip, name, team, isAlive, timestamp, command, msg);

	LOGMSG("[CHAT LOG] query: %s", query);

	g_hDatabase.Query(SQL_OnQuery, query, 0, DBPrio_Low);

	return Plugin_Continue;
}

Action SMSay_Callback(int client, const char[] command, int argc)
{
	if(!g_hDatabase)
	{
		LOGMSG("[CHAT LOG] db handle is null");
		return Plugin_Continue;
	}
	if(client <= 0 || !IsClientInGame(client))
	{
		LOGMSG("[CHAT LOG] not valid client");
		return Plugin_Continue;
	}

	char query[1024];

	char msg[256];
	char name[MAX_NAME_LENGTH];
	char auth[32];
	char ip[16];

	GetCmdArgString(msg, sizeof(msg));
	GetClientName(client, name, sizeof(name));
	GetClientAuthId(client, AuthId_Steam2, auth, sizeof(auth));
	GetClientIP(client, ip, sizeof(ip));

	int team = GetClientTeam(client);
	bool isAlive = IsPlayerAlive(client);
	int timestamp = GetTime();

	TrimString(msg);
	StripQuotes(msg);

	SQL_FormatQuery(g_hDatabase, query, sizeof(query), g_sQuery, g_sTable, g_iServerID, auth, ip, name, team, isAlive, timestamp, command, msg);

	LOGMSG("[CHAT LOG] query: %s", query);

	g_hDatabase.Query(SQL_OnQuery, query, 0, DBPrio_Low);

	return Plugin_Continue;
}

void SQL_OnQuery(Database db, DBResultSet results, const char[] error, any data)
{
	if(db == null || results == null || error[0] != '\0')
	{
		LogError("[CHAT LOG] Query Failed: %s", error);
		return;
	}
}

void SQL_OnConnect(Database db, const char[] error, any data)
{
	if(db == null)
	{
		SetFailState("[CHAT LOG] Failed to connect to database: %s", error);
		return;
	}

	char query[1024];
	FormatEx(query, sizeof(query), g_sCreateTable, g_sTable);
	LOGMSG("[CHAT LOG] query: %s", query);
	if(!SQL_FastQuery(db, query))
	{
		char err[256];
		SQL_GetError(db, err, sizeof(err));
		LogError("[CHAT LOG] Query Failed: %s", err);
	}	

	SQL_FastQuery(db, "SET NAMES 'utf8mb4'");
	SQL_FastQuery(db, "SET CHARSET 'utf8mb4'");

	db.SetCharset("utf8mb4");

	g_hDatabase = db;
}
