/**
 * Catches render errors in the field modal subtree so the admin page stays usable.
 */
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { Alert, AlertIcon, Button, Text, VStack } from '@chakra-ui/react';

interface FieldModalErrorBoundaryProps {
	children: ReactNode;
}

interface FieldModalErrorBoundaryState {
	hasError: boolean;
}

export class FieldModalErrorBoundary extends Component<
	FieldModalErrorBoundaryProps,
	FieldModalErrorBoundaryState
> {
	constructor( props: FieldModalErrorBoundaryProps ) {
		super( props );
		this.state = { hasError: false };
	}

	static getDerivedStateFromError(): FieldModalErrorBoundaryState {
		return { hasError: true };
	}

	override componentDidCatch( error: Error, errorInfo: ErrorInfo ) {
		if ( typeof console !== 'undefined' && console.error ) {
			console.error( 'PPOM field modal:', error, errorInfo );
		}
	}

	override render() {
		if ( this.state.hasError ) {
			return (
				<Alert status="error" variant="subtle" borderRadius="md" my={ 4 }>
					<AlertIcon />
					<VStack align="stretch" spacing={ 2 }>
						<Text fontSize="sm">
							The field modal hit an unexpected error. Reload the page to try again.
						</Text>
						<Button
							size="sm"
							variant="outline"
							alignSelf="flex-start"
							onClick={ () => window.location.reload() }
						>
							Reload page
						</Button>
					</VStack>
				</Alert>
			);
		}
		return this.props.children;
	}
}
