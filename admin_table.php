<?php
class Projects_List extends WP_List_Table{

    function __construct(){
        parent::__construct(array(
            'singular' => 'projects',
            'plural' => 'project',
            'ajax' => false
        ));
    }

    //Table columns
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

    //Checkbox column
    function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s"/>', $item['id']
        );
    }

    public function get_sortable_columns() {
        return $sortable = array(
            'id'=>'Id',
            'image'=>'Image',
            'name'=>'Name',
            'url'=>'URL',
            'description' => 'Description'
        );
    }

    //Getting the data from the db table
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

    function column_default($item, $column_name ) {
        $now = new DateTime('now');
        $month = $now->format('m');
        $year = $now->format('Y');

        switch( $column_name ) {
            case 'image':
                if ($item[ $column_name ] != null){
                    return sprintf('<img src= /wp-content/uploads/'  . $year . '/' . $month . '/' . $item[ $column_name ] . ' style="height: auto; width: 100px;">' );
                } else {
                    return 'no image';
                }
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

//                $actions = array(
//                    'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['name']),
//                );
//
//                return sprintf('%1$s <span style="color:silver"></span>%3$s',
//                    /*$1%s*/ $item[ $column_name ],
//                    /*$2%s*/ $item[ $column_name ],
//                    /*$3%s*/ $this->row_actions($actions)
//                );
//                break;
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

    //Total count of items - top right corner
    public static function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}portfolio_projects";

        return $wpdb->get_var( $sql );
    }

    //Bulk actions (delete)
    public function get_bulk_actions() {
        return array(
            'delete' => __( 'Delete')
        );
    }

    public function process_bulk_action()
    {
        // security check!
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Security check failed!' );

        }

        $action = $this->current_action();

        switch ( $action ) {
            case 'delete':
                var_dump("Delete");
                break;

            default:
                var_dump("Default");
                return;
                break;
        }
        return;
    }

    public static function delete_project($id) {
        global $wpdb;

        $wpdb->delete(
            "{$wpdb->prefix}portfolio_projects",
            [ 'id' => $id]
        );
    }
}