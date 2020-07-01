function hawMauticGetForms(ths)
{
    var form_type = jQuery(ths).val();
    if(form_type != '')
    {
        jQuery('#haw-form-1-name').html(jQuery('select[name="haw_mautic_form_1_type"] option:selected').text());
        jQuery.ajax({
            url: haw_mautic_integration_ajax_url,
            type: 'post',
            data:{
                form_type: form_type,
                action:'haw_mautic_get_forms'
            },
            dataType: 'json',
            success:function(response){
                var html = '<option value="" disabled selected="selected">Select Form</option>';
                if(response!='')
                {
                    jQuery.each(response,function(key, res){
                       html += '<option value="'+res.id+'">'+res.label+'</option>';
                    });
                }
                jQuery('select[name="haw_mautic_form_1"]').html(html);
            }
        });
    }
}

function hawMauticGetForm1Fields(ths)
{
    var form_type = jQuery('select[name="haw_mautic_form_1_type"]').val();
    var form_1 = jQuery(ths).val();
    if(form_1 != '')
    {
        jQuery.ajax({
            url: haw_mautic_integration_ajax_url,
            type: 'post',
            data:{
                form_type: form_type,
                form_1: form_1,
                action:'haw_mautic_get_form_1_fields'
            },
            dataType: 'json',
            success:function(response){
                var html = '<option value="" disabled selected="selected">Select Field</option>';
                if(response!='')
                {
                    jQuery.each(response,function(key, res){
                        html += '<option value="'+res.id+'">'+res.label+'</option>';
                    });
                }
                jQuery('.haw_mautic_form_1_field').html(html);
            }
        });
    }
}

function hawMauticGetForm2Fields(ths)
{
    var form_2 = jQuery(ths).val();
    if(form_2 != '')
    {
        jQuery.ajax({
            url: haw_mautic_integration_ajax_url,
            type: 'post',
            data:{
                form_2: form_2,
                action: 'haw_mautic_get_form_2_fields'
            },
            dataType: 'json',
            success:function(response){
                var html = '<option value="" disabled selected="selected">Select Field</option>';
                if(response!='')
                {
                    jQuery.each(response,function(key, res){
                        html += '<option value="'+res.id+'">'+res.label+'</option>';
                    });
                }
                jQuery('.haw_mautic_form_2_field').html(html);
            }
        });
    }
}

function hawMauticAddNew(ths)
{
    var html = '<tr><td><a onclick="hawMauticAddNew(this)"><i class="dashicons dashicons-plus-alt"></i> New Field</a> <a onclick="hawMauticRemoveMapField(this)"><i class="dashicons dashicons-trash"></i> Remove Field</a></td>';
    html += '<td>'+jQuery('#haw_mautic_add_new_map').find('tr').eq('5').find('td').eq('1').html()+'</td>';
    html += '<td>'+jQuery('#haw_mautic_add_new_map').find('tr').eq('5').find('td').eq('2').html()+'</td>';
    html += '</tr>';
    jQuery(ths).parents('tr').after(html);
    jQuery(ths).parents('tr').next('tr').find('select').val('');
}

function hawMauticRemoveMapField(ths)
{
    jQuery(ths).parents('tr').remove();
}

function hawMauticNextStep(ths, step)
{
    jQuery('span.haw-error').html('');
    if(step == '1')
    {
        var form_1_val = jQuery('select[name="haw_mautic_form_1_type"]').val();
        if( form_1_val == null || form_1_val == '')
        {
            jQuery('select[name="haw_mautic_form_1_type"]').next('span.haw-error').html('Please select form type.');
        }else{
            jQuery('#haw-form-1-name').html(jQuery('select[name="haw_mautic_form_1_type"] option:selected').text());
            jQuery('.step-2').removeClass('hidden');
            jQuery(ths).attr('onclick',"hawMauticNextStep(this, 2)");
        }
    }else if(step == '2')
    {
        var error = false;
        var form_1 = jQuery('select[name="haw_mautic_form_1"]').val();
        var form_2 = jQuery('select[name="haw_mautic_form_2"]').val();
        if(form_1 == null || form_1 == '')
        {
            jQuery('select[name="haw_mautic_form_1"]').next('span.haw-error').html('Please select form.');
            error = true;
        }
        if(form_2 == null || form_2 == '')
        {
            jQuery('select[name="haw_mautic_form_2"]').next('span.haw-error').html('Please select form.');
            error = true;
        }
        if(!error)
        {
            jQuery('.step-3').removeClass('hidden');
            jQuery(ths).addClass('hidden');
            jQuery('#submit').removeClass('hidden');
        }
    }
}