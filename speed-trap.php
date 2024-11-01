<?php
/*
Plugin Name: Speed Trap
Plugin URI: http://www.presscoders.com/plugins/speed-trap/
Description: Track WordPress pages and load times.
Version: 0.1
Author: David Gwyer
Author URI: http://www.presscoders.com
*/

/*  Copyright 2009 David Gwyer (email : d.v.gwyer@presscoders.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// @todo
// 1. could add another checkbox option to show query strings or just the filename.
// 2. Add a bar chart for front and and admin speeds, say last 20 pages loaded.

// 'pcst_' prefix is derived from [p]ress [c]oders [s]peed [t]rap

// Register hooks and callback functions.
register_activation_hook(__FILE__, 'pcst_add_defaults');
register_uninstall_hook(__FILE__, 'pcst_delete_plugin_options');
add_action( 'admin_init', 'pcst_init' );
add_action( 'admin_menu', 'pcst_add_options_page' );
add_filter( 'plugin_action_links', 'pcst_plugin_action_links', 10, 2 );
add_action( 'wp_enqueue_scripts', 'pcst_front_end_scripts' );
add_action( 'admin_print_styles', 'st_admin_styles' );

// Delete options table entries ONLY when plugin deactivated AND deleted
function pcst_delete_plugin_options() {
	delete_option('pcst_options');
}

// Define default option settings
function pcst_add_defaults() {
	$tmp = get_option('pcst_options');

	if( $tmp['chk_default_options_db'] ) {
		update_option( 'pc_site_load_log', array() );
		update_option( 'pc_admin_load_log', array() );
	}

    if(($tmp['chk_default_options_db']=='1')||(!is_array($tmp))) {
		delete_option('pcst_options');
		$arr = array(	"chk_site_path" => "",
						"chk_admin_path" => "",
						"chk_show_date" => "1",
						"chk_show_user" => "1",
						"site-page-load-time-log" => "",
						"admin-page-load-time-log" => "",
						"txt_whitelist" => "widgets.php, admin-ajax.php, wp-cron.php, wp-login.php",
						"chk_show_notification" => "1",
						"chk_default_options_db" => ""
		);
		update_option('pcst_options', $arr);
	}
}

// Init plugin options to white list our options
function pcst_init(){
	register_setting( 'pcst_plugin_options', 'pcst_options', 'pcst_validate_options' );
}

// Add menu page
function pcst_add_options_page() {
	$speed_trap_hook_suffix = add_options_page('Speed Trap Options Page', 'Speed Trap', 'manage_options', __FILE__, 'pcst_render_form');
	
	/* Enqueue scripts and styles on the Plugin options page. */
	add_action( "admin_print_styles-$speed_trap_hook_suffix", 'st_plugin_styles' );
	add_action( "admin_print_scripts-$speed_trap_hook_suffix", 'st_plugin_scripts' );
}

function st_plugin_styles() {
	/* Only show styles on Plugin options page. */
	wp_enqueue_style( 'st_plugin_stylesheet', plugins_url('style.css' , __FILE__ ) );
}

function st_plugin_scripts() {
	/* Only show scripts on Plugin options page. */
	wp_enqueue_script( 'st_plugin_script', plugins_url('speed-trap.js' , __FILE__ ) );
}

function st_admin_styles() {
	wp_enqueue_style( 'st_label_admin_stylesheet', plugins_url('label.css' , __FILE__ ) );
}

// Enqueue front end scripts
function pcst_front_end_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_style( 'st_label_front_stylesheet', plugins_url('label.css' , __FILE__ ) );
}

