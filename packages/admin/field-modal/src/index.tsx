/**
 * Entry: React field modal (admin, opt-in).
 */
import { createRoot, StrictMode } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import { ChakraProvider } from '@chakra-ui/react';
import { App } from './App';
import { FieldModalErrorBoundary } from './components/FieldModalErrorBoundary';
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
		<StrictMode>
			<ChakraProvider theme={ fieldModalTheme }>
				<FieldModalErrorBoundary>
					<App productmetaId={ boot.productmetaId } />
				</FieldModalErrorBoundary>
			</ChakraProvider>
		</StrictMode>
	);
} );
