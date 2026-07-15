# Conferbot Magento 2 Module

Embeds the [Conferbot](https://conferbot.com) AI chat widget on a Magento 2 store and passes the logged-in customer identity plus store/cart context to the bot.

## See it in action

The merchant flow is identical across every Conferbot integration: paste the Chatbot ID, enable, and the bot is live. Watch the [PrestaShop demo](https://github.com/Conferbot/conferbot-prestashop#see-it-in-action) or the [Shopify demo](https://github.com/Conferbot/conferbot-shopify#see-it-in-action) - a Magento-specific recording will be added after the staging smoke test (a local Magento instance requires Adobe Marketplace composer credentials, so it cannot be recorded in this repo's CI-style environment).

## How it works

The module adds a block to the `before.body.end` container on every storefront page and injects the standard Conferbot loader snippet:

```html
<script src="https://cdn.conferbot.com/dist/v1/widget.min.js" async></script>
<script>
  window.ConferbotWidget("<CHATBOT_ID>", "live_chat", {
    user:       { id, name, email },              // logged-in customer, if any
    customData: { platform: "magento", storeName, storeUrl,
                  storeLanguage, storeCurrency, cartTotal, cartItemCount }
  });
</script>
```

The only credential needed is the **Chatbot ID** (the bot's ID from the Conferbot dashboard). All widget appearance and behavior - position, colors, theme, language, auto-open, proactive prompts, disabled URLs - is configured in the Conferbot dashboard under **Customize** and delivered to the widget at runtime; the module does not duplicate those settings.

`options.user` and `options.customData` become the visitor's identity/context in Conferbot chats, so the bot and human agents see the customer's name, email, cart total, and store details.

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
| Chatbot ID | Required. In the Conferbot dashboard open your bot, go to **Share > Embed into website**, and copy the Bot ID (24-character hex). |
| How the Chat Appears | Floating chat bubble (default) or popup window. |
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
│   └── adminhtml/system.xml             # Stores > Configuration > Services > Conferbot
├── Model/
│   ├── Loader.php                       # PURE: bot ID extract/validate, options build, safe JSON
│   └── Config/
│       ├── Backend/BotId.php            # Extracts 24-hex from whatever was pasted on save
│       └── Source/EmbedType.php         # live_chat / popup_chat options
├── ViewModel/WidgetConfig.php           # Config + customer session + quote -> template data
├── view/frontend/
│   ├── layout/default.xml               # before.body.end block on every page
│   └── templates/widget.phtml           # Widget loader snippet
├── i18n/en_US.csv
├── tests/                               # Plain PHPUnit suite (no Magento needed)
├── phpunit.xml
└── logo.png
```

Adding context data is a one-file change in `Model/Loader.php` plus a getter in `ViewModel/WidgetConfig.php`.

## Tests

The PHPUnit suite covers bot ID parsing (bare/snippet/URL/garbage), strict validation, the demo bot ID, widget option building (guest/customer/cart variants, type casting, missing store fields), and script-injection safety of the inlined JSON. It runs without a Magento installation - `Model/Loader.php` is dependency-free by design.

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
- If the widget does not appear: check the module is enabled (`bin/magento module:status Conferbot_Chat`), Enable Chat is Yes in the current scope, the Chatbot ID is correct and the bot is published/not disabled, and flush the cache (`bin/magento cache:flush`). With full page cache enabled the block renders into the cached page; flush after config changes.

## License

Open Software License (OSL 3.0). See [LICENSE](LICENSE).
