<?php
/*
 * Plugin Name: WP Mautic Form Integrator
 * Plugin URI: http://hireawiz.com
 * Description: WP Mautic Form Integrator plugin is a bridge between Mautic & several highly used form builder plugins.
 * Version: 1.0.3
 * Author: Hireawiz
 * textdomain: wp-mautic-form-integrator
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include __DIR__ . '/vendor/autoload.php';

use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

/**
 * Global variable to manage the modules.
 * Module Name
 * Module Label
 * Module Plugin File Absolute Path
 */
global $haw_mautic_modules, $haw_mautic_modules_name, $haw_mautic_modules_plugin_file;
$haw_mautic_modules = $haw_mautic_modules_name = $haw_mautic_modules_plugin_file = array();

/**
 * Include all of the form integration files
 */
foreach ( glob( dirname( __FILE__ ) . "/modules/*.php" ) as $filename ) {
	require_once $filename;
}

/**
 * Start session for Mautic API
 */
function haw_mautic_integration_register_session() {
	$page = isset( $_GET['page'] )? sanitize_text_field( $_GET['page'] ) : '';
	if ( $page == 'haw-mautic-integration-add-new' || $page == 'haw-mautic-integration' ) {
		ob_start();
		if ( ! session_id() ) {
			session_name( 'wp-mautic' );
			session_start();
		}
	}

	if ( $page == 'haw-mautic-integration-setting' ) {
		ob_start();
	}
}
add_action('admin_init', 'haw_mautic_integration_register_session');

/**
 * Destroy mautic session on logout
 */
function haw_mautic_integration_logout() {
	if ( session_id() ) {
        session_destroy();
    }
}
add_action( 'wp_logout', 'haw_mautic_integration_logout' );

/**
 * Add ajax url in head
 */
function haw_mautic_integration_set_ajax_url() {
	echo "<script> var haw_mautic_integration_ajax_url = '" . admin_url('admin-ajax.php') . "';</script>";
}
add_action('admin_head','haw_mautic_integration_set_ajax_url');

/**
 * Create table on plugin activation for Mautic form & fields
 */
