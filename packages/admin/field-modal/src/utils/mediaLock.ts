/**
 * Tiny reference-counted lock that tells the field modal to ignore outside /
 * Esc dismissal while a WordPress media frame is open on top of it. Clicks
 * inside `.media-modal` land on a DOM sibling of our Chakra Dialog, which Zag
 * otherwise treats as "interact outside" and uses to close the modal.
 */
import { useSyncExternalStore } from 'react';

let count = 0;
const listeners = new Set< () => void >();
let syncFrame = 0;

const emit = () => {
	listeners.forEach( ( l ) => l() );
};

const syncMediaDomState = () => {
	if ( typeof window === 'undefined' || typeof document === 'undefined' ) {
		return;
	}

	if ( count === 0 ) {
		if ( syncFrame !== 0 ) {
			window.cancelAnimationFrame( syncFrame );
			syncFrame = 0;
		}

		document.body.style.removeProperty( 'pointer-events' );
		if ( document.body.style.length === 0 ) {
			document.body.removeAttribute( 'style' );
		}
		document.body.removeAttribute( 'data-inert' );

		document
			.querySelector< HTMLElement >( '.media-modal' )
			?.style.removeProperty( 'pointer-events' );
		document
			.querySelector< HTMLElement >( '.media-modal-backdrop' )
			?.style.removeProperty( 'pointer-events' );
		return;
	}

	document.body.style.pointerEvents = 'auto';
	document.body.removeAttribute( 'data-inert' );
	document
		.querySelector< HTMLElement >( '.media-modal' )
		?.style.setProperty( 'pointer-events', 'auto' );
	document
		.querySelector< HTMLElement >( '.media-modal-backdrop' )
		?.style.setProperty( 'pointer-events', 'auto' );

	syncFrame = window.requestAnimationFrame( syncMediaDomState );
};

export function lockMediaFrame() {
	count += 1;
	if ( syncFrame === 0 ) {
		syncMediaDomState();
	}
	emit();
}

export function unlockMediaFrame() {
	if ( count === 0 ) {
		return;
	}
	count -= 1;
	syncMediaDomState();
	emit();
}

export function useMediaFrameLocked(): boolean {
	return useSyncExternalStore(
		( cb ) => {
			listeners.add( cb );
			return () => {
				listeners.delete( cb );
			};
		},
		() => count > 0,
		() => false
	);
}