// Render the Plugin options form
function pcst_render_form() {
	?>
	<div class="wrap">
		
		<?php
			// Clear site pages log
			if( isset($_POST['clear_site_pages_log']) && $_POST['clear_site_pages_log'] == "true" ) {
				update_option( 'pc_site_load_log', array() ); // Clear site pages log
				?><div class="updated"><p>Settings saved.</p></div><?php
			}

			// Clear admin pages log
			if( isset($_POST['clear_admin_pages_log']) && $_POST['clear_admin_pages_log'] == "true" ) {
				update_option( 'pc_admin_load_log', array() ); // Clear admin pages log
				?><div class="updated"><p>Settings saved.</p></div><?php
			}
		?>

		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>
		<h2 id="st-plugin-header">Speed Trap Plugin</h2>

		<form action="<?php echo pc_currentURL(); ?>" method="post" id="pc-st-clear-logs">
			<div id="log-header">
				<span id="site-log-header">Site Pages Speed Log</span>
				<span>
					<input type="submit" class="button" value="Clear Site Pages Log" name="clear_site_log" />
					<input type="hidden" name="clear_site_pages_log" value="false" />
				</span>
				<span id="admin-log-header">Admin Pages Speed Log</span>
				<span>
					<input type="submit" class="button" value="Clear Admin Pages Log" name="clear_admin_log" />
					<input type="hidden" name="clear_admin_pages_log" value="false" />
				</span>
			</div>
		</form>

		<!-- Beginning of the Plugin Options Form -->
		<form method="post" action="options.php">
			<?php settings_fields('pcst_plugin_options'); ?>
			<?php $options = get_option('pcst_options'); ?>

			<table class="form-table st-table">

				<tr valign="top" id="st-tr1">
					<!-- Load time log for site pages -->
					<td>
						<?php
							$spl = get_option('pc_site_load_log');
							if( is_array($spl) ) $log_string = implode("\r\n", $spl);
						?>
						<textarea id="site-log-ta" name="pcst_options[site-page-load-time-log]" rows="20" cols="70" type='textarea' readonly="readonly"><?php echo $log_string; ?></textarea><br />
						<span class="span-label site-span-padding">Most recent site pages accessed (newest first).</span>
					</td>
					<!-- Load time log for site pages -->
					<td>
						<?php
							$apl = get_option('pc_admin_load_log');
							if( is_array($apl) ) $log_string = implode("\r\n", $apl);
						?>
						<textarea id="admin-log-ta" name="pcst_options[admin-page-load-time-log]" rows="20" cols="80" type='textarea' readonly="readonly"><?php echo $log_string; ?></textarea><br />
						<span class="span-label admin-span-padding">Most recent admin pages accessed (newest first).</span>
					</td>
				</tr>

				<tr><td colspan="2">
				<div id="site-log-settings">Plugin Settings</div>
				</td></tr>

				<tr valign="top" id="st-tr2">
					<!-- Checkbox Options -->
					<td>
						<label><input name="pcst_options[chk_site_path]" type="checkbox" value="1" <?php if (isset($options['chk_site_path'])) { checked('1', $options['chk_site_path']); } ?> /> Include full path to site pages</label><br />
						<label><input name="pcst_options[chk_admin_path]" type="checkbox" value="1" <?php if (isset($options['chk_admin_path'])) { checked('1', $options['chk_admin_path']); } ?> /> Include full path to admin pages</label><br />
						<label><input name="pcst_options[chk_show_date]" type="checkbox" value="1" <?php if (isset($options['chk_show_date'])) { checked('1', $options['chk_show_date']); } ?> /> Add date/time stamp</label><br />
						<label><input name="pcst_options[chk_show_user]" type="checkbox" value="1" <?php if (isset($options['chk_show_user'])) { checked('1', $options['chk_show_user']); } ?> /> Show current user</label><br />
					</td>
					<!-- Comma separated list of pages to ignore -->
					<td>
						<div id="pages-whitelist">Enter a comma separate list of pages to ignore:<br />
						<input type="text" size="65" name="pcst_options[txt_whitelist]" value="<?php echo $options['txt_whitelist']; ?>" /></div>
						<label><input name="pcst_options[chk_show_notification]" type="checkbox" value="1" <?php if (isset($options['chk_show_notification'])) { checked('1', $options['chk_show_notification']); } ?> /> Show load time label for each page?</label><br /><span class="span-label">Displayed at the bottom of every page on the front end and in admin</span>
					</td>
				</tr>

				<tr><td colspan="2"><div class="row-border"></div></td></tr>

				<tr valign="top">
					<th scope="row">Database Options</th>
					<td>
						<label><input name="pcst_options[chk_default_options_db]" type="checkbox" value="1" <?php if (isset($options['chk_default_options_db'])) { checked('1', $options['chk_default_options_db']); } ?> /> Restore Plugin defaults upon plugin deactivation/reactivation</label>
						<br /><span class="span-label">Only check this if you want to reset plugin settings upon Plugin reactivation</span>
					</td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>

		<p class="p-spacer">
			<p class="donation-text">If you have found the Speed Trap Plugin at all useful, please consider making a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=44RT6QHGKLLYS" target="_blank" class="donation-bold">donation</a>. Thanks.</p>
			<span><a href="http://www.facebook.com/PressCoders" title="Our Facebook page" target="_blank"><img class="img-border" src="<?php echo plugins_url(); ?>/speed-trap/images/facebook-icon.png" /></a></span>
			&nbsp;&nbsp;<span><a href="http://www.twitter.com/dgwyer" title="Follow on Twitter" target="_blank"><img class="img-border" src="<?php echo plugins_url(); ?>/speed-trap/images/twitter-icon.png" /></a></span>
			&nbsp;&nbsp;<span><a href="http://www.presscoders.com" title="PressCoders.com" target="_blank"><img class="img-border" src="<?php echo plugins_url(); ?>/speed-trap/images/pc-icon.png" /></a></span>
		</p>

	</div>
	<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function pcst_validate_options($input) {
	 // strip html from textboxes
	$input['site-page-load-time-log'] =  wp_filter_nohtml_kses($input['site-page-load-time-log']);
	$input['admin-page-load-time-log'] =  wp_filter_nohtml_kses($input['admin-page-load-time-log']);
	$input['txt_whitelist'] =  wp_filter_nohtml_kses($input['txt_whitelist']); // Sanitize textbox input (strip html tags, and escape characters)
	return $input;
}

// Display a Settings link on the main Plugins page
function pcst_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$pcst_links = '<a href="'.get_admin_url().'options-general.php?page=speed-trap/speed-trap.php">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $pcst_links );
	}

	return $links;
}

