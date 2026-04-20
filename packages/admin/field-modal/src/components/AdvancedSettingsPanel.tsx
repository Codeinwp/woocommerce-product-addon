/**
 * Switch toggle that reveals additional field sections (switch first, label after).
 */
import { useId, useState } from '@wordpress/element';
import { HStack, Switch, Text, VStack } from '@chakra-ui/react';

export interface AdvancedSettingsPanelProps {
	/** Label when the panel is collapsed (e.g. “Show advanced settings”). */
	showLabel: string;
	/** Label when the panel is expanded (e.g. “Hide advanced settings”). */
	hideLabel: string;
	children: React.ReactNode;
}

export function AdvancedSettingsPanel( {
	showLabel,
	hideLabel,
	children,
}: AdvancedSettingsPanelProps ) {
	const [ open, setOpen ] = useState( false );
	const switchId = useId();
	const labelText = open ? hideLabel : showLabel;

	return (
		<VStack align="stretch" gap={ 0 } width="100%">
			<HStack align="center" gap={ 2.5 } width="100%" py={ 1 }>
				<Switch.Root
					colorPalette="blue"
					checked={ open }
					onCheckedChange={ ( { checked } ) =>
						setOpen( Boolean( checked ) )
					}
					flexShrink={ 0 }
				>
					<Switch.HiddenInput id={ switchId } />
					<Switch.Control />
				</Switch.Root>
				<Text
					as="label"
					htmlFor={ switchId }
					flex="1"
					minW={ 0 }
					mb={ 0 }
					fontWeight="medium"
					fontSize="sm"
					color="gray.700"
					cursor="pointer"
					lineHeight="1.4"
				>
					{ labelText }
				</Text>
			</HStack>
			{ open ? (
				<VStack align="stretch" gap={ 3 } pt={ 3 } width="100%">
					{ children }
				</VStack>
			) : null }
		</VStack>
	);
}
