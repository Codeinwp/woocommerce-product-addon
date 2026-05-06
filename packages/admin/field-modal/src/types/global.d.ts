import type { PpomFieldModalBoot } from "./fieldModal";
import type { ClassicFieldRow } from "../adapters/wpAdminFieldModalAdapter";

declare global {
  interface Window {
    ppomFieldModalBoot?: PpomFieldModalBoot;
    ppomFieldModalClassicDraftFields?: ClassicFieldRow[];
  }
}

export {};
