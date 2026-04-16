/**
 * Grouped field type picker with Pro locks and optional upsell sidebar.
 */
import { useMemo } from '@wordpress/element';
import { Box, Flex, HStack, Input, Tabs, Text, VStack } from '@chakra-ui/react';
import { FieldTypeGrid } from './components/FieldTypeGrid';
import { FieldTypeUpsellSidebar } from './components/FieldTypeUpsellSidebar';
import { catalogGroupStableKey } from './utils/catalogGroupKeys';
import type {
	CatalogGroup,
	I18nDict,
	LicensePayload,
	ModalUpsellPayload,
} from './types/fieldModal';

/** Tab value for the combined "All groups" view (Chakra v3 `Tabs` uses string values). */
const ALL_GROUPS_TAB_VALUE = '__ppom_all_groups__';

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
				<VStack align="stretch" gap={ 3 }>
					<HStack flexWrap="wrap" gap={ 3 }>
						<Text fontWeight="bold" fontSize="lg">
							{ i18n.selectFieldType }
						</Text>
						<Input
							flex="1"
							minW="200px"
							size="md"
							placeholder={ i18n.searchFieldTypes }
							value={ query }
							onChange={ ( e ) =>
								onQueryChange( e.currentTarget.value )
							}
						/>
					</HStack>

					<Tabs.Root
						variant="subtle"
						colorPalette="blue"
						lazyMount
						defaultValue={ ALL_GROUPS_TAB_VALUE }
					>
						<Tabs.List flexWrap="wrap" gap={ 1 } mb={ 3 }>
							<Tabs.Trigger value={ ALL_GROUPS_TAB_VALUE } px={ 4 }>
								{ i18n.allTab }
							</Tabs.Trigger>
							{ ( catalogGroups || [] ).map( ( g, tabIdx ) => (
								<Tabs.Trigger
									key={ catalogGroupStableKey( g, tabIdx ) }
									value={ catalogGroupStableKey( g, tabIdx ) }
									px={ 4 }
								>
									{ g.label }
								</Tabs.Trigger>
							) ) }
						</Tabs.List>
						<Tabs.ContentGroup>
							<Tabs.Content value={ ALL_GROUPS_TAB_VALUE } px={ 0 }>
								{/*
								 * Single wrapper: `Tabs.Content` is forwardAsChild; multiple
								 * siblings (empty-state + mapped groups) break slot merging.
								 */}
								<Box w="100%">
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
								</Box>
							</Tabs.Content>
							{ ( catalogGroups || [] ).map( ( group, panelIdx ) => {
								const fg = filteredGroups.find( ( x ) => x.id === group.id );
								const groupFields = fg ? fg.fields : [];
								return (
									<Tabs.Content
										key={ catalogGroupStableKey( group, panelIdx ) }
										value={ catalogGroupStableKey( group, panelIdx ) }
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
									</Tabs.Content>
								);
							} ) }
						</Tabs.ContentGroup>
					</Tabs.Root>
				</VStack>
			</Box>
            { showUpsell && upsell ? (
				<FieldTypeUpsellSidebar upsell={ upsell } />
			) : null }
        </Flex>
    );
}
