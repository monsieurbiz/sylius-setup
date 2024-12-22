# Sylius Setup

## Howto

You will need : 
- [Castor](https://github.com/jolicode/castor#readme) as a task runner
- [symfony](https://github.com/symfony-cli/symfony-cli#readme) as a PHP wrapper.
- [node](https://github.com/nodejs/node#readme) at least at version 20.
- [jq](https://jqlang.github.io/jq/download/) as a command-line JSON processor.
- [gh](https://cli.github.com/) as a Github tool.

Then you can : 

- Use this template project as a new project in GitHub.
- Clone your project and run `castor local:setup` inside it.
- Clean the castor files if you don't want them in your project with `castor local:clean-up`.
- Remove the `composer.lock` line in `apps/sylius/.gitignore` if you want to commit it.
- And code!

## Setup GitHub Repository

Simply run: `castor github:project:init`

This command will configure the repository:

- Add autolink
- Change default branch and create protections
- Allow auto merge and automatically delete branch
- Add team permissions

See help, with `-h`, to display all command options.

## Setup Clever Cloud environment

Simply run: `castor clevercloud:setup` and follow the instructions.

Add a password for the HTTP auth using the `htpasswd` utility into `clevercloud/.htpasswd` file.

## Setup GitHub Environment

You'll need your credentials for Clever Cloud : `clever login` will help you get the required token and secret.

Simply run: `castor github:env:setup` and follow the instructions.

## Install Sylius plugins

After installing Sylius, you can install the plugins you need: `castor sylius:plugins:install`.  
Use the `--plugins` option to specify the plugins you want to install: `castor sylius:plugins:install --plugins=monsieurbiz/sylius-homepage-plugin --plugins=monsieurbiz/sylius-cms-page-plugin`.

You can find the list of all plugins available using `castor sylius:plugins:list`.

### Themes

You can find some themes in [themes-examples/](themes-examples/themes/) and follow their README.

## Questions & Troubleshooting

### Which version of Sylius am I installing?

By default, the `composer create-project` checks the platform you are working on.  
We use `symfony` as a wrapper for PHP, this way you can change your PHP version in the `.php-version` file.  
According to this composer will install the best version compatible with your computer.

So don't forget to change the `.php-version` to the latest, then you'll probably get the latest Sylius version as well.

Our installation process allows you to forget about this by asking all the required questions.

## License

Please see LICENSE.txt.
