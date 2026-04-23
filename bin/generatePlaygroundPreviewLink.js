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
	steps: [
		{
			step: 'runPHP',
			code: `<?php
				require '/wordpress/wp-load.php';
				update_option( 'woocommerce_onboarding_profile', [
					'completed' => true,
					'skipped'   => true,
				] );
				update_option( 'woocommerce_task_list_hidden', 'yes' );
				update_option( 'woocommerce_task_list_welcome_modal_dismissed', 'yes' );
				update_option( 'woocommerce_task_list_complete', 'yes' );
				delete_transient( '_wc_activation_redirect' );
				delete_option( '_transient_wc_activation_redirect' );

				$samples = [
					[ 'name' => 'Custom T-Shirt',     'sku' => 'PPOM-TSHIRT',   'price' => '24.00', 'short' => 'Soft cotton tee, ready for your custom PPOM add-ons.' ],
					[ 'name' => 'Ceramic Mug',        'sku' => 'PPOM-MUG',      'price' => '12.50', 'short' => '11oz ceramic mug — add engraving or color options via PPOM.' ],
					[ 'name' => 'Hardcover Notebook', 'sku' => 'PPOM-NOTEBOOK', 'price' => '18.00', 'short' => '120-page lined notebook, perfect for monogram add-ons.' ],
					[ 'name' => 'Tote Bag',           'sku' => 'PPOM-TOTE',     'price' => '15.00', 'short' => 'Heavy canvas tote — test file uploads and text add-ons.' ],
					[ 'name' => 'Phone Case',         'sku' => 'PPOM-CASE',     'price' => '29.00', 'short' => 'Slim phone case — great for testing conditional PPOM fields.' ],
				];
				foreach ( $samples as $data ) {
					if ( wc_get_product_id_by_sku( $data['sku'] ) ) {
						continue;
					}
					$product = new WC_Product_Simple();
					$product->set_name( $data['name'] );
					$product->set_sku( $data['sku'] );
					$product->set_regular_price( $data['price'] );
					$product->set_short_description( $data['short'] );
					$product->set_status( 'publish' );
					$product->set_catalog_visibility( 'visible' );
					$product->set_virtual( false );
					$product->save();
				}
			`,
		},
	],
};

// Convert the blueprint object to JSON and then encode it in Base64.
const blueprintJson = JSON.stringify( blueprint );
const encodedBlueprint = Buffer.from( blueprintJson ).toString( 'base64' );

// Output the full preview link.
process.stdout.write( 'https://playground.wordpress.net/#' + encodedBlueprint );
