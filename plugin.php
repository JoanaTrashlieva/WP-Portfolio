<?php
/*
Plugin Name: JT Portfolio WP
Description: Simple wordpress plugin to display portfolio projects on your site's pages.
Author: Joana Trashlieva
Version: 0.1
*/
?>
<?php
//Our class extends the WP_List_Table class, so we need to make sure that it's there
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

$file = ABSPATH."wp-content/plugins/WP-Portfolio/admin_table.php";
require( $file );

function admin_register_head() {
    $siteurl = get_option('siteurl');
    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/admin_table.css';
    echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
}
add_action('admin_head', 'admin_register_head');

register_activation_hook( __FILE__, 'portfolio_dbtable_install' );
add_action('admin_menu', 'portfolio_menu');
add_shortcode('projects', 'display_projects');

function portfolio_dbtable_install() {

    //variable for a dynamic table prefix - e.g. if plugin is moved to another installation
    global $wpdb;

    /*
    initialise a variable that hold the db table name (use the variable for prefix
    to make it more transferable)
    */
    $table_name = $wpdb->prefix . "portfolio_projects";

    //avoids replacing some characters with "?"
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
	  	id mediumint(9) NOT NULL AUTO_INCREMENT,
	  	name tinytext NOT NULL,
	  	url varchar(55) DEFAULT '' NOT NULL,
        description text NOT NULL,
        image varchar(50),
	  	PRIMARY KEY  (id)
	) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
}

function portfolio_menu() {
    $page_title = 'JT Portfolio Plugin Page';
    $menu_title = 'Portfolio';
    $capability = 'manage_options';
    $menu_slug = 'jt-portfolio-plugin';
    $function = 'init_page';
    $icon_url = '';
    $position = 100;

    add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
}

function init_page(){
    ?>
    <div class="wrap">
        <h2>Create new project</h2>
        <form method="post" <?php echo esc_url( admin_url( 'admin-post.php' ) ); ?> enctype="multipart/form-data">
            <p><strong>Name:</strong><br />
                <input required type="text" name="project-name" size="45" placeholder="Please enter the name of your project"/>
            </p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="project-name" />

            <p><strong>Address:</strong><br />
                <input type="url" name="project-url" size="45" placeholder=http://example.com/>
            </p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="project-url" />

            <p><strong>Short description:</strong><br />
                <input type="text" name="project-description" size="90" placeholder="Say a few words about it"/>
            </p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="project-description" />

            <p><strong>Thumbnail (png/jpeg/jpg):</strong><br />
            <input type='file' id="project-image" name="project-image" accept="image/png, , image/jpg">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="project-image" />

            <p>
                <input type="submit" name="Submit" value="Save"/>
            </p>
        </form>
    </div>
    <?php
    if (isset($_POST['Submit'])) {
        $name = $_POST["project-name"];
        $url = $_POST["project-url"];
        $description = $_POST["project-description"];
        $imageName = $_FILES["project-image"]['name'];
        imageUpload();
        portfolio_dbtable_populate($name, $url, $description, $imageName);
    }

    $wp_list_table = new Projects_List();
    echo '<div class="wrap"><h2>Existing projects</h2>';
        echo '<form id="existing-projects" method="post">';
            $page  = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
            $paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
            printf( '<input type="hidden" name="page" value="%s" />', $page );
            printf( '<input type="hidden" name="paged" value="%d" />', $paged );
            $wp_list_table->prepare_items(); // this will prepare the items AND process the bulk actions
            $wp_list_table->display();
        echo '</form>';
    echo '</div>';
}

function portfolio_dbtable_populate($name, $url, $description, $imageName){
    global $wpdb;

    $table_name = $wpdb->prefix . 'portfolio_projects';

    $wpdb->insert($table_name, array('name' => $name, 'url' => $url, 'description' => $description, 'image' => $imageName));
}

function imageUpload(){
    if(isset($_FILES['project-image'])){
        $uploaded = media_handle_upload('project-image', 0);

        if(is_wp_error($uploaded)){
//            echo "Error uploading file: " . $uploaded->get_error_message();
        }else{
            echo "File upload successful!";
        }
    }
}

function display_projects(){
    $now = new DateTime('now');
    $month = $now->format('m');
    $year = $now->format('Y');

    return '
    <div class="projects">
        <div class="block">
            <div class="thumbnail">
                <img src="wp-content/uploads/'  . $year . '/' . $month . '/' . $imageName . '" />
            </div>
            <div class="name">'. $name .'</div>
            <div class="url">
                <a href="'. get_option('project-url').'">'. $url .'</a>
            </div>
            <div class="description">'. $description .'</div>
        </div>
    </div>';
}

function remove_image_sizes( $sizes, $metadata ) {
    return [];
}
add_filter( 'intermediate_image_sizes_advanced', 'remove_image_sizes', 10, 2 );

