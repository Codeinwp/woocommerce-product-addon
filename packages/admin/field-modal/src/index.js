/**
 * Entry: React field modal (admin, opt-in).
 */
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import { ChakraProvider } from '@chakra-ui/react';
import { App } from './App';
import { fieldModalTheme } from './theme';

domReady( () => {
	const el = document.getElementById( 'ppom-field-modal-root' );
	const boot = window.ppomFieldModalBoot;
	if ( ! el || ! boot ) {
		return;
	}

	apiFetch.use( apiFetch.createNonceMiddleware( boot.nonce ) );

	const root = createRoot( el );
	root.render(
		<ChakraProvider theme={ fieldModalTheme }>
			<App productmetaId={ boot.productmetaId } />
		</ChakraProvider>
	);
} );
