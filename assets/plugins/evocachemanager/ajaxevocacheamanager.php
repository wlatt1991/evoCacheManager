<?php
/* -----------------------------------------------------------------------------
* Module: Cache All Pages
* -----------------------------------------------------------------------------
* @author       AKots - e-kao.ru
* @version      1.1
* @date         23/01/2011
*/

require_once('../../../manager/includes/protect.inc.php');
if (file_exists('../../../vendor/autoload.php')) {
	require_once('../../../vendor/autoload.php');
}

set_time_limit (600);

$database_type = "";
$database_server = "";
$database_user = "";
$database_password = "";
$dbase = "";
$table_prefix = "";
$base_url = "";
$base_path = "";
if (isset($_SERVER['SCRIPT_NAME']))
	$_SERVER['SCRIPT_NAME']=str_replace('assets/modules/evocacheallpages/ajaxevocacheallpages', 'index', $_SERVER['SCRIPT_NAME']);
if (isset($_SERVER['PHP_SELF']))
	$_SERVER['PHP_SELF']=str_replace('assets/modules/evocacheallpages/ajaxevocacheallpages', 'index', $_SERVER['PHP_SELF']);
if (!$rt = @include_once "../../../manager/includes/config.inc.php")
	exit(100);
define('MODX_API_MODE', true);
include_once(MODX_MANAGER_PATH.'/includes/document.parser.class.inc.php');
$modx = new DocumentParser;
$modx->db->connect();
$modx->getSettings();
startCMSSession();
if (isset ($_SESSION['mgrUsrConfigSet'])) {
	$temp_mgrUsrConfigSet=$_SESSION['mgrUsrConfigSet'];
	unset($_SESSION['mgrUsrConfigSet']);
}
if (isset ($_SESSION['mgrInternalKey'])) {
	$temp_mgrInternalKey=$_SESSION['mgrInternalKey'];
	unset($_SESSION['mgrInternalKey']);
}
if (isset ($_SESSION['mgrValidated'])) {
	$temp_mgrValidated=$_SESSION['mgrValidated'];
	unset($_SESSION['mgrValidated']);
}

if(version_compare(phpversion(), "4.3.0")>=0)
    set_include_path(get_include_path() . PATH_SEPARATOR . MODX_BASE_PATH);
else
    ini_set("include_path", MODX_BASE_PATH);
	
function saveCache($currentId) {
	global $modx;
	$modx->documentIdentifier=(int)$currentId;
	$modx->documentObject= $modx->getDocumentObject('id', $modx->documentIdentifier);
	if (!$modx->documentObject['template'])
		$modx->documentContent= "[*content*]";
	else {
		$result= $modx->db->query("SELECT `content` FROM " . $modx->getFullTableName("site_templates") . " WHERE " . $modx->getFullTableName("site_templates") . ".`id` = '" . $modx->documentObject['template'] . "';");
		$row=$modx->db->getRow($result);
		$modx->documentContent= $row['content'];
	}
	$modx->invokeEvent("OnLoadWebDocument");
	$modx->documentContent= $modx->parseDocumentSource($modx->documentContent);
	if (!empty($modx->sjscripts)) $modx->documentObject['__MODxSJScripts__'] = $modx->sjscripts;
	if (!empty($modx->jscripts)) $modx->documentObject['__MODxJScripts__'] = $modx->jscripts;
	$modx->invokeEvent("OnBeforeSaveWebPageCache");
	if ($fp= @ fopen(MODX_BASE_PATH . "assets/cache/docid_" . $modx->documentIdentifier . ".pageCache.php", "w")) {
		$sql= "SELECT document_group FROM " . $modx->getFullTableName("document_groups") . " WHERE document='" . $modx->documentIdentifier . "'";
		$docGroups= $modx->db->getColumn("document_group", $sql);
		if (is_array($docGroups)) $modx->documentObject['__MODxDocGroups__'] = implode(",", $docGroups);
		$docObjSerial= serialize($modx->documentObject);
		$cacheContent= $docObjSerial . "<!--__MODxCacheSpliter__-->" . $modx->documentContent;
		fputs($fp, "<?php die('Unauthorized access.'); ?>$cacheContent");
		fclose($fp);
	}
}

$output=100;	
if (isset($_POST["progress"])){
	$progress=(int) $_POST["progress"];
	if ($progress==0) {
       if ($result=$modx->db->select('id', $modx->getFullTableName("site_content"), "cacheable = '1' AND type = 'document' AND published = '1' AND deleted = '0' AND privateweb = '0' AND privatemgr = '0'")) {
			$_SESSION['cap']=array();
			$_SESSION['capCurrent']=0;
			$_SESSION['capPercent']=0;
			while ($line=$modx->db->getRow($result)) {
				if (!file_exists(MODX_BASE_PATH."assets/cache/docid_" . $line['id'] . ".pageCache.php")) {
					$_SESSION['cap'][]=$line['id'];
				}
			}
		}
	} 
	if (isset($_SESSION['capPercent']) && isset($_SESSION['cap']) && isset($_SESSION['capCurrent']) && $progress==$_SESSION['capPercent']) {
		$capCounter=count($_SESSION['cap']);
		if ($capCounter>0) {
			$capMin=$_SESSION['capCurrent'];
			$capMax=$capMin+($capCounter>100?round($capCounter/100):1)-1;
			If (ceil(($capMax+1)/$capCounter*100)>=100)
				$capMax=$capCounter-1;
			for ($i=$capMin; $i<=$capMax; $i++) {
				saveCache($_SESSION['cap'][$i]);
			}
			$_SESSION['capCurrent']=$capMax+1;
			$output=ceil($_SESSION['capCurrent']/$capCounter*100);
			$_SESSION['capPercent']=$output;
		} else
			$output=100;
	}
}
if ($output>=100) {
	$output=100;
	unset($_SESSION['capPercent']);
	unset($_SESSION['cap']);
	unset($_SESSION['capCurrent']);
}
if (isset ($temp_mgrUsrConfigSet))
	$_SESSION['mgrUsrConfigSet']=$temp_mgrUsrConfigSet;
if (isset ($temp_mgrInternalKey))
	$_SESSION['mgrInternalKey']=$temp_mgrInternalKey;
if (isset ($temp_mgrValidated))
	$_SESSION['mgrValidated']=$temp_mgrValidated;
echo $output;
?>
