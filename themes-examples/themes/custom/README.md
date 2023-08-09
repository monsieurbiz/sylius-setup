# Custom Theme using the Tailwind theme as parent

To install this theme you need to perform the following steps:

1. Install the Tailwind theme with the instructions from the [Tailwind Theme](https://github.com/monsieurbiz/sylius-tailwind-theme#readme);
2. Update your `webpack.config.js`:
    ```diff
      // Themes config
      const syliusTailwindThemeConfig = require('./vendor/monsieurbiz/sylius-tailwind-theme/webpack.config');
    + const customThemeConfig = require('./themes/custom/webpack.config');
      
    - module.exports = [shopConfig, adminConfig, appShopConfig, appAdminConfig, syliusTailwindThemeConfig];
    + module.exports = [shopConfig, adminConfig, appShopConfig, appAdminConfig, syliusTailwindThemeConfig, customThemeConfig];
    ```
3. Copy the [themes.yaml](../../config/packages/themes.yaml) file or at least its content into your `config/packages/` folder.
