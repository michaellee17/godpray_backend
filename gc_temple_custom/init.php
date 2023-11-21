<?php
/* Plugin Name:創意數位廟宇設定
 * Description:網站專屬廟宇設定
 * Version: 2023.09.06
 * Author:創意數位工作團隊
 * Author URI: http://www.gcreate.com.tw
 * 
 * GC_michael:2023-09-06 列表建置
 */
 
if( !defined('ABSPATH')){
    exit;
}

add_action( 'plugins_loaded', 'init_temple', 100 );
function init_temple(){
    include_once 'class/hooks.php';
    include_once 'class/god.php';
    include_once 'class/Vendors.php';
    include_once 'functions.php';
}