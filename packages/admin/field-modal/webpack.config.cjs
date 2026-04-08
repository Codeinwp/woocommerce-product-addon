/**
 * Transpile Chakra UI and related packages (modern ESM in node_modules).
 *
 * @param {object} rule Webpack rule from @wordpress/scripts.
 * @return {object}
 */
function allowChakraInBabel( rule ) {
	const testStr = rule.test ? rule.test.toString() : '';
	if ( ! rule.exclude || ! testStr.includes( 'jsx?' ) ) {
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
	module: {
		...defaultConfig.module,
		rules: defaultConfig.module.rules.map( allowChakraInBabel ),
	},
};
