/**
 * Grouped field type picker with Pro locks and optional upsell sidebar.
 */
import { useMemo } from '@wordpress/element';
import {
	Box,
	Button,
	Flex,
	HStack,
	Input,
	SimpleGrid,
	Tabs,
	TabList,
	Tab,
	TabPanels,
	TabPanel,
	Text,
	VStack,
	Badge,
} from '@chakra-ui/react';

/**
 * @param {Object}   props
 * @param {Array}    props.catalogGroups REST catalog_groups
 * @param {string}   props.query         Search string
 * @param {Function} props.onQueryChange
 * @param {Function} props.onPick        ( slug: string ) => void
 * @param {Object}   props.upsell        REST upsell or null
 * @param {Object}   props.license       REST license
 * @param {Object}   props.i18n
 */
export function FieldTypePicker( {
	catalogGroups,
	query,
	onQueryChange,
	onPick,
	upsell,
	license,
	i18n,
} ) {
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

	return (
		<Flex align="flex-start" gap={ 0 } flexWrap={ { base: 'wrap', lg: 'nowrap' } }>
			<Box flex="1" minW={ 0 } pr={ showUpsell ? { lg: 6 } : 0 }>
				<VStack align="stretch" spacing={ 4 }>
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
						<TabList flexWrap="wrap" gap={ 1 } mb={ 4 }>
							<Tab px={ 4 }>{ i18n.allTab }</Tab>
							{ ( catalogGroups || [] ).map( ( g ) => (
								<Tab key={ g.id } px={ 4 }>
									{ g.label }
								</Tab>
							) ) }
						</TabList>
						<TabPanels>
							<TabPanel px={ 0 }>
								{ filteredGroups.length === 0 && (
									<Text color="gray.600">{ i18n.noTypesMatch }</Text>
								) }
								{ filteredGroups.map( ( group ) => (
									<Box key={ group.id } mb={ 8 }>
										<Text fontWeight="semibold" mb={ 3 } fontSize="md">
											{ group.label }
										</Text>
										<FieldGroupGrid
											fields={ group.fields }
											onPick={ onPick }
											upsellUrl={ upsell && upsell.cta_url ? upsell.cta_url : '' }
											i18n={ i18n }
										/>
									</Box>
								) ) }
							</TabPanel>
							{ ( catalogGroups || [] ).map( ( group ) => {
								const fg = filteredGroups.find( ( x ) => x.id === group.id );
								const fields = fg ? fg.fields : [];
								return (
									<TabPanel key={ group.id } px={ 0 }>
										{ fields.length === 0 ? (
											<Text color="gray.600">{ i18n.noTypesMatch }</Text>
										) : (
											<FieldGroupGrid
												fields={ fields }
												onPick={ onPick }
												upsellUrl={
													upsell && upsell.cta_url ? upsell.cta_url : ''
												}
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

			{ showUpsell && (
				<Box
					flex="0 0 280px"
					w={ { base: '100%', lg: '280px' } }
					borderLeftWidth={ { base: 0, lg: '1px' } }
					borderTopWidth={ { base: '1px', lg: 0 } }
					borderColor="gray.200"
					pl={ { base: 0, lg: 6 } }
					pt={ { base: 6, lg: 0 } }
					mt={ { base: 4, lg: 0 } }
				>
					<VStack
						align="center"
						spacing={ 4 }
						p={ 4 }
						bg="gray.50"
						borderRadius="lg"
					>
						<Flex
							w={ 14 }
							h={ 14 }
							borderRadius="full"
							bg="blue.500"
							align="center"
							justify="center"
							color="white"
						>
							<span
								className="dashicons dashicons-lock"
								style={ { fontSize: 28, width: 28, height: 28 } }
								aria-hidden
							/>
						</Flex>
						<Text fontWeight="bold" fontSize="lg" textAlign="center">
							{ upsell.title }
						</Text>
						<Text fontSize="sm" color="gray.700" textAlign="center">
							{ upsell.intro }
						</Text>
						<VStack align="stretch" spacing={ 2 } w="full" fontSize="sm">
							{ ( upsell.features || [] ).map( ( line, i ) => (
								<HStack key={ i } align="flex-start">
									<Text color="green.500" fontWeight="bold">
										✓
									</Text>
									<Text>{ line }</Text>
								</HStack>
							) ) }
						</VStack>
						<Button
							as="a"
							href={ upsell.cta_url }
							target="_blank"
							rel="noopener noreferrer"
							width="full"
							colorScheme="green"
							size="lg"
							borderRadius="md"
						>
							{ upsell.cta_label }
						</Button>
					</VStack>
				</Box>
			) }
		</Flex>
	);
}

function FieldGroupGrid( { fields, onPick, upsellUrl, i18n } ) {
	return (
		<SimpleGrid columns={ { base: 2, sm: 3, md: 3 } } spacing={ 3 }>
			{ fields.map( ( f ) => {
				const locked = Boolean( f.locked );
				const iconClass = f.icon ? `fa ${ f.icon }` : 'fa fa-circle';
				return (
					<Button
						key={ f.slug }
						variant="outline"
						height="auto"
						minH="56px"
						py={ 3 }
						px={ 3 }
						flexDir="column"
						justifyContent="center"
						gap={ 2 }
						whiteSpace="normal"
						textAlign="center"
						bg={ locked ? 'white' : 'blue.500' }
						color={ locked ? 'gray.800' : 'white' }
						borderColor={ locked ? 'gray.300' : 'blue.600' }
						_hover={ {
							bg: locked ? 'gray.50' : 'blue.600',
						} }
						onClick={ () => {
							if ( locked && upsellUrl ) {
								window.open( upsellUrl, '_blank', 'noopener,noreferrer' );
								return;
							}
							if ( ! locked ) {
								onPick( f.slug );
							}
						} }
					>
						<HStack justify="center" spacing={ 2 } w="full">
							<Text
								as="span"
								className={ iconClass }
								aria-hidden
								fontSize="lg"
								lineHeight="1"
							/>
							<Text fontWeight="semibold" fontSize="sm">
								{ f.title }
							</Text>
							{ locked ? (
								<Badge colorScheme="green" fontSize="0.65rem">
									{ i18n.proBadge }
								</Badge>
							) : null }
						</HStack>
						{ f.description ? (
							<Text
								fontSize="xs"
								color={ locked ? 'gray.600' : 'whiteAlpha.900' }
								noOfLines={ 2 }
							>
								{ f.description }
							</Text>
						) : null }
					</Button>
				);
			} ) }
		</SimpleGrid>
	);
}
