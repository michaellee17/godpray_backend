<?php
/* Description:取得QA列表
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('CULTURE') ){
    class CULTURE{
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
            register_rest_route('gc', '/culture', array(
                'methods' => 'GET',
                'callback' => [$this,'get_culture'],
            ));
            register_rest_route('gc', '/culture/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, 'get_single_culture_data'],
            ));
        }


        function get_single_culture_data($request)
        {   
            if(empty($request['id'])){
                return wp_send_json_error("資料缺失");
            }

            $product_id = sanitize_text_field($request['id']);

            if($product_id){
                $product_categories = wp_get_post_terms($product_id, 'product_cat');
                $product = wc_get_product($product_id);
                $last_category_slug = end($product_categories)->slug;
                $last_category_name = end($product_categories)->name;
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $data = array(
                    'id' =>  $product_id,
                    'title' => get_post_field('post_title', $product_id),
                    'date' => get_the_date('', $product_id),
                    'image' =>  get_the_post_thumbnail_url($product_id, 'full'),
                    'content' => get_the_content(null,null,$product_id),
                    'category_slug' => $last_category_slug,
                    'category_name' => $last_category_name,
                    'price' => $regular_price,
                    'sale' => $sale_price,
                    'excerpt' => get_the_excerpt($product_id),
                );
                
                $similar_products = $this->get_similar_products($last_category_slug, $product_id);

                $gallery_images = get_post_meta($product_id, '_product_image_gallery', true);

                if (!empty($gallery_images)) {
                    $gallery_image_urls = array(get_the_post_thumbnail_url($product_id, 'full'));
                    $gallery_image_ids = explode(',', $gallery_images);
                    foreach ($gallery_image_ids as $image_id) {
                        $gallery_image_urls[] = wp_get_attachment_url($image_id);
                    }
        
                    $data['gallery'] = $gallery_image_urls;
                }
            }

            $parent_category_id = get_term_by('slug', 'cultural_product', 'product_cat')->term_id;

            $categories = $this->get_subcategories_recursive($parent_category_id);

            return array(
                'data' => $data,
                'type' => $categories,
                'relative' => $similar_products,
            );
        }


        function get_culture($request)
        {   
            $params = $request->get_params();
            $type = isset($params['type']) ? sanitize_text_field($params['type']) : 'cultural_product';
            $limit = isset($params['limit']) ? sanitize_text_field($params['limit']) : '';
            $page = isset($params['page']) ? sanitize_text_field($params['page']) : '';
            $order = isset($params['order']) ? sanitize_text_field($params['order']) : '';
            $parent_category_id = get_term_by('slug', 'cultural_product', 'product_cat')->term_id;

            $categories = $this->get_subcategories_recursive($parent_category_id);

            $args = array(
                'post_type' => 'product', 
                'posts_per_page' => $limit,
                'paged' => $page,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat', 
                        'field' => 'slug', 
                        'terms' => $type, 
                    ),
                ),
            );

            if ($order === 'priceDESC') {
                $args['orderby'] = 'meta_value_num'; 
                $args['meta_key'] = '_price'; 
                $args['order'] = 'DESC'; 
            } elseif ($order === 'priceASC') {
                $args['orderby'] = 'meta_value_num'; 
                $args['meta_key'] = '_price'; 
                $args['order'] = 'ASC'; 
            } elseif ($order === 'latest') {
                $args['orderby'] = 'date'; 
                $args['order'] = 'DESC'; 
            } else {
                $args['orderby'] = 'menu_order'; 
                $args['order'] = 'ASC'; 
            }
            
            
            $query = new WP_Query($args);
            
            $products = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());

                    $image_id = $product->get_image_id();
                    $image_url = wp_get_attachment_image_src($image_id, 'full')[0];
                    $product_categories = wp_get_post_terms(get_the_ID(), 'product_cat');
                    $last_category_name = end($product_categories)->name;
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $regular_price,
                        'sale' => $sale_price,
                        'image' => $image_url,
                        'category' => $last_category_name
                    );
                }
            }

            $total_products = $query->found_posts;
            
            wp_reset_postdata(); 
            
            return array(
                'products' => $products,
                'count' => $total_products,
                'type' => $categories
            );
        }


        function get_subcategories_recursive($parent_id) 
        {
            $subcategories = get_terms(
                array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'parent' => $parent_id,
                )
            );
        
            $categories = array();
        
            foreach ($subcategories as $subcategory) {
                $categories[] = array(
                    'id' => $subcategory->term_id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'children' => $this->get_subcategories_recursive($subcategory->term_id), 
                );
            }
        
            return $categories;
        }


        function get_similar_products($last_category_slug, $current_product_id) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $last_category_slug,
                    ),
                ),
                'post__not_in' => array($current_product_id), 
            );
        
            $query = new WP_Query($args);
        
            $similar_products = array();
        
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product_id = get_the_ID();
                    $product = wc_get_product($product_id);

                    $category_term = get_term_by('slug', $last_category_slug, 'product_cat');
                    $category_name = $category_term->name;
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $image_id = $product->get_image_id();
                    $image_url = wp_get_attachment_image_src($image_id, 'full')[0];
        
                    $similar_products[] = array(
                        'id' => $product_id,
                        'title' => get_the_title(),
                        'category_name' => $category_name,
                        'price' => $regular_price,
                        'sale' => $sale_price,
                        'image' => $image_url,
                    );
                }
            }
        
            wp_reset_postdata();
        
            return $similar_products;
        }
        
    }
    CULTURE::forge();
}