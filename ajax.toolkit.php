<?php
/**
 * Copyright (C) 2013-2019 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\CollapsibleSection\CollapsibleSectionUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\Component\Input\Select\SelectOptionUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\SelectUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\TextArea;
use Combodo\iTop\Application\UI\Base\Component\Panel\PanelUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;

define('TOOLKITENV', 'toolkit');


function RebuildToolkitEnvironment()
{
	$oConfig = new Config(APPCONF.'production'.'/'.ITOP_CONFIG_FILE);
	$oToolkitConfig = clone($oConfig);
	$oToolkitConfig->ChangeModulesPath('production', TOOLKITENV);

	if (file_exists(APPROOT.'data/production.delta.xml'))
	{
		copy(APPROOT.'data/production.delta.xml', APPROOT.'data/toolkit.delta.xml');
	}
	if (is_dir(APPROOT.'data/toolkit-modules'))
	{
		// Cleanup before copying
		SetupUtils::rrmdir(APPROOT.'data/toolkit-modules');
	}
	if (is_dir(APPROOT.'data/production-modules'))
	{
		SetupUtils::copydir(APPROOT.'data/production-modules', APPROOT.'data/toolkit-modules');
	}

	$oEnvironment = new RunTimeEnvironment(TOOLKITENV);
	$oEnvironment->WriteConfigFileSafe($oToolkitConfig);
	$oEnvironment->CompileFrom('production');
}


function MakeDictEntry($sKey, $sValueFromOldSystem, $sDefaultValue, &$bNotInDico)
{
	$sValue = Dict::S($sKey, 'x-no-nothing');
	if ($sValue == 'x-no-nothing')
	{
		$bNotInDico = true;
		$sValue = $sValueFromOldSystem;
		if (strlen($sValue) == 0)
		{
			$sValue = $sDefaultValue;
		}
	}
	return "	'$sKey' => '".str_replace("'", "\\'", $sValue)."',\n";
}

function MakeDictionaryTemplate($sModules = '', $sLanguage = 'EN US')
{
	$sRes = '';
	Dict::SetDefaultLanguage($sLanguage);
	$aAvailableLanguages = Dict::GetLanguages();
	$sDesc = $aAvailableLanguages[$sLanguage]['description'];
	$sLocalizedDesc = $aAvailableLanguages[$sLanguage]['localized_description'];

	$sRes .= "// Dictionary conventions\n";
	$sRes .= htmlentities("// Class:<class_name>\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>+\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>+\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>/Value:<value>\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>/Value:<value>+\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>/Stimulus:<stimulus_code>\n", ENT_QUOTES, 'UTF-8');
	$sRes .= htmlentities("// Class:<class_name>/Stimulus:<stimulus_code>+\n", ENT_QUOTES, 'UTF-8');
	$sRes .= "\n";

	// Note: I did not use EnumCategories(), because a given class maybe found in several categories
	// Need to invent the "module", to characterize the origins of a class
	if (strlen($sModules) == 0)
	{
		$aModules = array('bizmodel', 'core/cmdb', 'gui' , 'application', 'addon/userrights', 'monitoring');
	}
	else
	{
		$aModules = explode(', ', $sModules);
	}

	$sRes .= "//////////////////////////////////////////////////////////////////////\n";
	$sRes .= "// Note: The classes have been grouped by categories: ".implode(', ', $aModules)."\n";
	$sRes .= "//////////////////////////////////////////////////////////////////////\n";

	foreach ($aModules as $sCategory)
	{
		$sRes .= "//////////////////////////////////////////////////////////////////////\n";
		$sRes .= "// Classes in '$sCategory'\n";
		$sRes .= "//////////////////////////////////////////////////////////////////////\n";
		$sRes .= "//\n";
		$sRes .= "\n";
		foreach (MetaModel::GetClasses($sCategory) as $sClass)
		{
			if (!MetaModel::HasTable($sClass)) continue;

			$bNotInDico = false;
			$bNotImportant = true;

			$sClassRes = "//\n";
			$sClassRes .= "// Class: $sClass\n";
			$sClassRes .= "//\n";
			$sClassRes .= "\n";
			$sClassRes .= "Dict::Add('$sLanguage', '$sDesc', '$sLocalizedDesc', array(\n";
			$sClassRes .= MakeDictEntry("Class:$sClass", MetaModel::GetName_Obsolete($sClass), $sClass, $bNotInDico);
			$sClassRes .= MakeDictEntry("Class:$sClass+", MetaModel::GetClassDescription_Obsolete($sClass), '', $bNotImportant);
			foreach(MetaModel::ListAttributeDefs($sClass) as $sAttCode => $oAttDef)
			{
				if ($sAttCode == 'friendlyname') continue;

				// Skip this attribute if not originaly defined in this class
				if (MetaModel::GetAttributeOrigin($sClass, $sAttCode) != $sClass) continue;

				$sClassRes .= MakeDictEntry("Class:$sClass/Attribute:$sAttCode", $oAttDef->GetLabel_Obsolete(), $sAttCode, $bNotInDico);
				$sClassRes .= MakeDictEntry("Class:$sClass/Attribute:$sAttCode+", $oAttDef->GetDescription_Obsolete(), '', $bNotImportant);
				if ($oAttDef instanceof AttributeEnum)
				{
					if (MetaModel::GetStateAttributeCode($sClass) == $sAttCode)
					{
						foreach (MetaModel::EnumStates($sClass) as $sStateCode => $aStateData)
						{
							if (array_key_exists('label', $aStateData))
							{
								$sValue = $aStateData['label'];
							}
							else
							{
								$sValue = MetaModel::GetStateLabel($sClass, $sStateCode);
							}
							if (array_key_exists('description', $aStateData))
							{
								$sValuePlus = $aStateData['description'];
							}
							else
							{
								$sValuePlus = MetaModel::GetStateDescription($sClass, $sStateCode);
							}
							$sClassRes .= MakeDictEntry("Class:$sClass/Attribute:$sAttCode/Value:$sStateCode", $sValue, '', $bNotInDico);
							$sClassRes .= MakeDictEntry("Class:$sClass/Attribute:$sAttCode/Value:$sStateCode+", $sValuePlus, '', $bNotImportant);
						}
					}
					else
					{
						foreach ($oAttDef->GetAllowedValues() as $sKey => $value)
						{
							$sClassRes .= MakeDictEntry("Class:$sClass/Attribute:$sAttCode/Value:$sKey", $value, '', $bNotInDico);
							$sClassRes .= MakeDictEntry("Class:$sClass/Attribute:$sAttCode/Value:$sKey+", $value, '', $bNotImportant);
						}
					}
				}
			}
			foreach(MetaModel::EnumStimuli($sClass) as $sStimulusCode => $oStimulus)
			{
				$sClassRes .= MakeDictEntry("Class:$sClass/Stimulus:$sStimulusCode", $oStimulus->GetLabel_Obsolete(), '', $bNotInDico);
				$sClassRes .= MakeDictEntry("Class:$sClass/Stimulus:$sStimulusCode+", $oStimulus->GetDescription_Obsolete(), '', $bNotImportant);
			}

			$sClassRes .= "));\n";
			$sClassRes .= "\n";

			$sRes .= $sClassRes;
		}
	}
	return $sRes;
}

/**
 * Build a zip file containing dictionary files for the $sLangName language.
 * Note: The zip is made from english translations without checking if there are any existing dictionary entries for $sLangCode.
 *
 * @param string $sLangCode
 * @param string $sLangName
 * @param string $sLangLocName
 *
 * @throws \Exception
 */
