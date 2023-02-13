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

use Combodo\iTop\Application\Branding;
use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Button\Button;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\FieldSet\FieldSetUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Form\FormUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\Component\Input\InputUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Panel\PanelUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\TabContainer\TabContainer;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;


/**
 * Welcome the user
 */
function DisplayIndex()
{
	$oAlert = AlertUIBlockFactory::MakeForInformation("Welcome, use this toolkit in order to assist you in your iTop customization.","Please keep in mind that this is a developer tool that must never be hosted on your production server.");
	$oAlert->AddSubBlock(new Html("Please read carefully <a target='_blank' href='https://www.itophub.io/wiki/page?id=latest%3Acustomization%3Adatamodel&s%5B0%5D=toolkit#using_the_toolkit'>the documentation</a> prior any use!"));
	return $oAlert;
}

/**
 * Check the consistency
 */
function CheckConsistency()
{
	$oBlock = UIContentBlockUIBlockFactory::MakeStandard();
	$oAlert = AlertUIBlockFactory::MakeForInformation("Use this page to validate any modification made to the PHP classes that define the 'data model'.","It is advisable to fix any error detected at this stage before applying changes from the itop update tab.");
	$oBlock->AddSubBlock($oAlert);

	$oDivAction=UIContentBlockUIBlockFactory::MakeStandard("content_php",['ibo-is-html-content']);
	$oBlock->AddSubBlock($oDivAction);

	$oButton=ButtonUIBlockFactory::MakeForPrimaryAction("Check Consistency","a","b",false,"bt_content_php");
	$oButton->SetOnClickJsCode('CheckConsistency(true);');
	$oBlock->AddSubBlock($oButton);
	return $oBlock;
}

/**
 * Check the database schema - renamed "Update Itop" in the GUI!
 */
function CheckDBSchema()
{
	$oBlock = UIContentBlockUIBlockFactory::MakeStandard();
	$oAlert = AlertUIBlockFactory::MakeForInformation("Use this page to preview the changes in the format of the database, and update iTop."," For example if you add a new field to an object, this new field must be added into the database as well.<br/><b>Note:</b> the current version of the tool does not remove unused fields!");
	$oBlock->AddSubBlock($oAlert);

	$oFieldSetDB = FieldSetUIBlockFactory::MakeStandard('DB Schema');
	$oBlock->AddSubBlock($oFieldSetDB);

	$oDivAction=UIContentBlockUIBlockFactory::MakeStandard("content_schema",['ibo-is-visible']);
	$oFieldSetDB->AddSubBlock($oDivAction);

	$oButtonSchema=ButtonUIBlockFactory::MakeForPrimaryAction("📀 Check DB Schema","a","b",false, "bt_content_schema");
	$oButtonSchema->SetOnClickJsCode('CheckDBSchema(true);');
	$oDivAction->AddSubBlock($oButtonSchema);

	$oFieldSetCompile = FieldSetUIBlockFactory::MakeStandard('Compilation');
	$oBlock->AddSubBlock($oFieldSetCompile);

	$sSourceDir = MetaModel::GetConfig()->Get('source_dir');
	$sSourceDirHtml = htmlentities($sSourceDir, ENT_QUOTES, 'UTF-8');

	if (function_exists('symlink'))
	{
		$oCheckbox = InputUIBlockFactory::MakeForInputWithLabel('Create symbolic links instead of creating a copy in env-production (useful for debugging extensions)', "symlink", 1, "symlink", 'checkbox');
		$oCheckbox->SetBeforeInput(false);
		$oCheckbox->GetInput()->AddCSSClass('ibo-input-checkbox');
		$oFieldSetCompile->AddSubBlock($oCheckbox);

		if (defined('\MFCompiler::USE_SYMBOLIC_LINKS_FILE_PATH') && (\MFCompiler::IsUseSymbolicLinksFlagPresent()))
		{
			/** @var \Combodo\iTop\Application\UI\Base\Component\Input\Input $oCheckboxInput */
			$oCheckboxInput = $oCheckbox->GetInput();
			$oCheckboxInput->SetIsChecked(true);
		}
	}
	$oDivButton = UIContentBlockUIBlockFactory::MakeStandard("div_bt_compilation",['ibo-is-visible','mt-5','mb-5']);
	$oFieldSetCompile->AddSubBlock($oDivButton);
	$oButtonCompile=ButtonUIBlockFactory::MakeForPrimaryAction("📄 Update iTop code","Compile from $sSourceDirHtml to env-production","b",false, "bt_content_apply_sql");
	$oButtonCompile->SetOnClickJsCode('doApply(false);');
	$oDivButton->AddSubBlock($oButtonCompile);

	$oFieldSetCompile->AddSubBlock(new Html('<span id="apply_sql_indicator"></span>'));
	$oFieldSetCompile->AddSubBlock(UIContentBlockUIBlockFactory::MakeStandard("content_apply_sql",['ibo-is-visible']));

	$oFieldQuickSetup = FieldSetUIBlockFactory::MakeStandard('Quick setup tool');
	$oBlock->AddSubBlock($oFieldQuickSetup);

	$oDivQS = UIContentBlockUIBlockFactory::MakeStandard("content_schema",['ibo-is-visible']);
	$oAlert = AlertUIBlockFactory::MakeForInformation("Bookmark this button by dragging it in your bookmarks bar in order to skip every setup steps by clicking on your new bookmarklet with a setup page opened.");
	$oDivQS->AddSubBlock($oAlert);
	$oCurrentQuickCurrentButton = ButtonUIBlockFactory::MakeLinkNeutral("javascript:(function(){var quicksetup=document.createElement('SCRIPT');quicksetup.type='text/javascript';quicksetup.src='https://cdn.jsdelivr.net/gh/Combodo/itop-toolkit-community@master/js/quick-setup.js';document.getElementsByTagName('head')[0].appendChild(quicksetup);})();","Quick Setup");
	$oCurrentQuickCurrentButton->SetOnClickJsCode('javascript:return false;')->SetColor(Button::ENUM_COLOR_SCHEME_PRIMARY);
	$oDivQS->AddSubBlock($oCurrentQuickCurrentButton);
	$oFieldQuickSetup->AddSubBlock($oDivQS);
	
	return $oBlock;
}

