<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

global $modx;

$plugin_path = $modx->config['base_path'] . "assets/plugins/evocachemanager/";

include($plugin_path.'lang/english.inc.php');

if (file_exists($plugin_path.'lang/' . $modx->config['manager_language'] . '.inc.php')) {
    include($plugin_path.'lang/' . $modx->config['manager_language'] . '.inc.php');
}

$e = &$modx->Event;
if($e->name == 'OnManagerWelcomeHome') {
    $position = isset($position) ? $position : 20;
    $width = isset($width) ? $width : 12;

    $res = $modx->db->select('id', $modx->getFullTableName("site_content"), "cacheable = '1' AND type = 'document' AND published = '1' AND deleted = '0' AND privateweb = '0' AND privatemgr = '0'");
    $count_all_docs = $modx->db->getRecordCount($res);
    $count_cached_docs = 0;

    while ($line = $modx->db->getRow($res)) {
        if (count(glob(MODX_BASE_PATH."assets/cache/docid_" . $line['id'] . "*.pageCache.php")) > 0) {
            $count_cached_docs++;
        }
    }

    $perc = floor($count_cached_docs / $count_all_docs * 100);

    if ($perc <= 20) {
        $bar_color = '#d9534f';
    } elseif ($perc > 20 && $perc < 50) {
        $bar_color = '#f0ad4e';
    } elseif ($perc >= 50 && $perc < 90) {
        $bar_color = '#5bc0de';
    } else {
        $bar_color = '#5cb85c';
    }

    $widgets['managernote_widget'] = [
        'menuindex' => $position,
        'id' => 'evocachemanager_widget',
        'cols' => 'col-sm-'.$width,
        'icon' => 'fa-recycle',
        'title' => $ecm_lang['title'],
        'body' =>
            '<div class="card-body">
                <style type="text/css">
                    .progressbar_wrapper div{font:20px Arial;}
                </style>
                <div class="sectionBody">
                	<div style="margin-bottom: 0.5rem; width: 100%;">
						'.$ecm_lang['caching'].': <span id="ecm_perc">'.$perc.'</span>% (<span id="ecm_count_cached_docs">'.$count_cached_docs.'</span>/<span id="ecm_count_all_docs">'.$count_all_docs.'</span>). '.$ecm_lang['refresh_cache'].'
					</div>
					<div style="margin-bottom: 1rem; width: 100%;">
						<div id="ecm_progress" class="progress" style="height: 1.45rem">
							<div id="ecm_progress_bar" class="progress-bar" role="progressbar" style="background-color: '.($bar_color).'; width: '.$perc.'%;" aria-valuenow="'.$perc.'" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
					</div>
					<div style="width: 100%;">
						<button id="ecm_play_stop" type="button" class="btn btn-sm btn-success" onclick="doCache();">
							<i class="fa fa-play" aria-hidden="true"></i> <span>'.$ecm_lang['create'].'</span>
						</button>
						<button type="button" class="btn btn-sm btn-danger" onclick="modx.popup({url:\'index.php?a=26\', title:\''.$ecm_lang['clear'].'\', icon: \'fa-recycle\', iframe: \'ajax\', selector: \'.tab-page>.container\', position: \'right top\', width: \'auto\', maxheight: \'50%\', wrap: \'body\' })">
							<i class="fa fa-trash" aria-hidden="true"></i> '.$ecm_lang['clear'].'
						</button>
					</div>
				</div>
				<style>
					.progress-bar.active {
						-webkit-animation: progress-bar-stripes 1s linear infinite;
						-o-animation: progress-bar-stripes 1s linear infinite;
						animation: progress-bar-stripes 1s linear infinite;
					}
				</style>
                <script type="text/javascript">
					var doCacheVal = false;
					
					function doCache() {
						doCacheVal = !doCacheVal;
						if (doCacheVal) {
							$("#ecm_play_stop > span").text("Остановить");
							$("#ecm_play_stop > i").removeClass("fa-play");
							$("#ecm_play_stop > i").addClass("fa-stop");
							$("#ecm_progress_bar").addClass("progress-bar-striped");
							$("#ecm_progress_bar").addClass("active");
							getCache();
						} else {
							$("#ecm_play_stop > span").text("'.$ecm_lang['create'].'");
							$("#ecm_play_stop > i").addClass("fa-play");
							$("#ecm_play_stop > i").removeClass("fa-stop");
							$("#ecm_progress_bar").removeClass("active");
							$("#ecm_progress_bar").removeClass("progress-bar-striped");
						}
					}
					
					function getCache() {
						if (doCacheVal) {
							$.ajax({
							url: "/assets/plugins/evocachemanager/ajaxevocachemanager.php",
							type: "POST",
							success: function(res) {
								if (res) {
									$("#ecm_perc").text(res.perc);
									$("#ecm_count_cached_docs").text(res.count_cached_docs);
									$("#ecm_count_all_docs").text(res.count_all_docs);
									if (res.perc <= 20) {
										bar_color = "#d9534f";
									} else if (res.perc > 20 && res.perc < 50) {
										bar_color = "#f0ad4e";
									} else if (res.perc >= 50 && res.perc < 90) {
										bar_color = "#5bc0de";
									} else {
										bar_color = "#5cb85c";
									}
									$("#ecm_progress_bar").css({"width": res.perc+"%", "background-color": bar_color});
									getCache();
								}
							}
						});
						}
					}
                </script>
            </div>'
    ];

    $e->output(serialize($widgets));
}