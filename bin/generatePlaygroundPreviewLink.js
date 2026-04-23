#!/usr/bin/env node
// Read GitHub Action environment variables.
const repoName = process.env.GITHUB_REPO_NAME || '';
const branchName = process.env.DEV_ZIP_BRANCH_NAME || '';
const gitSha8 = process.env.DEV_ZIP_GIT_SHA_8 || '';

// Create the blueprint object with necessary schema and options.
const blueprint = {
	preferredVersions: {
		php: '8.1',
	},
	plugins: [
		'woocommerce',
		'woocommerce-product-generator',
		`https://verti-artifacts.s3.amazonaws.com/${ repoName }-${ branchName }-${ gitSha8 }/woocommerce-product-addon.zip`,
	],
	login: true,
	landingPage: '/wp-admin/admin.php?page=ppom',
	features: {
		networking: true,
	},
};

// Convert the blueprint object to JSON and then encode it in Base64.
const blueprintJson = JSON.stringify( blueprint );
const encodedBlueprint = Buffer.from( blueprintJson ).toString( 'base64' );

// Output the full preview link.
process.stdout.write( 'https://playground.wordpress.net/#' + encodedBlueprint );
