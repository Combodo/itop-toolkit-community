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
 * 
 * Copied from the setup page !!
 * 
 */
class SetupWebPage {
	
	static $m_aModuleArgs = array(
		'label' => 'One line description shown during the interactive setup',
		'dependencies' => 'array of module ids',
		'mandatory' => 'boolean',
		'visible' => 'boolean',
		'datamodel' =>  'array of data model files',
		'dictionary' => 'array of dictionary files',
		'data.struct' => 'array of structural data files',
		'data.sample' => 'array of sample data files',
		'doc.manual_setup' => 'url',
		'doc.more_information' => 'url',
	);
	
	static $m_aModules = array();
	
	// All the entries below are list of file paths relative to the module directory
	static $m_aFilesList = array('datamodel', 'webservice', 'dictionary', 'data.struct', 'data.sample');

	static $m_sModulePath = null;
	public static function SetModulePath($sModulePath)
	{
		self::$m_sModulePath = $sModulePath;
	}

	public static function AddModule($sFilePath, $sId, $aArgs)
	{
		foreach (self::$m_aModuleArgs as $sArgName => $sArgDesc)
		{
			if (!array_key_exists($sArgName, $aArgs))
			{
				throw new Exception("Module '$sId': missing argument '$sArgName'");
		   }
		}

		self::$m_aModules[$sId] = $aArgs;

		foreach(self::$m_aFilesList as $sAttribute)
		{
			if (isset(self::$m_aModules[$sId][$sAttribute]))
			{
				// All the items below are list of files, that are relative to the current file
				// being loaded, let's update their path to store path relative to the application directory
				foreach(self::$m_aModules[$sId][$sAttribute] as $idx => $sRelativePath)
				{
				self::$m_aModules[$sId][$sAttribute][$idx] = self::$m_sModulePath.'/'.$sRelativePath;
				}
			}
		}
	}
	
	static public function GetModules()
	{
		// Order the modules to take into account their inter-dependencies
		$aDependencies = array();
		foreach(self::$m_aModules as $sId => $aModule)
		{
			$aDependencies[$sId] = $aModule['dependencies'];
		}
		$aOrderedModules = array();
		$iLoopCount = 1;
		while(($iLoopCount < count(self::$m_aModules)) && (count($aDependencies) > 0) )
		{
			foreach($aDependencies as $sId => $aRemainingDeps)
			{
				$bDependenciesSolved = true;
				foreach($aRemainingDeps as $sDepId)
				{
					if (!in_array($sDepId, $aOrderedModules))
					{
						$bDependenciesSolved = false;
					}
				}
				if ($bDependenciesSolved)
				{
					$aOrderedModules[] = $sId;
					unset($aDependencies[$sId]);
				}
			}
			$iLoopCount++;
		}
		if (count($aDependencies) >0)
		{
			$sHtml = "<ul><b>Warning: the following modules have unmet dependencies, and have been ignored:</b>\n";			
			foreach($aDependencies as $sId => $aDeps)
			{
				$aModule = self::$m_aModules[$sId];
				$sHtml.= "<li>{$aModule['label']} (id: $sId), depends on: ".implode(', ', $aDeps)."</li>";
			}
			$sHtml .= "</ul>\n";
			$this->warning($sHtml);
		}
		// Return the ordered list, so that the dependencies are met...
		$aResult = array();
		foreach($aOrderedModules as $sId)
		{
			$aResult[$sId] = self::$m_aModules[$sId];
		}
		return $aResult;
	}
	
	public static function ListModuleFiles($sRelDir)
	{
		$sDirectory = APPROOT.'/'.$sRelDir;
		//echo "<p>$sDirectory</p>\n";
		if ($hDir = opendir($sDirectory))
		{
			// This is the correct way to loop over the directory. (according to the documentation)
			while (($sFile = readdir($hDir)) !== false)
			{
				$aMatches = array();
				if (is_dir($sDirectory.'/'.$sFile))
				{
					if (($sFile != '.') && ($sFile != '..') && ($sFile != '.svn'))
					{
						self::ListModuleFiles($sRelDir.'/'.$sFile);
					}
				}
				else if (preg_match('/^module\.(.*).php$/i', $sFile, $aMatches))
				{
					self::SetModulePath($sRelDir);
					try
					{
						//echo "<p>Loading: $sDirectory/$sFile...</p>\n";
						require_once($sDirectory.'/'.$sFile);
						//echo "<p>Done.</p>\n";
					}
					catch(Exception $e)
					{
						// Continue...
					}
				}
			}
			closedir($hDir);
		}
		else
		{
			echo "Error: Data directory (".$sDirectory.") not found or not readable.";
		}
	}
	
	
	/**
	 * Search (on the disk) for all defined iTop modules, load them and returns the list (as an array)
	 * of the possible iTop modules to install
	 * @param none
	 * @return Hash A big array moduleID => ModuleData
	 */
	function GetAvailableModules(Webpage $oP)
	{
		clearstatcache();
		self::ListModuleFiles('modules', $oP);
		return self::GetModules();
	}
	
	/**
	 * Build the config file from the parameters (especially the selected modules)
	 */
	static function BuildConfig(Config &$oConfig, $aParamValues, $aAvailableModules)
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
	 * Helper function to interpret the name of a module
	 * @param $sModuleId string Identifier of the module, in the form 'name/version'
	 * @return array(name, version)
	 */    
	static public function GetModuleName($sModuleId)
	{
		if (preg_match('!^(.*)/(.*)$!', $sModuleId, $aMatches))
		{
			$sName = $aMatches[1];
			$sVersion = $aMatches[2];
		}
		else
		{
			$sName = $sModuleId;
			$sVersion = "";
		}
		return array($sName, $sVersion);
	}

}

