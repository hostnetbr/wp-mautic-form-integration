<?php
/**
 * Define Constants
 */
define( 'HAW_MAUTIC_SI_CONTACT_FORM', 'haw_mautic_si_contact_form' );
$haw_mautic_modules[] 											= HAW_MAUTIC_SI_CONTACT_FORM;
$haw_mautic_modules_name[HAW_MAUTIC_SI_CONTACT_FORM] 			= 'Fast Secure Contact Form';
$haw_mautic_modules_plugin_file[HAW_MAUTIC_SI_CONTACT_FORM] 	= 'si-contact-form/si-contact-form.php';

/**
 * Action to get form list.
 *
 * @param $result
 * @param $form_type
 *
 * @return array of id & label
 */
function haw_mautic_si_contact_form_list( $result, $form_type ) {
	if ( $form_type == HAW_MAUTIC_SI_CONTACT_FORM ) {
		$glob_options = FSCF_Util::get_global_options();
		if ( isset( $glob_options['form_list'] ) and ! empty( $glob_options['form_list'] ) ) {
			foreach ( $glob_options['form_list'] as $key => $value ) {
				$result[] = array( 'id'=> $key, 'label' => $value );
			}
		}
	}
	return $result;
}
add_filter( 'haw_mautic_get_form_list', 'haw_mautic_si_contact_form_list', 10, 2 );

/**
 * Get form fields.
 *
 * @param $result
 * @param $form_type
 * @param $formID
 *
 * @return array of id & label
 */
function haw_mautic_si_contact_form_fields( $result, $form_type, $formID ) {
	if ( $form_type == HAW_MAUTIC_SI_CONTACT_FORM ) {
		$form_options = get_option( 'fs_contact_form' . $formID );
		if ( isset( $form_options['fields'] ) and ! empty( $form_options['fields'] ) ) {
			foreach ( $form_options['fields'] as $key => $row ) {
				$result[] = array( 'id' => $row['slug'], 'label' => $row['type'].' '.$row['slug'] );
			}
		}
	}
	return $result;
}
add_filter( 'haw_mautic_get_form_fields', 'haw_mautic_si_contact_form_fields', 10, 3 );

/**
 * Get form title.
 *
 * @param $form_title
 * @param $form_type
 * @param $formID
 *
 * @return
 */
function haw_mautic_si_contact_form_title( $form_title, $form_type, $formID ) {
	if( $form_type == HAW_MAUTIC_SI_CONTACT_FORM ) {
		$form_options 	= get_option( 'fs_contact_form' . $formID );
		$form_title 	= $form_options['form_name'];
	}
	return $form_title;
}
add_filter( 'haw_mautic_get_form_title', 'haw_mautic_si_contact_form_title', 10, 3 );

/**
 * Hook to push data on mautic.
 */
function haw_mautic_integration_si_hook() {
	if( get_option( HAW_MAUTIC_SI_CONTACT_FORM ) ) {
		$data 		= $_POST;
		$formID 	= sanitize_text_field( $data['form_id'] );
		
		do_action( 'haw_mautic_push_data_to_mautic', HAW_MAUTIC_SI_CONTACT_FORM, $formID, $data );
	}
}
add_action( 'fsctf_mail_sent', 'haw_mautic_integration_si_hook' );