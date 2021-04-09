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


/**
 * Welcome the user
 */
function DisplayIndex($oP)
{
	$oP->add("<div class=\"info\">");
	$oP->p("Welcome, use this toolkit in order to assist you in your iTop customization.");
	$oP->p("Please keep in mind that this is a developer tool that must never be hosted on your production server.");
	$oP->p("");
	$oP->p("Please read carefully <a target='_blank' href='https://www.itophub.io/wiki/page?id=latest%3Acustomization%3Adatamodel&s%5B0%5D=toolkit#using_the_toolkit'>the documentation</a> prior any use!");
	$oP->add("</div>");
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
	$oP->add("<div id=\"content_php\"><button onclick='CheckConsistency(true);'>Check Consistency</button></div>\n");
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
	$oP->add("<div id=\"content_schema\">
		<button onclick='CheckDBSchema(true);'>📀 Check DB Schema</button>
		<hr />");
		if (function_exists('symlink'))
		{
			$oP->add("<p><input type=\"checkbox\" id=\"symlink\" value=\"1\"><label for=\"symlink\">&nbsp;Create symbolic links instead of creating a copy in env-production (useful for debugging extensions)</label></p>\n");
		}
		$sSourceDir = MetaModel::GetConfig()->Get('source_dir');
		$sSourceDirHtml = htmlentities($sSourceDir, ENT_QUOTES, 'UTF-8');
	$oP->add("<button  onclick=\"doApply(false);\" title=\"Compile from $sSourceDirHtml to env-production\">📄 Update iTop code</button>&nbsp;<span id=\"apply_sql_indicator\"></span>\n");
	$oP->add("<div id=\"content_apply_sql\"></div>\n</div>\n");
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
	$oP->add("<button onclick='CheckDataSources(true);'>Check Data Sources</button>");
	$oP->add("</div>\n");

	$oP->add("<h2>Hierarchical Keys</h2>");
	$oP->add("<div id=\"content_hk\">\n");
	$oP->add("<button onclick='CheckHK(true);'>Check Hierarchical Keys</button>");
	$oP->add("</div>\n");
}

/**
 * Check the dictionary definitions
 */
function CheckDictionary($oP)
{
	$oP->add("<!-- tabs -->\n<div id=\"tabbedContent-dict\" class=\"tabbedContent\" class=\"light\">\n");
	$oP->add("<ul>\n");
	$oP->add("<li><a href=\"#tab_dict_0\" class=\"tab\"><span>Complete existing language</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_dict_1\" class=\"tab\"><span>Prepare new language</span></a></li>\n");
	$oP->add("</ul>\n");

	$oP->add("<div id=\"tab_dict_0\">");
	$oP->add("<div class=\"info\">");
	$oP->p("This page lists all the missing items in the 'dictionary' which is used for the display and localization in iTop. To fix the issues, edit the output below and paste it into the appropriate dictionary.php file.");
	$oP->add("</div>");
	$oP->add("<div id=\"content_dictionary\"><button onclick='CheckDictionary(true);'>🌐 Check Dictionary</button></div>\n");
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_dict_1\">");
	$oP->add("<div class=\"info\">");
	$oP->p("Here you can prepare a zip file containing all dictionary files for a new language. They will be filled with english translations appended with \"~~\" to easily find what remains for translation.");
	$oP->p("Check localization guidelines <a href=\"https://wiki.openitop.org/doku.php?id=2_2_0:customization:start#localization\" target=\"_blank\">see here</a>.");
	$oP->add("</div>");
	$oP->add(
<<<EOF
	<div>
		<form id="prepare_new_dictionary">
			<p><label><span>Language code:</span><input type="text" id="language_code" name="language_code" placeholder="eg. FR FR"/></label></p>
			<p><label><span>Language english name:</span><input type="text" id="language_name" name="language_name" placeholder="eg. French"/></label></p>
			<p><label><span>Language localized name:</span><input type="text" id="language_localized_name" name="language_localized_name" placeholder="eg. Français"/></label></p>
			<p><button type="submit" name="submit">Generate</button></p>
		</form>
	</div>
EOF
	);
	$oP->add("<div id=\"content_new_dictionary\"></div>\n");
	$oP->add_ready_script("\n$('#prepare_new_dictionary button[type=\"submit\"]').click(function(oEvent){ oEvent.preventDefault(); PrepareNewDictionary(true); });\n");
	$oP->add("</div>\n");

	$oP->add("</div>\n<!-- end of tabs-->\n");
	$oP->add_ready_script(
<<<EOF
	// Same stuff as for the main tabs (see below)
	var dictTabs = $('#tabbedContent-dict');	
	dictTabs.tabs();
EOF
	);
}



////////////////////////////////////////////////////////////////////////////////
// Main
////////////////////////////////////////////////////////////////////////////////

if (file_exists('../approot.inc.php'))
{
	// iTop 1.0.2+
	include('../approot.inc.php');
}
else // iTop 1.0 & 1.0.1
{
	define('APPROOT', '../');
}

