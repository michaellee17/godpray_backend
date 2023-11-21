<?php
/* Description:直播相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('LIVE') ){
    class LIVE{
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
            register_rest_route('gc', '/live', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_live_data'],
            ));
        }
        function get_all_live_data() {
           
            $args = array(
                'post_type' => 'live', 
                'posts_per_page' => -1,
                'meta_key' => 'liveorder',  
                'orderby' => 'meta_value_num', 
                'order' => 'ASC', 
            );
        
            $live_query = new WP_Query($args);
            $livedata = array();
        
            if ($live_query->have_posts()) {
                while ($live_query->have_posts()) {
                    $live_query->the_post();
                    $live = array(
                        'id' => get_the_ID(),
                        'title' => get_post_meta(get_the_ID(), 'livetitle', true),
                        'link' => get_post_meta(get_the_ID(), 'livelink', true),
                    );
                    $livedata[] = $live;
                }
            }
            return array(
                'live' => $livedata,
            );
        }
    }
    LIVE::forge();
}