# Sylius Setup

## Howto

You will need : 
- [Castor](https://github.com/jolicode/castor#readme) as a task runner
- [symfony](https://github.com/symfony-cli/symfony-cli#readme) as a PHP wrapper.
- [node](https://github.com/nodejs/node#readme) at least at version 20.
- [jq](https://jqlang.github.io/jq/download/) as a command-line JSON processor.
- [gh](https://cli.github.com/) as a Github tool.

Then you can : 
- Use this [template project as a new project in GitHub](https://monsieurbiz.com/media/gallery/images/plugins/setup.png).
- Clone your project with `gh repo clone monsieurbiz/your-repo` 
- Run `castor local:setup -v` inside it.

Go further : 
- Setup your GitHub repository with `castor github:project:init -v`.
- Setup your Clever Cloud environment with `castor clevercloud:setup -v`.
- Setup your GitHub environment with `castor github:env:init -v`.
    - For `staging`, will set up branch (`develop`) and setup environment in Github, can run `castor github:variables:init` at the end.
    - For `production`, will set up branch (`master`) and setup environment in Github, can run `castor github:variables:init` at the end.
- Setup your Github variables with `castor github:variables:init -v`
    - For `staging`, will set `STAGING_BRANCH` and `STAGING_URL`.
    - For `production`, will set `PRODUCTION_BRANCH` and `PRODUCTION_URL`.
- Install Sylius plugins with `castor sylius:plugins:install -v`.

⚠️ Sylius plugins setup is not yet implemented for Sylius 2.x

When you have finished : 
- Clean the castor files if you don't want them in your project with `castor local:clean-up -v`.
- It will ask you if you want to commit the `composer.lock` file.
- And code!

## Local commands

### Local Setup

`castor local:setup`

Setup the application in your machine.  
Doctrine conflict is only asked for Sylius 1.x

```
 Which application name do you want? [sylius]:
 >
 Which PHP do you want? [8.3]:
 >
 Which Sylius version do you want? [2.0]:
 >
 Do you want to fix a conflict with doctrine? Highly recommended for Sylius 1.x ONLY! (yes/no) [no]:
 >
```

### Local Reset

`castor local:reset`

Remove the application, and git reset the sources reset your sources to the initial state of the setup.
Usefull while updating the setup.

```
  Are you sure? This is a destructive action! (yes/no) [no]:
 >
 ```

### Local Clean up 

`castor local:clean-up`

Remove everything about the setup after your ended the configuration of your project.

```
  Are you sure? This is a destructive action! (yes/no) [no]:
 >
 Do you want to commit your `composer.lock` file?
 >
```

## Github commands

### Setup GitHub Repository

`castor github:project:init`

This command will configure the repository:

- Add autolink
- Change default branch and create protections
- Allow auto merge and automatically delete branch
- Add team permissions

```
 Autolink prefix (example: MBIZ-) [MBIZ-]:
 >

 Autolink URL template (example: https://support.monsieurbiz.com/issues/<num>) [https://support.monsieurbiz.com/issues/<num>]:
 >
 ```

### Setup GitHub Environment

`github:env:init`

You'll need your credentials for Clever Cloud : `clever login` will help you get the required token and secret.

Setup env for Github, can also setup variables by running `castor github:variables:init` at the end.

```
 Which environment? [staging]:
  [0] staging
  [1] production
 > 0


 [INFO] Setting up env for staging environment…


 Deployment branch? [develop]:
 >

 CLEVER_TOKEN?:
 >

 CLEVER SECRET?:
 >

 Would you like to setup the repository variables? (yes/no) [no]:
 >
 ```


### Github variables init 

`castor github:variables:init`

Can be ran by `castor github:env:init`.

#### Staging

```
 Which kind of environment? [staging]:
 >

 STAGING_BRANCH? [develop]:
 >

 STAGING_URL? [https://project.staging.monsieurbiz.cloud]:
 >
```

#### Production

```
 Which environment? [staging]:
  [0] staging
  [1] production
 > 1

 PRODUCTION_BRANCH? [master]:
 >

 PRODUCTION_URL? [https://project.preprod.monsieurbiz.cloud]:
 >
```

## Clever Cloud commands

### Setup Clever Cloud environment

`castor clevercloud:setup`

Add a password for the HTTP auth using the `htpasswd` utility into `clevercloud/.htpasswd` file.
Create app, database and necessary buckets to run the application

```
 What is the name of your project?:
 > Test setup

 What is the organization ID from Clever cloud (its name or its code starting with org_)?:
 > orga_***

 Which environment? [staging]:
  [0] staging
  [1] production
 >

 Do you want to setup credentials for protected environment? (yes/no) [no]:
 > yes

 Username:
 > test

 Password:
 >
 ```

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
