# Conferbot Magento 2 Module

Embeds the [Conferbot](https://conferbot.com) AI chat widget on a Magento 2 store and passes the logged-in customer identity plus store/cart context to the bot.

## See it in action

The merchant flow is the same in every Conferbot integration: paste the Chatbot ID, enable, and the bot is live. Watch the [PrestaShop demo](https://github.com/Conferbot/conferbot-prestashop) or the [WordPress demo](https://github.com/Conferbot/conferbot-wordpress). There is no Magento recording: a full Magento store is too heavy to run for one.

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

`etc/csp_whitelist.xml` allows the origins the widget uses (`cdn.conferbot.com`, `wdt.conferbot.com` over HTTPS and WebSocket and for the co-browse recorder script, `prd.media.cdn.conferbot.com`, `www.conferbot.com`, Google Fonts, `cdn.jsdelivr.net` for emoji data, OpenStreetMap tiles and `www.google.com` for AI answer source icons), and the inline loader carries Magento's CSP nonce, so the chat also works on checkout and payment pages under Magento 2.4.7+ strict CSP.

## Requirements

| | Supported |
|---|---|
| Magento | Open Source / Adobe Commerce 2.4.4 and later (2.4.x line). Hyva themes work too. |
| PHP | 8.1 or later (whatever your Magento version supports) |
| Modules it relies on | `Magento_Store`, `Magento_Customer`, `Magento_Checkout`, `Magento_Config`, `Magento_Csp` (all core) |
| Conferbot | A Conferbot account with a published chatbot, or the demo bot below to try it first |

Magento 2.3 and 2.4.0-2.4.3 are not supported (PHP 8.1 is required). The module has been tested with plain PHPUnit and in a browser against the built widget; it has not yet been run on a full Magento store (see [Tests](#tests)).

## Installation

Pick one of the three ways. All of them end with the same `bin/magento` commands, run from the Magento root as the user that owns the files.

### 1. Adobe Commerce Marketplace

The module is not listed on the Adobe Commerce Marketplace yet. Once it is, add it to your Marketplace account, then install it with Composer using your Marketplace access keys (`composer require conferbot/module-chat`) and continue at [Enable the module](#enable-the-module).

### 2. Composer from GitHub (recommended for now)

The package is not on Packagist yet, so point Composer at this repository:

```bash
composer config repositories.conferbot-magento vcs https://github.com/Conferbot/conferbot-magento
composer require conferbot/module-chat:dev-main
```

### 3. Manual (app/code)

1. Download this repository (**Code > Download ZIP** on GitHub) and unzip it.
2. Copy its contents to `app/code/Conferbot/Chat/` in your Magento root, so that `app/code/Conferbot/Chat/registration.php` exists.
3. Continue below.

### Enable the module

```bash
bin/magento module:enable Conferbot_Chat
bin/magento setup:upgrade
# production mode only:
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

Check it is on with `bin/magento module:status Conferbot_Chat`.

## Find your Chatbot ID

1. Sign in at [app.conferbot.com](https://app.conferbot.com) and open the bot you want on your store.
2. Click **Share**, then **Embed into your website**.
3. The ID is the 24-character code inside `ConferbotWidget("...")` in the code shown there, for example `64a1b2c3d4e5f6a7b8c9d0e1`. It is also in your browser's address bar while that page is open: `.../chatbot/64a1b2c3d4e5f6a7b8c9d0e1/embed`.

You do not have to pick it out by hand: paste the whole embed code or the dashboard URL and the module keeps only the ID.

**No account yet?** Paste the public demo bot ID (`691c970890527a0468f9b2c9`) to see the widget on your store before signing up.

## Configuration

In the Magento admin go to **Stores > Configuration > Services > Conferbot**.

Two steps: paste your Chatbot ID, set **Enable Chat** to Yes, **Save Config**. If what you pasted has no ID in it, the save is refused with a message saying where to find it.

| Setting | Description |
|---|---|
| Enable Chat | Master on/off switch. Set to No to remove the widget from the storefront without losing the ID. |
| Chatbot ID | Required. The 24-character ID (or the embed code / dashboard URL it is in). Nothing is shown until it is valid. |
| How the Chat Appears | Floating chat bubble (default) or popup window. |
| Identity Verification Secret | Optional. From Conferbot workspace settings > Security > Identity verification. Signs logged-in customers so Conferbot can trust them. Stored encrypted, never sent to the browser. |
| Widget Script URL | Leave as is (`https://cdn.conferbot.com/dist/v1/widget.min.js`). Only change it if Conferbot support asks you to. |

All settings support the default/website/store scopes (Widget Script URL is global), so each store view can run its own bot. The admin section needs the **Stores > Settings > Configuration > Conferbot Chat Configuration** ACL permission.

Everything else (flows, AI agent, live chat hours, colours, launcher, greetings, languages, pages to hide the chat on) is set per bot in the Conferbot dashboard and applies straight away; there is nothing to change in Magento.

## Verify it works

1. Save the configuration with your Chatbot ID and Enable Chat = Yes, then run `bin/magento cache:flush` (full page cache keeps the old page otherwise).
2. Open the storefront in a private window. The chat bubble should appear in the corner within a few seconds, on every page (unless the bot hides itself on that URL or has a load delay in the dashboard).
3. Click it: the chat opens and the bot's first message appears. Send a message and it shows up in your Conferbot inbox.
4. Log in as a customer, add something to the cart and chat again: the conversation shows the customer's name, email and cart total.
5. In the browser's developer tools you should see `widget.min.js` loaded from `cdn.conferbot.com` and a WebSocket to `wdt.conferbot.com`.

## Troubleshooting

| Symptom | What to check |
|---|---|
| No bubble at all | `bin/magento module:status Conferbot_Chat` says enabled; Enable Chat is Yes **in the scope you are viewing** (a store-view override can switch it off); the Chatbot ID is saved; the bot is published and not disabled in the dashboard; cache flushed. View the page source and search for `conferbot-js`: if it is missing, the module is not rendering (config or cache); if it is there, look at the browser console. |
| Bubble shows on some pages only | The bot's disabled URLs in the Conferbot dashboard, or a theme that removes the `before.body.end` container on some layouts. |
| Console shows `Refused to load ... because it violates the Content Security Policy` | The module whitelists the widget's origins in `etc/csp_whitelist.xml` (`cdn.conferbot.com`, `wdt.conferbot.com` over HTTPS and WebSocket, `prd.media.cdn.conferbot.com`, `www.conferbot.com`, Google Fonts, `cdn.jsdelivr.net`, OpenStreetMap tiles, `www.google.com` for answer-source icons). If another extension or a server header sets a stricter policy, add those origins there. Run `setup:upgrade` and `cache:flush` after updating the module so the new whitelist is read. |
| Old settings still in effect | Full page cache (built-in or Varnish) serves the page with the old loader until it is flushed: `bin/magento cache:flush`, and purge Varnish/CDN if you use one. |
| Logged-in customer shows as a guest, or another customer's name shows | The customer comes from the private `conferbot` customer-data section, never from the cached page. Make sure nothing caches `/customer/section/load` (some CDN rules do), and that your theme loads customer data (Luma: RequireJS `Magento_Customer/js/customer-data`; Hyva: the `private-content-loaded` event). |
| Customer details marked unverified | Add the Identity Verification Secret, or check it matches the one in Conferbot (copy it again; it is stored encrypted, so you cannot read it back). |
| Save fails with "We could not find a Chatbot ID" | What you pasted has no 24-character ID in it. Copy it again from Share > Embed into your website. |
| Changes do not appear after updating the module | Run `setup:upgrade`, then in production mode `setup:di:compile` and `setup:static-content:deploy -f`, then `cache:flush`. |

## Uninstall

Switch the chat off first if you only want to pause it (**Enable Chat = No**).

Installed with Composer:

```bash
bin/magento module:disable Conferbot_Chat
composer remove conferbot/module-chat
bin/magento setup:upgrade
bin/magento cache:flush
```

Installed in `app/code`:

```bash
bin/magento module:disable Conferbot_Chat
rm -rf app/code/Conferbot/Chat
bin/magento setup:upgrade
bin/magento cache:flush
```

The module creates no tables. To remove its saved settings as well:

```sql
DELETE FROM core_config_data WHERE path LIKE 'conferbot/%';
```

Your bot, conversations and contacts stay in your Conferbot account.

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

## Notes & limitations

- No API key or workspace ID is required - the widget resolves everything server-side from the Chatbot ID over Socket.IO (`wdt.conferbot.com`).
- Outbound webhooks (order status, product updates, abandoned cart) are **not** part of this version.

## License

Open Software License (OSL 3.0). See [LICENSE](LICENSE).
