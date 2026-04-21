/**
 * Binds classic PPOM admin triggers to the React field modal opener.
 */

export type FieldModalEntryMode = 'picker' | 'manage';

export interface FieldModalOpenPayload {
	entry: FieldModalEntryMode;
	selectFieldIndex?: number;
}

export interface BindPpomReactFieldModalOpenOptions {
	onOpen: ( payload: FieldModalOpenPayload ) => void;
}

/**
 * Subscribes to `.ppom-react-field-modal-open` header buttons and to per-row
 * `.ppom-edit-field` buttons in the classic field table. The per-row click is
 * delegated at the document level in the capture phase so it runs before the
 * jQuery handlers that would otherwise open the legacy inline modal.
 *
 * @returns Cleanup that removes listeners.
 */
export function bindPpomReactFieldModalOpenButtons(
	opts: BindPpomReactFieldModalOpenOptions
): () => void {
	const headerButtons = document.querySelectorAll(
		'.ppom-react-field-modal-open'
	);
	const headerHandlers: Array< { btn: Element; onClick: () => void } > = [];
	headerButtons.forEach( ( btn ) => {
		const onClick = () => {
			const mode = btn.getAttribute( 'data-ppom-react-mode' );
			const fromPicker = mode === 'picker';
			opts.onOpen( { entry: fromPicker ? 'picker' : 'manage' } );
		};
		btn.addEventListener( 'click', onClick );
		headerHandlers.push( { btn, onClick } );
	} );

	const onEditFieldClickCapture = ( event: Event ) => {
		const target = event.target as HTMLElement | null;
		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}
		const btn = target.closest(
			'.ppom-edit-field'
		) as HTMLElement | null;
		if ( ! btn || btn.classList.contains( 'ppom-is-pro-field' ) ) {
			return;
		}
		event.preventDefault();
		event.stopImmediatePropagation();
		const rawId = btn.getAttribute( 'id' );
		const parsed = rawId ? parseInt( rawId, 10 ) : NaN;
		opts.onOpen( {
			entry: 'manage',
			selectFieldIndex:
				Number.isFinite( parsed ) && parsed > 0 ? parsed : undefined,
		} );
	};
	document.addEventListener( 'click', onEditFieldClickCapture, true );

	return () => {
		headerHandlers.forEach( ( { btn, onClick } ) =>
			btn.removeEventListener( 'click', onClick )
		);
		document.removeEventListener(
			'click',
			onEditFieldClickCapture,
			true
		);
	};
}