/**
 * Check the database schema
 */
function CheckDataIntegrity()
{
	$oBlock = UIContentBlockUIBlockFactory::MakeStandard();
	$oAlert = AlertUIBlockFactory::MakeForInformation("Use this page to check the integrity of the data in the iTop database.","");
	$oBlock->AddSubBlock($oAlert);

	$oFieldSetDatasources = FieldSetUIBlockFactory::MakeStandard('Synchronization Data Sources');
	$oBlock->AddSubBlock($oFieldSetDatasources);
	$oDivDatasources=UIContentBlockUIBlockFactory::MakeStandard("content_datasources",['ibo-is-visible']);
	$oFieldSetDatasources->AddSubBlock($oDivDatasources);
	$oButtonDatasources=ButtonUIBlockFactory::MakeForPrimaryAction("Check Data Sources","a","b", false, "bt_content_datasources");
	$oButtonDatasources->SetOnClickJsCode('CheckDataSources(true);');
	$oFieldSetDatasources->AddSubBlock($oButtonDatasources);

	$oFieldSetKeys = FieldSetUIBlockFactory::MakeStandard('Hierarchical Keys');
	$oBlock->AddSubBlock($oFieldSetKeys);
	$oDivKeys=UIContentBlockUIBlockFactory::MakeStandard("content_hk",['ibo-is-html-content']);
	$oFieldSetKeys->AddSubBlock($oDivKeys);
	$oButtonKeys=ButtonUIBlockFactory::MakeForPrimaryAction("Check Hierarchical Keys","a","b", false, "bt_content_hk");
	$oButtonKeys->SetOnClickJsCode('CheckHK(true);');
	$oFieldSetKeys->AddSubBlock($oButtonKeys);

	return $oBlock;
}

/**
 * Check the dictionary definitions
 */
