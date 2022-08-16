
## Easy Testing

Running Cypress tests is easy!
Just install it by using the built-in makefile commands
and either open your tests in the Cypress UI, or run them directly from your CLI.

### Test environment & DB
Follow these steps to set up the test environment ff you run E2E for the first time:
```ruby
./psh.phar init-database --DB_NAME=shopware_e2e --APP_ENV=e2e
bin/console system:install --basic-setup --force
bin/console plugin:install --activate CheckoutCom -c
bin/console e2e:dump-db
```

And if you are done with testing, to switch back to dev DB for development, go to your `.env` file and change this:
```ruby 
DATABASE_URL=mysql://app:app@localhost:3306/shopware
```

### Installation

This folder contains a `makefile` with all required commands.
Run the installation command to install Cypress and all its dependencies on your machine.

```ruby 
make install
```
After installing, you need to set all the necessary environment variables for Cypress to work. We have provided `cypress.example.json` to define all the environment variables our plugin needs in order to run.

Create `cypress.json` inside `checkoutcomshopware/tests/Cypress` directory.

Set your own `secretKey` and `publicKey` and you are good to go!

### Cypress UI
If you want to run your Cypress UI, just open it with the following command.
Please note, because this is an Open Source project, we cannot include a
shop URL in the configuration. Thus you need to provide it on your own.
The tests might differ between Shopware versions, though the baseline is always the same.
So there is an additional parameter to tell Cypress what Shopware version should be tested.
This parameter is optional and its default is always the latest supported Shopware version.

```ruby 
make open-ui url=https://my-local-or-remote-domain

make open-ui url=https://my-local-or-remote-domain shopware=6.4
```

### Run in CLI
You can also use the CLI command to run Cypress on your machine or directly in your build pipeline.
Cypress will then test your local or remote shop with the tests of the provided Shopware version.

```ruby 
make run url=https://my-local-or-remote-domain shopware=6.x
```
