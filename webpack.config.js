/**
 * Webpack Encore Configuration
 *
 * Build assets modernes pour PrestaShop
 * - SCSS compilation
 * - TypeScript support
 * - PostCSS + Autoprefixer
 * - Minification production
 */

const Encore = require('@symfony/webpack-encore');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // ⭐ CRITICAL - PrestaShop Addons Compliance: Output JS in views/js/
    .setOutputPath('views/js/')

    // public path used by the web server to access the output path
    .setPublicPath('/modules/wepresta_passwordless_login/views/js')

    // manifest.json for cache busting
    .setManifestKeyPrefix('wepresta_passwordless_login')

    /*
     * ENTRY CONFIG
     */
    .addEntry('front', './_dev/js/front.js')
    .addEntry('admin', './_dev/js/admin.js')

    // enables the Symfony UX Stimulus bridge (used in Symfony projects)
    // .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.36';
    })

    // ⭐ CRITICAL - CSS redirect to views/css/
    .enableSassLoader()
    .configureMiniCssExtractPlugin(
        (loaderOptions) => { },
        (pluginOptions) => {
            pluginOptions.filename = '../css/[name].css';
        }
    )

    // enables PostCSS support
    .enablePostCssLoader((config) => {
        config.postcssOptions = {
            plugins: [
                require('autoprefixer'),
            ],
        };
    })

    // uncomment to enable TypeScript
    // .enableTypeScriptLoader()

    // ⭐ CRITICAL - Copy images to views/img/ for PrestaShop Addons compliance
    .copyFiles({
        from: './_dev/images',
        to: '../img/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
    })

    // Add aliases
    .addAliases({
        '@': path.resolve(__dirname, '_dev/js'),
        '@scss': path.resolve(__dirname, '_dev/scss'),
    })
    // ⭐ CRITICAL - Keep license banner inside the JS files (for PrestaShop Addons compliance)
    .configureTerserPlugin((options) => {
        options.extractComments = false;
    })
    .addPlugin(new webpack.BannerPlugin({
        banner: `Module for PrestaShop

                xxxx
                xxxx

                @author      WePresta
                @copyright   2026 WePresta
                @license     MIT`,
        raw: false,
        entryOnly: true,
    }));

module.exports = Encore.getWebpackConfig();

