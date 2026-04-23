import {
	Box,
	Button,
	Field,
	HStack,
	NativeSelect,
	Text,
	VStack,
} from '@chakra-ui/react';
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
import { controlSurface, labelProps } from './styles';

export interface RuleRowProps {
	rule: ConditionRule;
	ruleKey: string;
	index: number;
	isLast: boolean;
	canRemove: boolean;
	targets: ConditionsTarget[];
	builderFields: FieldRow[];
	operatorGroups: OperatorGroup[];
	conditionsProEnabled: boolean;
	upgradeUrl: string;
	upgradeCta: string;
	i18n: I18nDict;
	onPatch: ( ruleKey: string, patch: Record< string, unknown > ) => void;
	onAdd: () => void;
	onRemove: ( ruleKey: string ) => void;
}

export function RuleRow( {
	rule,
	ruleKey,
	index,
	isLast,
	canRemove,
	targets,
	builderFields,
	operatorGroups,
	conditionsProEnabled,
	upgradeUrl,
	upgradeCta,
	i18n,
	onPatch,
	onAdd,
	onRemove,
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

	return (
		<Box
			borderWidth="1px"
			borderColor="gray.100"
			borderRadius="md"
			p={ 4 }
			bg="gray.50"
		>
			<Text
				fontSize="xs"
				fontWeight="700"
				color="gray.500"
				textTransform="uppercase"
				letterSpacing="0.06em"
				mb={ 3 }
			>
				{ ( i18n.condRule || 'Rule' ) + ' ' + ( index + 1 ) }
			</Text>
			<VStack align="stretch" gap={ 3 }>
				<Field.Root>
					<Field.Label { ...labelProps }>
						{ i18n.condTargetField || 'Field' }
					</Field.Label>
					<NativeSelect.Root>
						<NativeSelect.Field
							size="sm"
							value={ String( rule.elements || '' ) }
							onValueChange={ ( e ) => {
								const elements = e.target.value;
								const t = findFieldType(
									builderFields,
									elements
								);
								let operators = String(
									rule.operators || 'is'
								);
								if (
									! operatorAllowedForTarget( operators, t )
								) {
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
				</Field.Root>

				<Field.Root>
					<Field.Label { ...labelProps }>
						{ i18n.condOperator || 'Operator' }
					</Field.Label>
					<NativeSelect.Root>
						<NativeSelect.Field
							size="sm"
							value={ op }
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
				</Field.Root>

				<RuleValueInput
					rule={ rule }
					ruleKey={ ruleKey }
					showSelect={ showSelect }
					showText={ showText }
					isBetween={ isBetween }
					proLocked={ proLocked }
					usesConstantOnly={ usesConstantOnly }
					optionValues={ optionValues }
					upgradeUrl={ upgradeUrl }
					upgradeCta={ upgradeCta }
					i18n={ i18n }
					onPatch={ onPatch }
				/>

				<HStack gap={ 2 } justify="flex-end">
					{ isLast ? (
						<Button
							size="sm"
							colorPalette="green"
							variant="outline"
							onClick={ onAdd }
						>
							{ i18n.condAddRule || 'Add rule' }
						</Button>
					) : null }
					{ canRemove ? (
						<Button
							size="sm"
							colorPalette="red"
							variant="outline"
							onClick={ () => onRemove( ruleKey ) }
						>
							{ i18n.condRemoveRule || 'Remove rule' }
						</Button>
					) : null }
				</HStack>
			</VStack>
		</Box>
	);
}
