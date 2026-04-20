/**
 * Button toggle that reveals additional field sections (same card style as primary settings).
 */
import {
	Box,
	Button,
	Collapsible,
	Text,
	VStack,
	useCollapsibleContext,
} from '@chakra-ui/react';
import { LuChevronDown } from 'react-icons/lu';

export interface AdvancedSettingsPanelProps {
	/** Label when the panel is collapsed (e.g. “Show advanced settings”). */
	showLabel: string;
	/** Label when the panel is expanded (e.g. “Hide advanced settings”). */
	hideLabel: string;
	children: React.ReactNode;
}

function ToggleButtonLabel( {
	showLabel,
	hideLabel,
}: Pick< AdvancedSettingsPanelProps, 'showLabel' | 'hideLabel' > ) {
	const ctx = useCollapsibleContext();
	const open = ctx?.open ?? false;
	return (
		<Text as="span" fontWeight="medium" color="gray.700">
			{ open ? hideLabel : showLabel }
		</Text>
	);
}

export function AdvancedSettingsPanel( {
	showLabel,
	hideLabel,
	children,
}: AdvancedSettingsPanelProps ) {
	return (
		<Collapsible.Root defaultOpen={ false } lazyMount>
			<VStack align="stretch" gap={ 0 } width="100%">
				<Collapsible.Trigger asChild>
					<Button
						type="button"
						variant="outline"
						width="100%"
						justifyContent="space-between"
						size="sm"
						px={ 3 }
						py={ 2 }
						fontWeight="normal"
						borderColor="gray.200"
						_hover={ { bg: 'gray.50' } }
					>
						<ToggleButtonLabel
							showLabel={ showLabel }
							hideLabel={ hideLabel }
						/>
						<Collapsible.Indicator
							display="flex"
							alignItems="center"
							justifyContent="center"
						>
							<Box
								as={ LuChevronDown }
								boxSize="1.1rem"
								color="gray.500"
							/>
						</Collapsible.Indicator>
					</Button>
				</Collapsible.Trigger>
				<Collapsible.Content>
					<VStack align="stretch" gap={ 3 } pt={ 3 } width="100%">
						{ children }
					</VStack>
				</Collapsible.Content>
			</VStack>
		</Collapsible.Root>
	);
}
