<?php
/**
 * Utility to export attachments of an iTop instance.
 * Launch without parameters to get usage.
 *
 * @since 3.1.1 N°7287 script creation
 */
/** @noinspection PhpComposerExtensionStubsInspection */


if (PHP_SAPI !== 'cli') {
    echo "This script can only run from CLI\n";
    exit(1);
}


const ITOP_INSTANCE_ROOT_URL = 'http://localhost/itop-31'; //FIXME

/**
 * iTop user should have the "REST Service User" profile, plus rights on the Attachment class
 */
const ITOP_API_USERNAME = 'rest'; //FIXME
const ITOP_API_PASSWORD = 'rest'; //FIXME



//--- CLI params
if ($argc < 3) {
    echo "Missing parameters !\n";
    echo "Usage: php " . $argv[0] . " <org_id> <item_class>\n";
    exit(2);
}

[$sCurrentScriptFullPath, $sOrgId, $sItemClass] = $argv;

$iOrgId = (int)$sOrgId;
if (!is_numeric($iOrgId) || $iOrgId <= 0) {
    echo "'org_id' must be a number greater than 0.\n";
    exit(3);
}

if (!is_string($sItemClass)) {
    echo "'item_class' must be a string.\n";
    exit(4);
}


//--- Attachments list query
$sAttachmentsQuery = "SELECT Attachment WHERE item_org_id=$iOrgId AND item_class='$sItemClass'";
$aJsonDataAttachmentsList = array(
        'operation' => 'core/get',
        'class' => 'Attachment',
        'key' => $sAttachmentsQuery,
        'output_fields' => 'id'
);

echo "Querying attachments...\n";
$aAttachmentsList = CallItopRestApi($aJsonDataAttachmentsList);

if (false === array_key_exists('objects', $aAttachmentsList)) {
    $sResponseMessage = $aAttachmentsList['message'];
    echo "  An error occurred: $sResponseMessage\n";
    exit(5);
}
if (is_null($aAttachmentsList['objects'])) {
    echo "  No attachment found\n";
    echo "  Query was: $sAttachmentsQuery\n";
    exit(6);
}
$iNumberOfAttachmentsFound = count($aAttachmentsList['objects']);
echo "  OK, $iNumberOfAttachmentsFound attachments found\n";


//--- Attachments export
$sExportDir = __DIR__ . DIRECTORY_SEPARATOR . $sItemClass;
if (file_exists($sExportDir)) {
    echo "WARNING Export directory exists $sExportDir\n";
} else {
    if (!mkdir($sExportDir) && !is_dir($sExportDir)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $sExportDir));
    }
}
$oOutputFile = fopen("Attachments_{$sOrgId}_{$sItemClass}.csv", 'w');
$aJsonDataAttachmentSingle = array(
        'operation' => 'core/get',
        'class' => 'Attachment',
        'output_fields' => 'contents,item_class,item_id,creation_date,user_id',
);


fputcsv($oOutputFile, ['contents_filename', 'contents_mimetype', 'item_class', 'item_id', 'creation_date', 'user_id']);
foreach ($aAttachmentsList['objects'] as $aAttachment) {
    $sAttachmentKey = $aAttachment['key'];
    echo 'Exporting Attachment:' . $sAttachmentKey . '...';
    $aJsonDataAttachmentSingle['key'] = $sAttachmentKey;
    $aAttachmentDetails = CallItopRestApi($aJsonDataAttachmentSingle);
    $aAttachmentFields = reset($aAttachmentDetails["objects"])['fields'];
    $sItemId = $aAttachmentFields['item_id'];
    $sContentFileName = $aAttachmentFields['contents']['filename'];

    fputcsv($oOutputFile, [
            $sContentFileName,
            $aAttachmentFields['contents']['mimetype'],
            $aAttachmentFields['item_class'],
            $sItemId,
            $aAttachmentFields['creation_date'],
            $aAttachmentFields['user_id']
    ]);

    $sContentBase64 = $aAttachmentFields['contents']['data'];
    $oContentBinary = base64_decode($sContentBase64);
    $sExportFileName = $sItemId . '__' . $sContentFileName;
    $sExportFileFullPath = $sExportDir . DIRECTORY_SEPARATOR . $sExportFileName;
    file_put_contents($sExportFileFullPath, $oContentBinary);

    echo " => $sExportFileName OK !\n";
}
fclose($oOutputFile);



/******************************************************************************/


/**
 * @uses curl_exec()
 */
function CallItopRestApi(array $aJsonData): array
{
    $sItopInstanceApiUrl = ITOP_INSTANCE_ROOT_URL . '/webservices/rest.php?version=1.3';

    $sUser = ITOP_API_USERNAME;
    $sPassword = ITOP_API_PASSWORD;

    $aData = [
            'version' => '1.3',
            'auth_user' => $sUser,
            'auth_pwd' => $sPassword,
            'json_data' => json_encode($aJsonData),
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $sItopInstanceApiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $iCurlErr = curl_errno($ch);
    $sCurlError = curl_error($ch);
    curl_close($ch);

    if ($iCurlErr !== 0) {
        throw new RuntimeException('CURL call error:' . $sCurlError);
    }

    return json_decode($response, true);
}