# WP_google_map_api_caller_plugin
A Wordpress plugin to complement [WP Google Maps](https://www.wpgmaps.com/), providing an API calling function.  This example was built to call the ActiveCampaign API but is configurable enough to serve as a starting point for any API call.

Note that this is not provided as a plug-and-play ready-to-use plugin, but rather as starting point that should save you a ton of time if you are looking to schedule updates to a WP Google Maps map based on an API call.

This plugin was developed in combination with WP Google Maps - Pro Add-on Version: 8.1.20, but this said there are no code dependencies between this plugin and the WP Google Maps plugin so you should be fine on any version of the WP Google Maps plugin as long as you can use the schedule option under 'Import Data'.

## How this plugin works
This plugin will call an API endpoint you specify (using your API key for credentials) and retrieve a json payload for Map Marker and Polyline data.
It will then manipulate that json data into a format that can be imported by WP Google Maps. ***Since all API data is structured differently, you will need to write this transformation script yourself in the 'glitter_merge_data.php' file*** (which is just one function called ***glitter_merge_data()***).
Once you have the .json export file in place, just point the WP Google Maps importer (Maps > Advanced > Import Data) to the file url.  Use 'import via url' and set it on a schedule.  And yes, you will be importing a file off the same server that hosts the Maps plugin...

## How to install
You can install this plugin as-is, and then modify it on your Wordpress instance post-install.

In the Github repo you will find the source files along with [a .zip file](https://github.com/frasermarlow/WP_google_map_api_caller_plugin/blob/main/wpgmap_api_plugin.zip).  Download the Zip file, then go to your wordpress Admain section > Plugins.  Select 'Install new' then click 'upload'.  Upload the Zip file, then on the Plugin page, click 'Activate'.

## Configuration options
This plugin includes a Wordpress Admin page for setting the following options:
- ***update frequency*** (based on the default Wordpress cron update frequencies, but also accomodates custom frequencies, and provides two examples for this)
- ***API Key***
- ***API endpoint***  Your source application's API endpoint (minus any 'resource' extentions - see below)
-  ***API resource*** additional 'resource' add to the URI as many APIs will include something like GET https://myapi.endpoint.com/v2/*clients* or https://myapi.endpoint.com/v2/*orders*.  (Note: this set up is to allow for multiple resource calls in the future).
- ***Map JSON template*** - this is the name of the file that you use as a template for the map, to which you will add the variable data from the API.  The best way to generate this template is to set up your map in WP Google Map and then export it to a .json file.
- ***Save location*** - this is the location where you will save the finished merged .json file.  This is the URL you will enter in the WP Google Map scheduled import.

## So what's with all this Glitter stuff?
The organization I built this plugin for is called Glitter. [Check them out](https://getglitterapp.com).