function CheckDictionary()
{
	$oTabContainer = UIContentBlockUIBlockFactory::MakeStandard();

	//** Tab:tab_dict_0 */
	$oTabComplete = FieldSetUIBlockFactory::MakeStandard('Complete existing language');
	$oTabContainer->AddSubBlock($oTabComplete);

	$oAlert = AlertUIBlockFactory::MakeForInformation("This page lists all the missing items in the 'dictionary' which is used for the display and localization in iTop. ","To fix the issues, edit the output below and paste it into the appropriate dictionary.php file.");
	$oTabComplete->AddSubBlock($oAlert);

	$oDivAction=UIContentBlockUIBlockFactory::MakeStandard("content_dictionary",['ibo-is-visible']);
	$oTabComplete->AddSubBlock($oDivAction);

	$oButton=ButtonUIBlockFactory::MakeForPrimaryAction("🌐 Check Dictionary","a","b");
	$oButton->SetOnClickJsCode('CheckDictionary(true);');
	$oButton->AddCSSClass('mt-5');
	$oTabComplete->AddSubBlock($oButton);


	//** Tab:tab_dict_1 */
	$oTabPrepare = FieldSetUIBlockFactory::MakeStandard('Prepare new language');
	$oTabContainer->AddSubBlock($oTabPrepare);

	$oAlertPrepare = AlertUIBlockFactory::MakeForInformation("Here you can prepare a zip file containing all dictionary files for a new language. ","They will be filled with english translations appended with \"~~\" to easily find what remains for translation.");
	$oAlertPrepare->AddSubBlock(new Html("Check localization guidelines <a href=\"https://wiki.openitop.org/doku.php?id=2_2_0:customization:start#localization\" target=\"_blank\">see here</a>."));
	$oTabPrepare->AddSubBlock($oAlertPrepare);

	$oFormPrepare = FormUIBlockFactory::MakeStandard("prepare_new_dictionary",['ibo-is-visible']);
	$oTabPrepare->AddSubBlock($oFormPrepare);

	$oInputCode = InputUIBlockFactory::MakeForInputWithLabel('Language code:', "language_code", "", "language_code", "text");
	$oInputCode->GetInput()->AddCSSClass('mb-5');
	$oInputCode->GetInput()->SetPlaceholder("eg. FR FR");
	$oFormPrepare->AddSubBlock($oInputCode);

	$oInputName = InputUIBlockFactory::MakeForInputWithLabel('Language english name:', "language_name", "", "language_name", "text");
	$oInputName->GetInput()->AddCSSClass('mb-5');
	$oInputName->GetInput()->SetPlaceholder("eg. French");
	$oFormPrepare->AddSubBlock($oInputName);

	$oInputLocalized = InputUIBlockFactory::MakeForInputWithLabel('Language localized name:', "language_localized_name", "", "language_localized_name", "text");
	$oInputLocalized->GetInput()->AddCSSClass('mb-5');
	$oInputLocalized->GetInput()->SetPlaceholder("eg. Français");
	$oFormPrepare->AddSubBlock($oInputLocalized);

	$oButtonGenerate=ButtonUIBlockFactory::MakeForPrimaryAction("Generate","a","b",false, "bt_prepare_new_dictionary");
	$oButtonGenerate->SetOnClickJsCode(' PrepareNewDictionary(true);');
	$oButtonGenerate->AddCSSClass('mb-5');
	$oFormPrepare->AddSubBlock($oButtonGenerate);

	$oDivDatasources=UIContentBlockUIBlockFactory::MakeStandard("content_new_dictionary",['ibo-is-visible']);
	$oTabPrepare->AddSubBlock($oDivDatasources);

	return $oTabContainer;

//	$oP->add_ready_script("\n$('#prepare_new_dictionary button[type=\"submit\"]').click(function(oEvent){ oEvent.preventDefault(); PrepareNewDictionary(true); });\n");
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///  LEGACY BEFORE 3.0
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Welcome the user
 */
function DisplayIndexLegacy($oP)
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
function CheckConsistencyLegacy($oP)
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
function CheckDBSchemaLegacy($oP)
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
	
	
	$oP->add("<hr/>");
	$oP->p("Bookmark this button by dragging it in your bookmarks bar in order to skip every setup steps by clicking on your new bookmarklet with a setup page opened");
	$oP->add("<a href=\"javascript:(function(){var quicksetup=document.createElement('SCRIPT');quicksetup.type='text/javascript';quicksetup.src='https://cdn.jsdelivr.net/gh/Combodo/itop-toolkit-community@master/js/quick-setup.js';document.getElementsByTagName('head')[0].appendChild(quicksetup);})();\" onclick=\"javascript:return false;\"> Quick Setup Tool</a>");

}

