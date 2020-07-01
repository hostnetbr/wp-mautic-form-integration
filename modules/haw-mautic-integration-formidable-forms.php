<?php
/**
 * Define Constants
 */
define('HAW_MAUTIC_FORMIDABLE_FORM','haw_mautic_formidableforms');
$haw_mautic_modules[] = HAW_MAUTIC_FORMIDABLE_FORM;
$haw_mautic_modules_name[HAW_MAUTIC_FORMIDABLE_FORM] = 'Formidable Forms';
$haw_mautic_modules_plugin_file[HAW_MAUTIC_FORMIDABLE_FORM] = 'formidable/formidable.php';



/**
 * Action to get form list of Formidable Forms.
 *
 * @param $result
 * @param $form_type
 *
 * @return array of id & label
 */
function haw_mautic_formidable_form_list( $result, $form_type ){
    if ( $form_type == HAW_MAUTIC_FORMIDABLE_FORM ) {

        global $wpdb;

        $query = 'select id,name from ' . $wpdb->prefix . 'frm_forms';
        $formidable_forms = $wpdb->get_results( $query );

        foreach( $formidable_forms as $form ) {
            $result[] = array( 'id' => $form->id , 'label' => $form->name );
        }
    }

    return $result;
}
add_filter( 'haw_mautic_get_form_list', 'haw_mautic_formidable_form_list', 10, 2 );

/**
 * Get form fields.
 *
 * @param $result
 * @param $form_type
 * @param $formID
 *
 * @return array of id & label
 */
function haw_mautic_formidable_form_fields( $result ,$form_type ,$formID )
{
    if( $form_type == HAW_MAUTIC_FORMIDABLE_FORM )
    {

        global $wpdb;
        $query = 'select id,default_value,name from '. $wpdb->prefix . 'frm_fields where form_id=%d';
        $form_fields = $wpdb->get_results(
                            $wpdb->prepare(
                                $query,
                                $formID
                            )
                        );

        foreach ( $form_fields as $field ) {
            $label = empty( $field->name )? $field->default_value : $field->name;
            $result[] = array( 'id' => $field->id, 'label' => $label );
        }

    }
    return $result;
}
add_filter( 'haw_mautic_get_form_fields', 'haw_mautic_formidable_form_fields', 10, 3 );

/**
 * Get form title.
 *
 * @param $form_title
 * @param $form_type
 * @param $formID
 *
 * @return null|string
 */
function haw_mautic_formidable_form_title( $form_title, $form_type, $formID ) {
    if ( $form_type == HAW_MAUTIC_FORMIDABLE_FORM ) {
        global $wpdb;
        $query = 'select name from '. $wpdb->prefix . 'frm_forms where id=%d';
        $form_title = $wpdb->get_var( $wpdb->prepare(
            $query,
            $formID
        ) );
    }
    return $form_title;
}
add_filter( 'haw_mautic_get_form_title', 'haw_mautic_formidable_form_title', 10, 3 );

/**
 * Hook to push data on mautic.
 *
 * @param $entry_id
 * @param $form_id
 */
function haw_mautic_integration_formidable_form_hook( $entry_id, $form_id ){
    if ( get_option( HAW_MAUTIC_FORMIDABLE_FORM ) ) {
        $data = $_POST['item_meta'];
        do_action( 'haw_mautic_push_data_to_mautic', HAW_MAUTIC_FORMIDABLE_FORM, $form_id, $data );
    }
}
add_action( 'frm_after_create_entry', 'haw_mautic_integration_formidable_form_hook', 30, 2 );