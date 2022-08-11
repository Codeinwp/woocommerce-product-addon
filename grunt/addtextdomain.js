/* jshint node:true */
// https://github.com/blazersix/grunt-wp-i18n
module.exports = {
    plugin: {
        options: {
            updateDomains: true,
            textdomain: '<%= package.plugin.textdomain %>'
        },
        files: {
            src: [
                '<%= files.php %>'
            ]
        }
    },
    composer: {
        options: {
            textdomain: '<%= package.plugin.textdomain %>',
            updateDomains: ['textdomain']
        },
        files: {
            src: [
                'vendor/codeinwp/**/*.php'
            ]
        }
    }
}
