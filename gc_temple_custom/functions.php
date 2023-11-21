<?php
if ( function_exists( 'YITH_Vendors' ) ) {
    function yith_wpv_vendor_to_remove_menu_items( $to_remove ) {
        return array( 'tools.php' );
    }


    function yith_wpv_vendor_menu_items( $to_add ) {
        $vendor = yith_get_vendor( 'current', 'user' );
        if ( $vendor->is_valid() && $vendor->has_limited_access() ) {
            $user     = wp_get_current_user();
            $to_add[] = 'edit.php';
            $to_add[] = 'edit.php?post_type=page';
        }


        return $to_add;
    }


    function yith_wpv_vendors_allowed_post_types( $allowed ) {
        $allowed[] = 'post';
        $allowed[] = 'page';


        return $allowed;
    }


    function yith_wcmv_add_page_caps() {
        $role = get_role( YITH_Vendors()->get_role_name() );
        if ( $role instanceof WP_Role ) {
            $role->add_cap( 'edit_pages' );
            $role->add_cap( 'edit_posts' );
            $role->add_cap('edit_published_posts');
    }
 }


 function yith_wcmv_pre_get_post_customizzation( $query ) {
 if ( is_admin() && 'post' == $query['post_type'] || 'page' == $query['post_type'] ) {
 $vendor = yith_get_vendor( 'current', 'user' );
 if ( $vendor->is_valid() && $vendor->has_limited_access() ) {
 $query['author'] = $vendor->owner;
 }
 }

 return $query;
 }


 function yith_wcmv_count_posts( $counts, $type, $perm ) {
 $vendor = yith_get_vendor( 'current', 'user' );


 if ( ! $vendor->is_valid() || in_array( $type, array( 'post', 'page' ) ) ) {


 global $wpdb;


 if ( ! post_type_exists( $type ) ) {
 return new stdClass;
 }


 $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = 0 AND post_author = %d";
 if ( 'readable' == $perm && is_user_logged_in() ) {
 $post_type_object = get_post_type_object( $type );


 }
 $query .= ' GROUP BY post_status';


 $results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, get_current_user_id() ), ARRAY_A );
 $counts = array_fill_keys( get_post_stati(), 0 );


 foreach ( $results as $row ) {
 if ( array_key_exists( $row['post_status'], $counts ) ) {
 $counts[ $row['post_status'] ] = $row['num_posts'];
 }
 }


 $counts = (object) $counts;
 }


 return $counts;
 }


 add_filter( 'yith_wpv_vendor_menu_items', 'yith_wpv_vendor_menu_items' );
 add_filter( 'yith_wpv_vendor_to_remove_menu_items', 'yith_wpv_vendor_to_remove_menu_items' );
 add_filter( 'yith_wcmv_disable_post', '__return_false' );
 add_filter( 'yith_wpv_vendors_allowed_post_types', 'yith_wpv_vendors_allowed_post_types' );
 add_action( 'admin_init', 'yith_wcmv_add_page_caps' );
 add_filter( 'request', 'yith_wcmv_pre_get_post_customizzation', 20 );
 add_filter( 'wp_count_posts', 'yith_wcmv_count_posts', 20, 3 );
}