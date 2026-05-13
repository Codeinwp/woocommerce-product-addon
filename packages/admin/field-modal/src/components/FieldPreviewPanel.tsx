/**
 * Field Guide sidebar panel. Shows an empty-state hint when no tile is hovered,
 * or a rich preview (title + long description + examples/features/notRightFor)
 * when a tile is hovered or focused in the Field Type picker.
 */
import { Box, HStack, Text, VStack } from '@chakra-ui/react';
import type { CatalogItem, I18nDict } from '../types/fieldModal';
import { getFieldGuide } from '../definitions/fieldGuides';

export interface FieldPreviewPanelProps {
	field: CatalogItem | null;
	i18n: I18nDict;
}

export function FieldPreviewPanel( { field, i18n }: FieldPreviewPanelProps ) {
	const regionLabel = i18n.fieldGuideRegion || 'Field guide';

	if ( ! field ) {
		return (
			<Box
				role="complementary"
				aria-label={ regionLabel }
				aria-live="polite"
				p={ 3 }
				bg="gray.50"
				borderRadius="lg"
			>
				<HStack align="center" gap={ 2 } mb={ 1 }>
					<Text
						as="span"
						className="fa fa-info-circle"
						aria-hidden
						color="blue.500"
						fontSize="md"
					/>
					<Text fontWeight="bold" fontSize="md" minW={ 0 } m={ 0 }>
						{ i18n.fieldGuideEmptyTitle }
					</Text>
				</HStack>
				<Text fontSize="sm" color="gray.700">
					{ i18n.fieldGuideEmptyBody }
				</Text>
			</Box>
		);
	}

	const guide = getFieldGuide( field.slug );
	const iconClass = field.icon ? `fa ${ field.icon }` : 'fa fa-circle';
	const title = String( field.title || field.slug );
	const longDescription = guide?.longDescription || field.description || '';

	return (
		<Box
			role="complementary"
			aria-label={ regionLabel }
			aria-live="polite"
			p={ 3 }
			bg="white"
			borderRadius="lg"
			borderWidth="1px"
			borderColor="gray.200"
		>
			<HStack align="center" gap={ 2 } mb={ 2 }>
				<Text
					as="span"
					className={ iconClass }
					aria-hidden
					color="blue.500"
					fontSize="lg"
				/>
				<Text fontWeight="bold" fontSize="md" minW={ 0 } m={ 0 }>
					{ title }
				</Text>
			</HStack>
			<VStack
				align="stretch"
				gap={ 4 }
				fontSize="sm"
				color="gray.800"
				css={ { '& > p': { margin: 0, lineHeight: 1.5 } } }
			>
				{ longDescription ? <Text>{ longDescription }</Text> : null }
				{ guide?.examples ? (
					<Text>
						<Text as="span" fontWeight="bold">
							{ i18n.examplesLabel }
						</Text>{ ' ' }
						{ guide.examples }
					</Text>
				) : null }
				{ guide?.features ? (
					<Text>
						<Text as="span" fontWeight="bold">
							{ i18n.featuresLabel }
						</Text>{ ' ' }
						{ guide.features }
					</Text>
				) : null }
				{ guide?.notRightFor ? (
					<Text>
						<Text as="span" fontWeight="bold">
							{ i18n.notRightForLabel }
						</Text>{ ' ' }
						{ guide.notRightFor }
					</Text>
				) : null }
			</VStack>
		</Box>
	);
}
