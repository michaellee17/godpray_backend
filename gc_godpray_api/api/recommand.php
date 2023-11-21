<?php
/* Description:推薦廟宇相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('RECOMMAND') ){
    class RECOMMAND{
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
            register_rest_route('gc', '/recommand', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_recommand_data'],
            ));
        }

        function get_all_recommand_data() {
            $args = array(
                'post_type' => 'recommand',
                'meta_key' => 'recommand_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
            );
        
            $recommand_query = new WP_Query($args);
            $recommandData = array();
        
            if ($recommand_query->have_posts()) {
                while ($recommand_query->have_posts()) {
                    $recommand_query->the_post();
                    $term_id = get_post_meta(get_the_ID(), 'recommand_temple', true);
        
                    if ($term_id) {
                        $term = get_term($term_id, 'yith_shop_vendor');
                        $title = get_term_meta($term_id, 'temple_name', true);
                        $location = get_term_meta($term_id, 'city', true) . get_term_meta($term_id, 'area', true);
                        $image = wp_get_attachment_image_src(get_term_meta($term_id, 'header_image', true), 'full')[0];
        
                        $recommandData[] = array(
                            'id' => $term_id,
                            'title' => $title,
                            'area' => $location,
                            'image' => $image
                        );
                    }
                }
            }
        
            return array(
                'recommand' => $recommandData,
            );
        }
    }
    RECOMMAND::forge();
}