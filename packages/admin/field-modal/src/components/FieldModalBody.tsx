/**
 * Modal body: loading, errors, picker step, or manage step.
 */
import { ModalBody } from '@chakra-ui/react';
import { HStack, Text, Spinner, Alert, AlertIcon } from '@chakra-ui/react';
import { FieldPickerPanel } from './FieldPickerPanel';
import { FieldManagePanel } from './FieldManagePanel';
import type { FieldModalBodyProps } from '../types/fieldModal';

export function FieldModalBody( {
	status,
	ctx,
	pickerOpen,
	picker,
	manage,
}: FieldModalBodyProps ) {
	const { loading, error } = status;
	return (
		<ModalBody
			flex="1"
			overflowY="auto"
			minH={ 0 }
			py={ 2 }
			px={ { base: 3, md: 4 } }
		>
			{ loading && (
				<HStack py={ 8 } justify="center">
					<Spinner size="md" />
					<Text>{ picker.i18n.loading || 'Loading…' }</Text>
				</HStack>
			) }
			{ error && (
				<Alert status="error" mb={ 3 } borderRadius="md">
					<AlertIcon />
					{ error }
				</Alert>
			) }
			{ ! loading && ctx && pickerOpen && (
				<FieldPickerPanel { ...picker } />
			) }
			{ ! loading && ctx && ! pickerOpen && (
				<FieldManagePanel { ...manage } />
			) }
		</ModalBody>
	);
}