/**
 * Check the consistency
 */
function CheckConsistency($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("Use this page to validate any modification made to the PHP classes that define the 'data model'.");
	$oP->p("It is advisable to fix any error detected at this stage before checking the database schema on the next tab.");
	$oP->add("</div>");
	$oP->add("<div id=\"content_php\"></div>\n");
	$oP->add_ready_script("\nCheckConsistency();\n");
}

/**
 * Check the database schema
 */
function CheckDBSchema($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("Use this page to check that the MySQL Schema is compatible with the definitions of the data model.".
			"For example if you add a new field to an object, this new field must be added into the database as well.");
	$oP->p("<b>Note:</b> the current version of the tool neither detects nor removes unused fields!");
	$oP->add("</div>");
	$oP->add("<div id=\"content_schema\"></div>\n");
	$oP->add_ready_script("\nCheckDBSchema();\n");
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
	$oP->add_ready_script("\nCheckDictionary();\n");
}

if (file_exists('../approot.inc.php'))
{
	// iTop 1.0.2
	include('../approot.inc.php');
}
else // iTop 1.0 & 1.0.1
{
	define('APPROOT', '../');
}
require_once(APPROOT.'application/nicewebpage.class.inc.php');
require_once(APPROOT.'application/utils.inc.php');

$sOperation = utils::ReadParam('operation', 'step1');

$oP = new NiceWebPage('Data Model Toolkit');
$oP->add_linked_stylesheet('./toolkit.css');

try
{
	$oP->add_script(
<<<EOF
	function doApply()
	{
		var oMap = { operation: 'apply_db_schema' };
		var iCount = 0;
		// Gather the parameters from the search form
		$('#sql_fixes :input:checked').each(
			function()
			{
				if (this.name != '')
				{
					oMap[this.name] = this.value;
					iCount++;
				}
			}
		);
		if (iCount == 0)
		{
			alert('Please select one or more query before pressing "Apply Selected SQL Commands"');
		}
		else
		{
			var bOk = confirm('Are you sure you want to patch the database ?');
			if (bOk)
			{
				$('#apply_sql_indicator').html('<img title=\"loading...\" src=\"../images/indicator.gif\" />');					
				$('#btn_sql_apply').attr('disabled', 'disabled');
				ajax_request = $.post('ajax.toolkit.php', oMap,
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
		}
		return false; // Do NOT submit the page anyhow
	}
	
	function CheckConsistency()
	{
		$('#content_php').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking the consistency of the data model definition...');
		ajax_request = $.get('ajax.toolkit.php', { 'operation': 'check_model' },
				function(data)
				{
					$('#content_php').empty();
					if (data == '')
					{
						$('#content_php').append('Ok, no problem detected.');
					}
					else
					{
						$('#content_php').append(data);
						$('#content_php>div p>b').each( function() {
							var sClassName = $(this).html();
							$(this).parent().after('<h2 class="class_name">Class '+sClassName+'</h2>');					
							}
						);
						$('#content_php').append('<p><input type="button" value="Refresh" onClick="CheckConsistency();"/></p>');
						$('#content_php>div').css( {'background':'transparent'} );					
					}
				}
		);		
	}
	
	function CheckDBSchema()
	{
		$('#content_schema').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking database schema...');
		ajax_request = $.get('ajax.toolkit.php', { 'operation': 'check_db_schema' },
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
						$('#btn_sql_apply').attr('disabled', '');
					}
				}
		);		
	}
	
	function CheckDictionary()
	{
		var oLang = $('#language');
		var sLang = 'EN US';
		if (oLang.length > 0)
		{
			sLang = oLang.val();
		}
		$('#content_dictionary').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Searching for missing dictionary items');
		ajax_request = $.get('ajax.toolkit.php', { 'operation': 'check_dictionary', 'lang': sLang },
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

	if (!file_exists(ITOP_CONFIG_FILE))
	{
		// The configuration file does not exist, create a dummy one
		require_once(APPROOT.'setup/moduleinstaller.class.inc.php');
		
		SetupWebPage::ListModuleFiles('modules');
		$aModules = SetupWebPage::GetModules();	
		$aParams = array( 'module' => array_keys($aModules)); // Install all modules
		$oConfig = new Config(ITOP_CONFIG_FILE, false /* Don't try to load it */);
		SetupWebPage::BuildConfig($oConfig, $aParams, $aModules);
		$oConfig->WriteToFile(ITOP_CONFIG_FILE);
		$oP->p('<b>Warning</b>: The configuration file "'.ITOP_CONFIG_FILE.'" did not exist.');
		$oP->p('A temporary configuration file has been created with all the modules enabled.');
		$oP->p('Edit the file "'.ITOP_CONFIG_FILE.'" to add the database server and credentials before re-loading this page.');
		$oP->output();
		exit;
	}

	$oP->add("<!-- tabs -->\n<div id=\"tabbedContent\" class=\"light\">\n");
	$oP->add("<ul>\n");
	$oP->add("<li><a href=\"#tab_0\" class=\"tab\"><span>Data Model Consistency</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_1\" class=\"tab\"><span>Database Schema</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_2\" class=\"tab\"><span>Translations / Dictionnary</span></a></li>\n");
	$oP->add("</ul>\n");
	$oP->add("<div id=\"tab_0\">");
	CheckConsistency($oP);
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_1\">");
	CheckDBSchema($oP);
	$oP->add("</div>\n");
	$oP->add("<div id=\"tab_2\">");
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
