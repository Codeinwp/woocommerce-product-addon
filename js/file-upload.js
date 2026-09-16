/**
 * File and cropper workflow for PPOM frontend fields.
 *
 * Each file/cropper field gets a dedicated Plupload instance. This script owns
 * the upload UI, preview/croppie state, and the hidden inputs that eventually
 * travel through add-to-cart, cart restore, and order meta persistence.
 *
 * @see ppom_get_field_meta_by_id in js/ppom.inputs.js
 * @see ppom_update_option_prices in js/price/ppom-price.js
 * @see ppom_generate_cropper_data_for_cart
 */

/**
 * Minimal localized metadata used by the upload/cropper bootstrap.
 *
 * @typedef {{
 *   data_name: string,
 *   type: 'file'|'cropper',
 *   file_size: string,
 *   files_allowed: string,
 *   file_types: string,
 *   title?: string,
 *   required?: string,
 *   file_cost?: string,
 *   onetime?: string,
 *   max_img_w?: string,
 *   min_img_w?: string,
 *   max_img_h?: string,
 *   min_img_h?: string
 * }} PPOMUploadFieldMeta
 */
let isCartBlock = false;
// Runtime registries keyed by PPOM field data_name.
const plupload_instances = Array();
const field_file_count = Array();
const file_list_preview_containers = Array();
const ppom_file_progress = '';
const featherEditor = '';
const uploaderInstances = {};
const Cropped_Data_Captured = false;

// Track nonce refresh state to avoid duplicate requests
let nonceRefreshPromise = null;
let lastNonceRefreshTime = Date.now();
const NONCE_CACHE_DURATION = 300000; // 5 minutes in milliseconds

/**
 * Storage key holding this browser's proof that it uploaded a given file.
 *
 * @param {string} fileName Stored file name.
 * @return {string} Key under which the token is kept.
 */
function ppom_delete_token_key( fileName ) {
	return 'ppom_delete_token_' + fileName;
}

/**
 * Keeps the delete token for an upload so it survives leaving the page.
 *
 * A form rendered again from the cart carries no token: the server must never
 * sign a file name it was handed, or it becomes a delete-token oracle. Holding
 * the proof in the browser keeps it available without asking the server to
 * vouch for a name it did not just store.
 *
 * @param {string} fileName Stored file name.
 * @param {string} token    Token issued with the upload.
 * @return {void}
 */
function ppom_remember_delete_token( fileName, token ) {
	if ( ! fileName || ! token ) {
		return;
	}

	try {
		window.sessionStorage.setItem(
			ppom_delete_token_key( fileName ),
			token
		);
	} catch ( error ) {
		// Private browsing or a full quota: the session check still applies.
	}
}

/**
 * Returns the kept token for an upload, if this browser still has one.
 *
 * @param {string} fileName Stored file name.
 * @return {string} Token, or an empty string.
 */
function ppom_read_delete_token( fileName ) {
	if ( ! fileName ) {
		return '';
	}

	try {
		return (
			window.sessionStorage.getItem(
				ppom_delete_token_key( fileName )
			) || ''
		);
	} catch ( error ) {
		return '';
	}
}

/**
 * Fetches fresh nonces from the REST API endpoint.
 *
 * This function is called before file operations to ensure nonces are valid,
 * solving the issue of stale nonces in cached pages or long-lived browser tabs.
 *
 * @returns {Promise<Object>} Promise that resolves with fresh nonce data.
 */
async function ppom_refresh_file_nonces() {
	// If we have a pending refresh request, return it instead of making a new one
	if ( nonceRefreshPromise ) {
		return nonceRefreshPromise;
	}

	// If nonces were refreshed recently (within cache duration), skip refresh
	const now = Date.now();
	if ( now - lastNonceRefreshTime < NONCE_CACHE_DURATION ) {
		return Promise.resolve( {
			ppom_file_upload_nonce: ppom_file_vars.ppom_file_upload_nonce,
			ppom_file_delete_nonce: ppom_file_vars.ppom_file_delete_nonce,
		} );
	}

	nonceRefreshPromise = fetch( ppom_file_vars.rest_url, {
		method: 'GET',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': ppom_file_vars.wp_rest_nonce,
		},
	} )
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( 'Failed to refresh nonces' );
			}
			return response.json();
		} )
		.then( ( data ) => {
			if ( data.status === 'success' ) {
				// Update the global nonce variables
				ppom_file_vars.ppom_file_upload_nonce =
					data.ppom_file_upload_nonce;
				ppom_file_vars.ppom_file_delete_nonce =
					data.ppom_file_delete_nonce;
				lastNonceRefreshTime = Date.now();
				return data;
			}
			throw new Error( 'Invalid nonce response' );
		} )
		.finally( () => {
			// Clear the promise so next request can proceed
			nonceRefreshPromise = null;
		} );

	return nonceRefreshPromise;
}

