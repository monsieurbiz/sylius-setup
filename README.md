# Sylius Setup

## Howto

You will need [Castor](https://github.com/jolicode/castor#readme) as a task runner, and [symfony](https://github.com/symfony-cli/symfony-cli#readme) as a PHP wrapper.

- Use this template project as a new project in Github.
- Clone your project and run `castor local:setup` inside it.
- And code!

## Setup Clever Cloud environment

Simply run: `castor clevercloud:setup` and follow the instructions.

## Questions & Troubleshooting

### Which version of Sylius am I installing?

By default the `composer create-project` checks the platform you are working on.  
We use `symfony` as a wrapper for PHP, this way you can change your PHP version in the `.php-version` file.  
According to this composer will install the best version compatible with your computer.

So don't forget to change the `.php-version` to the latest, then you'll probably get the latest Sylius version as well.

## License

Please see LICENSE.txt.
