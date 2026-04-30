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
import {
	AdvancedSettingsPanel,
	formatAdvancedDescription,
} from './AdvancedSettingsPanel';
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

	function renderSectionCard(
		block: Extract< FieldUiBlock, { kind: 'section' } >
	) {
		const secLabel = block.labelKey;
		return (
			<GroupedFieldSections
				key={ block.id }
				{ ...shared }
				sections={ [ { label: secLabel, keys: block.keys } ] }
			/>
		);
	}

	function renderWidgetBlock(
		block: Extract< FieldUiBlock, { kind: 'widget' } >
	) {
		const ctx: WidgetRenderContext = {
			...widgetCtxBase,
			widgetProps: block.props,
		};
		return (
			<Fragment key={ block.id }>
				{ renderFieldWidget( block.widget, ctx ) }
			</Fragment>
		);
	}

	function renderBlock( block: FieldUiBlock ) {
		if ( block.kind === 'section' ) {
			return renderSectionCard( block );
		}
		return renderWidgetBlock( block );
	}

	function renderFlatSectionBlocks(
		blocks: Array< Extract< FieldUiBlock, { kind: 'section' } > >,
		key: string
	) {
		if ( blocks.length === 0 ) {
			return null;
		}

		return (
			<GroupedFieldSections
				key={ key }
				{ ...shared }
				sections={ blocks.map( ( block ) => ( {
					label: block.labelKey,
					keys: block.keys,
				} ) ) }
				variant="flat"
			/>
		);
	}

	function renderAdvancedBlocks( blocks: FieldUiBlock[] ) {
		const rendered: Array< JSX.Element > = [];
		let pendingSections: Array<
			Extract< FieldUiBlock, { kind: 'section' } >
		> = [];

		function flushPendingSections( nextKey: string ) {
			const flatSections = renderFlatSectionBlocks(
				pendingSections,
				nextKey
			);
			if ( flatSections ) {
				rendered.push( flatSections );
			}
			pendingSections = [];
		}

		blocks.forEach( ( block, index ) => {
			if ( block.kind === 'section' ) {
				pendingSections.push( block );
				return;
			}

			flushPendingSections( `advanced-sections-before-${ block.id }` );
			rendered.push( renderWidgetBlock( block ) );

			if ( index === blocks.length - 1 ) {
				flushPendingSections( 'advanced-sections-end' );
			}
		} );

		flushPendingSections( 'advanced-sections-end' );

		return rendered;
	}

	function renderSettingsTabContent( blocks: FieldUiBlock[] ) {
		const primary: Array< JSX.Element > = [];
		const advancedBlocks: FieldUiBlock[] = [];

		blocks.forEach( ( block ) => {
			if ( block.advanced ) {
				advancedBlocks.push( block );
				return;
			}
			primary.push( renderBlock( block ) );
		} );

		const advancedLabel =
			i18n.advancedSettings || 'Advanced settings';
		const advancedSectionLabels = advancedBlocks.flatMap( ( block ) =>
			block.kind === 'section' ? [ block.labelKey ] : []
		);
		const advancedDescription = formatAdvancedDescription(
			advancedSectionLabels
		);

		return (
			<VStack align="stretch" gap={ 3 }>
				{ primary }
				{ advancedBlocks.length > 0 && (
					<AdvancedSettingsPanel
						label={ advancedLabel }
						description={ advancedDescription || undefined }
					>
						{ renderAdvancedBlocks( advancedBlocks ) }
					</AdvancedSettingsPanel>
				) }
			</VStack>
		);
	}

	function renderPlainTabContent( blocks: FieldUiBlock[] ) {
		return (
			<VStack align="stretch" gap={ 3 }>
				{ blocks.map( ( block ) => renderBlock( block ) ) }
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
