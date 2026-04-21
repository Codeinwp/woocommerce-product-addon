import { Field, HStack, Link, Switch } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';

export interface EnableSwitchProps {
	enabled: boolean;
	i18n: I18nDict;
	onToggle: ( on: boolean ) => void;
}

export function EnableSwitch( { enabled, i18n, onToggle }: EnableSwitchProps ) {
	return (
		<Field.Root
			display="flex"
			flexDirection="row"
			alignItems="center"
			flexWrap="wrap"
			gap={ 3 }
			mb={ 4 }
		>
			<Switch.Root
				id="ppom-cfr-enable"
				colorPalette="blue"
				checked={ Boolean( enabled ) }
				onCheckedChange={ ( { checked: next } ) => onToggle( next ) }
			>
				<Switch.HiddenInput />
				<Switch.Control />
			</Switch.Root>
			<HStack gap={ 2 } alignItems="center" flex="1" flexWrap="wrap">
				<Field.Label
					htmlFor="ppom-cfr-enable"
					mb={ 0 }
					fontWeight="semibold"
				>
					{ i18n.cfrEnableLabel || 'Enable Conditional Repeat' }
				</Field.Label>
				{ i18n.cfrDocsUrl ? (
					<Link
						href={ i18n.cfrDocsUrl }
						fontSize="xs"
						color="blue.700"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ i18n.cfrLearnMore || 'Learn more' }
					</Link>
				) : null }
			</HStack>
		</Field.Root>
	);
}
