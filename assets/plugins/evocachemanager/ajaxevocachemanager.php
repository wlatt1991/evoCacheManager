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

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')){
    $modx->sendRedirect($modx->config['site_url']);
}

set_time_limit(600);
error_reporting(0);
ini_set('display_errors','Off');

if ($fun === 'get' || $fun === 'init') {
    $cache_folder = MODX_BASE_PATH.$modx->getCacheFolder();
    $res = $modx->db->select('id', $modx->getFullTableName("site_content"), "cacheable = '1' AND type = 'document' AND published = '1' AND template <> '0' AND deleted = '0' AND privateweb = '0' AND privatemgr = '0'");

    $all_docs = array();
    while ($line = $modx->db->getRow($res)) {
        $all_docs[] = $line['id'];
    }

    $count_all_docs = count($all_docs);

    $files = glob($cache_folder.'docid_*');

    function file_map($str) {
        $str = explode($cache_folder.'docid_', $str)[1];
        $str = explode('.', $str)[0];
        return explode('_', $str)[0];
    }

    $files = array_map('file_map', $files);

    $no_cached_docs = array_diff($all_docs, $files);

    $count_cached_docs = $count_all_docs - count($no_cached_docs);

    if ($fun === 'get') {
        while($part > 0 && $id = array_pop($no_cached_docs)) {
            $part--;
            $count_cached_docs++;
            file_get_contents($modx->makeUrl($id, '', '', 'full'));
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
