<?php
// Copyright (C) 2010 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

require_once('../approot.inc.php');
require_once(APPROOT.'application/application.inc.php');
require_once(APPROOT.'application/webpage.class.inc.php');
require_once(APPROOT.'application/csvpage.class.inc.php');
require_once(APPROOT.'application/clipage.class.inc.php');

require_once(APPROOT.'application/startup.inc.php');

class DBBackup extends MetaModel
{
	static protected function DBEnumViews()
	{
		// This API do not rely on our capability to query the DB and retrieve
		// the list of existing tables
		// Rather, it uses the list of expected views, corresponding to the data model
		$aViews = array();
		foreach (self::GetClasses() as $sClass)
		{
			$aViews[$sClass] = self::DBGetView($sClass);
		}
		return $aViews;
	} 

	static function Escape($sParameter)
	{
		$sResult = $sParameter;
		if (strpos($sParameter, ' ') !== false)
		{
			$sResult = '"'.$sParameter.'"';
		}
		return $sResult;
	}
	
	static public function DoBackup($oP, $sBackupFileName)
	{
		$sHost = self::Escape(utils::GetConfig()->GetDBHost());
		$sUser = self::Escape(utils::GetConfig()->GetDBUser());
		$sPwd = self::Escape(utils::GetConfig()->GetDBPwd());
		$sDBName = self::Escape(utils::GetConfig()->GetDBName());
		$sDBSubName = utils::GetConfig()->GetDBSubName();
		$sTables = '';
		if ($sDBSubName != '')
		{
			// This instance of iTop uses a prefix for the tables, so there may be other tables in the database
			// Let's explicitely list all the tables and views to dump
			foreach(self::DBEnumTables() as $s)
			{
				$aTables[] = self::Escape($s);
			}
			foreach(self::DBEnumViews() as $s)
			{
				$aTables[] = self::Escape($s);
			}
			$sTables = implode(' ', $aTables);
		}
		
		// Store the results in a temporary file
		$sTmpFileName = self::Escape($sBackupFileName);
		$sCommand = "mysqldump --opt --host=$sHost --user=$sUser --password=$sPwd  --result-file=$sTmpFileName $sDBName $sTables";

		// Now run the command for real
		$oP->p("Executing command: $sCommand");
		$aOutput = array();
		$iRetCode = 0;
		@exec($sCommand, $aOutput, $iRetCode);
		foreach($aOutput as $sLine)
		{
			$oP->p($sLine);
		}
	}
}

/**
 * Checks if a parameter (possibly empty) was specified when calling this page
 */
function CheckParam($sParamName)
{
	global $argv;
	
	if (isset($_REQUEST[$sParamName])) return true; // HTTP parameter either GET or POST
	if (!is_array($argv)) return false;
	foreach($argv as $sArg)
	{
		if ($sArg == '--'.$sParamName) return true; // Empty command line parameter, long unix style
		if ($sArg == $sParamName) return true; // Empty command line parameter, Windows style
		if ($sArg == '-'.$sParamName) return true; // Empty command line parameter, short unix style
		if (preg_match('/^--'.$sParamName.'=(.*)$/', $sArg, $aMatches)) return true; // Command parameter with a value
	}
	return false;
}

function Usage($oP)
{
	$oP->p('Perform a backup of the iTop database by running mysqldump');
	$oP->p('Parameters:');
	$oP->p('backup_file [optional]: name of the file to store the backup into. If ommitted a temporary file name will be used.');
}
/////////////////////////////////
// Main program

if (utils::IsModeCLI())
{
	$oP = new CLIPage("iTop - Database Backup");
//	$sAuthUser = ReadMandatoryParam($oP, 'auth_user');
//	$sAuthPwd = ReadMandatoryParam($oP, 'auth_pwd');
//	if (UserRights::CheckCredentials($sAuthUser, $sAuthPwd))
//	{
//		UserRights::Login($sAuthUser); // Login & set the user's language
//	}
//	else
//	{
//		$oP->p("Access restricted or wrong credentials ('$sAuthUser')");
//		exit;
//	}
}
else
{
	require_once('../application/loginwebpage.class.inc.php');
	LoginWebPage::DoLogin(); // Check user rights and prompt if needed

	$oP = new WebPage("iTop - Database Backup");
}

if (CheckParam('?') || CheckParam('h') || CheckParam('help'))
{
	Usage($oP);
	$oP->output();
	exit;
}

$sDefaultBackupFileName = tempnam(sys_get_temp_dir(), 'itopdump-');
$sBackupFile =  utils::ReadParam('backup_file', $sDefaultBackupFileName, true);
DBBackup::DoBackup($oP, $sBackupFile);
$oP->output();
?>
