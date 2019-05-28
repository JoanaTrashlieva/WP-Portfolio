<?php
/*
Plugin Name: JT Portfolio WP
Description: Simple wordpress plugin to display portfolio projects on your site's pages.
Author: Joana Trashlieva
Version: 0.1
*/

add_action('admin_head', 'admin_register_head'); //admin menu
add_action('admin_menu', 'portfolio_menu'); //calls function
register_activation_hook( __FILE__, 'portfolio_dbtable_install' ); //runs on activating the plugin
add_shortcode('projects', 'display_projects'); //shortcode for using the plugin
add_filter( 'intermediate_image_sizes_advanced', 'remove_image_sizes', 10, 2 ); //stop wordpress from resizing images on upload
add_action('wp_enqueue_scripts', 'frontend_scripts');



//Link table file
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
$file = ABSPATH."wp-content/plugins/WP-Portfolio/admin_table.php";
require($file);

//Stops wordpress from resizing images on upload
function remove_image_sizes( $sizes, $metadata ) {
    return [];
}

//CSS, JS backend
function admin_register_head() {
    $siteurl = get_option('siteurl');
    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__));
    $css = '/css/admin_table.css';
    $css1= '/css/project-tiles.css';
    $js = '/js/quick_edit.js';
    echo "<script type='text/javascript' src='$url$js'></script>";
    echo "<link rel='stylesheet' type='text/css' href='$url$css'/>";
    echo "<link rel='stylesheet' type='text/css' href='$url$css1'/>";
}

//CSS frontend
function frontend_scripts() {
    $siteurl = get_option('siteurl');
    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__));
    $css1= '/css/project-tiles.css';
    wp_register_style( 'project-tiles', $url.$css1 );
    wp_enqueue_style( 'project-tiles' );
}

//Db connection
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
	) $charset_collate";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
}

//Admin menu
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

//Admin form
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
                <label>
                    <textarea rows="4" cols="50" name="project-description" placeholder="Say a few words about it"></textarea>
                </label>
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
            $wp_list_table->prepare_items();
            $wp_list_table->display();
    echo '</form>';
    echo '</div>';
}

//Send data to db table
function portfolio_dbtable_populate($name, $url, $description, $imageName){
    global $wpdb;
    $table_name = $wpdb->prefix . 'portfolio_projects';
    $wpdb->insert($table_name, array('name' => $name, 'url' => $url, 'description' => $description, 'image' => $imageName));
}

//Send updates to db table
function portfolio_dbtable_populate_updated($nameUpdated, $urlUpdated, $descriptionUpdated, $imageNameUpdated, $item){
    global $wpdb;
    $table_name = $wpdb->prefix . 'portfolio_projects';
    $wpdb->update($table_name, array('name' => $nameUpdated, 'url' => $urlUpdated, 'description' => $descriptionUpdated, 'image' => $imageNameUpdated), array('id'=> $item['id']));
}

function imageUpload(){
    if(isset($_FILES['project-image'])){
        $uploaded = media_handle_upload('project-image', 0);

        if(is_wp_error($uploaded)){
            echo "Error uploading image: " . $uploaded->get_error_message();
        }else{
            echo "File upload successful!";
        }
    }
}

function imageUploadUpdated(){
    if(isset($_FILES['project-image-updated'])){
        $uploaded = media_handle_upload('project-image-updated', 0);

        if(is_wp_error($uploaded)){
            echo "Error uploading image: " . $uploaded->get_error_message();
        }else{
            echo "File upload successful!";
        }
    }
}

//Display on frontend
function display_projects(){
    $now = new DateTime('now');
    $month = $now->format('m');
    $year = $now->format('Y');

    global $wpdb;
    $table_name = $wpdb->prefix . 'portfolio_projects';
    $projects = $wpdb->get_results( "SELECT * FROM $table_name");
    ?>
    <div class="projects"><?php
    foreach ($projects as $project) {
        echo '
            <div class="project">
                <div class="thumbnail">
                    <img src="wp-content/uploads/'  . $year . '/' . $month . '/'. $project->image .'"/>
                </div>
                <div class="content">
                    <div class="name">'. $project->name .'</div>
                    <div class="url">
                        <a href="'. get_option('project-url').'">'. $project->url .'</a>
                    </div>
                    <div class="description">'. $project->description .'</div>
                </div>
            </div>
        ';
        $project++;
    }
    ?></div><?php
}

