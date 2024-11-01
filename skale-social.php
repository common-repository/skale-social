<?php
/*


Plugin Name: Skale Social
Plugin URI: http://skaledigital.com
Description: A plugin that generates share buttons for a list of social networks, using urls generated by skaledigital.com shortlink api.
Version: 1.0
Author: Skale
Author URI: http://skaledigital.com
License: GPLv2

Copyright 2016 SkaleDigital.com - dev: ovidiu.stefancu@webland.ro

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

//======================================================================
// 		CONSTANTS
//======================================================================

define('SDS_FILE', __FILE__);
define('SDS_ROOT', dirname(__FILE__));
define('SDS_VERSION', '1.0');

//add icon list
include_once plugin_dir_path(__FILE__).'/skale-wp.php';
include_once plugin_dir_path(__FILE__).'/skale-util.php';
include_once plugin_dir_path(__FILE__).'/social-networks.php';
include_once plugin_dir_path(__FILE__).'/skale-social-widget.php';

// skaledigital.com shorturl engine
class SkaleDS extends SkaleDSutil{

	public $plug_name = "Skale";
	public $pre = "sds_";
	public $api_url = 'http://skaledigital.com/api/?api=__API_TOKEN__&url=__TARGET_URL__';

	public $cfg = array();
	public $cfgObj = false;

    function __construct() {
        add_action('init', array( &$this, 'create_post_type'));
		add_action('admin_menu', array( &$this, 'create_menu'));
		add_action('wp_enqueue_scripts', array( &$this, 'page_scripts'));
		add_action('admin_enqueue_scripts', array( &$this, 'page_scripts'));
		add_action( 'wp_footer', array( &$this, 'footer_stickybar'));



		add_action( 'wp_ajax_sds-test-token',  array( &$this, 'test_token') );
		add_action( 'wp_ajax_nopriv_sds-test-token',  array( &$this, 'test_token') );

		// add share buttons to content and/or excerpts
		add_filter('the_content', array( &$this, 'show_share_buttons'), 10);
		add_filter('the_excerpt', array( &$this, 'show_share_buttons'), 10);

		//delete generated links
		add_action('wp_ajax_delete_all_sds_links', array( &$this, 'delete_all_sds_links'));
		add_action('wp_ajax_nopriv_delete_all_sds_links', array( &$this, 'delete_all_sds_links'));

		//count posts and generate new skale_links_creation_time
		add_action('wp_ajax_count_linkable_skale_posts', array( &$this, 'count_linkable_skale_posts'));
		add_action('wp_ajax_nopriv_count_linkable_skale_posts', array( &$this, 'count_linkable_skale_posts'));

		//count posts and generate new skale_links_creation_time
		add_action('wp_ajax_get_posts_to_generate_links', array( &$this, 'get_posts_to_generate_links'));
		add_action('wp_ajax_nopriv_get_posts_to_generate_links', array( &$this, 'get_posts_to_generate_links'));

		add_filter('plugin_action_links_'.plugin_basename(__FILE__), array( &$this, 'settings_page_button'));

		add_action( 'wp_trash_post', array( &$this, 'before_post_delete') );

		//CRON JOB TO GENERATE LINKS hourly, 10 links - to reduce server load

		if(get_option($this->pre."run_cron") == "on"){
			if ( ! wp_next_scheduled( 'search_unlinked_posts' ) ) {
				wp_schedule_event( time(), 'hourly', 'search_unlinked_posts' );
			}
			add_action( 'search_unlinked_posts', array( &$this,  'cron_action'));
		}

		//ADD SHORTCODE
		add_shortcode( 'sds_share_bar', array( &$this, 'sds_share_bar'));

		//on plugin activation
	    register_activation_hook( __FILE__, array( &$this, 'plugin_activate'));


		$this->cfg[] = array(
			"label" => __(""),
			"name" => $this->pre."info",
			"description" => "<img src='".plugin_dir_url( __FILE__ )."/images/logo.png'>",
			"type" => "info",
			"value" => "on",
			"br" => "on",
		);

		// ADD OPTIONS
		$this->cfg[] = array(
			"label" => __("API TOKEN SETTING"),
			"name" => $this->pre."info",
			"description" => __("Follow these Simple Steps to Copy and Paste your API TOKEN. <br><a href='http://skaledigital.com' target='_blank'>Sign Up</a> or <a href='http://skaledigital.com' target='_blank'>Sign In</a> at SkaleDigital.com > Select My Account > Select Tools > Select Developer API > Copy + Paste Your API Token Below. Save Changes at the bottom of the page."),
			"type" => "info",
			"value" => "on"
		);

		$this->cfg[] = array(
			"label" => __("API TOKEN"),
			"name" => $this->pre."api_token",
			"type" => "text",
			"hr" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Search and generate shorturls for your new posts every hour."),
			"description" => __("It will schedule the generation of Skale shortlinks hourly. To be used in case you have a large number of posts. <br/> Can be disabled in case all your posts have Skale shortlinks."),
			"name" => $this->pre."run_cron",
			"type" => "checkbox",
			"hr" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Select and Sort what social icons you want to be visible"),
			"name" => $this->pre."info",
			"type" => "info",
			"menu-item" => __("Toggle Social Buttons"),
			"value" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Social Buttons"),
			"name" => $this->pre."share_buttons",
			"box-class" => "share-buttons-list",
			"type" => "text",
			"value" => "facebook twitter google"
		);

		$this->cfg[] = array(
			"label" => __("Active ON: "),
			"name" => $this->pre."info",
			"description" => __("Choose your social buttons locations.", "sds-lang"),
			"type" => "info",
			"menu-item" => __("Active ON"),
			"value" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Homepage"),
			"name" => $this->pre."on_homepage",
			"type" => "checkbox"
		);

		$this->cfg[] = array(
			"label" => __("Pages"),
			"name" => $this->pre."on_pages",
			"type" => "checkbox"
		);

		$this->cfg[] = array(
			"label" => __("Posts"),
			"name" => $this->pre."on_posts",
			"type" => "checkbox",
			"value" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Categories"),
			"name" => $this->pre."on_categories",
			"type" => "checkbox",
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Share-Bar Position"),
			"name" => $this->pre."share_btns_position",
			"type" => "select",
			"description" => __(""),
			"value" => "both",
			"options" => array(__("Before Content") => "top", __("After Content") => "bottom", __("Before and After") => "both"),
			"br" => "on"
		);


		$this->cfg[] = array(
			"label" => __("Footer Sticky-Bar"),
			"name" => $this->pre."sticky_bar",
			"type" => "checkbox",
			"description" => __("Add a Skale sticky social bar to your footer.")
		);

		$this->cfg[] = array(
			"label" => __("Footer Sticky-Bar Color"),
			"name" => $this->pre."sticky_bar_color",
			"type" => "colorpicker",
			"value" => "#000",
			"br" => 1
		);

		$this->cfg[] = array(
			"label" => __("Hide if screen is larger than this:"),
			"name" => $this->pre."max_screen",
			"type" => "text",
			"description" => __("Integer number"),
			"value" => "800",
			"hr" => "on"
		);


		$this->cfg[] = array(
			"label" => __("STYLE"),
			"name" => $this->pre."info",
			"type" => "info",
			"menu-item" => __("Style"),
			"description" => __("If you select Icon as Button Shape, set your Icon Color and Background to make them visible."),
			"value" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Buttons Shape"),
			"name" => $this->pre."shape",
			"type" => "select",
			"value" => "r-box",
			"options" => array( __("Icon") => "icon", __("Circle") => "circle", __("Box") => "box", __("Rounded Box") => "r-box"),
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Buttons Size"),
			"name" => $this->pre."size",
			"type" => "select",
			"br" => "on",
			"value" => 20,
			"options" => array(__("Tiny") => "12", __("Small") => "16", __("Medium") => "20", __("Large") => "32")
		);

		$this->cfg[] = array(
			"label" => __("Buttons Align"),
			"name" => $this->pre."align",
			"type" => "select",
			"value" => "left",
			"options" => array(__("Center") => "center", __("Left") => "left", __("Right") => "right"),
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Icon Color"),
			"name" => $this->pre."icon_color",
			"type" => "colorpicker",
			"br" => "on",
			"value" => "#ffffff"
		);

		$this->cfg[] = array(
			"label" => __("Icon Background Color"),
			"name" => $this->pre."box_color",
			"type" => "colorpicker",
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Show Network Names"),
			"name" => $this->pre."show_network_names",
			"type" => "checkbox",
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __(""),
			"name" => $this->pre."info",
			"description" => __(""),
			"type" => "info",
			"value" => "on",
			"hr" => "on"
		);

		$shortcode_info = __("To add the <strong>Skale Share-Bar</strong> anywhere you want, you can use a shortcode. You can simply copy & paste this dedicated shortcode").": <br/><div class='sds-shortcode-example'>[sds_share_bar]</div><br/>";
		$shortcode_info .=  __("Possible shortcode options").": <br/>";
		$shortcode_info .= "icon_color, box_color, size <br/>";
		$shortcode_info .= "<strong>". __('Example')." = </strong > [sds_share_bar icon_color='#fff' box_color='#f00' size='20']";

		$this->cfg[] = array(
			"label" => __("SHORTCODE"),
			"name" => $this->pre."info",
			"type" => "info",
			"value" => "on",
			"menu-item" =>  __("Shortcode"),
			"content" => $shortcode_info,
			"br" => "on",
			"hr" => "on"
		);

		$this->cfg[] = array(
			"label" => __("WIDGET"),
			"menu-item" => __("Widget"),
			"name" => $this->pre."info",
			"type" => "info",
			"value" => "on",
			"content" =>  __("To add the <strong>Skale Share-Bar</strong> as a widget, check the <a href='widgets.php'>Wordpress Widgets area</a> and find there our widget. "),
			"br" => "on",
			"hr" => "on"
		);

		$this->cfg[] = array(
			"label" => __("ADVANCED OPTIONS"),
			"name" => $this->pre."info",
			"description" => __(""),
			"type" => "info",
			"value" => "on",
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __("Don't Load <strong>FontAwesome</strong>"),
			"name" => $this->pre."dont_load_fa",
			"type" => "checkbox",
			"description" => __("Don't load the font <strong>FontAwesome</strong> attached in our plugin, if you already load it in your theme."),
			"br" => "on"
		);

		$this->cfg[] = array(
			"label" => __(""),
			"name" => $this->pre."info",
			"description" => "<img src='".plugin_dir_url( __FILE__ )."/images/f-logo.png'>",
			"type" => "info",
			"value" => "on",
		);


		//options end

		$cfgObj = $this->get_options();
    }


	function test_token($direct_call = "no") {
		$result = "";
		$api_key = false;

		if(isset($_POST["api_key"]) && strlen($_POST["api_key"]) > 3){
			$api_key = $_POST["api_key"];
		}

		if($direct_call == "yes"){
			$api_key = get_option("sds_api_token");
			if(!$api_key || strlen($api_key) < 5){
				$api_key = false;
			}
		}

        //if no link found in db - get it from the skale digital api and create a new post with the link
		if($api_key){
			//get link from SkaleDigital
		    $sds_api_url = str_replace("__TARGET_URL__", "http://www.google.com", $this->api_url);
		    $sds_api_url = str_replace("__API_TOKEN__", $api_key, $sds_api_url);

			$data = wp_remote_get($sds_api_url);

            if( !is_wp_error($data) ){
                $data = $data["body"];
                $data = json_decode($data);

                if($data->status === "success"){
					$result = "<span data-ok='1' >API KEY: OK</span>";
		        } else {
		            $result = "<span data-ok='0' style='color:orange; font-size:12px; '>Skaledigital.com API Issue: ". $data->message."</span>";
		        }
		    }
		} else {
			$result = "<span data-ok='0'>NO API KEY FOUND</span>";
		}


		if($direct_call !== "yes"){
			echo $result;
			wp_die();
		} else {
			return $result;
		}
	}

	function plugin_activate() {
		$cfgObj = $this->get_options(true);
	}

	function footer_stickybar() {
		$cfgObj = $this->get_options();
		if($cfgObj[$this->pre."sticky_bar"] == "on"){
			?>
			<div id="skaledigital-sharebar-sticky" data-color='<?php echo $cfgObj[$this->pre."sticky_bar_color"]; ?>'><?php echo $this->show_share_buttons(FALSE, TRUE); ?></div>

			<style>
			@media (min-width: <?php echo $cfgObj[$this->pre."max_screen"]; ?>px) { #skaledigital-sharebar-sticky { display: none; } }
			</style>

			<?php
		}
	}

	// get and show share buttons
	function show_share_buttons($content = false, $is_shortcode = false, $atts = false) {
		$cfgObj = $this->get_options();

		if(
			//IS PAGE
			(!is_home() && !is_front_page() && is_page() && $cfgObj[$this->pre."on_homepage"] == 'on')
			||
			(is_single() && $cfgObj[$this->pre."on_posts"] == 'on')
			||
			//IS CATEGORY
			(is_category() && $cfgObj[$this->pre."on_categories"] == 'on')
			||
			//IS ARCHIVE
			(is_archive() && $cfgObj[$this->pre."on_categories"] == 'on')
			||
			( (is_home() || is_front_page() ) && $cfgObj[$this->pre."on_homepage"] == 'on')
			||
			$is_shortcode == true
		) {

			global $post;

			//get data from shortcode
			if($atts !== false){
				$cfgObj[$this->pre."size"] = 		$atts["size"];
				$cfgObj[$this->pre."icon_color"] = 	$atts["icon_color"];
				$cfgObj[$this->pre."box_color"] = 	$atts["box_color"];
			}

			$htmlShareButtons = "";
			if(!$content){ $content = ""; }

			$htmlContent = $content;

			$urlCurrentPage = get_permalink($post->ID);
            $strPageTitle = get_the_title($post->ID);
	        $intPostID = get_the_ID();

			//
			$htmlShareButtons = $this->generateSocialButtonsBar($cfgObj, $urlCurrentPage, $strPageTitle, $intPostID);

			if ($is_shortcode == FALSE) {

				switch ($cfgObj[$this->pre."share_btns_position"]) {
					case 'top': // before the content
						$htmlContent = $htmlShareButtons . $content;
						break;

					case 'bottom': // after the content
						$htmlContent = $content . $htmlShareButtons;
						break;

					case 'both': // before and after the content
						$htmlContent = $htmlShareButtons . $content . $htmlShareButtons;
						break;

					default:
				       $htmlContent = $content . $htmlShareButtons;
				}

			} else {
				$htmlContent = $content . $htmlShareButtons;
			}
			return $htmlContent;

		}



		return $content;
	}


	function generateSocialButton($cfgObj, $social_btn_obj){
		$btnString = "";

		$post_id = false;
		if(isset($social_btn_obj["post_id"]) && $social_btn_obj["post_id"]){
			$post_id = $social_btn_obj["post_id"];
		}

		if($social_btn_obj["name"] == "pinterest" && isset($social_btn_obj["post_id"]) && $social_btn_obj["post_id"]){
			$urlPostThumb = wp_get_attachment_image_src(get_post_thumbnail_id($social_btn_obj["post_id"]), 'full');
			$social_btn_obj["post_thumb"] = $urlPostThumb[0];
		}

		//get icon color
		if(!isset($cfgObj[$this->pre."icon_color"]) || strlen($cfgObj[$this->pre."icon_color"]) < 2){
			$cfgObj[$this->pre."icon_color"] = "#fff";
		}

		//get icon box color
		if(isset($cfgObj[$this->pre."box_color"]) && strlen($cfgObj[$this->pre."box_color"]) > 2 && !is_admin()){
			$social_btn_obj["color"] = $cfgObj[$this->pre."box_color"];
		}

		//transform the shared url [default or using skaledigital shorturl in case api token is found]
		if(isset($social_btn_obj["target_url"])){
			$social_btn_obj["target_url"] = $this->get_short($social_btn_obj["target_url"], $cfgObj[$this->pre."api_token"], $post_id);
			$social_btn_obj["url"] = str_replace("__SHARE_URL__", $social_btn_obj["target_url"], $social_btn_obj["url"]);
		}

		//add page title to shared url
		if(isset($social_btn_obj["target_title"])){
			$social_btn_obj["url"] = str_replace("__PAGE_TITLE__", $social_btn_obj["target_title"], $social_btn_obj["url"]);
		}

		//if we need to get the featured post image
		if(isset($social_btn_obj["post_thumb"])){
			$social_btn_obj["url"] = str_replace("__POST_THUMB__", $social_btn_obj["post_thumb"], $social_btn_obj["url"]);
		} else {
			$social_btn_obj["url"] = str_replace("__POST_THUMB__", "-", $social_btn_obj["url"]);
		}

		$padding = intval(intval($cfgObj[$this->pre."size"])/3 + 3);
		if($cfgObj[$this->pre."shape"] == "icon" && !is_admin()){
			$padding = 2;
		}

		if(is_admin()){
			$cfgObj[$this->pre."icon_color"] = $social_btn_obj["color"];
			$social_btn_obj["color"] = "transparent";
		}

		$btnString .= '<a href="'.$social_btn_obj["url"].'" style="padding:'.$padding.'px; background-color:'.$social_btn_obj["color"].'; font-size:'.$cfgObj[$this->pre."size"].'px; line-height:'.$cfgObj[$this->pre."size"].'px;" data-service="'.$social_btn_obj["name"].'" class="sds-social-button sds-'.$social_btn_obj["name"].'-btn" title="'.$social_btn_obj["label"].'">';
		$btnString .= '<i style="color:'.$cfgObj[$this->pre."icon_color"].'; width:'.$cfgObj[$this->pre."size"].'px; font-size:'.$cfgObj[$this->pre."size"].'px; line-height:'.$cfgObj[$this->pre."size"].'px;" class="fa '.$social_btn_obj["icon"].'" aria-hidden="true"></i>';

		if($cfgObj[$this->pre."show_network_names"] == "on"){
			$btnString .= '<span class="sds-network-name" style="color:'.$cfgObj[$this->pre."icon_color"].';" >'.$social_btn_obj["label"].'</span>';
		}

		$btnString .= '</a>';

		return $btnString;
	}

	function generateSocialButtonsBar($cfgObj, $urlCurrentPage, $strPageTitle, $intPostID){
		$barString = "";

		$enabledButtons = $cfgObj[$this->pre."share_buttons"];
		if(strlen($enabledButtons)){
			$enabledButtons = explode(" ", $enabledButtons);

			if($cfgObj[$this->pre."show_network_names"] == "on"){
				if($cfgObj[$this->pre."shape"] == "circle"){
					$cfgObj[$this->pre."shape"] = "r-box";
				}
			}

			if(count($enabledButtons)){
				$barString .= "<div class='sds-share-icons-bar  sds-align-".$cfgObj[$this->pre."align"]." sds-style-".$cfgObj[$this->pre."shape"]."'>";
			}
			$social_buttons_list = wpw_sds_social_networks();

			foreach ($enabledButtons as $key) {
				if(isset($social_buttons_list[$key])){
					$btnObj = $social_buttons_list[$key];
					$btnObj["target_url"] = $urlCurrentPage;
					$btnObj["target_title"] = $strPageTitle;
					$btnObj["post_id"] = $intPostID;

					$barString .= $this->generateSocialButton($cfgObj, $btnObj);
				}
			}

			if(count($enabledButtons)){
				$barString .= "</div>";
			}
		}

		return $barString;
	}



	//gets the url or the short-url, depending on the api token validation and skaledigital server response.
    public function get_short($url, $sds_api_token, $post_id = false) {
        //return if url is small or api is not found
        if(strlen($url) < 5 || strlen($sds_api_token) < 3){ return $url; }
		$cfgObj = $this->get_options();

		$sds_item_id = 0;

        // CHECK FOR SDS URL IN DB
        $sds_link = "";
        $args = array(
        	'posts_per_page'   => 1,
            'meta_query' => array(
        		array(
        			'key' => 'target_url',
        			'value' => $url,
        		),
        		array(
        			'key' => 'api_token',
        			'value' => $sds_api_token,
        		)
            ),
        	'post_type'        => 'sds_link',
			'post_status'      => 'publish',
        	'suppress_filters' => true
        );
        $sds_links = get_posts( $args );

		//link item found
        if($sds_links){
			//check if has shorturl
            foreach ( $sds_links as $post ) {
                $sds_link = get_post_meta($post->ID, "sds_link", true);
				$sds_item_id = $post->ID;
            }

			//if no shorturl in db item link = we need to wait until is done
			if(!$sds_link || !strlen($sds_link)){
				$sds_link = $url;
			}
        }

        //if no link found in db - get it from the skale digital api and create a new post with the link
        if(!strlen($sds_link)){
            //get link from SkaleDigital
            $sds_api_url = str_replace("__TARGET_URL__", $url, $this->api_url);
            $sds_api_url = str_replace("__API_TOKEN__", $sds_api_token, $sds_api_url);


			//create wp item link in db
			$my_post = array(
			  'post_title'    => $url . " - " . $sds_api_token,
			  'post_content'  => "",
			  'post_status'   => 'publish',
			  'post_type'   => 'sds_link',
			  'post_author'   => 1
			);
			$new_sds_link_id = wp_insert_post( $my_post );
			if($new_sds_link_id){
				update_post_meta($new_sds_link_id, "target_url", $url);
				update_post_meta($new_sds_link_id, "api_token", $sds_api_token);
			}

            // Open the file using the HTTP headers set above
            $data = wp_remote_get($sds_api_url);

            if( !is_wp_error($data) ){
                $data = $data["body"];
                $data = json_decode($data);

                if($data->status === "success"){
                    $sds_link = $data->shortenedUrl;

                    $full_link = strpos($sds_link, "skaledigital.com");
                    //only param detected - add full link to the param
                    if($full_link === false){
                        $sds_link = "http://skaledigital.com/".$sds_link;
                    }
					if($new_sds_link_id){
						update_post_meta($new_sds_link_id, "sds_link", $sds_link);
					}
                } else {
                    echo "<p style='color:orange; font-size:12px; '>NOTIFICATION: SkaleDigital.com API Issue: ". $data->message."!</p>";
                }
            } else {
				//delete created link item from db in case of api error
				if($new_sds_link_id){
					wp_delete_post($new_sds_link_id, true);
				}
				echo $data->get_error_message();
			}
        }



        if(!strlen($sds_link)){
            return $url;
        } else {
			//update the post skale_time, to know when this was generated.
			if($post_id){ update_post_meta($post_id, "skale_link_time", $cfgObj["skale_time"]); }
            return $sds_link;
        }

    }

}

$sds_engine = new SkaleDS();