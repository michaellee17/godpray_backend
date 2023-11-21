<?php
/* Description:首頁banner相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('SLIDER') ){
    class SLIDER{
        private static $instance = null;


        public static function forge()
        {
            if( is_null( self::$instance ) ){
                self::$instance = new self;
            }
            return self::$instance;
        }


        private function __construct()
        {
            add_action('rest_api_init', [$this,'register_custom_api_endpoint']);
        }


        function register_custom_api_endpoint($rest) 
        {
            register_rest_route('gc', '/get-sliders', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_sliders_data'],
            ));
        }


        function get_all_sliders_data() 
        {
            $args = array(
                'post_type' => 'slider', 
                'posts_per_page' => -1,
                'meta_key' => 'sliderorder',  
                'orderby' => 'meta_value_num', 
                'order' => 'ASC', 
            );
        
            $sliders_query = new WP_Query($args);
            $sliders = array();
        
            if ($sliders_query->have_posts()) {
                while ($sliders_query->have_posts()) {
                    $sliders_query->the_post();
                    $slider = array(
                        'id' => get_the_ID(),
                        'title' => get_post_meta(get_the_ID(), 'slidertitle', true),
                        'subtitle' => get_post_meta(get_the_ID(), 'slidersubtitle', true),
                        'link' => get_post_meta(get_the_ID(), 'sliderlink', true),
                        'image' => wp_get_attachment_image_src(get_post_meta(get_the_ID(), 'sliderimage', true), 'full')[0],
                        'is_live' => get_post_meta(get_the_ID(), 'is_live', true),
                        'is_blank' => get_post_meta(get_the_ID(), 'is_blank', true),
                    );
        
                    $sliders[] = $slider; 
                }
            }
            
            return array(
                'sliders' => $sliders,
            );
        }
    }
    SLIDER::forge();
}