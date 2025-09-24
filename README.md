# Ideal Postcodes Address Validation for Magento 2

This repository contains a Magento 2.4.8-p2 module that validates customer and checkout addresses using the [Ideal Postcodes Autocomplete API](https://api.ideal-postcodes.co.uk/v1/autocomplete/addresses).

## Features

- Validates customer address book entries when they are saved through the Magento API or storefront.
- Verifies shipping and billing addresses submitted during checkout.
- Allows merchants to configure the API key, enable/disable validation, restrict validation to specific countries, and control the minimum accepted match score.

## Installation

1. Copy the module to `app/code/Idealpostcodes/AddressValidation` within your Magento installation (this repository already uses that structure).
2. Run Magento setup upgrades:

   ```bash
   bin/magento module:enable Idealpostcodes_AddressValidation
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```

## Configuration

1. In the Magento Admin Panel, navigate to **Stores → Configuration → Services → Ideal Postcodes → Address Validation**.
2. Enable the module and enter your Ideal Postcodes API key.
3. Optionally restrict validation to specific countries and/or adjust the minimum accepted match score.

## How It Works

- On address save and during checkout, the module builds a search query from the provided address fields and sends it to the Ideal Postcodes Autocomplete API.
- If the API returns no valid matches, the module throws a validation error and prompts the shopper to review their address.
- When the API is unavailable or responds with an error, the module surfaces a generic error message while logging the technical details for debugging.

## Notes

- The module gracefully skips validation if it is disabled or if the API key has not been configured.
- By default, validation runs for UK addresses (`GB`). You can update the configuration to cover more countries or leave the field blank to validate globally.
- API timeouts are kept intentionally low (5 seconds) to avoid slowing down checkout experiences.
