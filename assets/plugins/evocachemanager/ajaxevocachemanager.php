<?php
if (!isset($_POST["fun"])){
    die('What are you doing? Get out of here!');
}

header('Content-type: application/json');

$fun = (string)$_POST["fun"];
$part = isset($_POST["part"]) ? (int)$_POST["part"] : 2;

define('MODX_API_MODE', true);
include_once(dirname(__FILE__)."/../../../index.php");

$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}

$modx->invokeEvent("OnWebPageInit");

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')){
    $modx->sendRedirect($modx->config['site_url']);
}

set_time_limit(600);
error_reporting(0);
ini_set('display_errors','Off');

if ($fun === 'get' || $fun === 'init') {
    $res = $modx->db->select('id', $modx->getFullTableName("site_content"), "cacheable = '1' AND type = 'document' AND published = '1' AND template <> '0' AND deleted = '0' AND privateweb = '0' AND privatemgr = '0'");
    $count_all_docs = $modx->db->getRecordCount($res);
    $count_cached_docs = 0;

    $do = 0;

    while ($line = $modx->db->getRow($res)) {
        if (count(glob(MODX_BASE_PATH."assets/cache/docid_" . $line['id'] . "*.pageCache.php")) > 0) {
            $count_cached_docs++;
        } elseif (($do < $part) && ($fun === 'get')) {
            $count_cached_docs++;
            file_get_contents($modx->makeUrl($line['id'], '', '', 'full'));
            $do++;
        }
    }

    $perc = floor($count_cached_docs / $count_all_docs * 100);

    echo json_encode(array(
        'count_cached_docs' => $count_cached_docs,
        'count_all_docs' => $count_all_docs,
        'perc' => $perc,
        'do' => !!($count_cached_docs < $count_all_docs),
    ));
}

if ($fun === 'clear') {
    $modx->clearCache('full');
    echo json_encode(array(
        'result' => true,
    ));
}
