<?php
/* Description:最新活動相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('LATEST') ){
    class LATEST{
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
            register_rest_route('gc', '/latest', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_latest_data'],
            ));
            register_rest_route('gc', '/latest/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, 'get_single_latest_data'],
            ));
            register_rest_route('gc', '/latest/index', array(
                'methods' => 'GET',
                'callback' => [$this,'get_index_latest_data'],
            ));
            register_rest_route('gc', '/latest/type', array(
                'methods' => 'GET',
                'callback' => [$this,'get_latest_type'],
            ));
        }


        function get_latest_type() 
        {   
            global $wpdb;
            
            $sql1 = $wpdb->prepare("
                SELECT tt.term_id, t.name, t.slug
                FROM {$wpdb->term_taxonomy} AS tt
                LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
                WHERE tt.taxonomy = %s
            ", 'latest_type');
            
            //get_var取得單一結果，get_results取得多個結果
            $data = $wpdb->get_results($sql1, ARRAY_A); 

            return array(
                'type' => $data, 
            );
        }

        function get_all_latest_data($request) 
        {
            $params = $request->get_params();
            $latest_type = isset($params['latest_type']) ? sanitize_text_field($params['latest_type']) : '';
            $limit = isset($params['limit']) ? sanitize_text_field($params['limit']) : '';
            $page = isset($params['page']) ? sanitize_text_field($params['page']) : '';
           
            $term_id = 0; 

            if (!empty($latest_type)) {
                $term = get_term_by('slug', $latest_type, 'latest_type');
                $term_id = $term ? $term->term_id : 0;
            }

            $args = array(
                'post_type' => 'latest', 
                'posts_per_page' => $limit,
                'paged' => $page,
            );

            if (!empty($latest_type)) {
                $args['meta_query'] = array(
                    array(
                        'key' => 'latest_type_select',
                        'value' => $term_id,
                        'compare' => '=',
                    ),
                );
            }

            $latest_query = new WP_Query($args);
            $latestdata = array();
            $total = $latest_query->found_posts;

            if ($latest_query->have_posts()) {
                while ($latest_query->have_posts()) {
                    $latest_query->the_post();
                    $image_id = get_post_thumbnail_id();
                    $latest = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'image' =>  wp_get_attachment_image_src($image_id, 'full')[0],
                    );
                    $latestdata[] = $latest;
                }
            }
            
            return array(
                'latest' => $latestdata,
                'total' => $total,
            );
        }


        function get_single_latest_data($request)
        {   
            $latest_id = $request['id'];

            $latest_id = sanitize_text_field($latest_id);
            
            if($latest_id){
                $data = array(
                    'id' =>  $latest_id,
                    'title' => get_post_field('post_title', $latest_id),
                    'date' => get_the_date('', $latest_id),
                    'image' =>  get_the_post_thumbnail_url($latest_id, 'full'),
                    'content' => get_post_field('post_content', $latest_id),
                );

                //第三個參數設為false時不論一個或多個值都會回傳陣列
                $relate_id = get_post_meta($latest_id,'latest_relate',false);
                $relate_data = [];
                $counter = 0;
                if($relate_id !== []){
                    //有設定關聯則用關聯的最多五筆
                    foreach ($relate_id as $value) {
                        if ($counter >= 5) {
                            break; 
                        }
                        $relate_data[] = array(
                            'id' =>  $value,
                            'title' =>  get_post_field('post_title', $value),
                            'image' => get_the_post_thumbnail_url($value, 'full'),
                        );
                        $counter++; 
                    }
                }else{
                    //無設定關聯則抓最新的不包含自己
                    $args = array(
                        'post_type' => 'latest', 
                        'posts_per_page' => 5,
                        'orderby' => 'date', 
                        'order' => 'DESC',
                        'post__not_in' => array($latest_id),
                    );
                
                    $query = new WP_Query($args);
                
                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            $image_id = get_post_thumbnail_id();
                            $relate_data[] = array(
                                'id' => get_the_ID(),
                                'title' => get_the_title(),
                                'image' =>  wp_get_attachment_image_src($image_id, 'full')[0],
                            );
                        }
                        wp_reset_postdata();
                    }
                }

                $new_data = [];
                $args = array(
                    'post_type' => 'latest', 
                    'posts_per_page' => 5,
                    'orderby' => 'date', 
                    'order' => 'DESC',
                    'post__not_in' => array($latest_id),
                );
            
                $query = new WP_Query($args);
            
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $image_id = get_post_thumbnail_id();
                        $new_data[] = array(
                            'id' => get_the_ID(),
                            'title' => get_the_title(),
                            'image' =>  wp_get_attachment_image_src($image_id, 'full')[0],
                        );
                    }
                    wp_reset_postdata();
                }
                
                return array(
                    'latest' => $data,
                    'relate' => $relate_data,
                    'new' => $new_data
                );
            }
        }


        function get_index_latest_data($request) 
        {
            
            $args = array(
                'post_type' => 'latest', 
                'orderby' => 'date', 
                'order' => 'DESC',   
                'posts_per_page' => 4, 
            );

        
            $latest_query = new WP_Query($args);
            $latestdata = array();
            $total = $latest_query->found_posts;

            if ($latest_query->have_posts()) {
                while ($latest_query->have_posts()) {
                    $latest_query->the_post();
                    $image_id = get_post_thumbnail_id();
                    $latest = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'date' => get_the_date(),
                        'image' =>  wp_get_attachment_image_src($image_id, 'full')[0],
                    );
                    $latestdata[] = $latest;
                }
            }
            return array(
                'latest' => $latestdata,
            );
        }
    }
    LATEST::forge();
}