/**
 * Canonical field-row helpers for the React field modal.
 */
import type { CatalogItem, FieldRow } from '../types/fieldModal';
import { newClientId, stripClientIds, withClientIds } from './clientIds';
import { stableStringifyFieldRow } from './fieldFormSync';
import { slugifyDataName } from './slugifyDataName';

export interface NormalizedFieldRows {
	rows: FieldRow[];
	cleanFieldSnapshots: Record< string, string >;
	dirtyClientIds: string[];
}

function catalogTitleForType(
	type: unknown,
	catalog: CatalogItem[] = []
): string {
	const slug = String( type || '' )
		.trim()
		.toLowerCase();
	if ( ! slug ) {
		return '';
	}
	const entry = catalog.find( ( item ) => item.slug === slug );
	return String( entry?.title || '' ).trim();
}

export function makeUniqueDataName(
	base: string,
	existing: Set< string >
): string {
	const fallback = base || 'field';
	if ( ! existing.has( fallback ) ) {
		return fallback;
	}

	let n = 2;
	while ( existing.has( `${ fallback }_${ n }` ) ) {
		n++;
	}
	return `${ fallback }_${ n }`;
}

export function existingDataNames(
	fields: FieldRow[],
	excludeClientId?: string
): Set< string > {
	return new Set(
		fields
			.filter( ( field ) => field.clientId !== excludeClientId )
			.map( ( field ) => String( field.data_name || '' ).trim() )
			.filter( ( dataName ) => dataName !== '' )
	);
}

export function serializePersistedFields( fields: FieldRow[] ): string {
	return stableStringifyFieldRow( stripClientIds( fields ) );
}

export function serializePersistedField( field: FieldRow ): string {
	return serializePersistedFields( [ field ] );
}

export function cleanSnapshotsForRows(
	rows: FieldRow[]
): Record< string, string > {
	return Object.fromEntries(
		rows.map( ( row ) => [ row.clientId, serializePersistedField( row ) ] )
	);
}

export function normalizeFieldRowsForSession(
	fields: FieldRow[],
	catalog: CatalogItem[] = []
): NormalizedFieldRows {
	const rowsWithIds = withClientIds( fields || [] );
	const cleanFieldSnapshots = cleanSnapshotsForRows( rowsWithIds );
	const existing = existingDataNames( rowsWithIds );
	const dirtyClientIds = new Set< string >();

	const rows = rowsWithIds.map( ( field ) => {
		const type = String( field.type || '' ).trim();
		const title =
			String( field.title || '' ).trim() ||
			catalogTitleForType( type, catalog ) ||
			type;
		const dataName = String( field.data_name || '' ).trim();

		let next = field;
		if ( title && String( field.title || '' ).trim() === '' ) {
			next = { ...next, title };
			dirtyClientIds.add( field.clientId );
		}

		if ( dataName !== '' ) {
			return next;
		}

		const dataNameBase = slugifyDataName( title );
		if ( ! dataNameBase ) {
			return next;
		}

		const generatedDataName = makeUniqueDataName( dataNameBase, existing );
		existing.add( generatedDataName );
		dirtyClientIds.add( field.clientId );
		return {
			...next,
			data_name: generatedDataName,
		};
	} );

	return {
		rows,
		cleanFieldSnapshots,
		dirtyClientIds: Array.from( dirtyClientIds ),
	};
}

export function createNewFieldRow( args: {
	slug: string;
	title: string;
	existingRows: FieldRow[];
} ): FieldRow {
	const dataName = makeUniqueDataName(
		slugifyDataName( args.title ) || slugifyDataName( args.slug ),
		existingDataNames( args.existingRows )
	);
	return {
		clientId: newClientId(),
		type: args.slug,
		title: args.title || args.slug,
		data_name: dataName,
		status: 'on',
	};
}

function stripCopySuffix( name: string ): string {
	return name.replace( /_copy(_\d+)?$/, '' );
}

function makeUniqueCopyDataName(
	source: string,
	existing: Set< string >
): string {
	const sourceBase = stripCopySuffix( source );
	const base = `${ sourceBase || 'field' }_copy`;
	return makeUniqueDataName( base, existing );
}

export function cloneFieldForDuplicate(
	source: FieldRow,
	allFields: FieldRow[]
): FieldRow {
	const dup = JSON.parse( JSON.stringify( source ) ) as FieldRow;
	Object.keys( dup ).forEach( ( key ) => {
		if ( key.startsWith( '__' ) ) {
			delete dup[ key ];
		}
	} );
	dup.clientId = newClientId();
	dup.data_name = makeUniqueCopyDataName(
		String( source.data_name || '' ).trim(),
		existingDataNames( allFields )
	);
	return dup;
}

export function applyDraftDataNamePolicy( args: {
	previous: FieldRow;
	next: FieldRow;
	fields: FieldRow[];
	locked: boolean;
} ): { row: FieldRow; locked: boolean } {
	const previousTitle = String( args.previous.title || '' );
	const nextTitle = String( args.next.title || '' );
	const previousDataName = String( args.previous.data_name || '' );
	const nextDataName = String( args.next.data_name || '' );
	const dataNameChanged = nextDataName !== previousDataName;
	const titleChanged = nextTitle !== previousTitle;
	const expectedForPreviousTitle = makeUniqueDataName(
		slugifyDataName( previousTitle ) ||
			slugifyDataName( String( args.previous.type || '' ) ),
		existingDataNames( args.fields, args.previous.clientId )
	);
	const expectedForNextTitle = makeUniqueDataName(
		slugifyDataName( nextTitle ) ||
			slugifyDataName( String( args.next.type || '' ) ),
		existingDataNames( args.fields, args.next.clientId )
	);
	const locked =
		args.locked ||
		( previousDataName !== '' &&
			previousDataName !== expectedForPreviousTitle );

	if ( dataNameChanged ) {
		return {
			row: args.next,
			locked: nextDataName !== expectedForNextTitle,
		};
	}

	if ( titleChanged && ! locked ) {
		return {
			row: {
				...args.next,
				data_name: expectedForNextTitle,
			},
			locked: false,
		};
	}

	return {
		row: args.next,
		locked,
	};
}
