<?php
/* Description:訂單相關API
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('ORDER') ){
    class ORDER{
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
            //增加訂單類型
            add_filter( 'manage_edit-shop_order_columns', [$this, 'custom_shop_order_column']  );
            //增加訂單類型column
            add_action( 'manage_shop_order_posts_custom_column' , [$this, 'cbsp_credit_details'] );
            //增加訂單類型a標籤
            add_filter( 'views_edit-shop_order', [ $this, 'append_order_source_link' ], 20, 1 );
            //增加訂單類型query
            add_action( 'pre_get_posts', [ $this, 'apply_order_source_filters_on_backend' ] );

        }


        function register_custom_api_endpoint($rest) {
            //會員訂單
            register_rest_route('gc', '/order/(?P<order_type>[\w-]+)', array(
                'methods' => 'GET',
                'callback' => [$this, 'get_order'],
            ));
            //會員點燈、代辦訂單詳情
            register_rest_route('gc', '/order/prayer/detail', array(
                'methods' => 'GET',
                'callback' => [$this,'get_order_prayer_detail'],
            ));
             //會員文創訂單詳情
             register_rest_route('gc', '/order/culture/detail', array(
                'methods' => 'GET',
                'callback' => [$this,'get_order_culture_detail'],
            ));
             //建立點燈訂單
            register_rest_route('gc', '/light/order', array(
                'methods' => 'POST',
                'callback' => [$this,'create_light_order'],
            ));
            //建立代辦訂單
            register_rest_route('gc', '/todo/order', array(
            'methods' => 'POST',
            'callback' => [$this,'create_todo_order'],
            ));
            //訂單取消
            register_rest_route('gc', '/order/cancel', array(
                'methods' => 'POST',
                'callback' => [$this,'order_cancel'],
            ));
            //建立文創訂單
            register_rest_route('gc', '/culture/order', array(
                'methods' => 'POST',
                'callback' => [$this,'create_culture_order'],
            ));
            //感謝頁訂單
            register_rest_route('gc', '/thanks/info', array(
                'methods' => 'POST',
                'callback' => [$this,'get_thanks_info'],
            ));
        }


        public function apply_order_source_filters_on_backend( $query ) {
            global $pagenow;
        
            if ( $query->is_admin && $pagenow == 'edit.php' && $_GET['post_type'] == 'shop_order' ) {
                $order_type = isset( $_GET['order_type'] ) ? sanitize_text_field( $_GET['order_type'] ) : '';
        
                if ( ! empty( $order_type ) ) {
                    $meta_key_query = array(
                        array(
                            'key'   => 'order_type',
                            'value' => $order_type,
                        ),
                    );
        
                    $query->set( 'meta_query', $meta_key_query );
                }
            }
        }


        public function append_order_source_link( $views )
        {	
            $query_string = remove_query_arg( array( 'orderby', 'order' ) );
            $query_string = add_query_arg( 'order_type', 'light', $query_string );
            $views['light'] = '<a href="' . esc_url( $query_string ) . '">點燈</a>';
            $query_string2 = remove_query_arg( array( 'orderby', 'order' ) );
            $query_string2 = add_query_arg( 'order_type', 'culture', $query_string2 );
            $views['culture'] = '<a href="' . esc_url( $query_string2 ) . '">文創商品</a>';
            $query_string3 = remove_query_arg( array( 'orderby', 'order' ) );
            $query_string3 = add_query_arg( 'order_type', 'todo', $query_string );
            $views['todo'] = '<a href="' . esc_url( $query_string3 ) . '">代辦項目</a>';
            return $views;
        }

        public function cbsp_credit_details( $column ){
            global $post;
            $order_id = $post->ID;
            switch ( $column ){
                    case 'source':
                    $type = get_post_meta( $order_id, 'order_type', true );
                    if($type === 'culture'){
                        echo '文創商品';
                    }else if($type === 'light'){
                        echo '點燈';
                    }else if($type ==='todo'){
                        echo '代辦項目';
                    }
                }
            }


        public function custom_shop_order_column( $columns ){
            $columns['source'] = '訂單類型';
            return $columns;
        }


        function order_cancel($request)
        {   
            if(empty($request['order_id'])){
                return wp_send_json_error("資料缺失");
            }
            $order_id = sanitize_text_field($request['order_id']);
            
            if(empty($order_id)){
                return '無效的訂單 ID';
            }

            $order = wc_get_order($order_id);

            $order->update_status('cancelled');

            return wp_send_json_success('訂單消成功');

        }


        function get_thanks_info($request)
        {
            if(empty($request['order_id'] || $request['user_id'])){
                return wp_send_json_error("資料缺失");
            }

            $order_id = sanitize_text_field($request['order_id']);

            $order_type  = get_post_meta($order_id, 'order_type', true);

            $order = wc_get_order($order_id);

            $order_user_id = $order->user_id;

            $user_id = $request['user_id'];

            if($user_id != $order_user_id){
                return wp_send_json_error("訂單資訊錯誤");
            }

            $order_status = $order->get_status();
            $payment_method = get_post_meta($order_id,'_payment_method_way',true);
            $order_note = $order->get_customer_note();

            switch ($payment_method) {
                case 'Credit':
                    $custom_payment_method = '線上刷卡';
                    break;
                case 'CVS':
                    $custom_payment_method = '超商代碼繳費';
                    break;
                case 'WebATM':
                    $custom_payment_method = 'ATM虛擬帳戶匯款';
                    break;

                default:
                    // 默認情況，保持原始訂單狀態
                    $custom_payment_method = $payment_method;
                    break;
            }

            switch ($order_status) {
                case 'pending':
                    $custom_order_status = '等待付款中';
                    break;
                case 'processing':
                    $custom_order_status = '處理中';
                    break;
                case 'completed':
                    $custom_order_status = '完成';
                    break;
                case 'failed':
                    $custom_order_status = '失敗';
                    break;
                case 'cancelled':
                    $custom_order_status = '取消';
                    break;
                default:
                    // 默認情況，保持原始訂單狀態
                    $custom_order_status = $order_status;
                    break;
            }

            $order_date = $order->get_date_created()->date('Y-m-d');
            $items = $order->get_items();

            // 遍歷每個商品
            if($order_type == 'light' || $order_type == 'todo'){
                foreach ($items as $item) {
                    $product_name = $item->get_name(); 
                    $product_price = $item->get_total() / $item->get_quantity(); 
                    $product_quantity = $item->get_quantity();
        
                    // 添加訂單 ID 到訂單陣列中
                    $orders_with_id[] = array(
                        'order_id' => $order_id,
                        'name' => $product_name, 
                        'count' => $product_quantity,
                        'total' => $product_price,
                        'status' => $custom_order_status,
                        'date' => $order_date,
                        'payment' => $custom_payment_method,
                        'note' => $order_note
                    );
                }
                $info = [];

                for($i=1;$i<=$product_quantity;$i++){

                    $meta_key = "prayer_item_$i";

                    $info[] = get_post_meta($order_id, $meta_key, true);
                }


            }else if($order_type == 'culture'){
                $total = 0;
                $total_count = 0;
                foreach ($items as $item) {
                    $product_name = $item->get_name(); 
                    $product_price = $item->get_total() / $item->get_quantity(); 
                    $product_quantity = $item->get_quantity();
                    
                    $total += $item->get_total();
                    $total_count += $product_quantity;
                    $list[] = array(
                        'name' => $product_name,
                        'price' => $product_price,
                        'count' => $product_quantity
                    );
                    // 添加訂單 ID 到訂單陣列中
                }
                $orders_with_id[] = array(
                    'order_id' => $order_id,
                    'item' => $list,
                    'status' => $custom_order_status,
                    'date' => $order_date,
                    'payment' => $custom_payment_method,
                    'note' => $order_note,
                    'total' => $total,
                    'count' => $total_count
                );

                $billing_address = $order->get_address('billing');
        
                $shipping_address = $order->get_address('shipping');
        
                $info = array(
                    'billing_address' => $billing_address,
                    'shipping_address' => $shipping_address,
                );
            }
           
            return array(
                'type' => $order_type,
                'data' => $orders_with_id,
                'info' => $info,
            );
        }


        function get_order($request) 
        {   
            if(empty($request['user_id']) || empty($request['order_type'])){
                return wp_send_json_error("資料缺失");
            }

            $user_id = sanitize_text_field($request->get_param('user_id'));
            $order_type = sanitize_text_field($request->get_param('order_type'));
            $customer_orders = wc_get_orders(array(
                'customer' => $user_id, 
                'numberposts' => -1,
            ));
        
            // 新建一個陣列來存儲包含訂單 ID 的訂單
            $orders_with_id = [];
        
            foreach ($customer_orders as $order) {
                $order_type_meta  = get_post_meta($order->get_id(), 'order_type', true);
                if ($order_type_meta !== $order_type) {
                    continue; 
                }
                // 獲取訂單 ID
                $order_id = $order->get_id();
                $order_status = $order->get_status();
                $payment_method = get_post_meta($order_id,'_payment_method_way',true);
                $order_note = $order->get_customer_note();

                switch ($payment_method) {
                    case 'Credit':
                        $custom_payment_method = '線上刷卡';
                        break;
                    case 'CVS':
                        $custom_payment_method = '超商代碼繳費';
                        break;
                    case 'WebATM':
                        $custom_payment_method = 'ATM虛擬帳戶匯款';
                        break;

                    default:
                        // 默認情況，保持原始訂單狀態
                        $custom_payment_method = $payment_method;
                        break;
                }

                switch ($order_status) {
                    case 'pending':
                        $custom_order_status = '等待付款中';
                        break;
                    case 'processing':
                        $custom_order_status = '處理中';
                        break;
                    case 'completed':
                        $custom_order_status = '完成';
                        break;
                    case 'failed':
                        $custom_order_status = '失敗';
                        break;
                    case 'cancelled':
                        $custom_order_status = '取消';
                        break;
                    default:
                        // 默認情況，保持原始訂單狀態
                        $custom_order_status = $order_status;
                        break;
                }

                $order_date = $order->get_date_created()->date('Y-m-d');
                $items = $order->get_items();
        
                // 遍歷每個商品
                if($order_type == 'light' || $order_type == 'todo'){
                    foreach ($items as $item) {
                        $product_name = $item->get_name(); 
                        $product_price = $item->get_total() / $item->get_quantity(); 
                        $product_quantity = $item->get_quantity();
            
                        // 添加訂單 ID 到訂單陣列中
                        $orders_with_id[] = array(
                            'order_id' => $order_id,
                            'name' => $product_name, 
                            'count' => $product_quantity,
                            'total' => $product_price,
                            'status' => $custom_order_status,
                            'date' => $order_date,
                            'payment' => $custom_payment_method,
                            'note' => $order_note
                        );
                    }
                }else if($order_type == 'culture'){
                    $total = 0;
                    $total_count = 0;
                    foreach ($items as $item) {
                        $product_name = $item->get_name(); 
                        $product_price = $item->get_total() / $item->get_quantity(); 
                        $product_quantity = $item->get_quantity();
                        
                        $total += $item->get_total();
                        $total_count += $product_quantity;
                        $list[] = array(
                            'name' => $product_name,
                            'price' => $product_price,
                            'count' => $product_quantity
                        );
                        // 添加訂單 ID 到訂單陣列中
                    }
                    $orders_with_id[] = array(
                        'order_id' => $order_id,
                        'item' => $list,
                        'status' => $custom_order_status,
                        'date' => $order_date,
                        'payment' => $custom_payment_method,
                        'note' => $order_note,
                        'total' => $total,
                        'count' => $total_count
                    );
                }
            }
           
            return $orders_with_id;
        }


        function get_order_prayer_detail($request)
        {   
            if(empty($request['order_id']) || empty($request['count'])){
                return wp_send_json_error("資料缺失");
            }
            $order_id = sanitize_text_field($request['order_id']);
            $count = sanitize_text_field($request['count']);

            $prayers = [];
            for($i=1;$i<=$count;$i++){
                $meta_key = "prayer_item_$i";
                $prayers[] = get_post_meta($order_id, $meta_key, true);
            }

            return $prayers;
        }


        function get_order_culture_detail($request)
        {   
            if(empty($request['order_id'])){
                return wp_send_json_error("資料缺失");
            }

            $order_id = intval($_GET['order_id']);

            $order_id = sanitize_text_field($order_id);
        
            $order = wc_get_order($order_id);
        
            if ($order) {
                $billing_address = $order->get_address('billing');
        
                $shipping_address = $order->get_address('shipping');
        
                $order_data = array(
                    'order_id' => $order_id,
                    'billing_address' => $billing_address,
                    'shipping_address' => $shipping_address,
                );
        
                return $order_data;
            } else {
                return '訂單不存在';
            }
        }


        function create_light_order($request)
        {   
            if(empty($request['customer'])){
                return wp_send_json_error("資料缺失");
            }

            
            try {
                $order = wc_create_order();
                // 設定訂單的購買者資料
                $customer_data = $request['customer'];
                $address = array(
                    'first_name' => $customer_data['name'],
                    'email'      => $customer_data['email'],
                    'phone'      => $customer_data['phone'],
                    'address_1'  => $customer_data['selectedCity'].$customer_data['selectedArea'].$customer_data['address'],
                    'postcode'   => $customer_data['zipCode'],
                );
                
                //寫入訂購者資料
                $order->set_address( $address, 'billing' );

                $product_data = $request['detail'];
                $product_id = (int)$product_data['productID'];
                $product_count = $product_data['count'];
                $product_payment = $product_data['payment'];
                $product_remark = $product_data['remark'];
                $user_id = $product_data['user_id'];
               
                //寫入付款方式
                $order->set_payment_method('ecpay');
                
                $order->set_customer_note($product_remark);

                //寫入訂購者會員id
                $order->set_customer_id($user_id);

                $product = wc_get_product($product_id);
                if ($product) {
                    $order->add_product($product, $product_count);
                }

                // 設定訂單的自訂元資料（order meta）
                $prayer_data = $request['prayer'];
                foreach ($prayer_data as $index => $prayer_item) {
                    $meta_key = 'prayer_item_' . ($index + 1);
                    $order->update_meta_data($meta_key, $prayer_item);
                }
                
                $order->update_meta_data( 'order_type', 'light' );

                // 儲存訂單
                $order->save();

                $order_id = $order->get_id();

                update_post_meta( $order_id, '_payment_method_way', $product_payment );

                update_post_meta($order_id,'_order_total', $product_data['total']);

                update_post_meta($order_id,'ecpay_faileds', 0);
                
                $ecpay_gateway = new WC_Gateway_Ecpay();

                ob_start();

                $ecpay_gateway->receipt_page($order_id);

                $html = ob_get_contents();

                ob_end_clean();

                return wp_send_json_success($html);
                
            } catch (Exception $e) {
                // 捕獲例外並顯示錯誤訊息
                echo '發生錯誤：' . $e->getMessage();
            }
        }

        function create_culture_order($request)
        {   
            if(empty($request['billing'])){
                return wp_send_json_error("資料缺失");
            }
            try {
                $order = wc_create_order();
                // 設定訂單的購買者資料
                $billing_data = $request['billing'];
                $billing = array(
                    'first_name' => $billing_data['name'],
                    'company' => $billing_data['name'],
                    'email'      => $billing_data['email'],
                    'phone'      => $billing_data['phone'],
                    'city' => $billing_data['address'],
                    'address_1'  => $billing_data['selectedCity'],
                    'address_2' => $billing_data['selectedArea'],
                    'postcode'   => $billing_data['zipCode'],
                );
                if(isset($request['shipping'])){
                    $shipping_data = $request['shipping'];
                    $shipping = array(
                        'first_name' => $shipping_data['name'],
                        'company' => $shipping_data['name'],
                        'phone'      => $shipping_data['phone'],
                        'city' => $shipping_data['address'],
                        'address_1'  => $shipping_data['selectedCity'],
                        'address_2' => $shipping_data['selectedArea'],
                        'postcode'   => $shipping_data['zipCode'],
                    );
                }else{
                    $shipping = array(
                        'first_name' => $billing_data['name'],
                        'company' => $billing_data['name'],
                        'phone'      => $billing_data['phone'],
                        'city' => $billing_data['address'],
                        'address_1'  => $billing_data['selectedCity'],
                        'address_2' => $billing_data['selectedArea'],
                        'postcode'   => $billing_data['zipCode'],
                    );
                }
                
                //寫入訂購者資料
                $order->set_address( $billing, 'billing');
                $order->set_address($shipping, 'shipping');


                $order_data = $request['order'];
                $payment = $order_data['payment'];
                $remark = $billing_data['remark'];
                $user_id = $order_data['user_id'];
               
                //寫入付款方式
                $order->set_payment_method('ecpay');
                
                $order->set_customer_note($remark);

                //寫入訂購者會員id
                $cart_contents = $this->get_cart($user_id);
                
                foreach ($cart_contents as $cart_item_key => $cart_item) {
                    $product = $cart_item['data']; 
                    $product_id = $product->get_id(); 
                    $product_count = $cart_item['quantity']; 
                   
                    $order->add_product($product, $product_count);
                }
                
                $order->set_customer_id($user_id);
                $order->update_meta_data( 'order_type', 'culture' );
                // 儲存訂單
                $order->save();

                $order_id = $order->get_id();

                update_post_meta( $order_id, '_payment_method_way', $payment);

                update_post_meta($order_id,'_order_total', $order_data['total']);

                update_post_meta($order_id,'ecpay_faileds', 0);

                $this->clear_cart($user_id);
                
                $ecpay_gateway = new WC_Gateway_Ecpay();

                ob_start();

                $ecpay_gateway->receipt_page($order_id);

                $html = ob_get_contents();

                ob_end_clean();

                return wp_send_json_success($html);
                
            } catch (Exception $e) {
                // 捕獲例外並顯示錯誤訊息
                echo '發生錯誤：' . $e->getMessage();
            }
        }


        function create_todo_order($request)
        {   
            if(empty($request['customer'])){
                return wp_send_json_error("資料缺失");
            }
            try {
                $order = wc_create_order();
                // 設定訂單的購買者資料
                $customer_data = $request['customer'];
                $address = array(
                    'first_name' => $customer_data['name'],
                    'email'      => $customer_data['email'],
                    'phone'      => $customer_data['phone'],
                    'address_1'  => $customer_data['selectedCity'].$customer_data['selectedArea'].$customer_data['address'],
                    'postcode'   => $customer_data['zipCode'],
                );
                
                //寫入訂購者資料
                $order->set_address( $address, 'billing' );

                $product_data = $request['detail'];
                $product_id = (int)$product_data['productID'];
                $product_count = $product_data['count'];
                $product_payment = $product_data['payment'];
                $product_remark = $product_data['remark'];
                $user_id = $product_data['user_id'];
               
                //寫入付款方式
                $order->set_payment_method('ecpay');
                
                $order->set_customer_note($product_remark);

                //寫入訂購者會員id
                $order->set_customer_id($user_id);

                $product = wc_get_product($product_id);
                if ($product) {
                    $order->add_product($product, $product_count);
                }

                // 設定訂單的自訂元資料（order meta）
                $prayer_data = $request['prayer'];
                foreach ($prayer_data as $index => $prayer_item) {
                    $meta_key = 'prayer_item_' . ($index + 1);
                    $order->update_meta_data($meta_key, $prayer_item);
                }
                
                $order->update_meta_data( 'order_type', 'todo' );

                // 儲存訂單
                $order->save();

                $order_id = $order->get_id();

                update_post_meta( $order_id, '_payment_method_way', $product_payment );

                update_post_meta($order_id,'_order_total', $product_data['total']);

                update_post_meta($order_id,'ecpay_faileds', 0);
                
                $ecpay_gateway = new WC_Gateway_Ecpay();

                ob_start();

                $ecpay_gateway->receipt_page($order_id);

                $html = ob_get_contents();

                ob_end_clean();

                return wp_send_json_success($html);
                
            } catch (Exception $e) {
                // 捕獲例外並顯示錯誤訊息
                echo '發生錯誤：' . $e->getMessage();
            }
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


        function get_cart($user_id)
        {   

            $this->switch_to_user_by_id($user_id);
           
            $session_handler = new WC_Session_Handler();

            $session = $session_handler->get_session($user_id);
            
            if (is_null(WC()->cart)) {
                wc_load_cart();
            }

            $cart = wc()->cart;

            $cart_contents = $cart->get_cart();

            return $cart_contents;
        }

        function clear_cart($user_id) {
            $this->switch_to_user_by_id($user_id);
        
            $cart = wc()->cart;
        
            $cart->empty_cart();
        }
    }
    ORDER::forge();
}