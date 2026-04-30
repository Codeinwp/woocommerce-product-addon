/**
 * Conditional logic rules editor (parity with classes/fields.class.php html-conditions + ppom-admin.js).
 */
import { Button, HStack, Icon, Text, VStack } from '@chakra-ui/react';
import {
	useState,
	type Dispatch,
	type DragEvent,
	type ReactNode,
	type SetStateAction,
} from 'react';
import { LuPlus } from 'react-icons/lu';
import type { FieldModalLinks, FieldRow, I18nDict } from './types/fieldModal';
import {
	buildConditionsTargets,
	emptyRule,
	nextRuleIndex,
	normalizeConditions,
	reindexRules,
	type ConditionRule,
} from './utils/conditionsLogic';
import { arrayReorder } from './utils/arrayReorder';
import { buildOperatorGroups } from './components/conditions/operatorGroups';
import { ConditionsHeader } from './components/conditions/ConditionsHeader';
import { ConditionsBoundControl } from './components/conditions/ConditionsBoundControl';
import { ConditionsEmptyState } from './components/conditions/ConditionsEmptyState';
import { ConditionsRuleHeader } from './components/conditions/ConditionsRuleHeader';
import { RuleRow } from './components/conditions/RuleRow';

export interface ConditionsEditorProps {
	meta: Record< string, unknown >;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	builderFields?: FieldRow[];
	conditionsProEnabled?: boolean;
	links?: FieldModalLinks;
}

export function ConditionsEditor( {
	meta,
	values,
	onChange,
	i18n,
	builderFields = [],
	conditionsProEnabled = false,
	links = {},
}: ConditionsEditorProps ) {
	const title =
		i18n.condEditorTitle ||
		( meta.title ? String( meta.title ) : 'Conditional logic' );
	const desc =
		i18n.condEditorDesc ||
		( meta.desc
			? String( meta.desc )
			: 'Show or hide this field based on other product options.' );

	const logicOn =
		values.logic === 'on' || values.logic === true || values.logic === '1';

	const cond = normalizeConditions( values.conditions );
	const targets = buildConditionsTargets(
		builderFields,
		String( values.data_name || '' )
	);
	const operatorGroups = buildOperatorGroups( i18n );
	const upgradeUrl = links.conditionUpgradeUrl || '';
	const upgradeCta = i18n.conditionUpgradeCta || 'Upgrade to unlock';

	const setLogic = ( next: boolean ) => {
		onChange( { ...values, logic: next ? 'on' : 'off' } );
	};

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

	const reorderRules = ( from: number, slot: number ) => {
		const arr: ConditionRule[] = ruleEntries.map( ( e ) => e.rule );
		const next = arrayReorder( arr, from, slot );
		const nextRules: Record< string, ConditionRule > = {};
		next.forEach( ( r, i ) => {
			nextRules[ String( i ) ] = r;
		} );
		setConditions( { ...cond, rules: nextRules } );
	};

	const canRemove = ruleEntries.length > 1;

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
			<ConditionsHeader
				title={ title }
				desc={ desc }
				logicOn={ logicOn }
				i18n={ i18n }
				onToggle={ setLogic }
				withDivider={ logicOn }
			/>
			{ logicOn ? (
				<VStack align="stretch" gap={ 3 } mt={ 3 }>
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

					<VStack align="stretch" gap={ 2 }>
						<Text
							fontSize="sm"
							fontWeight="semibold"
							color="gray.800"
						>
							{ i18n.condConditionsLabel || 'Conditions' }
						</Text>
						<ConditionsRuleHeader i18n={ i18n } />
						<DraggableRuleList
							ruleEntries={ ruleEntries }
							onReorder={ reorderRules }
							renderRule={ ( ruleKey, rule, dragHandleProps ) => (
								<RuleRow
									rule={ rule }
									ruleKey={ ruleKey }
									canRemove={ canRemove }
									targets={ targets }
									builderFields={ builderFields }
									operatorGroups={ operatorGroups }
									conditionsProEnabled={
										conditionsProEnabled
									}
									upgradeUrl={ upgradeUrl }
									upgradeCta={ upgradeCta }
									i18n={ i18n }
									onPatch={ updateRule }
									onRemove={ removeRule }
									dragHandleProps={ dragHandleProps }
								/>
							) }
						/>
					</VStack>

					<HStack>
						<Button
							size="xs"
							colorPalette="blue"
							onClick={ addRule }
							mt={ 0.5 }
						>
							<Icon as={ LuPlus } boxSize={ 3 } mr={ 1 } />
							{ i18n.condAddCondition || 'Add condition' }
						</Button>
					</HStack>
				</VStack>
			) : (
				<ConditionsEmptyState
					i18n={ i18n }
					onEnable={ () => setLogic( true ) }
				/>
			) }
		</VStack>
	);
}