jQuery( function ( $ ) {
	// Keep cropper previews, price recalculation, and modal placement aligned
	// with the rest of the PPOM product form lifecycle.
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

	$( document ).on( 'ppom_image_ready', function ( e ) {
		const image_url = e.image_url;
		const image_id = e.image.id;
		const data_name = e.data_name;
		const input_type = e.input_type;
		const file_input = e.file_input;

		if ( input_type === 'cropper' ) {
			field_meta = ppom_get_field_meta_by_id( data_name );
			// console.log('ppom',field_meta)
			if ( field_meta.legacy_cropper === undefined ) {
				ppom_show_cropped_preview(
					data_name,
					image_url,
					image_id,
					file_input
				);
				// hiding the filelist-{data_name} when preview enabled
				$( `#filelist-${ data_name }` ).hide();
				// hide the file upload area too
				$( `.ppom-file-container.${ data_name }` ).hide();
				// also hide the crop ratio if only one option is provided
				if ( $( `#crop-size-${ data_name } option` ).length === 1 ) {
					$( `#crop-size-${ data_name }` ).hide();
				}
			}
		}

		// moving modal to body end
		$( '.ppom-modals' ).appendTo( 'body' );
	} );

	// On file removed
	$( document ).on( 'ppom_uploaded_file_removed', function ( e ) {
		const field_name = e.field_name;
		// var fileid      = e.fileid;

		ppom_reset_cropping_preview( field_name );
		ppom_update_option_prices();
	} );

	// Croppie update size
	$( '.ppom-croppie-preview' ).on(
		'change',
		'.ppom-cropping-size',
		function ( e ) {
			const data_name = $( this ).data( 'field_name' );
			const cropp_preview_container = jQuery(
				'.ppom-croppie-wrapper-' + data_name
			);
			const v_width = $( 'option:selected', this ).data( 'width' );
			const v_height = $( 'option:selected', this ).data( 'height' );

			cropp_preview_container
				.find( '.croppie-container' )
				.each( function ( i, croppie_dom ) {
					const image_id =
						jQuery( croppie_dom ).attr( 'data-image_id' );
					const croppie_container = jQuery(
						'.ppom-croppie-preview-' + image_id
					);
					// Destroy the current Croppie instance before re-initialising.
					// Do NOT read image src from the DOM here: Croppie can replace
					// it with a blob/data URL or remove the element entirely during
					// destroy(), producing an invalid URL. The canonical upload URL
					// is already stored in
					// file_list_preview_containers[data_name].image_url (set by
					// ppom_show_cropped_preview) and must not be overwritten.
					$( croppie_dom ).croppie( 'destroy' );
					const viewport = { width: v_width, height: v_height };

					file_list_preview_containers[ data_name ].croppie[
						image_id
					] = croppie_container;
					file_list_preview_containers[ data_name ].image_id =
						image_id;

					ppom_set_croppie_options( data_name, viewport, image_id );
				} );
		}
	);

	// Deleting File
	//
	// Delegated on `document` rather than bound to the first `.ppom-wrapper`
	// found: a click handler attached only to that one node never fires for a
	// second PPOM form's delete buttons on the same page (issue #735's
	// file-upload counterpart).
	document.addEventListener( 'click', async function ( e ) {
			if (
				! e.target.classList.contains( 'u_i_c_tools_del' ) ||
				! plupload_instances
			) {
				return;
			}

			e.preventDefault();

			const delMessage = ppom_file_vars.delete_file_msg;
			if ( ! confirm( delMessage ) ) {
				return;
			}

			// Fresh uploads are wrapped by the uploader in `.ppom-file-wrapper`;
			// files the server renders back into the form get `.u_i_c_box` instead.
			// Both carry data-fileid, so accept either or delete does nothing on a
			// restored file.
			const ppomFileWrapper = e.target.closest(
				'.ppom-file-wrapper, .u_i_c_box'
			);
			const fileId = ppomFileWrapper?.getAttribute( 'data-fileid' );
			const ppomFieldWrapper = e.target.closest(
				'div.ppom-field-wrapper'
			);
			const fileDataName =
				ppomFieldWrapper?.getAttribute( 'data-data_name' );

			if ( ! fileId || ! fileDataName ) {
				return;
			}

			const scopeWrapper = e.target.closest( '.ppom-wrapper' );
			const scopeProductId = scopeWrapper
				?.querySelector( '[name="ppom_product_id"]' )
				?.value;
			const instanceKey = scopeProductId
				? fileDataName + '__' + scopeProductId
				: fileDataName;

			field_file_count[ instanceKey ] = 0;

			const uploaderInstance = plupload_instances[ instanceKey ];
			if ( uploaderInstance ) {
				uploaderInstance.removeFile( fileId );
			}

			const checkbox = document.querySelector(
				`input[name="ppom[fields][${ fileDataName }][${ fileId }][org]"]`
			);
			const fileName = checkbox?.value;

			if ( ! fileName ) {
				return;
			}

			// Delete animation.
			// Scoped to this click's wrapper, not looked up by id: restored ids come
			// from each field's own array key, so the first file of every field is
			// `u_i_c_0` and a document lookup would leave another field's thumbnail
			// showing the spinner for good.
			const imageElement = ppomFileWrapper?.querySelector( 'img' );
			if ( imageElement ) {
				imageElement.src = `${ ppom_file_vars.plugin_url }/images/loading.gif`;
			}

			// Refresh nonces before delete operation
			try {
				await ppom_refresh_file_nonces();
			} catch ( error ) {
				// Continue with existing nonce if refresh fails
				console.warn(
					'Failed to refresh nonce, using existing:',
					error
				);
			}

			const data = new URLSearchParams( {
				action: 'ppom_delete_file',
				file_name: fileName,
				ppom_nonce: ppom_file_vars.ppom_file_delete_nonce,
			} );

			// On the page that performed the upload the token is on the input. A form
			// rendered again from the cart has no token in its markup -- the server
			// must not sign a file name it is handed, or it becomes an oracle -- so
			// fall back to the copy this browser kept.
			const deleteToken =
				checkbox?.dataset?.deleteToken ||
				ppom_read_delete_token( fileName );

			if ( deleteToken ) {
				data.append( 'ppom_delete_token', deleteToken );
			}

			try {
				const response = await fetch( ppom_file_vars.ajaxurl, {
					method: 'POST',
					body: data,
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						'X-Requested-With': 'XMLHttpRequest',
					},
				} );

				const responseText = await response.text();
				if ( ! response.ok ) {
					confirm( `Error: ${ responseText }` );
					return;
				}

				// The token is deliberately kept. delete_file() ends in die( 0 ), so a
				// refusal also answers 200 and there is no signal here that survives
				// translation; discarding on this path threw away the shopper's only
				// proof and made the file impossible to remove. Entries are per tab
				// and go when it closes.

				// Update UI. Remove the wrapper this click resolved rather than
				// looking one up by id: restored ids come from each field's own array
				// key, so the first file of every field is `u_i_c_0` and a document
				// lookup would take whichever comes first, stripping another field's
				// value out of the form.
				if ( ppomFileWrapper ) {
					ppomFileWrapper.remove();
				}

				if ( checkbox ) {
					checkbox.remove();
				}

				const croppiePreview = document.querySelector(
					`.ppom-croppie-preview-${ fileId }`
				);
				if ( croppiePreview ) {
					croppiePreview.remove();
				}

				// Send action to PPOM_Validate
				document.dispatchEvent(
					new CustomEvent( 'ppom_uploaded_file_removed', {
						detail: {
							field_name: fileDataName,
							fileid: fileId,
							time: new Date(),
						},
					} )
				);

				// Decrease file count
				field_file_count[ instanceKey ] -= 1;
			} catch ( error ) {
				confirm( `Error: ${ error.message }` );
			}
		} );

	// Two PPOM forms on one page each localize their own `ppom_input_vars`
	// snapshot (see ppom_input_vars_by_product in ppom.inputs.js); walk every
	// product present so a 'file'/'cropper' field shared by both forms gets
	// its own uploader instead of only the last-localized product's.
	const ppom_file_setup_products =
		typeof window.ppom_input_vars_by_product !== 'undefined'
			? window.ppom_input_vars_by_product
			: { '': ppom_input_vars };

	$.each( ppom_file_setup_products, function ( product_id, product_vars ) {
		const $scope = product_id
			? $( `.ppom-wrapper:has([name="ppom_product_id"][value="${ product_id }"])` )
			: $( document );

		$.each( product_vars.ppom_inputs, function ( index, file_input ) {
			if ( file_input.type === 'file' || file_input.type === 'cropper' ) {
				ppom_setup_file_upload_input( file_input, $scope );
			}
		} );
	} );
} ); //	jQuery(function($){});

