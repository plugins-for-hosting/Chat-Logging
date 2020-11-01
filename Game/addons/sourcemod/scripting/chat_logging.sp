#pragma semicolon 1

#include <sourcemod>
#include <sdktools>
#include <basecomm>

#pragma newdecls required

Database g_hDatabase;
bool g_bIsLog[10];
ConVar g_hServerID;
ConVar g_hTable;

static const char g_sSay[][] = {"say", "say_team", "sm_say", "sm_chat", "sm_csay", "sm_tsay", "sm_msay", "sm_hsay", "sm_psay"};
char g_sTable[256] = "chatlog";

public Plugin myinfo = 
{
	name = "Chat Logging",
	author = "R1KO",
	version = "2.3+1"
}

public void OnPluginStart()
{
	g_hServerID = CreateConVar("sm_chat_log_server_id", "1", "server ID");
	
	g_hTable = CreateConVar("sm_chat_log_table", "chatlog", "db Table");
	g_hTable.AddChangeHook(OnChatLogTableChange);
	
	g_hTable.GetString(g_sTable, sizeof(g_sTable));

	ConVar hCvar;

	RegConVar(hCvar, "sm_chat_log_triggers", "0", "Whether this logs chat triggers", OnLogTriggersChange, 9);
	RegConVar(hCvar, "sm_chat_log_say", "1", "Whether this logs normal chat", OnLogSayChange, 0);
	RegConVar(hCvar, "sm_chat_log_say_team", "1", "Whether this logs team chat", OnLogSayTeamChange, 1);
	RegConVar(hCvar, "sm_chat_log_sm_say", "1", "Whether this logs sm_say", OnLogSmSayChange, 2);
	RegConVar(hCvar, "sm_chat_log_chat", "1", "Whether this logs sm_chat", OnLogChatChange, 3);
	RegConVar(hCvar, "sm_chat_log_csay", "1", "Whether this logs sm_csay", OnLogCSayChange, 4);
	RegConVar(hCvar, "sm_chat_log_tsay", "1", "Whether this logs sm_tsay", OnLogTSayChange, 5);
	RegConVar(hCvar, "sm_chat_log_msay", "1", "Whether this logs sm_msay", OnLogMSayChange, 6);
	RegConVar(hCvar, "sm_chat_log_hsay", "1", "Whether this logs sm_hsay", OnLogHSayChange, 7);
	RegConVar(hCvar, "sm_chat_log_psay", "1", "Whether this logs sm_psay", OnLogPSayChange, 8);
	
	AutoExecConfig(true, "chat_logging");

	for(int i = 0; i < sizeof(g_sSay); ++i)
	{
		AddCommandListener(Say_Callback, g_sSay[i]);
	}

	if(!SQL_CheckConfig("chatlog"))
	{
		SetFailState("[CHAT LOG] Database failure: Could not find Database conf \"chatlog\"");
		return;
	}
	Database.Connect(SQL_OnConnect, "chatlog");
}

public void OnChatLogTableChange(ConVar hCvar, const char[] oldValue, const char[] newValue)
{
	hCvar.GetString(g_sTable, sizeof(g_sTable));
}

void RegConVar(ConVar &hCvar, const char[] sCvar, const char[] sDefValue, const char[] sDesc, ConVarChanged callback, int index)
{
	hCvar = CreateConVar(sCvar, sDefValue, sDesc, _, true, 0.0, true, 1.0);
	hCvar.AddChangeHook(callback);
	g_bIsLog[index] = hCvar.BoolValue;
}

public void OnLogTriggersChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[9] = GetConVarBool(hCvar); }
public void OnLogSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[0] = GetConVarBool(hCvar); }
public void OnLogSayTeamChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[1] = GetConVarBool(hCvar); }
public void OnLogSmSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[2] = GetConVarBool(hCvar); }
public void OnLogChatChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[3] = GetConVarBool(hCvar); }
public void OnLogCSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[4] = GetConVarBool(hCvar); }
public void OnLogTSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[5] = GetConVarBool(hCvar); }
public void OnLogMSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[6] = GetConVarBool(hCvar); }
public void OnLogHSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[7] = GetConVarBool(hCvar); }
public void OnLogPSayChange(ConVar hCvar, const char[] oldValue, const char[] newValue) { g_bIsLog[8] = GetConVarBool(hCvar); }

public Action Say_Callback(int iClient, const char[] sCommand, int args)
{
	if(!g_hDatabase)
	{
		return Plugin_Continue;
	}

	if(iClient > 0 && IsClientInGame(iClient))
	{
		char sText[192];
		GetCmdArgString(sText, sizeof(sText));
		if((IsChatTrigger() && g_bIsLog[9]) || !IsChatTrigger())
		{
			for(int i = 0; i < sizeof(g_sSay); ++i)
			{
				if(strcmp(sCommand, g_sSay[i]) == 0 && g_bIsLog[i])
				{
					if(i < 2 && BaseComm_IsClientGagged(iClient))
					{
						return Plugin_Handled;
					}

					DBStatement hStmt;
					
					char sError[256], sName[MAX_NAME_LENGTH], sAuth[32], sIP[16], sQuery[256];

					GetClientAuthId(iClient, AuthId_Steam2, sAuth, sizeof(sAuth));
					
					int iServerID = g_hServerID.IntValue;

					GetClientName(iClient, sName, sizeof(sName));
					GetClientIP(iClient, sIP, sizeof(sIP));

					TrimString(sText);
					StripQuotes(sText);

					FormatEx(sQuery, sizeof(sQuery), "INSERT INTO `%s` (`server_id`, `auth`, `ip`, `name`, `team`, `alive`, `timestamp`, `type`, `message`) VALUES (%i, '%s', '%s', ?, %i, %b, %i, '%s', ?);", g_sTable, iServerID, sAuth, sIP, GetClientTeam(iClient), IsPlayerAlive(iClient), GetTime(), sCommand);

					hStmt = SQL_PrepareQuery(g_hDatabase, sQuery, sError, sizeof(sError));
					if (hStmt != null)
					{
						hStmt.BindString(0, sName, false);	
						hStmt.BindString(1, sText, false);

						if (!SQL_Execute(hStmt))
						{
							SQL_GetError(hStmt, sError, sizeof(sError));
							LogError("[CHAT LOG] Fail SQL_Execute: %s", sError);
						}
					}
					else
					{
						LogError("[CHAT LOG] Fail SQL_PrepareQuery: %s", sError);
					}

					delete hStmt;
					
					return Plugin_Continue;
				}
			}
		}
	}
	
	return Plugin_Continue;
}

public void SQL_CheckError(Database hDB, DBResultSet hResults, const char[] sError, any data)
{
	if(sError[0]) LogError("[CHAT LOG] Query Failed: %s", sError);
}

public void SQL_OnConnect(Database hDatabase, const char[] sError, any data)
{
	if (hDatabase == null)
	{
		SetFailState("[CHAT LOG] Failed to connect to database (%s)", sError);
		return;
	}
	else
	{
		g_hDatabase = hDatabase;

		SQL_LockDatabase(g_hDatabase);
		char sQuery[1024];

		FormatEx(sQuery, sizeof(sQuery), "CREATE TABLE IF NOT EXISTS `%s` (\
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
												) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;", g_sTable);
		g_hDatabase.Query(SQL_CheckError, sQuery);
		SQL_UnlockDatabase(g_hDatabase);

		SQL_FastQuery(g_hDatabase, "SET NAMES 'utf8mb4'");
		SQL_FastQuery(g_hDatabase, "SET CHARSET 'utf8mb4'");

		g_hDatabase.SetCharset("utf8mb4");
	}
}