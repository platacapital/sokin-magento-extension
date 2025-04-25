# Sokin Pay for Magento

A Magento extension that integrates the Sokin Pay payment gateway with Magento 2 stores.

## Overview

This extension implements the Sokin Pay payment processing API for Magento 2, supporting:
- Credit card payments
- Pay by bank transfers
- Webhook handling for payment status updates
- Sandbox and Production environments
- Custom payment form rendering
- Order status management

## Technical Requirements

- Magento 2.4+
- PHP 7.4+
- MySQL 5.7+
- Composer 2.0+
- SSL certificate (required for production)

## Development Setup

1. Via Composer (recommended):
   ```bash
   composer require sokinpay/magento-payment-gateway
   ```

2. Manual Installation:
    1. Connect to your Magento server using SSH or FTP.
    2. Navigate to the root directory of your Magento installation.
        ##### 1. Via Clone the repository
        ```bash
        cd app/code/

        git clone https://github.com/sokinpay/magento-payment-gateway.git SokinPay/PaymentGateway
        ```
        ##### OR
       ##### 2. Via Zip File Download
        1. Upload the module package to the `app/code/` directory. If the directories do not exist, create them.
        2. If you are on a local server, navigate to the `app/code/` directory and then extract the zip file `(Sokin.zip)`.

       ##### Extract the Files
       1. If the module package is a compressed file (e.g., `.zip` or `.tar.gz`), extract its contents into the `app/code` directory.

   ##### Enable the Module And Run Commands
   ``` bash
   php bin/magento module:enable SokinPay_PaymentGateway
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   php bin/magento cache:flush
   ```

## Architecture

The extension follows Magento 2's module architecture:
```
SokinPay/
└── PaymentGateway/
    ├── Api/                     # API Interfaces
    ├── Block/                   # View Models
    ├── Controller/              # Admin and Frontend Controllers
    ├── Gateway/                 # Payment Gateway Implementation
    ├── Model/                   # Business Logic
    ├── Observer/                # Event Observers
    ├── Plugin/                  # Plugin
    ├── Service/                 # API Service
    ├── etc/                     # Configuration
    ├── view/                    # Frontend Assets
    └── composer.json
```

## Development

### Local Development
1. Set up a local Magento 2 development environment
2. Configure your IDE for Magento 2 development
3. Enable developer mode:
   ```bash
   php bin/magento deploy:mode:set developer
   ```

### Debugging
- Enable debug logging in `app/etc/env.php`
- Check `var/log/sokinpay-paymentgateway.log` for payment-specific logs
- Use Magento's debug mode for detailed error messages

## API Integration

### Webhook Handling
- Endpoint: `{base_url}/sokinpay/payment/webhook`
- Supports asynchronous payment status updates
- Implements idempotency for reliable processing

### Payment Flow
1. Payment initialization
2. Redirect to Sokin hosted checkout
3. Payment processing
4. Webhook notification
5. Order status update

## Admin Configuration

Configuration path: `Stores > Configuration > Sales > Payment Methods > Sokin Pay`

### Setup Configuration
   - In the Magento Admin panel, navigate to Stores > Configuration > Sales > Payment Methods.
   - Find the SokinPay Pay section and configure the settings as needed:
   - Enable/Disable: Enable the payment gateway.
   - Choose the Environment: Select the environment (e.g., Production, Staging).
   - Enter the X API Key: Found on the SokinPay Payment Dashboard.
   - Enter the API URL: URL for the API endpoint.
   - Enter the Checkout URL: URL for the checkout process.
   - Select Allowed Countries: Specify the countries where the payment method is available

##### Key configuration options:
```yaml
payment:
  sokinpay:
    active: true
    title: "Sokin Pay"
    environment: "sandbox" # or "production"
    api_key: "YOUR_API_KEY"
    webhook_secret: "YOUR_WEBHOOK_SECRET"
    debug: true # Enable for development
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow [Magento coding standards](https://developer.adobe.com/commerce/php/coding-standards/)
4. Submit a pull request

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

## Documentation

- [Extension Documentation](docs/)
- [Sokin API Documentation](https://api-docs.sokin.com)
- [Technical Support](mailto:support@sokin.com)

## License

This project is licensed under the [MIT License](LICENSE).

While not required by the license, we appreciate contributions back to the project. See our [CONTRIBUTING.md](CONTRIBUTING.md) guidelines for details on how to help improve this extension.
