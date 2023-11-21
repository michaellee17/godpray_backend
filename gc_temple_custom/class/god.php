<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('GOD')) {
    class GOD {
        private static $instance = null;

        public static function forge() {
            if (is_null(self::$instance)) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        private function __construct() {
            add_action('admin_menu', [$this, 'custom_theme_settings_menu']);
            add_action('admin_init', [$this, 'custom_theme_settings_init']);
        }

        public function custom_theme_settings_menu() {
            add_menu_page(
                '主神選項', // 選項頁面標題
                '主神選項', // 選單標題
                'manage_options', // 許可權（通常是 'manage_options'）
                'custom-theme-settings', // 頁面識別符
                [$this, 'custom_theme_settings_page'], // 呼叫的方法
                'dashicons-admin-generic' // 選單圖示
            );
        }

        public function custom_theme_settings_page() {
            ?>
            <div class="wrap">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('custom-theme-settings');
                    do_settings_sections('custom-theme-settings');
                    include(plugin_dir_path(__FILE__) . '../template/godEdit.php');
                    ?>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function custom_theme_settings_init() {
            register_setting('custom-theme-settings', 'main_gods');
            add_settings_section('main_gods_section', '主神選項', [$this, 'main_gods_section_callback'], 'custom-theme-settings');
            add_settings_field('main_gods_field', '主神選項', [$this, 'main_gods_field_callback'], 'custom-theme-settings', 'main_gods_section');
        }

        public function main_gods_section_callback() {
            echo '編輯主神名稱陣列(請以逗號分隔，最後一個不用)<br>';
            echo '前十二個將顯示在首頁的主祀神!';
            
        }

        public function main_gods_field_callback() {
            $main_gods = get_option('main_gods');
            echo '<textarea name="main_gods" rows="5" cols="50">' . esc_attr($main_gods) . '</textarea>';
        }
    }
    GOD::forge();
}
