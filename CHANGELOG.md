# Changelog

## 1.1.0 - 2026-10-17

- Full page cache safe: the cached page carries only the store context; the visitor's customer and cart come from a `conferbot` customer-data section loaded per visitor.
- Content Security Policy: the widget boots from the private section with a nonce from Magento's secure renderer, and `etc/csp_whitelist.xml` lists the widget's origins, including the co-browse recorder and agent source icons. The module now depends on `Magento_Csp`.
- Identity verification: with the Identity Verification Secret set (stored encrypted), the logged-in customer is sent with `hash = HMAC-SHA256(secret, user.id)`, computed on the server.
- Sales tracking: the order is reported on the checkout success page.
- The Chatbot ID field comment, error and translations point to Share > Embed into your website in the Conferbot dashboard.
- Declarative module (no setup_version).

## 1.0.0 - 2026-07-15

- Initial version: module that loads the Conferbot widget on every storefront page with the logged-in customer and store/cart context, and a Chatbot ID check on save. Not released on GitHub.
