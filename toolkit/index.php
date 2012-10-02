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



/**
 * Build the config file from the parameters (especially the selected modules)
 * 
 * DUPLICATED FROM SETUPPAGE.CLASS.INC.PHP  
 */
function BuildConfig(Config &$oConfig, $aParamValues, $aAvailableModules)
{
	// Initialize the arrays below with default values for the application...
	$aAddOns = $oConfig->GetAddOns();
	$aAppModules = $oConfig->GetAppModules();
	$aDataModels = $oConfig->GetDataModels();
	$aWebServiceCategories = $oConfig->GetWebServiceCategories();
	$aDictionaries = $oConfig->GetDictionaries();
	// Merge the values with the ones provided by the modules
	// Make sure when don't load the same file twice...
	foreach($aParamValues['module'] as $sModuleId)
	{
		if (isset($aAvailableModules[$sModuleId]['datamodel']))
		{
			$aDataModels = array_unique(array_merge($aDataModels, $aAvailableModules[$sModuleId]['datamodel']));
		}
		if (isset($aAvailableModules[$sModuleId]['webservice']))
		{
			$aWebServiceCategories = array_unique(array_merge($aWebServiceCategories, $aAvailableModules[$sModuleId]['webservice']));
		}
		if (isset($aAvailableModules[$sModuleId]['dictionary']))
		{
			$aDictionaries = array_unique(array_merge($aDictionaries, $aAvailableModules[$sModuleId]['dictionary']));
		}
		if (isset($aAvailableModules[$sModuleId]['settings']))
		{
			foreach($aAvailableModules[$sModuleId]['settings'] as $sProperty => $value)
			{
				list($sName, $sVersion) = self::GetModuleName($sModuleId);
				$oConfig->SetModuleSetting($sName, $sProperty, $value);
			}
		}
		if (isset($aAvailableModules[$sModuleId]['installer']))
		{
			$sModuleInstallerClass = $aAvailableModules[$sModuleId]['installer'];
			if (!class_exists($sModuleInstallerClass))
			{
				throw new Exception("Wrong installer class: '$sModuleInstallerClass' is not a PHP class - Module: ".$aAvailableModules[$sModuleId]['label']);
			}
			if (!is_subclass_of($sModuleInstallerClass, 'ModuleInstallerAPI'))
			{
				throw new Exception("Wrong installer class: '$sModuleInstallerClass' is not derived from 'ModuleInstallerAPI' - Module: ".$aAvailableModules[$sModuleId]['label']);
			}
			$aCallSpec = array($sModuleInstallerClass, 'BeforeWritingConfig');
			//$oConfig = call_user_func_array($aCallSpec, array($oConfig));
		}
	}
	$oConfig->SetAddOns($aAddOns);
	$oConfig->SetAppModules($aAppModules);
	$oConfig->SetDataModels($aDataModels);
	$oConfig->SetWebServiceCategories($aWebServiceCategories);
	$oConfig->SetDictionaries($aDictionaries);
}



/**
 * Check the consistency
 */
function CheckConsistency($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("Use this page to validate any modification made to the PHP classes that define the 'data model'.");
	$oP->p("It is advisable to fix any error detected at this stage before applying changes from the itop update tab.");
	$oP->add("</div>");
	$oP->add("<div id=\"content_php\"></div>\n");
	$oP->add_ready_script("\nCheckConsistency(false);\n");
}

/**
 * Check the database schema - renamed "Update Itop" in the GUI!
 */
function CheckDBSchema($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("Use this page to preview the changes in the format of the database, and update iTop.".
			" For example if you add a new field to an object, this new field must be added into the database as well.");
	$oP->p("<b>Note:</b> the current version of the tool does not remove unused fields!");
	$oP->add("</div>");
	$oP->add("<div id=\"content_schema\"></div>\n");
	$oP->add_ready_script("\nCheckDBSchema(false);\n");
}

/**
 * Check the database schema
 */
function CheckDataIntegrity($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("Use this page to check the integrity of the data in the iTop database.");
	$oP->add("</div>");
	
	$oP->add("<h2>Synchronization Data Sources</h2>");
	$oP->add("<div id=\"content_datasources\">");
	$oP->add("</div>\n");
	$oP->add_ready_script("\nCheckDataSources(false);\n");
	
	$oP->add("<h2>Hierarchical Keys</h2>");
	$oP->add("<div id=\"content_hk\">\n");
	$oP->add("</div>\n");
	$oP->add_ready_script("\nCheckHK(false);\n");
}

