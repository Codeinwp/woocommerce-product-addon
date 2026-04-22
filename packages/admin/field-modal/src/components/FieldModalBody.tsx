/**
 * Modal body: loading, errors, picker step, or manage step.
 */
import {
	Dialog,
	Alert,
	HStack,
	IconButton,
	Text,
	Spinner,
} from '@chakra-ui/react';
import { LuX } from 'react-icons/lu';
import { FieldPickerPanel } from './FieldPickerPanel';
import { FieldManagePanel } from './FieldManagePanel';
import type { FieldModalBodyProps } from '../types/fieldModal';

export function FieldModalBody( {
	status,
	onDismissError,
	ctx,
	pickerOpen,
	picker,
	manage,
}: FieldModalBodyProps ) {
	const { loading, error } = status;
	const dismissLabel = picker.i18n.dismiss || 'Dismiss';
	return (
        <Dialog.Body
			flex="1"
			overflowY="auto"
			minH={ 0 }
			bg="gray.100"
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
				<Alert.Root status="error" mb={ 3 } borderRadius="md">
					<Alert.Indicator />
					<Alert.Content flex="1">{ error }</Alert.Content>
					<IconButton
						aria-label={ dismissLabel }
						size="xs"
						variant="ghost"
						onClick={ onDismissError }
					>
						<LuX />
					</IconButton>
				</Alert.Root>
			) }
            { ! loading && ctx && pickerOpen && (
				<FieldPickerPanel { ...picker } />
			) }
            { ! loading && ctx && ! pickerOpen && (
				<FieldManagePanel { ...manage } />
			) }
        </Dialog.Body>
    );
}
