<?php
/*
Plugin Name: DupeOff
Plugin URI: http://dupeoff.com
Description: Plagiarism and Duplicate Content Detection - Check your content for duplication and plagiarism straight from your Wordpress admin
Version: 1.42
Author: DupeOff.com
Author URI: http://dupeoff.com
*/

include('classes/dupeoff_api.php');
include('inc/dupeoff_ajax.php');

/* Define the custom box */
add_action( 'add_meta_boxes', 'dupeoff_add_custom_box' );


/* Adds a box to the main column on the Post and Page edit screens */
function dupeoff_add_custom_box() {
	add_meta_box( 
	'dupeoff',
	__( 'DupeOff - Plagiarism and Duplicate Content Report', 'dupeoff_textdomain' ),
	'dupeoff_inner_custom_box',
	'post' 
	);
	add_meta_box( 
	'dupeoff',
	__( 'DupeOff - Plagiarism and Duplicate Content Report', 'dupeoff_textdomain' ),
	'dupeoff_inner_custom_box',
	'page' 
	);
}

/* Prints the box content */
function dupeoff_inner_custom_box( $post ) {

	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'dupeoff_noncename' );

	// The actual fields for data entry
	//echo '<label for="dupeoff_new_field">';
	//_e("Proofread Bot results will appear here", 'dupeoff_textdomain' );
	//echo '</label> ';
	echo '<button type="button"  id="dupeoff-submit" onclick="return false;">Get your DupeOff Report</button>
		<div id="dupeoff_result"></div>';
	echo '<script>        
		jQuery(document).ready(function(){

		//console.log(tinyMCE.activeEditor.getContent(content));
	
		jQuery("#dupeoff-submit").click(function(event){
			var dupeoff_text;

			// standard tinymce
			if (jQuery("#content_ifr").contents().find("#tinymce").html() !== "")
				dupeoff_text = jQuery("#content_ifr").contents().find("#tinymce").html();
				
			// tinymce advanced
			if (jQuery("#content").html() !== "")
				dupeoff_text = jQuery("#content").html();
			
			if (dupeoff_text === null || dupeoff_text === ""  || dupeoff_text === "undefined"  || dupeoff_text === "<p><br data-mce-bogus=\"1\"></p>" || dupeoff_text === "<p><br></p>" )
				{
				jQuery("#dupeoff-submit").after("<br/>Failed to get text from editor, please try switching to \"Visual\" if you use TinyMCE Advanced or report the issue at http://proofreadbot.com/support-forum mentioning your Wysiwyg extension, such as CKEditor etc...");
				}
			else
				{
				jQuery("#dupeoff-submit").after("<div id=\"dupeoff_throbber\"><img src=\"'. WP_PLUGIN_URL.'/dupeoff/throbber.gif\" /> Fetching report, may take a few seconds...</div>");
							
				var data = {
				action: \'post_check_dupeoff\',
				"text": dupeoff_text
				};

				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#dupeoff-submit").after(response);
					jQuery("#dupeoff_throbber").remove(); 
				});	
				}
			});
		});
		</script>';
}


/*
 * Set up options, convert old options, add filters if automatic display is enabled, and enqueue scripts
 * @uses get_option, update_option, add_filter, wp_enqueue_script
 * @return null
 */
function dupeoff_setup() {
	//Add filters if set to automatic display
	add_filter( 'the_content', 'dupeoff_auto' );
	//if( $options[ 'display' ] == 1 && $options[ 'content-excerpt' ] == 1 ) add_filter( 'the_excerpt', 'dupeoff_auto' );
}
add_action( 'plugins_loaded', 'dupeoff_setup' );


function dupeoff_auto( $content ) {
	global $post;
	$options = get_option('dupeoff_options');
	//print_r($options);
	
	$button_link="http://www.dupeoff.com";
	if(strlen($options['dupeoff_affiliate_id']) > 0) 
		{
		$button_link="http://".$options['dupeoff_affiliate_id'].".dupeoff.hop.clickbank.net/\" rel=\"nofollow";
		}
		
	$button = "<a href=\"".$button_link."\" target=\"_blank\"><img src=\"". WP_PLUGIN_URL ."/dupeoff/buttons/small_dontcopy_".$options["dupeoff_button_color"].".png\" /></a>";
	
	//print $button;
	
	//Add button to $content
	if( $options[ 'dupeoff_placement_posts' ] == true)  
		$content = $content . $button;

	
	return $content;
	
	}