/**
 * Check the dictionary definitions
 */
function CheckDictionary($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("This page lists all the missing items in the 'dictionary' which is used for the display and localization in iTop. To fix the issues, edit the output below and paste it into the appropriate dictionary.php file.");
	$oP->add("</div>");
	$oP->add("<div id=\"content_dictionary\"></div>\n");
	$oP->add_ready_script("\nCheckDictionary(false);\n");
}



////////////////////////////////////////////////////////////////////////////////
// Main
////////////////////////////////////////////////////////////////////////////////

if (file_exists('../approot.inc.php'))
{
	// iTop 1.0.2
	include('../approot.inc.php');
}
else // iTop 1.0 & 1.0.1
{
	define('APPROOT', '../');
}
require_once(APPROOT."/application/applicationcontext.class.inc.php");
require_once(APPROOT.'application/nicewebpage.class.inc.php');
require_once(APPROOT.'application/utils.inc.php');
require_once(APPROOT."setup/runtimeenv.class.inc.php");

if (!file_exists(ITOP_DEFAULT_CONFIG_FILE))
{
	echo "<h1>Toolkit</h1>\n";
	echo "<p>Please install iTop prior to running the toolkit</p>\n";
	exit;
}


require_once(APPROOT.'/application/startup.inc.php');

$sOperation = utils::ReadParam('operation', 'step1');

$oP = new NiceWebPage('Data Model Toolkit');
$oP->add_linked_stylesheet(utils::GetAbsoluteUrlAppRoot().'toolkit/toolkit.css');

try
{
	//$sAppRoot = utils::GetAbsoluteUrlAppRoot();
	$oP->add_script(
<<<EOF
	function GetAbsoluteUrlAppRoot()
	{
		return '../';
	}
	
	function doApply()
	{
		var oMap = { operation: 'apply_db_schema' };
		var iCount = 0;
		var bOk = confirm('Are you sure you want to compile the code and patch the database ?');
		if (bOk)
		{
			$('#apply_sql_indicator').html('<img title=\"loading...\" src=\"../images/indicator.gif\" />');					
			ajax_request = $.post(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', oMap,
					function(data)
					{
						$('#content_apply_sql').empty();
						if (data == '')
						{
							$('#content_apply_sql').append('Nothing done !');
						}
						else
						{
							$('#content_apply_sql').append(data);
						}
						$('#content_apply_sql').slideDown('slow');					
						$('#apply_sql_indicator').html('');					
					}
			);		
		}
		return false; // Do NOT submit the page anyhow
	}
	
	function CheckConsistency(bRefresh)
	{
		$('#content_php').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking the consistency of the data model definition...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_model', 'rebuild_toolkit_env': bRefresh },
				function(data)
				{
					$('#content_php').empty();
					if (data == '')
					{
						$('#content_php').append('Ok, no problem detected.');
						$('#content_php').append('<p><input type="button" value=" Refresh " onClick="CheckConsistency(true);"/></p>');
					}
					else
					{
						$('#content_php').append(data);
						$('#content_php>div p>b').each( function() {
							var sClassName = $(this).html();
							$(this).parent().after('<h2 class="class_name">Class '+sClassName+'</h2>');					
							}
						);
						$('#content_php').append('<p><input type="button" value=" Refresh " onClick="CheckConsistency(true);"/></p>');
						$('#content_php>div').css( {'background':'transparent'} );					
					}
				}
		);		
	}
	
	function CheckDBSchema(bRefresh)
	{
		$('#content_schema').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking database schema...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_db_schema', 'rebuild_toolkit_env': bRefresh },
				function(data)
				{
					$('#content_schema').empty();
					if (data == '')
					{
						$('#content_schema').append('<p>Ok, no problem detected.</p>');
					}
					else
					{
						$('#content_schema').append(data);
					}
				}
		);		
	}
	
	function CheckDataSources(bRefresh)
	{
		$('#content_datasources').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking data sources integrity...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_datasources', 'rebuild_toolkit_env': false },
				function(data)
				{
					$('#content_datasources').empty();
					if (data == '')
					{
						$('#content_datasources').append('<p>Ok, no problem detected.</p>');
					}
					else
					{
						$('#content_datasources').append(data);
					}
				}
		);		
	}
	
	function FixDataSources()
	{
		$('#content_datasources').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Fixing data sources integrity...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'fix_datasources' },
				function(data)
				{
					$('#content_datasources').empty();
					if (data == '')
					{
						$('#content_datasources').append('<p>Ok, no problem detected.</p>');
					}
					else
					{
						$('#content_datasources').append(data);
					}
				}
		);		
	}
	
	function CheckHK(bRefresh)
	{
		$('#content_hk').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking hierarchical keys integrity...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_hk', 'rebuild_toolkit_env': false },
				function(data)
				{
					$('#content_hk').empty();
					if (data == '')
					{
						$('#content_hk').append('<p>Ok, no problem detected.</p>');
					}
					else
					{
						$('#content_hk').append(data);
					}
				}
		);		
	}
	
	function BuildHK(bForce)
	{
		$('#content_hk').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Fixing hierarchical keys integrity...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'build_hk', 'force': bForce },
				function(data)
				{
					$('#content_hk').empty();
					if (data == '')
					{
						$('#content_hk').append('<p>Ok, no problem detected.</p>');
					}
					else
					{
						$('#content_hk').append(data);
					}
				}
		);		
	}
	
	function CheckDictionary(bRefresh)
	{
		var oLang = $('#language');
		var sLang = 'EN US';
		if (oLang.length > 0)
		{
			sLang = oLang.val();
		}
		var oModules = $('#modules');
		var sModules = 'bizmodel';
		if (oModules.length > 0)
		{
			sModules = oModules.val();
		}
		$('#content_dictionary').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Searching for missing dictionary items');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_dictionary', 'rebuild_toolkit_env': bRefresh, 'lang': sLang },
				function(data)
				{
					$('#content_dictionary').empty();
					$('#content_dictionary').append(data);
				}
		);		
	}
