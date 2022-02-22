# Marketplacer Magento Extension (module Marketplacer/Base)

## General information

The extension connects Magento to Marketplacer and provides the ability for the Merchant (from the Magento side) to
sell on their website products of other Sellers managed in the Marketplacer platform.
The extension creates Sellers and Brands entities, related frontend components and APIs in Magento to support this
ability.


## Extension contents

The extension consists of the following modules, each providing specific functionality:
1. Marketplacer/Base (required):
   a. Base reusable logic for all modules
   b. Marketplacer menu entry, config, ACL
2. Marketplacer/BrandApi (required):
   a. Brand API definition + related ACL
   b. Interfaces to be implemented for Marketplacer brands to create database relations + stubs
   c. Adding brand id and name to order items
3. Marketplacer/Brand (optional):
   a. Implementation of Brand interfaces to connect to database records
   b. Back-office management, URL rewrite generation, appearance in sitemap
   c. Storefront displaying on PDP, PLP, BLP, BDP, search page
   d. Setup upgrade scripts to create marketplacer_brand attribute
4. Marketplacer/SellerApi (required):
   a. Seller API definition + related ACL
   b. Interfaces to be implemented for marketplacer seller to create database relations + stubs
   c. Adding seller id and name to order items
5. Marketplacer/Seller (required):
   a. Implementation of Seller interfaces to connect to database records
   b. Back-office management, URL rewrite generation, appearance in sitemap
   c. Storefront displaying on PDP, PLP, SLP, SDP, search page
   d. Setup upgrade scripts to create marketplacer_seller attribute, create general seller, assign it to all
   products
6. Marketplacer/Marketplacer (required):
   a. Displaying of brands and seller data in orders, invoices, emails, etc.
   b. Container to display brand and seller on pdp, plp, search
   c. Displaying of seller business number

You can find more details with visual examples and API endpoint description in the User Guide distributed together with the package. 