// Build the temporary thumbnail shell shown while a file is uploading.
function add_thumb_box( file, $filelist_DIV ) {
	let inner_html =
		'<div class="u_i_c_thumb"><div class="progress_bar"><span class="progress_bar_runner"></span><span class="progress_bar_number">(' +
		plupload.formatSize( file.size ) +
		')<span></div></div>';
	inner_html +=
		'<div class="u_i_c_name"><strong>' + file.name + '</strong></div>';

	jQuery( '<div />', {
		id: 'u_i_c_' + file.id,
		class: 'uk-text-center ppom-file-wrapper',
		'data-fileid': file.id,
		html: inner_html,
	} ).appendTo( $filelist_DIV );

	// clearfix
	// 1- removing last clearfix first
	$filelist_DIV.find( '.u_i_c_box_clearfix' ).remove();

	jQuery( '<div />', {
		class: 'u_i_c_box_clearfix',
	} ).appendTo( $filelist_DIV );
}

// save croped/edited photo
function save_edited_photo( img_id, photo_url ) {
	//console.log(img_id);

	//setting new image width to 75
	jQuery( '#' + img_id ).attr( 'width', 75 );

	//disabling add to cart button for a while
	jQuery( 'form.cart' ).block( {
		message: null,
		overlayCSS: {
			background: '#fff',
			opacity: 0.6,
		},
	} );
	const post_data = {
		action: 'ppom_save_edited_photo',
		image_url: photo_url,
		filename: jQuery( '#' + img_id ).attr( 'data-filename' ),
	};

	jQuery.post( ppom_file_vars.ajaxurl, post_data, function ( resp ) {
		//console.log( resp );
		jQuery( 'form.cart' ).unblock();
	} );
}