function haw_mautic_integration_activate() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	//Mautic form table
	$table_name = $wpdb->prefix . 'haw_mautic_forms';
	if ( $wpdb->get_var( "show tables like '$table_name'") != $table_name ) {
		$sql = "CREATE TABLE $table_name (
		    id int(11) NOT NULL,
		    name varchar(255) DEFAULT '' NOT NULL,
		    alias varchar(255) DEFAULT '' NOT NULL,
		    UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	//Mautic form fields table
	$table_name = $wpdb->prefix . 'haw_mautic_form_fields';
	if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL,
            form_id int(11) NOT NULL,
            label varchar(255) DEFAULT '' NOT NULL,
            type varchar(55) DEFAULT '' NOT NULL,
            alias varchar(255) DEFAULT '' NOT NULL,
            UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	//Form map field with mautic table
	$table_name = $wpdb->prefix . 'haw_mautic_integration_form';
	if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE $table_name (
		    id int(11) NOT NULL AUTO_INCREMENT,
		    form_1 int(11) NOT NULL,
		    form_2 int(11) NOT NULL,
		    form_1_field varchar(255) DEFAULT '' NOT NULL,
		    form_2_field int(11) NOT NULL,
		    form_1_type varchar(255) DEFAULT '' NOT NULL,
		    PRIMARY KEY (id),
		    UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	add_option('haw_mautic_integration_do_activation_redirect', true);
}
register_activation_hook( __FILE__, 'haw_mautic_integration_activate' );

/**
 * Load the JS/CSS files
 */
function haw_mautic_admin_asset() {
	global $haw_mautic_add_new_page, $haw_mautic_list_page, $haw_mautic_setting_page;

    $screen = get_current_screen();

	// Add asset on Add / Edit page
    if (
        ($screen->id == $haw_mautic_add_new_page) ||
        (
            $screen->id == $haw_mautic_list_page &&
            isset( $_GET['action'] ) &&
            sanitize_text_field($_GET['action']) == 'edit'
        )
    ) {
		wp_enqueue_style( 'haw-mautic-css', plugin_dir_url( __FILE__ ) . 'css/main.css', false );
		wp_enqueue_script( 'haw-mautic-js', plugin_dir_url( __FILE__ ) . 'js/main.js', '', '', true );
    }

    // Add asset on Settings page
    if ( $screen->id == $haw_mautic_setting_page )
    {
        wp_enqueue_style( 'haw-mautic-css', plugin_dir_url( __FILE__ ) . 'css/main.css', false );
    }
}
add_action( 'admin_enqueue_scripts', 'haw_mautic_admin_asset' );

/**
 * Register new menu in the admin
 */
function haw_mautic_integration_menu() {
	global $haw_mautic_add_new_page, $haw_mautic_list_page, $haw_mautic_setting_page;

	$haw_mautic_list_page = add_menu_page(
        'WP Mautic Form Integrator',
        'WP Mautic Form Integrator',
        'manage_options',
        'haw-mautic-integration',
        'haw_mautic_integration_list',
        'dashicons-welcome-widgets-menus'
    );

	$haw_mautic_add_new_page = add_submenu_page(
        'haw-mautic-integration',
        'Add New', 'Add New',
        'manage_options',
        'haw-mautic-integration-add-new',
        'haw_mautic_integration_add_new'
    );

	$haw_mautic_setting_page = add_submenu_page(
		'haw-mautic-integration',
		'Settings',
		'Settings',
		'manage_options',
		'haw-mautic-integration-setting',
		'haw_mautic_integration_setting'
	);
}
add_action( 'admin_menu', 'haw_mautic_integration_menu' );

/**
 * Add setting option on Plugin listing page.
 * @param $links
 * @return array
 */
function haw_mautic_integration_action_links( $links ) {
    $links[] = '<a href="' . get_admin_url(null, 'admin.php?page=haw-mautic-integration-setting') .'">Settings</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'haw_mautic_integration_action_links' );

/**
 * Display Setting page
 */
function haw_mautic_integration_setting() {
	global $haw_mautic_modules, $haw_mautic_modules_name, $haw_mautic_modules_plugin_file;

	// Check for admin error notice if no dependent plugin is activated.
    haw_mautic_integration_no_plugin_activated($haw_mautic_modules_plugin_file);

	if ( isset( $_POST['submit'] ) && check_admin_referer( 'haw_mautic_integration_settings' ) ) {
		$publicKey = get_option( 'haw_mautic_public_key' );
		$secretKey = get_option( 'haw_mautic_secret_key' );

		$haw_mautic_public_key = sanitize_text_field( $_POST['haw_mautic_public_key'] );
		$haw_mautic_secret_key = sanitize_text_field( $_POST['haw_mautic_secret_key'] );

		if( $publicKey != $haw_mautic_public_key || $secretKey != $haw_mautic_secret_key ) {
			update_option( 'haw_mautic_access_token_data', '' );
			session_destroy();
		}

		$haw_mautic_drop_data = isset( $_POST['haw_mautic_drop_data'] )? intval( $_POST['haw_mautic_drop_data'] ) : '0';

		update_option( 'haw_mautic_base_url',   esc_url( trim( $_POST['haw_mautic_base_url'], '/' ) ) );
		update_option( 'haw_mautic_auth_type',  sanitize_text_field( $_POST['haw_mautic_auth_type'] ) );
		update_option( 'haw_mautic_public_key', $haw_mautic_public_key );
		update_option( 'haw_mautic_secret_key', $haw_mautic_secret_key );
		update_option( 'haw_mautic_drop_data',  $haw_mautic_drop_data );

		if ( count( $haw_mautic_modules ) > 0 ) {
			foreach ( $haw_mautic_modules as $module) {
				$mod_enable = isset( $_POST[ $module ] )? intval( $_POST[ $module ] ) : '0';
				update_option( $module, $mod_enable );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=haw-mautic-integration-setting&settings-updated=true' ) );
	}
	?>

	<?php if ( isset( $_GET['settings-updated'] ) ) { ?>
	    <div id="message" class="updated is-dismissible">
	        <p>Settings saved successfully.</p>
	    </div>
	<?php } ?>

	<div class="wrap">
	<h2><?php esc_html_e( 'WP Mautic From Integrator Settings', 'wp-mautic-form-integrator' ); ?></h2>

	<form method="post" action="">
		<?php wp_nonce_field( 'haw_mautic_integration_settings' ); ?>
		<table class="form-table haw_mautic_integration_settings">
			<tr valign="top">
				<th scope="row">Mautic Base URL</th>
				<td>
					<input type="text" name="haw_mautic_base_url" value="<?php echo esc_url( get_option('haw_mautic_base_url') ); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Auth Type</th>
				<td>
					<select class="post_form" name="haw_mautic_auth_type">
						<option value="OAuth1a" <?php echo ( get_option('haw_mautic_auth_type') == 'OAuth1a' )? 'selected="selected"' : ''; ?> >
							OAuth 1
						</option>
						<option value="OAuth2" <?php echo ( get_option('haw_mautic_auth_type') == 'OAuth2' )? 'selected="selected"' : ''; ?> >
							OAuth 2
						</option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Mautic Redirect URI</th>
				<td>
					<?php echo admin_url( 'admin.php?page=haw-mautic-integration-add-new' ); ?>
					<br>
					<p id="tagline-description" class="description">Redirect URI that you should add in Mautic API Credential.</p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Mautic Public Key</th>
				<td>
					<input type="text" name="haw_mautic_public_key" value="<?php echo esc_attr( get_option('haw_mautic_public_key') ); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Mautic Secret Key</th>
				<td>
					<input type="text" name="haw_mautic_secret_key" value="<?php echo esc_attr( get_option('haw_mautic_secret_key') ); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Mautic Integration For:</th>
				<td></td>
			</tr>

			<?php
			if ( count( $haw_mautic_modules ) > 0 ) {
				foreach ( $haw_mautic_modules as $module ) {
					if ( is_plugin_active( $haw_mautic_modules_plugin_file[ $module ] ) ) {
                    ?>
						<tr valign="top">
							<th scope="row"><?php echo $haw_mautic_modules_name[ $module ]; ?></th>
							<td>
								<input name="<?php echo $module; ?>" type="checkbox" value="1" <?php checked( '1', intval( get_option( $module ) ) ); ?> />
							</td>
						</tr>
					<?php
					}
				}
			}
	        ?>

			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr valign="top">
				<th scope="row">Drop database & options on uninstall</th>
				<td>
					<input name="haw_mautic_drop_data" type="checkbox" value="1" <?php checked( '1', intval( get_option( 'haw_mautic_drop_data' ) ) ); ?> />
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>

	</form>
	<?php
}

/**
 * Get form from Mautic server using Mautic API
 * @param string $page
 * @return bool
 */
function haw_mautic_integration_get_forms_from_server( $page = 'haw-mautic-integration-setting' ) {
	$baseUrl    = get_option( 'haw_mautic_base_url' );
	$version    = get_option( 'haw_mautic_auth_type' );
	$publicKey  = get_option( 'haw_mautic_public_key' );
	$secretKey  = get_option( 'haw_mautic_secret_key' );

	if ( ! empty( $baseUrl ) && ! empty( $version ) && ! empty( $publicKey ) && ! empty( $secretKey ) ) {
		$callback = admin_url( 'admin.php?page=' . $page );

		// ApiAuth::initiate will accept an array of OAuth settings
		$settings = array(
			'baseUrl'      => $baseUrl, // Base URL of the Mautic instance
			'version'      => $version, // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
			'clientKey'    => $publicKey, // Client/Consumer key from Mautic
			'clientSecret' => $secretKey, // Client/Consumer secret key from Mautic
			'callback'     => $callback   // Redirect URI/Callback URI for this script
		);

		// Initiate the auth object
		$auth = ApiAuth::initiate( $settings );

		$accessTokenData = get_option('haw_mautic_access_token_data');
		if ( isset( $accessTokenData ) && ! empty( $accessTokenData ) ) {
		    $auth->setAccessTokenDetails( json_decode( $accessTokenData, true ) );
		}

		/**
         * Initiate process for obtaining an access token;
         * this will redirect the user to the $authorizationUrl
         * and/or set the access_tokens when the user is redirected
         * back after granting authorization.
         *
         * If the access token is expired, and a refresh token is set
         * above, then a new access token will be requested.
         */

		if ( $auth->validateAccessToken() ) {

			$accessTokenData = $auth->getAccessTokenData();
			update_option( 'haw_mautic_access_token_data', json_encode( $accessTokenData ) );

			$auth->accessTokenUpdated();

			$formApi    = MauticApi::getContext( "forms", $auth, $baseUrl . '/api/' ); 

			$forms      = $formApi->getList();
			$forms      = $forms['forms'];
			
			global $wpdb;

			$mautic_forms_table         = $wpdb->prefix . 'haw_mautic_forms';
			$mautic_form_fields_table   = $wpdb->prefix . 'haw_mautic_form_fields';

			// Truncate mautic forms & fields
			$wpdb->query( 'TRUNCATE TABLE ' . $mautic_forms_table );
			$wpdb->query( 'TRUNCATE TABLE ' . $mautic_form_fields_table );

			if ( count( $forms ) > 0 ) {
				foreach ( $forms as $form ) {
					$form_id    = $form['id'];
					$data       = array(
                        'id'    => $form_id,
                        'name'  => $form['name'],
                        'alias' => $form['alias'],
                    );
					$wpdb->insert( $mautic_forms_table, $data );

					//insert form fields
					if ( count( $form['fields'] ) > 0 ) {
						foreach ( $form['fields'] as $field ) {
							$data = array(
                                'id'        => $field['id'],
                                'form_id'   => $form_id,
                                'label'     => $field['label'],
                                'alias'     => $field['alias'],
                                'type'      => $field['type'],
                            );
							$wpdb->insert( $mautic_form_fields_table, $data );
						}
					}
				}
			}
		}
		return true;
	}
	return false;
}

/**
 * Register here is the code for creating plugin option fields
 */
function register_haw_mautic_integration_settings() {
	register_setting( 'haw-mautic-integration-settings-group', 'haw_mautic_base_url' );
	register_setting( 'haw-mautic-integration-settings-group', 'haw_mautic_auth_type' );
	register_setting( 'haw-mautic-integration-settings-group', 'haw_mautic_public_key' );
	register_setting( 'haw-mautic-integration-settings-group', 'haw_mautic_secret_key' );
	register_setting( 'haw-mautic-integration-settings-group', 'haw_mautic_drop_data' );

	// Register settings for modules
	global $haw_mautic_modules;
	if ( count( $haw_mautic_modules ) > 0 ) {
		foreach ( $haw_mautic_modules as $modules ) {
			register_setting( 'haw-mautic-integration-settings-group', $modules );
		}
	}
}
add_action( 'admin_init', 'register_haw_mautic_integration_settings' );

/**
 * Update setting on dependent plugin deactivation
 * @param $plugin
 */
function haw_mautic_integration_detect_plugin_deactivation( $plugin ) {
	global $haw_mautic_modules, $haw_mautic_modules_plugin_file;

	// Disable module on their dependent plugin deactivation.
	if ( count( $haw_mautic_modules ) > 0 ) {
		foreach ( $haw_mautic_modules as $module ) {
			if ( $plugin == $haw_mautic_modules_plugin_file[ $module ] ) {
				update_option( $module, '0' );
		    }
		}
	}
}
add_action( 'deactivated_plugin', 'haw_mautic_integration_detect_plugin_deactivation', 10, 2 );

/**
 * Add new map in the system
 */
function haw_mautic_integration_add_new() {
	haw_mautic_integration_empty_settings();
	$got_forms = haw_mautic_integration_get_forms_from_server( 'haw-mautic-integration-add-new' );

	global $haw_mautic_modules, $haw_mautic_modules_name, $haw_mautic_modules_plugin_file, $wpdb;

	$mautic_forms_table = $wpdb->prefix . 'haw_mautic_forms';
	$mauticForms        = $wpdb->get_results( "SELECT id, name FROM " . $mautic_forms_table );

	if ( (count( $mauticForms ) == 0) && $got_forms ) {
		echo '<div class="error notice"><p>In doesn\'t seem you have any form in Mautic.
				Please go ahead and create the form in Mautic and then come here again to map the fields.</p></div>';
	}

	if ( isset( $_POST['submit'] ) && check_admin_referer( 'haw_mautic_integration_add_new' ) ) {
		$mautic_integration_form_table  = $wpdb->prefix . 'haw_mautic_integration_form';
		$haw_mautic_form_1_field        = $_POST['haw_mautic_form_1_field'];

		if ( count( $haw_mautic_form_1_field ) > 0 ) {
			foreach( $haw_mautic_form_1_field as $key => $field ) {

				$form_1_field = sanitize_text_field( $_POST['haw_mautic_form_1_field'][ $key ] );
				$form_2_field = intval( $_POST['haw_mautic_form_2_field'][ $key ] );

				if( ! empty( $form_1_field ) && ! empty( $form_2_field ) ) {
					$data = array(
                        'form_1_type'   => $_POST['haw_mautic_form_1_type'],
                        'form_1'        => $_POST['haw_mautic_form_1'],
                        'form_2'        => $_POST['haw_mautic_form_2'],
                        'form_1_field'  => $_POST['haw_mautic_form_1_field'][ $key ],
                        'form_2_field'  => $_POST['haw_mautic_form_2_field'][ $key ],
                    );
					$wpdb->insert( $mautic_integration_form_table, $data );
				}
			}
		}
		wp_redirect( admin_url( 'admin.php?page=haw-mautic-integration&saved=true' ) );
	}
	?>

	<div class="wrap haw_mautic_integration_add_new">
	<h1><?php esc_html_e( 'Map New Form', 'wp-mautic-form-integrator' ); ?></h1>

		<form method="post" action="">
			<?php wp_nonce_field( 'haw_mautic_integration_add_new'); ?>

			<div id="poststuff" >
				<div id="submitdiv" class="postbox">
					<table class="form-table" id="haw_mautic_add_new_map">
						<tr class="step-1">
							<td colspan="3"><h3>Step 1</h3></td>
						</tr>
						<tr valign="top" class="step-1">
							<td scope="row">Select Form Type</td>
							<td colspan="2">
								<select class="post_form" name="haw_mautic_form_1_type"
								onchange="hawMauticGetForms(this)">
									<option disabled value="" selected="selected">Select form plugin</option>
									<?php
									if ( count( $haw_mautic_modules ) > 0 ) {
										foreach ( $haw_mautic_modules as $module ) {
											if ( get_option( $module ) && is_plugin_active( $haw_mautic_modules_plugin_file[ $module ] ) ) {
												echo '<option value="' . esc_attr( $module ) . '">' . esc_html( $haw_mautic_modules_name[ $module ] )
												. '</option>';
											}
										}
									}
									?>
								</select>
								<span class="haw-error"></span>
							</td>
						</tr>
						<tr class="step-2 hidden">
							<td colspan="3"><hr class="top"><h3>Step 2</h3></td>
						</tr>
						<tr valign="top" class="step-2 hidden">
							<td scope="row">Select Form</td>
							<td>
								<p class="description" id="haw-form-1-name"></p>
								<select class="post_form" name="haw_mautic_form_1" onchange="hawMauticGetForm1Fields(this)">
									<option disabled value="" selected="selected">Select Form 1</option>
								</select>
								<span class="haw-error"></span>
							</td>
							<td>
								<p class="description">Mautic Form</p>
								<select class="post_form" name="haw_mautic_form_2" onchange="hawMauticGetForm2Fields(this)">
									<option disabled value="" selected="selected">Select Mautic Form</option>
									<?php
									if ( count( $mauticForms ) > 0 ) {
										foreach ( $mauticForms as $form ) {
											echo '<option value="' . esc_attr( $form->id ) . '">' . esc_html( $form->name ) . '</option>';
										}
									}
									?>
								</select>
								<span class="haw-error"></span>
							</td>
						</tr>
						<tr class="step-3 hidden">
							<td colspan="3"><hr class="top"><h3>Step 3</h3><br>Select form field to map form fields</td>
						</tr>
						<tr class="step-3 hidden">
							<td scope="row"><a onclick="hawMauticAddNew(this)"><i class="dashicons dashicons-plus-alt"></i> New Field</a></td>
							<td>
								<select class="post_form haw_mautic_form_1_field" name="haw_mautic_form_1_field[]" >
									<option disabled value="" selected="selected">Select Field 1</option>
								</select>
							</td>
							<td>
								<select class="post_form haw_mautic_form_2_field" name="haw_mautic_form_2_field[]" >
									<option disabled value="" selected="selected">Select Field 1</option>
								</select>
							</td>
						</tr>
					</table>
					<div class="inside">
						<div class="submitbox">
							<div id="major-publishing-actions">
								<div id="publishing-action">
									<span class="spinner"></span>
									<input type="submit" name="submit" id="submit" class="button button-primary hidden" value="Finish"  />
									<input type="button" class="button button-primary" value="Next Step" onclick="hawMauticNextStep(this, 1)">
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</form>
	<?php
}

/**
 * Edit page for Mautic form map
 */
function haw_mautic_integration_edit() {
	global $wpdb, $haw_mautic_modules, $haw_mautic_modules_name, $haw_mautic_modules_plugin_file;

	$form_type  = sanitize_text_field( $_GET['type'] );
	$form_1     = intval( $_GET['form_1'] );
	$form_2     = intval( $_GET['form_2'] );

	if ( count( $haw_mautic_modules ) > 0 ) {
		foreach ( $haw_mautic_modules as $module ) {
			if ( $form_type == $module ) {
				if ( ! is_plugin_active( $haw_mautic_modules_plugin_file[ $module ] ) ) {
		            echo '<div class="error notice"><p>Please install & activate the plugin ' . esc_html( $haw_mautic_modules_name[ $module ] )
		                . '.</p></div>';
					exit();
		        }

		        if ( ! get_option( $module ) ) {
		            echo '<div class="error notice"><p>In order to map the fields, please first enable
						' . esc_html( $haw_mautic_modules_name[ $module ] ) . ' in the settings page <a href="' .
						admin_url('admin.php?page=haw-mautic-integration-setting') . '">here</a>.</p></div>';
					exit();
		        }
	        }
		}
	}

	haw_mautic_integration_empty_settings();

	$got_forms = haw_mautic_integration_get_forms_from_server( 'haw-mautic-integration&action=edit&form_1=' . $form_1 .
																'&form_2=' . $form_2 . '&type=' . $form_type );

	//get map fields
	$mautic_integration_form_table  = $wpdb->prefix . 'haw_mautic_integration_form';
	$sql                            = "SELECT form_1_field,form_2_field ";
	$sql                            .= " FROM " . $mautic_integration_form_table;
	$sql                            .= " WHERE	form_1=%d AND form_2=%d AND form_1_type=%s";
	$mapFormFields                  = $wpdb->get_results( $wpdb->prepare( $sql, $form_1, $form_2, $form_type ) );

	//form1 fields
	$form1Fields                    = array();
	$form1Fields                    = apply_filters( 'haw_mautic_get_form_fields', $form1Fields, $form_type, $form_1 );

	//form2 fields
	$mautic_form_fields_table       = $wpdb->prefix . 'haw_mautic_form_fields';
	$mauticFormFields               = $wpdb->get_results(
											$wpdb->prepare(
												"SELECT id, label FROM " . $mautic_form_fields_table . " WHERE form_id=%d",
												$form_2
											)
                                      );

	$mautic_forms_table             = $wpdb->prefix . 'haw_mautic_forms';
	$form_2_title                   = $wpdb->get_var(
											$wpdb->prepare(
                                                "SELECT name FROM ".$mautic_forms_table." WHERE id=%d",
                                                $form_2
                                            )
                                        );

	if ( empty( $form_2_title ) && $got_forms ) {
		echo '<div class="error notice"><p>The associated Mautic form was not found.</p></div>';
	}

	if ( isset( $_POST['submit'] ) && check_admin_referer( 'haw_mautic_integration_edit' ) ) {

		// Delete old form field list
		$wpdb->query(
            $wpdb->prepare(
                "DELETE FROM ".$mautic_integration_form_table." WHERE form_1 =%d AND form_2=%d AND form_1_type=%s",
                $form_1,
                $form_2,
                $form_type
            )
        );

		$mautic_integration_form_table  = $wpdb->prefix . 'haw_mautic_integration_form';
		$haw_mautic_form_1_field        = $_POST['haw_mautic_form_1_field'];
		if ( count( $haw_mautic_form_1_field ) > 0 ) {
			foreach ( $haw_mautic_form_1_field as $key => $field ) {

				$form_1_field = sanitize_text_field( $_POST['haw_mautic_form_1_field'][ $key ] );
				$form_2_field = intval( $_POST['haw_mautic_form_2_field'][ $key ] );

				if( ! empty( $form_1_field ) && ! empty( $form_2_field ) ) {
					$data = array(
                        'form_1_type'   => $_POST['haw_mautic_form_1_type'],
                        'form_1'        => $_POST['haw_mautic_form_1'],
                        'form_2'        => $_POST['haw_mautic_form_2'],
                        'form_1_field'  => $_POST['haw_mautic_form_1_field'][ $key ],
                        'form_2_field'  => $_POST['haw_mautic_form_2_field'][ $key ],
                    );
					$wpdb->insert( $mautic_integration_form_table, $data );
				}
			}
		}
		wp_redirect( admin_url( 'admin.php?page=haw-mautic-integration&updated=true' ) );
	}
	?>

	<div class="wrap haw_mautic_integration_add_new">
	<h1>
		<?php esc_html_e( 'Edit Form Map', 'wp-mautic-form-integrator' ); ?>
		<a class="add-new-h2" href="<?php echo admin_url( 'admin.php?page=haw-mautic-integration-add-new' ) ; ?>">Add New</a>
	</h1>

		<form method="post" action="">
			<?php wp_nonce_field( 'haw_mautic_integration_edit' ); ?>

			<div id="poststuff" >
				<div id="submitdiv" class="postbox">
					<table class="form-table" id="haw_mautic_add_new_map">
						<tr class="step-1"><td colspan="3"><h3>Step 1</h3></td></tr>
						<tr valign="top" class="step-1">
							<td scope="row">Select Form Type</td>
							<td colspan="2">
								<input type="hidden" name="haw_mautic_form_1_type" value="<?php echo esc_attr( $form_type ); ?>">
								<select class="post_form" disabled>
									<option><?php echo esc_html( $haw_mautic_modules_name[ $form_type ] ); ?></option>
								</select>
							</td>
						</tr>
						<tr class="step-2 "><td colspan="3"><hr class="top"><h3>Step 2</h3></td></tr>
						<tr valign="top" class="step-2">
							<td scope="row">Select Form</td>
							<td>
								<?php
								$form_title = '';
								$form_title = apply_filters( 'haw_mautic_get_form_title', $form_title, $form_type, $form_1 );
								?>
								<p class="description" id="haw-form-1-name"><?php echo esc_html( $haw_mautic_modules_name[ $form_type ] ); ?></p>
								<input type="hidden" name="haw_mautic_form_1" value="<?php echo esc_attr( $form_1 ); ?>">
								<select class="post_form" disabled>
									<option><?php echo esc_html( $form_title ); ?></option>
								</select>
							</td>
							<td>
								<p class="description">Mautic Form</p>
								<input type="hidden" name="haw_mautic_form_2" value="<?php echo esc_attr( $form_2 ); ?>">
								<select class="post_form" disabled>
									<option><?php echo esc_html( $form_2_title ); ?></option>
								</select>
							</td>
						</tr>
						<tr class="step-3">
							<td colspan="3"><hr class="top"><h3>Step 3</h3><br>Select form field to map form fields</td>
						</tr>
						<?php
						if ( count( $mapFormFields ) > 0 ) {
							foreach ( $mapFormFields as $key => $mapFormField ) {
								echo '<tr>';
								if ( $key == 0 ) {
									echo '<td scope="row"><a onclick="hawMauticAddNew(this)">
									<i class="dashicons dashicons-plus-alt"></i> New Field</a></td>';
								} else {
									echo '<td scope="row"><a onclick="hawMauticAddNew(this)"><i class="dashicons dashicons-plus-alt"></i> New Field</a>
											<a onclick="hawMauticRemoveMapField(this)">
											<i class="dashicons dashicons-trash"></i> Remove Field</a></td>';
								}
								echo '<td>';
								echo '<select class="post_form haw_mautic_form_1_field" name="haw_mautic_form_1_field[]" >';
								echo '<option disabled value="" selected="selected">Select Field</option>';

								if ( count( $form1Fields ) > 0 ) {
									foreach ( $form1Fields as $field ) {
										$selected = ( $field['id'] == $mapFormField->form_1_field )? 'selected="selected"' : '';
										echo '<option value="' . esc_attr( $field['id'] ) . '" ' . $selected . '>' .
										esc_html( $field['label'] ) . '</option>';
									}
								}
								echo '</select></td>';
								echo '<td>';
								echo '<select class="post_form haw_mautic_form_2_field" name="haw_mautic_form_2_field[]" >';
								echo '<option disabled value="" selected="selected">Select Field</option>';
								if ( count( $mauticFormFields ) > 0 ) {
									foreach ( $mauticFormFields as $field ) {
										$selected = ( $field->id == $mapFormField->form_2_field )? 'selected="selected"' : '';
										echo '<option value="' . esc_attr( $field->id ) . '" ' . $selected . '>' . esc_html( $field->label ) . '</option>';
									}
								}
								echo '</select></td>';
								echo '</tr>';
							}
						}
						?>
					</table>
					<div class="inside">
						<div class="submitbox">
							<div id="major-publishing-actions">
								<div id="publishing-action">
									<span class="spinner"></span>
									<input type="submit" name="submit" id="submit" class="button button-primary" value="Update"  />
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	<?php
}

/**
 * Delete Mautic form map from database.
 */
function haw_mautic_integration_delete() {
	$redirect = admin_url('admin.php?page=haw-mautic-integration');

	global $wpdb;
	$mautic_integration_form_table = $wpdb->prefix . 'haw_mautic_integration_form';

	$form_type  = sanitize_text_field( $_GET['type'] );
	$form_1     = intval( $_GET['form_1'] );
	$form_2     = intval( $_GET['form_2'] );

	if( ! empty( $form_type ) && ! empty( $form_1 ) && ! empty( $form_2 ) ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM ".$mautic_integration_form_table." WHERE form_1=%d AND form_2=%d AND form_1_type=%s",
				 $form_1,
				 $form_2,
				 $form_type
             )
        );
		$redirect = admin_url( 'admin.php?page=haw-mautic-integration&deleted=true' );
	}
	wp_redirect($redirect);
}

/**
 * Ajax to get forms.
 */
function haw_mautic_get_forms() {
	$form_type = sanitize_text_field( $_POST['form_type'] );

	//Filter to get form list from modules
	$result = array();
	$result = apply_filters( 'haw_mautic_get_form_list', $result, $form_type );
	echo json_encode( $result );
	exit();
}
add_action( 'wp_ajax_haw_mautic_get_forms', 'haw_mautic_get_forms' );

/**
 * Ajax to get form fields.
 */
function haw_mautic_get_form_1_fields() {
	$form_type  = sanitize_text_field( $_POST['form_type'] );
	$formID     = intval( $_POST['form_1'] );

	//Filter to get form fields from modules
	$result     = array();
	$result     = apply_filters( 'haw_mautic_get_form_fields', $result, $form_type, $formID );

	echo json_encode( $result );
	exit();
}
add_action( 'wp_ajax_haw_mautic_get_form_1_fields', 'haw_mautic_get_form_1_fields' );

/**
 * Ajax to get Mautic form fields.
 */
function haw_mautic_get_form_2_fields() {
	$formID = intval( $_POST['form_2'] );

	if ( $formID != '' ) {
		$result = array();

		global $wpdb;
		$mautic_form_fields_table   = $wpdb->prefix . 'haw_mautic_form_fields';
		$sql                        = "SELECT id, label ";
		$sql                        .= " FROM " . $mautic_form_fields_table . " WHERE form_id=%d";
		$mauticFormFields           = $wpdb->get_results( $wpdb->prepare( $sql, $formID ) );

		if ( count( $mauticFormFields ) > 0 ) {
			foreach ( $mauticFormFields as $field ) {
				$result[] = array( 'id' => $field->id, 'label' => $field->label );
			}
		}
		echo json_encode( $result );
	}
	exit();
}
add_action( 'wp_ajax_haw_mautic_get_form_2_fields', 'haw_mautic_get_form_2_fields' );

/**
 * Action to push data on mautic it used by modules to push data.
 *
 * @param $form_type
 * @param $formID
 * @param $data
 */
function haw_mautic_integration_push_mautic_form( $form_type, $formID, $data ) {
	global $wpdb;
	$mautic_integration_form_table  = $wpdb->prefix . 'haw_mautic_integration_form';
	$mautic_form_fields_table       = $wpdb->prefix . 'haw_mautic_form_fields';

	//get map form fields
	$sql    = "SELECT mif.form_1_field,mif.form_2,mff.alias ";
	$sql    .= " FROM " . $mautic_integration_form_table . " as mif
			LEFT JOIN " . $mautic_form_fields_table . " as mff ON mff.id=mif.form_2_field
			WHERE form_1=%d
			AND form_1_type=%s";
	$fields = $wpdb->get_results( $wpdb->prepare( $sql, $formID , $form_type ) );

	if ( count( $fields ) > 0 ) {
		$query = $mauticFormIDs = array();
		foreach ( $fields as $field ) {
			$query[ $field->alias ] = is_array($data[ $field->form_1_field ])?
											$data[ $field->form_1_field ] :
											sanitize_text_field( $data[ $field->form_1_field ] );
			if ( ! in_array( $field->form_2, $mauticFormIDs ) ) {
				$mauticFormIDs[] = $field->form_2;
			}
		}

		if ( count( $mauticFormIDs ) > 0 ) {
			foreach ( $mauticFormIDs as $mauticFormID ) {
				_push_mautic_form( $query, $mauticFormID ); // Post data, formId
			}
		}
	}
}
add_action( 'haw_mautic_push_data_to_mautic', 'haw_mautic_integration_push_mautic_form', 10, 3 );

/**
 * Push data to a Mautic form
 * @param $query
 * @param $formId
 */
function _push_mautic_form( $query, $formId )
{
	if( is_array( $query ) && ! empty( $query ) && is_numeric( $formId ) ) {
		$ip                 = _get_ip();
		$query['return']    = get_home_url();
		$query['formId']    = $formId;

		$data = array(
            'mauticform' => $query,
        );

		$MauticBaseURL = get_option( 'haw_mautic_base_url' );
	
		if ( $MauticBaseURL ) {
			$url = $MauticBaseURL . "/form/submit?formId=" . $formId;
			$response = wp_remote_post(
				$url,
				array(
					'method'    => 'POST',
					'timeout'   => 45,
					'headers'   => array(
						'X-Forwarded-For' => $ip
					),
					'body'      => $data,
					'cookies'   => $_COOKIE
				)
			);

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				error_log( "Gform_Mautic Error: $error_message" );
				error_log( "posted url: $url" );
			}
		}
	}
}

/**
 * Get User's IP
 * @return mixed|string|void
 */
function _get_ip()
{
	$ip = '';
	$ip_list = [
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED'
	];
	foreach ( $ip_list as $key ) {
		if ( ! isset( $_SERVER[ $key ] ) ) {
			continue;
		}
		$ip = esc_attr( $_SERVER[ $key ] );
		if ( ! strpos( $ip, ',' ) ) {
			$ips =  explode( ',', $ip );
			foreach ( $ips as &$val ) {
				$val = trim( $val );
			}
			$ip = end ( $ips );
		}
		$ip = trim( $ip );
		break;
	}
	return $ip;
}

/**
 * Error notice id API details are empty.
 */
function haw_mautic_integration_empty_settings() {
	$baseUrl    = get_option( 'haw_mautic_base_url' );
	$version    = get_option( 'haw_mautic_auth_type' );
	$publicKey  = get_option( 'haw_mautic_public_key' );
	$secretKey  = get_option( 'haw_mautic_secret_key' );

	if ( empty( $baseUrl ) || empty( $version ) || empty( $publicKey ) || empty( $secretKey ) ) {
		echo '<div class="error notice">
				<p>Please enter the Mautic API details <a href="' . admin_url( 'admin.php?page=haw-mautic-integration-setting' ) . '">here</a>
				in order to use this plugin.</p>
			 </div>';
	} else {
		global $haw_mautic_add_new_page, $haw_mautic_modules;
	    $screen = get_current_screen();

	    if ( count( $haw_mautic_modules ) > 0 && $screen->id == $haw_mautic_add_new_page ) {
	        $show_notice = true;
	        foreach ( $haw_mautic_modules as $module ) {
	            if ( get_option( $module ) ) {
	                $show_notice = false;
	            }
	        }
	        if ( $show_notice ) {
	            echo '<div class="error notice"><p>Please visit the settings page
				<a href="'.admin_url('admin.php?page=haw-mautic-integration-setting').'">here</a> and enable the form
				builder plugin(s) which you want to map to Mautic.</p></div>';
	        }
	    }
	}
}

function haw_mautic_integration_notice__error() {
	global $haw_mautic_modules, $haw_mautic_modules_name, $haw_mautic_modules_plugin_file;
	if ( count( $haw_mautic_modules ) > 0 ) {
		foreach( $haw_mautic_modules as $module ) {
			if ( get_option( $module ) && ! is_plugin_active( $haw_mautic_modules_plugin_file[ $module ] ) ) {
				 echo '<div class="error notice"><p>' . $haw_mautic_modules_name[ $module ].
				 ' is enabled for the Mautic integration, but the associated form builder plugin in either not
				 installed or not activated. Please install or activate the plugin first.</p></div>';
			}
		}
	}
}
add_action( 'admin_notices', 'haw_mautic_integration_notice__error' );

/**
 * Display listing page of Mautic Integration
 */
function haw_mautic_integration_list() {
	$action = isset( $_GET['action'] )? $_GET['action'] : '';
	if ( $action == 'edit' ) {
		// Form map edit page
		haw_mautic_integration_edit();
	} elseif ( $action == 'delete' ) {
		// Delete form map
		haw_mautic_integration_delete();
	} else {
		global $haw_mautic_modules_plugin_file;
		haw_mautic_integration_no_plugin_activated($haw_mautic_modules_plugin_file);

		haw_mautic_integration_empty_settings();

		$listTable = new HAW_MAUTIC_INTEGRATION_TABLE();
		$listTable->prepare_items();
		?>
		<?php if ( isset( $_GET['updated'] ) ) { ?>
		    <div id="message" class="updated is-dismissible">
		        <p>Map integrations updated successfully.</p>
		    </div>
		<?php } elseif ( isset( $_GET['saved'] ) ) { ?>
		    <div id="message" class="updated is-dismissible">
		        <p>Map integrations added successfully.</p>
		    </div>
		<?php } elseif ( isset( $_GET['deleted'] ) ) { ?>
		    <div id="message" class="updated is-dismissible">
		        <p>Map integrations deleted successfully.</p>
		    </div>
		<?php } ?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'WP Mautic Form Integrator', 'wp-mautic-form-integrator' ); ?>
				<a class="add-new-h2" href="<?php echo admin_url( 'admin.php?page=haw-mautic-integration-add-new' ); ?>">Add New</a>
			</h1>
			<form action="" method="post">
			<?php $listTable->display(); ?>
			</form>
		</div>
		<?php
	}
}

