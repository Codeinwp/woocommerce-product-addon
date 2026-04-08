/**
 * Renders schema-defined settings in labeled sections (for typed field editors).
 */
import { Box, Text, VStack } from '@chakra-ui/react';
import { ResponsiveFieldGrid } from '../ResponsiveFieldGrid';

/**
 * @param {Object}   props
 * @param {Object}   props.schema
 * @param {Object}   props.values
 * @param {Function} props.onChange
 * @param {Object}   props.i18n
 * @param {number}   props.ppomFieldIndex
 * @param {Object}   [props.modalContext]  e.g. { builderFields, conditionsProEnabled } for conditions UI.
 * @param {Array<{ label: string, keys: string[] }>} props.sections
 */
export function GroupedFieldSections( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext = null,
	sections,
} ) {
	const settings =
		schema && schema.settings && typeof schema.settings === 'object'
			? schema.settings
			: {};
	const ctx = {
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		...( modalContext && typeof modalContext === 'object'
			? modalContext
			: {} ),
	};

	return (
		<VStack align="stretch" spacing={ 3 }>
			{ sections.map( ( sec ) => {
				const entries = sec.keys
					.filter( ( k ) => settings[ k ] && typeof settings[ k ] === 'object' )
					.map( ( k ) => ( { key: k, meta: settings[ k ] } ) );
				if ( entries.length === 0 ) {
					return null;
				}
				return (
					<Box
						key={ sec.label }
						bg="white"
						borderWidth="1px"
						borderColor="gray.200"
						borderRadius="md"
						px={ { base: 3, md: 4 } }
						py={ 3 }
						boxShadow="0 1px 2px rgba(0, 0, 0, 0.04)"
					>
						<Text
							as="h3"
							fontSize="11px"
							fontWeight="700"
							color="gray.500"
							textTransform="uppercase"
							letterSpacing="0.08em"
							mb={ 3 }
							pb={ 1.5 }
							borderBottomWidth="1px"
							borderBottomColor="gray.100"
						>
							{ sec.label }
						</Text>
						<ResponsiveFieldGrid entries={ entries } ctx={ ctx } />
					</Box>
				);
			} ) }
		</VStack>
	);
}
