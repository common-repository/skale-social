<?php
// skale-social plugin - wordpress function


class SkaleDSwp{



	//shortcode
	function sds_share_bar($atts) {
		$cfgObj = $this->get_options();

		$atts = shortcode_atts( array(
			'size' => $cfgObj[$this->pre."size"],
			'icon_color' => $cfgObj[$this->pre."icon_color"],
			'box_color' => $cfgObj[$this->pre."box_color"]
		), $atts );

		$htmlShareButtons = $this->show_share_buttons(FALSE, TRUE, $atts);
		return $htmlShareButtons;
	}

	function page_scripts() {
		if(!get_option($this->pre."dont_load_fa") || is_admin()){
			wp_register_style('fontawesome', plugins_url('/assets/fontawesome/css/font-awesome.min.css', SDS_FILE ));
			wp_enqueue_style('fontawesome');
		}

		if( is_admin() ) {
    		wp_enqueue_style( 'wp-color-picker' );
		}

		wp_register_style('sds-css', plugins_url('/css/style.css', SDS_FILE ));
		wp_enqueue_style('sds-css');

		wp_enqueue_script("jquery-ui-sortable");
		if( is_admin() ) {
			wp_register_script('sds-js', plugins_url('/js/module.js', SDS_FILE ), array( 'wp-color-picker' ), false, true );
			wp_enqueue_script('sds-js');
		} else {
			wp_register_script('sds-js', plugins_url('/js/module.js', SDS_FILE ));
			wp_enqueue_script('sds-js');
		}
	}


	function create_menu() {
		//create new top-level menu
		add_menu_page($this->plug_name, $this->plug_name.' Settings', 'administrator', __FILE__,  array( &$this, 'settings_page') ,plugins_url('/images/skaledigital_icon_small.png', __FILE__));

		//call register settings function
		add_action( 'admin_init',  array( &$this, 'register_mysettings') );
	}

	function settings_page() {
		?>
		<div class="wrap sds-options-area">
		<h2><?php echo $this->plug_name; ?> Settings</h2>
		<form method="post" action="options.php">
		    <?php settings_fields( 'sds-settings-group' ); ?>
			<input type="hidden" value="<?php echo $this->api_url; ?>" id="sds-api-url">
			<?php
			//generate settings
			foreach($this->cfg as $sds_option){
				if(isset($sds_option["value"])){
					$sds_option["value"] = get_option($sds_option["name"], $sds_option["value"]);
				} else {
					$sds_option["value"] = get_option($sds_option["name"]);
				}


				if($sds_option["type"] == "checkbox"){
					$sds_option['checked'] = ($sds_option['value'] == "on" ? 'checked="checked"' : "");
					$sds_option["value"] = "on";
				}

				if($sds_option["type"] == "select"){
					$sds_option['selected'] = $sds_option['value'];
				}

				if(!isset($sds_option["placeholder"])){
					$sds_option['placeholder'] = '';
				}
				echo $this->generate_input($sds_option);

				if($sds_option["name"] == $this->pre."share_buttons"){
					// generate social buttons list
					$social_buttons_list = wpw_sds_social_networks();

					?>
					<div class='social-icons-toggle'>
					<?php
					$cfgObj = $this->get_options();
					foreach($social_buttons_list as $social_btn_obj){
						echo $this->generateSocialButton($cfgObj, $social_btn_obj);
					}
					?>
					</div>
					<hr/>
					<?php
				}

			}
			?>

		    <p class="submit">
		    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		    </p>

		</form>

		</div>
		<?php
	}


	function create_post_type() {
        $labels = array(
    		'name'               => _x( 'Skale Links', 'post type general name', 'sds-textdomain' ),
    		'singular_name'      => _x( 'Skale Link', 'post type singular name', 'sds-textdomain' ),
    		'menu_name'          => _x( 'Skale Links', 'admin menu', 'sds-textdomain' ),
    		'name_admin_bar'     => _x( 'Skale Link', 'add new on admin bar', 'sds-textdomain' ),
    		'add_new'            => _x( 'Add New', 'book', 'sds-textdomain' ),
    		'add_new_item'       => __( 'Add New', 'sds-textdomain' ),
    		'new_item'           => __( 'New', 'sds-textdomain' ),
    		'edit_item'          => __( 'Edit', 'sds-textdomain' ),
    		'view_item'          => __( 'View', 'sds-textdomain' ),
    		'all_items'          => __( 'All', 'sds-textdomain' ),
    		'search_items'       => __( 'Search', 'sds-textdomain' ),
    		'parent_item_colon'  => __( 'Parent:', 'sds-textdomain' ),
    		'not_found'          => __( 'Nothing found.', 'sds-textdomain' ),
    		'not_found_in_trash' => __( 'Nothing found in Trash.', 'sds-textdomain' )
    	);

    	$args = array(
    		'labels'             => $labels,
            'description'        => __( 'SkaleDigital Shortcode url.', 'sds-textdomain' ),
    		'public'             => true,
    		'publicly_queryable' => true,
    		'show_ui'            => true,
    		'show_in_menu'       => true,
    		'query_var'          => true,
    		'rewrite'            => array( 'slug' => 'sds_link' ),
    		'capability_type'    => 'post',
    		'has_archive'        => true,
    		'hierarchical'       => false,
    		'menu_position'      => null,
    		'supports'           => array( 'title', 'author', 'custom-fields' )
    	);

    	register_post_type( 'sds_link', $args );
    }

