/**
 * Conditional Field Repeater mapping (Pro Plus) — parity with classic PPOM Pro admin form.
 *
 * @see ppom-pro/inc/Addons/ConditionalFieldRepeater/templates/form.php
 * @see ppom-pro/assets/conditional_field_repeater/admin/src/main.js
 */
import { useMemo, useState } from '@wordpress/element';
import { Box, Button, Field, Link, NativeSelect, Switch, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import type { ModalContextValue } from '../types/fieldModal';

const ORIGIN_TYPES = new Set( [ 'number', 'quantities', 'qtypack' ] );

export interface OriginCandidate {
	value: string;
	title: string;
	type: string;
}

export function getCfrOriginCandidates(
	builderFields: FieldRow[],
	currentDataName: string
): OriginCandidate[] {
	const seen = new Set< string >();
	const out: OriginCandidate[] = [];
	const current = String( currentDataName || '' ).trim();
	for ( const f of builderFields ) {
		const t = String( f.type || '' ).toLowerCase();
		const dn = String( f.data_name || '' ).trim();
		if ( ! ORIGIN_TYPES.has( t ) || ! dn ) {
			continue;
		}
		if ( dn === current ) {
			continue;
		}
		if ( seen.has( dn ) ) {
			continue;
		}
		seen.add( dn );
		out.push( {
			value: dn,
			title: String( f.title || dn ),
			type: t,
		} );
	}
	return out;
}

function readRepeaterForm(
	values: FieldRow
): Record< string, unknown > {
	const raw = values.cond_field_repeater;
	if (
		raw &&
		typeof raw === 'object' &&
		! Array.isArray( raw )
	) {
		return { ...( raw as Record< string, unknown > ) };
	}
	return {};
}

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
			window.prompt(
				i18n.cfrCopyFallback || 'Copy:',
				tag
			);
		}
	};

	return (
        <Box
			borderWidth="1px"
			borderColor="blue.100"
			borderRadius="md"
			p={ { base: 3, md: 4 } }
			bg="blue.50"
		>
            <Text fontWeight="semibold" fontSize="sm" mb={ 3 } color="blue.900">
				{ i18n.cfrSectionTitle || 'Conditional Repeater' }
			</Text>
            <Field.Root
				display="flex"
				alignItems="flex-start"
				gap={ 3 }
				mb={ 4 }
			>
				<Switch.Root
					id="ppom-cfr-enable"
					mt={ 1 }
					colorPalette="blue"
					checked={ Boolean( enabled ) }
					onCheckedChange={ ( { checked: next } ) => setEnabled( next ) }
				>
					<Switch.HiddenInput />
					<Switch.Control />
				</Switch.Root>
				<Box flex="1">
					<Field.Label htmlFor="ppom-cfr-enable" mb={ 1 } fontWeight="semibold">
						{ i18n.cfrEnableLabel || 'Enable Conditional Repeat' }
					</Field.Label>
					{ i18n.cfrDocsUrl ? (
						<Link
                            href={ i18n.cfrDocsUrl }
                            fontSize="xs"
                            color="blue.700"
                            target='_blank'
                            rel='noopener noreferrer'>
							{ i18n.cfrLearnMore || 'Learn more' }
						</Link>
					) : null }
				</Box>
			</Field.Root>
            <Box
				display={ enabled ? 'block' : 'none' }
				opacity={ enabled ? 1 : 0 }
				pointerEvents={ enabled ? 'auto' : 'none' }
			>
				<Field.Root mb={ 4 }>
					<Field.Label fontSize="sm">
						{ i18n.cfrOriginLabel || 'Origin' }
					</Field.Label>
					<NativeSelect.Root>
                        <NativeSelect.Field
                            size="sm"
                            bg="white"
                            value={ origin }
                            placeholder={
                                i18n.cfrOriginPlaceholder ||
                                'Select origin field…'
                            }
                            onValueChange={ ( e ) => setOrigin( e.target.value ) }>
                            <option value="">
                                { i18n.cfrOriginNone || 'None' }
                            </option>
                            { candidates.map( ( c ) => (
                                <option key={ c.value } value={ c.value }>
                                    { c.title } ({ c.value })
                                </option>
                            ) ) }
                        </NativeSelect.Field>
                        <NativeSelect.Indicator />
                    </NativeSelect.Root>
					<Text fontSize="xs" color="gray.600" mt={ 1 } lineHeight="1.5">
						{ i18n.cfrOriginHelp ||
							'Only Number, Variation Quantity, and Quantity Pack fields can be the origin.' }
					</Text>
				</Field.Root>

				<Box
					borderWidth="1px"
					borderColor="gray.200"
					borderRadius="md"
					p={ 3 }
					bg="white"
				>
					<Text fontWeight="semibold" fontSize="sm" mb={ 1 }>
						{ i18n.cfrMagicTagsHeading || 'Magic tags' }
					</Text>
					<Text fontSize="xs" color="gray.600" mb={ 3 } lineHeight="1.5">
						{ i18n.cfrMagicTagsDescription ||
							'Use these in the Field Title (Fields tab).' }
					</Text>
					<VStack align="stretch" gap={ 2 }>
						<Button
							size="sm"
							variant="outline"
							onClick={ () => copyTag( '{repeatNumber}' ) }
						>
							{`{repeatNumber}`}
						</Button>
						{ showOptionTitleTag ? (
							<Button
								size="sm"
								variant="outline"
								onClick={ () => copyTag( '{optionTitle}' ) }
							>
								{`{optionTitle}`}
							</Button>
						) : null }
						{ copiedHint ? (
							<Text fontSize="xs" color="green.600">
								{ ( i18n.cfrCopied || 'Copied' ) + ': ' + copiedHint }
							</Text>
						) : null }
					</VStack>
				</Box>
			</Box>
        </Box>
    );
}
