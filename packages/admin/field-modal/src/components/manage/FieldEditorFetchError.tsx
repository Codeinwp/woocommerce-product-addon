import { Alert } from '@chakra-ui/react';

export interface FieldEditorFetchErrorProps {
	message: string;
}

export function FieldEditorFetchError( { message }: FieldEditorFetchErrorProps ) {
	return (
		<Alert.Root status="error" borderRadius="md">
			<Alert.Indicator />
			{ message }
		</Alert.Root>
	);
}
