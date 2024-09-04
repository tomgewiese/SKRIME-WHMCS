# SKRIME Domain WHMCS Module

## Overview

This module allows the integration of SKRIME's domain registration service into WHMCS. With this module, you can register, renew, and manage domains through SKRIME directly from your WHMCS dashboard.

## System Requirements

- WHMCS version 7.0 or higher
- PHP version 7.2 or higher

## Installation

1. **Download:** Download the `modules.zip` file that contains the SKRIME WHMCS module.

2. **Unzip:** Unzip the `modules.zip` file in the root directory of your WHMCS installation. This will copy the necessary files into the appropriate directories.

    - The contents of the `modules.zip` should be copied into the following directory:
        - `/modules/registrars/skrime/`

3. **Activate the Module:**

    - Log in to your WHMCS admin dashboard.
    - Navigate to `System Settings` > `Domain Registrars`.
    - Find `SKRIME` and click on `Activate`.

4. **Configuration:**

    - After the module is activated, click on `Configure`.
    - Enter your SKRIME API key.
    - Save the configuration.

## Usage

After configuration, you can use SKRIME as a domain registrar within your WHMCS products and services. The module supports the following features:

- **Domain Registration:** Register new domains through SKRIME.
- **Domain Renewal:** Renew existing domains.
- **Domain Management:** Manage domain DNS, WHOIS data, and more.

## Support

If you need assistance with installing or using the module, please contact SKRIME support through our ticket system:

- Support ticket: https://skrime.eu/support/overview

Our support team will be happy to assist you with any questions or issues you may encounter.

## License

This module is released under the MIT License. See the LICENSE file for more details.
