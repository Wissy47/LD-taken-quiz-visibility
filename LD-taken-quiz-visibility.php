<?php
/**
 * Plugin Name:       LD Taken Quiz Visibility
 * Plugin URI:        https://github.com/Wissy47
 * Description:       This plugin control the visibility of quiz questions already taken by a learndash student. This quiz qestion are available on the Learndash student profile under the statistic section.
 * Version:           1.0.2
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Wisdom Ighofose
 * Author URI:        https://www.linkedin.com/in/wisdom-ighofose-875424128
 * Text Domain:       LD-taken-quiz-visibility
 **/
 

 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if( !is_plugin_active('sfwd-lms/sfwd_lms.php' ) ) {
    add_action( 'admin_init', 'LD_taken_quiz_visibility_deactivate_plugins' );
    add_action( 'admin_notices', 'LD_taken_quiz_visibility_admin_notice' );
}

function LD_taken_quiz_visibility_deactivate_plugins() {
    deactivate_plugins( plugin_basename( __FILE__ ) );
}

function LD_taken_quiz_visibility_admin_notice() {
    $child_plugin = __( 'LD taken quiz visibility', 'textdomain' );
    $parent_plugin = __( 'LearnDash LMS', 'textdomain' );

    echo '<div class="error"><p>'
        . sprintf( __( '%1$s requires %2$s to function correctly. Please activate %2$s before activating %1$s. For now, the plugin has been deactivated.', 'textdomain' ), '<strong>' . esc_html( $child_plugin ) . '</strong>', '<strong>' . esc_html( $parent_plugin ) . '</strong>' )
        . '</p></div>';

   if ( isset( $_GET['activate'] ) )
        unset( $_GET['activate'] );
}


add_action( 'admin_menu', 'ld_taken_quiz_visibility_menu' );  

function ld_taken_quiz_visibility_menu(){    
	// $page_title = 'LD Taken Quiz Visibility';  
	// $menu_title = 'LDTQV Control'; 
	// $capability = 'manage_options';  
	// $menu_slug  = 'ld-taken-quiz-visibility';  
	// $function   = 'ld_taken_quiz_visibility_page';  
	// $icon_url   = 'dashicons-media-code';  
	// $position   = 4;   
	// add_menu_page($page_title,
	// 			  $menu_title, 
	// 			  $capability,
	// 			  $menu_slug, 
	// 			  $function,  
	// 			  $icon_url, 
	// 			  $position ); 

    global $ld_taken_quiz_visibility_menu;
    $ld_taken_quiz_visibility_menu = add_submenu_page(
                            'learndash-lms', //The slug name for the parent menu
                            __( 'LDTQV Control', 'learndash-easy-dash' ), //Page title
                            __( 'LDTQV Control', 'learndash-easy-dash' ), //Menu title
                            'manage_options', //capability
                            'ld-taken-quiz-visibility', //menu slug 
                            'ld_taken_quiz_visibility_page' //function to output the content
    );
}
function ld_taken_quiz_visibility_page(){ ?>
<h1>LearnDash Quiz Questions Visibiltiy Control</h1>
<form method="post" action="options.php">
<?php settings_fields( 'ld-taken-quiz-visibility-settings' ); ?>
<?php do_settings_sections( 'ld-taken-quiz-visibility-settings' ); ?>
	<p>
		Select quiz you to want hide its questions on Student Statistic page <br>
        <strong>Student user role should be "subscriber" for this to work</strong>
	</p>
    
    <table class="form-table">
        <?php 
            $posts = get_posts (array (
                'numberposts' => -1,   // -1 returns all posts
                'post_type' => 'sfwd-quiz',
                'orderby' => 'title',
                'order' => 'ASC'
                ));
            $options = is_array(get_option('ld_taken_quiz_visibility'))? get_option('ld_taken_quiz_visibility'):[];
            foreach ($posts as $post): ?>
                <tr valign="top">
                    <th scope="row"><?php echo esc_attr($post->post_title);?></th>
                    <td>
                        <label>Hide <input type="checkbox" name="ld_taken_quiz_visibility[<?php echo $post->ID ?>]" value="hide"<?php echo array_key_exists($post->ID, $options) ? "checked": ""; ?>></label>
                    </td>
                </tr>
            <?php endforeach; ?>
         
    </table>
<?php submit_button(); ?>
</form>
<?php 
}
add_action( 'admin_init', 'update_ld_taken_quiz_visibility' ); 

function update_ld_taken_quiz_visibility() {   register_setting( 'ld-taken-quiz-visibility-settings', 'ld_taken_quiz_visibility' ); } 

add_filter('learndash_question_statistics_data', 'ld_taken_quiz_visibility_filter',10,3);

/**
 * If the user is not a subscriber, then return the question data. If the user is a subscriber, then
 * check if the quiz is in the options array. If it is, then set the question name to null.
 * 
 * @param question_data The data that is returned to the user.
 * @param quiz The quiz object
 * @param http_post_data This is the data that is sent to the server when the user submits the quiz.
 * 
 * @return The question data is being returned.
 */
function ld_taken_quiz_visibility_filter( $question_data, $quiz, $http_post_data ) {
	$options = get_option( 'ld_taken_quiz_visibility' );
    $quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz->getId());
    //In the case that you are using a custom user role for your user E.G 'student' you need to replace 'subscriber' with it.
	if ( !current_user_can('subscriber') ) {return $question_data;}

    if (array_key_exists($quiz_post_id, $options)) {     
        $question_data['questionName'] = null;
    }  
	return $question_data;
}