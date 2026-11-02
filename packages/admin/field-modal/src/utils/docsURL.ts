/**
 * Add the Themeisle UTM parameters to a docs link.
 *
 * Mirrors the SDK's `tsdk_utmify()` in PHP, so links built in the field modal
 * report the same utm_source, utm_medium, utm_campaign and utm_content values.
 *
 * @param url         The docs page URL.
 * @param utmCampaign The feature or placement the link belongs to.
 * @param utmMedium   The screen the link is shown on.
 * @return The URL with UTM parameters.
 *
 * @example
 * docsURL( 'https://docs.themeisle.com/product-option-manager/how-to-configure-the-chained-input', 'chained-input', 'field-editor' )
 * // https://docs.themeisle.com/product-option-manager/how-to-configure-the-chained-input?utm_source=wpadmin&utm_medium=field-editor&utm_campaign=chained-input&utm_content=docsproduct-option-managerhow-to-configure-the-chained-input
 */
export function docsURL(
	url: string,
	utmCampaign: string,
	utmMedium: string
): string {
	const content = [
		'https://',
		'themeisle.com',
		'/themes/',
		'/plugins/',
		'/upgrade',
	]
		.reduce( ( value, part ) => value.split( part ).join( '' ), url )
		.replace( /^\/+|\/+$/g, '' )
		.toLowerCase()
		.replace( /[^a-z0-9_-]/g, '' );

	const link = new URL( url );
	link.searchParams.set( 'utm_source', 'wpadmin' );
	link.searchParams.set( 'utm_medium', utmMedium );
	link.searchParams.set( 'utm_campaign', utmCampaign );
	link.searchParams.set( 'utm_content', content );

	return link.toString();
}
