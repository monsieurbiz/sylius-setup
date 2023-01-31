# Sylius Setup

## Howto

- Use this template project as a new project in Github.
- Edit the `.gitignore` file to remove unwanted lines.
- Edit the `.php-version` as needed.
- Run `make up` to make sure you have a database available before installing Sylius.
- Run `symfony composer create-project sylius/sylius-standard apps/sylius`.
- Run `make down install` to get a clean setup.
- Replace `MAILER_DSN=null://null` by `MAILER_DSN=smtp://localhost:1025` in `apps/sylius/.env`.
- And code!

## License

Please see LICENSE.txt.
