<?php
defined('ABSPATH') or die('No direct access permitted');

// widget class
class skaledigital_s_widget extends WP_Widget {

	// construct the widget
	public function __construct() {
		parent::__construct(
 		'skaledigital_s_widget', // Base ID
		'Skale Share-Bar', // Name
		array( 'description' => __( 'Skaledigital.com Share-Bar Widget', 'sds-textdomain' ), ) // Args
	);
	}

	// extract required arguments and run the shortcode
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if (!empty($title))
		echo $before_title . $title . $after_title;
		$shortcode = '[sds_share_bar]';
		echo do_shortcode($shortcode, 'sds-textdomain' );
		echo $after_widget;
	}

	public function form( $instance )
	{
		if ( isset( $instance[ 'title' ] ) )
		{
			$title = $instance[ 'title' ];
		}
		else
		{
			$title = __( 'Share Buttons', 'sds-textdomain' );
		}
		echo '<p><label for="' . $this->get_field_id('title') . '">' . 'Title:' . '</label><input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></p>';
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

}

// add ssba to available widgets
add_action( 'widgets_init', create_function( '', 'register_widget( "skaledigital_s_widget" );' ) );
