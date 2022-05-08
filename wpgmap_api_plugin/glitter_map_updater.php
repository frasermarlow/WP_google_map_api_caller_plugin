<?php
/**
Plugin Name:  Glitter Map Updater
Description:  Pulls data from ActiveCampaign and reformats it as a .json file that the WP Google Map plugin can ingest.  This version created April 21 2022.
Version:      1.3
Author:       Fraser Marlow
*/

require_once('glitter_merge_data.php');

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'The "add_action" function is missing.';
	exit;
}

// ADMIN

// Add menu item on the Wordpress Admin page
if ( is_admin() ){ // admin actions (functions that get called)
	// Fires before the administration menu loads in the admin.
  add_action('admin_menu', 'glitter_map_updater_menu');
	// Fires as an admin screen or script is being initialized.
  add_action('admin_init', 'register_glitter_map_settings');
}
 
function glitter_map_updater_menu(){
 // add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
    add_menu_page( 'Glitter Map Updater', 'Map Updater', 'manage_options', 'map-updater', 'g_map_render_admin_page', 'dashicons-admin-site', 67 );
}

// Register the settings for the plugin
if (!function_exists('register_glitter_map_settings')) {
	function register_glitter_map_settings(){
		# register_setting( string $option_group, string $option_name, array $args = array() )
		register_setting( 'map-updater', 'glitter_map_api');
		register_setting( 'map-updater', 'glitter_map_api_endpoint');
		register_setting( 'map-updater', 'glitter_map_api_resource');
		register_setting( 'map-updater', 'glitter_map_update_frequency');
		register_setting( 'map-updater', 'glitter_map_json_base');
		register_setting( 'map-updater', 'glitter_map_json_export_file');
		add_option('map_last_updated_at'); # note, this will only have effect the very first time you install the plugin.

		# add_settings_section( string $id, string $title, callable $callback, string $page )
		add_settings_section('glitter_map_admin_page_section1', 'Glitter Map Settings:', 'render_plugin_section_text1', 'map-updater');
		add_settings_section('glitter_map_admin_page_section2', 'Glitter Map Variables:', 'render_plugin_section_text2', 'map-updater');
		# add_settings_field( string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = array() )

		add_settings_field('glitter_map_update_frequency_field', 'Update frequency', 'glitter_map_update_frequency_input', 'map-updater', 'glitter_map_admin_page_section1');
		add_settings_field('glitter_map_api_field', 'ActiveCampaign API key', 'glitter_map_api_input', 'map-updater', 'glitter_map_admin_page_section2');
		add_settings_field('glitter_map_api_endpoint_field', 'API endpoint', 'glitter_map_api_endpoint_input', 'map-updater', 'glitter_map_admin_page_section2');		add_settings_field('glitter_map_api_resource_field', 'API resource', 'glitter_map_api_resource_input', 'map-updater', 'glitter_map_admin_page_section2');
		add_settings_field('glitter_map_json_base_field', 'Map JSON template', 'glitter_map_json_base_input', 'map-updater', 'glitter_map_admin_page_section2');
		add_settings_field('glitter_map_json_export_file_field', 'Where to save the export file', 'glitter_map_json_export_file_input', 'map-updater', 'glitter_map_admin_page_section2');		
	}
}

function render_plugin_section_text1() {
	// echo '<p>Main description of this section here.</p>';
	echo "Please provide the following values:";
}

function glitter_map_update_frequency_input() {
	$available_schedules = wp_get_schedules();
	$freq = get_option('glitter_map_update_frequency');
	echo "<p>Currently set to : <strong>" . $available_schedules[$freq]['display'] . "</strong></p>\n";
	echo "<p style='font-size:small; color:#aaa;'>Note: the higher frequency will mean more calls to the API endpoint (your source).  It will not impact the number of calls to the Google Maps API, which is based on page views of your map.</p>\n<br/>\n";
	?>
	<label for="cron_frequencies">Set the update frequency:</label>
  <select id="cron_frequencies" name="glitter_map_update_frequency">
	<?php
	foreach( $available_schedules as $k=>$v) {
		$schedule_dropdown = "<option value='{$k}' "; 
		$schedule_dropdown .= $freq==$k?'selected':'';
		$schedule_dropdown .= ">{$v['display']}</option>";
		echo $schedule_dropdown;
	}

	?>
  </select>
	<hr style="margin:30px 0"/>
	<?php
}