// Once an upload finishes, create the Croppie preview that feeds the hidden
// cropped-image payload later submitted with the add-to-cart request.
function ppom_show_cropped_preview(
	file_name,
	image_url,
	image_id,
	file_input
) {
	const cropp_preview_container = jQuery(
		'.ppom-croppie-wrapper-' + file_name
	);
	// Enable size option
	cropp_preview_container
		.find( '.ppom-cropping-size' )
		.prop( 'disabled', false );
	cropp_preview_container.find( '.ppom-cropping-size' ).show();

	const container_width = jQuery(
		'#ppom-file-container-' + file_name
	).width();
	const croppie_container = jQuery( '<div/>' )
		.addClass( 'ppom-croppie-preview-' + image_id )
		.attr( 'data-image_id', image_id )
		.appendTo( cropp_preview_container );

	// Change preview image
	jQuery( '<a/>' )
		.addClass( 'btn ' + image_id )
		.attr( 'href', 'javascript:;' )
		.attr( 'id', 'selectfiles-' + file_name + '-' + image_id )
		.attr( 'data-field-name', file_name )
		.attr( 'data-image-id', image_id )
		.html( 'Change image' )
		.appendTo( cropp_preview_container )
		.click( function ( e ) {
			e.preventDefault();
		} );

	const file_inputs = {
		...file_input,
		data_name: file_name + '-' + image_id,
		is_change_image: true,
		original_data_name: file_name,
	};
	// Cropper's "change image" re-upload keeps the document-wide default: its
	// own multi-form scoping is a separate, cropper-specific gap (unlike the
	// plain 'file' field flow above, this path isn't wired to a $scope).
	ppom_setup_file_upload_input( file_inputs, jQuery( document ) );

	// file_list_preview_containers[file_name]['croppie'] = cropp_preview_container.find('.ppom-croppie-preview');

	jQuery( croppie_container ).on(
		'update.croppie',
		function ( ev, cropData ) {
			// console.log(cropData);
			// croppie_container.croppie('result', 'rawcanvas').then(function(canvas) {
			// console.log(canvas);

			ppom_generate_cropper_data_for_cart( file_name );

			jQuery.event.trigger( {
				type: 'ppom_croppie_update',
				img_id: image_id,
				croppie_obj: croppie_container,
				crop_data: cropData,
				dataname: file_name,
				time: new Date(),
			} );
		}
	);

	file_list_preview_containers[ file_name ].croppie[ image_id ] =
		croppie_container;
	file_list_preview_containers[ file_name ].image_id = image_id;
	file_list_preview_containers[ file_name ].image_url = image_url;

	file_list_preview_containers[ file_name ].container_width = container_width;
	ppom_set_croppie_options( file_name, undefined, image_id );
}

function ppom_set_croppie_options( file_name, viewport, image_id ) {
	const croppie_options = ppom_file_vars.croppie_options;
	jQuery.each( croppie_options, function ( field_name, option ) {
		if ( file_name === field_name ) {
			// Deep-copy the shared options object so we never mutate the
			// original — repeated size changes would otherwise accumulate stale
			// url / viewport values from a previous call.
			const workingOption = jQuery.extend( true, {}, option );
			workingOption.url =
				file_list_preview_containers[ file_name ].image_url;
			if ( viewport !== undefined ) {
				viewport.type = workingOption.viewport.type;
				workingOption.viewport = viewport;
			}

			const preview = file_list_preview_containers[ file_name ];
			let applied = workingOption;
			if (
				preview.container_width !== undefined &&
				preview.container_width !== null
			) {
				applied = get_responsive_croppie_options(
					workingOption,
					preview.container_width
				);
			}
			// console.log($filelist_DIV[file_name]['croppie'][image_id]);
			preview.croppie[ image_id ].croppie( applied );
		}
	} );
}

// Reset cropping when image removed
function ppom_reset_cropping_preview( file_name ) {
	const cropp_preview_container = jQuery(
		'.ppom-croppie-wrapper-' + file_name
	);
	// Reseting preview DOM
	cropp_preview_container.find( '.ppom-croppie-preview' ).html( '' );
}

