<?php 

/*
add_action('pre_get_posts', 'display_concerts');

function display_concerts($query){
	$query->set('post_type', array('action'));	
	$query->set('meta_query', array(array('key' => 'wpcf-pasys', 'value' => false, 'type' => 'BOOLEAN' )));
	return;
}
*/

add_action('pre_get_posts', 'display_action');	
function display_action($query){

	if($query->is_front_page() && $query->is_main_query())
	{
		
		$query->set('post_type', array('concert'));
		$query->set('date_query', array('year' => 2006,'compare'=>'>=','year' => 2008,'compare'=>'<='));	
	$query->set('meta_query', array(array('key' => 'wpcf-lieu', 'value' => false, 'type' => 'BOOLEAN' )));


	}


return;
}

// Function that outputs the contents of the dashboard widget
function dashboard_widget_function() {

 $query = new WP_Query();
 
 $query->set('post_type', 'concert');
 
 $results = $query->get_posts();
 
 var_dump(count($results));
 
 

}

// Function used in the action hook
function add_dashboard_widgets() {
	wp_add_dashboard_widget('dashboard_widget', 'nombre de post sans pays ni lieu', 'dashboard_widget_function');
}

// Register the new dashboard widget with the 'wp_dashboard_setup' action
add_action('wp_dashboard_setup', 'add_dashboard_widgets' );



function geolocalize($post_id){
	if(wp_is_post_revision($post_id))
		return;
	$post=get_post($post_id);
	if(!in_array($post->post_type,array('concert')))
		return;
	$lieu=get_post_meta($post_id,'wpcf-lieu',true);
	if(empty($lieu))
		return;
	$lat=get_post_meta($post_id,'lat',true);
	if(empty($latlon)){
		$address= $lieu.',France';
		$result=doGeolocation($address);
		if(false===$result)
			return;
			
		try
		{
			$location=$result[0]['geometry']['location'];
			add_post_meta($post_id,'lat',$location["lat"]);
			add_post_meta($post_id,'lng',$location["lng"]);
			}
		catch(Exception $e){
			return;
			}
		}
	}
	
	add_action('save_post','geolocalize');
	
	
function doGeolocation($address){

    $url = "http://maps.google.com/maps/api/geocode/json?sensor=false" . "&address=" . urlencode($address);


    $proxy='wwwcache.univ-orleans.fr:3128';
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 5,
            'proxy' => $proxy,
            'request_fulluri' => true,
            )
        )
    );

    if($json = file_get_contents($url,0,$ctx)){

        $data = json_decode($json, TRUE);

        if($data['status']=="OK"){

            return $data['results'];
        }

    }

    return false;

}


function loads_scripts(){
	if(!is_post_type_archive('concert')&&!is_post_type_archive('action'))
		return;
	wp_register_script('leaflet-js','http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.js');
	wp_enqueue_script('leaflet-js');

	wp_register_style( 'leaflet-css','http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.css');

	wp_enqueue_style('leaflet-css');
	
	}
	
add_action('wp_enqueue_scripts','loads_scripts');


function getPostWithLatLon($post_type="concert")
{
	global $wpdb;
	$query="SELECT ID, post_title, p1.meta_value AS lat, p2.meta_value AS lng 
	FROM wp_archer_posts, wp_archer_postmeta AS p1, wp_archer_postmeta AS p2
	WHERE wp_archer_posts.post_type = 'concert'
	AND p1.post_id = wp_archer_posts.ID
	AND p2.post_id = wp_archer_posts.ID
	AND p1.meta_key = 'lat'
	AND p2.meta_key = 'lng'";
	return $wpdb->get_results($query);
}	
	
	
		
		
		
function getMarkerList($post_type="concert")
{
	$results=getPostWithLatLon($post_type);
	$array=array();
	

	
	foreach($results as $result){
		$array[]="var marker_$result->ID=L.marker([$result->lat,$result->lng]).addTo(map);";
		$array[]="var popup_$result->ID=L.popup().setContent('$result->post_title');";
		$array[]="popup_$result->ID.post_id=$result->ID;";
		$array[]="marker_$result->ID.bindPopup(popup_$result->ID)";
	}
	return implode(PHP_EOL,$array);
}	


function get_content(){
	if(!wp_verify_nonce($_REQUEST['nonce'],"popup_content")){
		exit("d'où vient cette reqûete?");}
	else{
		$post_id=$_REQUEST["post_id"];
		$po=get_post($post_id);	
		die($po->post_content);		
	}
}
	
add_action("wp_ajax_popup_content","get_content");
add_action("wp_ajax_nopriv_popup_content","get_content");				


