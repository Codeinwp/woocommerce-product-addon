import { Box, HStack, IconButton, Link, NativeSelect } from '@chakra-ui/react';
import { LuGripVertical, LuTrash2 } from 'react-icons/lu';
import {
	COMPARISON_VALUE_CAN_USE_SELECT,
	HIDE_COMPARISON_INPUT_FIELD,
	OPERATOR_COMPARISON_VALUE_FIELD_TYPE,
	PRO_OPERATOR_VALUES,
} from '../../conditionsConstants';
import type { FieldRow, I18nDict } from '../../types/fieldModal';
import {
	type ConditionRule,
	type ConditionsTarget,
	findFieldType,
	getComparisonOptionValues,
	operatorAllowedForTarget,
} from '../../utils/conditionsLogic';
import type { OperatorGroup } from './operatorGroups';
import { RuleValueInput } from './RuleValueInput';
import { controlSurface } from './styles';

export interface RuleRowProps {
	rule: ConditionRule;
	ruleKey: string;
	canRemove: boolean;
	targets: ConditionsTarget[];
	builderFields: FieldRow[];
	operatorGroups: OperatorGroup[];
	conditionsProEnabled: boolean;
	upgradeUrl: string;
	upgradeCta: string;
	i18n: I18nDict;
	onPatch: ( ruleKey: string, patch: Record< string, unknown > ) => void;
	onRemove: ( ruleKey: string ) => void;
	dragHandleProps?: {
		onPointerDown: () => void;
		onPointerUp: () => void;
	};
}

