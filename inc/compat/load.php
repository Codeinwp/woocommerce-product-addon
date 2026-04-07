<?php
/**
 * Loads procedural compatibility wrappers for `ppom_*` and related globals.
 *
 * Order matches historical bootstrap dependencies.
 *
 * @package PPOM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ppom_inc = dirname( __DIR__ );

require_once __DIR__ . '/functions-embedded.php';
require_once $ppom_inc . '/functions.php';
require_once $ppom_inc . '/validation.php';
require_once $ppom_inc . '/deprecated.php';
require_once $ppom_inc . '/arrays.php';
require_once $ppom_inc . '/hooks.php';
require_once $ppom_inc . '/woocommerce.php';
require_once $ppom_inc . '/admin.php';
require_once $ppom_inc . '/files.php';
require_once $ppom_inc . '/nmInput.class.php';
require_once $ppom_inc . '/prices.php';
