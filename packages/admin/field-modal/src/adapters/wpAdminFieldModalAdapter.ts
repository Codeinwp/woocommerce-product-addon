/**
 * Binds classic PPOM admin triggers to the React field modal opener.
 */

export type FieldModalEntryMode = 'picker' | 'manage' | 'copy';

export interface FieldModalOpenPayload {
	entry: FieldModalEntryMode;
	selectFieldIndex?: number;
}

export interface BindPpomReactFieldModalOpenOptions {
	onOpen: ( payload: FieldModalOpenPayload ) => void;
}

function parseRowFieldIndex( btn: HTMLElement ): number | undefined {
	const rawId = btn.getAttribute( 'id' );
	const parsed = rawId ? parseInt( rawId, 10 ) : NaN;
	return Number.isFinite( parsed ) && parsed > 0 ? parsed : undefined;
}

/**
 * Subscribes to `.ppom-react-field-modal-open` header buttons and to per-row
 * `.ppom-edit-field` / `.ppom_copy_field` buttons in the classic field table.
 * The per-row clicks are delegated at the document level in the capture phase
 * so they run before the jQuery handlers that would otherwise open or clone
 * the legacy inline modal.
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

	const onRowFieldClickCapture = ( event: Event ) => {
		const target = event.target as HTMLElement | null;
		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}
		const editBtn = target.closest(
			'.ppom-edit-field'
		) as HTMLElement | null;
		const copyBtn = target.closest(
			'.ppom_copy_field'
		) as HTMLElement | null;
		const btn = editBtn || copyBtn;
		if ( ! btn || btn.classList.contains( 'ppom-is-pro-field' ) ) {
			return;
		}
		event.preventDefault();
		event.stopImmediatePropagation();
		opts.onOpen( {
			entry: copyBtn ? 'copy' : 'manage',
			selectFieldIndex: parseRowFieldIndex( btn ),
		} );
	};
	document.addEventListener( 'click', onRowFieldClickCapture, true );

	return () => {
		headerHandlers.forEach( ( { btn, onClick } ) =>
			btn.removeEventListener( 'click', onClick )
		);
		document.removeEventListener( 'click', onRowFieldClickCapture, true );
	};
}
