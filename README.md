# Sylius Setup

## Howto

- Use this template project as a new project in Github.
- Edit the `.gitignore` file to remove unwanted lines.
- Edit the `.php-version` as needed.
- Run `symfony composer create-project --no-scripts sylius/sylius-standard apps/sylius`.
- Run `make install` to get a clean setup.
- Replace `MAILER_DSN=null://null` by `MAILER_DSN=smtp://localhost:1025` in `apps/sylius/.env`.
- And code!

## Questions & Troubleshooting

### Which version of Sylius am I installing?

By default the `composer create-project` checks the platform you are working on.  
We use `symfony` as a wrapper for PHP, this way you can change your PHP version in the `.php-version` file.  
According to this composer will install the best version compatible with your computer.

So don't forget to change the `.php-version` to the latest, then you'll probably get the latest Sylius version as well.

## License

Please see LICENSE.txt.
