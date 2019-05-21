<?php
class Projects_List extends WP_List_Table{

    function __construct()
    {
        parent::__construct(array(
            'singular' => 'projects',
            'plural' => 'project',
            'ajax' => false
        ));
    }

    function get_columns() {
        $columns= array(
            'cb'  => '<input type="checkbox" />',
            'id' => __('Id'),
            'image'=>__('Image'),
            'name'=>__('Name'),
            'url'=>__('URL'),
            'description'=>__('Description'),
            'edit' => __('Edit')
        );
        return $columns;
    }

    function column_default( $item, $column_name ) {
        $now = new DateTime('now');
        $month = $now->format('m');
        $year = $now->format('Y');

        switch( $column_name ) {
            case 'image':
                $imagePath = 'wp-content/uploads/'  . $year . '/' . $month . '/' . $item[ $column_name ];
                return $imagePath;
            case 'id':
            case 'name':
            case 'url':
            case 'description':
                return $item[ $column_name ];
            case 'edit':
                $editlink  = '/wp-admin/link.php?action=edit&link_id=' . $item["id"];
                return '<a href="'.$editlink.'">Edit</a>';
            default:
                return print_r( $item, true );
        }
    }

    function get_projects($per_page = 5, $page_number = 1) {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}portfolio_projects";
        $records = $wpdb->get_results($sql);


        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        return $result;
    }

    function prepare_items(){
        $this->_column_headers = $this->get_column_info();

        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'projects_per_page', 5 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );


        $this->items = self::get_projects( $per_page, $current_page );
    }

    public static function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}portfolio_projects";

        return $wpdb->get_var( $sql );
    }

    public static function delete_project( $id ) {
        global $wpdb;

        $wpdb->delete(
            "{$wpdb->prefix}portfolio_projects",
            [ 'ID' => $id ],
            [ '%d' ]
        );
    }

    public function get_bulk_actions() {
        $actions = [
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }

    public function process_bulk_action() {
        if ( 'delete' === $this->current_action() ) {

            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sp_delete_project' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                self::delete_project( absint( $_GET['project'] ) );

                wp_redirect( esc_url( add_query_arg() ) );
                exit;
            }

        }

        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {

            $delete_ids = esc_sql( $_POST['bulk-delete'] );
            foreach ( $delete_ids as $id ) {
                self::delete_project( $id );

            }
            wp_redirect( esc_url( add_query_arg() ) );
            exit;
        }
    }

    public function get_sortable_columns() {
        return $sortable = array(
            'id'=>'Id',
            'image'=>'Name',
            'name'=>'Name',
            'url'=>'URL',
            'description' => 'Description'
        );
    }

    function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s"/>', $item['id']
        );
    }
}