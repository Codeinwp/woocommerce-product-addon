/**
 * Grid of field type tiles (free vs locked Pro) for the type picker.
 */
import { Fragment, memo } from '@wordpress/element';
import { Button, SimpleGrid, Text } from '@chakra-ui/react';
import { Tooltip } from './ui/tooltip';
import { FieldTypeProBadge } from './FieldTypeProBadge';
import { firstSentence, getFieldGuide } from '../definitions/fieldGuides';
import type { CatalogItem, I18nDict } from '../types/fieldModal';

export interface FieldTypeGridProps {
	fields: CatalogItem[];
	onPick: ( slug: string ) => void;
	upsellUrl: string;
	i18n: I18nDict;
	onFieldHover?: ( field: CatalogItem | null ) => void;
}

function FieldTypeGridComponent( {
	fields,
	onPick,
	upsellUrl,
	i18n,
	onFieldHover,
}: FieldTypeGridProps ) {
	return (
		<SimpleGrid columns={ { base: 2, sm: 3, md: 3 } } gap="15px">
			{ fields.map( ( f: CatalogItem ) => {
				const locked = Boolean( f.locked );
				const iconClass = f.icon ? `fa ${ f.icon }` : 'fa fa-circle';
				const phpDesc = f.description
					? String( f.description ).trim()
					: '';
				const guide = getFieldGuide( f.slug );
				const tooltipText = guide
					? firstSentence( guide.longDescription )
					: phpDesc;
				const ariaLabel = tooltipText
					? `${ f.title }. ${ tooltipText }`
					: String( f.title || f.slug );

				const button = (
					<Button
						variant="outline"
						height="auto"
						minH="unset"
						p={ 2 }
						pr={ locked ? 11 : 2 }
						position={ locked ? 'relative' : undefined }
						overflow={ locked ? 'visible' : undefined }
						flexDir="row"
						justifyContent="flex-start"
						alignItems="center"
						gap="10px"
						whiteSpace="normal"
						textAlign="left"
						bg={ locked ? 'white' : 'blue.500' }
						color={ locked ? 'gray.800' : 'white' }
						borderColor={ locked ? 'gray.300' : 'blue.600' }
						borderRadius="md"
						fontSize="xs"
						fontWeight="600"
						_hover={ {
							bg: locked ? 'gray.50' : 'blue.600',
						} }
						aria-label={ ariaLabel }
						onMouseEnter={ () => onFieldHover?.( f ) }
						onMouseLeave={ () => onFieldHover?.( null ) }
						onFocus={ () => onFieldHover?.( f ) }
						onBlur={ () => onFieldHover?.( null ) }
						onClick={ () => {
							if ( locked && upsellUrl ) {
								window.open(
									upsellUrl,
									'_blank',
									'noopener,noreferrer'
								);
								return;
							}
							if ( ! locked ) {
								onPick( f.slug );
							}
						} }
					>
						<Text
							as="span"
							className={ iconClass }
							aria-hidden
							fontSize="md"
							lineHeight="1"
							flexShrink={ 0 }
						/>
						<Text
							as="span"
							flex="1"
							minW={ 0 }
							fontWeight={ locked ? '400' : 'semibold' }
							fontSize="xs"
							whiteSpace="nowrap"
							overflow="hidden"
							textOverflow="ellipsis"
						>
							{ f.title }
						</Text>
						{ locked ? (
							<FieldTypeProBadge label={ i18n.proBadge } />
						) : null }
					</Button>
				);

				return (
					<Fragment key={ f.slug }>
						{ tooltipText ? (
							<Tooltip
								content={ tooltipText }
								openDelay={ 300 }
								showArrow
								positioning={ { placement: 'top' } }
							>
								{ button }
							</Tooltip>
						) : (
							button
						) }
					</Fragment>
				);
			} ) }
		</SimpleGrid>
	);
}

export const FieldTypeGrid = memo( FieldTypeGridComponent );
