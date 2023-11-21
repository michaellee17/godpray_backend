<?php
/* Description產品相關api(未完成)
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('WC_Payment_Gateway')) {
    include_once('../../ecpay/includes/class-wc-gateway-ecpay.php');
}

if( !class_exists('PRODUCT') ){
    class PRODUCT{
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
            add_action('woocommerce_admin_order_data_after_billing_address', [$this,'display_custom_order_meta']);
            add_action('admin_enqueue_scripts', [$this,'enqueue_custom_styles']);
            add_filter('woocommerce_get_checkout_order_received_url', [$this,'custom_checkout_order_received_url'], 10, 2);
        }


        function register_custom_api_endpoint() {
            //取得個別商品資料
            register_rest_route('gc', '/product/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, 'get_single_product_data'],
            ));
            //綠界導回
            register_rest_route('gc', '/ecpay/(?P<order_id>\d+)', array(
                'methods' => 'POST',
                'callback' => [$this, 'get_ecpay'],
            ));
            //會員中心結帳
            register_rest_route('gc', '/order/checkout', array(
                'methods' => 'POST',
                'callback' => [$this,'order_checkout'],
            ));
            
        }


        function enqueue_custom_styles() 
        {
            wp_enqueue_style('custom-styles', plugins_url('assets/css/style.css', dirname(__FILE__)));
        }


        function custom_checkout_order_received_url($order_received_url, $order) 
        {
            
            $order_id = $order->get_id();

            if(empty($order_id)){
                return '查無此訂單';
            }

            $payment_method = get_post_meta($order_id,'_payment_method_way',true);

            if($payment_method === 'CVS'){

                $order_received_url = frontend_url.'/thanks/'.$order_id;

            }else if($payment_method === 'Credit'){

                $order_received_url = get_site_url() . '/api/gc/ecpay/' .$order_id;
            }

            return $order_received_url;
        }


        function get_ecpay($request)
        {   
            if(empty($request['order_id'])){
                return '查無此訂單';
            }
            $order_id = $request['order_id'];
            
            $frontend_url = frontend_url;

            $target_url = frontend_url.'/thanks/'.$order_id;

            header("Location: $target_url");
            exit;
        }


        function display_custom_order_meta($order){
          
            $payment_method = get_post_meta($order->get_id(),'_payment_method_way', true);
           
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

            if (!empty($payment_method)) {
                ?>
                <div class="order_data_column">
                    <h4>付款方式</h4>
                    <p><?php echo esc_html($custom_payment_method); ?></p>
                </div>
                <?php
            }

            $prayer_items = array();
            
            for ($i = 1; ; $i++) {
                $custom_meta = get_post_meta($order->get_id(), 'prayer_item_' . $i, true);
                
                if (empty($custom_meta)) {
                    break;
                }
                
                $prayer_items[] = $custom_meta;
            }
        
            ?>
            <div class="prayer_container">
            <?php
            foreach ($prayer_items as $index => $prayer_item) {
                ?>
                <div class="order_data_column prayer">
                    <h4>第<?php echo ($index + 1); ?>位被祈福者資料</h4>
                    <?php if (isset($prayer_item['name'])) : ?>
                        <p>姓名:<?php echo esc_html($prayer_item['name']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($prayer_item['gender'])) : ?>
                        <p>性別:<?php echo esc_html($prayer_item['gender']); ?></p>
                    <?php endif; ?>  
                    <?php if (isset($prayer_item['yValue']) && isset($prayer_item['mValue']) && isset($prayer_item['dValue']) && isset($prayer_item['tValue']) ) : ?>
                        <p>農曆成辰:<?php echo esc_html($prayer_item['yValue'].'年'.$prayer_item['mValue'].'月'.$prayer_item['dValue'].'日'.$prayer_item['tValue']).'時'; ?></p>
                    <?php endif; ?>  
                    <?php if (isset($prayer_item['zipCode'])) : ?>
                        <p>郵遞區號:<?php echo esc_html($prayer_item['zipCode']); ?></p>
                    <?php endif; ?>  
                    <?php if (isset($prayer_item['selectedCity']) &&  isset($prayer_item['selectedArea']) && isset($prayer_item['address'])) : ?>
                        <p>地址:<?php echo esc_html($prayer_item['selectedCity'].$prayer_item['selectedArea'].$prayer_item['address']); ?></p>
                    <?php endif; ?>
                    <!-- <?php if (isset($prayer_item['phone'])) : ?>
                        <p>電話號碼:<?php echo esc_html($prayer_item['phone']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($prayer_item['email'])) : ?>
                        <p>信箱:<?php echo esc_html($prayer_item['email']); ?></p>
                    <?php endif; ?>    -->
                </div>
                <?php 
             
            }
            ?></div>
            <?php
        }


        function order_checkout($request)
        {   
            if(empty($request['order_id'])){
                return wp_send_json_error("資料缺失");
            }

            $order_id = $request['order_id'];

            $ecpay_gateway = new WC_Gateway_Ecpay();

            $faileds = (int)get_post_meta( $order_id, 'ecpay_faileds', true );

            $faileds += 1;

            update_post_meta($order_id,'ecpay_faileds',$faileds);

            ob_start();

            $ecpay_gateway->receipt_page($order_id);

            $html = ob_get_contents();
            
            ob_end_clean();

            return wp_send_json_success($html);
        }


        function get_single_product_data($request)
        {
            global $wpdb;

            if (empty($request['id']) || !is_numeric($request['id'])) {
                return '無效的產品 ID';
            }

            $product_id = $request['id'];

            $product = wc_get_product($product_id);

            if (is_object($product)) {
                $price = $product->get_price();
                $name = $product->get_name();
                return array(
                    'price'=> $price,
                    'name' => $name
                );
            } else {
                return '查無資料';
            }
        }
    }
    PRODUCT::forge();
}