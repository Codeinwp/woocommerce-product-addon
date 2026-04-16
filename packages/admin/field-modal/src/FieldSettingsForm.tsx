/**
 * Schema-driven field settings (fallback when no typed editor is registered).
 */
import { useMemo } from '@wordpress/element';
import {
	Box,
	Text,
	Button,
	VStack,
	Alert,
	AlertIcon,
	List,
	ListItem,
} from '@chakra-ui/react';
import { classifySettingTab } from './schemaTabs';
import { renderSettingRow, openLegacyFieldModal } from './schemaSettingControl';
import { ResponsiveFieldGrid } from './ResponsiveFieldGrid';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from './SettingsConditionsTabs';
import { GroupedFieldSections } from './editors/GroupedFieldSections';
import { buildFallbackGroupedSections } from './fieldSettingSectionBlueprint';
import { isReactModalExcludedSchemaKey } from './schema/reactModalExcludedKeys';
import { ConditionalRepeaterSection } from './components/ConditionalRepeaterSection';
import type { FieldSettingsFormProps } from './types/fieldModal';

export function FieldSettingsForm( {
	schema,
	values,
	onChange,
	fieldType,
	i18n,
	ppomFieldIndex,
	modalContext = null,
}: FieldSettingsFormProps ) {
	const buckets = useMemo( () => {
		const settings: Record< string, unknown > =
			schema && schema.settings && typeof schema.settings === 'object'
				? ( schema.settings as Record< string, unknown > )
				: {};
		const conditions: Array< {
			key: string;
			meta: Record< string, unknown >;
		} > = [];
		const unsupported: Array< {
			key: string;
			meta: Record< string, unknown >;
		} > = [];
		Object.keys( settings ).forEach( ( key ) => {
			if ( isReactModalExcludedSchemaKey( key ) ) {
				return;
			}
			const meta = settings[ key ];
			if ( ! meta || typeof meta !== 'object' ) {
				return;
			}
			const metaRecord = meta as Record< string, unknown >;
			const bucket = classifySettingTab( key, metaRecord, fieldType );
			const entry = { key, meta: metaRecord };
			if ( bucket === 'conditions' ) {
				conditions.push( entry );
			} else if ( bucket === 'unsupported' ) {
				unsupported.push( entry );
			}
		} );
		return { conditions, unsupported };
	}, [ schema, fieldType ] );

	const fallbackSections = useMemo(
		() => buildFallbackGroupedSections( schema, fieldType, i18n ),
		[ schema, fieldType, i18n ]
	);

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
	const showRepeaterTab = shouldShowConditionalRepeaterTab( modalContext );

	const unsupportedAlert =
		buckets.unsupported.length > 0 ? (
			<Alert status="warning" variant="subtle" borderRadius="md">
				<AlertIcon />
				<Box>
					<Text fontSize="sm" lineHeight="1.5">
						{ i18n.legacyEditorHint }
					</Text>
					<List fontSize="sm" mt={ 2 } spacing={ 1 } pl={ 4 } styleType="disc">
						{ buckets.unsupported.map( ( { key, meta } ) => {
							const t =
								meta.title != null
									? String( meta.title )
									: key;
							return (
								<ListItem key={ key }>
									{ t }{ ' ' }
									<Text as="span" color="gray.500" fontSize="xs">
										({ key })
									</Text>
								</ListItem>
							);
						} ) }
					</List>
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

	const groupedEditorProps = {
		schema,
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		modalContext,
	};

	const settingsPanel = (
		<VStack align="stretch" spacing={ 3 }>
			{ unsupportedAlert }
			<GroupedFieldSections
				{ ...groupedEditorProps }
				sections={ fallbackSections }
			/>
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
			hasRepeater={ showRepeaterTab }
			repeater={
				<ConditionalRepeaterSection
					i18n={ i18n }
					modalContext={ modalContext }
					values={ values }
					onChange={ onChange }
				/>
			}
		/>
	);

	return body;
}
