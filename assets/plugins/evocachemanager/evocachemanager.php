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

    $widgets['managernote_widget'] = [
        'menuindex' => $position,
        'id' => 'evocachemanager_widget',
        'cols' => 'col-sm-'.$width,
        'icon' => 'fa-recycle',
        'title' => $ecm_lang['title'],
        'body' =>
            '<div class="card-body">
                <script type="text/javascript" src="'.MODX_SITE_URL.'assets/plugins/evocachemanager/progressbar.js"></script>
                <style type="text/css">
                    .progressbar_wrapper div{font:20px Arial;}
                </style>
                <div class="sectionBody">
                    <ul class="actionButtons" style="margin: 10px 20px">
                        <li id="Button2">
                            <a href="#" onclick="$(\'progressbar_0_dark\').setStyle(\'background\', \'#006\');ajaxCache(0);">
                                <i class="fa fa-plus" aria-hidden="true"></i> '.$ecm_lang['create'].'
                            </a>
                        </li>
                        <li id="Button1">
                            <a href="#" onclick="document.location.href=\'index.php?a=26\';">
                                <i class="fa fa-recycle" aria-hidden="true"></i> '.$ecm_lang['clear'].'
                            </a>
                        </li>
                    </ul>		
                    <div id="wrapper" style="margin: 10px 20px"></div>
                </div>
                <script type="text/javascript">
                var pb = new ProgressBar("0",{
                        \'width\':400,
                        \'height\':40
                    });
                
                $(\'wrapper\').appendChild(pb);
                
                function ajaxCache(progress) {
                    new Ajax(\''.MODX_SITE_URL.'assets/plugins/evocachemanager/ajaxevocachemanager.php\', {
                        method: \'post\',
                        postBody: Object.toQueryString({progress: progress}),
                        onComplete: nextCache
                    }).request();
                }
    
                function nextCache(result) {
                    pb.setValue(result);
                    if (result<100 && result>0) {
                        if (result>20) {$(\'progressbar_0_dark\').setStyle(\'background\', \'#008\');}
                        if (result>40) {$(\'progressbar_0_dark\').setStyle(\'background\', \'#00a\');}
                        if (result>60) {$(\'progressbar_0_dark\').setStyle(\'background\', \'#00c\');}
                        if (result>80) {$(\'progressbar_0_dark\').setStyle(\'background\', \'#00e\');}
                        ajaxCache(result)
                    } else {
                        $(\'progressbar_0_dark\').setStyle(\'background\', \'#d00\');
                    }
                }
                </script>
            </div>'
    ];

    $e->output(serialize($widgets));
}