type RuleRenderer = (
	ruleKey: string,
	rule: ConditionRule,
	dragHandleProps: {
		onPointerDown: () => void;
		onPointerUp: () => void;
	}
) => ReactNode;

interface DraggableRuleListProps {
	ruleEntries: Array< { key: string; rule: ConditionRule } >;
	onReorder: ( from: number, slot: number ) => void;
	renderRule: RuleRenderer;
}

function DraggableRuleList( {
	ruleEntries,
	onReorder,
	renderRule,
}: DraggableRuleListProps ) {
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const [ dropEdge, setDropEdge ] = useState< {
		index: number;
		edge: 'above' | 'below';
	} | null >( null );

	const startDrag = ( index: number, e: DragEvent< HTMLDivElement > ) => {
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData( 'text/plain', String( index ) );
		setDragIndex( index );
	};

	const overRow = ( index: number, e: DragEvent< HTMLDivElement > ) => {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		const rect = e.currentTarget.getBoundingClientRect();
		const edge = e.clientY < rect.top + rect.height / 2 ? 'above' : 'below';
		setDropEdge( { index, edge } );
	};

	const dropOnRow = ( index: number, e: DragEvent< HTMLDivElement > ) => {
		e.preventDefault();
		if ( dragIndex === null ) {
			return;
		}
		const rect = e.currentTarget.getBoundingClientRect();
		const edge = e.clientY < rect.top + rect.height / 2 ? 'above' : 'below';
		const slot = edge === 'below' ? index + 1 : index;
		onReorder( dragIndex, slot );
		setDragIndex( null );
		setDropEdge( null );
	};

	const endDrag = () => {
		setDragIndex( null );
		setDropEdge( null );
	};

	return (
		<VStack align="stretch" gap={ 1.5 }>
			{ ruleEntries.map( ( { key, rule }, index ) => {
				const showEdge =
					dragIndex !== null &&
					dragIndex !== index &&
					dropEdge !== null &&
					dropEdge.index === index;
				let indicatorShadow: string | undefined;
				if ( showEdge && dropEdge.edge === 'above' ) {
					indicatorShadow =
						'inset 0 2px 0 0 var(--chakra-colors-blue-500)';
				} else if ( showEdge && dropEdge.edge === 'below' ) {
					indicatorShadow =
						'inset 0 -2px 0 0 var(--chakra-colors-blue-500)';
				}
				const isBeingDragged = dragIndex === index;
				return (
					<DraggableRow
						key={ key }
						isBeingDragged={ isBeingDragged }
						indicatorShadow={ indicatorShadow }
						onDragStart={ ( e ) => startDrag( index, e ) }
						onDragOver={ ( e ) => overRow( index, e ) }
						onDrop={ ( e ) => dropOnRow( index, e ) }
						onDragEnd={ endDrag }
					>
						{ ( dragHandleProps ) =>
							renderRule( key, rule, dragHandleProps )
						}
					</DraggableRow>
				);
			} ) }
		</VStack>
	);
}

interface DraggableRowProps {
	isBeingDragged: boolean;
	indicatorShadow: string | undefined;
	onDragStart: ( e: DragEvent< HTMLDivElement > ) => void;
	onDragOver: ( e: DragEvent< HTMLDivElement > ) => void;
	onDrop: ( e: DragEvent< HTMLDivElement > ) => void;
	onDragEnd: () => void;
	children: ( dragHandleProps: {
		onPointerDown: () => void;
		onPointerUp: () => void;
	} ) => ReactNode;
}

function DraggableRow( {
	isBeingDragged,
	indicatorShadow,
	onDragStart,
	onDragOver,
	onDrop,
	onDragEnd,
	children,
}: DraggableRowProps ) {
	const [ draggable, setDraggable ] = useState( false );
	return (
		<div
			draggable={ draggable }
			onDragStart={ onDragStart }
			onDragOver={ onDragOver }
			onDrop={ onDrop }
			onDragEnd={ () => {
				setDraggable( false );
				onDragEnd();
			} }
			style={ {
				opacity: isBeingDragged ? 0.4 : 1,
				boxShadow: indicatorShadow,
				transition: 'opacity 120ms ease, box-shadow 120ms ease',
			} }
		>
			{ children( {
				onPointerDown: () => setDraggable( true ),
				onPointerUp: () => setDraggable( false ),
			} ) }
		</div>
	);
}
