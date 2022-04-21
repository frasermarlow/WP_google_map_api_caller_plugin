<?php

// function to take a base for a WP Google Map import, and merge in a second $that came from our API call.

function glitter_merge_sources($base_array, $api_call_data) {
    
    add_option('map_block_count'); # note, this will only have effect the very first time you install the plugin.
	update_option('map_block_count', random_int(30,150));
    $test_array = array();  // useful for testing
    
    return array_merge($test_array, $base_array, $api_call_data);
}

?>