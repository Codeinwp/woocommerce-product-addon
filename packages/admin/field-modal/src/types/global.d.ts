import type { PpomFieldModalBoot } from './fieldModal';

declare global {
	interface Window {
		ppomFieldModalBoot?: PpomFieldModalBoot;
	}
}

export {};
