<?php
/**
 * BadgeOS REST API Settings
 *
 * @author   BadgeOS
 * @category Admin
 * @package  BadgeOS_REST_API/SETTINGS
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BOS_SOCIAL_SHARING_SETTINGS
 */
class BadgeOS_REST_API_SETTINGS {

    /**
     * Hook in tabs.
     */
    public function __construct () {

        add_action( 'badgeos_general_settings_tab_header', array( $this, 'settings_tab_header' ) );
		add_action( 'badgeos_general_settings_tab_content', array( $this, 'settings' ) );
    }

    /**
	 * Register add-on settings.
	 *
	 * @since 1.0.0
	 */
	function settings_tab_header( $settings = [] ) {
		?>
            <li>
                <a href="#badgeos_settings_rest_api">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-code" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'REST API', 'badgeos' ); ?>
                </a>
            </li>
		<?php
	}
	
	/**
	 * Register add-on settings.
	 *
	 * @since 1.0.0
	 */
	function settings( $settings = [] ) {
        
        $badgeos_settings_rest_api_enable        = isset( $settings['badgeos_settings_rest_api_enable'] ) ? sanitize_text_field( $settings['badgeos_settings_rest_api_enable'] ): 'no';
        $badgeos_settings_rest_enable_api_keys   = isset( $settings['badgeos_settings_rest_enable_api_keys'] ) ? sanitize_text_field( $settings['badgeos_settings_rest_enable_api_keys'] ): 'no';
        $badgeos_settings_rest_api_endpoint      = isset( $settings['badgeos_settings_rest_api_endpoint'] ) && !empty( $settings['badgeos_settings_rest_api_endpoint'] ) ? sanitize_text_field( $settings['badgeos_settings_rest_api_endpoint'] ): 'badgeos-api';
        
        ?>
			<div id="badgeos_settings_rest_api">
                <table cellspacing="5" cellpadding="5" width="100%">
                    <tbody>
                        <tr valign="top">
                            <th scope="row" width="25%"><label for="badgeos_settings_rest_api_enable"><?php _e( 'Enable BadgeOS API?', 'badgeos' ); ?></label></th>
                            <td width="75%">
                                <label>
                                    <select id="badgeos_settings_rest_api_enable" name="badgeos_settings[badgeos_settings_rest_api_enable]">
                                        <option value="no" selected><?php _e( 'No', 'badgeos' ); ?></option>
                                        <option value="yes" <?php echo $badgeos_settings_rest_api_enable=='yes'?'selected':'';?>><?php _e( 'Yes', 'badgeos' ); ?></option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" width="25%"><label class="badgeos_hide_if_api_disabled" for="badgeos_settings_rest_enable_api_keys"><?php _e( 'Enable API Keys?', 'badgeos' ); ?></label></th>
                            <td width="75%">
                                <label class="badgeos_hide_if_api_disabled">
                                    <select id="badgeos_settings_rest_enable_api_keys" name="badgeos_settings[badgeos_settings_rest_enable_api_keys]">
                                        <option value="no" selected><?php _e( 'No', 'badgeos' ); ?></option>
                                        <option value="yes" <?php echo $badgeos_settings_rest_enable_api_keys=='yes'?'selected':'';?>><?php _e( 'Yes', 'badgeos' ); ?></option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label class="badgeos_hide_if_api_disabled" for="badgeos_settings_rest_api_endpoint"><?php _e( 'API End Point:', 'badgeos' ); ?></label></th>
                            <td>
                                <label class="badgeos_hide_if_api_disabled">
                                    <input type="text" id="badgeos_settings_rest_api_endpoint" value="<?php echo $badgeos_settings_rest_api_endpoint;?>" name="badgeos_settings[badgeos_settings_rest_api_endpoint]" />
                                </label>
                            </td>
                        </tr>
                        <?php do_action( 'badgeos_general_settings_rest_api_fields', $settings ); ?>
                    </tbody>
                </table>
                <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">
            </div>
		<?php
	} /* settings() */
}

$GLOBALS['BadgeOS_REST_API_SETTINGS'] = new BadgeOS_REST_API_SETTINGS();