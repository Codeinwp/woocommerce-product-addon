// @ts-check

/**
 * Shared confirmation/error popup used by PPOM admin screens.
 *
 * Unlike the field-builder inline modals, this is a single programmatic popup
 * instance exposed on `window.ppomPopup` so list-table and settings actions can
 * reuse the same confirmation UX.
 *
 * @see js/admin/ppom-meta-table.js
 * @see js/admin/ppom-admin.js
 */

/**
 * @typedef {{
 *   title?: string,
 *   text?: string,
 *   hideCloseBtn?: boolean,
 *   type?: string,
 *   onConfirmation?: () => void,
 *   onClose?: () => void
 * }} PpomPopupOptions
 */
class PpomPopup {
	constructor() {
		this.overlay = document.createElement( 'div' );
		this.overlay.classList.add( 'ppom-popup-overlay' );

		// Close on outside click.
		this.overlay.addEventListener( 'click', ( event ) => {
			if ( event.target === this.overlay ) {
				this.close();
			}
		} );

		this.popup = document.createElement( 'div' );
		this.popup.classList.add( 'ppom-popup' );

		this.container = document.createElement( 'div' );
		this.container.classList.add( 'ppom-popup-container' );

		this.title = document.createElement( 'h2' );
		this.title.classList.add( 'ppom-popup-title' );

		this.text = document.createElement( 'p' );
		this.text.classList.add( 'ppom-popup-text' );

		const containerActions = document.createElement( 'div' );
		containerActions.classList.add( 'ppom-popup-actions' );

		this.confirmButton = document.createElement( 'button' );
		this.confirmButton.classList.add( 'ppom-btn-confirm' );
		this.confirmButton.textContent =
			window.ppom_vars.i18n.popup.confirmationBtn;
		this.confirmButton.addEventListener(
			'click',
			this.confirm.bind( this )
		);

		this.cancelButton = document.createElement( 'button' );
		this.cancelButton.classList.add( 'ppom-btn-cancel' );
		this.cancelButton.textContent = window.ppom_vars.i18n.popup.cancelBtn;
		this.cancelButton.addEventListener( 'click', this.close.bind( this ) );

		containerActions.appendChild( this.cancelButton );
		containerActions.appendChild( this.confirmButton );

		this.container.appendChild( this.title );
		this.container.appendChild( this.text );
		this.container.appendChild( containerActions );
		this.popup.appendChild( this.container );
		this.overlay.appendChild( this.popup );

		/** @type {() => void} */
		this.onConfirmation = () => {};
		/** @type {() => void} */
		this.onClose = () => {};
	}

	/**
	 * Open the shared admin popup with optional callbacks and copy.
	 *
	 * @param {PpomPopupOptions} [options]
	 * @return {void}
	 */
	open( options = {} ) {
		if ( options?.title ) {
			this.title.innerHTML = options.title;
		}

		if ( options?.text ) {
			this.text.innerHTML = options.text;
		}

		if ( options?.onConfirmation ) {
			this.onConfirmation = options.onConfirmation;
		}

		if ( options?.onClose ) {
			this.onClose = options.onClose;
		}

		this.cancelButton.classList.toggle(
			'ppom-hide',
			Boolean( options?.hideCloseBtn )
		);
		this.text.classList.toggle(
			'ppom-hide',
			Boolean( options?.text?.length )
		);
		this.popup.classList.toggle( 'ppom-error', 'error' === options?.type );

		this.show();
	}

	close() {
		this.hide();
		this.onClose?.();
	}

	confirm() {
		this.hide();
		this.onConfirmation?.();
	}

	show() {
		document.body.appendChild( this.overlay );
	}

	hide() {
		document.body.removeChild( this.overlay );
	}
}

window.addEventListener( 'DOMContentLoaded', () => {
	/**
	 * @type {import('../global.d.ts').Popup}
	 */
	window.ppomPopup = new PpomPopup();
} );
