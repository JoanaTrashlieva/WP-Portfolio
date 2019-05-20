<?php
class Projects_List extends WP_List_Table
{

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
            'cb' => ('id'),
            'id' => ('Id'),
            'image'=>__('Image'),
            'name'=>__('Name'),
            'url'=>__('URL'),
            'description'=>__('Description')
        );
        return $columns;
    }

    function prepare_items() {
        $columns = $this->get_columns();


        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        $query = "SELECT * FROM " .$wpdb->prefix . "portfolio_projects";

        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';

        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        $totalitems = $wpdb->query($query); //return the total number of affected rows

        $perpage = 5;

        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';

        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }

        //How many pages do we have in total?
        $totalpages = ceil($totalitems / $perpage);

        //adjust the query to take pagination into account
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int)$offset . ',' . (int)$perpage;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $_wp_column_headers[$screen->id] = $columns;
        $this->items = $wpdb->get_results($query);
    }

//    function column_default( $item, $column_name ) {
//        switch( $column_name ) {
//            case 'id':
//            case 'image':
//            case 'name':
//            case 'url':
//            case 'description':
//                return $item[ $column_name ];
//            default:
//                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
//        }
//    }

    public function get_sortable_columns() {
        return $sortable = array(
            'id'=>'Id',
            'image'=>'Name',
            'name'=>'Name',
            'url'=>'URL',
            'description' => 'Description'
        );
    }

    public static function get_projects($per_page = 5, $page_number = 1)
    {

        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}portfolio_projects";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;


        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    public static function delete_project($id)
    {
        global $wpdb;

        $wpdb->delete(
            "{$wpdb->prefix}portfolio_projects",
            ['id' => $id],
            ['%d']
        );
    }

    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}portfolio_projects";

        return $wpdb->get_var($sql);
    }

    public function no_items() {
        _e( 'No projects avaliable.', 'sp' );
    }

//    function column_name( $item ) {
//
//        // create a nonce
//        $delete_nonce = wp_create_nonce( 'sp_delete_project' );
//
//        $title = '<strong>' . $item['name'] . '</strong>';
//
//        $actions = [
//            'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
//        ];
//
//        return $title . $this->row_actions( $actions );
//    }
//
//    function column_cb( $item ) {
//        return sprintf(
//            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
//        );
//    }

    /**
     * Display the rows of records in the table
     * @return string, echo the markup of the rows
     */
//    function display_rows() {
//        $records = $this->items;
//        if(!empty($records)){foreach($records as $rec){
//
//            $editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->id;
//            echo '<tr id="record_' .$rec->id. '" style="max-width: 1024px;">';
//
//            echo '<td>'.stripslashes($rec->id).'</td>';
//            echo '<td>'.stripslashes($rec->name).'</td>';
//            echo '<td>'.stripslashes($rec->url).'</td>';
//            echo '<td>'.$rec->description.'</td>';
//            echo '<td><a href="'.$editlink.'">Edit</a></td>';
//
//            echo'</tr>';
//        }}
//    }

//    public static function get_projects( $per_page = 5, $page_number = 1 ) {
//
//        global $wpdb;
//
//        $sql = "SELECT * FROM {$wpdb->prefix}portfolio_projects";
//
//        if ( ! empty( $_REQUEST['orderby'] ) ) {
//            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//        }
//
//        $sql .= " LIMIT $per_page";
//
//        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
//
//
//        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
//
//        return $result;
//    }
}