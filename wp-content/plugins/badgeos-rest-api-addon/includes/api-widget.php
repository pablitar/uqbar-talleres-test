<?php
//widget displays API creation form for the logged in user
class badgeos_restful_api_key_widget extends WP_Widget {

	//process the new widget
	function __construct() {
		$widget_ops = array(
			'classname' => 'badgeos_restful_api_addon',
			'description' => __( 'Displays API subscription form to registered users.', 'badgeos' )
		);
		parent::__construct( 'badgeos_restful_api_widget', __( 'BadgeOS API subscription', 'badgeos' ), $widget_ops );
	}

	//build the widget settings form
	function form( $instance ) {
		$defaults = array( 'title' => __( 'API Key Subscription', 'badgeos' ),'api_access' => "read" );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$api_access = $instance['api_access'];
        ?>
            <p><label><?php _e( 'Title', 'badgeos' ); ?>: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>
			<p>
				<label>
					<?php _e( 'Default API Access', 'badgeos' ); ?>: 
					<select id="api_access" name="<?php echo esc_attr( $this->get_field_name( 'api_access' ) ); ?>" class="widget-total-points-type">
						<option value="Read" selected><?php _e( 'Read Only', 'bos-api' ); ?></option>
						<option value="Read_Write" <?php echo $api_access=='Read_Write'? 'selected':''; ?>><?php _e( 'Read/Write', 'bos-api' ); ?></option>
						<option value="choose" <?php echo $api_access=='choose'? 'selected':''; ?>><?php _e( 'Allow Choose', 'bos-api' ); ?></option>
					</select>
				</label>
			</p>
        <?php
	}

	//save and sanitize the widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['api_access'] = sanitize_text_field( $new_instance['api_access'] );
        return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {
		global $user_ID;

		if( array_key_exists( 'before_widget', $args ) )
			echo $args['before_widget'];

        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
		$title = apply_filters( 'widget_title', $instance['title'] );
        $api_access = isset( $instance['api_access'] ) ? $instance['api_access'] : 'Read';
        

        if ( !empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; };

		//user must be logged in to view earned badges and points
		if ( is_user_logged_in() ) {
				
			$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
			if ( isset( $settings['badgeos_settings_rest_enable_api_keys'] ) && $settings['badgeos_settings_rest_enable_api_keys']=='yes') {
				$user_id = get_current_user_id();
				$args=array(
					'post_type' => 'badgeos-api-keys',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'author' => $user_id
				);
			
				$api_keys = get_posts( $args );
				$total = count($api_keys);
				?>
					<p class="badgeos-total-points">
						<form id="frm_badge_restapi_submit" name="frm_badge_restapi_submit">
							<table>
								<?php if( $total > 0 ) { 
										
									$apikey = get_post_meta( $api_keys[0]->ID, '_badgeos_restapi_apikey', true );
								?> 
									<tr>
										<td valign="top" width="30%"><?php _e( 'API Key:', 'bos-api' ); ?></td>
										<td valign="top" width="70%">
											<input readonly="readonly" type="text" value="<?php echo $apikey; ?>" /><br>
										</td>
									</tr>
								<?php } else {
									if( $api_access == 'choose' ) { ?> 
										<tr>
											<td valign="top"><?php _e( 'API Access', 'bos-api' ); ?></td>
											<td valign="top">
												<select name="badgeos_restapi_access" id="badgeos_restapi_access" class="widget-total-points-type">
													<option value="Read" selected><?php _e( 'Read Only', 'bos-api' ); ?></option>
													<option value="Read_Write" <?php echo $api_access=='Read_Write'? 'selected':''; ?>><?php _e( 'Read/Write', 'bos-api' ); ?></option>
												</select>
											</td>
										</tr>
									<?php } else { ?> 
										<input type="hidden" name="badgeos_restapi_access" id="badgeos_restapi_access" value="<?php echo $api_access; ?>" />
									<?php } ?>
									<tr>
										<td valign="top" width="30%"><?php _e( 'Allowed Domain', 'bos-api' ); ?></td>
										<td valign="top" width="70%">
											<input class="widefat" name="badgeos_restapi_domain" id="badgeos_restapi_domain" type="url" value="" /><br>
										</td>
									</tr>
									<tr>
										<td valign="top"></td>
										<td valign="top">
											<input type="submit" class="btn btn-primary" id="btn_badgeos_restapi_widget_submit" class="btn_badgeos_restapi_widget_submit" value="<?php _e( 'Generate', 'bos-api' ); ?>" />
										</td>
									</tr>
									<input type="hidden" name="action" value="badgeos_restapi_api_generate">
								<?php } ?>
							</table>
						</form>
					</p>
				<?php
			} else {
				//user is not logged in so display a message
				_e( 'API key is not needed for this website.', 'bos-api' );
			}
		} else {
			//user is not logged in so display a message
			_e( 'You must be logged in to view/generate an API key.', 'bos-api' );
		}
		if( array_key_exists( 'after_widget', $args ) )
			echo $args['after_widget'];
	}

}

