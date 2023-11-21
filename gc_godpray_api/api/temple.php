<?php
/* Description:廟宇相關api
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('TEMPLE') ){
    class TEMPLE{
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
            register_rest_route('gc', '/temple', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_temple_data'],
            ));
            register_rest_route('gc', '/temple/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, 'get_single_temple_data'],
            ));
            register_rest_route('gc', '/temple/search', array(
                'methods' => 'GET',
                'callback' => [$this,'get_all_temple_search_data'],
            ));
        }


        function get_all_temple_search_data($request) 
        {
            global $wpdb;
            $params = $request->get_params();
            $limit = isset($params['limit']) ? intval($params['limit']) : 10;
            $page = isset($params['page']) ? intval($params['page']) : 1;
            $city = isset($params['city']) ? sanitize_text_field($params['city']) : '';
            $god = isset($params['god']) ? sanitize_text_field($params['god']) : '';
            $text = isset($params['text']) ? sanitize_text_field($params['text']) : '';
           
            $offset = ($page - 1) * $limit;
           
            $sql = "SELECT term_id FROM wp_term_taxonomy where taxonomy = 'yith_shop_vendor' LIMIT $limit OFFSET $offset"; 
            $results = $wpdb->get_results($sql, ARRAY_A);
           
           
            $data = array(); 
            
            foreach ($results as $result) {
                $term_id = $result['term_id'];
                $name = get_term_meta($term_id,'temple_name',true);
                $main_god = get_term_meta($term_id,'main_god',true);
                $location = get_term_meta($term_id, 'city', true).get_term_meta($term_id, 'area', true);
                $address = get_term_meta($term_id,'location',true);
                $image = get_term_meta($term_id,'header_image',true);
                

                $cityMatch = empty($city) || stripos($location, $city) !== false;

                $textMatch = empty($text) || stripos($name, $text) !== false;

                $godMatch = empty($god) || stripos($main_god, $god) !== false;

                if ($cityMatch && $textMatch && $godMatch) {
                    $data[] = array(
                        'id' => $term_id,
                        'name' => $name,
                        'main_god' => $main_god,
                        'location' => $location,
                        'address' => $address,
                        'image_url' => wp_get_attachment_image_src($image, 'full')[0],
                    );
                }
            }
            
            return array(
                'data' => $data,
                'total' => count($data)
            );
        }


        function get_all_temple_data($request) {
            global $wpdb;
        
            $params = $request->get_params();
            $limit = isset($params['limit']) ? absint($params['limit']) : 12;
            $page = isset($params['page']) ? absint($params['page']) : 1;
            $city = isset($params['city']) ? sanitize_text_field($params['city']) : '';
            $god = isset($params['god']) ? sanitize_text_field($params['god']) : '';
            $offset = ($page - 1) * $limit;
        
            $sql = "SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'yith_shop_vendor'";
            $sql2 = "SELECT COUNT(*) FROM wp_term_taxonomy WHERE taxonomy = 'yith_shop_vendor'";
        
            if (!empty($city)) {
                $sql .= " AND term_id IN (SELECT term_id FROM wp_termmeta WHERE meta_key = 'city' AND meta_value = '$city')";
                $sql2 .= " AND term_id IN (SELECT term_id FROM wp_termmeta WHERE meta_key = 'city' AND meta_value = '$city')";
            }
        
            if (!empty($god)) {
                $sql .= " AND term_id IN (SELECT term_id FROM wp_termmeta WHERE meta_key = 'main_god' AND meta_value = '$god')";
                $sql2 .= " AND term_id IN (SELECT term_id FROM wp_termmeta WHERE meta_key = 'main_god' AND meta_value = '$god')";
            }
        
            $sql .= " LIMIT $limit OFFSET $offset";
        
            $results = $wpdb->get_results($sql, ARRAY_A);
            $total_count = $wpdb->get_var($sql2);
            $data = array();
        
            foreach ($results as $result) {
                $term_id = $result['term_id'];
                $name = get_term_meta($term_id, 'temple_name', true);
                $main_god = get_term_meta($term_id, 'main_god', true);
                $location = get_term_meta($term_id, 'city', true) . get_term_meta($term_id, 'area', true);
                $address = get_term_meta($term_id, 'location', true);
                $image = get_term_meta($term_id, 'header_image', true);
                $data[] = array(
                    'id' => $term_id,
                    'name' => $name,
                    'main_god' => $main_god,
                    'location' => $location,
                    'address' => $address,
                    'image_url' => wp_get_attachment_image_src($image, 'full')[0],
                );
            }
        
            return array(
                'data' => $data,
                'total' => $total_count,
            );
        }


        function get_single_temple_data($request) {
            
            global $wpdb;

            $term_id = $request['id'];
            
            if ($term_id) {
                $sql = $wpdb->prepare("
                    SELECT term_id
                    FROM $wpdb->terms
                     WHERE name = %s ", '點燈');

                $light_term_id = $wpdb->get_var($sql);
                if($light_term_id){
                    $sql = $wpdb->prepare("
                    SELECT object_id
                    FROM $wpdb->term_relationships
                    WHERE term_taxonomy_id = %d
                    AND object_id IN (
                        SELECT object_id
                        FROM $wpdb->term_relationships
                        WHERE term_taxonomy_id = %d
                    )", $term_id, $light_term_id);
            
                    $light_product_ids = $wpdb->get_col($sql);
                    $light_product_ids = $this->wrap_array_if_not($light_product_ids);

                    $light_products = array();

                    foreach ($light_product_ids as $product_id) {
                        $product = wc_get_product($product_id);
                        
                        if ($product) {
                            $light_products[] = array(
                                'id' => $product_id,
                                'name' =>  $product->get_name(),
                                'price' => $product->get_price(),
                                'image_url' => $product_image_url = wp_get_attachment_image_src($product->get_image_id(), 'full')[0],
                                
                            );
                        }
                    }
                }
                $sql = $wpdb->prepare("
                SELECT term_id
                FROM $wpdb->terms
                 WHERE name = %s ", '疏文');

                $shuwen_term_id = $wpdb->get_var($sql);
                
                if($shuwen_term_id){
                    $sql = $wpdb->prepare("
                    SELECT object_id
                    FROM $wpdb->term_relationships
                    WHERE term_taxonomy_id = %d
                    AND object_id IN (
                        SELECT object_id
                        FROM $wpdb->term_relationships
                        WHERE term_taxonomy_id = %d
                    )", $term_id, $shuwen_term_id);
            
                    $shuwen_product_ids = $wpdb->get_col($sql);
                    $shuwen_product_ids = $this->wrap_array_if_not($shuwen_product_ids);
                    $shuwen_products = array();

                    foreach ($shuwen_product_ids as $product_id) {
                        $product = wc_get_product($product_id);

                        if ($product) {
                            $shuwen_products[] = array(
                                'id' => $product_id,
                                'name' =>  $product->get_name(),
                                'price' => $product->get_price(),
                                'image_url' => $product_image_url = wp_get_attachment_image_src($product->get_image_id(), 'full')[0],
                            );
                        }
                    }
                }

                $sql = $wpdb->prepare("
                SELECT term_id
                FROM $wpdb->terms
                 WHERE name = %s ", '代辦項目');

                $todo_term_id =  $wpdb->get_var($sql);

                if($todo_term_id){
                    $sql = $wpdb->prepare("
                    SELECT object_id
                    FROM $wpdb->term_relationships
                    WHERE term_taxonomy_id = %d
                    AND object_id IN (
                        SELECT object_id
                        FROM $wpdb->term_relationships
                        WHERE term_taxonomy_id = %d
                    )", $term_id, $todo_term_id);
            
                    $todo_product_ids = $wpdb->get_col($sql);
                    $todo_product_ids = $this->wrap_array_if_not($todo_product_ids);
                    $todo_products = array();

                    foreach ($todo_product_ids as $product_id) {
                        $product = wc_get_product($product_id);
                        $post = get_post($product_id);
                        if ($product) {
                            $todo_products[] = array(
                                'id' => $product_id,
                                'name' =>  $product->get_name(),
                                'price' => $product->get_price(),
                                'image_url' => $product_image_url = wp_get_attachment_image_src($product->get_image_id(), 'full')[0],
                                'content' => $post->post_content,
                            );
                        }
                    }
                }
 

                $data = array(
                    'id' =>  $term_id,
                    'name' => get_term_meta($term_id,'temple_name',true),
                    'main_god' => get_term_meta($term_id,'main_god',true),
                    'location' => get_term_meta($term_id, 'city', true).get_term_meta($term_id, 'area', true),
                    'address' => get_term_meta($term_id,'location',true),
                    'phone' => get_term_meta($term_id,'telephone',true),
                    'info' => get_term_meta($term_id,'info',true),
                    'cover' => wp_get_attachment_image_src(get_term_meta($term_id,'avatar',true), 'full')[0],
                    'image_url' => wp_get_attachment_image_src(get_term_meta($term_id,'header_image',true), 'full')[0],
                    'live_iframe' => get_term_meta($term_id,'live_iframe',true),
                    'light_content' => get_term_meta($term_id,'light_content',true),
                    'shuwen_content' => get_term_meta($term_id,'shuwen_content',true),
                    'todo_title' => get_term_meta($term_id,'todo_title',true),
                    'todo_content' => get_term_meta($term_id,'todo_content',true),
                    'todo_image' => wp_get_attachment_image_src(get_term_meta($term_id,'todo_image',true), 'full')[0],
                );
        
                return array(
                    'data' => $data,
                    'light' => $light_products,
                    'shuwen' => $shuwen_products,
                    'todo' => $todo_products
                );
            } else {
                return new WP_Error('not_found', '廟宇編號錯誤', array('status' => 404));
            }
        }

        function wrap_array_if_not($value) {
            if (!is_array($value)) {
                $value = array($value);
            }
            return $value;
        }
    }
    TEMPLE::forge();
}