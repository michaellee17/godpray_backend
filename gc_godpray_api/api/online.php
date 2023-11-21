<?php
/* Description:線上客服相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('ONLINE') ){
    class ONLINE{
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
            register_rest_route('gc', '/online/service', array(
                'methods' => 'POST',
                'callback' => [$this,'set_customer_form'],
            ));
        }


        function set_customer_form($request) {

           $data = $request['data'];

           foreach ($data as $item) {
              if($item == ''){
                return wp_send_json_error("資料缺失");
              }
           }

           $name = sanitize_text_field($data['name']);

           $phone = sanitize_text_field($data['phone']);

           $email = sanitize_text_field($data['email']);

           $temple = sanitize_text_field($data['temple']);

           $subject = sanitize_text_field($data['subject']);

           $content = sanitize_text_field($data['content']);

           $entry_data = array(
                'form_id' => 2,
                'status' => 'active', 
                'date_created' => current_time('mysql'), 	
		    );

            $entry_id = GFAPI::add_entry($entry_data);

            if ($entry_id) {
                gform_update_meta($entry_id, '1', $name);
                gform_update_meta($entry_id, '3', $phone);
                gform_update_meta($entry_id, '4', $email);
                gform_update_meta($entry_id, '5', $temple);
                gform_update_meta($entry_id, '6', $subject);
                gform_update_meta($entry_id, '7', $content);
    
                wp_send_json(array('success' => true));
            } else {
                wp_send_json(array('success' => false));
            }
        }
    }
    ONLINE::forge();
}