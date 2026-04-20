/**
 * Renders `FieldUiDefinition` blocks (sections + widgets) inside `FieldTabs`.
 */
import { Fragment } from 'react';
import { VStack } from '@chakra-ui/react';
import { FieldTabs, type FieldTabItem } from '../panels/FieldTabs';
import { GroupedFieldSections } from '../editors/GroupedFieldSections';
import { ConditionalRepeaterSection } from './ConditionalRepeaterSection';
import { shouldShowConditionalRepeaterTab } from '../panels/shouldShowConditionalRepeaterTab';
import { renderFieldWidget } from '../widgets/registry';
import { AdvancedSettingsPanel } from './AdvancedSettingsPanel';
import type {
	FieldUiDefinition,
	FieldUiBlock,
	WidgetRenderContext,
} from '../definitions/types';
import type {
	FieldEditorBaseProps,
	FieldRow,
	ModalContextValue,
} from '../types/fieldModal';

export interface DefinitionDrivenFieldEditorProps extends FieldEditorBaseProps {
	definition: FieldUiDefinition;
	mergedBuilderFields: FieldRow[];
}

export function DefinitionDrivenFieldEditor( {
	definition,
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	form,
	modalContext = null,
	mergedBuilderFields,
}: DefinitionDrivenFieldEditorProps ) {
	const activeClientId = String( values.clientId || '' );
	const builderFields = modalContext?.builderFields ?? [];

	const widgetCtxBase: Omit< WidgetRenderContext, 'widgetProps' > = {
		activeClientId,
		field: values,
		builderFields,
		mergedBuilderFields,
		schema: schema ?? null,
		i18n,
		ppomFieldIndex,
		modalContext,
		updateField: onChange,
	};

	const shared = {
		schema,
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		form,
		modalContext,
	};

	function renderSectionCard( block: Extract< FieldUiBlock, { kind: 'section' } > ) {
		const secLabel = i18n[ block.labelKey ] || block.labelKey;
		return (
			<GroupedFieldSections
				key={ block.id }
				{ ...shared }
				sections={ [ { label: secLabel, keys: block.keys } ] }
			/>
		);
	}

	function renderSettingsTabContent( blocks: FieldUiBlock[] ) {
		const primary: Array< JSX.Element > = [];
		const advancedSections: Array< Extract< FieldUiBlock, { kind: 'section' } > > =
			[];

		blocks.forEach( ( block ) => {
			if ( block.kind === 'widget' ) {
				const ctx: WidgetRenderContext = {
					...widgetCtxBase,
					widgetProps: block.props,
				};
				primary.push(
					<Fragment key={ block.id }>
						{ renderFieldWidget( block.widget, ctx ) }
					</Fragment>
				);
				return;
			}
			if ( block.advanced ) {
				advancedSections.push( block );
				return;
			}
			primary.push( renderSectionCard( block ) );
		} );

		const showAdvanced =
			i18n.showAdvancedSettings || 'Show advanced settings';
		const hideAdvanced =
			i18n.hideAdvancedSettings || 'Hide advanced settings';

		return (
			<VStack align="stretch" gap={ 3 }>
				{ primary }
				{ advancedSections.length > 0 && (
					<AdvancedSettingsPanel
						showLabel={ showAdvanced }
						hideLabel={ hideAdvanced }
					>
						{ advancedSections.map( ( block ) =>
							renderSectionCard( block )
						) }
					</AdvancedSettingsPanel>
				) }
			</VStack>
		);
	}

	function renderPlainTabContent( blocks: FieldUiBlock[] ) {
		return (
			<VStack align="stretch" gap={ 3 }>
				{ blocks.map( ( block ) => {
					if ( block.kind === 'section' ) {
						return renderSectionCard( block );
					}
					const ctx: WidgetRenderContext = {
						...widgetCtxBase,
						widgetProps: block.props,
					};
					return (
						<Fragment key={ block.id }>
							{ renderFieldWidget( block.widget, ctx ) }
						</Fragment>
					);
				} ) }
			</VStack>
		);
	}

	const tabs: FieldTabItem[] = definition.tabs.map( ( tab ) => {
		const blocks = definition.blocks.filter( ( b ) => b.tab === tab.id );
		const label = i18n[ tab.labelKey ] || tab.labelKey;
		const content =
			tab.id === 'settings'
				? renderSettingsTabContent( blocks )
				: renderPlainTabContent( blocks );
		return { id: tab.id, label, content };
	} );

	const showRepeaterTab = shouldShowConditionalRepeaterTab(
		modalContext as ModalContextValue | null
	);
	const repeaterLabel =
		i18n.repeaterTab || i18n.cfrSectionTitle || 'Conditional Repeater';

	const withRepeater: FieldTabItem[] = showRepeaterTab
		? [
				...tabs,
				{
					id: 'repeater',
					label: repeaterLabel,
					content: (
						<ConditionalRepeaterSection
							i18n={ i18n }
							modalContext={ modalContext }
							values={ values }
							onChange={ onChange }
						/>
					),
				},
		  ]
		: tabs;

	return (
		<FieldTabs
			tabs={ withRepeater }
			defaultTabId={ definition.tabs[ 0 ]?.id }
		/>
	);
}
