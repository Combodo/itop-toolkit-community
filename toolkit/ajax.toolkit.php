<?php
// Copyright (C) 2011 Combodo SARL
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
	$sRes .= htmlentities("// Class:<class_name>\n");
	$sRes .= htmlentities("// Class:<class_name>+\n");
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>\n");
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>+\n");
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>/Value:<value>\n");
	$sRes .= htmlentities("// Class:<class_name>/Attribute:<attribute_code>/Value:<value>+\n");
	$sRes .= htmlentities("// Class:<class_name>/Stimulus:<stimulus_code>\n");
	$sRes .= htmlentities("// Class:<class_name>/Stimulus:<stimulus_code>+\n");
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

function CheckDBSchema()
{
	$aAnalysis = array();
	list($aErrors, $aSugFix) = MetaModel::DBCheckFormat();
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
	return $aAnalysis;	
}

function InitDataModel($sConfigFileName, $bModelOnly = true)
{
	require_once(APPROOT.'/core/log.class.inc.php');
	require_once(APPROOT.'/core/kpi.class.inc.php');
	require_once(APPROOT.'/core/coreexception.class.inc.php');
	require_once(APPROOT.'/core/dict.class.inc.php');
	require_once(APPROOT.'/core/attributedef.class.inc.php');
	require_once(APPROOT.'/core/filterdef.class.inc.php');
	require_once(APPROOT.'/core/stimulus.class.inc.php');
	require_once(APPROOT.'/core/MyHelpers.class.inc.php');
	require_once(APPROOT.'/core/expression.class.inc.php');
	require_once(APPROOT.'/core/cmdbsource.class.inc.php');
	require_once(APPROOT.'/core/sqlquery.class.inc.php');
	require_once(APPROOT.'/core/dbobject.class.php');
	require_once(APPROOT.'/core/dbobjectsearch.class.php');
	require_once(APPROOT.'/core/dbobjectset.class.php');
	require_once(APPROOT.'/application/cmdbabstract.class.inc.php');
	require_once(APPROOT.'/core/userrights.class.inc.php');
	require_once(APPROOT.'/setup/moduleinstallation.class.inc.php');

   $oConfig = new Config($sConfigFileName);
	MetaModel::ResetCache($oConfig);
	MetaModel::Startup($sConfigFileName, $bModelOnly, false /* allow cache */);
}


/****************************************************************************
 * 
 * Main Program
 * 
 ****************************************************************************/
if (file_exists('../approot.inc.php'))
{
	// iTop 1.0.2
	include('../approot.inc.php');
}
else // iTop 1.0 & 1.0.1
{
	define('APPROOT', '../');
}

