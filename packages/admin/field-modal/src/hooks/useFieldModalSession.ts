/**
 * Session controller for the PPOM React field modal workflow.
 */
import type { Dispatch, SetStateAction } from "react";
import { useCallback, useMemo, useReducer } from "@wordpress/element";
import { getFieldUiDefinition } from "../definitions/registry";
import { newClientId, stripClientIds, withClientIds } from "../utils/clientIds";
import { createInitialModalState, modalReducer } from "../state/modalReducer";
import { errorMessage } from "../utils/errorMessage";
import { stableStringifyFieldRow } from "../utils/fieldFormSync";
import { fieldModalI18n } from "../i18n";
import { useMountEffect } from "./useMountEffect";
import {
  defaultFieldModalSessionAdapters,
  type FieldModalSessionAdapters,
} from "../adapters/fieldModalSessionAdapters";
import type {
  CatalogGroup,
  FieldModalContextPayload,
  FieldRow,
  I18nDict,
  ModalContextValue,
  SchemaObject,
} from "../types/fieldModal";

function getFieldSaveValidationError(
  fields: FieldRow[],
  i18n: I18nDict,
): string {
  for (const field of fields) {
    if (!Object.prototype.hasOwnProperty.call(field, "data_name")) {
      continue;
    }

    if (String(field.data_name || "").trim() === "") {
      return i18n.dataNameRequired;
    }
  }

  return "";
}

function serializePersistedFields(fields: FieldRow[]): string {
  return stableStringifyFieldRow(stripClientIds(fields));
}

function serializePersistedField(field: FieldRow): string {
  return serializePersistedFields([field]);
}

function definitionNeedsServerSchema(slug: string): boolean {
  return slug === "texter";
}

function getLocalDefinitionSchema(type: unknown): SchemaObject | null {
  const slug = typeof type === "string" ? type.toLowerCase() : "";
  if (!slug) {
    return null;
  }
  const definition = getFieldUiDefinition(slug);
  if (!definition?.settings || definitionNeedsServerSchema(slug)) {
    return null;
  }
  return {
    type: slug,
    settings: definition.settings,
    tabs: definition.tabs,
  };
}

function stripCopySuffix(name: string): string {
  return name.replace(/_copy(_\d+)?$/, "");
}

function makeUniqueCopyDataName(source: string, existing: Set<string>): string {
  const base = `${stripCopySuffix(source)}_copy`;
  if (!existing.has(base)) {
    return base;
  }
  let n = 2;
  while (existing.has(`${base}_${n}`)) {
    n++;
  }
  return `${base}_${n}`;
}

function cloneFieldForDuplicate(
  source: FieldRow,
  allFields: FieldRow[],
): FieldRow {
  const existingDataNames = new Set(
    allFields.map((f) => String(f.data_name || "")),
  );
  const dup = JSON.parse(JSON.stringify(source)) as FieldRow;
  dup.clientId = newClientId();
  dup.data_name = makeUniqueCopyDataName(
    String(source.data_name || ""),
    existingDataNames,
  );
  return dup;
}

function fieldAtOneBasedIndex(
  rows: FieldRow[],
  selectFieldIndex?: number,
): FieldRow | null {
  if (
    typeof selectFieldIndex !== "number" ||
    Number.isNaN(selectFieldIndex) ||
    selectFieldIndex < 1
  ) {
    return null;
  }
  return rows[selectFieldIndex - 1] ?? null;
}

function cleanSnapshotsForRows(rows: FieldRow[]): Record<string, string> {
  return Object.fromEntries(
    rows.map((row) => [row.clientId, serializePersistedField(row)]),
  );
}

export interface FieldModalSessionState {
  open: boolean;
  pickerOpen: boolean;
  pickerQuery: string;
  loading: boolean;
  saving: boolean;
  error: string;
  ctx: FieldModalContextPayload | null;
  fields: FieldRow[];
  selectedId: string | null;
  editDraft: FieldRow | null;
  schemaLoading: boolean;
  schemaFetchError: string;
  modalEntry: "picker" | "manage";
}

export interface FieldModalSessionDerived {
  isDirty: boolean;
  isNewField: boolean;
  i18n: I18nDict;
  ppomFieldIndex: number;
  catalogGroups: CatalogGroup[];
  fieldTypeLabel: string;
  activeSchema: SchemaObject | null;
  modalContext: ModalContextValue;
}

