/**
 * Runtime renderer for the active field editor.
 */
import { useMemo } from '@wordpress/element';
import { VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { getFieldUiDefinition } from '../definitions/registry';
import { resolveFieldModalRoute } from '../definitions/routing';
import { mergeBuilderFieldsWithActive } from '../utils/mergedBuilderFields';
import { UnknownSlugPanel } from '../panels/UnknownSlugPanel';
import { DefinitionDrivenFieldEditor } from './DefinitionDrivenFieldEditor';
import { FieldEditorFetchError } from './manage/FieldEditorFetchError';
import { SchemaLoadingSkeleton } from './manage/SchemaLoadingSkeleton';
import { SchemaUnavailableAlert } from './manage/SchemaUnavailableAlert';
import type {
	FieldFormApiLike,
	FieldRow,
	I18nDict,
	ModalContextValue,
	SchemaObject,
} from '../types/fieldModal';

export interface EditorRuntimeProps {
	fields: FieldRow[];
	values: FieldRow;
	activeSchema: SchemaObject | null;
	schemaLoading: boolean;
	schemaFetchError?: string;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	ppomFieldIndex: number;
	form: FieldFormApiLike;
	modalContext: ModalContextValue;
}

export function EditorRuntime( {
	fields,
	values,
	activeSchema,
	schemaLoading,
	schemaFetchError,
	onChange,
	i18n,
	ppomFieldIndex,
	form,
	modalContext,
}: EditorRuntimeProps ) {
	const slug = values.type ? String( values.type ) : '';
	const route = resolveFieldModalRoute( slug );
	const uiDefinition = getFieldUiDefinition( slug );

	const mergedBuilderFields = useMemo(
		() => mergeBuilderFieldsWithActive( fields, values.clientId, values ),
		[ fields, values ]
	);

	return (
		<VStack align="stretch" gap={ 3 }>
			{ schemaFetchError && (
				<FieldEditorFetchError message={ schemaFetchError } />
			) }
			{ schemaLoading && ! activeSchema && ! schemaFetchError && (
				<SchemaLoadingSkeleton />
			) }
			{ activeSchema &&
				! schemaFetchError &&
				route.kind === 'definition' &&
				uiDefinition && (
					<DefinitionDrivenFieldEditor
						definition={ uiDefinition }
						mergedBuilderFields={ mergedBuilderFields }
						schema={ activeSchema }
						values={ values }
						onChange={ onChange }
						i18n={ i18n }
						ppomFieldIndex={ ppomFieldIndex }
						form={ form }
						modalContext={ modalContext }
					/>
				) }
			{ ! schemaLoading &&
				values.type &&
				( route.kind === 'unknown' || route.kind === 'legacyPhp' ) && (
					<UnknownSlugPanel
						slug={ route.slug }
						i18n={ i18n }
						ppomFieldIndex={ ppomFieldIndex }
					/>
				) }
			{ ! schemaLoading &&
				! schemaFetchError &&
				! activeSchema &&
				values.type &&
				route.kind === 'definition' && (
					<SchemaUnavailableAlert i18n={ i18n } />
				) }
		</VStack>
	);
}
