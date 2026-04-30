/**
 * Conditional Field Repeater mapping (Pro Plus) — parity with classic PPOM Pro admin form.
 *
 * @see ppom-pro/inc/Addons/ConditionalFieldRepeater/templates/form.php
 * @see ppom-pro/assets/conditional_field_repeater/admin/src/main.js
 */
import { useMemo, useState } from '@wordpress/element';
import { VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import type { ModalContextValue } from '../types/fieldModal';
import {
	getCfrOriginCandidates,
	readRepeaterForm,
} from '../utils/conditionalRepeaterData';
import { EnableSwitch } from './conditional-repeater/EnableSwitch';
import { OriginFieldSelector } from './conditional-repeater/OriginFieldSelector';
import { MagicTagsBox } from './conditional-repeater/MagicTagsBox';
import { RepeaterEmptyState } from './conditional-repeater/RepeaterEmptyState';

export {
	getCfrOriginCandidates,
	type OriginCandidate,
} from '../utils/conditionalRepeaterData';

export interface ConditionalRepeaterMappingEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	modalContext: ModalContextValue | null | undefined;
}

export function ConditionalRepeaterMappingEditor( {
	values,
	onChange,
	i18n,
	modalContext,
}: ConditionalRepeaterMappingEditorProps ) {
	const [ copiedHint, setCopiedHint ] = useState( '' );
	const builderFields = modalContext?.builderFields ?? [];
	const currentDataName = String( values.data_name || '' ).trim();

	const candidates = useMemo(
		() => getCfrOriginCandidates( builderFields, currentDataName ),
		[ builderFields, currentDataName ]
	);

	const enabled =
		values.cond_field_repeater_enable === 'on' ||
		values.cond_field_repeater_enable === true;

	const form = readRepeaterForm( values );
	const origin = String( form.origin ?? '' );

	const selectedType = useMemo( () => {
		const hit = candidates.find( ( c ) => c.value === origin );
		return hit ? hit.type : '';
	}, [ candidates, origin ] );

	const showOptionTitleTag =
		selectedType === 'quantities' || selectedType === 'qtypack';

	const setEnabled = ( on: boolean ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				cond_field_repeater_enable: on ? 'on' : 'off',
			};
		} );
	};

	const setOrigin = ( nextOrigin: string ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			const nextForm = {
				...readRepeaterForm( prev ),
				origin: nextOrigin,
			};
			return {
				...prev,
				cond_field_repeater: nextForm,
			};
		} );
	};

	const copyTag = async ( tag: string ) => {
		try {
			await navigator.clipboard.writeText( tag );
			setCopiedHint( tag );
			window.setTimeout( () => setCopiedHint( '' ), 2000 );
		} catch {
			window.prompt( i18n.cfrCopyFallback || 'Copy:', tag );
		}
	};

	return (
		<VStack
			align="stretch"
			gap={ 0 }
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			bg="white"
			px={ { base: 3, md: 4 } }
			py={ 3 }
		>
			<EnableSwitch
				enabled={ enabled }
				i18n={ i18n }
				docsUrl={ modalContext?.links.cfrDocsUrl || '' }
				onToggle={ setEnabled }
				withDivider={ enabled }
			/>
			{ enabled ? (
				<VStack align="stretch" gap={ 3 } mt={ 3 }>
					<OriginFieldSelector
						origin={ origin }
						candidates={ candidates }
						i18n={ i18n }
						onChange={ setOrigin }
					/>
					<MagicTagsBox
						showOptionTitleTag={ showOptionTitleTag }
						copiedHint={ copiedHint }
						i18n={ i18n }
						onCopy={ copyTag }
					/>
				</VStack>
			) : (
				<RepeaterEmptyState
					i18n={ i18n }
					onEnable={ () => setEnabled( true ) }
				/>
			) }
		</VStack>
	);
}