export interface FieldModalSessionCommands {
  openPicker: () => void;
  showPicker: () => void;
  openEditor: (selectFieldIndex?: number) => void;
  copyField: (sourceFieldIndex?: number) => void;
  selectField: (id: string | null) => void;
  setPickerQuery: (query: string) => void;
  updateDraft: Dispatch<SetStateAction<FieldRow | null>>;
  addFieldOfType: (slug: string) => void;
  removeField: (clientId: string) => void;
  openLegacyEditor: () => void;
  save: () => void;
  close: () => void;
  clearError: () => void;
}

export interface FieldModalSession {
  state: FieldModalSessionState;
  derived: FieldModalSessionDerived;
  commands: FieldModalSessionCommands;
}

export function useFieldModalSession(
  productmetaId: number | undefined,
  adapters: FieldModalSessionAdapters = defaultFieldModalSessionAdapters,
): FieldModalSession {
  const [state, dispatch] = useReducer(
    modalReducer,
    undefined,
    createInitialModalState,
  );

  const i18n = fieldModalI18n;
  const ctx = state.ctx;

  const ensureSchemaForType = useCallback(
    async (type: unknown) => {
      const t = typeof type === "string" ? type.toLowerCase() : "";
      if (!t || getLocalDefinitionSchema(t) || state.schemasCache[t]) {
        return null;
      }
      dispatch({ type: "SET_SCHEMA_FETCH_ERROR", message: "" });
      dispatch({ type: "SET_SCHEMA_LOADING", loading: true });
      try {
        const schema = await adapters.transport.fetchSchema(t);
        if (schema) {
          dispatch({
            type: "SET_SCHEMA_FOR_TYPE",
            typeKey: t,
            schema,
          });
          return schema;
        }
        dispatch({
          type: "SET_SCHEMA_FETCH_ERROR",
          message: i18n.schemaEmptyResponse,
        });
        return null;
      } catch (e) {
        dispatch({
          type: "SET_SCHEMA_FETCH_ERROR",
          message: errorMessage(e),
        });
        return null;
      } finally {
        dispatch({ type: "SET_SCHEMA_LOADING", loading: false });
      }
    },
    [adapters.transport, i18n.schemaEmptyResponse, state.schemasCache],
  );

  const loadContext = useCallback(
    async (
      selectFieldIndex: number | undefined,
      entry: "picker" | "manage",
    ) => {
      dispatch({ type: "LOAD_CONTEXT_START" });
      try {
        const res = await adapters.transport.fetchContext(productmetaId);
        const rows = withClientIds(res.fields || []);
        const selectedRow =
          fieldAtOneBasedIndex(rows, selectFieldIndex) ??
          (entry === "manage" ? rows[0] ?? null : null);
        dispatch({
          type: "LOAD_CONTEXT_SUCCESS",
          ctx: res,
          fields: rows,
          cleanFieldSnapshots: cleanSnapshotsForRows(rows),
          selectedId: selectedRow?.clientId ?? null,
        });
        if (selectedRow) {
          void ensureSchemaForType(selectedRow.type);
        }
      } catch (e) {
        dispatch({
          type: "LOAD_CONTEXT_ERROR",
          message: errorMessage(e),
        });
      }
    },
    [adapters.transport, ensureSchemaForType, productmetaId],
  );

  const openPicker = useCallback(() => {
    dispatch({ type: "OPEN", entry: "picker" });
    void loadContext(undefined, "picker");
  }, [loadContext]);

  const showPicker = useCallback(() => {
    dispatch({ type: "SET_PICKER_OPEN", open: true });
  }, []);

  const openEditor = useCallback(
    (selectFieldIndex?: number) => {
      dispatch({ type: "OPEN", entry: "manage" });
      void loadContext(selectFieldIndex, "manage");
    },
    [loadContext],
  );

  const copyField = useCallback(
    async (sourceFieldIndex?: number) => {
      if (typeof sourceFieldIndex !== "number" || sourceFieldIndex < 1) {
        openEditor();
        return;
      }

      dispatch({ type: "OPEN", entry: "manage" });
      dispatch({ type: "LOAD_CONTEXT_START" });
      try {
        const res = await adapters.transport.fetchContext(productmetaId);
        const rows = withClientIds(res.fields || []);
        dispatch({
          type: "LOAD_CONTEXT_SUCCESS",
          ctx: res,
          fields: rows,
          cleanFieldSnapshots: cleanSnapshotsForRows(rows),
          selectedId: null,
        });
        const source = rows[sourceFieldIndex - 1];
        if (!source) {
          return;
        }
        const dup = cloneFieldForDuplicate(source, rows);
        dispatch({
          type: "DUPLICATE_FIELD_ROW",
          sourceClientId: source.clientId,
          newRow: dup,
          snapshot: serializePersistedField(dup),
        });
        void ensureSchemaForType(dup.type);
      } catch (e) {
        dispatch({
          type: "LOAD_CONTEXT_ERROR",
          message: errorMessage(e),
        });
      }
    },
    [adapters.transport, ensureSchemaForType, openEditor, productmetaId],
  );

  useMountEffect(() =>
    adapters.admin.bindOpenTriggers({
      onOpen: ({ entry, selectFieldIndex }) => {
        if (entry === "copy") {
          copyField(selectFieldIndex);
          return;
        }
        if (entry === "picker") {
          openPicker();
          return;
        }
        openEditor(selectFieldIndex);
      },
    }),
  );

  const editDraft = useMemo(() => {
    if (!state.selectedId) {
      return null;
    }
    return state.fields.find((f) => f.clientId === state.selectedId) ?? null;
  }, [state.fields, state.selectedId]);

  const ppomFieldIndex =
    state.selectedId && state.fields.length
      ? state.fields.findIndex((f) => f.clientId === state.selectedId) + 1
      : 0;

  const catalogGroups = useMemo(
    () =>
      ctx?.catalog_groups && ctx.catalog_groups.length > 0
        ? ctx.catalog_groups
        : [],
    [ctx?.catalog_groups],
  );

  const fieldTypeLabel = useMemo(() => {
    const raw = editDraft?.type;
    if (!raw || typeof raw !== "string") {
      return "";
    }
    const slug = raw.toLowerCase();
    const flat = ctx?.catalog;
    if (Array.isArray(flat)) {
      for (const c of flat) {
        if (
          c &&
          typeof c.slug === "string" &&
          c.slug.toLowerCase() === slug &&
          typeof c.title === "string" &&
          c.title.trim() !== ""
        ) {
          return c.title;
        }
      }
    }
    for (const g of catalogGroups) {
      const groupFields = g.fields;
      if (!Array.isArray(groupFields)) {
        continue;
      }
      for (const c of groupFields) {
        if (
          c &&
          typeof c.slug === "string" &&
          c.slug.toLowerCase() === slug &&
          typeof c.title === "string" &&
          c.title.trim() !== ""
        ) {
          return c.title;
        }
      }
    }
    return raw;
  }, [editDraft?.type, ctx?.catalog, catalogGroups]);

  const activeSchema =
    getLocalDefinitionSchema(editDraft?.type) ??
    (editDraft && editDraft.type
      ? state.schemasCache[String(editDraft.type).toLowerCase()] ?? null
      : null);

  const isDirty = useMemo(() => {
    return (
      state.dirtyClientIds.length > 0 ||
      state.removedPersistedClientIds.length > 0
    );
  }, [state.dirtyClientIds, state.removedPersistedClientIds]);

  const isNewField = useMemo(() => {
    if (!state.selectedId) {
      return false;
    }
    return !state.persistedClientIds.includes(state.selectedId);
  }, [state.selectedId, state.persistedClientIds]);

  const modalContext: ModalContextValue = useMemo(
    () => ({
      builderFields: state.fields,
      conditionsProEnabled: ctx?.conditions_pro_enabled === true,
      conditionalRepeaterUnlocked: ctx?.conditional_repeater_unlocked === true,
      conditionalRepeaterShowUpsell:
        ctx?.conditional_repeater_show_upsell === true,
      links: ctx?.links || {},
    }),
    [
      state.fields,
      ctx?.conditions_pro_enabled,
      ctx?.conditional_repeater_unlocked,
      ctx?.conditional_repeater_show_upsell,
      ctx?.links,
    ],
  );

  const setPickerQuery = useCallback((query: string) => {
    dispatch({ type: "SET_PICKER_QUERY", query });
  }, []);

  const selectField = useCallback(
    (id: string | null) => {
      dispatch({ type: "SET_SELECTED_ID", id });
      const row = id
        ? state.fields.find((field) => field.clientId === id)
        : null;
      if (row) {
        void ensureSchemaForType(row.type);
      }
    },
    [ensureSchemaForType, state.fields],
  );

  const updateDraft: Dispatch<SetStateAction<FieldRow | null>> = useCallback(
    (action) => {
      const current =
        state.selectedId === null || state.selectedId === undefined
          ? null
          : state.fields.find((f) => f.clientId === state.selectedId) ?? null;
      const row =
        typeof action === "function"
          ? (action as (p: FieldRow | null) => FieldRow | null)(current)
          : action;
      if (!row || typeof row !== "object" || !row.clientId) {
        return;
      }
      dispatch({
        type: "PATCH_FIELD_ROW_FROM_FORM",
        row,
        snapshot: serializePersistedField(row),
      });
      void ensureSchemaForType(row.type);
    },
    [ensureSchemaForType, state.fields, state.selectedId],
  );

  const addFieldOfType = useCallback(
    (slug: string) => {
      const flat = ctx?.catalog || [];
      const entry = flat.find((c) => c.slug === slug);
      if (entry && entry.locked) {
        return;
      }
      const title = (entry?.title as string | undefined) || slug;
      const row: FieldRow = {
        clientId: newClientId(),
        type: slug,
        title,
        data_name: "",
        status: "on",
      };
      dispatch({
        type: "ADD_FIELD_ROW",
        row,
        snapshot: serializePersistedField(row),
      });
      void ensureSchemaForType(row.type);
    },
    [ctx?.catalog, ensureSchemaForType],
  );

  const removeField = useCallback(
    (clientId: string) => {
      const nextFields = state.fields.filter(
        (field) => field.clientId !== clientId,
      );
      const nextSelected =
        state.selectedId === clientId
          ? nextFields[0] ?? null
          : state.fields.find((field) => field.clientId === state.selectedId) ??
            null;
      dispatch({ type: "REMOVE_FIELD_ROW", clientId });
      if (nextSelected) {
        void ensureSchemaForType(nextSelected.type);
      }
    },
    [ensureSchemaForType, state.fields, state.selectedId],
  );

  const openLegacyEditor = useCallback(() => {
    adapters.admin.openLegacyEditor(ppomFieldIndex);
  }, [adapters.admin, ppomFieldIndex]);

  const save = useCallback(async () => {
    if (!ctx) {
      return;
    }
    dispatch({ type: "CLEAR_ERROR" });
    const saveValidationError = getFieldSaveValidationError(state.fields, i18n);
    if (saveValidationError) {
      dispatch({
        type: "LOAD_CONTEXT_ERROR",
        message: saveValidationError,
      });
      return;
    }

    dispatch({ type: "SET_SAVING", saving: true });
    try {
      adapters.admin.commitFieldsToClassicForm(stripClientIds(state.fields));
      dispatch({ type: "CLOSE" });
    } catch (e) {
      dispatch({
        type: "LOAD_CONTEXT_ERROR",
        message: errorMessage(e),
      });
    } finally {
      dispatch({ type: "SET_SAVING", saving: false });
    }
  }, [adapters.admin, ctx, state.fields, i18n]);

  const close = useCallback(() => {
    dispatch({ type: "CLOSE" });
  }, []);

  const clearError = useCallback(() => {
    dispatch({ type: "CLEAR_ERROR" });
  }, []);

  return {
    state: {
      open: state.open,
      pickerOpen: state.pickerOpen,
      pickerQuery: state.pickerQuery,
      loading: state.loading,
      saving: state.saving,
      error: state.error,
      ctx,
      fields: state.fields,
      selectedId: state.selectedId,
      editDraft,
      schemaLoading: state.schemaLoading,
      schemaFetchError: state.schemaFetchError,
      modalEntry: state.modalEntry,
    },
    derived: {
      isDirty,
      isNewField,
      i18n,
      ppomFieldIndex,
      catalogGroups,
      fieldTypeLabel,
      activeSchema,
      modalContext,
    },
    commands: {
      openPicker,
      showPicker,
      openEditor,
      copyField,
      selectField,
      setPickerQuery,
      updateDraft,
      addFieldOfType,
      removeField,
      openLegacyEditor,
      save,
      close,
      clearError,
    },
  };
}
