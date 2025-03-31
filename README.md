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
   ```bash
   # Clone the repository
   cd app/code/
   git clone https://github.com/sokinpay/magento-payment-gateway.git SokinPay/PaymentGateway

   # Enable the module
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
    ├── Observer/               # Event Observers
    ├── etc/                    # Configuration
    ├── view/                   # Frontend Assets
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

### Testing
1. Unit Tests:
   ```bash
   vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/SokinPay/PaymentGateway/Test/Unit
   ```

2. Integration Tests:
   ```bash
   vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/SokinPay/PaymentGateway/Test/Integration
   ```

3. API Testing:
   - Use the sandbox environment credentials
   - Test cards available in [Sokin API Documentation](https://api-docs.sokin.com)

### Debugging
- Enable debug logging in `app/etc/env.php`
- Check `var/log/sokinpay.log` for payment-specific logs
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

Key configuration options:
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