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
        return sprintf('<input type="checkbox" name="id[]" value="%s"/>', $item['id']);
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
                return '<div id="id-edit">'.print_r( $item['id'], true ).'</div>';
                break;
            case 'name':
            case 'url':
            case 'description':
                return $item[ $column_name ];
            case 'edit':
                global $wpdb;
                $id = $item['id'];
                $sql = "SELECT * FROM {$wpdb->prefix}portfolio_projects WHERE id=$id";
                $info = $wpdb->get_results($sql);

                if($info){
                    $oldImage = $info[0]->image;
                    $oldName = $info[0]->name;
                    $oldURL = $info[0]->url;
                    $oldDescr = $info[0]->description;
                } else {
                    return null;
                }

                if($oldImage){
                    $imageName = $oldImage;
                } else {
                    $imageName = "No Image";
                }

                if (isset($_POST['Save'])) {
                    $id = $_POST['quickAction'];
                    $nameUpdated = $_POST["project-name-updated"];
                    $urlUpdated = $_POST["project-url-updated"];
                    $descriptionUpdated = $_POST["project-description-updated"];
                    $imageNameUpdated = $_POST["project-image-updated"];
                    imageUploadUpdated();
                    portfolio_dbtable_populate_updated($id, $nameUpdated, $urlUpdated, $descriptionUpdated, $imageNameUpdated, $item);
                    echo "<meta http-equiv='refresh' content='0'>";
                }

                if (isset($_POST['Delete'])) {
                    $id = $_POST['quickAction'];
                    $wpdb->delete(
                        "{$wpdb->prefix}portfolio_projects",
                        ['id' => $id],
                        ['%d']
                    );
                    echo "<meta http-equiv='refresh' content='0'>";
                }

                return '<a class="test" onclick="toggleQuickEdit(this)" data-id-number='. $item['id'].'>Edit</a>
                        <form method="post">
                            <input type="hidden" value="'.$item['id'].'" name="quickAction" />
                            <tr class="quick-edit" data-tr-number='. $item['id'].'>
                                <td colspan="3" >
                                    <input type=\'file\' id="project-image-updated" name="project-image-updated" accept="image/png, , image/jpg" />
                                    <input type="hidden" name="action" value="update" />
                                    <input type="hidden" name="page_options" value="project-image-updated" />
                                    <p>Current: <i>'.$imageName.'</i></p>
                                </td>
                                <td><p><p>New name: </p>
                                        <input required type="text" name="project-name-updated" size="20" value="' . $oldName . '"/>
                                        <input type="hidden" name="action" value="update" />
                                        <input type="hidden" name="page_options" value="project-name-updated" />
                                    </p></td>
                                <td><p><p>New url: </p>
                                        <input type="url" name="project-url-updated" size="20" value="'.$oldURL.'">
                                        <input type="hidden" name="action" value="update" />
                                        <input type="hidden" name="page_options" value="project-url-updated" />
                                    </p></td>
                                <td><p><p>New description: </p>
                                        <textarea rows="4" cols="30" name="project-description-updated">'.$oldDescr.'</textarea>
                                        <input type="hidden" name="action" value="update" />
                                        <input type="hidden" name="page_options" value="project-description-updated" />
                                    </p></td>
                                <td class="testing">
                                    <input id="save" name="Save" type="submit" value="Save" />
                                    <input id="delete" type="submit" value="Delete" name="Delete"/>
                                    <input id="discard" type="reset" value="Discard" onclick="closeQuickEdit(this)" /><br />
                                </td>
                            </tr>
                        </form>';
                break;
            default:
                return print_r( $item, true );
                break;
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

    public function process_bulk_action(){
        // security check!
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) ) {
                wp_die('Security check failed!');
            }
        }

        $action = $this->current_action();
        switch ( $action ) {
            case 'delete':
                global $wpdb;
                $table_name = $wpdb->prefix.'portfolio_projects';

                if ('delete' === $this->current_action()) {
                    $ids = isset($_REQUEST['id'])? $_REQUEST['id'] : array();

                    if (is_array($ids)) {
                        $ids = implode(',', $ids);
                    }
                    if (!empty($ids)) {
                        $wpdb->query("DELETE * FROM $table_name WHERE id IN($ids)");
                    }
                }
                break;
            default:
                return;
                break;
        }
        return;
    }
}