add_action('admin_menu', 'dupeoffoptions_add_page_fn');
// Add sub page to the Settings Menu
function dupeoffoptions_add_page_fn() {
	add_options_page('DupeOff', 'DupeOff', 'administrator', __FILE__, 'dupeoff_options_page');
}

function dupeoff_options_page() {
?>
   <div class="wrap">
      <div class="icon32" id="icon-options-general"><br></div>
      <h2>DupeOff Options</h2>
      You can use the DupeOff plugin without entering here anything. If you would like to use full checks, please enter your DupeOff username and password. (<a href="http://dupeoff.com/user/register">Get one here</a>
      <form action="options.php" method="post">
         <?php settings_fields('dupeoff_options'); ?>
         <?php do_settings_sections(__FILE__); ?>
         <p class="submit">
            <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
         </p>
      </form>
   </div>
<?php
}

add_action('admin_init', 'dupeoffsoptions_init_fn' );
// Register our settings. Add the settings section, and settings fields
function dupeoffsoptions_init_fn(){
   register_setting('dupeoff_options', 'dupeoff_options', 'dupeoff_options_validate' );

   add_settings_section('main_section', 'DupeOff Settings', 'dupeoff_section_text_fn', __FILE__);
   
   add_settings_field('dupeoff_username', 'DupeOff Username', 'dupeoff_setting_username_fn', __FILE__, 'main_section');
   add_settings_field('dupeoff_password', 'DupeOff Password', 'dupeoff_setting_password_fn', __FILE__, 'main_section');
   add_settings_field('min_user_level', 'Lowest Role that can check DupeOff', 'dupeoff_setting_role_fn', __FILE__, 'main_section');

   add_settings_section('button_section', 'DupeOff Button Settings', 'dupeoff_button_section_text_fn', __FILE__);
   
   add_settings_field('dupeoff_button', 'Select Button', 'dupeoff_setting_button_fn', __FILE__, 'button_section');
   add_settings_field('dupeoff_button_color', 'Select Button Color', 'dupeoff_setting_button_color_fn', __FILE__, 'button_section');
  // add_settings_field('dupeoff_button_placement_homepage', 'Show button on homepage', 'dupeoff_setting_button_placement_homepage_fn', __FILE__, 'button_section');
   add_settings_field('dupeoff_button_placement_posts', 'Show button on posts', 'dupeoff_setting_button_placement_posts_fn', __FILE__, 'button_section');
   
	add_settings_section('affiliate_section', 'DupeOff Affiliate Program - Make Money', 'dupeoff_affiliate_section_text_fn', __FILE__);
   add_settings_field('dupeoff_affiliate_id', 'DupeOff Affiliate Id', 'dupeoff_setting_affiliate_id_fn', __FILE__, 'affiliate_section');
      
   // add custom capability depending on settings
   $options = get_option('dupeoff_options');
   $min_role = $options ? $options['role'] : 'administrator' ;
   $roles = array('Administrator'=>'administrator', 'Editor'=>'editor', 'Author'=>'author', 'Contributor'=>'contributor');

   foreach($roles as $role=>$val)
   {
      $role = get_role($val);
      $role->add_cap( 'check_dupeoff' );

      if($val == $min_role)
         break;
   }
}

function dupeoff_section_text_fn()
{
   echo '<p>Enter your dupeoff information.</p>';
}

function dupeoff_button_section_text_fn()
{
   echo '<p>Select optionally a button to protect your content and inform visitors on your site! You can also make some money by participating in the DupeOff affiliate program.</p>';
}

function dupeoff_affiliate_section_text_fn()
{
   echo '<p>Make money by participating in the DupeOff affiliate program, powered by Clickbank. The button you selected will link with your clickbank id so referrals can be credited to you. If you don\'t have a clickbank id  get it  <a rel="nofollow" href="http://dupeoff.com/affiliate/signup" target="_blank">here</a>, you will be taken to the Clickbank vendor / affiliate signup page. (Vendor and affiliate accounts are the same at Clickbank.)</p>';
}

function dupeoff_options_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	return $input; // return validated input
}

