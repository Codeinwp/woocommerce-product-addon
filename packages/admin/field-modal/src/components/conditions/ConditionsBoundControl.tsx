import { HStack, NativeSelect, Text } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';
import { controlSurface } from './styles';

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
		<HStack
			align="center"
			flexWrap="wrap"
			gap={ 2 }
			fontSize="sm"
			color="gray.700"
		>
			<NativeSelect.Root width="auto" minW="100px">
				<NativeSelect.Field
					size="sm"
					value={ visibility }
					onValueChange={ ( e ) =>
						onVisibilityChange( e.target.value )
					}
					aria-label={ i18n.condShowHide || 'Visibility' }
					{ ...controlSurface }
				>
					<option value="Show">{ i18n.condShow || 'Show' }</option>
					<option value="Hide">{ i18n.condHide || 'Hide' }</option>
				</NativeSelect.Field>
				<NativeSelect.Indicator />
			</NativeSelect.Root>
			<Text>{ i18n.condThisFieldWhen || 'this field when' }</Text>
			<NativeSelect.Root width="auto" minW="100px">
				<NativeSelect.Field
					size="sm"
					value={ bound }
					onValueChange={ ( e ) => onBoundChange( e.target.value ) }
					aria-label={ i18n.condAllAny || 'Match mode' }
					{ ...controlSurface }
				>
					<option value="All">{ i18n.condAll || 'All' }</option>
					<option value="Any">{ i18n.condAny || 'Any' }</option>
				</NativeSelect.Field>
				<NativeSelect.Indicator />
			</NativeSelect.Root>
			<Text>{ i18n.condConditionsMatch || 'conditions match.' }</Text>
		</HStack>
	);
}