function render_plugin_section_text2() {
	// echo '<p>Main description of this section here.</p>';
	echo "Admin settings (please don't change unless you know what you are doing!):";
}

function glitter_map_api_input() {
	# get_option( string $option, mixed $default = false )
	$options = get_option('glitter_map_api');
	echo "<input id='plugin_text_string' name='glitter_map_api' size='80' type='text' value='{$options}' />";
}

function glitter_map_api_endpoint_input() {
	// get_option( string $option, mixed $default = false )
	$options = get_option('glitter_map_api_endpoint');
	echo "<p>Note, the API endpoint may have a 'resource' value (like 'clients' or 'orders') - do not include this in the URI.</p>\n<br/>\n";
	echo "<input id='plugin_text_string' name='glitter_map_api_endpoint' size='80' type='text' value='{$options}' />";
}

function glitter_map_api_resource_input() {
	// get_option( string $option, mixed $default = false )
	$options = get_option('glitter_map_api_resource');
	echo "<input id='plugin_text_string' name='glitter_map_api_resource' size='80' type='text' value='{$options}' />";
}

function glitter_map_json_base_input() {
	// get_option( string $option, mixed $default = false )
	$options = get_option('glitter_map_json_base');
	$thisdirectory = __DIR__;
	echo "<p>The name of the .json file that the plugin will augment with imported fields from ActiveCampaign</p><p>This file has to sit in the folder of the plugin - namely <em>{$thisdirectory}</em>.</p>\n<br/>\n";
	echo "<input id='plugin_text_string' name='glitter_map_json_base' size='80' type='text' value='{$options}' />";
}

function glitter_map_json_export_file_input() {
	echo "<p>Where to save the .json file that the Google Map app will import.</p>\n";

	if(strlen(get_option('glitter_map_json_export_file')) > 0){
		$options = get_option('glitter_map_json_export_file');
		$export_file_url = get_site_url() . "/" . $options;
		echo "<p>Currently: <a href='" . $export_file_url . "' target='_new'>" . $export_file_url . "</a></p><br/>\n";
	} else {
		$options = "";
	}
	echo "<input id='plugin_text_string' name='glitter_map_json_export_file' size='80' type='text' value='{$options}' />";
}

function g_map_render_admin_page(){
	$output = "<img src='http://staging-getglitterapp.kinsta.cloud/wp-content/uploads/2022/04/trash_globe.gif' height='180px' style='float:right; margin: 40px 20% 0 0;'>";
	$output .="<div class='wrap'>\n<h1>Glitter Map Updater</h1>\n<h3>V" . get_plugin_data(__FILE__)['Version'] . " by " . get_plugin_data(__FILE__)['Author'] . "</h3>\n<p>This plugin calls the ActiveCampaign API and pulls in the data required to update the map.<br/>\n";
	$output .= "There are currently " . get_option('map_block_count') . " blocks on the map.  |  Last updated at " . get_option('map_last_updated_at') . " " . date_default_timezone_get() . "</p>";
	$output .= "<form method='post' action='options.php'>\n";
	echo $output;
	settings_fields( 'map-updater' ); # Docs say this should match the group name used in register_setting(), but actually should match the page
	do_settings_sections( 'map-updater' );
	submit_button();
	$output ="</form>\n</div>";
	echo $output;
}

//  Main plugin functions ----------------------------------------------------------------------------

class GlitterMapObject {
	
  private $api_key;
  private $api_endpoint;
	private $limit;  // TODO:  Build pagination logic past 100 accounts
	private $offset;
	
  function __construct() {
    $this->api_endpoint = get_option('glitter_map_api_endpoint');  # Note: our API call has an extra endpoint variable called 'resource'
    $this->api_key = get_option('glitter_map_api');
  }