try
{
	//require_once(APPROOT.'/application/application.inc.php');
	//require_once(APPROOT.'/application/startup.inc.php');
	require_once(APPROOT.'/application/utils.inc.php');

	$sOperation = utils::ReadParam('operation', '');

	switch($sOperation)
	{
		case 'check_model':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		MetaModel::CheckDefinitions();
		break;
		
		case 'check_dictionary':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, true);
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
		echo "<input type=\"button\" value=\" Refresh \" onclick=\"CheckDictionary();\"/>\n";
		echo "<textarea style=\"width:100%;height:400px;\">";
		echo MakeDictionaryTemplate($sModules, $sDefaultCode);
		echo "</textarea>\n";
		break;
		
		case 'check_db_schema':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		$aAnalysis = CheckDBSchema();
		
		$aSQLFixes = array();
		echo "<form id=\"sql_fixes\" method=\"post\" onSubmit=\"return doApply();\">\n";
		foreach($aAnalysis as $sClass => $aData)
		{
			echo "<h2>".MetaModel::GetClassIcon($sClass)."&nbsp;Class $sClass</h2>\n";
			echo "<ul>\n";
			if (isset($aData['table_issues']))
			{
				$index = 0;
				foreach($aData['table_issues'] as $sAttCode => $aIssues)
				{
					//echo implode('<br/>', $aIssues).'<br/>';
					foreach($aIssues as $sText)
					{
						//if ($index > 0) echo "</li>\n<li>";
						echo "<li>$sText</li>";
						$index++;
					}
				}
			}
			echo "</ul>\n";
			if (isset($aData['table_fixes']))
			{
				//echo "<p>Suggested fix:</p>";
				foreach($aData['table_fixes'] as $sAttCode => $aIssues)
				{
					$index = 0;
					foreach($aIssues as $sSQL)
					{
						echo "<p class=\"sql_checkbox\"><input type=\"checkbox\" name=\"table[$sClass][$sAttCode][$index]\" checked/> $sSQL;</p/>\n";
						$index++;
					}
					//echo implode('<br/>', $aIssues).'<br/>';
					$aSQLFixes[] = implode(";\n", $aIssues);
				}
				//echo "</p></li>\n";
			}
			echo "<ul>\n";
			if (isset($aData['view_issues']))
			{
				//echo "<li>\n";
				$index = 0;
				foreach($aData['view_issues'] as $sAttCode => $aIssues)
				{
					foreach($aIssues as $sText)
					{
						//if ($index > 0) echo "</li>\n<li>";
						echo "<li>$sText</li>";
						$index++;
					}
				}
			}
			echo "</ul>\n";
			if (isset($aData['view_fixes']))
			{
				//echo "<p>Suggested fix:</p>";
				foreach($aData['view_fixes'] as $sAttCode => $aIssues)
				{
					$index = 0;
					foreach($aIssues as $sSQL)
					{
						echo "<p class=\"sql_checkbox\"><input type=\"checkbox\" name=\"view[$sClass][$sAttCode][$index]\" checked/> $sSQL;</p>\n";
						$index++;
					}
					//echo implode('<br/>', $aIssues);
					$aSQLFixes[] .= implode(";\n", $aIssues);
				}
				//echo "</p></li>\n";
			}
			echo "</ul>\n";
		}
		if (count($aSQLFixes) == 0)
		{
			echo "<p>Ok, no issue found.</p>\n";
		}
		echo "<p>&nbsp;</p>\n";
		echo "<input type=\"button\" value=\" Refresh \" onclick=\"CheckDBSchema();\"/>\n";
		if (count($aSQLFixes) > 0)
		{
			echo "<input type=\"submit\" id=\"btn_sql_apply\" value=\" Apply Selected SQL commands ! \"/>&nbsp;<span id=\"apply_sql_indicator\"></span>\n";
		}
		echo "</form>\n";
		echo "<div id=\"content_apply_sql\"></div>\n";
		echo "<p>&nbsp;</p>\n";
		echo "<hr>\n";
		echo "<h2>SQL commands to copy/paste:</h2>\n";
		if (count($aSQLFixes) > 0)
		{
			$aSQLFixes[] = '';
		}
		echo "<textarea style=\"width:100%;height:200px;font-family:Courrier, Courrier New, Nimbus Mono L, monospaced\">".implode(";\n", $aSQLFixes)."</textarea>";
		break;
		
		case 'check_hk':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		echo "<pre>\n";
		$bUpdateNeeded = MetaModel::CheckHKeys(true /*bDiagnostics*/, true /*bVerbose*/, false /*bForceComputation*/);
		echo "</pre>\n";
		if ($bUpdateNeeded)
		{
			echo "<p><button onClick=\"BuildHK(false);\">Compute HKeys</button>&nbsp;&nbsp;<button onClick=\"CheckHK();\"> Refresh </button></p>\n";
		}
		else
		{
			echo "<p><button onClick=\"BuildHK(true);\">Rebuild HKeys Anyway</button>&nbsp;&nbsp;<button onClick=\"CheckHK();\"> Refresh </button></p>\n";		
		}
		break;
		
		case 'build_hk':
		$bForce = utils::ReadParam('force', 0);
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		echo "<pre>\n";
		$bUpdateNeeded = MetaModel::CheckHKeys(false, true /*bVerbose*/, $bForce /*bForceComputation*/);
		echo "</pre>\n";
		echo "<p><button onClick=\"CheckHK();\"> Refresh </button></p>\n";
		break;
		

		case 'check_datasources':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		echo "<pre>\n";
		$bUpdateNeeded = MetaModel::CheckDataSources(true /* bDiagnostics */, true /*bVerbose*/);
		echo "</pre>\n";
		if ($bUpdateNeeded)
		{
			echo "<p><button onClick=\"FixDataSources();\">Fix Data Sources</button>&nbsp;&nbsp;<button onClick=\"CheckDataSources();\"> Refresh </button></p>\n";
		}
		else
		{
			echo "<p><button onClick=\"CheckDataSources();\"> Refresh </button></p>\n";		
		}
		break;

		case 'fix_datasources':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		$oChange = MetaModel::NewObject("CMDBChange");
		$oChange->Set("date", time());
		$oChange->Set("userinfo", 'Change made via the toolkit');
		$oChange->DBInsert();
		echo "<pre>\n";
		$bUpdateNeeded = MetaModel::CheckDataSources(false /* bDiagnostics */, true /*bVerbose*/, $oChange);
		echo "</pre>\n";
		echo "<p><button onClick=\"CheckDataSources();\"> Refresh </button></p>\n";		
		break;

		case 'apply_db_schema':
		InitDataModel(ITOP_DEFAULT_CONFIG_FILE, false);
		$aAnalysis = 	$aAnalysis = CheckDBSchema();
		$aTables = utils::ReadParam('table', array());
		$aViews = utils::ReadParam('view', array());

		echo "<p>Applying SQL commands...</p>";

		try
		{
			foreach($aAnalysis as $sClass => $aData)
			{
				if (isset($aData['table_fixes']))
				{
					foreach($aData['table_fixes'] as $sAttCode => $aIssues)
					{
						$index = 0;
						foreach($aIssues as $sSQL)
						{
							if (isset($aTables[$sClass][$sAttCode][$index]))
							{
								CMDBSource::Query($sSQL);
								echo "<p class=\"sql_ok\">$sSQL;</p>\n";
							}
							$index++;
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
						$index = 0;
						foreach($aIssues as $sSQL)
						{
							if (isset($aViews[$sClass][$sAttCode][$index]))
							{
								CMDBSource::Query($sSQL);
								echo "<p class=\"sql_ok\">$sSQL;</p>\n";
							}
							$index++;
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
		echo "The operation $sOperation is not supported";
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
