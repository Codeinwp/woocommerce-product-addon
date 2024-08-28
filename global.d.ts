interface PopupOptions {
    title?: string;
    text?: string;
    hideCloseBtn?: boolean;
    type?: 'error' | 'success';
    onConfirmation?: () => void;
    onClose?: () => void;
}

export declare class Popup {
    overlay: HTMLDivElement;
    popup: HTMLDivElement;
    container: HTMLDivElement;
    title: HTMLHeadingElement;
    text: HTMLParagraphElement;
    confirmButton: HTMLButtonElement;
    cancelButton: HTMLButtonElement;
    onConfirmation: () => void;
    onClose: () => void;

    constructor();
    open(options?: PopupOptions): void;
    close(): void;
    confirm(): void;
    show(): void;
    hide(): void;
}

interface GlobalVars {
    i18n: {
        addGroupUrl: string;
        addGroupLabel: string;
        bulkActionsLabel: string;
        deleteLabel: string;
        exportLabel: string;
        exportLockedLabel: string;
        importLabel: string;
        importLockedLabel: string;
        freemiumCFRContent: string;
        freemiumCFRTab: string;
        popup: {
            confirmTitle: string;
            confirmationBtn: string;
            cancelBtn: string;
            finishTitle: string;
            errorTitle: string;
            checkFieldTitle: string;
        }
    }
}

interface BulkQuantityGlobal {
    i18n: {
        validation: {
            end_bigger_than_start: string;
            start_cannot_be_equal_with_end: string;
            range_intersection: string;
            invalid_pattern: string;
        }
    }
}

interface FileUploadGlobal {
    ajaxurl: string;
    plugin_url: string;
    file_upload_path_thumb: string;
    file_upload_path: string;
    mesage_max_files_limit: string;
    file_inputs: any[];
    delete_file_msg: string;
    plupload_runtime: string;
    croppie_options: {
        viewport: {
            width: number;
            height: number;
            type: string;
        },
        boundary: {
            width: number;
            height: number;
        },
        enableExif: boolean;
        enforceBoundary: boolean;
        enableZoom: boolean;
        showZoomer: boolean;
    }[];
    ppom_file_upload_nonce: string;
    ppom_file_delete_nonce: string;
    enable_file_rename: boolean;
    product_id: number;
}

interface InputVarsGlobal {
    ajaxurl: string;
    ppom_inputs: any[]; 
    field_meta: any[];
    ppom_validate_nonce: string;
    wc_thousand_sep: string;
    wc_currency_pos: string;
    wc_decimal_sep: string;
    wc_no_decimal: number;
    wc_product_price: number;
    wc_product_regular_price: number;
    total_discount_label: string;
    price_matrix_heading: string;
    product_base_label: string;
    option_total_label: string;
    fixed_fee_heading: string;
    total_without_fixed_label: string;
    product_quantity_label: string;
    product_title: string;
    per_unit_label: string;
    show_price_per_unit: boolean;
    text_quantity: string;
    show_option_price: boolean;
    is_shortcode: string;
    plugin_url: string;
    is_mobile: boolean;
    product_id: number;
    tax_prefix: string;
}

/**
 * Define global variables to allow IDE auto-completion and checking.
 */
declare global {
    interface Window {
        ppomPopup?: Popup;
        ppom_vars: GlobalVars;
        ppom_file_vars: FileUploadGlobal;
        ppom_input_vars: InputVarsGlobal
    }
}