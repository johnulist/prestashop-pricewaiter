PriceWaiter add-on for PrestaShop
=================================

## Functionality

The PriceWaiter module for PrestaShop makes it easy for PrestaShop store operators to integrate and run PriceWaiter on their eCommerce sites. It automatically places the PriceWaiter button on product pages, allows administrators to disable the button or conversion tools (e.g. Exit Intent) for specific categories or products, connects to the back office to retrieve cost and inventory data about products ordered through PriceWaiter, and writes orders placed through PriceWaiter back to the PrestaShop system.

## Installation

The PriceWaiter module performs the following actions upon installation:

- Registers a hook on the Product Buttons section of the product page, which displays a PriceWaiter negotiation button near the shop's default Add to Cart button.
- Enables the REST webservice (if not already enabled) for the PrestaShop instance.
- Creates a webservice access token for PriceWaiter to use and saves it in the Configuration table. This token is granted access to PrestaShop objects to allow it to read inventory and product cost data (wholesale, retail and sale prices) and to create orders (with all associated customer and fulfillment data).
- Sends shop information, including name, web address, contact information and the above mentioned webservice access token, to PriceWaiter to allow for quick and easy creation of a PriceWaiter account.

## Product page

The PriceWaiter button appears near the Add to Cart button on the product page. It harvests the following information and sends it to PriceWaiter when the customer makes an offer on the product:

- Product name and SKU (reference)
- Link to product image
- Selected options (e.g. size, color, etc.)
- Quantity requested
- Customer's offered price per item
- PrestaShop product, combination, and specific price ids
- PrestaShop version (for customer support purposes)

The PriceWaiter system processes this information as an offer, which is displayed to the retailer in the PriceWaiter back office system. The retailer can accept or reject the offer, or make a counter-offer which changes price, quantity, or terms. Once accepted or countered, the customer has the opportunity to check out on PriceWaiter's site, with payment processed directly by the retailer's selected payment processor. The order data is then written back to PrestaShop.

## Uninstall

Uninstalling the PriceWaiter module removes all created objects:

- The registered hook on the product page
- The webservice access token and its associated permissions
- All configurations stored in the PrestaShop database

Uninstalling does NOT turn off the PrestaShop webservice, since that is a global setting and other modules or services may be using it.
