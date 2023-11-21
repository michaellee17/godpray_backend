<?php
/* Plugin Name:創意數位客製前台API
 * Description:網站專屬客製前台API
 * Version: 2023.08.22
 * Author:創意數位工作團隊
 * Author URI: http://www.gcreate.com.tw
 * 
 * GC_michael:2023-08-22 外掛建置，api串接測試
 * GC_michael:2023-09-01 廟宇api
 * GC_michael:2023-09-01 搜尋api
 * GC_michael:2023-09-07 最新活動api
 * GC_michael:2023-09-10 主神api
 * GC_michael:2023-09-14 搜尋api分化為篩選跟搜尋
 * GC_michael:2023-10-19 代辦項目api
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_frontEnd', 100);
function init_frontEnd()
{
    include_once 'api/cors.php';
    include_once 'api/product.php';
    include_once 'api/slider.php';
    include_once 'api/temple.php';
    include_once 'api/live.php';
    include_once 'api/recommand.php';
    include_once 'api/latest.php';
    include_once 'api/god.php';
    include_once 'api/member.php';
    include_once 'api/order.php';
    include_once 'api/question.php';
    include_once 'api/culture.php';
    include_once 'api/cart.php';
    include_once 'api/todo.php';
    include_once 'api/online.php';
}
