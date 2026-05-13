import { Skeleton, VStack } from '@chakra-ui/react';

export function SchemaLoadingSkeleton() {
	return (
		<VStack gap={ 2 } align="stretch">
			<Skeleton height="36px" />
			<Skeleton height="36px" />
			<Skeleton height="72px" />
		</VStack>
	);
}