/**
 * Check the database schema
 */
function CheckDataIntegrityLegacy($oP)
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
function CheckDictionaryLegacy($oP)
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

/**
 * @param \NiceWebPage $oP
 *
 * @throws \ConfigException
 * @throws \CoreException
 */
function DisplayLegacy(NiceWebPage $oP)
{
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
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_dictionary', 'rebuild_toolkit_env': bRefresh, 'lang': sLang, 'modules': sModules },
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
	DisplayIndexLegacy($oP);
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_0\">");
	CheckConsistencyLegacy($oP);
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_1\">");
	CheckDBSchemaLegacy($oP);
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_2\">");
	CheckDataIntegrityLegacy($oP);
	$oP->add("</div>\n");

	$oP->add("<div id=\"tab_3\">");
	CheckDictionaryLegacy($oP);
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
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///  END LEGACY BEFORE 3.0
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
require_once(APPROOT.'application/utils.inc.php');
require_once(APPROOT."setup/runtimeenv.class.inc.php");
if (version_compare(ITOP_DESIGN_LATEST_VERSION , '3.0') < 0) {
	require_once(APPROOT.'application/nicewebpage.class.inc.php');
}

if (!file_exists(ITOP_DEFAULT_CONFIG_FILE))
{
	echo "<h1>Toolkit</h1>\n";
	echo "<p>Please install iTop prior to running the toolkit</p>\n";
	exit;
}

require_once(APPROOT.'/application/startup.inc.php');

$oP = new NiceWebPage('Data Model Toolkit');
$oP->add_linked_stylesheet(utils::GetAbsoluteUrlAppRoot().'toolkit/toolkit.css');

/**
 * @param \NiceWebPage $oP
 *
 * @throws \ConfigException
 * @throws \CoreException
 */