/**
 * Attach one Plupload instance per PPOM file/cropper field and keep the
 * resulting hidden checkbox inputs compatible with the price/validation stack.
 *
 * @param {PPOMUploadFieldMeta} file_input
 * @return {void}
 */
function ppom_setup_file_upload_input( file_input, $scope ) {
	const file_inputs = file_input;
	const parts = file_input.data_name.split( '-' );
	const [ file_data_name, file_id ] = parts;
	let data_name = file_data_name;

	if ( file_id !== undefined ) {
		data_name = file_data_name + '-' + file_id;
	}

	// Two PPOM forms on one page can render the same field data_name and the
	// same DOM ids (issue #735's file-upload counterpart) — $scope is this
	// call's own `.ppom-wrapper`, and every internal registry key below is
	// suffixed with its product id so the two forms get independent
	// uploaders instead of the second silently no-op'ing against the first's.
	$scope = $scope && $scope.length ? $scope : jQuery( document );
	const scope_product_id = $scope.find( '[name="ppom_product_id"]' ).val();
	const key_suffix = scope_product_id ? '__' + scope_product_id : '';
	const instance_key = file_data_name + key_suffix;
	const instance_full_key = data_name + key_suffix;

	if ( plupload_instances[ instance_full_key ] !== undefined ) {
		return;
	}

	if (
		! Object.prototype.hasOwnProperty.call(
			field_file_count,
			instance_key
		)
	) {
		field_file_count[ instance_key ] = 0;
	}
	file_list_preview_containers[ instance_key ] = $scope.find(
		'#filelist-' + file_data_name
	);

	// Energy pack
	const bar = window.document.getElementById(
		`ppom-progressbar-${ file_data_name }`
	);

	const ppom_file_data = {
		action: 'ppom_upload_file',
		data_name: file_data_name,
		ppom_nonce: ppom_file_vars.ppom_file_upload_nonce,
		product_id: scope_product_id || ppom_file_vars.product_id,
	};

	let img_dim_errormsg = 'Please upload correct image dimension';
	if ( file_input.img_dimension_error ) {
		img_dim_errormsg = file_input.img_dimension_error;
	}

	const $browseButton = $scope.find( '#selectfiles-' + data_name );
	const $container = $scope.find( '#ppom-file-container-' + file_data_name );

	plupload_instances[ instance_key ] = new plupload.Uploader( {
		runtimes: ppom_file_vars.plupload_runtime,
		// Pass the scoped DOM elements themselves rather than the bare id
		// strings: plupload resolves a string via `document.getElementById`,
		// which always finds the *first* of two forms' identically-id'd
		// buttons/containers — binding both uploaders to the same node.
		browse_button: $browseButton[ 0 ],
		container: $container[ 0 ],
		drop_element: $container[ 0 ],
		url: ppom_file_vars.ajaxurl,
		multipart_params: ppom_file_data,
		max_file_size: file_input.file_size,
		max_file_count: parseInt( file_input.files_allowed ),
		unique_names: ppom_file_vars.enable_file_rename,
		chunk_size: file_input.chunk_size || '1mb',

		filters: {
			mime_types: [
				{ title: 'Filetypes', extensions: file_input.file_types || 'jpg,png,gif' },
			],
		},

		init: {
			PostInit() {
				// Plupload's HTML5 runtime injects a real <input type="file">
				// (invisible, stacked over the "Select files" button) into our
				// `container` element with no accessible name of its own.
				// Give it one now that it exists. Cropper's "Change image"
				// flow runs this same setup a second time against the same
				// container (see ppom_show_cropped_preview), so a plain
				// querySelector here would keep re-labelling the first
				// (original) input and leave the second one unnamed --
				// each PostInit only ever runs after the shim it belongs to
				// has just been appended, so the *last* match in the
				// container is always this instance's own input.
				const containerFileInputs = $container[ 0 ]
					? $container[ 0 ].querySelectorAll( 'input[type="file"]' )
					: [];
				const nativeFileInput =
					containerFileInputs[ containerFileInputs.length - 1 ];
				if ( nativeFileInput ) {
					// Read from what's actually rendered rather than
					// `file_input.title`: that's the raw stored metadata,
					// not the translated/filtered/escaped text a sighted
					// shopper sees. For the normal case the field legend
					// (e.g. "Upload Your Design") is the useful name --
					// the chooser button's own text is just the generic
					// default "Select files". For cropper's "Change image"
					// uploader (is_change_image) it's the other way round:
					// the button text is the specific name and the legend
					// is the shared, less-useful field title.
					const chooserButton = $browseButton[ 0 ];
					const legend = $container[ 0 ]
						? $container[ 0 ].querySelector( ':scope > legend' )
						: null;
					const primaryName = file_input.is_change_image
						? chooserButton?.textContent.trim()
						: legend?.textContent.trim();
					const fallbackName = file_input.is_change_image
						? legend?.textContent.trim()
						: chooserButton?.textContent.trim();
					const accessibleName =
						primaryName || fallbackName || 'Select files';
					nativeFileInput.setAttribute( 'aria-label', accessibleName );
				}

				// file_list_preview_containers[instance_key].html('');
				if (
					! file_list_preview_containers[ instance_key ].is(
						':visible'
					)
				) {
					jQuery( document ).on( 'ppom_field_shown', function ( e ) {
						// e.scope (see ppom-conditions-v2.js) is this specific
						// form's `.ppom-wrapper` when available, so a field
						// shown in one PPOM form doesn't get set up against
						// another form's identically-named field/container.
						const $shownScope =
							e.scope && e.scope.length
								? e.scope
								: jQuery( document );
						jQuery.each(
							ppom_input_vars.ppom_inputs,
							function ( index, file_input ) {
								if (
									file_input &&
									( file_input.type === 'file' ||
										file_input.type === 'cropper' )
								) {
									if (
										file_input.data_name &&
										file_input.files_allowed &&
										file_input.file_size &&
										file_input.files_allowed
									) {
										ppom_setup_file_upload_input(
											file_input,
											$shownScope
										);
									}
								}
							}
						);
					} );
				}
				/*$('#uploadfiles-'+file_data_name).bind('click', function() {
                	upload_instance[file_data_name].start();
                	return false;
                });*/
			},

			FilesAdded( up, files ) {
				// Adding progress bar
				const file_pb = jQuery( '<div/>' )
					.addClass( 'progress' )
					.css( 'width', '100%' )
					.css( 'clear', 'both' )
					.css( 'margin', '5px auto' )
					.appendTo( file_list_preview_containers[ instance_key ] );
				const file_pb_runner = jQuery( '<div/>' )
					.addClass( 'progress-bar' )
					.attr( 'role', 'progressbar' )
					.attr( 'aria-valuenow', 0 )
					.attr( 'aria-valuemin', 0 )
					.attr( 'aria-valuemax', 100 )
					.css( 'height', '15px' )
					.css( 'width', 0 )
					.appendTo( file_pb );

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

				if ( file_id !== undefined ) {
					--field_file_count[ instance_key ];
				}

				if (
					field_file_count[ instance_key ] + files_added >
					plupload_instances[ instance_key ].settings.max_file_count
				) {
					alert(
						plupload_instances[ instance_key ].settings
							.max_file_count +
							ppom_file_vars.mesage_max_files_limit
					);
				} else {
					if ( file_id !== undefined ) {
						jQuery( '.ppom-croppie-preview-' + file_id )
							.hide( 500 )
							.remove();
						jQuery( `.btn.${ file_id }` ).hide( 500 ).remove();
						jQuery( '#u_i_c_' + file_id )
							.hide( 500 )
							.remove();
						jQuery(
							`input[name="ppom[fields][${ file_data_name }][${ file_data_name }][cropped]"]`
						)
							.hide( 500 )
							.remove();
					}

					plupload.each( files, function ( file ) {
						if (
							file.type.indexOf( 'image' ) !== -1 &&
							file.type !== 'image/photoshop'
						) {
							const img = new moxie.image.Image();
							img.load = function () {
								const img_height = this.height;
								const img_width = this.width;

								const aspect_ratio =
									Math.max( img_width, img_height ) /
									Math.min( img_width, img_height );

								if (
									img_width >=
										parseFloat( file_input.max_img_w ) ||
									img_width <=
										parseFloat( file_input.min_img_w ) ||
									img_height >=
										parseFloat( file_input.max_img_h ) ||
									img_height <=
										parseFloat( file_input.min_img_h )
								) {
									up.removeFile( file );
									alert( img_dim_errormsg );
								} else {
									field_file_count[ instance_key ]++;
									// Code to add pending file details, if you want
									add_thumb_box(
										file,
										file_list_preview_containers[
											instance_key
										],
										up
									);
									up.start();
								}
							};
							img.load( file.getSource() );
						} else {
							field_file_count[ instance_key ]++;
							// Code to add pending file details, if you want
							add_thumb_box(
								file,
								file_list_preview_containers[ instance_key ],
								up
							);
							up.start();
						}

						// Energy pack
						if ( bar ) {
							bar.removeAttribute( 'hidden' );
							bar.max = file.size;
							bar.value = file.loaded;
						}
					} );
				}
			},

			FileUploaded( up, file, info ) {
				const obj_resp = jQuery.parseJSON( info.response );

				if ( obj_resp.file_name === 'ThumbNotFound' ) {
					plupload_instances[ instance_key ].removeFile( file.id );
					jQuery( '#u_i_c_' + file.id )
						.hide( 500 )
						.remove();
					field_file_count[ instance_key ]--;

					alert( 'There is some error please try again' );
					return;
				} else if ( obj_resp.status === 'error' ) {
					plupload_instances[ instance_key ].removeFile( file.id );

					jQuery( '#u_i_c_' + file.id )
						.hide( 500 )
						.remove();

					field_file_count[ instance_key ]--;
					alert( obj_resp.message );
					return;
				}

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

				file_list_preview_containers[ instance_key ]
					.find( '#u_i_c_' + file.id )
					.html( obj_resp.html )
					.trigger( {
						type: 'ppom_image_ready',
						image: file,
						data_name: file_data_name,
						input_type: file_input.type,
						image_url: obj_resp.file_url,
						image_resp: obj_resp,
						file_input: file_inputs,
						time: new Date(),
					} );

				// checking if uploaded file is thumb
				const ext = obj_resp.file_name
					.substring( obj_resp.file_name.lastIndexOf( '.' ) + 1 )
					.toLowerCase();

				if (
					ext === 'png' ||
					ext === 'gif' ||
					ext === 'jpg' ||
					ext === 'jpeg'
				) {
					const file_full =
						ppom_file_vars.file_upload_path + obj_resp.file_name;
					// thumb thickbox only shown if it is image
					file_list_preview_containers[ instance_key ]
						.find( '#u_i_c_' + file.id )
						.find( '.u_i_c_thumb' )
						.append(
							'<div style="display:none" id="u_i_c_big' +
								file.id +
								'"><img src="' +
								file_full +
								'" /></div>'
						);

					// Aviary editing tools
					if (
						file_input.photo_editing === 'on' &&
						ppom_file_vars.aviary_api_key !== ''
					) {
						const editing_tools = file_input.editing_tools;
						file_list_preview_containers[ instance_key ]
							.find( '#u_i_c_' + file.id )
							.find( '.u_i_c_tools_edit' )
							.append(
								'<a onclick="return   (\'thumb_' +
									file.id +
									"', '" +
									file_full +
									"', '" +
									obj_resp.file_name +
									"', '" +
									editing_tools +
									'\')" href="javascript:;" title="Edit"><img width="15" src="' +
									ppom_file_vars.plugin_url +
									'/images/edit.png" /></a>'
							);
					}
				} else {
					file_thumb = ppom_file_vars.plugin_url + '/images/file.png';
					file_list_preview_containers[ instance_key ]
						.find( '#u_i_c_' + file.id )
						.find( '.u_i_c_thumb' )
						.html(
							'<img src="' +
								file_thumb +
								'" id="thumb_' +
								file.id +
								'" />'
						);
				}

				// adding checkbox input to Hold uploaded file name as array
				const file_container = file_list_preview_containers[
					instance_key
				].find( '#u_i_c_' + file.id );
				let input_class = 'ppom-input';
				input_class +=
					file_input.required === 'on' ? ' ppom-required' : '';

				ppom_remember_delete_token(
					obj_resp.file_name,
					obj_resp.delete_token
				);

				// Add file check
				jQuery(
					'<input checked="checked" name="ppom[fields][' +
						file_data_name +
						'][' +
						file.id +
						'][org]" type="checkbox"/>'
				)
					.attr( 'data-price', file_input.file_cost )
					.attr( 'data-label', obj_resp.file_name )
					// Proof this visitor uploaded the file, replayed on delete. Also
					// kept in the browser so it survives leaving this page.
					.attr( 'data-delete-token', obj_resp.delete_token || '' )
					.attr( 'data-data_name', file_input.data_name )
					.attr( 'data-title', file_input.title )
					.attr( 'data-onetime', file_input.onetime )
					.val( obj_resp.file_name )
					.css( 'display', 'none' )
					.addClass( 'ppom-file-cb-' + file_data_name )
					.addClass( 'ppom-file-cb' )
					.addClass( input_class )
					.appendTo( file_container )
					.trigger( 'change' );

				ppom_update_option_prices();

				jQuery( 'form.cart' ).unblock();
				isCartBlock = false;

				// Removing progressbar
				file_list_preview_containers[ instance_key ]
					.find( '.progress' )
					.remove();

				if ( bar ) {
					setTimeout( function () {
						bar.setAttribute( 'hidden', 'hidden' );
					}, 1000 );
					bar.max = file.size;
					bar.value = file.loaded;
				}

				// Trigger
				jQuery.event.trigger( {
					type: 'ppom_file_uploaded',
					file,
					file_meta: file_input,
					file_resp: obj_resp,
					time: new Date(),
				} );
			},

			async BeforeUpload( up, file ) {
				// Refresh nonces before upload to handle stale nonces in cached pages
				try {
					await ppom_refresh_file_nonces();
					// Update the multipart_params with the fresh nonce
					up.setOption( 'multipart_params', {
						action: 'ppom_upload_file',
						data_name: file_data_name,
						ppom_nonce: ppom_file_vars.ppom_file_upload_nonce,
						product_id: ppom_file_vars.product_id,
					} );
				} catch ( error ) {
					// Log warning but continue with existing nonce
					console.warn(
						'Failed to refresh upload nonce, using existing:',
						error
					);
				}
			},

			UploadProgress( up, file ) {
				// Energy pack
				if ( bar ) {
					bar.max = file.size;
					bar.value = file.loaded;
				}

				file_list_preview_containers[ instance_key ]
					.find( '.progress-bar' )
					.css( 'width', file.percent + '%' );

				//disabling add to cart button for a while
				if ( ! isCartBlock ) {
					jQuery( 'form.cart' ).block( {
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6,
							onBlock() {
								isCartBlock = true;
							},
						},
					} );
				}
			},

			Error( up, err ) {
				if ( -600 === err.code && file_input.file_size ) {
					alert(
						ppom_file_vars.max_file_size.replace(
							'%s',
							file_input.file_size.toUpperCase()
						)
					);
				} else if ( -601 === err.code && file_input.file_types ) {
					alert( ppom_file_vars.invalid_file_type );
				} else if ( -602 === err.code ) {
					alert( ppom_file_vars.duplicate_file );
				} else {
					alert( 'Error #' + err.code + ': ' + err.message );
				}
			},
		},
	} );

	// console.log('running file', upload_instance[file_data_name]);
	plupload_instances[ instance_key ].init();
	uploaderInstances[ instance_key ] = plupload_instances[ instance_key ];
}

