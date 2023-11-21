<?php
/* Description:取得主神列表
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('GODS') ){
    class GODS{
        private static $instance = null;

        public static function forge()
        {
            if( is_null( self::$instance ) ){
                self::$instance = new self;
            }
            return self::$instance;
        }


        private function __construct(){
            add_action('rest_api_init', [$this,'register_custom_api_endpoint']);
        }


        function register_custom_api_endpoint($rest) {
            register_rest_route('gc', '/god', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_god'],
            ));
        }


        function get_all_god()
        {
            $data = esc_attr(get_option('main_gods'));

            return $data;
        }
    }
    GODS::forge();
}