<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if( !class_exists('TEMPLE_LIST') ){
    class TEMPLE_LIST{
        private static $instance = null;

        public static function forge()
        {
            if( is_null( self::$instance ) ){
                self::$instance = new self;
            }
            return self::$instance;
        }


        private function __construct(){
            add_action('admin_menu', [$this,'custom_menu_page']);
            add_action('admin_enqueue_scripts', [$this,'enqueue_custom_styles']);
            add_action('wp_ajax_add_temple',[$this,'add_temple']);
            add_action('wp_ajax_edit_temple',[$this,'edit_temple']);
        }


        function enqueue_custom_styles() {
            //載入媒體庫
            wp_enqueue_media();
            wp_enqueue_style( 'temple', plugins_url( '/assets/css/temple.css', __DIR__ ) );
        }

        function add_temple() {
            global $wpdb;
            if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
                return;
            }
            $data = array(
                'name' => sanitize_text_field($_POST['name']),
                'main_god' => sanitize_text_field($_POST['main_god']),
                'address' => sanitize_text_field($_POST['address']),
                'phone' => sanitize_text_field($_POST['phone']),
                'image_url' => sanitize_text_field($_POST['image_url']),
                'info' => sanitize_textarea_field($_POST['info']),
                'light_content' => sanitize_textarea_field($_POST['light_content']),
                'light_products' => sanitize_text_field($_POST['light_products']),
                'shuwen_content' => sanitize_textarea_field($_POST['shuwen_content']),
                'shuwen_products' => sanitize_text_field($_POST['shuwen_products']),
                'live_iframe' => esc_html($_POST['live_iframe'])
            );
            $table_name = 'gc_temple'; 
            $wpdb->insert($table_name, $data);
            // 檢查是否成功
            if ($wpdb->last_error) {
                wp_send_json_error(array(
                    'message' => '操作失敗'
                ));
            } else {
                wp_send_json_success(array(
                    'message' => '操作成功'
                ));
            }
        }


        function edit_temple() {
            global $wpdb;
            if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
                return;
            }
            $data = array(
                'name' => sanitize_text_field($_POST['name']),
                'main_god' => sanitize_text_field($_POST['main_god']),
                'address' => sanitize_text_field($_POST['address']),
                'phone' => sanitize_text_field($_POST['phone']),
                'image_url' => sanitize_text_field($_POST['image_url']),
                'info' => sanitize_textarea_field($_POST['info']),
                'light_content' => sanitize_textarea_field($_POST['light_content']),
                'light_products' => sanitize_text_field($_POST['light_products']),
                'shuwen_content' => sanitize_textarea_field($_POST['shuwen_content']),
                'shuwen_products' => sanitize_text_field($_POST['shuwen_products']),
                'live_iframe' => esc_html($_POST['live_iframe'])
            );
            $table_name = 'gc_temple'; 
            $temple_id = sanitize_text_field($_POST['temple_id']);
            $result = $wpdb->update($table_name, $data, array('id' => $temple_id)); 
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => '操作成功'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => '操作失敗'
                ));
            }
        }


        function custom_menu_page() 
        {
            add_menu_page('廟宇列表', '廟宇列表', 'manage_options', 'custom-list', [$this,'custom_list_page']);
            add_submenu_page('custom-list','新增廟宇', '新增廟宇', 'manage_options', 'custom-add-temple', [$this, 'custom_add_temple_page'] );
            $submenu_slug = 'temple_edit';
            add_submenu_page('custom-list','', '', 'manage_options',  $submenu_slug, [$this, 'custom_edit_temple_page'] );
        }

        
        function custom_list_page() 
        {
            $list_table = new Custom_List_Table();
            echo '<form id="posts-filter" method="post">';
            echo '<div class="title">
                    <h2>廟宇列表</h2>
                    <a href="admin.php?page=custom-add-temple"><button type="button">新增廟宇</button></a>
                </div>';
            $list_table->prepare_items();
            $list_table->display();
            echo '</form>';
        }


        function custom_add_temple_page()
        {
            $template_path = plugin_dir_path(__FILE__) . '../template/addTemple.php';
            
            if (file_exists($template_path)) {
                include($template_path);
            } else {
                echo '模板路徑不正確';
            }
        }


        function custom_edit_temple_page()
        {
            $template_path = plugin_dir_path(__FILE__) . '../template/editTemple.php';
            
            if (file_exists($template_path)) {
                include($template_path);
            } else {
                echo '模板路徑不正確';
            }
        }

    }
    TEMPLE_LIST::forge();
}

if( !class_exists('Custom_List_Table') ){
    class Custom_List_Table extends WP_List_Table {
        public function __construct() 
        {
            parent::__construct(array(
                'singular' => 'custom_item',
                'plural' => 'custom_items',
                'ajax' => false
            ));
        }

    
        public function get_columns() 
        {
            return array(
                'cb' => '<input type="checkbox" />',
                'id' => '編號',
                'name' => '名稱',
                'main_god' => '主神',
                'address' => '地址',
                'phone' => '電話',
                'image_url' => '圖片網址',
                'info' => '簡介',
                'light_content' => '點燈內容',
                'light_products' => '點燈商品',
                'shuwen_content' => '疏文內容',
                'shuwen_products' => '疏文商品',
                'live_iframe' => '直播iframe'
            );
        }

    
        public function prepare_items() 
        {
            global $wpdb;
            $table_name = 'gc_temple'; 

            $query = "SELECT * FROM $table_name";
        
            $data = $wpdb->get_results($query, ARRAY_A);
            $columns = $this->get_columns();
            // $hidden = array();
            // $sortable = array();
            // $primary  = 'name';
            $this->_column_headers = array($columns);

            $this->items = $data;
            $this->set_pagination_args(array(
                'total_items' => count($this->items),
                'per_page' => 10 
            ));

            $this->process_bulk_action();
        }

    
        public function column_default($item, $column_name) 
        {
            return $item[$column_name];
        }

    
        public function column_cb($item) 
        {
            return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
        }


        public function column_name($item) 
        {   
            $actions = array(
                'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">編輯</a>', $_REQUEST['page'], 'edit', $item['id']),
                'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">刪除</a>', $_REQUEST['page'], 'delete', $item['id']),
            );
        
            return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions));
        }

    
        public function display() 
        {
            parent::display();
        }


        public function process_bulk_action() 
        {   
            global $wpdb;
            $table_name = 'gc_temple';
            if ('delete' === $this->current_action()) {
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (!empty($ids)) {
                    if(is_array($ids)){
                        //批量刪除
                        foreach ($ids as $id) {
                            $wpdb->delete($table_name, array('id' => $id), array('%d'));
                        }
                    }else{
                        //單獨刪除
                        $wpdb->delete($table_name, array('id' => $ids));
                    }
                }
                wp_redirect(home_url().'/wp-admin/admin.php?page=custom-list');
                exit;
            }
            
            if ('edit' === $this->current_action()) {
                $edit_id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;
                if ($edit_id > 0) {
                    wp_redirect(admin_url('admin.php?page=temple_edit&id=' . $edit_id));
                    exit; 
                }
            }
        }


        public function get_bulk_actions() {
            $actions = array(
                'delete' => '刪除'
            );
            return $actions;
        }
    }
}
