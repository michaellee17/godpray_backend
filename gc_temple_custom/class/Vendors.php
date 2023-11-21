<?php
/* Description:修改供應商登入後的介面
 */
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists('VENDORS') ){
    class VENDORS{
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
            //供應商商品內頁分類分類篩選
            add_filter('list_terms_exclusions', array($this,'custom_list_terms_exclusions'), 10, 3);
            
            #GC_minos:2023-11-09 使應商在瀏覽後台商品清單時，其所見的商品分類為該供應商所擁有修改權限的商品分類，而非所有分類
            add_filter( 'get_terms_args', [ $this,'modify_admin_product_cat_dropdown' ], 10, 2 );

            #GC_minos:2023-11-09 在上述的hook之後只能過濾其分類，但每個分類裡面有多少商品還是會顯示term meta裡面的product_count_product_cat值
            #該hook使用每個分類的ID重新去查詢該供應商在該分類所擁有的正確數量
            add_filter( 'get_terms', [ $this, 'recalculate_product_cat_count' ], 10, 4 );

            //商品增加作者修改 for 管理員代供應商創商品
            add_action( 'init', [ $this,'add_author_post_type_support'], 10 );

        }


        function add_author_post_type_support() {
            add_post_type_support( 'product',  array(  'author' ) );
        }


        function custom_list_terms_exclusions($exclusions, $args) {
            if (is_admin() && current_user_can('yith_vendor')) {
                global $wpdb;
        
                $term_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE slug = %s", 'cultural_product'));
        
                if ($term_id) {
                    $excluded_term_ids = get_term_children($term_id, 'product_cat');
                    $excluded_categories = array($term_id);
                    $excluded_categories = array_merge($excluded_categories, $excluded_term_ids);
                    $excluded_categories = implode(',', $excluded_categories);
        
                    if (!empty($excluded_categories)) {
                        $exclusions = " AND t.term_id NOT IN ($excluded_categories)";
                    }
                }
            }
        
            return $exclusions;
        }
    

        function modify_admin_product_cat_dropdown( $args, $taxs ) 
        {
            global $wpdb,$current_user;

            #GC_minos:2023-11-09記憶體會爆掉的原因是因為每一個頁面其實都會經過一百多次的list_terms_exclusions這個hook
            #所以限定只有符合以下條件的terms_args才會進入判斷
            if (is_admin() && current_user_can('yith_vendor') && 
                isset( $taxs[0] ) && 'product_cat' == $taxs[0] && 
                isset( $args['show_option_none'] ) && '請選擇分類' == $args['show_option_none'] 
            ) {
                #GC_minos:2023-11-09使用以下Query取得「以供應商所建立的商品的來取得所有商品分類ID」
                $query = sprintf( "SELECT
                terms.term_id
                FROM
                    %s AS terms
                JOIN %s AS taxonomy
                ON
                    terms.term_id = taxonomy.term_id
                JOIN %s AS tr
                ON
                    taxonomy.term_taxonomy_id = tr.term_taxonomy_id
                WHERE
                    tr.object_id IN(
                    SELECT
                        ID
                    FROM
                        `wp_posts`
                    WHERE
                        `post_author` = %d AND post_type = 'product' AND post_status != 'auto-draft'
                ) AND taxonomy.taxonomy = 'product_cat'" , 
                    $wpdb->terms,
                    $wpdb->term_taxonomy,
                    $wpdb->term_relationships,
                    $current_user->ID
                );

                $include_term_ids = $wpdb->get_col( $query );
                if( $include_term_ids ){
                    $args['term_taxonomy_id'] = $include_term_ids;
                    $args['from_gc'] = true; #GC_minos:作為標記給接下來的方法判斷使用
                    $args['cache_results'] = false; #GC_minos:不要將重新查詢結果做cache，因為這是for個別供應商使用
                }
               
            }
            
            return $args;
        }

        function recalculate_product_cat_count( $terms, $taxonomy, $query_vars, $term_query ){
            if( $terms && isset( $query_vars['from_gc']) ){
                foreach( $terms as &$term ){
                    $term->count = $this->get_product_cat_count( $term->term_id );
                }
            }
            return $terms;
        }

        /**
         * Created by GC_minos
         * Created on 2023-11-09
         * Created for 以商品分類的ID取得該供應商在該分類的商品數量
         */
        function get_product_cat_count( $term_id ){
            global $wpdb, $current_user;

            $count = 0;
            $query = sprintf(
                "SELECT
                COUNT(*)
                FROM
                    %s AS tr
                JOIN %s AS p
                ON
                    p.ID = tr.object_id
                WHERE
                    p.post_author = %d AND p.post_status != 'auto-draft' AND tr.term_taxonomy_id = %d",
                $wpdb->term_relationships,
                $wpdb->posts,
                $current_user->ID,
                $term_id
            );
            return $wpdb->get_var( $query );
        }
        
    }
    VENDORS::forge();
}