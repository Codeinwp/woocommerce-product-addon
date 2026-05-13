/**
 * Gate a close action behind a two-step confirm so accidental
 * Esc / outside-click / Cancel button presses don't discard work.
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

const CONFIRM_TIMEOUT_MS = 5000;

export interface UseConfirmCloseOptions {
	requireConfirm: boolean;
	onClose: () => void;
}

export interface UseConfirmCloseResult {
	confirming: boolean;
	requestClose: () => void;
	resetConfirm: () => void;
}

export function useConfirmClose( {
	requireConfirm,
	onClose,
}: UseConfirmCloseOptions ): UseConfirmCloseResult {
	const [ confirming, setConfirming ] = useState( false );
	const timerRef = useRef< number | null >( null );

	const clearTimer = useCallback( () => {
		if ( timerRef.current !== null ) {
			window.clearTimeout( timerRef.current );
			timerRef.current = null;
		}
	}, [] );

	const resetConfirm = useCallback( () => {
		clearTimer();
		setConfirming( false );
	}, [ clearTimer ] );

	useEffect( () => clearTimer, [ clearTimer ] );

	// If the dirty signal goes away (user navigated back to the picker,
	// or the modal closed) drop any pending confirm state.
	useEffect( () => {
		if ( ! requireConfirm && confirming ) {
			resetConfirm();
		}
	}, [ requireConfirm, confirming, resetConfirm ] );

	const requestClose = useCallback( () => {
		if ( ! requireConfirm ) {
			resetConfirm();
			onClose();
			return;
		}
		if ( confirming ) {
			resetConfirm();
			onClose();
			return;
		}
		clearTimer();
		setConfirming( true );
		timerRef.current = window.setTimeout( () => {
			timerRef.current = null;
			setConfirming( false );
		}, CONFIRM_TIMEOUT_MS );
	}, [ requireConfirm, confirming, clearTimer, resetConfirm, onClose ] );

	return { confirming, requestClose, resetConfirm };
}
