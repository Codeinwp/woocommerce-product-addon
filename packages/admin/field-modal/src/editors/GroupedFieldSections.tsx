/**
 * Renders schema-defined settings in labeled sections or one compact flat group.
 */
import { Fragment } from 'react';
import { Text, VStack } from '@chakra-ui/react';
import { ResponsiveFieldGrid } from '../ResponsiveFieldGrid';
import type { GroupedFieldSectionsProps } from '../types/fieldModal';

function sectionEntries(
	sections: Array< { label: string; keys: string[] } >,
	settings: Record< string, Record< string, unknown > >
) {
	return sections.map( ( sec: { label: string; keys: string[] } ) => {
		const filteredKeys = sec.keys
			.filter(
				( k: string ) =>
					settings[ k ] && typeof settings[ k ] === 'object'
			)
			.filter( ( k: string ) => {
				if ( k !== 'logic' ) {
					return true;
				}
				return ! sec.keys.includes( 'conditions' );
			} );
		const entries = filteredKeys.map( ( k: string ) => ( {
			key: k,
			meta: settings[ k ],
		} ) );
		const isConditionsOnly =
			entries.length === 1 &&
			entries[ 0 ].meta &&
			entries[ 0 ].meta.type === 'html-conditions';
		return {
			label: isConditionsOnly ? '' : sec.label,
			entries,
			isConditionsOnly: Boolean( isConditionsOnly ),
		};
	} );
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
			{ groups.map( ( { label, entries, isConditionsOnly }, idx ) => {
				if ( entries.length === 0 ) {
					return null;
				}
				const key = label || `section-${ idx }`;
				if ( isConditionsOnly ) {
					return (
						<Fragment key={ key }>
							<ResponsiveFieldGrid
								entries={ entries }
								ctx={ ctx }
							/>
						</Fragment>
					);
				}
				return (
					<VStack
						key={ key }
						align="stretch"
						gap={ 2 }
						minW={ 0 }
						bg="white"
						borderRadius="md"
						px={ { base: 2.5, md: 3 } }
						py={ 2.5 }
					>
						{ label ? (
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
						) : null }
						<ResponsiveFieldGrid entries={ entries } ctx={ ctx } />
					</VStack>
				);
			} ) }
		</VStack>
	);
}
