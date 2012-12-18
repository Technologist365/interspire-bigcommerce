<?php

// Plugin Settings Class
class Bigcommerce_settings {
	static $configured = false;
	static $errors = array();

	// Gets Stored Settings, Failover To Defaults
	function get_options() {
		return ( object ) get_option(
			'wpinterspire', array(
				'username' => '',
				'xmltoken' => '',
				'storepath' => '',
				'showlink' => '',
			)
		);
	}

	// Tied To WP Hook By The Same Name
	function admin_init() {
		global $pagenow;

		// Handles Saving Of Settings
        register_setting(
        	'wpinterspire_options',
        	'wpinterspire',
        	array( 'Bigcommerce_settings', 'sanitize_settings' )
        );

		// Load Support For Localizations
		load_plugin_textdomain(
			'wpinterspire', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

		// Only Continue For Self Settings Page
		if(
			$pagenow == 'options-general.php'
			&& isset( $_REQUEST['page'] )
			&& $_REQUEST['page'] != 'wpinterspire'
		) { return; }

		// Run Settings Check
		self::$configured = self::CheckSettings();

		// (Re)Build Products Upon Request
		if (
			isset( $_REQUEST['wpinterspirerebuild'] )
			&& $_REQUEST['wpinterspirerebuild'] == 'all'
		) {
			Bigcommerce_parser::BuildProductsSelect( true );
			Bigcommerce_parser::BuildCategoriesSelect( true );
		}
    }

	// Sanitizes Setting Value Submissions
	function sanitize_settings( $input ) {
		return $input;
	}

	// Checks Saved Settings
	function CheckSettings() {
    	return ( Bigcommerce_api::GetCategories() );
	}

	// Tied To WP Hook By The Same Name - Adds Settings Link
	function plugin_action_links( $links ) {
		$links['settings'] = '<a href="options-general.php?page=wpinterspire">'
			. __( 'Settings', 'wpinterspire' ) . '</a>';
		return $links;
	}

	// Tied To WP Hook By The Same Name - Adds Admin Submenu Link
    function admin_menu() {
		add_options_page(
			'Bigcommerce',
			'Bigcommerce',
			'administrator',
			'wpinterspire',
			array( 'Bigcommerce_settings', 'admin_page' )
		);
    }

    // Tied To Admin Submenu Link
	function admin_page() {
    	$options = self::get_options();
		$vendors = array(
			'http://beautomated.bigcommerce.com/',
			'http://katzwebservices.bigcommerce.com/',
		);
		require( dirname( __FILE__ ) . '/../views/admin-page.html.php' );
    }

	// Displays The Configuration Check
	function show_configuration_check() {

		// Configured
		if( self::$configured ) {

			// Configured Message
			$content = __( 'Your Bigcommerce API settings are configured properly.', 'wpinterspire' )
				. (

					// Add Caching Status Messages
					( ! get_option( 'wpinterspire_productselect' ) )
					? __( ' However, your products and categories have not yet been built.', 'wpinterspire' )
					: __( ' When editing posts, look for the ', 'wpinterspire' )
						. '<img src="' . plugins_url( 'favicon.png', dirname( __FILE__ ) )
						. '" width="16" height="16" alt="' . __( 'Bigcommerce icon', 'wpinterspire') . '" />'
						. __( ' icon. Click it to add a product to your post or page.', 'wpinterspire' )
				);

		// Unconfigured
		} else {
			$content =  __( 'Your Bigcommerce API settings are not configured properly.', 'wpinterspire' );

			// Add Specific Errors
			if( self::$errors ) {
				$content .= '<br /><blockquote>' . implode( '<br />', self::$errors ) . '</blockquote>';
			}
		}

		// Output
		echo self::make_notice_box(
			$content, ( ( self::$configured ) ? false : true )
		);
	}

	// Generic Notice Box Maker
    function make_notice_box( $content, $error=false ) {
        $output = '';
        if( ! $error ) {
        	$output .= '<div id="message" class="updated">';
        } else {
            $output .= '<div id="messgae" class="error">';
        }
        $output .= '<p>' . $content . '</p></div>';
        return $output;
    }
}

?>