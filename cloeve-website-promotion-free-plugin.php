<?php
/*
Plugin Name: Website Promotion Free
description: Build & use custom promotions on your own site.
Version: 1.0
Author: Cloeve Tech
Author URI: https://cloeve.com/tech
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

// plugin files
require_once(dirname(__FILE__) . '/model/cloeve-website-promotion-free-list.php');
require_once(dirname(__FILE__) . '/cloeve-website-promotion-free-api.php');


class Cloeve_Website_Promotion_Free_Plugin {

    // const
    const MENU_TITLE = "Website Promotion Free";
    const ADMIN_PAGE = "cloeve-website-promotion-free";
    const DEFAULT_TAB = "promos";
    const SCRIPT_HANDLE = "cloeve_website_promotion_free";
    const JS_FILE_PATH = '/scripts/cloeve-website-promotion-free.js';
    const CSS_FILE = '/scripts/cloeve-website-promotion-free.css';
    const SHORTCODE_TAG = 'cloeve_website_promotion';

    // class instance
    static $instance;

    // table object
    public $table_obj;

    // class constructor
    public function __construct() {
        add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'plugin_menu' ] );

        // db tables
        dbDelta( Cloeve_Website_Promotion_Free_List::retrieveCreateSQL() );

        // scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'load_scripts']);

        // shortcode
        add_shortcode( 'cloeve_website_promotion', [__CLASS__, 'input_shortcode_handler'] );

        // API
        add_action( 'rest_api_init', ['Cloeve_Website_Promotion_Free_API', 'register_endpoints'] );
    }

    /**
     * scripts
     */
    static function load_scripts() {
        wp_register_style( self::SCRIPT_HANDLE, plugins_url( self::CSS_FILE, __FILE__ )  );
        wp_enqueue_style( self::SCRIPT_HANDLE );
        wp_enqueue_script( self::SCRIPT_HANDLE, plugins_url( self::JS_FILE_PATH, __FILE__ ), array( 'jquery' ) );
    }

    public static function set_screen( $status, $option, $value ) {
        return $value;
    }

    public function plugin_menu() {

        $icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg id="campaign" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19.97 20"><defs><style>.cls-1{fill:#fff;}</style></defs><title>icon_campaign</title><g id="_4GVwbE.tif" data-name="4GVwbE.tif"><path class="cls-1" d="M10.9,19.66a3.24,3.24,0,0,1-2.3-.89q-.67-.64-1.32-1.32A.22.22,0,0,0,7,17.4l-2.23,1c-.11.05-.12.11-.08.22a1,1,0,0,1-.31,1.22,1,1,0,0,1-1.28-.14L.31,16.91a1,1,0,0,1-.12-1.25,1,1,0,0,1,1.18-.33c.15.06.2,0,.26-.11q1.52-3.42,3-6.84Q5.63,6.2,6.6,4a.33.33,0,0,0-.09-.47,3,3,0,0,1-.31-.31A1,1,0,1,1,7.57,1.88C9,3.31,10.42,4.73,11.85,6.16l3.59,3.58,2.7,2.72a1,1,0,0,1,0,1.38,1,1,0,0,1-1.39,0l-.44-.45a.17.17,0,0,0-.24,0l-2.48,1.1c-.15.06-.1.13,0,.21A3.26,3.26,0,0,1,12.74,19,3.32,3.32,0,0,1,10.9,19.66Zm-.14-1.95a1.31,1.31,0,0,0,1.29-.81,1.34,1.34,0,0,0-.29-1.46.18.18,0,0,0-.24-.06l-2.34,1c-.12,0-.15.09,0,.19.25.25.49.51.75.75A1.17,1.17,0,0,0,10.76,17.71Z" transform="translate(-0.02 0)"/><path class="cls-1" d="M12.37,4.53a.91.91,0,0,1-.86-1.23c.19-.51.43-1,.64-1.5s.34-.79.52-1.19a1,1,0,0,1,1-.6.94.94,0,0,1,.8.85.9.9,0,0,1-.1.49c-.36.85-.73,1.71-1.1,2.56A.93.93,0,0,1,12.37,4.53Z" transform="translate(-0.02 0)"/><path class="cls-1" d="M19.07,5.55a.92.92,0,0,1,.89.74.9.9,0,0,1-.54,1L16.85,8.44A.93.93,0,0,1,15.58,8a.94.94,0,0,1,.54-1.25l2.5-1.07A1.14,1.14,0,0,1,19.07,5.55Z" transform="translate(-0.02 0)"/><path class="cls-1" d="M13.73,5.38A.94.94,0,0,1,14,4.66L15.9,2.77a1,1,0,0,1,1.36,0,.93.93,0,0,1,0,1.36C16.61,4.72,16,5.35,15.36,6a1,1,0,0,1-1.61-.42C13.74,5.48,13.73,5.41,13.73,5.38Z" transform="translate(-0.02 0)"/></g></svg>');

        $hook = add_menu_page(
             self::MENU_TITLE,
            self::MENU_TITLE,
            'manage_options',
            self::ADMIN_PAGE,
            [ $this, 'plugin_settings_page' ],
            $icon
        );

        add_action( "load-$hook", [ $this, 'screen_option' ] );

    }


    /**
     * Plugin settings page
     */
    public function plugin_settings_page() {

        // get current tab
        $tab = key_exists('tab', $_GET) ? $_GET['tab'] : self::DEFAULT_TAB;

        // get promo
        $get_promo_id = key_exists('promo_id', $_GET) ? $_GET['promo_id'] : 0;
        $post_promo_id = key_exists('promo_id', $_POST) ? $_POST['promo_id'] : 0;


        // sanitize
        $get_promo_id = sanitize_text_field($get_promo_id);
        $get_promo_id = (int)$get_promo_id;
        $post_promo_id = sanitize_text_field($post_promo_id);
        $post_promo_id = (int)$post_promo_id;

        if(!empty($get_promo_id)){
            $promo = Cloeve_Website_Promotion_Free_List::retrieve_record_by_id($get_promo_id);
        }else{
            $promo = [];
        }

        // new product
        $new_promo_name = key_exists('new_promo_name', $_POST) ? $_POST['new_promo_name'] : '';
        $new_promo_image_url = key_exists('new_promo_image_url', $_POST) ? $_POST['new_promo_image_url'] : '';
        $new_promo_image_ratio = key_exists('new_promo_image_ratio', $_POST) ? $_POST['new_promo_image_ratio'] : 0;
        $new_promo_click_url = key_exists('new_promo_click_url', $_POST) ? $_POST['new_promo_click_url'] : '';
        $new_promo_active = key_exists('new_promo_active', $_POST) ? $_POST['new_promo_active'] == true : 0;

        // sanitize
        $new_promo_name = sanitize_text_field($new_promo_name);
        $new_promo_image_url = sanitize_text_field($new_promo_image_url);
        $new_promo_click_url = sanitize_text_field($new_promo_click_url);
        $new_promo_image_ratio = (int)$new_promo_image_ratio;
        $new_promo_active = (int)$new_promo_active;

        if(!empty($new_promo_name)){

            // set data
            $record_data = [
                'name' => $new_promo_name,
                'image_url' => $new_promo_image_url,
                'image_ratio' => $new_promo_image_ratio,
                'click_url' => $new_promo_click_url,
                'active' => empty($post_promo_id) ? 1 : $new_promo_active,
            ];

            // insert/update
            if(empty($post_promo_id)){
                Cloeve_Website_Promotion_Free_List::insert_new_record($record_data);
            }else{
                Cloeve_Website_Promotion_Free_List::update_record($post_promo_id, $record_data);
            }
        }

         ?>
            <div class="wrap">
                <h2><?php echo self::MENU_TITLE;?>
                    <a href="?page=<?php echo self::ADMIN_PAGE;?>&amp;tab=new_promo" class="page-title-action">Add Promotion</a>
                </h2>
                <nav class="nav-tab-wrapper ">
                    <a href="?page=<?php echo self::ADMIN_PAGE;?>&amp;tab=promos" class="nav-tab <?php echo $tab === 'promos' ? 'nav-tab-active' : '';?>">Promotions</a>
                    <a href="?page=<?php echo self::ADMIN_PAGE;?>&amp;tab=examples" class="nav-tab <?php echo $tab === 'examples' ? 'nav-tab-active' : '';?>">Shortcode Examples</a>
                </nav>

                <?php if($tab == 'examples'){?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-3">
                        <div id="post-body-content">
                            <?php foreach (Cloeve_Website_Promotion_Free_List::CLOEVE_WEBSITE_PROMO_IMAGE_RATIOS AS $index => $ratio){
                                echo '<h2>'.$ratio.':</h2><pre>['. self::SHORTCODE_TAG .' ratio_type="'.$index.'"]</pre>';
                                echo '<br class="clear">';
                            }?>
                        </div>
                    </div>
                    <br class="clear">
                </div>

                <?php }else if($tab == 'new_promo'){?>

                    <form method="post" action="?page=<?php echo self::ADMIN_PAGE;?>&tab=promos" novalidate="novalidate">
                        <input type="hidden" id="promo_id" name="promo_id" value="<?php echo $get_promo_id;?>" />
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label>Promotion Name</label></th>
                                <td><input name="new_promo_name" type="text" id="new_promo_name" placeholder="ex: New Product Promo Banner" class="regular-text" value="<?php echo isset($promo['name']) ? $promo['name'] : '';?>"/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Image URL</label></th>
                                <td><input name="new_promo_image_url" type="text" id="new_promo_image_url" placeholder="ex: https://mywebsite.com/image.png" class="regular-text" value="<?php echo isset($promo['image_url']) ? $promo['image_url'] : '';?>"/></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Image Ratio</label></th>
                                <td>
                                    <select id="new_promo_image_ratio" name="new_promo_image_ratio">
                                        <?php foreach (Cloeve_Website_Promotion_Free_List::CLOEVE_WEBSITE_PROMO_IMAGE_RATIOS AS $index => $ratio){
                                            $checked = isset($promo['image_ratio']) && $promo['image_ratio'] == $index ? 'selected' : '';
                                            echo '<option value="'.$index.'" '.$checked.'>'.$ratio.'</option>';
                                        }?>
                                    </select>

                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Click URL</label></th>
                                <td><input name="new_promo_click_url" type="text" id="new_promo_click_url" placeholder="ex: https://mywebsite.com/product_page" class="regular-text" value="<?php echo isset($promo['click_url']) ? $promo['click_url'] : '';?>"/></td>
                            </tr>
                            <?php if($get_promo_id > 0):?>
                            <tr>
                                <th scope="row">Active</th>
                                <td> <fieldset>
                                        <legend class="screen-reader-text"><span>Active</span></legend>
                                        <label>
                                            <input name="new_promo_active" type="checkbox" id="new_promo_active"  <?php echo isset($promo['active']) && $promo['active'] == 1 ? 'checked' : '';?>/>
                                        </label>
                                    </fieldset></td>
                            </tr>
                            <?php endif;?>
                        </table>
                        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save"  /></p>
                    </form>
                <?php }else{?>
                    <div id="poststuff">
                        <div id="post-body" class="metabox-holder columns-3">
                            <div id="post-body-content">
                                <div class="meta-box-sortables ui-sortable">
                                    <form method="post">
                                        <?php
                                        $this->table_obj->prepare_items();
                                        $this->table_obj->display(); ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <br class="clear">
                    </div>
                <?php }?>

            </div>
        <?php
    }

    /**
     * Screen options
     */
    public function screen_option() {

        // get current tab
        $tab = key_exists('tab', $_GET) ? $_GET['tab'] : self::DEFAULT_TAB;

        $this->table_obj = ($tab === 'promos') ? new Cloeve_Website_Promotion_Free_List() : null;
    }

    /**
     * layout
     * @param int $type
     * @return string
     */
    static function promo_layout($type){
        // retrieve
        $promo = Cloeve_Website_Promotion_Free_List::retrieve_random_record_for_ratio($type);

        // return empty if no result
        if($promo == false){
            return '';
        }
        
        ob_start();
        if($type == 1){ ?>
            <div onclick="cloevePromoFreeClicked('<?php echo $promo['id'];?>', '<?php echo $promo['click_url'];?>')" style="cursor:pointer; width:100%; padding-top:56.25%; background-position:center; background-repeat:no-repeat; background-size:cover; background-image: url('<?php echo $promo['image_url'];?>');"></div>
        <?php }else if($type == 2){ ?>
            <div onclick="cloevePromoFreeClicked('<?php echo $promo['id'];?>', '<?php echo $promo['click_url'];?>')" style="cursor:pointer; width:100%; padding-top:12.5%; background-position:center; background-repeat:no-repeat; background-size:cover; background-image: url('<?php echo $promo['image_url'];?>');"></div>
        <?php }else{ ?>
            <div onclick="cloevePromoFreeClicked('<?php echo $promo['id'];?>', '<?php echo $promo['click_url'];?>')" style="cursor:pointer; width:100%; padding-top:100%; background-position:center; background-repeat:no-repeat; background-size:cover; background-image: url('<?php echo $promo['image_url'];?>');"></div>
        <?php }
        return ob_get_clean();
    }

    /**
     * input_shortcode_handler
     * @param $atts
     * @param $content
     * @param $tag
     * @return string
     */
    static function input_shortcode_handler( $atts, $content, $tag ){

        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        // sanitize
        $type = (int)self::sanitize_shortcode_tag($atts, 'ratio_type', 0);


        return self::promo_layout($type);
    }

    static function sanitize_shortcode_tag($tags, $key, $default) {
        $value = key_exists($key, $tags) ? (string)$tags[$key] : $default;
        if (strpos($value, 'php') !== false || strpos($value, '<') !== false || strpos($value, '>') !== false) {
            $value = $default;
        }

        return $value;
    }

    /** Singleton instance */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}


// load plugin
add_action( 'plugins_loaded', function () {
    Cloeve_Website_Promotion_Free_Plugin::get_instance();
} );