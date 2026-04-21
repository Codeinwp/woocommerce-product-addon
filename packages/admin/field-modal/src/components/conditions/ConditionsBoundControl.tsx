import { Field, HStack, NativeSelect, Text } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';
import { controlSurface, labelProps } from './styles';

export interface ConditionsBoundControlProps {
	visibility: string;
	bound: string;
	i18n: I18nDict;
	onVisibilityChange: ( next: string ) => void;
	onBoundChange: ( next: string ) => void;
}

export function ConditionsBoundControl( {
	visibility,
	bound,
	i18n,
	onVisibilityChange,
	onBoundChange,
}: ConditionsBoundControlProps ) {
	return (
		<HStack align="flex-end" flexWrap="wrap" gap={ 2 }>
			<Field.Root maxW="200px">
				<Field.Label { ...labelProps }>
					{ i18n.condShowHide || 'Show / Hide' }
				</Field.Label>
				<NativeSelect.Root>
					<NativeSelect.Field
						size="sm"
						value={ visibility }
						onValueChange={ ( e ) =>
							onVisibilityChange( e.target.value )
						}
						{ ...controlSurface }
					>
						<option value="Show">
							{ i18n.condShow || 'Show' }
						</option>
						<option value="Hide">
							{ i18n.condHide || 'Hide' }
						</option>
					</NativeSelect.Field>
					<NativeSelect.Indicator />
				</NativeSelect.Root>
			</Field.Root>
			<Text fontSize="sm" color="gray.600" pb={ 2 }>
				{ i18n.condOnlyIf || 'only if' }
			</Text>
			<Field.Root maxW="200px">
				<Field.Label { ...labelProps }>
					{ i18n.condAllAny || 'All / Any' }
				</Field.Label>
				<NativeSelect.Root>
					<NativeSelect.Field
						size="sm"
						value={ bound }
						onValueChange={ ( e ) => onBoundChange( e.target.value ) }
						{ ...controlSurface }
					>
						<option value="All">{ i18n.condAll || 'All' }</option>
						<option value="Any">{ i18n.condAny || 'Any' }</option>
					</NativeSelect.Field>
					<NativeSelect.Indicator />
				</NativeSelect.Root>
			</Field.Root>
			<Text fontSize="sm" color="gray.600" pb={ 2 }>
				{ i18n.condFollowingMatches || 'of the following match' }
			</Text>
		</HStack>
	);
}
