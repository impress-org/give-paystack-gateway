# Give - Paystack Gateway

Accept donations through Paystack payment gateway with GiveWP.

## Description

The Give - Paystack Gateway plugin integrates the popular Paystack payment processor with GiveWP, allowing you to accept donations from supporters across Africa and beyond. Paystack provides a secure, reliable payment infrastructure that supports multiple payment methods including cards, bank transfers, and mobile money.

## Features

- **Secure Payments**: Accept donations through Paystack's secure payment infrastructure
- **Multiple Payment Methods**: Support for cards, bank transfers, and mobile money
- **Seamless Integration**: Works perfectly with GiveWP's donation forms
- **Modern Interface**: Built with React and TypeScript for a smooth user experience
- **Recurring Donations**: Support for recurring donation plans (if supported by your Paystack account)
- **Transaction Management**: View and manage donations directly from your WordPress dashboard

## Requirements

- WordPress 6.5 or higher
- PHP 7.4 or higher
- GiveWP 4.0.0 or higher
- Active Paystack account

## Installation

1. Upload the plugin files to the `/wp-content/plugins/give-paystack` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **Donations > Settings > Payment Gateways > Paystack** to configure the gateway
4. Enter your Paystack API keys (available from your Paystack dashboard)
5. Configure your gateway settings and save

## Configuration

### API Keys

You'll need to obtain your API keys from your Paystack dashboard:

1. Log in to your [Paystack Dashboard](https://dashboard.paystack.com/)
2. Navigate to **Settings > API Keys & Webhooks**
3. Copy your **Public Key** and **Secret Key**
4. In WordPress, go to **Donations > Settings > Payment Gateways > Paystack**
5. Enter your API keys in the respective fields

### Test Mode

For testing purposes, you can enable Test Mode in the gateway settings. This allows you to process test transactions without affecting real payments.

## Development

### Getting Set Up

1. Clone this repository locally
2. Run `composer install` from the CLI
3. Run `npm install` from the CLI

### Asset Compilation

To compile your CSS & JS assets, run one of the following:

- `npm run dev` — Compiles all assets for development one time
- `npm run watch` — Compiles all assets for development and watches for changes
- `npm run build` — Compiles all assets for production

### Testing

Run the test suite:

```bash
composer test
```

## Support

For support with this gateway, please:

1. Check the [GiveWP Documentation](https://givewp.com/documentation/)
2. Visit the [GiveWP Support Forum](https://wordpress.org/support/plugin/give/)
3. Contact [GiveWP Support](https://givewp.com/support/) if you have an active license

## Contributing

We welcome contributions to improve this gateway. Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This plugin is licensed under the GPLv3 or later. For more information, see the [LICENSE](LICENSE) file.

## Changelog

For a detailed list of changes, please see the [CHANGELOG.md](CHANGELOG.md) file.
