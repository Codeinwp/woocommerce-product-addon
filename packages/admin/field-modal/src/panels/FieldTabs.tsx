/**
 * Definition-driven tab shell for field modal editors (replaces `SettingsConditionsTabs` long-term).
 */
import type { ReactNode } from 'react';
import { Box, Tabs } from '@chakra-ui/react';

export interface FieldTabItem {
	id: string;
	label: string;
	content: ReactNode;
}

export interface FieldTabsProps {
	tabs: FieldTabItem[];
	/** When omitted, first tab is selected. */
	defaultTabId?: string;
}

export function FieldTabs( { tabs, defaultTabId }: FieldTabsProps ) {
	if ( ! tabs.length ) {
		return null;
	}
	if ( tabs.length === 1 ) {
		return <Box w="100%">{ tabs[ 0 ].content }</Box>;
	}
	const defaultValue = defaultTabId ?? tabs[ 0 ].id;
	return (
		<Tabs.Root
			variant="line"
			colorPalette="blue"
			lazyMount
			defaultValue={ defaultValue }
		>
			<Tabs.List borderBottomColor="gray.200" mb={ 3 } gap={ 1 }>
				{ tabs.map( ( t ) => (
					<Tabs.Trigger
						key={ t.id }
						value={ t.id }
						fontWeight="semibold"
						px={ 1 }
						py={ 1.5 }
					>
						{ t.label }
					</Tabs.Trigger>
				) ) }
			</Tabs.List>
			<Tabs.ContentGroup>
				{ tabs.map( ( t ) => (
					<Tabs.Content key={ t.id } value={ t.id } px={ 0 } pt={ 0 }>
						<Box w="100%">{ t.content }</Box>
					</Tabs.Content>
				) ) }
			</Tabs.ContentGroup>
		</Tabs.Root>
	);
}
