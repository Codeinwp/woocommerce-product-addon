/**
 * Binds classic PPOM admin triggers to the React field modal opener.
 */

export type FieldModalEntryMode = 'picker' | 'manage';

export interface BindPpomReactFieldModalOpenOptions {
	onOpen: ( entry: FieldModalEntryMode ) => void;
}

/**
 * Subscribes to `.ppom-react-field-modal-open` buttons (data-ppom-react-mode).
 *
 * @returns Cleanup that removes listeners.
 */
export function bindPpomReactFieldModalOpenButtons(
	opts: BindPpomReactFieldModalOpenOptions
): () => void {
	const buttons = document.querySelectorAll( '.ppom-react-field-modal-open' );
	if ( ! buttons.length ) {
		return () => undefined;
	}
	const handlers: Array< { btn: Element; onClick: () => void } > = [];
	buttons.forEach( ( btn ) => {
		const onClick = () => {
			const mode = btn.getAttribute( 'data-ppom-react-mode' );
			const fromPicker = mode === 'picker';
			opts.onOpen( fromPicker ? 'picker' : 'manage' );
		};
		btn.addEventListener( 'click', onClick );
		handlers.push( { btn, onClick } );
	} );
	return () => {
		handlers.forEach( ( { btn, onClick } ) =>
			btn.removeEventListener( 'click', onClick )
		);
	};
}
