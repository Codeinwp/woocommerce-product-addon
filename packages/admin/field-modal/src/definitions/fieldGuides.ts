/**
 * Rich copy for the "Field Guide" preview panel shown in the Select Field Type modal.
 * The short PHP `description` remains the source for any non-modal UI; entries here
 * power the hovered-tile sidebar preview and (via `firstSentence`) the tile tooltip.
 */
import { __ } from '@wordpress/i18n';

export interface FieldGuideEntry {
	longDescription: string;
	examples?: string;
	features?: string;
	notRightFor?: string;
}

export const FIELD_GUIDES: Record< string, FieldGuideEntry > = {
	text: {
		longDescription: __(
			'Adds a single-line text field to the product page. Customers type short text that gets attached to their order.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Engraved name on a ring, monogram initials on a wallet, jersey number on a shirt, gift tag message.',
			'woocommerce-product-addon'
		),
		features: __(
			'Set min/max character limits, add input masks for formatted data (e.g. phone format), set a default value, make required or optional. Can add a fixed price for the personalization.',
			'woocommerce-product-addon'
		),
	},
	textarea: {
		longDescription: __(
			'Multi-line text area for longer customer input. Expands to show several lines at once.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Special cake inscription, custom poem for a print, detailed order instructions, dedication text for a book.',
			'woocommerce-product-addon'
		),
		features: __(
			'Character limits, optional rich text editor, can be pre-filled with a default value. Use instead of Text Input when customers need to write more than a few words.',
			'woocommerce-product-addon'
		),
	},
	email: {
		longDescription: __(
			'Email address field with automatic format validation. Ensures the customer enters a valid email address.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Recipient email for a digital gift card, contact email for custom order follow-up, email for a subscription product activation.',
			'woocommerce-product-addon'
		),
		features: __(
			'Built-in email format validation, custom error messages, default value support.',
			'woocommerce-product-addon'
		),
	},
	number: {
		longDescription: __(
			'Accepts only numeric values with spinner arrows. Supports min, max, and step constraints.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Number of pages for a custom notebook, desired width in centimeters, age for an age-restricted product, number of copies.',
			'woocommerce-product-addon'
		),
		features: __(
			'Min/max range, step intervals (e.g. increments of 5), default value. Use this for plain number collection. For numbers that affect pricing, see Measure Input or Quantity Option instead.',
			'woocommerce-product-addon'
		),
	},
	hidden: {
		longDescription: __(
			'Invisible field that is not shown on the product page but submits a preset value with the order. Optionally visible on the cart page.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Internal order routing code, default processing type, campaign tracking ID, pre-set configuration value.',
			'woocommerce-product-addon'
		),
		features: __(
			'Set a fixed value, toggle cart page visibility. Useful for passing data behind the scenes without customer interaction.',
			'woocommerce-product-addon'
		),
	},
	phone: {
		longDescription: __(
			'Phone number field with an international country code selector and format validation.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Contact phone for custom orders, recipient phone for delivery, callback number for services.',
			'woocommerce-product-addon'
		),
		features: __(
			'Country flag dropdown with auto-detected country codes, phone format validation, Material Design styling option.',
			'woocommerce-product-addon'
		),
	},
	checkbox: {
		longDescription: __(
			'Multiple-choice checkboxes where customers can select one or more options. Each option can have its own add-on price.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Pizza toppings (pepperoni, mushrooms, olives), gift add-ons (wrapping + card + ribbon), T-shirt features (pocket, embroidery, name tag).',
			'woocommerce-product-addon'
		),
		features: __(
			'Set min/max number of allowed selections, set default checked options, individual pricing or discounts per option. Use instead of Radio or Select when customers should be able to pick multiple items.',
			'woocommerce-product-addon'
		),
	},
	radio: {
		longDescription: __(
			'Single-choice radio buttons shown as a visible list. Customer must pick exactly one option.',
			'woocommerce-product-addon'
		),
		examples: __(
			'T-shirt size (S, M, L, XL), print finish (matte, glossy, satin), delivery speed (standard, express, overnight).',
			'woocommerce-product-addon'
		),
		features: __(
			'Default selected option, add-on pricing per option. All options are visible at once, making it easy to compare. Use Select instead if you have many options and want to save space.',
			'woocommerce-product-addon'
		),
	},
	select: {
		longDescription: __(
			'Dropdown menu that lets the customer pick one option from a list. Options are hidden until the dropdown is opened.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Country selection, material choice (wood, metal, plastic, ceramic), font style for engraving when there are 20+ fonts.',
			'woocommerce-product-addon'
		),
		features: __(
			'Default selection, add-on pricing per option. Best for long lists (5+ options) where showing all at once would clutter the page. For shorter lists, Radio gives better visibility.',
			'woocommerce-product-addon'
		),
	},
	switcher: {
		longDescription: __(
			'Visual toggle-style radio buttons displayed as a compact switch bar. Functionally identical to Radio, but with a modern, button-like appearance. Can change the product image when an option is selected.',
			'woocommerce-product-addon'
		),
		examples: __(
			'T-shirt color with image swap, plan tier (Basic, Pro, Enterprise), material type with product preview.',
			'woocommerce-product-addon'
		),
		features: __(
			'Attach images per option to swap the product image on selection, add-on pricing, color customization for the switch bar.',
			'woocommerce-product-addon'
		),
	},
	superlist: {
		longDescription: __(
			'Dropdown selection from predefined comprehensive lists like countries, languages, months, or colors. Saves time when you need a standard list of many options.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Country of residence, preferred language, birth month, color preference from a standard palette.',
			'woocommerce-product-addon'
		),
		features: __(
			'Built-in reference lists (Countries, Languages, Months, Colors), ability to exclude specific options from the list. Avoids manually entering hundreds of options.',
			'woocommerce-product-addon'
		),
	},
	chained: {
		longDescription: __(
			'Linked dropdown fields where the choices in each dropdown depend on the previous selection. Supports two or more levels of chaining.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Car parts: Make > Model > Year. Location: Country > State > City. Fashion: Category > Brand > Style.',
			'woocommerce-product-addon'
		),
		features: __(
			'Multi-level dependent dropdowns, each level filters based on previous selection. Keeps forms clean by only showing relevant options at each step.',
			'woocommerce-product-addon'
		),
	},
	file: {
		longDescription: __(
			'Lets customers upload files that get attached to their order. Supports images, PDFs, documents, and other file types.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Logo file for custom printing on a mug, design artwork for a T-shirt, reference photo for a portrait painting, document for a print service.',
			'woocommerce-product-addon'
		),
		features: __(
			'Restrict allowed file types (jpg, png, pdf, etc.), set min/max image dimensions, multiple file upload support. Files are stored and accessible from the order details.',
			'woocommerce-product-addon'
		),
	},
	emojis: {
		longDescription: __(
			'Text entry field with an integrated emoji picker. Can render as a text input, textarea, or dropdown.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Personalized greeting card message with emojis, cake decoration text with emoji symbols, fun product labels.',
			'woocommerce-product-addon'
		),
		features: __(
			'Searchable emoji library, choice of input type (single-line, multi-line, or dropdown). Combines text and emoji in a single field.',
			'woocommerce-product-addon'
		),
	},
	conditional_meta: {
		longDescription: __(
			'Image selection field where each image option can trigger a different set of PPOM fields to appear. The selected image can also swap the product gallery image.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Phone case style selector where each style shows different customization options, product variant preview where "Engraved" shows text fields and "Printed" shows file upload.',
			'woocommerce-product-addon'
		),
		features: __(
			'Link each image to a different PPOM field group (meta ID), dynamic field visibility based on selection. Combines visual preview with conditional form logic.',
			'woocommerce-product-addon'
		),
	},
	image: {
		longDescription: __(
			'Displays options as clickable image thumbnails. Customer clicks an image to select it. Supports single or multiple selection with optional pricing.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Fabric pattern selection for upholstery, frame style for a print, charm shape for a bracelet, design template for invitations.',
			'woocommerce-product-addon'
		),
		features: __(
			'Upload images per option, add-on pricing (fixed or percentage), selection limits. Use instead of Radio/Checkbox when a visual preview is more important than text labels.',
			'woocommerce-product-addon'
		),
	},
	imageselect: {
		longDescription: __(
			'A dropdown menu where each option shows a small image alongside its label. Saves space while still giving visual context.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Color selection with color swatch previews, product accessory with thumbnail, frame material with texture preview.',
			'woocommerce-product-addon'
		),
		features: __(
			'Gallery integration, option to replace the main product image on selection, adjustable dropdown height. Use instead of Images when you have many visual options and need a compact layout.',
			'woocommerce-product-addon'
		),
	},
	audio: {
		longDescription: __(
			'Lets customers listen to or watch media clips, then select the one they want. Each option is an uploaded audio or video file.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Music box melody selection, video message template for a digital gift, ringtone selection for a custom device, sound effect for a toy.',
			'woocommerce-product-addon'
		),
		features: __(
			'Upload audio/video files per option, inline playback preview, multiple selection support.',
			'woocommerce-product-addon'
		),
	},
	cropper: {
		longDescription: __(
			'File upload with a built-in cropping interface. Customer uploads an image, then crops it to your required dimensions before submitting.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Profile photo for an ID badge, image for a custom phone case that must fit exact dimensions, headshot for a yearbook print.',
			'woocommerce-product-addon'
		),
		features: __(
			'Circle or square crop viewport, define exact crop dimensions, restrict file types. Ensures uploaded images always match your production requirements.',
			'woocommerce-product-addon'
		),
	},
	texter: {
		longDescription: __(
			"Overlays the customer's typed text directly on the product image in real time. As they type, they see exactly how their personalization will look on the product.",
			'woocommerce-product-addon'
		),
		examples: __(
			'Name engraving preview on a mug, custom text on a T-shirt mockup, monogram preview on leather goods, message preview on a sign.',
			'woocommerce-product-addon'
		),
		features: __(
			'Multiple text areas on one image, font family selection, text alignment, color settings. Reduces "what will it look like?" support questions.',
			'woocommerce-product-addon'
		),
	},
	date: {
		longDescription: __(
			'Calendar popup where the customer picks a single date. The selected date is attached to the order.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Preferred delivery date, event date for a custom banner, appointment date for a service, birth date for a personalized item.',
			'woocommerce-product-addon'
		),
		features: __(
			'Multiple date formats, restrict selectable date ranges (e.g. no past dates), set a default date. For picking a start and end date, use DateRange instead.',
			'woocommerce-product-addon'
		),
	},
	timezone: {
		longDescription: __(
			'Dropdown with all standard timezones. Customer selects their timezone which is saved with the order.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Timezone for a live online class, scheduling timezone for a consulting session, delivery timezone for time-sensitive products.',
			'woocommerce-product-addon'
		),
		features: __(
			'Predefined timezone list, default selection. Often paired with the Date field.',
			'woocommerce-product-addon'
		),
	},
	daterange: {
		longDescription: __(
			'Calendar picker for selecting a start date and end date as a range. Optionally includes a time picker.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Hotel check-in and check-out dates, equipment rental period, event duration, subscription start and end.',
			'woocommerce-product-addon'
		),
		features: __(
			'Visual range selection on calendar, optional time component, required/optional. Use instead of Date when you need two dates (from/to).',
			'woocommerce-product-addon'
		),
	},
	section: {
		longDescription: __(
			'Displays custom HTML content on the product page. This is not an input field — it shows information only. Optionally also shown on the cart page.',
			'woocommerce-product-addon'
		),
		examples: __(
			'"This product ships in 3-5 business days" notice, care instructions, size guide, region-specific availability message, embedded video tutorial.',
			'woocommerce-product-addon'
		),
		features: __(
			'Full HTML support (text, links, images, embeds), optional cart page display. Use for any static information the customer needs to see alongside the form fields.',
			'woocommerce-product-addon'
		),
	},
	color: {
		longDescription: __(
			'Full spectrum color picker. Customer clicks on a color wheel or enters a hex code to select any color.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Custom paint color for furniture, thread color for embroidery, background color for a custom print, LED light color.',
			'woocommerce-product-addon'
		),
		features: __(
			'Color spectrum selector, hex code input, optional preset palette display, opens on click or always visible. For limiting customers to specific predefined colors, use Color Palettes instead.',
			'woocommerce-product-addon'
		),
	},
	palettes: {
		longDescription: __(
			'Displays predefined color swatches as clickable circles or squares. Customer picks from your curated set of colors.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Available T-shirt colors, enamel paint options for jewelry, leather color for a handbag, ink color for stamping.',
			'woocommerce-product-addon'
		),
		features: __(
			'Circle or square swatch shapes, single or multiple selection, add-on pricing per color, custom border color. Use instead of Color Picker when you want to restrict choices to specific colors you actually offer.',
			'woocommerce-product-addon'
		),
	},
	fonts: {
		longDescription: __(
			'Font family selector with live text preview. Customer picks a font and sees how their text will look in that font before ordering.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Font for engraved text on a trophy, lettering style for a custom sign, embroidery font for a monogram, typeface for wedding invitations.',
			'woocommerce-product-addon'
		),
		features: __(
			'Google Fonts integration, upload custom fonts via @font-face, live preview of selected font, default font selection. Often paired with a Text Input field.',
			'woocommerce-product-addon'
		),
	},
	domain: {
		longDescription: __(
			'Domain name entry field with real-time availability checking. Customer types a domain and presses Enter to check if it is available.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Domain registration bundled with a hosting plan, website package that includes a custom domain, business starter kit with domain setup.',
			'woocommerce-product-addon'
		),
		features: __(
			'Real-time domain validation on Enter key, custom messages for available/unavailable domains.',
			'woocommerce-product-addon'
		),
	},
	textcounter: {
		longDescription: __(
			'Text input with a live character or word counter. Can charge per character or per word, making the price scale with input length.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Engraving text charged at $0.50 per character, custom label printing charged per word, embroidered message with a 20-character limit.',
			'woocommerce-product-addon'
		),
		features: __(
			'Count by characters or words, set per-unit price, enforce min/max limits. Use instead of Text Input when you need to charge based on text length.',
			'woocommerce-product-addon'
		),
	},
	collapse: {
		longDescription: __(
			'Groups other fields into a collapsible section with a toggle header. Click to expand or collapse the section.',
			'woocommerce-product-addon'
		),
		examples: __(
			'"Advanced Options" section that hides by default, "Gift Options" toggle containing message + wrapping fields, "Technical Specifications" for complex products.',
			'woocommerce-product-addon'
		),
		features: __(
			'Add a Collapse Start field, then your fields, then a Collapse End field. Works with all 30+ field types. Keeps long forms manageable without removing options.',
			'woocommerce-product-addon'
		),
	},
	divider: {
		longDescription: __(
			'Adds a visual separator line between fields. Not an input — purely for layout and organization.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Separate "Personalization" fields from "Delivery" fields, visual break between required and optional sections.',
			'woocommerce-product-addon'
		),
		features: __(
			'Five built-in line styles to choose from.',
			'woocommerce-product-addon'
		),
	},
	fixedprice: {
		longDescription: __(
			'A list of options displayed as radio buttons or a dropdown, where each option adds a fixed amount to the product price. The price is always the same regardless of quantity or other inputs.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Gift wrapping (+$5), rush processing (+$15), warranty upgrade (+$25), premium packaging (+$3).',
			'woocommerce-product-addon'
		),
		features: __(
			'Radio or dropdown display, custom quantity and price labels, decimal precision settings.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You need the price to change based on quantity (use Quantity Option) or based on a measurement (use Measure Input).',
			'woocommerce-product-addon'
		),
	},
	quantities: {
		longDescription: __(
			'Replaces the standard WooCommerce variation dropdown with individual quantity inputs for each variation. Customers can order multiple variations at once in a single add-to-cart action.',
			'woocommerce-product-addon'
		),
		examples: __(
			'T-shirt ordering: 3x Small + 2x Medium + 1x Large in one click. Flower arrangement: 5x Red Roses + 3x White Lilies. Office supplies: different quantities of each color.',
			'woocommerce-product-addon'
		),
		features: __(
			'Three layouts (vertical, horizontal, grid), min/max quantity per variation, optional variation images. Each variation is added to the cart as a separate line item.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You need a two-dimensional grid (Size x Color) — use Variation Matrix. If you just need add-on options with quantities (not WooCommerce variations), use Select Option Quantity.',
			'woocommerce-product-addon'
		),
	},
	pricematrix: {
		longDescription: __(
			'Calculates the price based on a quantity or measurement value using a pricing table you define. You set price breakpoints and the system looks up the correct price.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Business card printing: 100 cards = $20, 250 = $35, 500 = $50. Custom sign by area: up to 1 sqm = $50, up to 2 sqm = $80. The customer enters a number and sees the price update.',
			'woocommerce-product-addon'
		),
		features: __(
			'Optional quantity slider, background pricing (price hidden until selection), customizable quantity steps. The pricing table maps quantity/measurement ranges to specific prices.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You need a simple per-unit price (use Quantity Option) or tiered per-unit discounts (use Bulk Quantity).',
			'woocommerce-product-addon'
		),
	},
	qtypack: {
		longDescription: __(
			'Lets customers choose from predefined quantity bundles. Each bundle has a fixed total, and the customer distributes quantities across options within that pack size.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Cupcake box of 12: choose 4x Chocolate + 4x Vanilla + 4x Red Velvet (total must equal 12). Donut pack of 6: pick any mix of flavors. Seed variety pack: choose 5 types from the catalog.',
			'woocommerce-product-addon'
		),
		features: __(
			'Set max pack size, custom pack-size-exceeded message, min/max per option. The total across all options cannot exceed the pack size.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You just want a single quantity input with per-unit pricing (use Quantity Option) or if each option has an independent quantity (use Select Option Quantity).',
			'woocommerce-product-addon'
		),
	},
	vqmatrix: {
		longDescription: __(
			'Displays a two-dimensional grid table where rows and columns represent two variation attributes. Each cell is a quantity input. Designed for bulk or wholesale ordering.',
			'woocommerce-product-addon'
		),
		examples: __(
			'School uniform order: rows = Size (S/M/L/XL), columns = Color (Navy/White/Grey). Customer fills in 5x Medium Navy, 3x Large White, etc. T-shirt wholesale: Size x Color grid.',
			'woocommerce-product-addon'
		),
		features: __(
			'Attach images per option, label customization, min/max quantity per cell.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You only need quantities per single variation attribute (use Variation Quantity). If your product is not a WooCommerce variable product, this field is not applicable.',
			'woocommerce-product-addon'
		),
	},
	measure: {
		longDescription: __(
			'A numeric input where the customer enters a measurement and the price scales proportionally. You set a price per unit of measurement.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Fabric sold at $12/meter: customer enters 3.5m, pays $42. Custom rope at $2/foot. Flooring at $8/sq ft. Canvas priced by width x height with a price multiplier.',
			'woocommerce-product-addon'
		),
		features: __(
			'Price multiplier for unit conversion (e.g. meters to centimeters), min/max value constraints. The final price = base price multiplied by the entered measurement.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'The price is a flat fee per item (use Fixed Price) or you need tiered pricing (use Bulk Quantity).',
			'woocommerce-product-addon'
		),
	},
	quantityoption: {
		longDescription: __(
			'A single quantity input where the customer types how many they want. You set a price per unit, and the add-on price = unit price x quantity entered.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Additional watch bands at $8 each: customer enters 3, pays $24 extra. Extra buttons at $0.50 each. Custom sticker prints at $1.20 each.',
			'woocommerce-product-addon'
		),
		features: __(
			'Min/max quantity limits, per-unit pricing, simple quantity input.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You want a per-unit price that changes at different volumes (use Bulk Quantity), or you need multiple options each with their own quantity (use Select Option Quantity).',
			'woocommerce-product-addon'
		),
	},
	bulkquantity: {
		longDescription: __(
			'Quantity input where the per-unit price changes based on how many the customer orders. You define pricing tiers/ranges.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Custom magnets: 1-25 = $3 each, 26-100 = $2.50 each, 101+ = $1.80 each. Printed flyers with volume discounts. Wholesale pricing across product variations (S/M/L each with their own tiered price).',
			'woocommerce-product-addon'
		),
		features: __(
			'Multiple price tiers, price range display option, base price visibility toggle, works per variation. Customer enters a quantity and sees the applicable unit price.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You have a flat per-unit price that does not change (use Quantity Option) or predefined pack sizes (use Quantities Pack).',
			'woocommerce-product-addon'
		),
	},
	selectqty: {
		longDescription: __(
			'A dropdown where the customer first picks an option, then enters a quantity for that option. Each option can have a different price. The customer can add multiple options each with their own quantity.',
			'woocommerce-product-addon'
		),
		examples: __(
			'Pizza toppings: 2x Pepperoni ($1 each) + 3x Mushrooms ($0.75 each). Jewelry charms: 1x Heart ($5) + 2x Star ($4 each). Burger add-ons: 2x Extra Cheese + 1x Bacon.',
			'woocommerce-product-addon'
		),
		features: __(
			'Customizable option labels, independent quantity per option, per-option pricing.',
			'woocommerce-product-addon'
		),
		notRightFor: __(
			'You only need a single quantity input without choosing from options (use Quantity Option) or you want predefined packs with a total limit (use Quantities Pack).',
			'woocommerce-product-addon'
		),
	},
};

export function getFieldGuide( slug: string ): FieldGuideEntry | null {
	return FIELD_GUIDES[ slug ] ?? null;
}

/**
 * Extract the first sentence of a string for use as a tile tooltip.
 * Keeps the trailing punctuation; falls back to the full string if no terminal
 * punctuation is present.
 */
export function firstSentence( text: string ): string {
	if ( ! text ) {
		return '';
	}
	const m = text.match( /^(.+?[.!?])(\s|$)/ );
	return m ? m[ 1 ] : text;
}
