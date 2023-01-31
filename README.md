# Sylius Setup

## Howto

- Use this template project as a new project in Github.
- Edit the `.gitignore` file to remove unwanted lines.
- Edit the `.php-version` as needed.
- Run `symfony composer create-project --no-scripts sylius/sylius-standard apps/sylius`.
- Run `make install` to get a clean setup.
- Replace `MAILER_DSN=null://null` by `MAILER_DSN=smtp://localhost:1025` in `apps/sylius/.env`.
- And code!

## License

Please see LICENSE.txt.