  //// Creating widget front-end view
  public function call_api($resource) {

    $curl = curl_init();
    $url = $this->api_endpoint . $resource . "?limit=100";  # here we append the 'resource' extension to the endpoint.  This allows the API to be extensible
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Api-Token: ". $this->api_key
      ),
    ));
  
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
  
    if ($err) {
      //Only show errors while testing
      // echo "cURL Error #:" . $err;
    } else {
      //The API returns data in JSON format, so first convert that to an array of data objects
      $responseObj = json_decode($response);
      return (array) $responseObj;
    }
  } // end public function widget
} // Class glitter_map_widget ends here

//  outside of Class function call:
if (!function_exists('glitter_update_map_json')) {
function glitter_update_map_json(){

    // grab our 'base' map, or template we will augment with the data from the API call.
	$base_map_data = file_get_contents(__DIR__ . '/' . get_option('glitter_map_json_base'));
	$map_data = json_decode($base_map_data,true);
	$map = new GlitterMapObject();
	
	// merge the two data sources - this will be a custom function you will design to parse and organize your imported data.
	$map_data = glitter_merge_data($map_data, $map->call_api(get_option('glitter_map_api_resource')));
	
	// finally, save the file and update the 'last updated' timestamp.
	$final_file = fopen(get_option('glitter_map_json_export_file'), 'w');
	fwrite($final_file, json_encode($map_data));
	fclose($final_file);
	
	update_option('map_last_updated_at', date("Y-m-d H:i:s"));
	
	}
}

// # SCHEDULING -----------------------------------------------------------

// add custom cron interval

if (!function_exists('cron_add_minute')) {
		function cron_add_minute( $schedules ) {
			// Adds once every minute to the existing schedules.
				$schedules['everyminute'] = array(
					'interval' => 60,
					'display' => __( 'Once Every Minute' )
				);
				return $schedules;
		}
}

if (!function_exists('cron_add_five_minutes')) {
		function cron_add_five_minutes( $schedules ) {
			// Adds once every minute to the existing schedules.
				$schedules['every5minutes'] = array(
					'interval' => 60*5,
					'display' => __( 'Every Five Minutes' )
				);
				return $schedules;
		}
}

if (!function_exists('cron_add_paused')) {
		function cron_add_paused( $schedules ) {
			// Adds once every minute to the existing schedules.
				$schedules['paused'] = array(
					'interval' => 9999999999,
					'display' => __( 'Paused' )
				);
				return $schedules;
		}
}

add_filter( 'cron_schedules', 'cron_add_minute' );
add_filter( 'cron_schedules', 'cron_add_five_minutes' );
add_filter( 'cron_schedules', 'cron_add_paused' );

// create a scheduled event (if it does not exist already)
function cronstarter_activation() {
	if( !wp_next_scheduled( 'glitter_map_cron_job' ) ) {  
	   wp_schedule_event( time(), get_option('glitter_map_update_frequency'), 'glitter_map_cron_job' );  // Valid values for the recurrence are ‘hourly’, ‘daily’, and ‘twicedaily’. Change to 'everyminute' for testing  
	}
}

// unschedule event upon plugin deactivation
function cronstarter_deactivate() {	
	// find out when the last event was scheduled
	$timestamp = wp_next_scheduled ('glitter_map_cron_job');
	// unschedule previous event if any
	wp_unschedule_event ($timestamp, 'glitter_map_cron_job');
} 

// HOOKS ------------------------------------------------------------------------------------------

// Create a [glitter_map_api_test] shortcode we can add to a test page

function register_shortcodes(){
   add_shortcode('glitter_map_api_test', 'glitter_update_map_json');
}
add_action( 'init', 'register_shortcodes');


// and make sure it's called whenever WordPress loads
add_action('wp', 'cronstarter_activation');
register_deactivation_hook (__FILE__, 'cronstarter_deactivate');

// hook that function onto our scheduled event:
add_action ('glitter_map_cron_job', 'glitter_update_map_json'); 

add_shortcode('glitter_update_map_json', 'glitter_update_map_json');
/* That's all folks! */

?>
