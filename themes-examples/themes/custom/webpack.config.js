const path = require('path');
const Encore = require('@symfony/webpack-encore');

const syliusBundles = path.resolve(__dirname, '../../vendor/sylius/sylius/src/Sylius/Bundle/');
const uiBundleScripts = path.resolve(syliusBundles, 'UiBundle/Resources/private/js/');
const uiBundleResources = path.resolve(syliusBundles, 'UiBundle/Resources/private/');
const mainShopAssets = path.resolve(__dirname, '../../assets/shop/');
const themeAlias = {
  'sylius/ui': uiBundleScripts,
  'sylius/ui-resources': uiBundleResources,
  'sylius/bundle': syliusBundles,
  '@mainShopAssets': mainShopAssets,
};

Encore
  .setOutputPath('public/build/themes/custom')
  .setPublicPath('/build/themes/custom')

  .addEntry('custom-theme-entry', './themes/custom/assets/entry.js')

  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enablePostCssLoader((options) => {
    options.postcssOptions = {
      config: path.resolve(__dirname, 'postcss.config.js'),
    };
  })
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction());

const config = Encore.getWebpackConfig();
config.name = 'custom-theme';
config.resolve.alias = themeAlias;

Encore.reset();

module.exports = config;