// Persist the Croppie canvas output into hidden inputs so PHP can rebuild the
// edited image from the same request payload used for normal uploaded files.
function ppom_generate_cropper_data_for_cart( field_name ) {
	const cropp_preview_container = jQuery(
		'.ppom-croppie-wrapper-' + field_name
	);

	cropp_preview_container
		.find( '.croppie-container' )
		.each( function ( i, croppie_dom ) {
			const image_id = jQuery( croppie_dom ).attr( 'data-image_id' );
			jQuery( croppie_dom )
				.croppie( 'result', {
					type: 'rawcanvas',
					// size: { width: 300, height: 300 },
					size: 'original',
					format: 'png',
				} )
				.then( function ( canvas ) {
					const image_url = canvas.toDataURL();
					//console.log(image_url);
					// remove first
					jQuery(
						`input[name="ppom[fields][${ field_name }][${ image_id }][cropped]"]`
					).remove();

					// Add file check
					jQuery(
						'<input checked="checked" name="ppom[fields][' +
							field_name +
							'][' +
							image_id +
							'][cropped]" type="checkbox"/>'
					)
						.val( image_url )
						.css( 'display', 'none' )
						.appendTo( file_list_preview_containers[ field_name ] );
				} );
		} );
}

/**
 * Scale Croppie boundary/viewport when the field container is narrower than
 * configured dimensions (responsive cropper).
 *
 * @param {Object} baseOptions Croppie options object for the field.
 * @param {number} max_width  Container width in pixels.
 * @return {Object} Options safe to pass to .croppie() without mutating globals.
 */
