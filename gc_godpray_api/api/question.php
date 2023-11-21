<?php
/* Description:取得QA列表
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('QUESTION') ){
    class QUESTION{
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
            register_rest_route('gc', '/question', array(
                'methods' => 'GET',
                'callback' => [$this,'get_question'],
            ));
        }


        function get_question()
        {
            $args = array(
                'post_type' => 'question',
                'posts_per_page' => -1, 
                'orderby' => 'date', 
                'order' => 'ASC',
            );

            $query = new WP_Query($args); 

            $data = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $id = get_the_ID();
                    $title = get_the_title();
                    $question = get_post_meta($id,'question',true);
                    $answer = get_post_meta($id,'answer',true);

                    $data[] = array(
                        'id' => $id,
                        'title' => $title,
                        'question' => $question,
                        'answer' => $answer
                    );
                } 
            }  
            return $data;
        }
    }
    QUESTION::forge();
}