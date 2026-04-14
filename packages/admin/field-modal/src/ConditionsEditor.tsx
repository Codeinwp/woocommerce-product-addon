/**
 * Conditional logic rules editor (parity with classes/fields.class.php html-conditions + ppom-admin.js).
 */
import {
	Box,
	Button,
	FormControl,
	FormLabel,
	HStack,
	VStack,
	Select,
	Input,
	Text,
	Link,
} from '@chakra-ui/react';
import {
	COMPARISON_VALUE_CAN_USE_SELECT,
	HIDE_COMPARISON_INPUT_FIELD,
	OPERATOR_COMPARISON_VALUE_FIELD_TYPE,
	OPERATORS_FIELD_COMPATIBILITY,
	PRO_OPERATOR_VALUES,
} from './conditionsConstants';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from './types/fieldModal';

type ConditionRule = Record< string, unknown >;

interface ConditionsState {
	visibility: string;
	bound: string;
	rules: Record< string, ConditionRule >;
}

const controlSurface = {
	bg: 'white',
	borderColor: 'gray.200',
	borderRadius: 'md',
	_hover: { borderColor: 'gray.300' },
	_focus: {
		borderColor: 'blue.500',
		boxShadow: '0 0 0 1px #2271b1',
	},
};

const labelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 1,
};

function emptyRule(): ConditionRule {
	return {
		elements: '',
		operators: 'is',
		element_values: '',
		element_constant: '',
		'cond-between-interval': { from: '', to: '' },
	};
}

function normalizeConditions( raw: unknown ): ConditionsState {
	if ( ! raw || typeof raw !== 'object' ) {
		return {
			visibility: 'Show',
			bound: 'All',
			rules: { 0: emptyRule() },
		};
	}
	const rawObj = raw as Record< string, unknown >;
	const visibility =
		rawObj.visibility === 'Hide' ? 'Hide' : 'Show';
	const bound = rawObj.bound === 'Any' ? 'Any' : 'All';
	let rules = rawObj.rules;
	if ( ! rules || typeof rules !== 'object' ) {
		rules = { 0: emptyRule() };
	} else {
		const ruleMap = rules as Record< string, unknown >;
		const keys = Object.keys( ruleMap );
		if ( keys.length === 0 ) {
			rules = { 0: emptyRule() };
		} else {
			const next: Record< string, ConditionRule > = {};
			keys.forEach( ( k ) => {
				const r = ruleMap[ k ];
				const between =
					r &&
					typeof r === 'object' &&
					r !== null &&
					'cond-between-interval' in r &&
					typeof ( r as Record< string, unknown > )[ 'cond-between-interval' ] ===
						'object'
						? ( r as Record< string, unknown > )[ 'cond-between-interval' ] as Record<
								string,
								unknown
						  >
						: null;
				next[ k ] =
					r && typeof r === 'object'
						? {
								...emptyRule(),
								...r,
								'cond-between-interval':
									between
										? {
												from:
													between.from != null
														? String( between.from )
														: '',
												to:
													between.to != null
														? String( between.to )
														: '',
										  }
										: { from: '', to: '' },
						  }
						: emptyRule();
			} );
			rules = next;
		}
	}
	return { visibility, bound, rules: rules as Record< string, ConditionRule > };
}

function reindexRules( rules: Record< string, ConditionRule > ) {
	const sorted = Object.keys( rules )
		.map( ( k ) => ( { n: Number( k ), v: rules[ k ] } ) )
		.filter( ( x ) => ! Number.isNaN( x.n ) )
		.sort( ( a, b ) => a.n - b.n );
	const out: Record< string, ConditionRule > = {};
	sorted.forEach( ( x, i ) => {
		out[ String( i ) ] = x.v;
	} );
	return out;
}

function nextRuleIndex( rules: Record< string, ConditionRule > ) {
	const nums = Object.keys( rules ).map( Number );
	return ( nums.length ? Math.max( ...nums ) : -1 ) + 1;
}

function canUseFieldType( fieldType: string ) {
	if ( ! fieldType ) {
		return false;
	}
	return Object.values( OPERATORS_FIELD_COMPATIBILITY ).some( ( list ) =>
		list.includes( fieldType )
	);
}