EOF
);

	$oP->add("<h1>Data Model Toolkit</h1>\n");

	define('TOOLKITENV', 'toolkit');

	// Compile the current code into the environment 'toolkit'
	// The environment will be rebuilt in case of refresh (if refreshing a view relying on this environment)
	//
	$oConfig = new Config(APPCONF.'production/'.ITOP_CONFIG_FILE);
	$oToolkitConfig = clone($oConfig);
	$oToolkitConfig->ChangeModulesPath('production', TOOLKITENV);

	$oEnvironment = new RunTimeEnvironment(TOOLKITENV);
	$oEnvironment->WriteConfigFileSafe($oToolkitConfig);
	$oEnvironment->CompileFrom('production');

	$oP->add("<!-- tabs -->\n<div id=\"tabbedContent\" class=\"light\">\n");
	$oP->add("<ul>\n");
	$oP->add("<li><a href=\"#tab_0\" class=\"tab\"><span>Data Model Consistency</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_1\" class=\"tab\"><span>iTop update</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_2\" class=\"tab\"><span>Data Integrity</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_3\" class=\"tab\"><span>Translations / Dictionnary</span></a></li>\n");
	$oP->add("</ul>\n");
	$oP->add("<div id=\"tab_0\">");
	CheckConsistency($oP);
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_1\">");
	CheckDBSchema($oP);
	$oP->add("</div>\n");
	
	$oP->add("<div id=\"tab_2\">");
	CheckDataIntegrity($oP);
	$oP->add("</div>\n");
	
	$oP->add("<div id=\"tab_3\">");
	CheckDictionary($oP);
	$oP->add("</div>\n");
	
	$oP->add("</div>\n<!-- end of tabs-->\n");
	$oP->add_ready_script(
<<<EOF
	// Tabs, using JQuery BBQ to store the history
	// The "tab widgets" to handle.
	var tabs = $('#tabbedContent');
	    
	// This selector will be reused when selecting actual tab widget A elements.
	var tab_a_selector = 'ul.ui-tabs-nav a';
	  
	// Enable tabs on all tab widgets. The `event` property must be overridden so
	// that the tabs aren't changed on click, and any custom event name can be
	// specified. Note that if you define a callback for the 'select' event, it
	// will be executed for the selected tab whenever the hash changes.
	tabs.tabs();
EOF
	);
}
catch(Exception $e)
{
	$oP->add("Error: ".$e->getMessage());
}

$oP->output();
?>
