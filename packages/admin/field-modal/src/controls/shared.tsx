import { Box, Field } from '@chakra-ui/react';
import { helperTextProps, labelProps } from './chakraFieldStyles';
import type { SettingRowContext } from '../types/fieldModal';

export interface PrimitiveSettingControlProps {
	settingKey: string;
	meta: Record< string, unknown >;
	ctx: SettingRowContext;
}

export function readControlTitle(
	settingKey: string,
	meta: Record< string, unknown >
): string {
	return meta.title ? String( meta.title ) : settingKey;
}

export function readControlDescription(
	meta: Record< string, unknown >
): string {
	return meta.desc ? String( meta.desc ) : '';
}

export function readControlValue(
	settingKey: string,
	ctx: SettingRowContext
): unknown {
	return ctx.values[ settingKey ];
}

export function updateFallbackSettingValue(
	ctx: SettingRowContext,
	settingKey: string,
	nextValue: unknown
): void {
	ctx.onChange( { ...ctx.values, [ settingKey ]: nextValue } );
}

export function renderHelperText(
	description: string,
	opts?: { allowHtml?: boolean }
): JSX.Element | null {
	if ( ! description ) {
		return null;
	}
	if ( opts?.allowHtml ) {
		return (
			<Field.HelperText
				{ ...helperTextProps }
				dangerouslySetInnerHTML={ { __html: description } }
			/>
		);
	}
	return <Field.HelperText { ...helperTextProps }>{ description }</Field.HelperText>;
}

export function renderMetaLink(
	link: unknown
): JSX.Element | null {
	if ( link == null || link === '' ) {
		return null;
	}
	return (
		<Box
			fontSize="xs"
			mt={ 1 }
			color="gray.600"
			css={ helperTextProps.css }
			dangerouslySetInnerHTML={ { __html: String( link ) } }
		/>
	);
}

export function normalizeToggleValue( value: unknown ): boolean {
	return (
		value === 'on' ||
		value === true ||
		value === '1' ||
		value === 1 ||
		value === 'true'
	);
}

export { labelProps };
