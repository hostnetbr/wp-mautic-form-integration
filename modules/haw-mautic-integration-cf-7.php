<?php
/**
 * Define Constants
 */
define( 'HAW_MAUTIC_CF_7_FORM', 'haw_mautic_cf_7' );
$haw_mautic_modules[]                                   = HAW_MAUTIC_CF_7_FORM;
$haw_mautic_modules_name[ HAW_MAUTIC_CF_7_FORM ]        = 'Contact Form 7';
$haw_mautic_modules_plugin_file[ HAW_MAUTIC_CF_7_FORM ] = 'contact-form-7/wp-contact-form-7.php';

/**
 * Action to get form list of Contact form 7.
 *
 * @param $result
 * @param $form_type
 *
 * @return array of id & label
 */
function haw_mautic_cf_7_form_list( $result, $form_type ) {
	if ( $form_type == HAW_MAUTIC_CF_7_FORM ) {
		$args = array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1 );
		if ( $cf7Forms = get_posts( $args ) ) {
			foreach ( $cf7Forms as $cf7Form ) {
				$result[] = array( 'id' => $cf7Form->ID, 'label' => $cf7Form->post_title );
			}
		}
	}
	return $result;
}
add_filter( 'haw_mautic_get_form_list', 'haw_mautic_cf_7_form_list', 10, 2 );

/**
 * Get form fields.
 *
 * @param $result
 * @param $form_type
 * @param $formID
 *
 * @return array of id & label
 */
function haw_mautic_cf_7_form_fields( $result, $form_type, $formID ) {
	if ( $form_type == HAW_MAUTIC_CF_7_FORM ) {
		$args = get_post_meta( $formID, '_form', 'true' );
		preg_match_all( '/\[([^\]]*)\]/', $args, $matches );
		if ( isset( $matches['1'] ) ) {
			foreach ( $matches['1'] as $fields ) {
				$split      = explode( ' ', $fields );
				$result[]   = array( 'id' => $split[1], 'label' => $fields );
			}
		}
	}
	return $result;
}
add_filter( 'haw_mautic_get_form_fields', 'haw_mautic_cf_7_form_fields', 10, 3 );

/**
 * Get form title.
 *
 * @param $form_title
 * @param $form_type
 * @param $formID
 *
 * @return string
 */
function haw_mautic_cf_7_form_title( $form_title, $form_type, $formID ) {
	if ( $form_type == HAW_MAUTIC_CF_7_FORM ) {
		$form_title =  get_the_title( $formID );
	}
	return $form_title;
}
add_filter( 'haw_mautic_get_form_title', 'haw_mautic_cf_7_form_title', 10, 3 );

/**
 * Hook to push data on mautic.
 *
 * @param $cf7
 */
function haw_mautic_integration_cf7_hook( $cf7 ) {
	if ( get_option( HAW_MAUTIC_CF_7_FORM ) ) {
		$submission = WPCF7_Submission::get_instance();
		if ( $submission ) {
			$formID         = $cf7->id();
			$posted_data    = $submission->get_posted_data();
			do_action( 'haw_mautic_push_data_to_mautic', HAW_MAUTIC_CF_7_FORM, $formID, $posted_data );
		}
	}
}
//Manage: Call back on after mail sent
add_action( 'wpcf7_mail_sent', 'haw_mautic_integration_cf7_hook' );
//add_action( 'wpcf7_before_send_mail', 'haw_mautic_integration_cf7_hook' );