function Display(NiceWebPage $oP)
{

	$oP->add_linked_script(utils::GetAbsoluteUrlAppRoot().'js/jquery.ba-bbq.min.js');
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
			if (bFull)
			{
				$('#bt_content_apply_sql').attr("disabled", true);
			} else {
				$('#bt_up_code_and_db').attr("disabled", true);
			}
			ajax_request = $.ajax({
				url: GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php',
				data: oMap,
				success: function(data)	{
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
					
					if (bFull)
					{
						$('#bt_content_apply_sql').attr("disabled", false);
					} else {
						$('#bt_up_code_and_db').attr("disabled", false);
					}
				},
				error: function() {
					$('#content_apply_sql').empty();
					$('#content_apply_sql').append('Error !');
					notifyMe('Nothing done !')
					$('#apply_sql_indicator').html('');
					document.title = sOriginalTitle;
					$('#content_apply_sql').slideDown('slow');
				}
			});		
		}
	}
	
	function notifyMe(notificationStr) {
		console.warn('notifyMe');
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
		$('#bt_content_php').attr("disabled", true);
		$('#content_php').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking the consistency of the data model definition...');
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_model', 'rebuild_toolkit_env': bRefresh },
				function(data)
				{
					$('#content_php').empty();
					if (data == '')
					{
						$('#content_php').append('Ok, no problem detected.');
						$('#bt_content_php').attr("disabled", false);
					}
					else
					{
						$('#content_php').append(data);
						$('#content_php>div p>b').each( function() {
							var sClassName = $(this).html();
							$(this).parent().after('<h2 class="class_name">Class '+sClassName+'</h2>');					
							}
						);
						$('#bt_content_php').attr("disabled", false);
						$('#content_php>div').css( {'background':'transparent'} );					
					}
				}
		);		
	}
	
	function CheckDBSchema(bRefresh)
	{
		$('#bt_content_schema').attr("disabled", true);
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
						if($('#bt_up_code_and_db').length > 0){
							$("#bt_up_code_and_db").appendTo("#div_bt_compilation");
						}
					}
				}
		);		
	}
	
	function CheckDataSources(bRefresh)
	{
		console.warn('CheckDataSources');
		$('#content_datasources').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking data sources integrity...');
		$('#bt_content_datasources').attr("disabled", true);
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
						$('#bt_content_datasources').attr("disabled", false);			
				}
		);		
	}
	
	function FixDataSources()
	{
		console.warn('FixDataSources');
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
		console.warn('CheckHK');
		$('#content_hk').html('<img title=\"loading...\" src=\"../images/indicator.gif\" /> Checking hierarchical keys integrity...');
		$('#bt_content_hk').attr("disabled", true);
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
						$('#bt_content_hk').attr("disabled", false);			
				}
		);		
	}
	
	function BuildHK(bForce)
	{
		console.warn('BuildHK');
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
		console.warn('CheckDictionary');
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
		$('#bt_content_dictionary').attr("disabled", true);
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'check_dictionary', 'rebuild_toolkit_env': bRefresh, 'lang': sLang, 'modules': sModules },
				function(data)
				{
					$('#content_dictionary').empty();
					$('#content_dictionary').append(data);
						$('#bt_content_dictionary').attr("disabled", false);			
				}
		);		
	}
	
	function PrepareNewDictionary(bRefresh)
	{
		console.warn('PrepareNewDictionary');
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
		$('#bt_prepare_new_dictionary').attr("disabled", true);
		ajax_request = $.get(GetAbsoluteUrlAppRoot()+'toolkit/ajax.toolkit.php', { 'operation': 'prepare_new_dictionary', 'rebuild_toolkit_env': bRefresh, 'lang_code': sLangCode, 'lang_name': sLangName, 'lang_loc_name': sLangLocName },
				function(data)
				{
					$('#content_new_dictionary').empty();
					$('#content_new_dictionary').append(data);
					$('#bt_prepare_new_dictionary').attr("disabled", false);
				}
		);		
	}
JS
	);

	define('TOOLKITENV', 'toolkit');

	$oConfig = new Config(APPCONF.'production/'.ITOP_CONFIG_FILE);
	if ($oConfig->Get('source_dir') == '')
	{
		throw new Exception('Missing entry source_dir from the config file');
	}

	$oPanelIndex = PanelUIBlockFactory::MakeNeutral("")
		->SetIcon(Branding::GetCompactMainLogoAbsoluteUrl())
		->SetTitle('Data Model Toolkit');
	$oP->AddSubBlock($oPanelIndex);

	$oTabContainer = new TabContainer('tabbedContent', 'tab');
	$oPanelIndex->AddSubBlock($oTabContainer);

	//** Tab:tab_index */
	$oTabIndex = $oTabContainer->AddTab('index','Index');
	$oTabIndex->AddSubBlock(DisplayIndex());

	//** Tab:tab_0 */
	$oTabConsistency = $oTabContainer->AddTab('0','Data Model Consistency');
	$oTabConsistency->AddSubBlock(CheckConsistency());

	//** Tab:tab_1 */
	$oTabUpdate = $oTabContainer->AddTab('1','iTop update');
	$oTabUpdate->AddSubBlock(CheckDBSchema());

	//** Tab:tab_2 */
	$oTabIntegrity = $oTabContainer->AddTab('2','Data Integrity');
	$oTabIntegrity->AddSubBlock(CheckDataIntegrity());

	//** Tab:tab_3 */
	$oTabDictionary = $oTabContainer->AddTab('3','Translations / Dictionary');
	$oTabDictionary->AddSubBlock(CheckDictionary());
}
try
{
	//$sAppRoot = utils::GetAbsoluteUrlAppRoot();
	if (version_compare(ITOP_DESIGN_LATEST_VERSION , '3.0') < 0) {
		DisplayLegacy($oP);
	} else
	{
		Display($oP);
	}
}
catch(Exception $e)
{
	$oP->add("Error: ".$e->getMessage());
}

$oP->output();
?>
