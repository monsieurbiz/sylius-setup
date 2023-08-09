/** @type {import('tailwindcss').Config} */
const path = require('path');

module.exports = {
  content: [
    path.resolve(__dirname, '../../vendor/monsieurbiz/sylius-tailwind-theme/assets/**/*.js'),
    path.resolve(__dirname, '../../vendor/monsieurbiz/sylius-tailwind-theme/templates/**/*.html.twig'),
    path.resolve(__dirname, './assets/**/*.js'),
    path.resolve(__dirname, './templates/**/*.html.twig')
  ],
  theme: {
    extend: {
      margin: {
        auto: 'auto',
      },
    },
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: [
      {
        custom: {
          primary: '#ff0000',
          secondary: '#007eff',
          accent: '#18ff00',
          neutral: '#0f0f11',
          'base-100': '#ffffff',
        },
      },
    ],
  },
};
