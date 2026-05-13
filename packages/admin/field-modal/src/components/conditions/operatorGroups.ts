import type { I18nDict } from '../../types/fieldModal';

export interface OperatorOption {
	value: string;
	label: string;
}

export interface OperatorGroup {
	label: string;
	options: OperatorOption[];
}

export function buildOperatorGroups( i18n: I18nDict ): OperatorGroup[] {
	return [
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
}
