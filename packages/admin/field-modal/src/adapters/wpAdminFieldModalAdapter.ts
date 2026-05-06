/**
 * Binds classic PPOM admin triggers to the React field modal opener.
 */
import type { FieldRow } from "../types/fieldModal";

export type FieldModalEntryMode = "picker" | "manage" | "copy";
export type ClassicFieldRow = Omit<FieldRow, "clientId">;

export interface FieldModalOpenPayload {
  entry: FieldModalEntryMode;
  selectFieldIndex?: number;
}

export interface BindPpomReactFieldModalOpenOptions {
  onOpen: (payload: FieldModalOpenPayload) => void;
}

function parseRowFieldIndex(btn: HTMLElement): number | undefined {
  const rawId = btn.getAttribute("id");
  const parsed = rawId ? parseInt(rawId, 10) : NaN;
  return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function asString(value: unknown, fallback = ""): string {
  if (value === null || value === undefined) {
    return fallback;
  }
  if (typeof value === "boolean") {
    return value ? "on" : "";
  }
  return String(value);
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

function createHiddenInput(name: string, value: unknown): HTMLInputElement {
  const input = document.createElement("input");
  input.type = "hidden";
  input.name = name;
  input.value = asString(value);
  return input;
}

function createFieldIndexInput(value: number): HTMLInputElement {
  const input = document.createElement("input");
  input.type = "hidden";
  input.id = "field_index";
  input.value = String(value);
  return input;
}

function appendHiddenInputs(
  parent: HTMLElement,
  name: string,
  value: unknown,
): void {
  if (value === undefined) {
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((item, index) => {
      appendHiddenInputs(parent, `${name}[${index}]`, item);
    });
    return;
  }

  if (isPlainObject(value)) {
    Object.entries(value).forEach(([key, nestedValue]) => {
      appendHiddenInputs(parent, `${name}[${key}]`, nestedValue);
    });
    return;
  }

  parent.appendChild(createHiddenInput(name, value));
}

function getFieldValue(
  field: ClassicFieldRow,
  key: string,
  fallback = "",
): string {
  return asString(field[key], fallback);
}

function getRequiredText(field: ClassicFieldRow): string {
  const required = field.required;
  const isRequired = required === true || asString(required) === "on";
  const vars = (
    window as Window & {
      ppom_vars?: { i18n?: Record<string, string> };
    }
  ).ppom_vars;

  if (isRequired) {
    return vars?.i18n?.yes || "Yes";
  }
  return vars?.i18n?.no || "No";
}

function getSelectFieldText(): string {
  const vars = (
    window as Window & {
      ppom_vars?: { i18n?: Record<string, string> };
    }
  ).ppom_vars;
  return vars?.i18n?.selectField || "Select field";
}

function appendCell(
  row: HTMLTableRowElement,
  tag: "td" | "th",
  className: string,
  child: Node,
): HTMLElement {
  const cell = document.createElement(tag);
  cell.className = className;
  cell.appendChild(child);
  row.appendChild(cell);
  return cell;
}

function textSpan(className: string, text: string): HTMLSpanElement {
  const span = document.createElement("span");
  span.className = className;
  span.textContent = text;
  return span;
}

function createActionButton(
  className: string,
  iconClass: string,
  index: number,
  title: string,
  fieldType?: string,
): HTMLButtonElement {
  const button = document.createElement("button");
  button.type = "button";
  button.className = `button ${className}`;
  button.id = String(index);
  button.title = title;
  if (fieldType) {
    button.dataset.fieldType = fieldType;
  }
  if (className === "ppom-edit-field") {
    button.dataset.modalId = `ppom_field_model_${index}`;
  }

  const icon = document.createElement("span");
  icon.className = `dashicons ${iconClass}`;
  icon.setAttribute("aria-hidden", "true");
  button.appendChild(icon);

  const label = document.createElement("span");
  label.className = "screen-reader-text";
  label.textContent = title;
  button.appendChild(label);

  return button;
}

function createFieldRow(
  field: ClassicFieldRow,
  index: number,
): HTMLTableRowElement {
  const row = document.createElement("tr");
  row.className = `row_no_${index}`;
  row.id = `ppom_sort_id_${index}`;

  const orderIcon = document.createElement("span");
  orderIcon.className = "ppom-sortable-handle dashicons dashicons-move";
  orderIcon.setAttribute("aria-hidden", "true");
  appendCell(row, "td", "order column-order", orderIcon);

  const checkboxWrapper = document.createElement("span");
  const checkboxLabel = document.createElement("label");
  checkboxLabel.className = "screen-reader-text";
  checkboxLabel.htmlFor = `ppom-field-cb-${index}`;
  checkboxLabel.textContent = getSelectFieldText();
  checkboxWrapper.appendChild(checkboxLabel);
  const checkbox = document.createElement("input");
  checkbox.type = "checkbox";
  checkbox.id = `ppom-field-cb-${index}`;
  checkbox.value = String(index);
  checkboxWrapper.appendChild(checkbox);
  appendCell(row, "th", "check-column", checkboxWrapper);

  const status = getFieldValue(field, "status", "on") || "on";
  const statusWrapper = document.createElement("div");
  statusWrapper.className = "onoffswitch";
  const statusCheckbox = document.createElement("input");
  statusCheckbox.type = "checkbox";
  statusCheckbox.className = "onoffswitch-checkbox";
  statusCheckbox.id = `ppom-onoffswitch-${index}`;
  statusCheckbox.tabIndex = 0;
  statusCheckbox.checked = status === "on";
  statusWrapper.appendChild(statusCheckbox);
  const statusLabel = document.createElement("label");
  statusLabel.className = "onoffswitch-label";
  statusLabel.htmlFor = statusCheckbox.id;
  statusLabel.appendChild(textSpan("onoffswitch-inner", ""));
  statusLabel.appendChild(textSpan("onoffswitch-switch", ""));
  statusWrapper.appendChild(statusLabel);
  statusWrapper.appendChild(
    createHiddenInput(`ppom[${index}][status]`, status),
  );
  appendCell(row, "td", "status column-status", statusWrapper);

  appendCell(
    row,
    "td",
    "data_name column-data_name",
    textSpan("ppom_meta_field_id", getFieldValue(field, "data_name")),
  );
  appendCell(
    row,
    "td",
    "type column-type",
    textSpan("ppom_meta_field_type", getFieldValue(field, "type")),
  );
  appendCell(
    row,
    "td",
    "title column-title",
    textSpan("ppom_meta_field_title", getFieldValue(field, "title")),
  );
  appendCell(
    row,
    "td",
    "placeholder column-placeholder",
    textSpan("ppom_meta_field_plchlder", getFieldValue(field, "placeholder")),
  );
  appendCell(
    row,
    "td",
    "required column-required",
    textSpan("ppom_meta_field_req", getRequiredText(field)),
  );

  const actions = document.createElement("span");
  const fieldType = getFieldValue(field, "type");
  actions.appendChild(
    createActionButton(
      "ppom_copy_field",
      "dashicons-admin-page",
      index,
      "Copy Field",
      fieldType,
    ),
  );
  actions.appendChild(document.createTextNode(" "));
  actions.appendChild(
    createActionButton(
      "ppom-edit-field",
      "dashicons-edit",
      index,
      "Edit Field",
    ),
  );
  actions.appendChild(document.createTextNode(" "));
  actions.appendChild(
    createActionButton(
      "ppom-delete-field",
      "dashicons-trash",
      index,
      "Delete Field",
    ),
  );
  appendCell(row, "td", "actions column-actions", actions);

  return row;
}

function markClassicFormDirty(): void {
  const marker = document.createElement("button");
  marker.type = "button";
  marker.className = "ppom-add-fields-js-action";
  marker.hidden = true;
  document.body.appendChild(marker);
  marker.click();
  marker.remove();
}

export function getClassicDraftFields(): ClassicFieldRow[] | null {
  return window.ppomFieldModalClassicDraftFields || null;
}

export function commitFieldsToClassicForm(fields: ClassicFieldRow[]): void {
  const saveModel = document.querySelector(
    ".ppom-save-fields-meta .ppom_save_fields_model",
  ) as HTMLElement | null;
  const tableBody = document.querySelector(
    ".ppom_field_table tbody",
  ) as HTMLTableSectionElement | null;

  if (!saveModel || !tableBody) {
    throw new Error("PPOM field builder form was not found.");
  }

  saveModel.replaceChildren();

  fields.forEach((field, zeroIndex) => {
    const fieldIndex = zeroIndex + 1;
    Object.entries(field).forEach(([key, value]) => {
      appendHiddenInputs(saveModel, `ppom[${fieldIndex}][${key}]`, value);
    });
  });

  saveModel.appendChild(createFieldIndexInput(fields.length + 1));

  tableBody.replaceChildren(
    ...fields.map((field, index) => createFieldRow(field, index + 1)),
  );

  window.ppomFieldModalClassicDraftFields = fields.map((field) => ({
    ...field,
  }));
  markClassicFormDirty();
}

/**
 * Subscribes to `.ppom-react-field-modal-open` header buttons and to per-row
 * `.ppom-edit-field` / `.ppom_copy_field` buttons in the classic field table.
 * The per-row clicks are delegated at the document level in the capture phase
 * so they run before the jQuery handlers that would otherwise open or clone
 * the legacy inline modal.
 *
 * @param opts Adapter callbacks.
 * @return Cleanup that removes listeners.
 */
export function bindPpomReactFieldModalOpenButtons(
  opts: BindPpomReactFieldModalOpenOptions,
): () => void {
  const headerButtons = document.querySelectorAll(
    ".ppom-react-field-modal-open",
  );
  const headerHandlers: Array<{ btn: Element; onClick: () => void }> = [];
  headerButtons.forEach((btn) => {
    const onClick = () => {
      const mode = btn.getAttribute("data-ppom-react-mode");
      const fromPicker = mode === "picker";
      opts.onOpen({ entry: fromPicker ? "picker" : "manage" });
    };
    btn.addEventListener("click", onClick);
    headerHandlers.push({ btn, onClick });
  });

  const onRowFieldClickCapture = (event: Event) => {
    const target = event.target as HTMLElement | null;
    if (!target || typeof target.closest !== "function") {
      return;
    }
    const editBtn = target.closest(".ppom-edit-field") as HTMLElement | null;
    const copyBtn = target.closest(".ppom_copy_field") as HTMLElement | null;
    const btn = editBtn || copyBtn;
    if (!btn || btn.classList.contains("ppom-is-pro-field")) {
      return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    opts.onOpen({
      entry: copyBtn ? "copy" : "manage",
      selectFieldIndex: parseRowFieldIndex(btn),
    });
  };
  document.addEventListener("click", onRowFieldClickCapture, true);

  return () => {
    headerHandlers.forEach(({ btn, onClick }) =>
      btn.removeEventListener("click", onClick),
    );
    document.removeEventListener("click", onRowFieldClickCapture, true);
  };
}
