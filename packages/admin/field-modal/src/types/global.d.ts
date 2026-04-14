import type { PpomFieldModalBoot, PpomFieldModalEditorsMap } from './fieldModal';

declare global {
	interface Window {
		ppomFieldModalBoot?: PpomFieldModalBoot;
		ppomFieldModalEditors?: PpomFieldModalEditorsMap;
	}
}

export {};
