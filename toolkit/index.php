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
