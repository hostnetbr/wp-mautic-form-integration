<?php
// die if not uninstalling
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

if ( get_option( 'haw_mautic_drop_data' ) ) {

	global $haw_mautic_modules, $wpdb;

	/**
	 * Include all of the form integration files
	 */
	foreach ( glob( dirname( __FILE__ ) . "/modules/*.php" ) as $filename ) {
		require_once $filename;
	}

	//Delete plugin options
	delete_option( 'haw_mautic_base_url' );
	delete_option( 'haw_mautic_auth_type' );
	delete_option( 'haw_mautic_public_key' );
	delete_option( 'haw_mautic_secret_key' );
	delete_option( 'haw_mautic_drop_data' );
	delete_option( 'haw_mautic_access_token_data' );

	//Delete modules options
	if ( count( $haw_mautic_modules ) > 0 ) {
		foreach ( $haw_mautic_modules as $module ) {
			delete_option( $module );
		}
	}

	//Drop plugin tables
	$mautic_forms               = $wpdb->prefix . 'haw_mautic_forms';
	$mautic_form_fields         = $wpdb->prefix . 'haw_mautic_form_fields';
	$mautic_integration_form    = $wpdb->prefix . 'haw_mautic_integration_form';

	$wpdb->query( "DROP TABLE IF EXISTS " . $mautic_forms );
	$wpdb->query( "DROP TABLE IF EXISTS " . $mautic_form_fields );
	$wpdb->query( "DROP TABLE IF EXISTS " . $mautic_integration_form );

}