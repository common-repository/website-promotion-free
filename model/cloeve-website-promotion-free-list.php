<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}



class Cloeve_Website_Promotion_Free_List extends WP_List_Table {

    const CLOEVE_WEBSITE_PROMO_TABLE = 'cloeve_website_promo';
    const CLOEVE_WEBSITE_PROMO_TABLE_DELETE = 'cloeve_website_promo_delete';

    const CLOEVE_WEBSITE_PROMO_IMAGE_RATIOS = [ '1:1 (Square)', '16:9 (Typical Video)', '8:1 (Long Horizontal)'];

    /** Class constructor */
    public function __construct() {

        parent::__construct( [
            'singular' => __( 'Website Promotion', 'sp' ), //singular name of the listed records
            'plural'   => __( 'Website Promotions', 'sp' ), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ] );

    }

    /**
     * Table create SQL
     * @return string
     */
    public static function retrieveCreateSQL(){

        // wp db
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        return "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        image_ratio SMALLINT NOT NULL DEFAULT 0,
        click_url VARCHAR(255) NOT NULL,
        view_count INT DEFAULT 0,
        click_count INT DEFAULT 0,
        active BOOL DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY id (id)
	    ) $charset_collate;";
    }


    /**
     * Retrieve data from the database
     * @param $id
     * @return mixed
     */
    public static function retrieve_record_by_id($id) {

        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        $sql = "SELECT * FROM $table_name WHERE id =" .$id;
        $results = $wpdb->get_results( $sql, 'ARRAY_A' );
        if(count($results) <= 0){
            return false;
        }
        return $results[0];
    }

    /**
     * Update data
     * @param $id
     * @return mixed
     */
    public static function update_click_count_for_id($id) {

        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        $sql = "SELECT * FROM $table_name WHERE id =" .$id;
        $results = $wpdb->get_results( $sql, 'ARRAY_A' );
        if(count($results) <= 0){
            return;
        }

        // update count++
        self::update_record($id, ['click_count' => 1 + (int)$results[0]['click_count']]);
    }

    /**
     * Retrieve data from the database
     * @param $type
     * @return mixed
     */
    public static function retrieve_random_record_for_ratio($type) {

        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        // ids
        $sql = "SELECT id FROM $table_name WHERE image_ratio =" .$type;
        $results = $wpdb->get_results( $sql, 'ARRAY_A' );
        if(count($results) <= 0){
            return false;
        }

        // get random
        $ids = [];
        foreach ($results AS $result){
            $ids[] = $result['id'];
        }
        $random_index = rand(0, count($ids)-1);

        // promo
        $sql = "SELECT * FROM $table_name WHERE id =" .$ids[$random_index];
        $results = $wpdb->get_results( $sql, 'ARRAY_A' );
        if(count($results) <= 0){
            return false;
        }

        // update count++
        self::update_record($results[0]['id'], ['view_count' => 1 + (int)$results[0]['view_count']]);

        return $results[0];
    }

    /**
     * Retrieve data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public function retrieve_data( $per_page = 25, $page_number = 1 ) {

        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        $sql = "SELECT * FROM $table_name";

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        return $wpdb->get_results( $sql, 'ARRAY_A' );
    }



    /**
     * @param $new_data
     */
    public static function insert_new_record($new_data){

        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        // set date
        $new_data['created_at'] = gmdate('Y-m-d H:i:s');

        // exec
        $wpdb->insert($table_name, $new_data);
    }


    /**
     * @param $id
     * @param $new_data
     */
    public static function update_record($id, $new_data){

        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        // exec
        $wpdb->update($table_name, $new_data, ['id' => $id]);
    }


    /**
     * Delete a record.
     *
     * @param int $id ID
     */
    public function delete_record( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;


        $wpdb->delete(
            $table_name,
            [ 'id' => $id ],
            [ '%d' ]
        );
    }


    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::CLOEVE_WEBSITE_PROMO_TABLE;

        $sql = "SELECT COUNT(*) FROM $table_name";

        return $wpdb->get_var( $sql );
    }


    /** Text displayed when no data is available */
    public function no_items() {
        _e( 'No data available.', 'sp' );
    }


    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
                return '<a href="?page='. Cloeve_Website_Promotion_Free_Plugin::ADMIN_PAGE.'&amp;tab=new_promo&amp;promo_id='.$item[ $column_name ].'">'.esc_html($item[ $column_name ])."</a>";
            case 'image_url':
                return '<img src="'.$item[ $column_name ].'" style="width:100px"/>';
            case 'click_url':
                return '<a href="'.$item[ $column_name ].'" target="_blank">'.esc_html($item[ $column_name ])."</a>";
            case 'image_ratio':
                return  key_exists($item[ $column_name ], self::CLOEVE_WEBSITE_PROMO_IMAGE_RATIOS) ? self::CLOEVE_WEBSITE_PROMO_IMAGE_RATIOS[$item[ $column_name ]] : 'n/a';
            case 'active':
                return $item[ $column_name ] == 1 ? '&#9989;' : '';
            case 'created_at':
                $timezone = get_option('timezone_string');
                if(empty($timezone)){
                    $created_at = (new DateTime( $item[ $column_name ]))->format('F j, Y h:i A');
                }else{
                    $created_at = (new DateTime( $item[ $column_name ]))->setTimezone(new DateTimeZone($timezone))->format('F j, Y h:i A');
                }
                return esc_html($created_at);
            default:
                return esc_html($item[ $column_name ]);
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }


    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_name( $item ) {

        $delete_nonce = wp_create_nonce( self::CLOEVE_WEBSITE_PROMO_TABLE_DELETE );

        $title = '<strong>' . $item['name'] . '</strong>';

        $actions = [
            'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
        ];

        return $title . $this->row_actions( $actions );
    }


    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'id'    => __( 'ID', 'sp' ),
            'name'    => __( 'Name', 'sp' ),
            'image_url' => __( 'Image', 'sp' ),
            'image_ratio' => __( 'Ratio', 'sp' ),
            'click_url' => __( 'Click URL', 'sp' ),
            'view_count' => __( 'View Count', 'sp' ),
            'click_count' => __( 'Click Count', 'sp' ),
            'active' => __( 'Active', 'sp' ),
            'created_at' => __( 'Created', 'sp' )
        ];

        return $columns;
    }


    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'id' => array( 'id', true ),
            'name' => array( 'name', true ),
            'image_url' => array( 'image_url', true ),
            'image_ratio' => array( 'image_ratio', true ),
            'click_url' => array( 'click_url', true ),
            'view_count' => array( 'view_count', true ),
            'click_count' => array( 'click_count', true ),
            'active' => array( 'active', true ),
            'created_at' => array( 'created_at', true )
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = 25;
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );

        $this->items = $this->retrieve_data( $per_page, $current_page );
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, self::CLOEVE_WEBSITE_PROMO_TABLE_DELETE ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                $this->delete_record( absint( $_GET['id'] ) );
            }

        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {

            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                $this->delete_record( $id );

            }
        }
    }

}