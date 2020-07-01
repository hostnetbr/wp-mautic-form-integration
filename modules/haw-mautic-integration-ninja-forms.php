<?php
/**
 * Define Constants
 */
define( 'HAW_MAUTIC_NINJA_FORM', 'haw_mautic_ninjaforms' );
$haw_mautic_modules[]                                     = HAW_MAUTIC_NINJA_FORM;
$haw_mautic_modules_name[ HAW_MAUTIC_NINJA_FORM ]         = 'Ninja Forms';
$haw_mautic_modules_plugin_file[ HAW_MAUTIC_NINJA_FORM ]  = 'ninja-forms/ninja-forms.php';

/**
 * Action to get form list of Ninja Form.
 *
 * @param $result
 * @param $form_type
 *
 * @return array of id & label
 */
function haw_mautic_ninja_form_list( $result, $form_type ) {
    if ( $form_type == HAW_MAUTIC_NINJA_FORM ) {

        if (defined('NINJA_FORMS_VERSION')) {
            $ninja_version = NINJA_FORMS_VERSION;
        } else {
            $ninja_version = NINJA_FORM_VERSION;
        }
        
        if ($ninja_version > '2.8.14') {
            $all_forms = Ninja_Forms()->form()->get_forms();
            foreach ( $all_forms as $form ) {
                $result[] = array( 'id' => $form->get_id(), 'label' => $form->get_setting( 'title' ) );
            }
        }else{
            $all_forms = ninja_forms_get_all_forms();    
            foreach ( $all_forms as $form ) {
                $result[] = array( 'id' => $form['id'], 'label' => $form['name'] );
            }
        }
    }
    
    return $result;
}
add_filter( 'haw_mautic_get_form_list', 'haw_mautic_ninja_form_list', 10, 2 );


/**
 * Get form fields.
 *
 * @param $result
 * @param $form_type
 * @param $formID
 *
 * @return array of id & label
 */
function haw_mautic_ninja_form_fields( $result, $form_type, $formID ) {
    if ( $form_type == HAW_MAUTIC_NINJA_FORM ) {
        
        if (defined('NINJA_FORMS_VERSION')) {
            $ninja_version = NINJA_FORMS_VERSION;
        } else {
            $ninja_version = NINJA_FORM_VERSION;
        }
        
        if ($ninja_version > '2.8.14') {
            $form_fields = Ninja_Forms()->form( $formID )->get_fields();
            foreach ( $form_fields as $field ) {
                $result[] = array( 'id' => $field->get_id(), 'label' => $field->get_setting('label') );
            }
        }else{
            $form_fields = ninja_forms_get_fields_by_form_id( $formID );
            foreach ( $form_fields as $field ) {
                $result[] = array( 'id' => $field["id"], 'label' => $field['data']['label'] );
            }
        }
    }
    return $result;
}
add_filter( 'haw_mautic_get_form_fields', 'haw_mautic_ninja_form_fields', 10, 3 );

/**
 * Get form title.
 *
 * @param $form_title
 * @param $form_type
 * @param $formID
 *
 * @return
 */
function haw_mautic_ninja_form_title( $form_title, $form_type, $formID ) {
    if ( $form_type == HAW_MAUTIC_NINJA_FORM ) {  

        if (defined('NINJA_FORMS_VERSION')) {
            @$ninja_version = NINJA_FORMS_VERSION;
        } else {
            @$ninja_version = NINJA_FORM_VERSION;
        }
        
        if ($ninja_version > '2.8.14') {
            $all_forms = Ninja_Forms()->form()->get_forms();
            foreach ( $all_forms as $form ) {
                if ( $form->get_id() == $formID ) {
                    $form_title = $form->get_setting( 'title' );
                }
            }
        }else{
            $all_forms = ninja_forms_get_all_forms();
            foreach ( $all_forms as $form ) {
                if ( $form['id'] == $formID ) {
                    $form_title = $form['name'];
                }
            }
        }
    }
    return $form_title;
}
add_filter( 'haw_mautic_get_form_title', 'haw_mautic_ninja_form_title', 10, 3 );

/**
 * Hook to push data on mautic.
 */
function haw_mautic_integration_ninja_form_hook_old() {
    global $ninja_forms_processing;
    if ( get_option( HAW_MAUTIC_NINJA_FORM ) ) {
        $formID     = $ninja_forms_processing->get_form_ID();
        $data       = $ninja_forms_processing->get_all_fields();
        do_action( 'haw_mautic_push_data_to_mautic', HAW_MAUTIC_NINJA_FORM, $formID, $data );
    }
}
function haw_mautic_integration_ninja_form_hook_new($form_data) {
    if ( get_option( HAW_MAUTIC_NINJA_FORM ) ) {
        $formID = $form_data[ 'form_id' ];
        $data   = array();
        foreach ($form_data['fields'] as $field_data) {
            $data[$field_data['id']] = $field_data['value'];
        }
        do_action( 'haw_mautic_push_data_to_mautic', HAW_MAUTIC_NINJA_FORM, $formID, $data );
    }
}
//ninja_forms_process
add_action( 'ninja_forms_after_submission', 'haw_mautic_integration_ninja_form_hook_new', 10 );
add_action( 'ninja_forms_post_process', 'haw_mautic_integration_ninja_form_hook_old', 10 );
