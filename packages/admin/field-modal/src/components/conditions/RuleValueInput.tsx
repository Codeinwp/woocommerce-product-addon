import { HStack, Input, NativeSelect, Text } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';
import type { ConditionRule } from '../../utils/conditionsLogic';
import { controlSurface } from './styles';

export interface RuleValueInputProps {
	rule: ConditionRule;
	ruleKey: string;
	showSelect: boolean;
	showText: boolean;
	isBetween: boolean;
	proLocked: boolean;
	usesConstantOnly: boolean;
	optionValues: string[];
	i18n: I18nDict;
	onPatch: ( ruleKey: string, patch: Record< string, unknown > ) => void;
}

export function RuleValueInput( {
	rule,
	ruleKey,
	showSelect,
	showText,
	isBetween,
	proLocked,
	usesConstantOnly,
	optionValues,
	i18n,
	onPatch,
}: RuleValueInputProps ) {
	const betweenIv = rule[ 'cond-between-interval' ] as
		| { from?: string; to?: string }
		| undefined;

	if ( showSelect ) {
		return (
			<NativeSelect.Root>
				<NativeSelect.Field
					size="sm"
					value={ String( rule.element_values || '' ) }
					aria-label={ i18n.condValueHeader || 'Value' }
					onValueChange={ ( e ) =>
						onPatch( ruleKey, {
							element_values: e.target.value,
							element_constant: '',
						} )
					}
					{ ...controlSurface }
				>
					<option value="">
						{ i18n.condSelectValue || 'Select value…' }
					</option>
					{ optionValues.map( ( v ) => (
						<option key={ v } value={ v }>
							{ v }
						</option>
					) ) }
				</NativeSelect.Field>
				<NativeSelect.Indicator />
			</NativeSelect.Root>
		);
	}

	if ( showText ) {
		return (
			<Input
				size="sm"
				placeholder={ i18n.condEnterValuePlaceholder || 'Enter value' }
				aria-label={ i18n.condValueHeader || 'Value' }
				value={ String(
					usesConstantOnly
						? rule.element_constant || rule.element_values || ''
						: rule.element_values || rule.element_constant || ''
				) }
				onValueChange={ ( e ) => {
					const v = e.target.value;
					if ( usesConstantOnly ) {
						onPatch( ruleKey, {
							element_constant: v,
							element_values: '',
						} );
					} else {
						onPatch( ruleKey, {
							element_values: v,
							element_constant: v,
						} );
					}
				} }
				{ ...controlSurface }
			/>
		);
	}

	if ( isBetween && ! proLocked ) {
		return (
			<HStack gap={ 2 } align="center">
				<Input
					size="sm"
					type="number"
					value={ String( betweenIv?.from ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( ruleKey, {
							'cond-between-interval': {
								from: e.target.value,
								to: betweenIv?.to ?? '',
							},
						} )
					}
					{ ...controlSurface }
				/>
				<Text fontSize="sm" color="gray.600">
					{ i18n.condAnd || 'and' }
				</Text>
				<Input
					size="sm"
					type="number"
					value={ String( betweenIv?.to ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( ruleKey, {
							'cond-between-interval': {
								from: betweenIv?.from ?? '',
								to: e.target.value,
							},
						} )
					}
					{ ...controlSurface }
				/>
			</HStack>
		);
	}

	return null;
}
