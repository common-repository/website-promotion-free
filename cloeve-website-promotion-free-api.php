<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

// plugin files
require_once(dirname(__FILE__) . '/model/cloeve-website-promotion-free-list.php');

class Cloeve_Website_Promotion_Free_API {


    // /wp-json/NAMESPACE/ENDPOINT
    const NAMESPACE = 'cloeve-tech/website-promo-free/v1';
    const RECORD_CLICK = '/wp-json/cloeve-tech/website-promo-free/v1/record_click';

    static function register_endpoints() {
        register_rest_route( self::NAMESPACE, '/record_click', array(
            'methods' => 'POST',
            'callback' => [__CLASS__, 'record_click'],
        ) );
    }

    /**
     *  Record Click API
     */
    static function record_click() {

        // get args
        $promo_id = key_exists('promo_id', $_POST) ? $_POST['promo_id'] : 0;
        $promo_id = sanitize_text_field($promo_id);
        $promo_id = (int)$promo_id;

        // validate
        if(empty($promo_id) || (int)$promo_id <= 0){
            return new WP_Error( 'no_promo', 'No valid promo found, please try again.', array( 'status' => 400 ) );
        }

        // update count++
        Cloeve_Website_Promotion_Free_List::update_click_count_for_id($promo_id);

        // return
        return json_encode(['message' => 'Success!']);
    }
}

