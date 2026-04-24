/**
 * Pure helpers for the conditional logic editor.
 */
import { OPERATORS_FIELD_COMPATIBILITY } from '../conditionsConstants';
import type { FieldRow } from '../types/fieldModal';

export type ConditionRule = Record< string, unknown >;

export interface ConditionsState {
	visibility: string;
	bound: string;
	rules: Record< string, ConditionRule >;
}

export function emptyRule(): ConditionRule {
	return {
		elements: '',
		operators: 'is',
		element_values: '',
		element_constant: '',
		'cond-between-interval': { from: '', to: '' },
	};
}

export function normalizeConditions( raw: unknown ): ConditionsState {
	if ( ! raw || typeof raw !== 'object' ) {
		return {
			visibility: 'Show',
			bound: 'All',
			rules: { 0: emptyRule() },
		};
	}
	const rawObj = raw as Record< string, unknown >;
	const visibility = rawObj.visibility === 'Hide' ? 'Hide' : 'Show';
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
					typeof ( r as Record< string, unknown > )[
						'cond-between-interval'
					] === 'object'
						? ( ( r as Record< string, unknown > )[
								'cond-between-interval'
						  ] as Record< string, unknown > )
						: null;
				next[ k ] =
					r && typeof r === 'object'
						? {
								...emptyRule(),
									...r,
									'cond-between-interval': between
										? {
												from:
													between.from !== null &&
													between.from !== undefined
														? String( between.from )
														: '',
												to:
													between.to !== null &&
													between.to !== undefined
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
	return {
		visibility,
		bound,
		rules: rules as Record< string, ConditionRule >,
	};
}

export function reindexRules( rules: Record< string, ConditionRule > ) {
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

export function nextRuleIndex( rules: Record< string, ConditionRule > ) {
	const nums = Object.keys( rules ).map( Number );
	return ( nums.length ? Math.max( ...nums ) : -1 ) + 1;
}

export function canUseFieldType( fieldType: string ) {
	if ( ! fieldType ) {
		return false;
	}
	return Object.values( OPERATORS_FIELD_COMPATIBILITY ).some( ( list ) =>
		list.includes( fieldType )
	);
}

export function getComparisonOptionValues( field: Record< string, unknown > ) {
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
			if (
					o &&
					typeof o === 'object' &&
					'option' in o &&
					( o as { option?: unknown } ).option !== null &&
					( o as { option?: unknown } ).option !== undefined
				) {
					push( ( o as { option: unknown } ).option );
				}
		} );
	} else if ( opts && typeof opts === 'object' ) {
		Object.values( opts as Record< string, unknown > ).forEach( ( o ) => {
			if (
					o &&
					typeof o === 'object' &&
					'option' in o &&
					( o as { option?: unknown } ).option !== null &&
					( o as { option?: unknown } ).option !== undefined
				) {
					push( ( o as { option: unknown } ).option );
				}
		} );
	}

	const imgs = field.images;
	if ( Array.isArray( imgs ) ) {
		imgs.forEach( ( img: unknown ) => {
			if (
					img &&
					typeof img === 'object' &&
					'title' in img &&
					( img as { title?: unknown } ).title !== null &&
					( img as { title?: unknown } ).title !== undefined
				) {
					push( ( img as { title: unknown } ).title );
				}
		} );
	}

	return [ ...new Set( out ) ];
}

export function findFieldType( builderFields: FieldRow[], dataName: unknown ) {
	const id = String( dataName || '' ).trim();
	if ( ! id ) {
		return '';
	}
	const row = builderFields.find(
		( f ) => String( f.data_name || '' ).trim() === id
	);
	return row && row.type ? String( row.type ) : '';
}

export function operatorAllowedForTarget(
	operator: string,
	targetType: string
) {
	if ( ! targetType ) {
		return true;
	}
	const allowed = OPERATORS_FIELD_COMPATIBILITY[ operator ];
	if ( ! allowed ) {
		return true;
	}
	return allowed.includes( targetType );
}

export interface ConditionsTarget {
	fieldId: string;
	fieldLabel: string;
	fieldType: string;
	canUse: boolean;
}

export function buildConditionsTargets(
	builderFields: FieldRow[],
	selfDataName: string
): ConditionsTarget[] {
	const selfId = String( selfDataName || '' ).trim();
	return builderFields
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
		.filter( ( t ) => t.fieldId && t.fieldId !== selfId && t.canUse );
}
