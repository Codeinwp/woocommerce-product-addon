<?php
/**
 * Class Test_Cart_And_Options
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Cart_And_Options extends PPOM_Test_Case {

	/**
	 * Ensure cart meta generation sanitizes known fields and skips hidden/deleted ones.
	 *
	 * @return void
	 */
	public function testMakeMetaDataSanitizesRegisteredFieldsAndSkipsHiddenAndDeletedValues() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
				$this->build_text_field( 'hidden_note', 'Hidden Note' ),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'id'           => (string) $meta_id,
						'engraving'    => '<h1>Hello</h1>',
						'hidden_note'  => '<script>Secret</script>',
						'removed_field'=> 'Should not render',
					),
					'conditionally_hidden' => 'hidden_note',
				),
			),
			'cart'
		);

		$this->assertArrayHasKey( 'engraving', $meta );
		$this->assertSame( 'Engraving', $meta['engraving']['name'] );
		$this->assertSame( 'Hello', $meta['engraving']['value'] );
		$this->assertArrayNotHasKey( 'id', $meta );
		$this->assertArrayNotHasKey( 'hidden_note', $meta );
		$this->assertArrayNotHasKey( 'removed_field', $meta );
	}

	/**
	 * Ensure section/HTML fields with "Show in Cart" disabled produce no cart meta entry.
	 *
	 * @return void
	 */
	public function testMakeMetaDataSkipsSectionFieldWhenCartDisplayIsOff() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_section_field( 'terms_notice', 'Terms Notice', array( 'cart_display' => 'off' ) ),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'id'           => (string) $meta_id,
						'terms_notice' => '<p>Static content</p>',
					),
				),
			),
			'cart'
		);

		$this->assertArrayNotHasKey( 'terms_notice', $meta );
	}

	/**
	 * Ensure section/HTML fields with "Show in Cart" enabled still render in cart meta.
	 *
	 * @return void
	 */
	public function testMakeMetaDataRendersSectionFieldWhenCartDisplayIsOn() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_section_field( 'terms_notice', 'Terms Notice', array( 'cart_display' => 'on' ) ),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'id'           => (string) $meta_id,
						'terms_notice' => '<p>Static content</p>',
					),
				),
			),
			'cart'
		);

		$this->assertArrayHasKey( 'terms_notice', $meta );
		$this->assertSame( 'Terms Notice', $meta['terms_notice']['name'] );
		$this->assertSame( 'Static content', $meta['terms_notice']['value'] );
	}

	/**
	 * Ensure checkbox arrays are rendered as a readable comma-separated value.
	 *
	 * @return void
	 */
	public function testMakeMetaDataFormatsCheckboxArrays() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_checkbox_field(
					'extras',
					'Extras',
					array(
						array(
							'option' => 'Red',
						),
						array(
							'option' => 'Blue',
						),
					)
				),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'extras' => array( 'Red', 'Blue' ),
					),
				),
			),
			'cart'
		);

		$this->assertSame( 'Extras', $meta['extras']['name'] );
		$this->assertSame( 'Red, Blue', $meta['extras']['value'] );
	}

	/**
	 * Ensure file and cropper fields render HTML previews in cart context.
	 *
	 * @return void
	 */
	public function testMakeMetaDataFormatsFileAndCropperFieldsForCartContext() {
		$product          = $this->create_simple_product();
		$file_name        = 'document.txt';
		$crop_source_name = 'canvas.txt';
		$cropped_name     = ppom_file_get_name( $crop_source_name, $product->get_id() );

		$this->insert_ppom_meta(
			array(
				$this->build_file_field( 'attachment', 'Attachment' ),
				$this->build_cropper_field(
					'avatar',
					'Avatar',
					array(
						array(
							'option' => 'Square',
							'id'     => 'avatar_square',
							'width'  => '120',
							'height' => '120',
						),
					)
				),
			),
			$product->get_id()
		);

		file_put_contents( ppom_get_dir_path() . $file_name, 'document body' );
		file_put_contents( ppom_get_dir_path() . $crop_source_name, 'source body' );
		file_put_contents( ppom_get_dir_path( 'cropped' ) . $cropped_name, 'cropped body' );

		try {
			$meta = ppom_make_meta_data(
				array(
					'data' => $product,
					'ppom' => array(
						'fields' => array(
							'attachment' => array(
								'file_1' => array(
									'org' => $file_name,
								),
							),
							'avatar'     => array(
								'ratio'  => 'avatar_square',
								'file_1' => array(
									'org' => $crop_source_name,
								),
							),
						),
					),
				),
				'cart'
			);
		} finally {
			$this->remove_ppom_upload_artifacts( $file_name );
			$this->remove_ppom_upload_artifacts( $crop_source_name );
			if ( file_exists( ppom_get_dir_path( 'cropped' ) . $cropped_name ) ) {
				unlink( ppom_get_dir_path( 'cropped' ) . $cropped_name );
			}
		}

		$this->assertStringContainsString( '<table', $meta['attachment']['value'] );
		$this->assertStringContainsString( $file_name, $meta['attachment']['value'] );
		$this->assertStringContainsString( $crop_source_name, $meta['avatar']['value'] );
		$this->assertStringContainsString( 'Your image-Square', $meta['avatar']['value'] );
	}

	/**
	 * Ensure file and cropper fields collapse to uploaded file names in order context.
	 *
	 * @return void
	 */
	public function testMakeMetaDataFormatsFileAndCropperFieldsForOrderContext() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_file_field( 'attachment', 'Attachment' ),
				$this->build_cropper_field(
					'avatar',
					'Avatar',
					array(
						array(
							'option' => 'Square',
							'id'     => 'avatar_square',
						),
					)
				),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'attachment' => array(
							'file_1' => array(
								'org' => 'document.txt',
							),
						),
						'avatar'     => array(
							'ratio'  => 'avatar_square',
							'file_1' => array(
								'org' => 'canvas.txt',
							),
						),
					),
				),
			),
			'order'
		);

		$this->assertSame( 'document.txt', $meta['attachment']['value'] );
		$this->assertSame( 'canvas.txt', $meta['avatar']['value'] );
	}

	/**
	 * Ensure invalid conditional rules are filtered before HTML attributes are rendered.
	 *
	 * @return void
	 */
	public function testGetConditionalDataAttributesFiltersInvalidRules() {
		$html = ppom_get_conditional_data_attributes(
			array(
				'type'  => 'text',
				'logic' => 'on',
				'conditions' => array(
					'bound'      => 'All',
					'visibility' => 'Show',
					'rules'      => array(
						array(
							'elements'       => 'field_a',
							'operators'      => 'is',
							'element_values' => 'yes',
						),
						array(
							'elements'       => 'field_b',
							'operators'      => 'is',
							'element_values' => '',
						),
						array(
							'elements'              => 'field_c',
							'operators'             => 'between',
							'cond-between-interval' => array(
								'from' => '1',
								'to'   => '3',
							),
						),
						array(
							'elements'              => 'field_d',
							'operators'             => 'between',
							'cond-between-interval' => array(
								'from' => '1',
							),
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'data-cond="1"', $html );
		$this->assertStringContainsString( 'data-cond-total="2"', $html );
		$this->assertStringContainsString( 'data-cond-operator1="is"', $html );
		$this->assertStringContainsString( 'data-cond-operator2="between"', $html );
		$this->assertStringNotContainsString( 'data-cond-operator3', $html );
	}

	/**
	 * Ensure option conversion handles placeholder options, stock filtering, and percentage pricing.
	 *
	 * @return void
	 */
	public function testConvertOptionsToKeyValAddsFirstOptionSkipsOutOfStockAndConvertsPercent() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '100',
			)
		);

		$options = ppom_convert_options_to_key_val(
			array(
				array(
					'option' => 'Sold Out',
					'price'  => '5',
					'stock'  => '0',
				),
				array(
					'option' => 'Premium',
					'price'  => '10%',
				),
			),
			array(
				'type'         => 'select',
				'data_name'    => 'plan',
				'first_option' => 'Choose one',
			),
			$product
		);

		$this->assertArrayHasKey( '', $options );
		$this->assertSame( 'Choose one', $options['']['label'] );
		$this->assertArrayHasKey( 'Premium', $options );
		$this->assertArrayNotHasKey( 'Sold Out', $options );
		$this->assertSame( '10%', $options['Premium']['percent'] );
		$this->assertSame( '10%', $options['Premium']['raw_price'] );
		$this->assertSame( 'plan_premium', $options['Premium']['option_id'] );
		$this->assertSame( 10.0, (float) $options['Premium']['price'] );
	}

	/**
	 * Image options keep the attachment id stored under `id`.
	 *
	 * @return void
	 */
	public function testConvertOptionsToKeyValKeepsStoredImageAttachmentId(): void {
		$product = $this->create_simple_product();

		$options = ppom_convert_options_to_key_val(
			array(
				array(
					'title' => 'Image A',
					'id'    => '1794',
					'link'  => 'https://example.com/image-a.jpg',
					'price' => '50',
				),
				array(
					'title' => 'Image B',
					'id'    => '1795',
					'link'  => 'https://example.com/image-b.jpg',
					'price' => '',
				),
			),
			array(
				'type'      => 'image',
				'data_name' => 'choose_logo',
			),
			$product
		);

		$this->assertSame( '1794', $options['Image A']['image_id'] );
		$this->assertSame( '1795', $options['Image B']['image_id'] );
		$this->assertNotSame( $options['Image A']['option_id'], $options['Image B']['option_id'] );
	}

	/**
	 * Ensure legacy option arrays without persisted IDs still resolve price by generated option ID.
	 *
	 * @return void
	 */
	public function testGetFieldOptionPriceByIdSupportsLegacyOptionsWithoutIds() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '100',
			)
		);

		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array(
							'option' => 'Gold',
							'price'  => '12.5',
						),
					)
				),
			),
			$product->get_id()
		);

		$field_meta      = ppom_get_field_meta_by_dataname( null, 'plan', $meta_id );
		$legacy_options  = ppom_convert_options_to_key_val( $field_meta['options'], $field_meta, $product );
		$generated_id    = $legacy_options['Gold']['id'];
		$price = ppom_get_field_option_price_by_id(
			array(
				'data_name' => 'plan',
				'option_id' => $generated_id,
			),
			$product,
			$meta_id
		);

		$this->assertSame( 12.5, (float) $price );
	}

	/**
	 * Ensure hidden-field resolution respects explicit lists and posted fallbacks.
	 *
	 * @return void
	 */
	public function testIsFieldHiddenByConditionUsesProvidedListAndPostFallback() {
		$this->assertFalse( ppom_is_field_hidden_by_condition( 'alpha' ) );
		$this->assertTrue( ppom_is_field_hidden_by_condition( 'beta', 'alpha,beta,beta' ) );
		$this->assertFalse( ppom_is_field_hidden_by_condition( 'gamma', 'alpha,beta' ) );

		$_POST['ppom']['conditionally_hidden'] = 'posted_one,posted_two';

		$this->assertTrue( ppom_is_field_hidden_by_condition( 'posted_two', 'alpha,beta' ) );
		$this->assertFalse( ppom_is_field_hidden_by_condition( 'beta', 'alpha,beta' ) );
	}

	/**
	 * Ensure cart matrix max quantity reads the upper bound from the last range.
	 *
	 * @return void
	 */
	public function testGetCartItemMaxQuantityReturnsUpperBoundOfLastRange() {
		$max_quantity = ppom_get_cart_item_max_quantity(
			array(
				'ppom' => array(
					'ppom_pricematrix' => wp_json_encode(
						array(
							array(
								'raw' => '1-2',
							),
							array(
								'raw' => '3-5',
							),
						)
					),
				),
			)
		);

		$this->assertSame( 5, (int) $max_quantity );
	}

	/**
	 * Ensure quantity stock handling subtracts the requested amount from matched options.
	 *
	 * @return void
	 */
	public function testFieldHasStockForQuantitiesSubtractsRequestedAmount() {
		$has_stock = ppom_field_has_stock(
			array(
				'type'         => 'quantities',
				'manage_stock' => 'on',
				'options'      => array(
					array(
						'option' => 'Large',
						'stock'  => 5,
					),
					array(
						'option' => 'Medium',
						'stock'  => 4,
					),
				),
			),
			array(
				'Large'  => 2,
				'Medium' => 0,
			)
		);

		$this->assertCount( 1, $has_stock );
		$this->assertSame( 'Large', $has_stock[0]['option'] );
		$this->assertSame( 3, (int) $has_stock[0]['stock'] );
	}

	/**
	 * When a price matrix is attached to the product, quantities option prices
	 * are blanked so the matrix drives pricing from the total quantity.
	 *
	 * @return void
	 */
	public function testQuantitiesOptionPricesBlankedWhenPriceMatrixAttached() {
		$product = $this->create_simple_product();

		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_quantities_field(
					'kits',
					'Kits',
					array(
						array(
							'option' => 'Kit A',
							'price'  => '999',
							'id'     => '_kit_a',
						),
					)
				),
				array(
					'type'      => 'pricematrix',
					'title'     => 'Sets',
					'data_name' => 'sets',
					'options'   => array(
						array(
							'option' => '1-10',
							'price'  => '80',
							'id'     => 'r1',
						),
					),
				),
			),
			$product->get_id()
		);

		$field_meta = ppom_get_field_meta_by_dataname( null, 'kits', $meta_id );
		$options    = ppom_convert_options_to_key_val( $field_meta['options'], $field_meta, $product );

		$this->assertSame( '', $options['Kit A']['price'] );
		$this->assertSame( '', $options['Kit A']['raw_price'] );
	}

	/**
	 * Without a price matrix, quantities option prices are kept as-is.
	 *
	 * @return void
	 */
	public function testQuantitiesOptionPricesKeptWithoutPriceMatrix() {
		$product = $this->create_simple_product();

		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_quantities_field(
					'kits',
					'Kits',
					array(
						array(
							'option' => 'Kit A',
							'price'  => '999',
							'id'     => '_kit_a',
						),
					)
				),
			),
			$product->get_id()
		);

		$field_meta = ppom_get_field_meta_by_dataname( null, 'kits', $meta_id );
		$options    = ppom_convert_options_to_key_val( $field_meta['options'], $field_meta, $product );

		$this->assertSame( 999.0, (float) $options['Kit A']['price'] );
	}

	/**
	 * Build cart meta for one field the way the cart stores it: raw, slashed `$_POST` values.
	 *
	 * @param string                                       $type  Field type.
	 * @param array<int|string, string|array<string, string>> $value Unslashed posted field value.
	 * @return array<string, mixed> Meta row for the field.
	 */
	private function make_meta_for_posted_field( string $type, array $value ): array {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				array(
					'type'      => $type,
					'title'     => 'Shopper Field',
					'data_name' => 'shopper_field',
				),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'id'            => (string) $meta_id,
						'shopper_field' => wp_slash( $value ),
					),
				),
			),
			'order'
		);

		$this->assertArrayHasKey( 'shopper_field', $meta );

		return $meta['shopper_field'];
	}

	/**
	 * Markup written as JSON unicode escapes, which survives tag stripping until decoded.
	 *
	 * @return string
	 */
	private function unicode_escaped_markup(): string {
		$bs = chr( 92 );

		return $bs . 'u003cb' . $bs . 'u003ex' . $bs . 'u003c/b' . $bs . 'u003e';
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function quantity_field_types(): array {
		return array(
			'quantities' => array( 'quantities' ),
			'qtypack'    => array( 'qtypack' ),
			'vm'         => array( 'vm' ),
		);
	}

	/**
	 * @dataProvider quantity_field_types
	 *
	 * @param string $type Quantity-style field type.
	 * @return void
	 */
	public function test_make_meta_data_escapes_posted_quantity_labels( string $type ): void {
		$meta = $this->make_meta_for_posted_field(
			$type,
			array(
				'<b>x</b>' => '2',
				'S & M'    => '1',
			)
		);

		$this->assertStringNotContainsString( '<b>x', $meta['value'] );
		$this->assertStringContainsString( '&lt;b&gt;x&lt;/b&gt; = 2', $meta['value'] );
		$this->assertStringContainsString( 'S &amp; M = 1', $meta['value'] );
		$this->assertStringContainsString( '<strong>', $meta['value'] );
	}

	/**
	 * @return void
	 */
	public function test_make_meta_data_escapes_posted_event_ticket_labels(): void {
		$meta = $this->make_meta_for_posted_field(
			'eventcalendar',
			array( '2026-01-15' => array( '<b>x</b>' => '1' ) )
		);

		$this->assertStringNotContainsString( '<b>x', $meta['display'] );
		$this->assertStringContainsString( '&lt;b&gt;x&lt;/b&gt; = 1', $meta['display'] );
	}

	/**
	 * @return void
	 */
	public function test_make_meta_data_escapes_decoded_audio_title(): void {
		$meta = $this->make_meta_for_posted_field(
			'audio',
			array( '7' => '{"link":"https://example.com/a.mp3","title":"' . $this->unicode_escaped_markup() . '"}' )
		);

		$this->assertStringNotContainsString( '<b>x', $meta['value'] );
		$this->assertStringContainsString( '>&lt;b&gt;x&lt;/b&gt;</a>', $meta['value'] );
		$this->assertStringContainsString( 'href="https://example.com/a.mp3"', $meta['value'] );
	}

	/**
	 * @return void
	 */
	public function test_make_meta_data_escapes_decoded_fancycropper_label(): void {
		$meta = $this->make_meta_for_posted_field(
			'fancycropper',
			array(
				'popup1' => '{"imageURL":"a.png","fileName":"a.png","cropped":[{"croppedURL":"c.png","qty":"1","label":"' . $this->unicode_escaped_markup() . '"}]}',
			)
		);

		$this->assertStringNotContainsString( '<b>x', $meta['value'] );
		$this->assertStringContainsString( '<td>&lt;b&gt;x&lt;/b&gt; x 1</td>', $meta['value'] );
	}

	/**
	 * @return void
	 */
	public function test_get_image_name_escapes_decoded_image_label(): void {
		$images = array(
			wp_slash( '{"link":"https://example.com/a.png","raw":"' . $this->unicode_escaped_markup() . '"}' ),
			wp_slash( '{"link":"https://example.com/b.png","raw":"Blue & Red"}' ),
		);

		$this->assertSame( '&lt;b&gt;x&lt;/b&gt;, Blue &amp; Red', ppom_get_image_name( $images ) );
	}

	/**
	 * JSON-escape every percent sign so it survives sanitization and appears only after json_decode().
	 *
	 * @param string $text Text holding percent-encoded octets.
	 * @return string
	 */
	private function json_escaped_percents( string $text ): string {
		return str_replace( '%', chr( 92 ) . 'u0025', $text );
	}

	/**
	 * @return void
	 */
	public function test_order_item_meta_keeps_percent_encoded_shopper_values_inert_after_woocommerce_decodes_them(): void {
		$product = $this->create_simple_product();
		$types   = array( 'quantities', 'qtypack', 'vm', 'eventcalendar', 'audio', 'fancycropper', 'image' );
		$fields  = array();
		foreach ( $types as $type ) {
			$fields[] = array(
				'type'      => $type,
				'title'     => 'Field ' . $type,
				'data_name' => $type . '_field',
			);
		}
		$meta_id = $this->insert_ppom_meta( $fields, $product->get_id() );

		$breakout = $this->json_escaped_percents( '%22%3E%3Cb%3Eu%3C/b%3E' );
		$label    = $this->json_escaped_percents( '%3Cb%3Ex%3C/b%3E' );
		$posted   = array(
			'id'                 => (string) $meta_id,
			'quantities_field'   => array( '%3Cb%3Eq%3C/b%3E' => '2' ),
			'qtypack_field'      => array( '%3Cb%3Ep%3C/b%3E' => '2' ),
			'vm_field'           => array( '%3Cb%3Ev%3C/b%3E' => '2' ),
			'eventcalendar_field' => array( '2026-01-15' => array( '%3Cb%3Ee%3C/b%3E' => '1' ) ),
			'audio_field'        => array( '7' => '{"link":"https://example.com/a.mp3' . $breakout . '","title":"' . $label . '"}' ),
			'fancycropper_field' => array(
				'popup1' => '{"imageURL":"a.png' . $breakout . '","fileName":"a.png","cropped":[{"croppedURL":"c.png' . $breakout . '","qty":"1","label":"' . $label . '"}]}',
			),
			'image_field'        => array( '3' => '{"link":"https://example.com/i.png' . $breakout . '","raw":"' . $label . '"}' ),
		);

		$order = $this->create_order_with_product( $product );
		$items = $order->get_items();
		$item  = reset( $items );
		\PPOM\WooCommerce\Order\OrderHandler::order_item_meta(
			$item,
			'cart-key',
			array(
				'data' => $product,
				'ppom' => array( 'fields' => wp_slash( $posted ) ),
			),
			$order
		);
		$item->save();

		$displayed = array();
		foreach ( \WC_Order_Factory::get_order_item( $item->get_id() )->get_all_formatted_meta_data() as $meta ) {
			$displayed[ $meta->key ] = $meta->display_value;
		}

		foreach ( $types as $type ) {
			$this->assertArrayHasKey( $type . '_field', $displayed, $type );
			$this->assertStringNotContainsString( '<b>', $displayed[ $type . '_field' ], $type );
		}
		$this->assertStringContainsString(
			'%3Cb%3Eq%3C/b%3E = 2',
			html_entity_decode( wp_strip_all_tags( $displayed['quantities_field'] ), ENT_QUOTES, 'UTF-8' )
		);
	}
}