export function RuleRow( {
	rule,
	ruleKey,
	canRemove,
	targets,
	builderFields,
	operatorGroups,
	conditionsProEnabled,
	upgradeUrl,
	upgradeCta,
	i18n,
	onPatch,
	onRemove,
	dragHandleProps,
}: RuleRowProps ) {
	const targetType = findFieldType( builderFields, rule.elements );
	const optionValues = getComparisonOptionValues(
		( builderFields.find(
			( f ) =>
				String( f.data_name || '' ).trim() ===
				String( rule.elements || '' ).trim()
		) ?? {} ) as Record< string, unknown >
	);

	const op = String( rule.operators || 'is' );
	const hideValue = HIDE_COMPARISON_INPUT_FIELD.includes( op );
	const isBetween = op === 'between';
	const proLocked = PRO_OPERATOR_VALUES.has( op ) && ! conditionsProEnabled;
	const usesConstantOnly =
		op === 'contains' ||
		op === 'not-contains' ||
		op === 'regex' ||
		op === 'number-multiplier';

	let showSelect = false;
	let showText = false;
	if ( ! proLocked && ! isBetween && ! hideValue ) {
		const canSelect =
			COMPARISON_VALUE_CAN_USE_SELECT.includes( op ) &&
			targetType &&
			OPERATOR_COMPARISON_VALUE_FIELD_TYPE.select.includes( targetType );
		if ( canSelect && optionValues.length > 0 ) {
			showSelect = true;
		} else {
			showText = true;
		}
	}

	const removeLabel = i18n.condRemoveCondition || 'Remove condition';
	const dragLabel = i18n.condDragHandle || 'Drag to reorder';

	let valueColumn = (
		<RuleValueInput
			rule={ rule }
			ruleKey={ ruleKey }
			showSelect={ showSelect }
			showText={ showText }
			isBetween={ isBetween }
			proLocked={ proLocked }
			usesConstantOnly={ usesConstantOnly }
			optionValues={ optionValues }
			i18n={ i18n }
			onPatch={ onPatch }
		/>
	);

	if ( proLocked && upgradeUrl ) {
		valueColumn = (
			<Box>
				<Link
					href={ upgradeUrl }
					fontSize="sm"
					color="blue.600"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ upgradeCta }
				</Link>
			</Box>
		);
	} else if ( hideValue ) {
		valueColumn = <Box />;
	}

	return (
		<HStack align="center" gap={ 2 } w="full" data-rule-key={ ruleKey }>
			<IconButton
				aria-label={ dragLabel }
				size="xs"
				variant="ghost"
				color="gray.500"
				_hover={ { color: 'gray.700', bg: 'gray.100' } }
				cursor="grab"
				onPointerDown={ dragHandleProps?.onPointerDown }
				onPointerUp={ dragHandleProps?.onPointerUp }
				title={ dragLabel }
				flexShrink={ 0 }
			>
				<LuGripVertical />
			</IconButton>
			<Box flex="1 1 0" minW={ 0 }>
				<NativeSelect.Root>
					<NativeSelect.Field
						size="sm"
						value={ String( rule.elements || '' ) }
						aria-label={ i18n.condFieldHeader || 'Field' }
						onValueChange={ ( e ) => {
							const elements = e.target.value;
							const t = findFieldType( builderFields, elements );
							let operators = String( rule.operators || 'is' );
							if ( ! operatorAllowedForTarget( operators, t ) ) {
								operators = 'any';
							}
							onPatch( ruleKey, {
								elements,
								operators,
								element_values: '',
								element_constant: '',
								'cond-between-interval': {
									from: '',
									to: '',
								},
							} );
						} }
						{ ...controlSurface }
					>
						<option value="">
							{ i18n.condSelectField || 'Select a field…' }
						</option>
						{ targets.map( ( t ) => (
							<option key={ t.fieldId } value={ t.fieldId }>
								{ t.fieldLabel } ({ t.fieldId })
							</option>
						) ) }
					</NativeSelect.Field>
					<NativeSelect.Indicator />
				</NativeSelect.Root>
			</Box>
			<Box flex="1 1 0" minW={ 0 }>
				<NativeSelect.Root>
					<NativeSelect.Field
						size="sm"
						value={ op }
						aria-label={ i18n.condOperatorHeader || 'Operator' }
						onValueChange={ ( e ) => {
							const operators = e.target.value;
							const patch: Record< string, unknown > = {
								operators,
							};
							if (
								HIDE_COMPARISON_INPUT_FIELD.includes(
									operators
								) ||
								operators === 'between'
							) {
								patch.element_values = '';
								patch.element_constant = '';
							}
							if ( operators !== 'between' ) {
								patch[ 'cond-between-interval' ] = {
									from: '',
									to: '',
								};
							}
							onPatch( ruleKey, patch );
						} }
						{ ...controlSurface }
					>
						{ operatorGroups.map( ( grp ) => (
							<optgroup key={ grp.label } label={ grp.label }>
								{ grp.options.map( ( o ) => {
									const allowed =
										! targetType ||
										operatorAllowedForTarget(
											o.value,
											targetType
										);
									const isPro = PRO_OPERATOR_VALUES.has(
										o.value
									);
									const disabled =
										! allowed ||
										( isPro && ! conditionsProEnabled );
									let label = o.label;
									if ( isPro && ! conditionsProEnabled ) {
										label =
											label +
											' (' +
											( i18n.proBadge || 'PRO' ) +
											')';
									}
									return (
										<option
											key={ o.value }
											value={ o.value }
											disabled={ disabled }
										>
											{ label }
										</option>
									);
								} ) }
							</optgroup>
						) ) }
					</NativeSelect.Field>
					<NativeSelect.Indicator />
				</NativeSelect.Root>
			</Box>
			<Box flex="1 1 0" minW={ 0 }>
				{ valueColumn }
			</Box>
			<IconButton
				size="xs"
				variant="ghost"
				colorPalette="red"
				aria-label={ removeLabel }
				title={ removeLabel }
				onClick={ () => onRemove( ruleKey ) }
				flexShrink={ 0 }
				visibility={ canRemove ? 'visible' : 'hidden' }
				pointerEvents={ canRemove ? undefined : 'none' }
				tabIndex={ canRemove ? undefined : -1 }
			>
				<LuTrash2 />
			</IconButton>
		</HStack>
	);
}
