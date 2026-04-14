/**
 * Transpile Chakra UI and related packages (modern ESM in node_modules).
 *
 * @param {object} rule Webpack rule from @wordpress/scripts.
 * @return {object}
 */
function allowChakraInBabel( rule ) {
	const testStr = rule.test ? rule.test.toString() : '';
	/** Matches wp-scripts `/\.m?(j|t)sx?$/` and legacy `jsx?` babel app-code rules. */
	const isAppJsTsRule =
		testStr.includes( 'jsx?' ) || testStr.includes( '(j|t)sx' );
	if ( ! rule.exclude || ! isAppJsTsRule ) {
		return rule;
	}
	return {
		...rule,
		exclude: ( filepath ) => {
			if ( /node_modules/.test( filepath ) ) {
				return ! /@chakra-ui|@emotion|framer-motion|color2k|react-fast-compare|react-focus-lock|react-remove-scroll|tslib|stylis|use-callback-ref|use-sidecar/.test(
					filepath
				);
			}
			return false;
		},
	};
}

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		/** Keep stable `index.js` / `index.asset.php` for PHP enqueue (entry is `index.tsx`). */
		filename: 'index.js',
	},
	module: {
		...defaultConfig.module,
		rules: defaultConfig.module.rules.map( allowChakraInBabel ),
	},
};
