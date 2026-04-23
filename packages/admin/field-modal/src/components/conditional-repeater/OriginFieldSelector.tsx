import { Field, NativeSelect, Text } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';
import type { OriginCandidate } from '../../utils/conditionalRepeaterData';

export interface OriginFieldSelectorProps {
	origin: string;
	candidates: OriginCandidate[];
	i18n: I18nDict;
	onChange: ( nextOrigin: string ) => void;
}

export function OriginFieldSelector( {
	origin,
	candidates,
	i18n,
	onChange,
}: OriginFieldSelectorProps ) {
	return (
		<Field.Root mb={ 4 }>
			<Field.Label fontSize="sm">
				{ i18n.cfrOriginLabel || 'Origin' }
			</Field.Label>
			<NativeSelect.Root>
				<NativeSelect.Field
					size="sm"
					bg="white"
					value={ origin }
					placeholder={
						i18n.cfrOriginPlaceholder || 'Select origin field…'
					}
					onValueChange={ ( e ) => onChange( e.target.value ) }
				>
					<option value="">{ i18n.cfrOriginNone || 'None' }</option>
					{ candidates.map( ( c ) => (
						<option key={ c.value } value={ c.value }>
							{ c.title } ({ c.value })
						</option>
					) ) }
				</NativeSelect.Field>
				<NativeSelect.Indicator />
			</NativeSelect.Root>
			<Text fontSize="xs" color="gray.600" mt={ 1 } lineHeight="1.5">
				{ i18n.cfrOriginHelp ||
					'Only Number, Variation Quantity, and Quantity Pack fields can be the origin.' }
			</Text>
		</Field.Root>
	);
}
