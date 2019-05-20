<?php
/*
Plugin Name: JT Portfolio WP
Description: Simple wordpress plugin to display portfolio projects on your site's pages.
Author: Joana Trashlieva
Version: 0.1
*/
?>
<?php
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
        image text NOT NULL,
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
    imageUpload();
    ?>
    <div class="wrap">
        <h2>Create new project</h2>
        <form method="post" <?php echo esc_url( admin_url( 'admin-post.php' ) ); ?> enctype="multipart/form-data">
            <p><strong>Name:</strong><br />
                <input type="text" name="project-name" size="45" placeholder="Please enter the name of your project"/>
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

            <p><strong>Thumbnail (png/jpeg):</strong><br />
            <input type='file' id="project-image" name="project-image" accept="image/png, image/jpeg">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="project-image" />

            <p>
                <input type="submit" name="Submit" value="Save"/>
            </p>
        </form>
    </div>
    <?php
    if (isset($_POST['Submit'])){
        $name = $_POST["project-name"];
        $url = $_POST["project-url"];
        $description = $_POST["project-description"];
        $imageName = $_FILES["project-image"]['name'];
        portfolio_dbtable_populate($name, $url, $description, $imageName);
    }
}

function portfolio_dbtable_populate($name, $url, $description, $imageName){
    global $wpdb;

    $table_name = $wpdb->prefix . 'portfolio_projects';

    $wpdb->insert($table_name, array('name' => $name, 'url' => $url, 'description' => $description, 'image' => $imageName));
}

function imageUpload(){
    if(isset($_FILES['project-image'])){
        $jpg = $_FILES['project-image'];
        $uploaded=media_handle_upload('project-image', 0);

        if(is_wp_error($uploaded)){
            echo "Error uploading file: " . $uploaded->get_error_message();
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
                <img src="wp-content/uploads/'  . $year . '/' . $month . '/' . get_option('project-image') . '" />
            </div>
            <div class="name">'.get_option('project-name').'</div>
            <div class="url">
                <a href="'. get_option('project-url').'">'.get_option('project-url').'</a>
            </div>
            <div class="description">'.get_option('project-description').'</div>
        </div>
    </div>';
}