// use widgets_init action hook to execute custom function
add_action( 'widgets_init', 'badgeos_restapi_register_widgets' );

 //register our widget
function badgeos_restapi_register_widgets() {

	register_widget( 'badgeos_restful_api_key_widget' );
}

add_action( 'wp_ajax_badgeos_restapi_api_generate', 'badgeos_restapi_api_generate_callback' );

/**
 * hook in our credly ajax function
 */
function badgeos_restapi_api_generate_callback() {
	ini_set( 'display_errors', 'On' );
	error_reporting( E_ALL );
	$access = sanitize_text_field( $_POST['badgeos_restapi_access'] );
	$domain = sanitize_text_field( $_POST['badgeos_restapi_domain'] );

	$current_user = wp_get_current_user();
	if( $current_user ) {
		$api_key = md5( time().rand(0,1000000) );
		$title = $current_user->user_login.' '.__( 'with id#', 'bos-api' ).get_current_user_id().' '.__( ' generated', 'bos-api' ).' '.$api_key;
		$post_args = array(
			'post_title'    => $title,
			'post_content'  => $title,
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_type' 	=> 'badgeos-api-keys',
			'post_category' => array( )
		  );
		$result = wp_insert_post( $post_args );

		if ( $result && ! is_wp_error( $result ) ) {
			$post_id = $result;
			update_post_meta( $post_id, '_badgeos_restapi_apikey', $api_key );
			update_post_meta( $post_id, '_badgeos_restapi_permission', $access );
			update_post_meta( $post_id, '_badgeos_restapi_domain', $domain );
			update_post_meta( $post_id, '_badgeos_restapi_user', $current_user->user_login );
			
			wp_send_json( [ 'type'=>'success', 'message'=> __( 'API Key is generated.', 'bos-api' ) ] );
		}  else{
			wp_send_json( [ 'type'=>'error', 'message'=> __( 'We are unable to process your request.', 'bos-api' ) ] );
		}
	} else{
		wp_send_json( [ 'type'=>'error', 'message'=> __( 'You must be login before generating an API key.', 'bos-api' ) ] );
	}

	
	   
	  // Insert the post into the database
	  wp_insert_post( $my_post );
	print_r($_REQUEST);
	// if ( ! isset( $_REQUEST['ID'] ) ) {
	// 	echo json_encode( sprintf( '<strong class="error">%s</strong>', __( 'Error: Sorry, nothing found.', 'badgeos' ) ) );
	// 	die();
	// }

	// $send_to_credly = $GLOBALS['badgeos_credly']->post_credly_user_badge( get_current_user_id(), $_REQUEST['ID'] );

	// if ( $send_to_credly ) {

	// 	echo json_encode( sprintf( '<strong class="success">%s</strong>', __( 'Success: Sent to Credly!', 'badgeos' ) ) );
	// 	die();

	// } else {

	// 	echo json_encode( sprintf( '<strong class="error">%s</strong>', __( 'Error: Sorry, Send to Credly Failed.', 'badgeos' ) ) );
	// 	die();

	// }
}