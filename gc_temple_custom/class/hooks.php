<?php
/* Description:修改hooks
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('HOOKS') ){
    class HOOKS{
        private static $instance = null;

        public static function forge()
        {
            if( is_null( self::$instance ) ){
                self::$instance = new self;
            }
            return self::$instance;
        }


        private function __construct(){
            add_filter('yith_wcmv_skip_wc_clean_for_fields_array', array($this, 'modify_skip_wc_clean_for_array'));
        }

        
        public function modify_skip_wc_clean_for_array($fields_array)
        {
            $fields_array[] = 'live_iframe';
            return $fields_array;
        }
        
    
    }
    HOOKS::forge();
}