function getComparisonOptionValues( field: Record< string, unknown > ) {
	if ( ! field || typeof field !== 'object' ) {
		return [];
	}
	const out: string[] = [];
	const push = ( v: unknown ) => {
		const s = String( v || '' ).trim();
		if ( s ) {
			out.push( s );
		}
	};

	const opts = field.options;
	if ( Array.isArray( opts ) ) {
		opts.forEach( ( o: unknown ) => {
			if ( o && typeof o === 'object' && 'option' in o && ( o as { option?: unknown } ).option != null ) {
				push( ( o as { option: unknown } ).option );
			}
		} );
	} else if ( opts && typeof opts === 'object' ) {
		Object.values( opts as Record< string, unknown > ).forEach( ( o ) => {
			if ( o && typeof o === 'object' && 'option' in o && ( o as { option?: unknown } ).option != null ) {
				push( ( o as { option: unknown } ).option );
			}
		} );
	}

	const imgs = field.images;
	if ( Array.isArray( imgs ) ) {
		imgs.forEach( ( img: unknown ) => {
			if ( img && typeof img === 'object' && 'title' in img && ( img as { title?: unknown } ).title != null ) {
				push( ( img as { title: unknown } ).title );
			}
		} );
	}

	return [ ...new Set( out ) ];
}

function findFieldType( builderFields: FieldRow[], dataName: unknown ) {
	const id = String( dataName || '' ).trim();
	if ( ! id ) {
		return '';
	}
	const row = builderFields.find(
		( f ) => String( f.data_name || '' ).trim() === id
	);
	return row && row.type ? String( row.type ) : '';
}

function operatorAllowedForTarget( operator: string, targetType: string ) {
	if ( ! targetType ) {
		return true;
	}
	const allowed = OPERATORS_FIELD_COMPATIBILITY[ operator ];
	if ( ! allowed ) {
		return true;
	}
	return allowed.includes( targetType );
}

export interface ConditionsEditorProps {
	meta: Record< string, unknown >;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	builderFields?: FieldRow[];
	conditionsProEnabled?: boolean;
}

