# Custom Theme using the Tailwind theme as parent

To install this theme you need to perform the following steps:

1. You may need our Theme Companion plugin: `symfony composer require monsieurbiz/sylius-theme-companion-plugin`
2. Require the tailwind theme: `symfony composer require monsieurbiz/sylius-tailwind-theme`
3. Follow the instructions from the [Tailwind Theme](https://github.com/monsieurbiz/sylius-tailwind-theme#readme);
4. Update your `webpack.config.js`:
    ```diff
      // Themes config
      const syliusTailwindThemeConfig = require('./vendor/monsieurbiz/sylius-tailwind-theme/webpack.config');
    + const customThemeConfig = require('./themes/custom/webpack.config');
      
    - module.exports = [shopConfig, adminConfig, appShopConfig, appAdminConfig, syliusTailwindThemeConfig];
    + module.exports = [shopConfig, adminConfig, appShopConfig, appAdminConfig, syliusTailwindThemeConfig, customThemeConfig];
    ```
5. Add the `../../config/packages/themes.yaml` (from here) or at least its content into your `config/` folder.