	public function settings_page_button($links) {
    	$links[] = '<a href="admin.php?page=skale-social/skale-wp.php">'.__('Settings').'</a>';
    	return $links;
    }

	function before_post_delete( $postid ){

		$post_type = get_post_type($postid);

		if ( $post_type == 'sds_link' ) {
			$parent_id = url_to_postid( get_post_meta($postid, "target_url", true) );
			if($parent_id){
				update_post_meta($parent_id, "skale_link_time", "");
			}

			wp_delete_post($postid, true);
		}
	}


	function register_mysettings() {
		foreach($this->cfg as $sds_option){
			register_setting( 'sds-settings-group', $sds_option["name"]);
		}
	}

	//delete link items, 20 at a time.
	function delete_all_sds_links($data) {
		$args = array(
			'post_type'=> 'sds_link',
			"posts_per_page" => 20
		);
		$myposts = get_posts( $args );

		foreach ( $myposts as $pt ){
			wp_trash_post($pt->ID);
		}

		$remaining =  wp_count_posts('sds_link');
		echo $remaining->publish;
		die();
	}

	// gets next posts to have their permalink transformed in shorturls
	// 10 at a time.
	function get_posts_to_generate_links($direct_call = "no") {
		$cfgObj = $this->get_options();
		$time_id = $cfgObj["skale_time"];
		$done_nr = 0;
		if(!isset($_POST["apikey"]) && $direct_call !== "yes"){ echo 0; die();}
		if(isset($_POST["done_nr"])){
			$done_nr = intval($_POST["done_nr"]);
		}

		if($direct_call == "yes"){
			$api_key = get_option("sds_api_token");
		} else if(isset($_POST["apikey"])) {
			$api_key = $_POST["apikey"];
		}

		//get posts and pages that have the skale_link_time different

		$args = array(
			'posts_per_page'   => 10,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'skale_link_time',
					'compare' => 'NOT EXISTS' // doesn't work
				),
				array(
					'key' => 'skale_link_time',
					'value' => $time_id,
					'compare' => "!="
				)
			),
			'post_type'        => array("post", "page"),
			'post_status'      => 'publish'
		);


		$posts_array = get_posts( $args );
		$nr_generated = count($posts_array);
		$done_nr = $done_nr + $nr_generated;

		foreach ($posts_array as $post) {
			$perma = get_permalink($post->ID);

			update_post_meta($post->ID, "skale_link_time", $time_id);
			$this->get_short($perma, $api_key);
		}

		//get remaining
		$args = array(
			'posts_per_page'   => -1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'skale_link_time',
					'compare' => 'NOT EXISTS' // doesn't work
				),
				array(
					'key' => 'skale_link_time',
					'value' => $time_id,
					'compare' => "!="
				)
			),
			'post_type'        => array("post", "page"),
			'post_status'      => 'publish'
		);

		$posts_array = get_posts( $args );
		$result = '<div data-remaining="'.count($posts_array).'" data-done="'.$done_nr.'"><div/>';

		if($direct_call !== "yes"){
			echo $result;
			die();
		} else {
			return $result;
		}
	}


	function count_linkable_skale_posts($data) {
		//generate new url_creation_time because we regenerate links

		//force regenerate for all by changing this time id
		if(isset($_POST["force_regen"]) && $_POST["force_regen"] == "on"){
			update_option($this->pre."_skale_time", date('H:i:s', time()));
		}


		$posts =  wp_count_posts('post');
		$pages =  wp_count_posts('page');



		echo $posts->publish + $pages->publish;
		die();
	}

	function get_posts_without_($data) {
		$posts =  wp_count_posts('post');
		$pages =  wp_count_posts('page');
		echo $posts->publish + $pages->publish;
		die();
	}




	//execute this daily - search posts and pages without shorturl generated, in case api key is ok
	function cron_action() {
		$result = $this->test_token("yes");
		//if apy key not good, end here
		if(strpos($result, "data-ok='1'") === false){ return false; }
		update_option("sds-testing-api-key", "OK");
		$this->get_posts_to_generate_links("yes");

	}




}
