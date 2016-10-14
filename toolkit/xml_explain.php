<?php
/**
 * Created by PhpStorm.
 * User: Romain
 * Date: 22/01/2016
 * Time: 14:32
 */

use Combodo\iTop\DesignDocument;
use Combodo\iTop\DesignElement;

require_once('../approot.inc.php');

function DisplayElement(DesignElement $oNode, $iDepth = 0)
{
	$sIndent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $iDepth);

	$aAttributes = array();
	foreach($oNode->attributes as $sAttrName => $oAttrNode)
	{
		$aAttributes[] = $sAttrName.'="'.htmlentities($oAttrNode->nodeValue, ENT_QUOTES, 'UTF-8').'"';
	}
	$sAttributes = count($aAttributes) ? ' '.implode(' ', $aAttributes) : '';

	$sText = trim($oNode->GetText());
	if ($sText == '')
	{
		echo $sIndent.'&lt;'.htmlentities($oNode->tagName, ENT_QUOTES, 'UTF-8').$sAttributes.'&gt;<br/>';
		foreach ($oNode->childNodes as $oChild)
		{
			if ($oChild instanceof DesignElement)
			{
				DisplayElement($oChild, $iDepth + 1);
			}
		}
		echo $sIndent.'&lt;/'.htmlentities($oNode->tagName, ENT_QUOTES, 'UTF-8').'&gt;<br/>';
	}
	else
	{
		echo $sIndent.'&lt;'.htmlentities($oNode->tagName, ENT_QUOTES, 'UTF-8').$sAttributes.'&gt;'.htmlentities($sText).'&lt;/'.htmlentities($oNode->tagName, ENT_QUOTES, 'UTF-8').'&gt;<br/>';
	}
}

//require_once(APPROOT.'setup/modelfactory.class.inc.php');
require_once(APPROOT.'core/designdocument.class.inc.php');

$oRef = new DesignDocument();
$oRef->load(APPROOT.'data/explain_ref.xml');
DisplayElement($oRef->documentElement);

$oDelta = new DesignDocument();
$oDelta->load(APPROOT.'data/explain_delta.xml');

$oValidation = new DesignDocument();
$oValidation->load(APPROOT.'data/explain_validation.xml');

DisplayElement($oDelta->documentElement);