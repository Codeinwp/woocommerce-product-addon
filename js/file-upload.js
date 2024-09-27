/**
 * file upload js
 * @since 8.4
 **/
let isCartBlock = false;
const plupload_instances = Array();
const field_file_count = Array();
const file_list_preview_containers = Array();
var ppom_file_progress = '';
var featherEditor = '';
const uploaderInstances = {};
var Cropped_Data_Captured = false;

jQuery(function($) {

    // If cropper input found in fields
    // if (ppom_get_field_meta_by_type('cropper').length > 0) {

    //     var wc_cart_form = $('form.cart');
    //     $(wc_cart_form).on('submit', function(e) {

    //         // e.preventDefault();
    //         var cropper_fields = ppom_get_field_meta_by_type('cropper');
    //         $.each(cropper_fields, function(i, cropper) {

    //             if (cropper.legacy_cropper !== undefined) return;

    //             var cropper_name = cropper.data_name;
    //             ppom_generate_cropper_data_for_cart(cropper.data_name);

    //         });
    //     });
    // }

    $(document).on('ppom_image_ready', function(e) {

        var image_url = e.image_url;
        var image_id = e.image.id;
        var data_name = e.data_name;
        var input_type = e.input_type;

        if (input_type === 'cropper') {

            field_meta = ppom_get_field_meta_by_id(data_name);
            // console.log('ppom',field_meta)
            if (field_meta.legacy_cropper === undefined) {
                ppom_show_cropped_preview(data_name, image_url, image_id);
                // hiding the filelist-{data_name} when preview enabled
                $(`#filelist-${data_name}`).hide();
                // hide the file upload area too
                $(`.ppom-file-container.${data_name}`).hide();
                // also hide the crop ratio if only one option is provided
                if( $(`#crop-size-${data_name} option`).length === 1){
                    $(`#crop-size-${data_name}`).hide();
                }
            }
        }

        // moving modal to body end
        $('.ppom-modals').appendTo('body');
    });

    // On file removed
    $(document).on('ppom_uploaded_file_removed', function(e) {

        var field_name = e.field_name;
        // var fileid      = e.fileid;

        ppom_reset_cropping_preview(field_name);
        ppom_update_option_prices();
    });


    // Croppie update size
    $('.ppom-croppie-preview').on('change', '.ppom-cropping-size', function(e) {

        var data_name = $(this).data('field_name');
        var cropp_preview_container = jQuery(".ppom-croppie-wrapper-" + data_name);
        var v_width = $('option:selected', this).data('width');
        var v_height = $('option:selected', this).data('height');

        cropp_preview_container.find('.croppie-container').each(function(i, croppie_dom) {

            var image_id = jQuery(croppie_dom).attr('data-image_id');
            $(croppie_dom).croppie('destroy');
            const viewport = {'width': v_width, 'height': v_height};
            ppom_set_croppie_options(data_name, viewport, image_id);
        });

    });

    // Deleting File
    document.querySelector('.ppom-wrapper')?.addEventListener('click', async function(e) {
        if (
            ! e.target.classList.contains('u_i_c_tools_del') ||
            ! plupload_instances
        ) {
            return;
        }

        e.preventDefault();

        const delMessage = ppom_file_vars.delete_file_msg;
        if ( ! confirm( delMessage ) ) return;

        const ppomFileWrapper = e.target.closest('.ppom-file-wrapper');
        const fileId = ppomFileWrapper?.getAttribute("data-fileid");
        const ppomFieldWrapper = e.target.closest('div.ppom-field-wrapper');
        const fileDataName = ppomFieldWrapper?.getAttribute("data-data_name");

        if ( !fileId || !fileDataName ) return;

        field_file_count[fileDataName] = 0;

        const uploaderInstance = plupload_instances[fileDataName];
        if ( uploaderInstance ) {
            uploaderInstance.removeFile(fileId);
        }

        const checkbox = document.querySelector(`input[name="ppom[fields][${fileDataName}][${fileId}][org]"]`);
        const fileName = checkbox?.value;

        if ( ! fileName ) return;

        // Delete animation.
        const imageElement = document.querySelector(`#u_i_c_${fileId} img`);
        if ( imageElement ) {
            imageElement.src = `${ppom_file_vars.plugin_url}/images/loading.gif`;
        }

        const data = new URLSearchParams({
            action: 'ppom_delete_file',
            file_name: fileName,
            ppom_nonce: ppom_file_vars.ppom_file_delete_nonce
        });

        try {
            const response = await fetch(ppom_file_vars.ajaxurl, {
                method: 'POST',
                body: data,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const responseText = await response.text();
            if ( ! response.ok ) {
                confirm(`Error: ${responseText}`);
                return;
            }

            // Update UI
            const fileContainer = document.querySelector(`#u_i_c_${fileId}`);
            if ( fileContainer ) {
                fileContainer.remove();
            }

            if ( checkbox ) {
                checkbox.remove();
            }

            const parentBox = e.target.closest('.u_i_c_box');
            if ( parentBox ) {
                parentBox.remove();
            }

            const croppiePreview = document.querySelector(`.ppom-croppie-preview-${fileId}`);
            if ( croppiePreview ) {
                croppiePreview.remove();
            }

            // Send action to PPOM_Validate
            document.dispatchEvent(new CustomEvent("ppom_uploaded_file_removed", {
                detail: {
                    field_name: fileDataName,
                    fileid: fileId,
                    time: new Date()
                }
            }));

            // Decrease file count
            field_file_count[fileDataName] -= 1;

        } catch (error) {
            confirm(`Error: ${error.message}`);
        }
    });

    $.each(ppom_input_vars.ppom_inputs, function(index, file_input) {


        if (file_input.type === 'file' || file_input.type === 'cropper') {

            var file_data_name = file_input.data_name;
            ppom_setup_file_upload_input(file_input);
        }

    }); // $.each(ppom_file_vars


}); //	jQuery(function($){});

// generate thumbbox
function add_thumb_box(file, $filelist_DIV) {

    let inner_html = '<div class="u_i_c_thumb"><div class="progress_bar"><span class="progress_bar_runner"></span><span class="progress_bar_number">(' + plupload.formatSize(file.size) + ')<span></div></div>';
    inner_html += '<div class="u_i_c_name"><strong>' + file.name + '</strong></div>';

    jQuery('<div />', {
        'id': 'u_i_c_' + file.id,
        'class': 'uk-text-center ppom-file-wrapper',
        'data-fileid': file.id,
        'html': inner_html,

    }).appendTo($filelist_DIV);

    // clearfix
    // 1- removing last clearfix first
    $filelist_DIV.find('.u_i_c_box_clearfix').remove();

    jQuery('<div />', {
        'class': 'u_i_c_box_clearfix',
    }).appendTo($filelist_DIV);

}


// save croped/edited photo
function save_edited_photo(img_id, photo_url) {

    //console.log(img_id);

    //setting new image width to 75
    jQuery('#' + img_id).attr('width', 75);

    //disabling add to cart button for a while
    jQuery('form.cart').block({
        message: null,
        overlayCSS: {
            background: "#fff",
            opacity: .6
        }
    });
    var post_data = {
        action: 'ppom_save_edited_photo',
        image_url: photo_url,
        filename: jQuery('#' + img_id).attr('data-filename')
    };

    jQuery.post(ppom_file_vars.ajaxurl, post_data, function(resp) {

        //console.log( resp );
        jQuery('form.cart').unblock();

    });
}

// Cropping image with Croppie
function ppom_show_cropped_preview(file_name, image_url, image_id) {

    var cropp_preview_container = jQuery(".ppom-croppie-wrapper-" + file_name);
    // Enable size option
    cropp_preview_container.find('.ppom-cropping-size').prop('disabled', false);
    cropp_preview_container.find('.ppom-cropping-size').show();

    const croppie_container = jQuery('<div/>')
        .addClass('ppom-croppie-preview-' + image_id)
        .attr('data-image_id', image_id)
        .appendTo(cropp_preview_container);

    // Change preview image
    jQuery('<a/>')
        .addClass('btn ' + image_id)
        .attr('href', '#')
        .html('Change image')
        .appendTo(cropp_preview_container)
        .click(function(e){
            e.preventDefault();
            location.reload();
        });


    // file_list_preview_containers[file_name]['croppie'] = cropp_preview_container.find('.ppom-croppie-preview');

    jQuery(croppie_container).on('update.croppie', function(ev, cropData) {
            // console.log(cropData);
            // croppie_container.croppie('result', 'rawcanvas').then(function(canvas) {
            // console.log(canvas);

            ppom_generate_cropper_data_for_cart(file_name);

            jQuery.event.trigger({
                type: 'ppom_croppie_update',
                img_id: image_id,
                croppie_obj: croppie_container,
                crop_data: cropData,
                dataname: file_name,
                time: new Date()
            });

    });

    file_list_preview_containers[file_name]['croppie'][image_id] = croppie_container;
    file_list_preview_containers[file_name]['image_id'] = image_id;
    file_list_preview_containers[file_name]['image_url'] = image_url;

    ppom_set_croppie_options(file_name, undefined, image_id);
}

function ppom_set_croppie_options(file_name, viewport, image_id) {

    const croppie_options = ppom_file_vars.croppie_options;
    jQuery.each(croppie_options, function(field_name, option) {

        if (file_name === field_name) {

            option.url = file_list_preview_containers[file_name]['image_url'];
            if (viewport !== undefined) {
                viewport.type = option.viewport.type;
                option.viewport = viewport;
            }

            // console.log($filelist_DIV[file_name]['croppie'][image_id]);
            file_list_preview_containers[file_name]['croppie'][image_id].croppie(option);
        }
    });
}

// Reset cropping when image removed
function ppom_reset_cropping_preview(file_name) {

    var cropp_preview_container = jQuery(".ppom-croppie-wrapper-" + file_name);
    // Reseting preview DOM
    cropp_preview_container.find('.ppom-croppie-preview').html('');
}

// Attach FILE API with DOM
function ppom_setup_file_upload_input(file_input) {

    const file_data_name = file_input.data_name;
    if ( plupload_instances[file_data_name] !== undefined ) {
        return;
    }

    field_file_count[file_data_name] = 0;
    file_list_preview_containers[file_data_name] = jQuery('#filelist-' + file_data_name);

    // Energy pack
    const bar = window.document.getElementById(`ppom-progressbar-${file_data_name}`);

    const ppom_file_data = {
        'action': 'ppom_upload_file',
        'data_name': file_data_name,
        'ppom_nonce': ppom_file_vars.ppom_file_upload_nonce,
        'product_id': ppom_file_vars.product_id,
    };

    let img_dim_errormsg = 'Please upload correct image dimension';
    if (file_input.img_dimension_error) {
        img_dim_errormsg = file_input.img_dimension_error;
    }

    plupload_instances[file_data_name] = new plupload.Uploader({
        runtimes: ppom_file_vars.plupload_runtime,
        browse_button: 'selectfiles-' + file_data_name, // you can pass in id...
        container: 'ppom-file-container-' + file_data_name, // ... or DOM Element itself
        drop_element: 'ppom-file-container-' + file_data_name,
        url: ppom_file_vars.ajaxurl,
        multipart_params: ppom_file_data,
        max_file_size: file_input.file_size,
        max_file_count: parseInt(file_input.files_allowed),
        unique_names: ppom_file_vars.enable_file_rename,
        chunk_size: '2mb',
        unique_names: false,

        filters: {
            mime_types: [
                { title: "Filetypes", extensions: file_input.file_types }
            ]
        },

        init: {
            PostInit: function() {

                // file_list_preview_containers[file_data_name].html('');
                if ( ! file_list_preview_containers[file_data_name].is(':visible') ) {
                    jQuery(document).on('ppom_field_shown', function() {

                        jQuery.each(ppom_input_vars.ppom_inputs, function(index, file_input) {
                            if (file_input && (file_input.type === 'file' || file_input.type === 'cropper')) {
                                if (
                                    file_input.data_name &&
                                    file_input.files_allowed &&
                                    file_input.file_size &&
                                    file_input.files_allowed
                                ) {
                                    ppom_setup_file_upload_input(file_input);
                                }
                            }

                        });
                    } );
                }
                /*$('#uploadfiles-'+file_data_name).bind('click', function() {
                	upload_instance[file_data_name].start();
                	return false;
                });*/
            },

            FilesAdded: function(up, files) {

                // Adding progress bar
                const file_pb = jQuery('<div/>')
                    .addClass('progress')
                    .css('width', '100%')
                    .css('clear', 'both')
                    .css('margin', '5px auto')
                    .appendTo(file_list_preview_containers[file_data_name]);
                const file_pb_runner = jQuery('<div/>')
                    .addClass('progress-bar')
                    .attr('role', 'progressbar')
                    .attr('aria-valuenow', 0)
                    .attr('aria-valuemin', 0)
                    .attr('aria-valuemax', 100)
                    .css('height', '15px')
                    .css('width', 0)
                    .appendTo(file_pb);

                const files_added = files.length;
                // return;

                // console.log('image w bac', files);
                // plupload.each(files, function(file, i) {
                //     var img = new mOxie.Image;
                //     img.onload = function() {
                //         var img_height = this.height;
                //         var img_width = this.width;
                //         // if ((img_height >= 1024 || img_height <= 1100) && (img_width >= 750 || img_width <= 800)) {
                //         if ((img_width >= parseFloat(file_input.max_img_w) || img_width <= parseFloat(file_input.min_img_w))) {
                //             alert("Height and Width must not exceed 1100*800.");
                //             return false;
                //         }
                //         console.log('image h', parseFloat(file_input.max_img_w));
                //         // access image size here using this.width and this.height
                //     };
                //     img.load(file.getSource());
                // });

                if ((field_file_count[file_data_name] + files_added) > plupload_instances[file_data_name].settings.max_file_count) {
                    alert(plupload_instances[file_data_name].settings.max_file_count + ppom_file_vars.mesage_max_files_limit);
                }
                else {

                    plupload.each(files, function(file) {

                        if (file.type.indexOf("image") !== -1 && file.type !== 'image/photoshop') {
                            const img = new moxie.image.Image();
                            img.load = function() {

                                const img_height = this.height;
                                const img_width = this.width;

                                let aspect_ratio = Math.max(img_width, img_height) / Math.min(img_width, img_height);

                                if (img_width >= parseFloat(file_input.max_img_w) || img_width <= parseFloat(file_input.min_img_w)) {
                                    plupload_instances[file_data_name].stop();
                                    plupload_instances[file_data_name].removeFile(file);
                                    alert(img_dim_errormsg);
                                }
                                else if (img_height >= parseFloat(file_input.max_img_h) || img_height <= parseFloat(file_input.min_img_h)) {
                                    plupload_instances[file_data_name].stop();
                                    plupload_instances[file_data_name].removeFile(file);
                                    alert(img_dim_errormsg);
                                }
                                else {
                                    field_file_count[file_data_name]++;
                                    // Code to add pending file details, if you want
                                    add_thumb_box(file, file_list_preview_containers[file_data_name], up);
                                    setTimeout('plupload_instances[\'' + file_data_name + '\'].start()', 100);
                                }
                            };
                            img.load(file.getSource());
                        }
                        else {
                            field_file_count[file_data_name]++;
                            // Code to add pending file details, if you want
                            add_thumb_box(file, file_list_preview_containers[file_data_name], up);
                            setTimeout('plupload_instances[\'' + file_data_name + '\'].start()', 100);
                        }


                        // Energy pack
                        if ( bar ) {
                            bar.removeAttribute('hidden');
                            bar.max = file.size;
                            bar.value = file.loaded;
                        }
                    });
                }


            },

            FileUploaded: function(up, file, info) {


                const obj_resp = jQuery.parseJSON(info.response);

                if (obj_resp.file_name === 'ThumbNotFound') {

                    plupload_instances[file_data_name].removeFile(file.id);
                    jQuery("#u_i_c_" + file.id).hide(500).remove();
                    field_file_count[file_data_name]--;

                    alert('There is some error please try again');
                    return;

                }
                else if (obj_resp.status === 'error') {

                    plupload_instances[file_data_name].removeFile(file.id);

                    jQuery("#u_i_c_" + file.id).hide(500).remove();

                    field_file_count[file_data_name]--;
                    alert(obj_resp.message);
                    return;
                };

                // var img_w = obj_resp.file_w
                // var img_h = obj_resp.file_h
                // if (img_w > parseFloat(file_input.max_img_w)) {
                //     upload_instance[file_data_name].removeFile(file.id);
                //     jQuery("#u_i_c_" + file.id).hide(500).remove();
                //     file_count[file_data_name]--;
                //     alert('Image Dimension Error');
                //     jQuery('form.cart').unblock();
                //     return;
                // }

                let file_thumb = '';

                /*if( file_input.file_cost != "" ) {
                    jQuery('input[name="woo_file_cost"]').val( file_input.file_cost );
                }*/

                file_list_preview_containers[file_data_name].find('#u_i_c_' + file.id).html(obj_resp.html)
                    .trigger({
                        type: "ppom_image_ready",
                        image: file,
                        data_name: file_data_name,
                        input_type: file_input.type,
                        image_url: obj_resp.file_url,
                        image_resp: obj_resp,
                        time: new Date()
                    });


                // checking if uploaded file is thumb
                const ext = obj_resp.file_name.substring(obj_resp.file_name.lastIndexOf('.') + 1).toLowerCase();

                if (
                    ext === 'png' ||
                    ext === 'gif' ||
                    ext === 'jpg' ||
                    ext === 'jpeg'
                ) {
                    const file_full = ppom_file_vars.file_upload_path + obj_resp.file_name;
                    // thumb thickbox only shown if it is image
                    file_list_preview_containers[file_data_name]
                        .find('#u_i_c_' + file.id)
                        .find('.u_i_c_thumb')
                        .append('<div style="display:none" id="u_i_c_big' + file.id + '"><img src="' + file_full + '" /></div>');

                    // Aviary editing tools
                    if (file_input.photo_editing === 'on' && ppom_file_vars.aviary_api_key !== '') {
                        const editing_tools = file_input.editing_tools;
                        file_list_preview_containers[file_data_name]
                            .find('#u_i_c_' + file.id)
                            .find('.u_i_c_tools_edit')
                            .append('<a onclick="return   (\'thumb_' + file.id + '\', \'' + file_full + '\', \'' + obj_resp.file_name + '\', \'' + editing_tools + '\')" href="javascript:;" title="Edit"><img width="15" src="' + ppom_file_vars.plugin_url + '/images/edit.png" /></a>');
                    }
                } else {
                    file_thumb = ppom_file_vars.plugin_url + '/images/file.png';
                    file_list_preview_containers[file_data_name].find('#u_i_c_' + file.id)
                        .find('.u_i_c_thumb')
                        .html('<img src="' + file_thumb + '" id="thumb_' + file.id + '" />')
                }

                // adding checkbox input to Hold uploaded file name as array
                const file_container = file_list_preview_containers[file_data_name].find('#u_i_c_' + file.id);
                let input_class = 'ppom-input';
                input_class += file_input.required === 'on' ? ' ppom-required' : '';

                // Add file check
                jQuery('<input checked="checked" name="ppom[fields][' + file_data_name + '][' + file.id + '][org]" type="checkbox"/>')
                    .attr('data-price', file_input.file_cost)
                    .attr('data-label', obj_resp.file_name)
                    .attr('data-data_name', file_input.data_name)
                    .attr('data-title', file_input.title)
                    .attr('data-onetime', file_input.onetime)
                    .val(obj_resp.file_name)
                    .css('display', 'none')
                    .addClass('ppom-file-cb-' + file_data_name)
                    .addClass('ppom-file-cb')
                    .addClass(input_class)
                    .appendTo(file_container)
                    .trigger('change');

                ppom_update_option_prices();

                jQuery('form.cart').unblock();
                isCartBlock = false;

                // Removing progressbar
                file_list_preview_containers[file_data_name].find('.progress').remove();

                if ( bar ) {
                    setTimeout(function() {
                        bar.setAttribute('hidden', 'hidden');
                    }, 1000);
                    bar.max = file.size;
                    bar.value = file.loaded;
                }

                // Trigger
                jQuery.event.trigger({
                    type: "ppom_file_uploaded",
                    file: file,
                    file_meta: file_input,
                    file_resp: obj_resp,
                    time: new Date()
                });
            },

            UploadProgress: function(up, file) {

                // Energy pack
                if ( bar ) {
                    bar.max = file.size;
                    bar.value = file.loaded;
                }

                file_list_preview_containers[file_data_name].find('.progress-bar').css('width', file.percent + '%');

                //disabling add to cart button for a while
                if (!isCartBlock) {
                    jQuery('form.cart').block({
                        message: null,
                        overlayCSS: {
                            background: "#fff",
                            opacity: .6,
                            onBlock: function() {
                                isCartBlock = true;
                            }
                        }
                    });
                }
            },

            Error: function(up, err) {
                //document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
                alert("\nError #" + err.code + ": " + err.message);
            }
        }


    });

    // console.log('running file', upload_instance[file_data_name]);
    plupload_instances[file_data_name].init();
    uploaderInstances[file_data_name] = plupload_instances[file_data_name];
}

// Generate Cropped image data for cart
function ppom_generate_cropper_data_for_cart(field_name) {

    const cropp_preview_container = jQuery(".ppom-croppie-wrapper-" + field_name);

    cropp_preview_container.find('.croppie-container').each(function(i, croppie_dom) {

        const image_id = jQuery(croppie_dom).attr('data-image_id');
        jQuery(croppie_dom).croppie('result', {
            type: 'rawcanvas',
            // size: { width: 300, height: 300 },
            size: 'original',
            format: 'png'
        }).then(function(canvas) {
            const image_url = canvas.toDataURL();
            //console.log(image_url);
            // remove first
            jQuery(`input[name="ppom[fields][${field_name}][${image_id}][cropped]"`).remove();

            // Add file check
           jQuery('<input checked="checked" name="ppom[fields][' + field_name + '][' + image_id + '][cropped]" type="checkbox"/>')
                .val(image_url)
                .css('display', 'none')
                .appendTo(file_list_preview_containers[field_name]);

        });
    });
}
