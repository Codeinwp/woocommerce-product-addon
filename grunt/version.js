/* jshint node:true */
// https://github.com/kswedberg/grunt-version
module.exports = {
    project: {
        src: ['package.json'],
    },
    load_php: {
        options: {
            prefix: "PPOM_VERSION', '",
        },
        src: ['woocommerce-product-addon.php'],
    },
    readmetxt: {
        options: {
            prefix: 'Stable tag\\:.*\\s',
        },
        src: ['readme.txt'],
    },
    entryHeader: {
        options: {
            prefix: 'Version\\:.*\\s',
        },
        src: ['woocommerce-product-addon.php'],
    },
};