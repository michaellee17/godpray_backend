<?php
/* Description:代辦項目相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('TODO') ){
    class TODO{
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
            register_rest_route('gc', '/todo/index', array(
                'methods' => 'GET',
                'callback' => [$this,'get_index_todo_data'],
            ));
            register_rest_route('gc', '/todo/list', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_list_todo_data'],
            ));
            register_rest_route('gc', '/todo/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, 'get_single_todo_data'],
            ));
        }


        function get_single_todo_data($request)
        {
            $post_id = $request['id'];

            $post = get_post($post_id);

            if($post){
                $title = $post->post_title;
                $content =  wp_strip_all_tags($post->post_content);
                $thumbnail_id = get_post_meta($post_id, 'inner_image', true);
                $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'full')[0];
                $categories = get_the_category($post_id);
                $start = get_post_meta($post_id,'startdate',true);
                $end = get_post_meta($post_id,'enddate',true);


                // usort($categories, function($a, $b) {
                //     return $a->term_id - $b->term_id;
                // });

                // $max_term_id = end($categories)->term_id;

                // $slug = end($categories)->slug;

                // $target_term = get_term_by('slug', $slug, 'product_cat');
                
                // $target_term_id = $target_term->term_id;
              
                // $args = array(
                //     'post_type' => 'product',
                //     'posts_per_page' => -1,
                //     'tax_query' => array(
                //         array(
                //             'taxonomy' => 'product_cat',
                //             'field' => 'term_id',
                //             'terms' => $target_term_id
                //         ),
                //     ),
                // );

                // $products = get_posts($args);

                $related_ids = get_post_meta($post_id,'related_products',false);

                $formatted_products = array();

                foreach ($related_ids as $id) {
                    $product = get_post($id);
                    $product_id = $product->ID;
                    $product_thumbnail = get_the_post_thumbnail_url($product->ID, 'full');
                    $product_name = $product->post_title;
                    $product_price = get_post_meta($product->ID, '_price', true);
                    $product_excerpt = get_the_excerpt($product->ID);

                    $formatted_product = array(
                        'id' => $product_id,
                        'thumbnail' => $product_thumbnail,
                        'name' => $product_name,
                        'price' => $product_price,
                        'excerpt' => $product_excerpt
                    );
        
                    $formatted_products[] = $formatted_product;
                }

                $post_data = array(
                    'title' => $title,
                    'content' => $content,
                    'thumbnail' => $thumbnail,
                    'start' => $start,
                    'end' =>$end
                );

                return array(
                    'data' => $post_data,
                    'products' => $formatted_products
                );
            }
        }


        function get_all_list_todo_data($request)
        {
            $params = $request->get_params();
            $limit = isset($params['limit']) ? absint($params['limit']) : 8;
            $page = isset($params['page']) ? absint($params['page']) : 1;
        
            $args = array(
                'post_type' => 'post',      
                'category_name' => 'todo',  
                'posts_per_page' => $limit, 
                'paged' => $page,
            );
        
            $query = new WP_Query($args);
        
            $data = array();
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
        
                    $post_id = get_the_ID();
                    $title = get_the_title();
                    $content = wp_strip_all_tags(get_the_content());
                    $image = get_the_post_thumbnail_url($post_id, 'full');
                    $start = get_post_meta($post_id,'startdate',true);
                    $end = get_post_meta($post_id,'enddate',true);
                    if (!empty($title) && !empty($content) && !empty($image)) {
                        $todo_data = array(
                            'post_id' => $post_id,
                            'title' => $title,
                            'content' => $content,
                            'image' => $image,
                            'start' => $start,
                            'end' => $end
                        );
                        $data[] = $todo_data;
                    }
                }
        
                wp_reset_postdata();
            }
        
            $count = $query->found_posts;
        
            // $start = ($page - 1) * $limit;

            // $data = array_slice($data, $start, $limit);
            
            
            return array(
                'data' => $data,
                'count' => $count
            );
        }

        function get_index_todo_data() 
        {
            $args = array(
                'post_type' => 'todo',
                'meta_key' => 'order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
            );
        
            $todo_query = new WP_Query($args);
            $todoData = array();
        
            if ($todo_query->have_posts()) {
                while ($todo_query->have_posts()) {
                        $todo_query->the_post();
                        $todo_id = get_the_ID();
                        $post_id = get_post_meta($todo_id,'related_todo_post',true);
                        $title = get_the_title($post_id);
                        $image = get_the_post_thumbnail_url($post_id, 'full');
        
                        $todoData[] = array(
                            'id' => $post_id,
                            'title' => $title,
                            'image' => $image
                        );
                }
            }
        
            return array(
                'todo' => $todoData,
            );
        }
    }
    TODO::forge();
}