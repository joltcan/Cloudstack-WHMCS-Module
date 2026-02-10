# CloudStack WHMCS Provisioning Module

A provisioning module for WHMCS that integrates with Apache CloudStack. It allows hosting providers to automate the provisioning and management of CloudStack virtual machines and accounts directly from WHMCS.

## Features

-   **Automated Account & VM Provisioning:**
    -   Create, Suspend, Unsuspend, and Terminate CloudStack accounts.
-   **VM Management:**
    -   Client Area controls for Reboot, Start, and Stop virtual machines.
    -   Admin Area controls for the same power functions.
    -   Reset VM password from the WHMCS Admin Area.
-   **Simplified Product Configuration:**
    -   **Automatic Configurable Options:** A one-click "Create Config Option" button on the product module settings page that automatically fetches and creates configurable options for:
        -   Zones
        -   Service Offerings
        -   Disk Offerings
        -   Templates
    -   **Automatic Custom Fields:** Automatically creates necessary custom fields on the product for storing CloudStack-related IDs.

## Requirements

-   WHMCS 8.x or later
-   PHP 8.1 or later (compatible up to 8.4)
-   Apache CloudStack 4.18 or later (developed with 4.21.0 in mind)
-   A `CloudStackClient.php` library (this module assumes one is present).

## Installation

1.  Download the repository files.
2.  Create a directory named `cloudstack` inside your WHMCS `/modules/servers/` directory.
3.  Upload `cloudstack.php` to `/modules/servers/cloudstack/`.
4.  The module expects a `CloudStackClient.php` class to be located at `/modules/servers/cloudstack/class/CloudStackClient.php`. Make sure your client library is placed there.
5.  Upload `hooks.php` to `/includes/hooks/cloudstack_admin_product_hook.php`. Giving it a unique name is good practice.

## Configuration

### 1. Set up the Server in WHMCS

-   Log in to your WHMCS Admin Area.
-   Navigate to **Setup > Products/Services > Servers**.
-   Click **Add New Server**.
-   Give the server a name (e.g., "My CloudStack").
-   Under "Module", select **Cloudstack**.
-   Enter your CloudStack **API Key**, **Secret Key**, and **End Point** URL.
-   Save Changes.

### 2. Set up the Product in WHMCS

-   Navigate to **Setup > Products/Services > Products/Services**.
-   Create or edit a product.
-   Go to the **Module Settings** tab.
-   Select **Cloudstack** as the module and choose the server you just configured.
-   You will see the module options, including a link: **"Create Config Option"**.

### 3. Generate Configurable Options

-   Click the **"Create Config Option"** link on the product's Module Settings page.
-   This will automatically connect to your CloudStack API and create a new Configurable Option Group for this product.
-   The group will be populated with dropdowns for Zones, Service Offerings, Disk Offerings, and Templates based on what's available in your CloudStack environment.
-   You can review and set pricing for these options under **Setup > Products/Services > Configurable Options**.

## Usage

-   Once configured, clients can order the product and select the desired Zone, Template, etc., from the configurable options during checkout.
-   Upon provisioning, WHMCS will create the account and VM in CloudStack.
-   Clients can manage their VM's power state from the product details page in the client area.
-   Admins can manage the service from the product details page in the admin area.

## License

This project is currently unlicensed.