/**
 * Check if any dependent plugin is activated or not.
 *
 * @param $haw_mautic_modules_plugin_file
 */
function haw_mautic_integration_no_plugin_activated($haw_mautic_modules_plugin_file)
{
	if ( count( $haw_mautic_modules_plugin_file ) > 0 ) {
        $show_notice = true;
        foreach ( $haw_mautic_modules_plugin_file as $module ) {
            if ( is_plugin_active( $module ) ) {
                $show_notice = false;
            }
        }
        if ( $show_notice ) {
            echo '<div class="error notice">
				<p>
				This plugin requires one or more form builder plugins listed below. <br>
				- <a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a><br>
				- <a href="https://wordpress.org/plugins/ninja-forms/" target="_blank">Ninja Forms</a><br>
				- <a href="https://wordpress.org/plugins/formidable/" target="_blank">Formidable Forms</a><br>
				- <a href="https://wordpress.org/plugins/si-contact-form/" target="_blank">Fast Secure Contact Form</a><br>
				- <a href="http://www.gravityforms.com/" target="_blank">Gravity Forms</a>
				</p>
				</div>';
        }
    }
}


// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class HAW_MAUTIC_INTEGRATION_TABLE extends WP_List_Table {
	function prepare_items() {
		  $columns      = $this->get_columns();
		  $hidden       = array();
		  $sortable     = array();
		  $this->_column_headers = array( $columns, $hidden, $sortable );
		  $this->process_bulk_action();
		  $items        = $this->table_data();

		  $per_page     = 10;
		  $current_page = $this->get_pagenum();
		  $total_items  = count($items);

		  // only necessary because we have sample data
		  $data = array_slice( $items, (( $current_page - 1 ) * $per_page ), $per_page );

		  $this->set_pagination_args( array(
		    'total_items' => $total_items,                  //WE have to calculate the total number of items
		    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		  ) );
		  $this->items  = $data;
	}

	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'form_1'        => 'Form',
			'form_1_type'   => 'Form Type',
			'name'          => 'Mautic Form',
			'field_count'   => 'Fields Mapped',
		);

		return $columns;
	}

	function table_data() {
		global $wpdb, $haw_mautic_modules_plugin_file;
		$mautic_integration_form_table = $wpdb->prefix . 'haw_mautic_integration_form';
		$mautic_forms_table = $wpdb->prefix . 'haw_mautic_forms';

		$sql = 'SELECT mif.*,mf.name, count(mif.id) as field_count ';
		$sql .= ' FROM ' . $mautic_integration_form_table . ' as mif LEFT JOIN ' . $mautic_forms_table . ' as mf ON mf.id=mif.form_2 ';
 		$sql .= ' WHERE mif.id!=0 AND ( ';

	    if ( count( $haw_mautic_modules_plugin_file ) > 0 ) {
	        foreach ( $haw_mautic_modules_plugin_file as $module => $module_file ) {
	            if ( is_plugin_active( $module_file ) && get_option( $module ) ) {
	                $sql .= ' mif.form_1_type="' . esc_sql( $module ). '" OR ';
	            }
	        }
	    }

 		$sql .= ' mif.form_1_type="" )';
 		$sql .= ' GROUP BY mif.form_1, mif.form_2, mif.form_1_type ORDER BY id DESC';
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
 		return $result;
	}

	function column_default( $item, $column_name ) {
		global $haw_mautic_modules_name;
		switch( $column_name ) {
		case 'form_1':
		case 'form_1_type':
			return $haw_mautic_modules_name[ $item[ $column_name ] ];
			break;
		case 'name':
			return $item[ $column_name ];
			break;
		case 'field_count':
			return $item[ $column_name ];
			break;
		default:
		  return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}

	function column_form_1( $item ) {
		$onclick = 'if (confirm(\'Please confirm to delete.\')) {return true;} return false;';
        $actions = array(
            'edit'      => sprintf(
                '<a href="?page=%s&action=%s&form_1=%d&form_2=%d&type=%s">Edit</a>',
                'haw-mautic-integration',
                'edit',
                $item['form_1'],
                $item['form_2'],
                $item['form_1_type']
            ),
            'delete'    => sprintf(
                '<a href="?page=%s&action=%s&form_1=%d&form_2=%d&type=%s" onclick="' . $onclick . '">Delete</a>',
                'haw-mautic-integration',
                'delete',
                $item['form_1'],
                $item['form_2'],
                $item['form_1_type']
            ),
        );

		// Filter to get Form title from Modules
		$form_title = '';
		$form_title = apply_filters( 'haw_mautic_get_form_title', $form_title, $item['form_1_type'], $item['form_1'] );

        return sprintf(
            '%1$s <span style="color:silver ; display : none;">(id:%2$s)</span>%3$s',
            $form_title,
            $item['id'],
            $this->row_actions($actions)
        );
    }

	function get_bulk_actions() {
		$actions = array(
		    'delete'    => 'Delete'
		);
		return $actions;
	}

	function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="forms[]" value="%s" />', $item['form_1'] . '-' . $item['form_1_type']
        );
    }

	function process_bulk_action() {
	   if ( 'delete' === $this->current_action() ) {
			//  wp_die('Items deleted (or they would be if we had items to delete)!');
			if ( count( $_POST['forms'] ) > 0 ) {
				foreach ( $_POST['forms'] as $id ) {
					global $wpdb;
					$mautic_integration_form_table = $wpdb->prefix . 'haw_mautic_integration_form';
					$splitVal = explode( '-', $id );
					if ( isset( $splitVal['0'] ) && isset( $splitVal['1'] ) ) {
						$sql = "DELETE ";
						$sql .= " FROM " . $mautic_integration_form_table;
						$sql .= " WHERE form_1 =%d AND form_1_type=%s ";
						$wpdb->query( $wpdb->prepare( $sql, $splitVal['0'], $splitVal['1'] ) );
					}
				}
			}

			wp_redirect( esc_url( add_query_arg() ) );
			exit;
        }
	}

	function no_items() {
	  _e( 'No form map integration found.' );
	}
}