function dupeoff_setting_role_fn() {
   $options = get_option('dupeoff_options');
   $items = array('Administrator'=>'administrator', 'Editor'=>'editor', 'Author'=>'author', 'Contributor'=>'contributor');

   echo "<select id='dupeoff_role' name='dupeoff_options[role]'>";

   foreach($items as $item=>$value)
   {
      $selected = ($options['role']== $value ) ? 'selected="selected"' : '';
      echo "<option value='$value' $selected>$item</option>";
   }

   echo "</select>";
}

function dupeoff_setting_username_fn() {
   $options = get_option('dupeoff_options');
   echo "<input name='dupeoff_options[dupeoff_username]' size='40' type='text' value='{$options['dupeoff_username']}' />";
}

function dupeoff_setting_password_fn() {
   $options = get_option('dupeoff_options');
   echo "<input name='dupeoff_options[dupeoff_password]' size='40' type='text' value='{$options['dupeoff_password']}' />";
}

function dupeoff_setting_affiliate_id_fn() {
   $options = get_option('dupeoff_options');
   echo "<input name='dupeoff_options[dupeoff_affiliate_id]' size='40' type='text' value='{$options['dupeoff_affiliate_id']}' />";
}


function dupeoff_setting_button_fn() {
   $options = get_option('dupeoff_options');

	?>
		<fieldset>
		<label><input name='dupeoff_options[dupeoff_button]' value='dontcopy' type='radio' <?php if( !$options['dupeoff_button'] || $options['dupeoff_button']=='dontcopy' ) echo ' checked="checked"'; ?>>
		<img src="<?php echo WP_PLUGIN_URL ?>/dupeoff/buttons/small_dontcopy_light.png" /></label><br/>
		<label><input name='dupeoff_options[dupeoff_button]' value='unique' type='radio' <?php if( !$options['dupeoff_button'] || $options['dupeoff_button']=='unique' ) echo ' checked="checked"'; ?>>
		<img src="<?php echo WP_PLUGIN_URL ?>/dupeoff/buttons/small_unique_light.png" /></label>
		</fieldset> 	
	<?php
}

function dupeoff_setting_button_color_fn() {
   $options = get_option('dupeoff_options');
   $items = array('Light'=>'light', 'Dark'=>'dark', 'Green'=>'green', 'Red'=>'red', 'Yellow'=>'yellow', 'Blue'=>'blue');

   echo "<select id='dupeoff_button_color' name='dupeoff_options[dupeoff_button_color]'>";

   foreach($items as $item=>$value)
   {
      $selected = ($options['dupeoff_button_color']== $value ) ? 'selected="selected"' : '';
      echo "<option value='$value' $selected>$item</option>";
   }

   echo "</select>";
}

function dupeoff_setting_button_placement_homepage_fn() {
   $options = get_option('dupeoff_options');
  if( $options['dupeoff_placement_homepage']== true) 
	$checked = 'checked="checked"';
   
   echo "<input name='dupeoff_options[dupeoff_placement_homepage]' type='checkbox' value='true' $checked />";
}

function dupeoff_setting_button_placement_posts_fn() {
   $options = get_option('dupeoff_options');

   if($options['dupeoff_placement_posts']== true )
		$checked = 'checked="checked"';
		
   echo "<input name='dupeoff_options[dupeoff_placement_posts]' type='checkbox' value='true' $checked />";
}

			
/* Set up meta box */
add_filter('page_row_actions', 'add_dupeoff_link_to_row');
add_filter('post_row_actions', 'add_dupeoff_link_to_row');

function add_dupeoff_link_to_row($links)
{
   if(current_user_can('check_dupeoff'))
   {
      $links['dupeoff'] = "<a href='javascript:' class='check_DupeOff'>DupeOff Report</a>";
   }

   return $links;
}

// Place in Option List on Settings > Plugins page 
function dupeoff_actlinks( $links, $file ){
	//Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
	
	if ( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=dupeoff/dupeoff.php">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
		}
	return $links;
}
add_filter("plugin_action_links", 'dupeoff_actlinks', 10, 2);

?>