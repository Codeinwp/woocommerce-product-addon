/**
 * Schema-driven field settings (fallback when no typed editor is registered).
 */
import { useMemo } from '@wordpress/element';
import { Box, Text, Button, VStack, Alert, AlertIcon } from '@chakra-ui/react';
import { classifySettingTab } from './schemaTabs';
import { renderSettingRow, openLegacyFieldModal } from './schemaSettingControl';
import { ResponsiveFieldGrid } from './ResponsiveFieldGrid';
import { SettingsConditionsTabs } from './SettingsConditionsTabs';

function sectionCardTitle( text ) {
	return (
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
			{ text }
		</Text>
	);
}

/**
 * @param {Object}   props
 * @param {Object}   props.schema      { settings: {}, tabs: {} }
 * @param {Object}   props.values      Current field row (editDraft).
 * @param {Function} props.onChange    ( next: Object ) => void
 * @param {string}   props.fieldType
 * @param {Object}   props.i18n
 * @param {number}   props.ppomFieldIndex 1-based row index for legacy modal.
 * @param {boolean}  props.isFallback    Distinct chrome for non-typed types.
 * @param {Object}   [props.modalContext] Passed into setting rows (conditions editor).
 */
export function FieldSettingsForm( {
	schema,
	values,
	onChange,
	fieldType,
	i18n,
	ppomFieldIndex,
	isFallback = false,
	modalContext = null,
} ) {
	const buckets = useMemo( () => {
		const settings =
			schema && schema.settings && typeof schema.settings === 'object'
				? schema.settings
				: {};
		const fields = [];
		const conditions = [];
		const unsupported = [];
		Object.keys( settings ).forEach( ( key ) => {
			const meta = settings[ key ];
			if ( ! meta || typeof meta !== 'object' ) {
				return;
			}
			const bucket = classifySettingTab( key, meta, fieldType );
			const entry = { key, meta };
			if ( bucket === 'conditions' ) {
				conditions.push( entry );
			} else if ( bucket === 'unsupported' ) {
				unsupported.push( entry );
			} else {
				fields.push( entry );
			}
		} );
		return { fields, conditions, unsupported };
	}, [ schema, fieldType ] );

	const ctx = {
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		...( modalContext && typeof modalContext === 'object'
			? modalContext
			: {} ),
	};

	const conditionControls = buckets.conditions.filter(
		( { meta } ) => meta.type !== 'html-conditions'
	);
	const htmlConditions = buckets.conditions.filter(
		( { meta } ) => meta.type === 'html-conditions'
	);
	const hasConditions =
		conditionControls.length > 0 || htmlConditions.length > 0;

	const unsupportedAlert =
		buckets.unsupported.length > 0 ? (
			<Alert status="warning" variant="subtle" borderRadius="md">
				<AlertIcon />
				<Box>
					<Text fontSize="sm" lineHeight="1.5">
						{ i18n.legacyEditorHint }
					</Text>
					<Button
						size="sm"
						mt={ 2 }
						variant="outline"
						colorScheme="blue"
						onClick={ () => openLegacyFieldModal( ppomFieldIndex ) }
					>
						{ i18n.openLegacyModal }
					</Button>
				</Box>
			</Alert>
		) : null;

	const fieldsCard = (
		<Box
			bg="white"
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			px={ { base: 3, md: 4 } }
			py={ 3 }
			boxShadow="0 1px 2px rgba(0, 0, 0, 0.04)"
		>
			{ ! hasConditions ? sectionCardTitle( i18n.fieldsTab ) : null }
			<ResponsiveFieldGrid entries={ buckets.fields } ctx={ ctx } />
		</Box>
	);

	const settingsPanel = (
		<VStack align="stretch" spacing={ 3 }>
			{ unsupportedAlert }
			{ fieldsCard }
		</VStack>
	);

	const conditionsPanel = (
		<Box
			bg="white"
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			px={ { base: 3, md: 4 } }
			py={ 3 }
			boxShadow="0 1px 2px rgba(0, 0, 0, 0.04)"
		>
			<VStack align="stretch" spacing={ 3 }>
				<ResponsiveFieldGrid
					entries={ conditionControls }
					ctx={ ctx }
				/>
				{ htmlConditions.map( ( { key, meta } ) =>
					renderSettingRow( key, meta, ctx )
				) }
			</VStack>
		</Box>
	);

	const body = (
		<SettingsConditionsTabs
			i18n={ i18n }
			hasConditions={ hasConditions }
			settings={ settingsPanel }
			conditions={ conditionsPanel }
		/>
	);

	if ( isFallback ) {
		return (
			<Box pt={ 1 }>
				<Text
					fontWeight="semibold"
					mb={ 3 }
					fontSize="sm"
					color="gray.600"
				>
					{ i18n.fallbackSettingsLayout ||
						'Classic-style settings (full editor coming soon)' }
				</Text>
				{ body }
			</Box>
		);
	}

	return body;
}