function BuildNewLanguagepackage($sLangCode, $sLangName, $sLangLocName)
{
	// Prepare lang prefix
	$aLangCodeParts = explode(' ', $sLangCode);
	$sLangPrefix = strtolower($aLangCodeParts[0]);
	if($aLangCodeParts[0] !== $aLangCodeParts[1])
	{
		$sLangPrefix .= '_'.strtolower($aLangCodeParts[1]);
	}

	// Working directory
	$sWorkingFolder = APPROOT.'data/tmp-dict-folder/';
	SetupUtils::builddir($sWorkingFolder);

	// Prepare zip file
	$sZipPath = APPROOT.'data/';
	$sZipTime = date('Y.m.d.H.i.s');
	$sZipName = 'iTop-'.ITOP_VERSION.'-'.$sLangPrefix.'-translation-files-'.$sZipTime.'.zip';
	$sZipFilePath = $sZipPath.$sZipName;

	if(!class_exists('ZipArchive'))
	{
		throw new Exception('ZipArchive class not found!');
	}

	$oZip = new ZipArchive();
	$oZip->open($sZipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	// Remove current files from working dir if any
	IssueLog::Info($sWorkingFolder.$sLangPrefix.'.dict*.php');
	foreach(glob($sWorkingFolder.$sLangPrefix.'.dict*.php') as $sExistingFile)
	{
		@unlink($sExistingFile);
	}

	// Process application dictionary files
	foreach(glob(APPROOT.'dictionaries/en.*.php') as $sSourceFile)
	{
		$sDestFile = $sWorkingFolder.$sLangPrefix.substr($sSourceFile, strrpos($sSourceFile, '/', -1) + 3);
		MakeDictionaryFile($sLangCode, $sLangName, $sLangLocName, $sSourceFile, $sDestFile);
	}

	// Process extensions dictionary files
	foreach(glob(APPROOT.'datamodels/*/*/en.*.php') as $sSourceFile)
	{
		$sDestFile = $sWorkingFolder.$sLangPrefix.substr($sSourceFile, strrpos($sSourceFile, '/', -1) + 3);
		MakeDictionaryFile($sLangCode, $sLangName, $sLangLocName, $sSourceFile, $sDestFile);
	}

	// Making zip file
	foreach(glob($sWorkingFolder.$sLangPrefix.'.dict*.php') as $sProcessedFile)
	{
		$sProcessedFileName = substr($sProcessedFile, strrpos($sProcessedFile, '/', -1) +1);
		$oZip->addFile($sProcessedFile, $sProcessedFileName);
	}
	$oZip->close();

	// Remove working dir
	SetupUtils::rrmdir($sWorkingFolder);

	// Display download link
	return "Translation files package is available under /data/$sZipName";
}

/**
 * Create $sDestFile by duplicating $sSourceFile, replacing the language meta data with $sLangCode / $sLangName / $sLangLocName and adding "~~" at the end of all translations and
 *
 * @param string $sLangCode
 * @param string $sLangName
 * @param string $sLangLocName
 * @param string $sSourceFile
 * @param string $sDestFile
 *
 * @return bool
 */
function MakeDictionaryFile($sLangCode, $sLangName, $sLangLocName, $sSourceFile, $sDestFile)
{
	$sDestFileContent = "";

	try
	{
		$oSourceHandle = fopen($sSourceFile, 'r+');
		if($oSourceHandle)
		{
			while( ($sLine = fgets($oSourceHandle)) !== false )
			{
				$sNewLine = null;

				// Look only for entries
				$aTmpMatches = array();
				if(preg_match("/Dict::Add\('(.*)',\ ?'(.*)',\ ?'(.*)',\ ?array\(/", $sLine, $aTmpMatches) === 1)
				{
					$sNewLine = "Dict::Add('{$sLangCode}', '{$sLangName}', '{$sLangLocName}', array(\n";
				}
				elseif(preg_match("/'(.*)'\ ?=>\ ?'(.*)',/", $sLine, $aTmpMatches) === 1)
				{
					$sNewLine = "\t'".$aTmpMatches[1]."' => '".$aTmpMatches[2]."~~',\n";
				}
				else
				{
					$sNewLine = $sLine;
				}

				$sDestFileContent .= $sNewLine;
			}
		}
	}
	catch(Exception $e)
	{
		echo "<p>Could not make dictionary file for ".$sSourceFile.", it will not be included in the zip file (Cause: ".$e->getMessage().")</p>";
		return false;
	}

	file_put_contents($sDestFile, $sDestFileContent);
	return true;
}

function CheckDBSchema()
{
	$aAnalysis = array();
	list($aErrors, $aSugFix, $aCondensedQueries) = MetaModel::DBCheckFormat();
	foreach ($aErrors as $sClass => $aTarget)
	{
		foreach ($aTarget as $sAttCode => $aIssues)
		{
			foreach ($aIssues as $sIssue)
			{
				$aAnalysis[$sClass]['table_issues'][$sAttCode][] = $sIssue;
			}
		}
	}
	foreach ($aSugFix as $sClass => $aTarget)
	{
		foreach ($aTarget as $sAttCode => $aQueries)
		{
			foreach ($aQueries as $sQuery)
			{
				if (!empty($sQuery))
				{
					$aAnalysis[$sClass]['table_fixes'][$sAttCode][] = $sQuery;
				}
			}
		}
	}
	$sSQL = '';
	foreach ($aCondensedQueries as $sCondensedQuery)
	{
		$sSQL .= $sCondensedQuery;
		if (substr_compare($sCondensedQuery, ';', -1) !== 0)
		{
			$sSQL .= ";\n";
		}
		else
		{
			$sSQL .= "\n";
		}
	}
	$aAnalysis['*CondensedQueries']['sql'] = $sSQL;

	if (defined(ITOP_VERSION) && version_compare(ITOP_VERSION, '2.7.0') < 0)
	{
		list($aErrors, $aSugFix) = MetaModel::DBCheckViews();
		foreach ($aErrors as $sClass => $aTarget)
		{
			foreach ($aTarget as $sAttCode => $aIssues)
			{
				foreach ($aIssues as $sIssue)
				{
					$aAnalysis[$sClass]['view_issues'][$sAttCode][] = $sIssue;
				}
			}
		}
		foreach ($aSugFix as $sClass => $aTarget)
		{
			foreach ($aTarget as $sAttCode => $aQueries)
			{
				foreach ($aQueries as $sQuery)
				{
					if (!empty($sQuery))
					{
						$aAnalysis[$sClass]['view_fixes'][$sAttCode][] = $sQuery;
					}
				}
			}
		}
	}
	return $aAnalysis;
}

function InitDataModel($sConfigFileName, $bModelOnly = true)
{


	MetaModel::ResetCache();
	MetaModel::Startup($sConfigFileName, $bModelOnly, false /* allow cache */, false /* $bTraceSourceFiles */, TOOLKITENV);
}


/****************************************************************************
 *
 * Main Program
 *
 ****************************************************************************/
$bBypassMaintenance = true; // Reset maintenance mode in case of problem

if (file_exists('../approot.inc.php'))
{
	include('../approot.inc.php');
}
else // iTop 1.0 & 1.0.1
{
	define('APPROOT', '../');
}

if (class_exists('\Combodo\iTop\Application\Helper\Session')) {
	\Combodo\iTop\Application\Helper\Session::Start();
} else {
	session_start();
}


try
{
	require_once(APPROOT."setup/runtimeenv.class.inc.php");
	require_once(APPROOT.'/application/utils.inc.php');
	define('ITOP_TOOLKIT_CONFIG_FILE', APPCONF.TOOLKITENV.'/'.ITOP_CONFIG_FILE);

	// Cleanup maintenance mode
	if (method_exists(SetupUtils::class, 'IsInMaintenanceMode')) {
		if (SetupUtils::IsInMaintenanceMode()) {
			SetupUtils::ExitMaintenanceMode();
		}
	}

	$bRebuildToolkitEnv = (utils::ReadParam('rebuild_toolkit_env', '') == 'true');
	if ($bRebuildToolkitEnv)
	{
		RebuildToolkitEnvironment();
	}

	$sOperation = utils::ReadParam('operation', '');


	if (version_compare(ITOP_DESIGN_LATEST_VERSION , '3.0') < 0) {
		//////////////////////////////////////////////////////////////////////////////////////////////////////////
		/// LEGACY
		/// /////////////////////////////////////////////////////////////////////////////////////////////////////
		switch($sOperation)
		{
			case 'check_model':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, false);
				MetaModel::CheckDefinitions();
				break;

			case 'check_dictionary':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, true);
				$sDefaultCode = utils::ReadParam('lang', 'EN US');
				$sModules = utils::ReadParam('modules', 'bizmodel');
				$aAvailableLanguages = Dict::GetLanguages();
				echo "<select id=\"language\" name=\"language\">\n";
				foreach($aAvailableLanguages as $sLangCode => $aInfo)
				{
					$sSelected = ($sLangCode == $sDefaultCode ) ? 'selected ' : '';
					echo "<option value=\"{$sLangCode}\" $sSelected>{$aInfo['description']} ({$aInfo['localized_description']})</option>\n";
				}
				$aModules = array(
					'bizmodel',
					'core/cmdb',
					'gui',
					'application',
					'addon/userrights',
					'monitoring',
				);
				echo "</select>\n";
				echo "<select id=\"modules\" name=\"modules\">\n";
				foreach ($aModules as $sProposedModules)
				{
					if ($sProposedModules == $sModules)
					{
						echo "<option value=\"$sProposedModules\" SELECTED>$sProposedModules</option>\n";
					}
					else
					{
						echo "<option value=\"$sProposedModules\">$sProposedModules</option>\n";
					}
				}
				echo "</select>\n";
				echo "<input type=\"button\" value=\"⟳ Refresh\" onclick=\"CheckDictionary(true);\"/>\n";
				echo "<textarea style=\"width:100%;height:400px;\">";
				echo MakeDictionaryTemplate($sModules, $sDefaultCode);
				echo "</textarea>\n";
				break;

			case 'prepare_new_dictionary':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, true);
				$sLangCode = trim(utils::ReadParam('lang_code', '', false, 'raw'));
				$sLangName = trim(utils::ReadParam('lang_name', '', false, 'raw'));
				$sLangLocName = trim(utils::ReadParam('lang_loc_name', '', false, 'raw'));
				if(empty($sLangCode) || empty($sLangName) || empty($sLangLocName))
				{
					echo "Please fill all fields.";

				}
				else
				{
					$sMessage = BuildNewLanguagepackage($sLangCode, $sLangName, $sLangLocName);
					echo"<div>$sMessage</div>";
				}
				break;

			case 'check_db_schema':
				$sCurrEnv = (isset($_SESSION['itop_env']) && !is_null($_SESSION['itop_env'])) ? $_SESSION['itop_env'] : null;
				$_SESSION['itop_env'] = TOOLKITENV;
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, false);

				$aAnalysis = CheckDBSchema();
				$_SESSION['itop_env'] = $sCurrEnv;

				$aSQLFixesTables = array();
				$aSQLFixesAll = array();
				foreach($aAnalysis as $sClass => $aData)
				{
					if (isset($aData['table_issues']))
					{
						echo "<h2>".MetaModel::GetClassIcon($sClass)."&nbsp;Class $sClass</h2>\n";
						echo "<ul>\n";
						foreach($aData['table_issues'] as $sAttCode => $aIssues)
						{
							foreach($aIssues as $sText)
							{
								echo "<li>$sText</li>";
							}
						}
						echo "</ul>\n";
					}
					if (isset($aData['table_fixes']))
					{
						echo "<p class=\"fixes\">\n";
						foreach($aData['table_fixes'] as $sAttCode => $aIssues)
						{
							foreach($aIssues as $sSQL)
							{
								$sSQLEscaped = htmlentities($sSQL, ENT_QUOTES, 'UTF-8');
								echo "<p class=\"fix-sql\">$sSQLEscaped</p>\n";
							}
							$aSQLFixesTables[] = implode(";\n", $aIssues);
							$aSQLFixesAll[] = implode(";\n", $aIssues);
						}
						echo "</p>\n";
					}
					if (isset($aData['view_fixes']))
					{
						foreach($aData['view_fixes'] as $sAttCode => $aIssues)
						{
							$aSQLFixesAll[] = implode(";\n", $aIssues);
						}
					}
				}
				if (count($aSQLFixesTables) == 0)
				{
					echo "<p>Ok, the database format is compliant with the data model. (Note: the views have not been checked)</p>\n";
				}
				echo "<p>&nbsp;</p>\n";
				if (function_exists('symlink'))
				{
					echo "<p><input type=\"checkbox\" id=\"symlink\" value=\"1\"><label for=\"symlink\">&nbsp;Create symbolic links instead of creating a copy in env-production (useful for debugging extensions)</label></p>\n";
				}
				echo "<input type=\"button\" value=\"⟳ Refresh \" onclick=\"CheckDBSchema(true);\"/>\n";
				if (count($aSQLFixesTables) > 0)
				{
					echo "<input type=\"submit\" onclick=\"doApply(true);\"title=\"Compile + Update DB tables and views\" value=\"📀 Update iTop code and Database! \"/>&nbsp;<span id=\"apply_sql_indicator\"></span>\n";
				}
				$sSourceDir = MetaModel::GetConfig()->Get('source_dir');
				$sSourceDirHtml = htmlentities($sSourceDir, ENT_QUOTES, 'UTF-8');
				echo "<input type=\"submit\" onclick=\"doApply(false);\"title=\"Compile from $sSourceDirHtml to env-production\" value=\"📄 Update iTop code \"/>&nbsp;<span id=\"apply_sql_indicator\"></span>\n";
				echo "<div id=\"content_apply_sql\"></div>\n";
				echo "<p>&nbsp;</p>\n";
				echo "<hr>\n";
				echo "<h2>SQL commands to copy/paste:</h2>\n";
				$sSQLFixAll = $aAnalysis['*CondensedQueries']['sql'];
				echo "<textarea style=\"width:100%;height:200px;font-family:Courrier, Courrier New, Nimbus Mono L,serif\">$sSQLFixAll</textarea>";
				break;

			case 'check_hk':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, false);
				echo "<pre>\n";
				$bUpdateNeeded = MetaModel::CheckHKeys(true /*bDiagnostics*/, true /*bVerbose*/, false /*bForceComputation*/);
				echo "</pre>\n";
				if ($bUpdateNeeded)
				{
					echo "<p><button onClick=\"BuildHK(false);\">Compute HKeys</button>&nbsp;&nbsp;<button onClick=\"CheckHK(true);\"> Refresh </button></p>\n";
				}
				else
				{
					echo "<p><button onClick=\"BuildHK(true);\">Rebuild HKeys Anyway</button>&nbsp;&nbsp;<button onClick=\"CheckHK(true);\"> Refresh </button></p>\n";
				}
				break;

			case 'build_hk':
				$bForce = utils::ReadParam('force', 0);
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				echo "<pre>\n";
				$bUpdateNeeded = MetaModel::CheckHKeys(false, true /*bVerbose*/, $bForce /*bForceComputation*/);
				echo "</pre>\n";
				echo "<p><button onClick=\"CheckHK(true);\"> Refresh </button></p>\n";
				break;


			case 'check_datasources':
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				echo "<pre>\n";
				$bUpdateNeeded = MetaModel::CheckDataSources(true /* bDiagnostics */, true /*bVerbose*/);
				echo "</pre>\n";
				if ($bUpdateNeeded)
				{
					echo "<p><button onClick=\"FixDataSources();\">Fix Data Sources</button>&nbsp;&nbsp;<button onClick=\"CheckDataSources(true);\"> Refresh </button></p>\n";
				}
				else
				{
					echo "<p><button onClick=\"CheckDataSources(true);\"> Refresh </button></p>\n";
				}
				break;

			case 'fix_datasources':
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				$oChange = MetaModel::NewObject("CMDBChange");
				$oChange->Set("date", time());
				$oChange->Set("userinfo", 'Change made via the toolkit');
				$oChange->DBInsert();
				echo "<pre>\n";
				$bUpdateNeeded = MetaModel::CheckDataSources(false /* bDiagnostics */, true /*bVerbose*/);
				echo "</pre>\n";
				echo "<p><button onClick=\"CheckDataSources(true);\"> Refresh </button></p>\n";
				break;

			case 'update_code':
				// Compile the code into the production environment
				echo "<p>Compiling...</p>";
				$bUseSymlinks = utils::ReadParam('symlink', false);
				$oEnvironment = new RunTimeEnvironment('production');
				$oEnvironment->CompileFrom('production', $bUseSymlinks);
				utils::InitTimeZone();
				$datetime = date("Y-m-d H:i:s");
				echo "<p>Done! ($datetime)</p>";
				break;

		case 'update_code_db':

				// Compile the code into the production environment
				echo "<p>Compiling...</p>";
				$bUseSymlinks = utils::ReadParam('symlink', false);
				$oEnvironment = new RunTimeEnvironment('production');
				$oEnvironment->CompileFrom('production', $bUseSymlinks);

				echo "<p>Updating the DB format (tables and views)...</p>";
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				$aAnalysis = CheckDBSchema();

				try
				{
					foreach($aAnalysis as $sClass => $aData)
					{
						if (isset($aData['table_fixes']))
						{
							foreach($aData['table_fixes'] as $sAttCode => $aIssues)
							{
								foreach($aIssues as $sSQL)
								{
									CMDBSource::Query($sSQL);
									echo "<p class=\"sql_ok\">$sSQL;</p>\n";
								}
							}
						}
					}

					foreach($aAnalysis as $sClass => $aData)
					{
						if (isset($aData['view_fixes']))
						{
							foreach($aData['view_fixes'] as $sAttCode => $aIssues)
							{
								foreach($aIssues as $sSQL)
								{
									CMDBSource::Query($sSQL);
									echo "<p class=\"sql_ok\">$sSQL;</p>\n";
								}
							}
						}
					}
					echo "<p>Done.</p>";
				}
				catch(MySQLException $e)
				{
					echo "<p class=\"sql_error\">$sSQL;</p>\n";
					echo "<p class=\"sql_error\">".$e->getHtmlDesc()."</p>\n";
					echo "<p class=\"sql_error\">Operation aborted.</p>\n";
				}
				break;

			default:
				echo"The operation $sOperation is not supported";
		}

		//////////////////////////////////////////////////////////////////////////////////////////////////////////
		/// END LEGACY
		/// /////////////////////////////////////////////////////////////////////////////////////////////////////
	} else {

		$oPage = new AjaxPage('');
		$oPage->no_cache();
		switch($sOperation)
		{
			case 'check_model':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, false);
				MetaModel::CheckDefinitions();
				break;

			case 'check_dictionary':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, true);
				$sDefaultCode = utils::ReadParam('lang', 'EN US');
				$sModules = utils::ReadParam('modules', 'bizmodel');
				$aAvailableLanguages = Dict::GetLanguages();

				$oBlock = UIContentBlockUIBlockFactory::MakeStandard();
				$oPage->AddSubBlock($oBlock);

				$oTextArea = new TextArea('', MakeDictionaryTemplate($sModules, $sDefaultCode),null,100,10);
				$oTextArea->AddCSSClass('ibo-input-text--export');
				$oTextArea->AddCSSClass('mb-5');
				$oBlock->AddSubBlock($oTextArea);

				$oSelectLanguage = SelectUIBlockFactory::MakeForSelect("language","language");
				$oBlock->AddSubBlock($oSelectLanguage);
				foreach($aAvailableLanguages as $sLangCode => $aInfo)
				{
					$oOption = SelectOptionUIBlockFactory::MakeForSelectOption($sLangCode,$aInfo['description'].' ('.$aInfo['localized_description'].')',($sLangCode == $sDefaultCode ));
					$oSelectLanguage->AddOption($oOption);
				}
				$aModules = [
					'bizmodel',
					'core/cmdb',
					'gui',
					'application',
					'addon/userrights',
					'monitoring',
				];
				$oSelectModule = SelectUIBlockFactory::MakeForSelect("modules","modules");
				$oBlock->AddSubBlock($oSelectModule);

				foreach ($aModules as $sProposedModules)
				{
					$oOption = SelectOptionUIBlockFactory::MakeForSelectOption($sProposedModules,$sProposedModules,($sProposedModules == $sModules));
					$oSelectModule->AddOption($oOption);
				}
				break;

			case 'prepare_new_dictionary':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, true);
				$sLangCode = trim(utils::ReadParam('lang_code', '', false, 'raw'));
				$sLangName = trim(utils::ReadParam('lang_name', '', false, 'raw'));
				$sLangLocName = trim(utils::ReadParam('lang_loc_name', '', false, 'raw'));
				if(empty($sLangCode) || empty($sLangName) || empty($sLangLocName))
				{
					echo "Please fill all fields.";

				}
				else
				{
					$sMessage = BuildNewLanguagepackage($sLangCode, $sLangName, $sLangLocName);
					$oPage->AddSubBlock(AlertUIBlockFactory::MakeForSuccess($sMessage));
				}
				break;

			case 'check_db_schema':
				$sCurrEnv = (isset($_SESSION['itop_env']) && !is_null($_SESSION['itop_env'])) ? $_SESSION['itop_env'] : null;
				$_SESSION['itop_env'] = TOOLKITENV;
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, false);

				$aAnalysis = CheckDBSchema();
				$_SESSION['itop_env'] = $sCurrEnv;

				$aSQLFixesTables = array();
				$aSQLFixesAll = array();
				foreach($aAnalysis as $sClass => $aData)
				{
					if (isset($aData['table_issues']))
					{
						$oFieldSet = PanelUIBlockFactory::MakeForClass($sClass,"Class ".$sClass) ;
						$oFieldSet->SetIcon(MetaModel::GetClassIcon($sClass, false));
						$oPage->AddSubBlock($oFieldSet);

						$oFieldSet->AddSubBlock( new Html("<ul>"));
						foreach($aData['table_issues'] as $sAttCode => $aIssues)
						{
							foreach($aIssues as $sText)
							{
								$oFieldSet->AddSubBlock( new Html("<li>$sText</li>"));
							}
						}
						$oFieldSet->AddSubBlock( new Html("</ul>"));
					}
					if (isset($aData['table_fixes']))
					{
						$oBlockFixes = UIContentBlockUIBlockFactory::MakeStandard(null,['fixes']);
						$oFieldSet->AddSubBlock($oBlockFixes);
						foreach($aData['table_fixes'] as $sAttCode => $aIssues)
						{
							foreach($aIssues as $sSQL)
							{
								$oBlockSql = UIContentBlockUIBlockFactory::MakeStandard(null,['fix-sql']);
								$oBlockSql->AddSubBlock(new Html($sSQL));
								$oBlockFixes->AddSubBlock($oBlockSql);
							}
							$aSQLFixesTables[] = implode(";\n", $aIssues);
							$aSQLFixesAll[] = implode(";\n", $aIssues);
						}
					}
					if (isset($aData['view_fixes']))
					{
						foreach($aData['view_fixes'] as $sAttCode => $aIssues)
						{
							$aSQLFixesAll[] = implode(";\n", $aIssues);
						}
					}
				}
				if (count($aSQLFixesTables) == 0)
				{
					$oPage->AddSubBlock(AlertUIBlockFactory::MakeForSuccess( "Ok, the database format is compliant with the data model. (Note: the views have not been checked)"));
				}

				$sSQLFixAll = $aAnalysis['*CondensedQueries']['sql'];
				$oCommandsTitle = CollapsibleSectionUIBlockFactory::MakeStandard('SQL commands to copy/paste:');
				$oCommandsTitle->SetOpenedByDefault(true);
				$oPage->AddSubBlock($oCommandsTitle);
				$oCommands = new TextArea("",$sSQLFixAll);
				$oCommands->AddCSSClasses(['ibo-input-text--export',"ibo-query-oql"]);
				$oCommandsTitle->AddSubBlock($oCommands);

				if (count($aSQLFixesTables) > 0)
				{
					$oButtonUpCodeAndDb = ButtonUIBlockFactory::MakeForPrimaryAction("Compile + Update DB tables and views","e","📀 Update iTop code and Database! ", true,"bt_up_code_and_db");
					$oButtonUpCodeAndDb->SetOnClickJsCode("doApply(true);");
					$oPage->AddSubBlock($oButtonUpCodeAndDb);
				}


				break;

			case 'check_hk':
				InitDataModel(ITOP_TOOLKIT_CONFIG_FILE, false);
				ob_start();
				$bUpdateNeeded = MetaModel::CheckHKeys(true, true);
				$sContent = ob_get_contents();
				ob_end_clean();

				if ($bUpdateNeeded)
				{
					$oButtonBuildHK = ButtonUIBlockFactory::MakeForPrimaryAction("Compute HKeys","e","Compute HKeys ", false,"bt_compute_hk");
					$oButtonBuildHK->SetOnClickJsCode("BuildHK(false);");
					$oButtonBuildHK->AddCSSClass("mb-5");
					$oPage->AddSubBlock($oButtonBuildHK);
				}
				else
				{
					$oButtonBuildHK = ButtonUIBlockFactory::MakeForPrimaryAction("Rebuild HKeys Anyway","e","Rebuild HKeys Anyway ", false,"bt_compute_hk");
					$oButtonBuildHK->SetOnClickJsCode("BuildHK(true);");
					$oButtonBuildHK->AddCSSClass("mb-5");
					$oPage->AddSubBlock($oButtonBuildHK);
				}
				if ($sContent !== false) {
					$oBlock = UIContentBlockUIBlockFactory::MakeForCode($sContent);
					$oPage->AddSubBlock($oBlock);
				}
				break;

			case 'build_hk':
				$bForce = (utils::ReadParam('force', 'false') == 'true');
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				ob_start();
				$bUpdateNeeded = MetaModel::CheckHKeys(false, true, $bForce);
				$sContent = ob_get_contents();
				ob_end_clean();
				if ($sContent !== false) {
					$oBlock = UIContentBlockUIBlockFactory::MakeForCode($sContent);
					$oPage->AddSubBlock($oBlock);
				}
				break;


			case 'check_datasources':
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				ob_start();
				$bUpdateNeeded = MetaModel::CheckDataSources(true, true);
				$sContent = ob_get_contents();
				ob_end_clean();
				if ($bUpdateNeeded)
				{
					$oButtonBuildHK = ButtonUIBlockFactory::MakeForPrimaryAction("Fix Data Sources","e","Fix Data Sources ", false,"bt_fix_DS");
					$oButtonBuildHK->SetOnClickJsCode("FixDataSources();");
					$oPage->AddSubBlock($oButtonBuildHK);
				}
				if ($sContent !== false) {
					$oBlock = UIContentBlockUIBlockFactory::MakeForCode($sContent);
					$oPage->AddSubBlock($oBlock);
				}
				break;

			case 'fix_datasources':
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				$oChange = MetaModel::NewObject("CMDBChange");
				$oChange->Set("date", time());
				$oChange->Set("userinfo", 'Change made via the toolkit');
				$oChange->DBInsert();
				ob_start();
				$bUpdateNeeded = MetaModel::CheckDataSources(false, true);
				$sContent = ob_get_contents();
				ob_end_clean();
				if ($sContent !== false) {
					$oBlock = UIContentBlockUIBlockFactory::MakeForCode($sContent);
					$oPage->AddSubBlock($oBlock);
				}
				break;

			case 'update_code':
				// Compile the code into the production environment
				$bUseSymlinks = utils::ReadParam('symlink', false);
				$oEnvironment = new RunTimeEnvironment('production');
				$oEnvironment->CompileFrom('production', $bUseSymlinks);
				$datetime = date("Y-m-d H:i:s");
				$oPage->AddSubBlock(AlertUIBlockFactory::MakeForSuccess(" Compiling...","Done! ($datetime)"));
				break;

			case 'update_code_db':

				// Compile the code into the production environment
				$bUseSymlinks = utils::ReadParam('symlink', false);
				$oEnvironment = new RunTimeEnvironment('production');
				$oEnvironment->CompileFrom('production', $bUseSymlinks);

				$oAlert = AlertUIBlockFactory::MakeForSuccess(" Compiling...","Updating the DB format (tables and views)...");
				$oPage->AddSubBlock($oAlert);
				InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
				$aAnalysis = CheckDBSchema();

				try
				{
					foreach($aAnalysis as $sClass => $aData)
					{
						if (isset($aData['table_fixes']))
						{
							foreach($aData['table_fixes'] as $sAttCode => $aIssues)
							{
								foreach($aIssues as $sSQL)
								{
									CMDBSource::Query($sSQL);
									$oAlert->AddSubBlock(new Html("<p class=\"sql_ok\">$sSQL;</p>"));
								}
							}
						}
					}

					foreach($aAnalysis as $sClass => $aData)
					{
						if (isset($aData['view_fixes']))
						{
							foreach($aData['view_fixes'] as $sAttCode => $aIssues)
							{
								foreach($aIssues as $sSQL)
								{
									CMDBSource::Query($sSQL);
									$oAlert->AddSubBlock(new Html("<p class=\"sql_ok\">$sSQL;</p>"));
								}
							}
						}
					}
					$oAlert->AddSubBlock(new Html("<p>Done</p>"));
				}
				catch(MySQLException $e)
				{
					echo "<p class=\"sql_error\">$sSQL;</p>\n";
					echo "<p class=\"sql_error\">".$e->getHtmlDesc()."</p>\n";
					echo "<p class=\"sql_error\">Operation aborted.</p>\n";
					$oAlertFailure = AlertUIBlockFactory::MakeForFailure($sSQL,$e->getHtmlDesc() );
					$oAlertFailure->AddSubBlock(new Html("<p>Operation aborted.</p>"));
					$oPage->AddSubBlock($oAlertFailure);
				}
				break;

			default:
				$oPage->AddSubBlock(AlertUIBlockFactory::MakeForFailure("The operation $sOperation is not supported"));
		}
		$oPage->output();
	}

}
catch(Exception $e)
{
	echo "<p>An error occured while processing the PHP files of the data model:</p><p>".$e->getMessage();
	echo "</p><p>Check the PHP files describing the data model before running the toolkit again !</p>";

	/* Romain: I had implemented this to view the call stack, otherwise if the Exception was not trapped, the Apache server was crashing... why ?
	if ($e instanceof CoreException)
	{
		echo "<p>".$e->getTraceAsHtml()."</p>\n";
	}
	*/
}
?>