// Grab the page load time upon WordPress shutdown.
function page_load_time() {

	/* Grab the page load time before we do anything else that may skew the results. */
	$load_time = timer_stop(0, 3);

	/* Grab the current user info. */
	global $current_user;

	$options = get_option('pcst_options'); /* Get the Plugin options. */

	/* Get list of pages to ignore. */
	$page_whitelist = $options['txt_whitelist'];
	$page_whitelist = explode(",", $page_whitelist);
	array_walk($page_whitelist, 'pc_trim_element');

	/* Get current page URL. */
	$raw_url = pc_currentURL();
	$url_no_querystring = array_shift(explode('?', basename($raw_url)));
	$file = basename($raw_url);
	$file_no_querystring = basename($url_no_querystring);

	/* If the current page is in the whitelist array just return. */
	if ( in_array( $file_no_querystring, $page_whitelist ) ) return;

	/* Show date if option set. */
	$date = ( isset($options['chk_show_date']) && $options['chk_show_date'] ) ? ' ['.date("jS M Y, G:i:s").']' : '';

	/* Show username if option set. */
	$cu = is_user_logged_in() ? ' ['.$current_user->data->user_login.']' : ' [guest]';

	$username = ( isset($options['chk_show_user']) && $options['chk_show_user'] ) ? $cu  : '';

	if( !is_admin() ) {
		/* Display full path, or just the page name with optional query string. */
		if( isset($options['chk_site_path']) && $options['chk_site_path'] ) { $url = $raw_url; }
		else { $url = $file; }

		if( is_array($site_pages_log = get_option('pc_site_load_log') ) ) {
			array_unshift( $site_pages_log, $url.' -> '.$load_time.'s'.$username.$date ); /* Add to beginning of array. */
			// $site_pages_log[] = $url.' -> '.$load_time.'s'.$username.$date; /* Add to end of array. */
			update_option( 'pc_site_load_log', $site_pages_log ); /* Update Plugin options. */
		}
		else {
			$site_pages_log = array();
			update_option( 'pc_site_load_log', $site_pages_log ); /* Update Plugin options. */
		}
	}
	else {
		/* Display full path, or just the page name with optional query string. */
		if( isset($options['chk_admin_path']) && $options['chk_admin_path'] ) { $url = $raw_url; }	else { $url = $file; }

		if( is_array($admin_pages_log = get_option('pc_admin_load_log') ) ) {
			array_unshift( $admin_pages_log, $url.' -> '.$load_time.'s'.$username.$date ); /* Add to beginning of array. */
			// $admin_pages_log[] = $url.' -> '.$load_time.'s'.$username.$date; /* Add to end of array. */
			update_option( 'pc_admin_load_log', $admin_pages_log ); /* Update Plugin options. */
		}
		else {
			$admin_pages_log = array();
			update_option( 'pc_admin_load_log', $admin_pages_log ); /* Update Plugin options. */
		}
	}

	if( is_user_logged_in() ) {
		if( current_user_can('administrator') ) {

			if( isset($options['chk_show_notification']) && $options['chk_show_notification'] ) {
				/* Conditionally show the page load time and make sure it is echoed inside the closing body tag. */
				?>
				<script language="javascript">
					jQuery(document).ready(function($) {
						$("body").append("<div id=\"pc-speed-trap\">Speed Trap: <strong><?php echo $url; ?></strong> loaded in <strong><?php echo $load_time; ?>s</strong><?php echo $username.$date; ?>.</div>");
					});
				</script>
				<?php
			}
		}
	}
}
add_action( 'shutdown', 'page_load_time' );

/* Plugin utility functions. */

function pc_currentURL() {
		$pageURL = 'http';
		if( isset($_SERVER["HTTPS"]) ) {
			if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
}

function pc_trim_element(&$value) 
{ 
    $value = trim($value);
}