// iTop 2.7.0+
if (file_exists(APPROOT.'/bootstrap.inc.php'))
{
	require_once(APPROOT.'/bootstrap.inc.php');
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

$oP = new NiceWebPage('Data Model Toolkit');
$oP->add_linked_stylesheet(utils::GetAbsoluteUrlAppRoot().'toolkit/toolkit.css');

try
{
	//$sAppRoot = utils::GetAbsoluteUrlAppRoot();
	$oP->add_script(
<<<JS
	function GetAbsoluteUrlAppRoot()
	{
		return '../';
	}
	
	function doApply(bFull)
	{
		if (bFull)
		{
			var oMap = { operation: 'update_code_db' };
			var bOk = confirm('Are you sure you want to compile the code and patch the database ?');
		}
		else
		{
			var oMap = { operation: 'update_code' };
			var bOk = confirm('Are you sure you want to compile the code ?');
		}
		
		var oUseSymlinks = $('#symlink:checked');
		if (oUseSymlinks.length > 0)
		{
			oMap.symlink = 1;
		}
		var iCount = 0;
		
		if (bOk)
		{
			// remove previous results
			$("div#content_apply_sql").html("");
			
			var sTitleLoadingPrefix = '⚠️ - ';
			var sOriginalTitle = document.title;
			
			$('#apply_sql_indicator').html('<img title=\"loading...\" src=\"../images/indicator.gif\" />');
			document.title = sTitleLoadingPrefix + sOriginalTitle;
			
			ajax_request = $.post(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', oMap,
					function(data)
					{
						$('#content_apply_sql').empty();
						if (data == '')
						{
							$('#content_apply_sql').append('Nothing done !');
							notifyMe('Nothing done !')
						}
						else
						{
							$('#content_apply_sql').append(data);
							notifyMe('Update finished')
						}
						$('#content_apply_sql').slideDown('slow');
											
						$('#apply_sql_indicator').html('');
						document.title = sOriginalTitle;
					}
			);		
		}
	}
	
	function notifyMe(notificationStr) {
	  if (!("Notification" in window)) {
	    return;
	  } else if (Notification.permission === "granted") {
	    var notification = new Notification(notificationStr);
	  } else if (Notification.permission !== 'denied') {
	    Notification.requestPermission(function (permission) {
	
	      if(!('permission' in Notification)) {
	        Notification.permission = permission;
	      }
	
	      if (permission === "granted") {
	        var notification = new Notification(notificationStr);
	      }
	    });
  }
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
	
	function PrepareNewDictionary(bRefresh)
	{
		var oForm = ('#prepare_new_dictionary');
		var sLangCode = $('#language_code').val();
		var sLangName = $('#language_name').val();
		var sLangLocName = $('#language_localized_name').val();
		
		if(sLangCode === '' || sLangName === '' || sLangLocName === '')
		{
			alert('Please fill all fields');
			return false;
		}
		
		$('#content_new_dictionary').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Preparing dictionary files for "'+sLangName+'"');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'prepare_new_dictionary', 'rebuild_toolkit_env': bRefresh, 'lang_code': sLangCode, 'lang_name': sLangName, 'lang_loc_name': sLangLocName },
				function(data)
				{
					$('#content_new_dictionary').empty();
					$('#content_new_dictionary').append(data);
				}
		);		
	}
JS
);

	$oP->add("<h1>Data Model Toolkit</h1>\n");

	define('TOOLKITENV', 'toolkit');

	$oConfig = new Config(APPCONF.'production/'.ITOP_CONFIG_FILE);
	if ($oConfig->Get('source_dir') == '')
	{
		throw new Exception('Missing entry source_dir from the config file');
	}

	$oP->add("<!-- tabs -->\n<div id=\"tabbedContent\" class=\"light\">\n");
	$oP->add("<ul>\n");
	$oP->add("<li><a href=\"#tab_index\" class=\"tab\"><span>Index</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_0\" class=\"tab\"><span>Data Model Consistency</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_1\" class=\"tab\"><span>iTop update</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_2\" class=\"tab\"><span>Data Integrity</span></a></li>\n");
	$oP->add("<li><a href=\"#tab_3\" class=\"tab\"><span>Translations / Dictionary</span></a></li>\n");
	$oP->add("</ul>\n");

	$oP->add("<div id=\"tab_index\">");
	DisplayIndex($oP);
	$oP->add("</div>\n");

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
<<<JS
	// Tabs, using JQuery BBQ to store the history
	// The "tab widgets" to handle.
	var tabs = $('#tabbedContent');

	// Ugly patch for a change in the behavior of jQuery UI:
	// Before jQuery UI 1.9, tabs were always considered as "local" (opposed to Ajax)
	// when their href was beginning by #. Starting with 1.9, a <base> tag in the page
	// is taken into account and causes "local" tabs to be considered as Ajax
	// unless their URL is equal to the URL of the page...
	$('#tabbedContent ul li a').each(function() {
		var sHash = location.hash;
		var sCleanLocation = location.href.toString().replace(sHash, '').replace(/#$/, '');
    	$(this).attr("href", sCleanLocation+$(this).attr("href"));
	});
			    
	// This selector will be reused when selecting actual tab widget A elements.
	var tab_a_selector = 'ul.ui-tabs-nav a';
	  
	// Enable tabs on all tab widgets. The `event` property must be overridden so
	// that the tabs aren't changed on click, and any custom event name can be
	// specified. Note that if you define a callback for the 'select' event, it
	// will be executed for the selected tab whenever the hash changes.
	tabs.tabs();
JS
	);
}
catch(Exception $e)
{
	$oP->add("Error: ".$e->getMessage());
}

$oP->output();
?>
