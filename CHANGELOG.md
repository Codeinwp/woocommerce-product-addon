##### [Version 32.0.2](https://github.com/Codeinwp/woocommerce-product-addon/compare/v32.0.1...v32.0.2) (2022-11-24)

- [Fix] Unnecessary spaces in Text Area Field
- WC 7.1 Compatibility Info updated.
- [Fix] Use the correct text-domain in the translated string.
- [Fix] Change on the Fixed Fee TAX Price Calculation: If WC prices include taxes; inconsistent TAX calculation was happening on the Fixed Fees between the product page/cart page, that's fixed. https://github.com/Codeinwp/ppom-pro/issues/41
- [Fix] Change on the Fixed Fee TAX Price Calculation: Use the tax class of the dependent product for the fixed fee instead of the standard tax https://github.com/Codeinwp/ppom-pro/issues/82
- [Fix] [PPOM Pro] If a Price Matrix field was shown according to the two or more conditional field dependencies; the price matrix price was passed to the cart as 0. That's fixed. https://github.com/Codeinwp/ppom-pro/issues/40
- [Promote] Conditional Field Repeater freemium/locked tab
- [PPOM Pro] Compatibility for PPOM PRO Bulk Quantity Field.
- [Feat] New WP Filter Hook (ppom_legacy_input_meta_classes) was added. https://github.com/Codeinwp/woocommerce-product-addon/pull/78
- [Fix] Some minor fixes on Frontend Input Classes https://github.com/Codeinwp/woocommerce-product-addon/pull/78
- [Feat] Changelog viewer admin screen was created.
- Compatibility support for PPOM Pro v25.1.0

##### [Version 32.0.1](https://github.com/Codeinwp/woocommerce-product-addon/compare/v32.0.0...v32.0.1) (2022-10-19)

- [Fix] PPOM Field prices are not passed into the cart issue (affects only non-pro users) has been fixed. (that was a regression occurred after the v32.0.0 release)
- [Fix] in the various areas (cart, checkout, admin order details etc.); PPOM Field Titles are shown as data-name instead of human-readable field title issue has been fixed. (that was a regression occurred after the v32.0.0 release)
- [Fix] A minor fix on the ppom_hooks_save_cropped_image global function.

#### [Version 32.0.0](https://github.com/Codeinwp/woocommerce-product-addon/compare/v31.0.1...v32.0.0) (2022-10-18)


- [Fix] Order Again PPOM compatibility support has been added. [#47](https://github.com/Codeinwp/woocommerce-product-addon/issues/47) 
- [Fix] [Pro compatibility] Fix the conditional field issue of the PPOM Pro Image Dropdown field [#48](https://github.com/Codeinwp/woocommerce-product-addon/issues/48) 
- [Fix] [Pro compatibility] Fix for PPOM Pro (Image Cropper doesn't work with some AJAX add to cart plugins)
- [Fix] Reload the PPOM Field Group Listing screen if a field is deleted.
- [Fix] [Pro compatibility] Conditional Field Support of the Image Dropdown field of the PPOM Pro has been fixed.
- [Fix] [Pro compatibility] Product featured image is replaced by the ImageSelect field without making a selection
- Promote locked fields

##### [Version 31.0.1](https://github.com/Codeinwp/woocommerce-product-addon/compare/v31.0.0...v31.0.1) (2022-09-16)

- Themeisle-SDK upgraded to latest version (v3.2.30)

#### [Version 31.0.0](https://github.com/Codeinwp/woocommerce-product-addon/compare/v30.1.4...v31.0.0) (2022-09-12)

- Improvement on PPOM Pro compatibility
- Readme.txt updated.

##### [Version 30.1.4](https://github.com/Codeinwp/woocommerce-product-addon/compare/v30.1.3...v30.1.4) (2022-09-02)

- [Fix] The fatal error related to the tsdk_utmify() function being missing has been fixed.

##### [Version 30.1.3](https://github.com/Codeinwp/woocommerce-product-addon/compare/v30.1.2...v30.1.3) (2022-09-02)

- [Fix] Browser console logs have been removed.
- [Fix] The bug that occurs when a group saving empty fields has been fixed.
- [Tweak] Do not allow removing of the Administrator role from PPOM Permissions

##### [Version 30.1.2](https://github.com/Codeinwp/woocommerce-product-addon/compare/v30.1.1...v30.1.2) (2022-08-15)

- [Fix] A PHP Notice fixed and a technical improvement has been made.
- Readme.txt updated
- Improvement on the themeisle-sdk compatibility.

##### [Version 30.1.1](https://github.com/Codeinwp/woocommerce-product-addon/compare/v30.1.0...v30.1.1) (2022-08-12)

- change plugin maintainer
