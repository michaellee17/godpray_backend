<?php
/* Description:購物車相關操作
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('CART') ){
    class CART{
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
            register_rest_route('gc', '/add/cart', array(
                'methods' => 'POST',
                'callback' => [$this,'add_to_cart'],
            ));
            register_rest_route('gc', '/get/cart', array(
                'methods' => 'POST',
                'callback' => [$this,'get_cart'],
            ));
            register_rest_route('gc', '/cart/delete', array(
                'methods' => 'POST',
                'callback' => [$this,'delete_cart_item'],
            ));
            register_rest_route('gc', '/cart/update', array(
                'methods' => 'POST',
                'callback' => [$this,'update_cart_item'],
            ));
        }


        function update_cart_item($request)
        {   
            if (empty($request['user_id']) || empty($request['product_id']) || empty($request['quantity'])) {
                return wp_send_json_error("資料缺失");
            }

            $user_id = intval($request['user_id']);
            $product_id = intval($request['product_id']);
            $quantity = intval($request['quantity']);

            $user_id = sanitize_text_field($user_id);
            $product_id = sanitize_text_field($product_id);
            $quantity = sanitize_text_field($quantity);
            
            if ($user_id > 0 && $product_id > 0) {
                $this->switch_to_user_by_id($user_id);

                $session_handler = new WC_Session_Handler();

                $session = $session_handler->get_session($user_id);
                
                if (is_null(WC()->cart)) {
                    wc_load_cart();
                }
                
                $cart = WC()->cart;
        
                $cart_contents = $cart->get_cart();
                
                foreach ($cart_contents as $key => $item) {
                    if ($item['product_id'] == $product_id) {
                        // 使用 set_quantity 方法更新数量
                        WC()->cart->set_quantity($key, $quantity);
                    }
                }
                return '更新成功';
            } else {
                return '更新失敗';
            }
        }


        function delete_cart_item($request)
        {
            if (empty($request['user_id']) || empty($request['product_id'])) {
                return wp_send_json_error("資料缺失");
            }

            $user_id = intval($request['user_id']);
            $product_id = intval($request['product_id']);

            $user_id = sanitize_text_field($user_id);
            $product_id = sanitize_text_field($product_id);
            
            if ($user_id > 0 && $product_id > 0) {
                $this->switch_to_user_by_id($user_id);

                $session_handler = new WC_Session_Handler();

                $session = $session_handler->get_session($user_id);
                
                if (is_null(WC()->cart)) {
                    wc_load_cart();
                }
                
                $cart = WC()->cart;
        
                $cart_contents = $cart->get_cart();
                foreach ($cart_contents as $key => $item) {
                    if($item['product_id']==$product_id){
                        WC()->cart->remove_cart_item( $key );                   
                    }
                }
                
                return '刪除成功';
               
            } else {
                return '刪除失敗';
            }
        }
        

        function get_cart($request)
        {   
            if (empty($request['user_id']) ) {
                return wp_send_json_error("資料缺失");
            }

            $user_id = intval($request['user_id']);

            $user_id = sanitize_text_field($user_id);

            $this->switch_to_user_by_id($user_id);
           
            $session_handler = new WC_Session_Handler();

            $session = $session_handler->get_session($user_id);
            
            if (is_null(WC()->cart)) {
                wc_load_cart();
            }

            $cart = wc()->cart;
            
            $cart_contents = $cart->get_cart();
            
            $data = $this->get_product_info($cart_contents);

            return $data;
        }


        //依據取得的商品再去取mini_cart要顯示的資料
        function get_product_info($cart_contents) {
            $product_info = array();
            $total = 0; 
            
            foreach ($cart_contents as $item) {
                $product_id = $item['product_id'];
                $product = wc_get_product($product_id);
                
                if (is_object($product)) {
                    $product_name = $product->get_name();
                    $image_url = get_the_post_thumbnail_url($product_id, 'full');
                    $price = $product->get_price();
                    $quantity = $item['quantity'];
                    $subtotal = $price * $quantity; 
                    $total += $subtotal; 
                    
                    $product_info[] = array(
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'image_url' => $image_url,
                        'price' => $price,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal, 
                    );
                }
            }
            
            return array(
                'product_info' => $product_info,
                'total' => $total, 
            );
        }


        function add_to_cart($request)
        {
            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
            include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
            global $wpdb;

            if (empty($request['user_id']) ) {
                return wp_send_json_error("資料缺失");
            }

            $user_id = $request['user_id'];

            $quantity = isset($request['quantity']) ? intval($request['quantity']) : 1;

            $quantity = sanitize_text_field($quantity);

            WC()->session = new WC_Session_Handler();

            WC()->session->init();
        
            $wc_session_data = WC()->session->get_session($user_id);
           
            $full_user_meta = get_user_meta($user_id,'_woocommerce_persistent_cart_1',true);
           
            WC()->customer = new WC_Customer( $user_id, true );
           
            WC()->cart = new WC_Cart();
            
            if($full_user_meta['cart']) {
                foreach($full_user_meta['cart'] as $sinle_user_meta) {
                     WC()->cart->add_to_cart( $sinle_user_meta['product_id'], $sinle_user_meta['quantity']  );
                }
            }
        
            WC()->cart->add_to_cart( $request['product_id'], $quantity);

            $updatedCart = [];
        
            foreach(WC()->cart->cart_contents as $key => $val) {
                unset($val['data']);
                $updatedCart[$key] = $val;
            }
        
            if($wc_session_data) {
                $wc_session_data['cart'] = serialize($updatedCart);
                $serializedObj = maybe_serialize($wc_session_data);
                
                $table_name = 'wp_woocommerce_sessions';
        
                $sql = "UPDATE $table_name SET session_value = '$serializedObj' WHERE session_key = '$user_id'";

                $rez = $wpdb->query($sql);

                WC()->session->save_data($user_id);
            }
        
            $full_user_meta['cart'] = $updatedCart;
          
            update_user_meta($user_id, '_woocommerce_persistent_cart_1', $full_user_meta);
            
            return array(
                'success' => true,
                'responsecode' => 200,
                "message" => "加入購物車成功",
                "data" => [],
            );
        }


        function switch_to_user_by_id($user_id) 
        {
            if (is_numeric($user_id)) {

                $user = get_userdata($user_id);
              
                wp_set_current_user($user_id);
              
                wp_set_auth_cookie($user_id);
              
                do_action('wp_login', wp_get_current_user()->user_login, $user);
            }
        }
    }
    CART::forge();
}