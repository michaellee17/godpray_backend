<?php
/* Description:會員相關
 */
use Firebase\JWT\JWT;
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('MEMBER') ){
    class MEMBER{
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
            //註冊
            register_rest_route('gc', '/register', array(
                'methods' => 'POST',
                'callback' => [$this,'member_register'],
            ));
            //登入
            register_rest_route('gc', '/login', array(
                'methods' => 'POST',
                'callback' => [$this,'member_login'],
            ));
            //更新會員資料
            register_rest_route('gc', '/profile/update', array(
                'methods' => 'POST',
                'callback' => [$this,'update_member_profile'],
            ));
            //取得會員資料
            register_rest_route('gc', '/profile/data', array(
                'methods' => 'GET',
                'callback' => [$this,'get_member_profile'],
            ));
            //記出重設密碼連結
            register_rest_route('gc', '/reset', array(
                'methods' => 'POST',
                'callback' => [$this,'send_reset_link'],
            ));
            //執行重設密碼
            register_rest_route('gc', '/sendReset', array(
                'methods' => 'POST',
                'callback' => [$this,'reset_password'],
            ));
           
        }

       

        function reset_password($request) {
            if (empty($request['user_id']) || empty($request['password']) ) {
                return wp_send_json_error("資料缺失");
            }

            $password = sanitize_text_field($request['password']);
            $user_id = sanitize_text_field($request['user_id']);

            $update_password = wp_update_user(array(
                'ID' => $user_id,
                'user_pass' => $password
              ));
            if ($update_password) {
                return wp_send_json_success("密碼重設成功");
            } else {
                return wp_send_json_error("密碼重設失敗");
            }
        }


        function send_reset_link($request)
        {
            $frontend_url = frontend_url;

            if(empty($request['email'])){
                return wp_send_json_error("資料缺失");
            }
            
            $email = sanitize_text_field($request['email']);

            $user_exists = email_exists($email);
            if (!$user_exists) {
                return wp_send_json_error("該信箱未註冊");
            } else {
                $user_id = $user_exists;
                $encrypted_user_id = base64_encode($user_id);
                $expiration_time = time() + 3600;
                $reset_link = $frontend_url . '/resetPassword/' . urlencode($encrypted_user_id) . '/' . $expiration_time;
                $subject = '神界祈福平台重設密碼';
                $message = '請點下連結前往重設密碼：<a href="' . esc_url($reset_link) . '">前往修改密碼</a>';
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $email_sent = wp_mail($email, $subject, $message, $headers);
        
                if ($email_sent) {
                    return wp_send_json_success("重設密碼連結已寄出.");
                } else {
                    return wp_send_json_error("郵件寄出有問題");
                }
            }
        }


        function get_member_profile($request)
        {   
            if(empty($request['user_id'])){
                return wp_send_json_error("資料缺失");
            }

            $user_id = sanitize_text_field($request['user_id']);

            $user = get_userdata($user_id);

            if (!$user) {
                return wp_send_json_error("找不到該使用者");
            }

            $name = get_user_meta($user_id, 'user_nickname', true);
            $email = $user->user_email;
            $city = get_user_meta($user_id, 'user_address_city', true);
            $area = get_user_meta($user_id, 'user_address_area', true);
            $address = get_user_meta($user_id, 'user_address', true);
            $zipCode = get_user_meta($user_id,'user_zipCode',true);
            $phone = get_user_meta($user_id,'user_phone',true);

            $profile_data = array(
                'name' => $name,
                'email' => $email,
                'city' => $city,
                'area' => $area,
                'address' => $address,
                'zipCode' => $zipCode,
                'phone' => $phone
            );

            return wp_send_json_success($profile_data);
        }


        function member_login($request)
        {   
            if (empty($request['email']) || empty($request['password']) ) {
                return wp_send_json_error("資料缺失");
            }

            $password = sanitize_text_field($request['password']);
            $email = sanitize_text_field($request['email']);


            $user = wp_authenticate($email, $password);

            if (is_wp_error($user)) {
                return wp_send_json_error("帳號或密碼錯誤");
            } else {
                
                $user_id = $user->ID;
                $name = get_user_meta($user_id, 'user_nickname', true);
                $city = get_user_meta($user_id, 'user_address_city', true);
                $area = get_user_meta($user_id, 'user_address_area', true);
                $address = get_user_meta($user_id, 'user_address', true);
                $zipCode = get_user_meta($user_id,'user_zipCode',true);
                $phone = get_user_meta($user_id,'user_phone',true);
                $secret_key = 'gc_michael';
                $payload = array(
                    "user_id" => $user_id,
                    "email" => $email
                );
                $jwt = Firebase\JWT\JWT::encode($payload, $secret_key, 'HS256');

                return wp_send_json_success(array(
                    "jwt" => $jwt,
                    "user_id" => $user_id,
                    "email" => $email,
                    'name' => $name,
                    'city' => $city,
                    'area' => $area,
                    'address' => $address,
                    'zipCode' => $zipCode,
                    'phone' => $phone
                ));
            }
        }


        function member_register($request)
        {
            if (empty($email) || empty($password)) {
                return wp_send_json_error("資料不完整");
            }
            
            $password = sanitize_text_field($request['password']);
            $email = sanitize_text_field($request['email']);

            $user_exists = email_exists($email);
            if ($user_exists) {
                return wp_send_json_error("信箱已被註冊");
            }
        
            $user_id = wp_create_user($email, $password, $email);
        
            if (is_wp_error($user_id)) {
               
                return wp_send_json_error("用戶ID已存在");
            }
        
            // 生成 JWT
            $secret_key = 'gc_michael';
            $payload = array(
                "user_id" => $user_id,
                "email" => $email
            );
            $jwt = Firebase\JWT\JWT::encode($payload, $secret_key, 'HS256');
            
            $response_data = array(
                "jwt" => $jwt,
                "user_id" => $user_id,
                "email" => $email
            );

            wp_send_json_success($response_data);
        }


        function update_member_profile()
        {
            $request = json_decode(file_get_contents('php://input'));

            if (empty($request->email) || empty($request->user_id) || empty($request->city) || empty($request->area) || empty($request->address) || empty($request->name) || empty($request->zipCode)) {
                return wp_send_json_error("資料不完整");
            }

            if (!empty($request->oldPsw) && !empty($request->newPsw)) {
                $user = get_userdata($request->user_id);
                
                if (!wp_check_password($request->oldPsw, $user->user_pass, $request->user_id)) {
                    return wp_send_json_error("舊密碼不正確");
                }
                
                wp_set_password($request->newPsw, $request->user_id);
            }

            $user_data = array(
                'ID' => $request->user_id,  
                'user_email' => $request->email,
            );

            $result = wp_update_user($user_data);

            if (is_wp_error($result)) {
                return wp_send_json_error("更新使用者資料失敗：" . $result->get_error_message());
            }

            update_user_meta($request->user_id, 'user_address_city', $request->city);
            update_user_meta($request->user_id, 'user_address_area', $request->area);
            update_user_meta($request->user_id, 'user_address', $request->address);
            update_user_meta($request->user_id, 'user_nickname', $request->name);
            update_user_meta($request->user_id, 'user_zipCode', $request->zipCode);
            update_user_meta($request->user_id, 'user_phone', $request->phone);

            return wp_send_json_success("使用者資料已成功更新");
        }
    }
    MEMBER::forge();
}