/**
 * Conditional logic rules editor (parity with classes/fields.class.php html-conditions + ppom-admin.js).
 */
import { VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from './types/fieldModal';
import {
	buildConditionsTargets,
	emptyRule,
	nextRuleIndex,
	normalizeConditions,
	reindexRules,
} from './utils/conditionsLogic';
import { buildOperatorGroups } from './components/conditions/operatorGroups';
import { ConditionsHeader } from './components/conditions/ConditionsHeader';
import { ConditionsBoundControl } from './components/conditions/ConditionsBoundControl';
import { RuleRow } from './components/conditions/RuleRow';

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
	const targets = buildConditionsTargets(
		builderFields,
		String( values.data_name || '' )
	);
	const operatorGroups = buildOperatorGroups( i18n );
	const upgradeUrl = i18n.conditionUpgradeUrl || '';
	const upgradeCta = i18n.conditionUpgradeCta || 'Upgrade to unlock';

	const setConditions = ( nextCond: unknown ) => {
		onChange( { ...values, conditions: nextCond } );
	};

	const updateRule = (
		ruleKey: string,
		patch: Record< string, unknown >
	) => {
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

	return (
		<VStack align="stretch" gap={ 0 }>
			<ConditionsHeader
				title={ title }
				desc={ desc }
				logicOn={ logicOn }
				i18n={ i18n }
			/>
			<VStack align="stretch" gap={ 3 } mt={ 2 }>
				<ConditionsBoundControl
					visibility={ cond.visibility }
					bound={ cond.bound }
					i18n={ i18n }
					onVisibilityChange={ ( next ) =>
						setConditions( { ...cond, visibility: next } )
					}
					onBoundChange={ ( next ) =>
						setConditions( { ...cond, bound: next } )
					}
				/>

				{ ruleEntries.map( ( { key: ruleKey, rule }, idx ) => (
					<RuleRow
						key={ ruleKey }
						rule={ rule }
						ruleKey={ ruleKey }
						index={ idx }
						isLast={ ruleKey === lastKey }
						canRemove={ ruleEntries.length > 1 }
						targets={ targets }
						builderFields={ builderFields }
						operatorGroups={ operatorGroups }
						conditionsProEnabled={ conditionsProEnabled }
						upgradeUrl={ upgradeUrl }
						upgradeCta={ upgradeCta }
						i18n={ i18n }
						onPatch={ updateRule }
						onAdd={ addRule }
						onRemove={ removeRule }
					/>
				) ) }
			</VStack>
		</VStack>
	);
}