function get_responsive_croppie_options( baseOptions, max_width ) {
	const boundary = baseOptions.boundary || {};

	const boundaryWidthOriginal = Number( boundary.width );
	const boundaryHeightOriginal = Number( boundary.height );
	if (
		! Number.isFinite( boundaryWidthOriginal ) ||
		! Number.isFinite( boundaryHeightOriginal ) ||
		boundaryWidthOriginal <= 0 ||
		boundaryHeightOriginal <= 0
	) {
		return baseOptions;
	}
	if ( max_width >= boundaryWidthOriginal ) {
		return baseOptions;
	}
	if ( ! Number.isFinite( max_width ) ) {
		return baseOptions;
	}
	const aspectRatio = boundaryHeightOriginal / boundaryWidthOriginal || 1;
	const boundaryWidth = Math.floor( max_width );
	const boundaryHeight = Math.floor( boundaryWidth * aspectRatio );

	const result = {
		...baseOptions,
		boundary: {
			...baseOptions.boundary,
			width: boundaryWidth,
			height: boundaryHeight,
		},
	};

	if (
		baseOptions.viewport &&
		baseOptions.viewport.width &&
		baseOptions.viewport.height
	) {
		const scale = boundaryWidth / boundaryWidthOriginal;
		const viewportWidth = Math.floor( baseOptions.viewport.width * scale );
		const viewportHeight = Math.floor(
			baseOptions.viewport.height * scale
		);

		result.viewport = {
			...baseOptions.viewport,
			width: Math.min( viewportWidth, boundaryWidth - 2 ),
			height: Math.min( viewportHeight, boundaryHeight - 2 ),
		};
	}

	return result;
}
