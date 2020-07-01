<?php
/**
 * Define Constants
 */
define( 'HAW_MAUTIC_GRAVITY_FORM', 'haw_mautic_gravityforms' );
$haw_mautic_modules[]                                       = HAW_MAUTIC_GRAVITY_FORM;
$haw_mautic_modules_name[ HAW_MAUTIC_GRAVITY_FORM ]         = 'Gravity Forms';
$haw_mautic_modules_plugin_file[ HAW_MAUTIC_GRAVITY_FORM ]  = 'gravityforms/gravityforms.php';

/**
 * Action to get form list of Gravity Form.
 *
 * @param $result
 * @param $form_type
 *
 * @return array of id & label
 */
function haw_mautic_gravity_form_list( $result, $form_type ) {
	if ( $form_type == HAW_MAUTIC_GRAVITY_FORM ) {
		$forms = RGFormsModel::get_forms( null, 'title' );
		foreach ( $forms as $form ) {
			$result[] = array( 'id' => $form->id, 'label' => $form->title );
		}
	}
	return $result;
}
add_filter( 'haw_mautic_get_form_list', 'haw_mautic_gravity_form_list', 10, 2 );

/**
 * Get form fields.
 *
 * @param $result
 * @param $form_type
 * @param $formID
 *
 * @return array of id & label
 */
function haw_mautic_gravity_form_fields( $result, $form_type, $formID ) {
	if ( $form_type == HAW_MAUTIC_GRAVITY_FORM ) {
		$form = RGFormsModel::get_form_meta( $formID );
		if ( is_array( $form["fields"] ) ) {
			foreach ( $form["fields"] as $field ) {
				if ( isset( $field["inputs"] ) && is_array( $field["inputs"] ) ) {
					foreach ( $field["inputs"] as $input ) {
						if ( ! ( isset( $input['isHidden'] ) && $input['isHidden'] ) ) {
							$label = GFCommon::get_label( $field, $input["id"] );
							if ( empty( $label ) ) {
								$label = $input['placeholder'];
							}
							$result[] = array( 'id' => $input["id"], 'label' => $label );
						}
					}
				} elseif ( ! rgar( $field, 'displayOnly' ) ) {
					$label = GFCommon::get_label( $field );
					if ( empty( $label ) ) {
						$label = $field['placeholder'];
					}
					$result[] = array( 'id' => $field["id"], 'label' => $label );
				}
			}
		}
	}
	return $result;
}
add_filter( 'haw_mautic_get_form_fields','haw_mautic_gravity_form_fields', 10, 3 );

/**
 * Get form title.
 *
 * @param $form_title
 * @param $form_type
 * @param $formID
 *
 * @return string
 */
function haw_mautic_gravity_form_title( $form_title, $form_type, $formID ) {
	if ( $form_type == HAW_MAUTIC_GRAVITY_FORM ) {
		$forms      = RGFormsModel::get_form( $formID );
		$form_title = isset( $forms->title )? $forms->title : '';
	}
	return $form_title;
}
add_filter( 'haw_mautic_get_form_title', 'haw_mautic_gravity_form_title', 10, 3 );

/**
 * Hook to push data on mautic.
 *
 * @param $entry
 * @param $form
 */
function haw_mautic_integration_gravity_form_hook( $entry, $form ) {
	if ( get_option( HAW_MAUTIC_GRAVITY_FORM ) ) {
		$formID     = $form['id'];
		$data       = array();
		if ( isset( $form['fields'] ) && count( $form['fields'] ) > 0 ) {
			foreach ( $form["fields"] as $field ) {
				if ( isset( $field["inputs"] ) && is_array( $field["inputs"] ) ) {
					foreach ( $field["inputs"] as $input ) {
						if ( ! ( isset( $input['isHidden'] ) && $input['isHidden'] ) ) {
							$data[ $input['id'] ] = rgar( $entry, $input['id'] );
						}
					}
				} elseif ( ! rgar( $field, 'displayOnly' ) ) {
					$data[ $field['id'] ] = rgar( $entry, $field['id'] );
				}
			}
			foreach ( $form['fields'] as $field ) {
				$data[ $field['id'] ] = rgar( $entry, $field['id'] );
			}
		}
		do_action( 'haw_mautic_push_data_to_mautic', HAW_MAUTIC_GRAVITY_FORM, $formID, $data );
	}
}
add_action( 'gform_after_submission', 'haw_mautic_integration_gravity_form_hook', 10, 2 );
