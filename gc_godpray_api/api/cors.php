<?php
/* Description:網站來源限制
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('CORS') ){
    class CORS{
        private static $instance = null;

        public static function forge()
        {
            if( is_null( self::$instance ) ){
                self::$instance = new self;
            }
            return self::$instance;
        }


        private function __construct(){
            add_action('init', [$this,'handle_preflight']);
        }


        function handle_preflight() {
            $allowed_urls = constant('allowed_urls');
            $origin = get_http_origin();
            if (in_array($origin,$allowed_urls)) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
                header("Access-Control-Allow-Credentials: true");
                header('Access-Control-Allow-Headers: Origin, X-Requested-With, X-WP-Nonce, Content-Type, Accept, Authorization');
                if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
                    status_header(200);
                    exit();
                }
            }
        }
    
    }
    CORS::forge();
}