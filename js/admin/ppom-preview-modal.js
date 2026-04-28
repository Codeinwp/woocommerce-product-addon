/* global ppom_vars */
'use strict';

jQuery( function ( $ ) {
	// Cache modal DOM nodes once; all preview interactions reuse these handles.
	const previewModal = $( '#ppom-live-preview-modal' );
	const previewSelect = $( '#ppom-preview-product-select' );
	const previewIframe = $( '#ppom-live-preview-iframe' );
	const previewWrap = previewModal.find( '.ppom-preview-iframe-wrap' );
	const previewErrorNotice = previewModal.find( '.ppom-preview-notice-error' );
	const previewWarningNotice = previewModal.find(
		'.ppom-preview-notice-warning'
	);
	const previewEmptyMessage = previewWarningNotice.find(
		'.ppom-preview-empty-message'
	);
	const previewGoToAssignmentBtn = previewWarningNotice.find(
		'.ppom-preview-go-to-assignment'
	);
	const previewRefreshBtn = previewModal.find( '.ppom-preview-refresh' );
	const savePreviewBtn = $( '.ppom-save-and-preview' );
	const savePreviewTooltipAnchor = $( '.ppom-save-preview-tooltip-anchor' );

	let shouldOpenPreviewAfterSave = false;
	let previewSelectInitialized = false;
	let previewCurrentUrl = '';
	let shouldIgnorePreviewSelectChange = false;
	let previewPpomId = parseInt( ppom_vars?.preview?.ppomId || 0, 10 );
	const previewAssignedProducts = Array.isArray(
		ppom_vars?.preview?.assignedProducts
	)
		? ppom_vars.preview.assignedProducts
		: [];

	function setPreviewMessage( type, message ) {
		// Ensure only one state is visible at a time: iframe, warning, or error.
		previewWrap.addClass( 'ppom-hide-element' );
		previewErrorNotice.addClass( 'ppom-hide-element' ).empty();
		previewWarningNotice.addClass( 'ppom-hide-element' );

		if ( type === 'warning' ) {
			previewEmptyMessage.text( message );
			previewWarningNotice.removeClass( 'ppom-hide-element' );
			return;
		}

		previewErrorNotice.text( message ).removeClass( 'ppom-hide-element' );
	}

	function setSavePreviewAvailabilityState( enabled, hintMessage ) {
		if ( ! savePreviewBtn.length ) {
			return;
		}

		savePreviewBtn.prop( 'disabled', ! enabled );
		savePreviewBtn.attr( 'aria-disabled', ! enabled ? 'true' : 'false' );
		if ( enabled ) {
			savePreviewTooltipAnchor.removeAttr( 'title' );
		} else {
			savePreviewTooltipAnchor.attr( 'title', hintMessage || '' );
		}
	}

	function checkSavePreviewAvailability() {
		if ( ! savePreviewBtn.length || ! previewPpomId ) {
			return;
		}

		setSavePreviewAvailabilityState(
			false,
			ppom_vars.i18n.previewCheckingAvailability
		);

		const requestBody = new URLSearchParams();
		requestBody.append( 'action', 'ppom_get_preview_url' );
		requestBody.append( 'nonce', ppom_vars.preview.nonce );
		requestBody.append( 'ppom_id', String( previewPpomId ) );

		fetch( ajaxurl, {
			method: 'POST',
			body: requestBody,
		} )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				if ( response?.status === 'success' && response?.preview_url ) {
					setSavePreviewAvailabilityState( true, '' );
					return;
				}

				setSavePreviewAvailabilityState(
					false,
					response?.message || ppom_vars.i18n.previewDisabledHint
				);
			} )
			.catch( () => {
				setSavePreviewAvailabilityState(
					false,
					ppom_vars.i18n.previewDisabledHint
				);
			} );
	}

	function upsertPreviewOption( product ) {
		const productId = parseInt( product?.id || 0, 10 );
		const productText = product?.text || '';

		if ( ! productId || ! productText || ! previewSelect.length ) {
			return;
		}

		if ( previewSelect.find( 'option[value="' + productId + '"]' ).length ) {
			return;
		}

		const option = new Option( productText, String( productId ), false, false );
		previewSelect.append( option );
	}

	function loadPreviewByProduct( productId = '' ) {
		if ( ! previewModal.length || ! previewPpomId ) {
			return;
		}

		// Show immediate feedback while URL is being resolved server-side.
		setPreviewMessage( 'error', ppom_vars.i18n.previewLoading );

		const requestBody = new URLSearchParams();
		requestBody.append( 'action', 'ppom_get_preview_url' );
		requestBody.append( 'nonce', ppom_vars.preview.nonce );
		requestBody.append( 'ppom_id', String( previewPpomId ) );
		if ( productId ) {
			requestBody.append( 'product_id', String( productId ) );
		}

		fetch( ajaxurl, {
			method: 'POST',
			body: requestBody,
		} )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				// Backend can return an empty-state code when no eligible product exists.
				if (
					response?.status !== 'success' ||
					! response?.preview_url
				) {
					const fallbackMessage =
						response?.code === 'ppom_preview_no_product'
							? ppom_vars.i18n.previewDisabledHint
							: ppom_vars.i18n.previewNoUrl;
					setPreviewMessage(
						response?.code === 'ppom_preview_no_product'
							? 'warning'
							: 'error',
						fallbackMessage
					);
					return;
				}

				previewCurrentUrl = response.preview_url;
				previewIframe.attr( 'src', previewCurrentUrl );
				previewWrap.removeClass( 'ppom-hide-element' );
				previewErrorNotice.addClass( 'ppom-hide-element' );
				previewWarningNotice.addClass( 'ppom-hide-element' );

				if ( response.product ) {
					// Keep dropdown in sync with the product used by the backend.
					upsertPreviewOption( response.product );
					shouldIgnorePreviewSelectChange = true;
					previewSelect
						.val( String( response.product.id ) )
						.trigger( 'change.select2' );
					shouldIgnorePreviewSelectChange = false;
				}
			} )
			.catch( () => {
				setPreviewMessage( 'error', ppom_vars.i18n.previewNoUrl );
			} );
	}

	function initializePreviewSelect() {
		if ( previewSelectInitialized || ! previewSelect.length ) {
			return;
		}

		// Preload only direct assignments; full catalog is loaded on demand via AJAX search.
		previewAssignedProducts.forEach( ( product ) => {
			upsertPreviewOption( product );
		} );

		if ( typeof $.fn.select2 === 'function' ) {
			previewSelect.select2( {
				placeholder: ppom_vars.i18n.previewSelectPlaceholder,
				width: '100%',
				ajax: {
					url: ajaxurl,
					dataType: 'json',
					delay: 250,
					data: function ( params ) {
						return {
							action: 'ppom_search_products',
							nonce: ppom_vars.preview.nonce,
							term: params.term || '',
						};
					},
					processResults: function ( data ) {
						return {
							results: Array.isArray( data?.results )
								? data.results
								: [],
						};
					},
				},
			} );
		}

		previewSelect.on( 'change', function () {
			if ( shouldIgnorePreviewSelectChange ) {
				return;
			}
			const selectedProduct = $( this ).val();
			if ( selectedProduct ) {
				loadPreviewByProduct( selectedProduct );
			}
		} );

		previewSelectInitialized = true;
	}

	function openLivePreviewModal( savedProductMetaId ) {
		if ( ! previewModal.length ) {
			return;
		}

		const parsedSavedMetaId = parseInt( savedProductMetaId || 0, 10 );
		if ( parsedSavedMetaId > 0 ) {
			// New groups become editable IDs after first save; keep runtime ID current.
			previewPpomId = parsedSavedMetaId;
			ppom_vars.preview.ppomId = parsedSavedMetaId;
			checkSavePreviewAvailability();
		}

		$( 'body' ).append(
			"<div class='ppom-modal-overlay ppom-js-modal-close'></div>"
		);
		previewModal.fadeIn();
		initializePreviewSelect();

		// Priority: currently selected option -> first assigned option -> backend auto-resolve.
		const selectedValue = previewSelect.val();
		if ( selectedValue ) {
			loadPreviewByProduct( selectedValue );
			return;
		}

		if ( previewAssignedProducts.length > 0 ) {
			const firstAssigned = previewAssignedProducts[ 0 ];
			shouldIgnorePreviewSelectChange = true;
			previewSelect
				.val( String( firstAssigned.id ) )
				.trigger( 'change.select2' );
			shouldIgnorePreviewSelectChange = false;
			loadPreviewByProduct( firstAssigned.id );
			return;
		}

		loadPreviewByProduct();
	}

	$( '.ppom-save-and-preview' ).on( 'click', function ( e ) {
		e.preventDefault();
		// Mark intent and delegate to the existing save submit flow.
		shouldOpenPreviewAfterSave = true;
		$( '.ppom-save-fields-meta' ).trigger( 'submit' );
	} );

	previewRefreshBtn.on( 'click', function ( e ) {
		e.preventDefault();
		if ( previewCurrentUrl ) {
			previewIframe.attr( 'src', previewCurrentUrl );
			return;
		}

		const selectedProduct = previewSelect.val();
		loadPreviewByProduct( selectedProduct || '' );
	} );

	previewGoToAssignmentBtn
		.text( ppom_vars.i18n.previewGoToAssignment )
		.on( 'click', function ( e ) {
			e.preventDefault();
			const attachBtn = document.querySelector( '.ppom-products-modal' );
			if ( ! attachBtn ) {
				return;
			}
			attachBtn.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			attachBtn.classList.add( 'ppom-preview-assignment-highlight' );
			setTimeout( () => {
				attachBtn.classList.remove( 'ppom-preview-assignment-highlight' );
			}, 1800 );
		} );

	window.ppomPreviewModal = {
		// Called by ppom-admin.js right before the normal redirect logic.
		consumeSaveAndPreviewIntent() {
			const currentValue = shouldOpenPreviewAfterSave;
			shouldOpenPreviewAfterSave = false;
			return currentValue;
		},
		open: openLivePreviewModal,
	};

	document.addEventListener( 'ppom:attachments-updated', function () {
		checkSavePreviewAvailability();
	} );

	checkSavePreviewAvailability();
} );
