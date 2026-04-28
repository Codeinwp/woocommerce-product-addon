/**
 * Renders schema-defined settings in labeled sections or one compact flat group.
 */
import { Text, VStack } from '@chakra-ui/react';
import { ResponsiveFieldGrid } from '../ResponsiveFieldGrid';
import type { GroupedFieldSectionsProps } from '../types/fieldModal';

function sectionEntries(
	sections: Array< { label: string; keys: string[] } >,
	settings: Record< string, Record< string, unknown > >
) {
	return sections.map( ( sec: { label: string; keys: string[] } ) => ( {
		label: sec.label,
		entries: sec.keys
			.filter(
				( k: string ) =>
					settings[ k ] && typeof settings[ k ] === 'object'
			)
			.map( ( k: string ) => ( {
				key: k,
				meta: settings[ k ],
			} ) ),
	} ) );
}

export function GroupedFieldSections( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	form,
	modalContext = null,
	sections,
	variant = 'sectioned',
}: GroupedFieldSectionsProps ) {
	const settings: Record< string, Record< string, unknown > > = schema &&
	schema.settings &&
	typeof schema.settings === 'object'
		? ( schema.settings as Record< string, Record< string, unknown > > )
		: {};
	const ctx = {
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		form,
		...( modalContext && typeof modalContext === 'object'
			? modalContext
			: {} ),
	};
	const groups = sectionEntries( sections, settings );

	if ( variant === 'flat' ) {
		const entries = groups.flatMap( ( group ) => group.entries );

		if ( entries.length === 0 ) {
			return null;
		}

		return (
			<VStack
				align="stretch"
				gap={ 2 }
				minW={ 0 }
				bg="white"
				borderRadius="md"
				px={ { base: 2.5, md: 3 } }
				py={ 2.5 }
			>
				<ResponsiveFieldGrid entries={ entries } ctx={ ctx } />
			</VStack>
		);
	}

	return (
		<VStack align="stretch" gap={ 3 }>
			{ groups.map( ( { label, entries } ) => {
				if ( entries.length === 0 ) {
					return null;
				}
				return (
					<VStack
						key={ label }
						align="stretch"
						gap={ 2 }
						minW={ 0 }
						bg="white"
						borderRadius="md"
						px={ { base: 2.5, md: 3 } }
						py={ 2.5 }
					>
						<Text
							as="h3"
							fontSize="11px"
							fontWeight="700"
							color="gray.500"
							textTransform="uppercase"
							letterSpacing="0.08em"
							mb={ 0 }
							pb={ 1 }
							borderBottomWidth="1px"
							borderBottomColor="gray.100"
						>
							{ label }
						</Text>
						<ResponsiveFieldGrid entries={ entries } ctx={ ctx } />
					</VStack>
				);
			} ) }
		</VStack>
	);
}
