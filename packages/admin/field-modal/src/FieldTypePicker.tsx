/**
 * Grouped field type picker with Pro locks and optional upsell sidebar.
 */
import { useMemo } from '@wordpress/element';
import {
	Box,
	Flex,
	HStack,
	Input,
	Tabs,
	TabList,
	Tab,
	TabPanels,
	TabPanel,
	Text,
	VStack,
} from '@chakra-ui/react';
import { FieldTypeGrid } from './components/FieldTypeGrid';
import { FieldTypeUpsellSidebar } from './components/FieldTypeUpsellSidebar';
import { catalogGroupStableKey } from './utils/catalogGroupKeys';
import type {
	CatalogGroup,
	I18nDict,
	LicensePayload,
	ModalUpsellPayload,
} from './types/fieldModal';

export interface FieldTypePickerProps {
	catalogGroups: CatalogGroup[];
	query: string;
	onQueryChange: ( q: string ) => void;
	onPick: ( slug: string ) => void;
	upsell?: ModalUpsellPayload | null;
	license?: LicensePayload | null;
	i18n: I18nDict;
}

export function FieldTypePicker( {
	catalogGroups,
	query,
	onQueryChange,
	onPick,
	upsell,
	license,
	i18n,
}: FieldTypePickerProps ) {
	const q = query.trim().toLowerCase();

	const filteredGroups = useMemo( () => {
		if ( ! catalogGroups || ! Array.isArray( catalogGroups ) ) {
			return [];
		}
		return catalogGroups
			.map( ( group ) => {
				const fields = ( group.fields || [] ).filter( ( f ) => {
					if ( ! q ) {
						return true;
					}
					const t = String( f.title || '' ).toLowerCase();
					const s = String( f.slug || '' ).toLowerCase();
					const d = String( f.description || '' ).toLowerCase();
					return t.includes( q ) || s.includes( q ) || d.includes( q );
				} );
				return { ...group, fields };
			} )
			.filter( ( g ) => g.fields.length > 0 );
	}, [ catalogGroups, q ] );

	const showUpsell = Boolean( license && license.show_upsell && upsell && upsell.cta_url );
	const upsellUrl = upsell && upsell.cta_url ? upsell.cta_url : '';

	return (
		<Flex align="flex-start" gap={ 0 } flexWrap={ { base: 'wrap', lg: 'nowrap' } }>
			<Box flex="1" minW={ 0 } pr={ showUpsell ? { lg: 4 } : 0 }>
				<VStack align="stretch" spacing={ 3 }>
					<HStack flexWrap="wrap" spacing={ 3 }>
						<Text fontWeight="bold" fontSize="lg">
							{ i18n.selectFieldType }
						</Text>
						<Input
							flex="1"
							minW="200px"
							size="md"
							placeholder={ i18n.searchFieldTypes }
							value={ query }
							onChange={ ( e ) => onQueryChange( e.target.value ) }
						/>
					</HStack>

					<Tabs variant="soft-rounded" colorScheme="blue" isLazy>
						<TabList flexWrap="wrap" gap={ 1 } mb={ 3 }>
							<Tab px={ 4 }>{ i18n.allTab }</Tab>
							{ ( catalogGroups || [] ).map( ( g, tabIdx ) => (
								<Tab
									key={ catalogGroupStableKey( g, tabIdx ) }
									px={ 4 }
								>
									{ g.label }
								</Tab>
							) ) }
						</TabList>
						<TabPanels>
							<TabPanel px={ 0 }>
								{ filteredGroups.length === 0 && (
									<Text color="gray.600">{ i18n.noTypesMatch }</Text>
								) }
								{ filteredGroups.map( ( group, fgIdx ) => (
									<Box
										key={ catalogGroupStableKey( group, fgIdx ) }
										mb={ 6 }
									>
										<Text fontWeight="semibold" mb={ 2 } fontSize="md">
											{ group.label }
										</Text>
										<FieldTypeGrid
											fields={ group.fields }
											onPick={ onPick }
											upsellUrl={ upsellUrl }
											i18n={ i18n }
										/>
									</Box>
								) ) }
							</TabPanel>
							{ ( catalogGroups || [] ).map( ( group, panelIdx ) => {
								const fg = filteredGroups.find( ( x ) => x.id === group.id );
								const groupFields = fg ? fg.fields : [];
								return (
									<TabPanel
										key={ catalogGroupStableKey( group, panelIdx ) }
										px={ 0 }
									>
										{ groupFields.length === 0 ? (
											<Text color="gray.600">{ i18n.noTypesMatch }</Text>
										) : (
											<FieldTypeGrid
												fields={ groupFields }
												onPick={ onPick }
												upsellUrl={ upsellUrl }
												i18n={ i18n }
											/>
										) }
									</TabPanel>
								);
							} ) }
						</TabPanels>
					</Tabs>
				</VStack>
			</Box>

			{ showUpsell && upsell ? (
				<FieldTypeUpsellSidebar upsell={ upsell } />
			) : null }
		</Flex>
	);
}
