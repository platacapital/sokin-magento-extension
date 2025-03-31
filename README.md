SokinPay Payment Gateway provides a seamless and secure payment processing solution integrated with your Magento store

## Step-by-Step Installation(Manually)

### Step 1: Download the Module File
1. Connect to your Magento server using SSH or FTP.
2. Navigate to the root directory of your Magento installation.
3. Upload the module package to the `app/code/` directory. If the directories do not exist, create them.
4. If you are on a local server, navigate to the `app/code/` directory and then extract the file.

### Step 2: Extract the Files
1. If the module package is a compressed file (e.g., `.zip` or `.tar.gz`), extract its contents into the `app/code` directory.

### Step 3: Enable the Module
Run the following command to enable the module:
```bash
php bin/magento module:enable SokinPay_PaymentGateway

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush


- In the Magento Admin panel, navigate to Stores > Configuration > Sales > Payment Methods.
- Find the SokinPay Pay section and configure the settings as needed:
- Enable/Disable: Enable the payment gateway.
- Choose the Environment: Select the environment (e.g., Production, Staging).
- Enter the X API Key: Found on the SokinPay payment Dashboard.
- Enter the API URL: URL for the API endpoint.
- Enter the Checkout URL: URL for the checkout process.
- Select Allowed Countries: Specify the countries where the payment method is available