export function ConditionsEditor( {
	meta,
	values,
	onChange,
	i18n,
	builderFields = [],
	conditionsProEnabled = false,
}: ConditionsEditorProps ) {
	const title = meta.title ? String( meta.title ) : 'Conditions';
	const desc = meta.desc ? String( meta.desc ) : '';

	const logicOn =
		values.logic === 'on' || values.logic === true || values.logic === '1';

	const cond = normalizeConditions( values.conditions );

	const selfId = String( values.data_name || '' ).trim();

	const targets = builderFields
		.map( ( f: FieldRow ) => {
			const fieldId = String( f.data_name || '' ).trim();
			const fieldLabel = String( f.title || fieldId || '' ).trim();
			const fieldType = f.type ? String( f.type ) : '';
			return {
				fieldId,
				fieldLabel,
				fieldType,
				canUse: canUseFieldType( fieldType ),
			};
		} )
		.filter(
			( t ) =>
				t.fieldId &&
				t.fieldId !== selfId &&
				t.canUse
		);

	const setConditions = ( nextCond: unknown ) => {
		onChange( { ...values, conditions: nextCond } );
	};

	const updateRule = ( ruleKey: string, patch: Record< string, unknown > ) => {
		const rules = { ...cond.rules };
		rules[ ruleKey ] = { ...rules[ ruleKey ], ...patch };
		setConditions( { ...cond, rules } );
	};

	const addRule = () => {
		const idx = String( nextRuleIndex( cond.rules ) );
		const rules = { ...cond.rules, [ idx ]: emptyRule() };
		setConditions( { ...cond, rules } );
	};

	const removeRule = ( ruleKey: string ) => {
		const keys = Object.keys( cond.rules );
		if ( keys.length <= 1 ) {
			setConditions( { ...cond, rules: { 0: emptyRule() } } );
			return;
		}
		const next = { ...cond.rules };
		delete next[ ruleKey ];
		setConditions( { ...cond, rules: reindexRules( next ) } );
	};

	const ruleEntries = Object.keys( cond.rules )
		.map( ( k ) => ( { key: k, rule: cond.rules[ k ] } ) )
		.sort( ( a, b ) => Number( a.key ) - Number( b.key ) );

	const lastKey = ruleEntries.length
		? ruleEntries[ ruleEntries.length - 1 ].key
		: '0';

	const operatorGroups = [
		{
			label: i18n.condGroupValue || 'Value comparison',
			options: [
				{ value: 'is', label: i18n.condOpIs || 'is' },
				{ value: 'not', label: i18n.condOpNot || 'is not' },
				{ value: 'empty', label: i18n.condOpEmpty || 'is empty' },
				{ value: 'any', label: i18n.condOpAny || 'has any value' },
			],
		},
		{
			label: i18n.condGroupText || 'Text matching',
			options: [
				{ value: 'contains', label: i18n.condOpContains || 'contains' },
				{
					value: 'not-contains',
					label: i18n.condOpNotContains || 'does not contain',
				},
				{ value: 'regex', label: i18n.condOpRegex || 'matches RegEx' },
			],
		},
		{
			label: i18n.condGroupNumeric || 'Numeric comparison',
			options: [
				{
					value: 'greater than',
					label: i18n.condOpGreater || 'greater than',
				},
				{ value: 'less than', label: i18n.condOpLess || 'less than' },
				{ value: 'between', label: i18n.condOpBetween || 'is between' },
				{
					value: 'number-multiplier',
					label: i18n.condOpMultiple || 'is multiple of',
				},
				{ value: 'even-number', label: i18n.condOpEven || 'is even' },
				{ value: 'odd-number', label: i18n.condOpOdd || 'is odd' },
			],
		},
	];

	const upgradeUrl = i18n.conditionUpgradeUrl || '';
	const upgradeCta = i18n.conditionUpgradeCta || 'Upgrade to unlock';

	return (
		<Box>
			<Text fontWeight="semibold" fontSize="sm" color="gray.800">
				{ title }
			</Text>
			{ desc ? (
				<Text fontSize="sm" color="gray.600" mt={ 1 } lineHeight="1.5">
					{ desc }
				</Text>
			) : null }

			{ ! logicOn ? (
				<Text mt={ 3 } fontSize="xs" color="gray.600" lineHeight="1.5">
					{ i18n.condEnableLogicHint ||
						'Turn on “Use conditional logic” above to apply these rules on the product page.' }
				</Text>
			) : null }

			<VStack align="stretch" spacing={ 3 } mt={ 2 }>
				<HStack
					align="flex-end"
					flexWrap="wrap"
					spacing={ 2 }
					gap={ 2 }
				>
					<FormControl maxW="200px">
						<FormLabel { ...labelProps }>{ i18n.condShowHide || 'Show / Hide' }</FormLabel>
						<Select
							size="sm"
							value={ cond.visibility }
							onChange={ ( e ) =>
								setConditions( {
									...cond,
									visibility: e.target.value,
								} )
							}
							{ ...controlSurface }
						>
							<option value="Show">{ i18n.condShow || 'Show' }</option>
							<option value="Hide">{ i18n.condHide || 'Hide' }</option>
						</Select>
					</FormControl>
					<Text fontSize="sm" color="gray.600" pb={ 2 }>
						{ i18n.condOnlyIf || 'only if' }
					</Text>
					<FormControl maxW="200px">
						<FormLabel { ...labelProps }>{ i18n.condAllAny || 'All / Any' }</FormLabel>
						<Select
							size="sm"
							value={ cond.bound }
							onChange={ ( e ) =>
								setConditions( { ...cond, bound: e.target.value } )
							}
							{ ...controlSurface }
						>
							<option value="All">{ i18n.condAll || 'All' }</option>
							<option value="Any">{ i18n.condAny || 'Any' }</option>
						</Select>
					</FormControl>
					<Text fontSize="sm" color="gray.600" pb={ 2 }>
						{ i18n.condFollowingMatches || 'of the following match' }
					</Text>
				</HStack>

				{ ruleEntries.map( ( { key: ruleKey, rule }, idx ) => {
					const betweenIv = rule[ 'cond-between-interval' ] as
						| { from?: string; to?: string }
						| undefined;
					const targetType = findFieldType(
						builderFields,
						rule.elements
					);
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
					const proLocked =
						PRO_OPERATOR_VALUES.has( op ) && ! conditionsProEnabled;
					const usesConstantOnly =
						op === 'contains' ||
						op === 'not-contains' ||
						op === 'regex' ||
						op === 'number-multiplier';

					let showSelect = false;
					let showText = false;
					if ( proLocked ) {
						showSelect = false;
						showText = false;
					} else if ( isBetween ) {
						showSelect = false;
						showText = false;
					} else if ( hideValue ) {
						showSelect = false;
						showText = false;
					} else {
						const canSelect =
							COMPARISON_VALUE_CAN_USE_SELECT.includes( op ) &&
							targetType &&
							OPERATOR_COMPARISON_VALUE_FIELD_TYPE.select.includes(
								targetType
							);
						if ( canSelect && optionValues.length > 0 ) {
							showSelect = true;
							showText = false;
						} else {
							showSelect = false;
							showText = true;
						}
					}

					return (
						<Box
							key={ ruleKey }
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
								{ ( i18n.condRule || 'Rule' ) + ' ' + ( idx + 1 ) }
							</Text>
							<VStack align="stretch" spacing={ 3 }>
								<FormControl>
									<FormLabel { ...labelProps }>
										{ i18n.condTargetField || 'Field' }
									</FormLabel>
									<Select
										size="sm"
										value={ String( rule.elements || '' ) }
										onChange={ ( e ) => {
											const elements = e.target.value;
											const t = findFieldType(
												builderFields,
												elements
											);
											let operators = String(
												rule.operators || 'is'
											);
											if (
												! operatorAllowedForTarget(
													operators,
													t
												)
											) {
												operators = 'any';
											}
											updateRule( ruleKey, {
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
											{ i18n.condSelectField ||
												'Select a field…' }
										</option>
										{ targets.map( ( t ) => (
											<option
												key={ t.fieldId }
												value={ t.fieldId }
											>
												{ t.fieldLabel } ({ t.fieldId })
											</option>
										) ) }
									</Select>
								</FormControl>

								<FormControl>
									<FormLabel { ...labelProps }>
										{ i18n.condOperator || 'Operator' }
									</FormLabel>
									<Select
										size="sm"
										value={ op }
										onChange={ ( e ) => {
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
											updateRule( ruleKey, patch );
										} }
										{ ...controlSurface }
									>
										{ operatorGroups.map( ( grp ) => (
											<optgroup
												key={ grp.label }
												label={ grp.label }
											>
												{ grp.options.map( ( o ) => {
													const allowed =
														! targetType ||
														operatorAllowedForTarget(
															o.value,
															targetType
														);
													const isPro =
														PRO_OPERATOR_VALUES.has(
															o.value
														);
													const disabled =
														! allowed ||
														( isPro &&
															! conditionsProEnabled );
													let label = o.label;
													if (
														isPro &&
														! conditionsProEnabled
													) {
														label =
															label +
															' (' +
															( i18n.proBadge ||
																'PRO' ) +
															')';
													}
													return (
														<option
															key={ o.value }
															value={ o.value }
															disabled={
																disabled
															}
														>
															{ label }
														</option>
													);
												} ) }
											</optgroup>
										) ) }
									</Select>
								</FormControl>

								{ proLocked && upgradeUrl ? (
									<Link
										href={ upgradeUrl }
										isExternal
										fontSize="sm"
										color="blue.600"
									>
										{ upgradeCta }
									</Link>
								) : null }

								{ showSelect ? (
									<FormControl>
										<FormLabel { ...labelProps }>
											{ i18n.condValue || 'Value' }
										</FormLabel>
										<Select
											size="sm"
											value={ String(
												rule.element_values || ''
											) }
											onChange={ ( e ) =>
												updateRule( ruleKey, {
													element_values:
														e.target.value,
													element_constant: '',
												} )
											}
											{ ...controlSurface }
										>
											<option value="">
												{ i18n.condSelectValue ||
													'Select value…' }
											</option>
											{ optionValues.map( ( v ) => (
												<option key={ v } value={ v }>
													{ v }
												</option>
											) ) }
										</Select>
									</FormControl>
								) : null }

								{ showText ? (
									<FormControl>
										<FormLabel { ...labelProps }>
											{ i18n.condValue || 'Value' }
										</FormLabel>
										<Input
											size="sm"
											value={ String(
												usesConstantOnly
													? rule.element_constant ||
													  rule.element_values ||
													  ''
													: rule.element_values ||
													  rule.element_constant ||
													  ''
											) }
											onChange={ ( e ) => {
												const v = e.target.value;
												if ( usesConstantOnly ) {
													updateRule( ruleKey, {
														element_constant: v,
														element_values: '',
													} );
												} else {
													updateRule( ruleKey, {
														element_values: v,
														element_constant: v,
													} );
												}
											} }
											{ ...controlSurface }
										/>
									</FormControl>
								) : null }

								{ isBetween && ! proLocked ? (
									<HStack spacing={ 2 } align="center">
										<Input
											size="sm"
											type="number"
											value={ String( betweenIv?.from ?? '' ) }
											onChange={ ( e ) =>
												updateRule( ruleKey, {
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
											onChange={ ( e ) =>
												updateRule( ruleKey, {
													'cond-between-interval': {
														from: betweenIv?.from ?? '',
														to: e.target.value,
													},
												} )
											}
											{ ...controlSurface }
										/>
									</HStack>
								) : null }

								<HStack spacing={ 2 } justify="flex-end">
									{ ruleKey === lastKey ? (
										<Button
											size="sm"
											colorScheme="green"
											variant="outline"
											onClick={ addRule }
										>
											{ i18n.condAddRule || 'Add rule' }
										</Button>
									) : null }
									{ ruleEntries.length > 1 ? (
										<Button
											size="sm"
											colorScheme="red"
											variant="outline"
											onClick={ () =>
												removeRule( ruleKey )
											}
										>
											{ i18n.condRemoveRule ||
												'Remove rule' }
										</Button>
									) : null }
								</HStack>
							</VStack>
						</Box>
					);
				} ) }
			</VStack>
		</Box>
	);
}
