# Conferbot Magento 2 Module

Embeds the [Conferbot](https://conferbot.com) AI chat widget on a Magento 2 store and passes the logged-in customer identity plus store/cart context to the bot.

## See it in action

The merchant flow is identical across every Conferbot integration: paste the Chatbot ID, enable, and the bot is live. Watch the [PrestaShop demo](https://github.com/Conferbot/conferbot-prestashop#see-it-in-action) or the [Shopify demo](https://github.com/Conferbot/conferbot-shopify#see-it-in-action) - a Magento-specific recording will be added after the staging smoke test (a local Magento instance requires Adobe Marketplace composer credentials, so it cannot be recorded in this repo's CI-style environment).

## How it works

The module adds a block to the `before.body.end` container on every storefront page. Most pages are served from the full page cache and shared by every visitor, so the page itself carries only the store's context. The visitor's identity and cart come from Magento's private content: a `conferbot` customer-data section, loaded per visitor and refreshed after cart and login changes. The widget boots once that is known (or as a guest when it is not available), so the cached page stays correct for everyone:

```js
// Rendered through Magento's SecureHtmlRenderer (CSP nonce).
require(['Magento_Customer/js/customer-data'], function (customerData) {
  var section = customerData.get('conferbot');   // { user: { id: "mag-12", name, email, hash }, cartTotal, cartItemCount }
  // ...load https://cdn.conferbot.com/dist/v1/widget.min.js, then:
  window.ConferbotWidget("<CHATBOT_ID>", "live_chat", {
    user:       section().user,                  // logged-in customer, if any
    customData: { platform: "magento", storeName, storeUrl, storeLanguage, storeCurrency, cartTotal, cartItemCount }
  });
  // On the checkout success page, once per order:
  window.ConferbotWidget.track("purchase", { value, currency, order_id });
});
```

Hyva and other themes without RequireJS get the same data from their `private-content-loaded` event.

The only credential needed is the **Chatbot ID**. Everything visitors see is set per bot in the Conferbot dashboard and delivered at runtime, with nothing to configure in Magento: flows and AI agents, live chat with handover to your team (queue position, leave a message when nobody is free, read receipts, file sharing, post-chat rating), the home screen, themes and dark mode, colours, launcher and mascot, greetings, languages and disabled URLs. The module does not duplicate those settings.

### Identity verification (optional)

With a secret from Conferbot (workspace settings > Security > Identity verification) under **Identity Verification Secret**, each logged-in customer is sent with `hash = HMAC-SHA256(secret, user.id)`, computed on your server in the customer-data section. Conferbot then trusts their name and email and can link their chats to their contact. The secret is stored encrypted and never sent to the browser. Without it, the customer's details are still passed but marked unverified, and if your workspace *requires* verification they are dropped.

### Sales tracking

The checkout success page reports the order (`purchase`, with grand total, currency and order number) once per order, and cart increases are reported as `add_to_cart`. Conferbot credits a sale to a chat that happened within the week before it.

### Content Security Policy

`etc/csp_whitelist.xml` allows the origins the widget uses (`cdn.conferbot.com`, `wdt.conferbot.com` over HTTPS and WebSocket, `prd.media.cdn.conferbot.com`, `www.conferbot.com`, Google Fonts, `cdn.jsdelivr.net` for emoji data and OpenStreetMap tiles), and the inline loader carries Magento's CSP nonce, so the chat also works on checkout and payment pages under Magento 2.4.7+ strict CSP.

## Requirements

- Magento 2.4.x (Open Source or Adobe Commerce)
- PHP 8.1+
- A Conferbot account with a published chatbot (or use the built-in demo bot to try it first)

## Installation

### Via Composer (recommended)

```bash
composer require conferbot/module-chat
bin/magento module:enable Conferbot_Chat
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual (app/code)

1. Copy this repository to `app/code/Conferbot/Chat/` in your Magento root.
2. Enable and install:

```bash
bin/magento module:enable Conferbot_Chat
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

In the Magento admin go to **Stores > Configuration > Services > Conferbot**.

Two steps: paste your Chatbot ID, set **Enable Chat** to Yes, save. You can paste the bare ID, the full embed snippet, or a dashboard URL - the module extracts the ID for you.

**No account yet?** Paste the public demo bot ID (`691c970890527a0468f9b2c9`). It works out of the box so you can see the widget live before signing up.

| Setting | Description |
|---|---|
| Enable Chat | Master on/off switch. |
| Chatbot ID | Required. In the Conferbot dashboard open your bot, go to **Share > Embed** and copy the code; the module finds the 24-character ID in it. |
| How the Chat Appears | Floating chat bubble (default) or popup window. |
| Identity Verification Secret | Optional. Signs logged-in customers so Conferbot can trust them. Stored encrypted. |
| Widget Script URL | Leave as is for the Conferbot CDN. Useful for staging/self-hosted widget builds. |

All settings support Magento's default/website/store scopes (Widget Script URL is global).

## Architecture

The Magento wiring (system.xml, view model, template) stays thin; the loader logic lives in one pure, individually tested class:

```
conferbot-magento/
├── registration.php                     # Component registration
├── composer.json                        # conferbot/module-chat, magento2-module
├── etc/
│   ├── module.xml                       # Module declaration + sequence
│   ├── config.xml                       # Defaults: disabled, live_chat, CDN URL
│   ├── acl.xml                          # Admin config resource
│   ├── csp_whitelist.xml                # Widget origins for Magento's CSP
│   ├── frontend/di.xml                  # Registers the "conferbot" customer-data section
│   ├── frontend/sections.xml            # Refreshes it after cart and login changes
│   └── adminhtml/system.xml             # Stores > Configuration > Services > Conferbot
├── Model/
│   ├── Loader.php                       # PURE: bot ID extract/validate, options build, safe JSON
│   └── Config/
│       ├── Backend/BotId.php            # Extracts 24-hex from whatever was pasted on save
│       └── Source/EmbedType.php         # live_chat / popup_chat options
├── CustomerData/Conferbot.php           # Private section: signed customer + cart (never in the cached page)
├── ViewModel/WidgetConfig.php           # Config + store context -> template data (cache-safe)
├── ViewModel/OrderEvents.php            # Success page: the order as a purchase event
├── view/frontend/
│   ├── layout/default.xml               # before.body.end block on every page
│   ├── layout/checkout_onepage_success.xml
│   └── templates/widget.phtml, order.phtml
├── i18n/en_US.csv
├── tests/                               # Plain PHPUnit suite (no Magento needed)
├── phpunit.xml
└── logo.png
```

Per-visitor data belongs in `CustomerData/Conferbot.php` (never in the view model, whose output is cached); store-wide data in `ViewModel/WidgetConfig.php`.

## Tests

The PHPUnit suite (23 tests) covers bot ID parsing (bare/snippet/URL/garbage), strict validation, the demo bot ID, widget option building (guest/customer/cart variants, type casting, missing store fields), the identity hash, the purchase event, and script-injection safety of the inlined JSON. The loader's boot logic (section arrives after a reload, cart increase, queued purchase; RequireJS and Hyva paths) was exercised in Chrome against the built widget with a stand-in for `customer-data`. It has not yet been run on a full Magento store. It runs without a Magento installation - `Model/Loader.php` is dependency-free by design.

```bash
# any PHP 8.1+ with phpunit 9:
phpunit

# or containerized:
docker run --rm -v "$PWD":/mod -v /path/to/phpunit-9.phar:/phpunit.phar:ro \
  -w /mod php:8.2-cli php /phpunit.phar
```

## Manual verification

1. Save the configuration with a valid Chatbot ID (or the demo bot) and Enable Chat = Yes.
2. Open the storefront - the chat bubble should appear on every page (subject to the bot's own dashboard settings such as disabled URLs and load delay).
3. Log in as a customer, add something to the cart, and start a chat: the conversation in the Conferbot inbox should show the customer's name/email and cart context.

## Notes & limitations

- No API key or workspace ID is required - the widget resolves everything server-side from the Chatbot ID over Socket.IO (`wdt.conferbot.com`).
- Outbound webhooks (order status, product updates, abandoned cart) are **not** part of this version.
- If the widget does not appear: check the module is enabled (`bin/magento module:status Conferbot_Chat`), Enable Chat is Yes in the current scope, the Chatbot ID is correct and the bot is published/not disabled, and flush the cache (`bin/magento cache:flush`). With full page cache enabled the loader renders into the cached page, so flush after config changes; the customer and cart are never cached (they come from the customer-data section).

## License

Open Software License (OSL 3.0). See [LICENSE](LICENSE).
