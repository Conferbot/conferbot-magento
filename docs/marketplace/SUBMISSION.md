# Adobe Commerce Marketplace: submission kit

Everything needed to list the Conferbot module (`conferbot/module-chat` 1.1.0, module `Conferbot_Chat`) on the Adobe Commerce Marketplace. Checked against the Adobe seller guides on 2026-10-17.

## 1. Where to submit

| What | Where |
| --- | --- |
| Account | A **Commerce Marketplace account** (Adobe ID), created from the Developer Portal at https://commercedeveloper.adobe.com (also reachable from https://commercemarketplace.adobe.com). Choose a **company** account; all of one company's listings must sit under one account. Accept the Marketplace Master Terms and the Development Terms. Set the **vendor name to `conferbot`**, matching the Composer vendor. Guide: https://developer.adobe.com/commerce/marketplace/guides/sellers/developer-register |
| Submission | Developer Portal > Extensions > **Add product**. Fill in the product information, then send it through **Technical review** (the package) and **Marketing review** (the listing); both must pass. Guide: https://developer.adobe.com/commerce/marketplace/guides/sellers/submit-for-review |
| Technical rules | https://developer.adobe.com/commerce/marketplace/guides/sellers/technical-review-guidelines |
| Marketing rules | https://developer.adobe.com/commerce/marketplace/guides/sellers/marketing-review-guidelines |
| Fees | No submission or listing fee. Paid extensions split revenue 85/15; this one is free. Tax forms (W-8/W-9) and PayPal are only required to *sell*. |

## 2. Current requirements and where we stand

| Requirement | Status |
| --- | --- |
| Zip with `composer.json` and `registration.php` at the root; no `.git` or `vendor` | OK. `conferbot-module-chat-1.1.0.zip` is flat with both at the root. |
| Zip name `vendor-name_package-name-version.zip` | **Rename at upload** to `conferbot_module-chat-1.1.0.zip`. |
| `composer.json`: `name`, `type: magento2-module`, `version`, `license`, `autoload` of `registration.php`; no `source`/`dist` keys | OK: `conferbot/module-chat`, 1.1.0, OSL-3.0. |
| Package of 30 MB or less | OK (41 KB). |
| Magento2 coding standard (phpcs, Adobe's sniff set) | **Not run yet.** Run `vendor/bin/phpcs --standard=Magento2 .` with `magento/magento-coding-standard` before submitting. |
| Install and Varnish test in production mode on every supported PHP version; MFTF; manual QA | **Risk.** The README says the module "has not yet been run on a full Magento store". It is designed for full page cache (private customer-data section), but EQP is its first real install. Install it once on a 2.4.7/2.4.8 instance in production mode with Varnish before submitting, or expect a technical rejection. |
| No `TODO`/`FIXME` left in the code | OK (none found). |
| User guide PDF, English, 5 MB or less | Done in this kit: `assets/user-guide.pdf`. |
| Name of 4 words or fewer (Adobe's pages say 4 and 5), English, Title Case; no "Magento", "Adobe", "Extension", "Module" or "Plugin"; `&` instead of "And" | OK with the name below. |
| Icon: JPG/PNG of 5 MB or less, no Adobe/Magento logos | Done: `assets/icon-512x512.png`. |
| **At least 2 gallery images** (JPG/PNG of 5 MB or less, no URLs or empty white space) | **Missing.** There is no Magento recording, and a full Magento store is too heavy to run on this machine. They have to be captured on a real install (see checklist). |
| Third-party subscription disclosed **in bold in the opening paragraph** of the long description | Done in the copy below. |

Review: no SLA. The Development Terms allow up to 90 business days for acceptance; in practice it is a few weeks with feedback rounds.

## 3. Blockers

1. **A real install test** (production mode plus Varnish) and **phpcs Magento2** clean. EQP runs both, and the module has never been installed on a full store.
2. **Two or more gallery images** taken from that install: the admin configuration and the storefront widget.
3. The Adobe account: company profile, vendor name `conferbot`, and the terms. Tax and PayPal are only needed if it is ever paid.

## 4. Listing copy (ready to paste)

- **Title** (3 words): `Conferbot AI Chatbot`. Allowed because Conferbot is the integrated service itself.
- **Integration info:** service "Conferbot", URL https://www.conferbot.com, third-party subscription required: **Yes (a free plan is available)**.
- **Short description:** `Add an AI chatbot and live chat to your store: answer shoppers 24/7, capture leads and hand over to your team, with customer and cart context.`
- **Long description** (starts with a plain paragraph; the fee note is in bold):

  > Conferbot adds an AI-powered chat assistant to every page of your store. It answers product and order questions instantly, day and night, collects leads, and hands the conversation to a person on your team whenever a shopper needs one. **A Conferbot account is required to use your own bot. A free plan is available, and paid Conferbot plans are billed by Conferbot, not through this listing.**
  >
  > Setup takes a minute: install, paste your Chatbot ID under Stores > Configuration > Services > Conferbot, set Enable Chat to Yes and save. The widget's look and behaviour (guided flows or an AI agent trained on your content, live chat with handover, colours, launcher, greetings, languages) are set in the Conferbot dashboard and apply straight away.
  >
  > Built for full page cache and Varnish. Cached pages carry only store context. The visitor's identity and cart come from a private customer-data section, so every visitor sees their own context. The inline loader carries Magento's CSP nonce, and the widget's origins are whitelisted, so the chat also works under strict CSP on checkout. Hyva themes are supported.
  >
  > Key features:
  > - Logged-in customer name, email and cart total passed to the conversation
  > - Optional identity verification: each customer is signed with HMAC-SHA256 on the server, and the secret is stored encrypted and never sent to the browser
  > - The order on the checkout success page is reported once, so sales are credited to the chats that led to them
  > - Per website and store view configuration: run a different bot per store view
  > - No database tables, and no theme edits

- **Categories:** main *Customer Support*; subcategories *Live Chat* and *Marketing* (pick the closest labels the form offers; 1 main plus up to 3 sub).
- **Compatibility:** Adobe Commerce and Magento Open Source **2.4.4 to 2.4.8**, PHP 8.1 to 8.4 (tick only what you install-test). Platform "Adobe Commerce 2.x (M2)". Stability: Stable. Browsers: all current.
- **License:** OSL-3.0 (custom licence name "Open Software License 3.0", URL https://opensource.org/licenses/OSL-3.0). **Price:** Free.
- **Release notes (1.1.0):** paste the 1.1.0 section of `CHANGELOG.md`.
- **Support:** `support@conferbot.com` (the support email in `composer.json`). Documentation: https://docs.conferbot.com
- **Privacy policy:** https://www.conferbot.com/privacy-policy (HTTP 200). **Terms:** https://www.conferbot.com/terms-of-service (HTTP 200)
- Do **not** link to the other Conferbot integrations or to buying anything off-Marketplace in the listing or the PDF; Adobe rejects that.

### Data and GDPR statement

This statement matches `CustomerData/Conferbot.php`, `ViewModel/*`, `view/frontend/templates/*.phtml` and `etc/config.xml`.

- Settings live in `core_config_data` under `conferbot/general/*`. The identity secret is stored encrypted. The module creates no tables.
- On storefront pages where the chat is enabled with a valid ID, the browser loads `https://cdn.conferbot.com/dist/v1/widget.min.js` and connects to `wdt.conferbot.com`. The page passes the widget:
  - the Chatbot ID
  - `platform: "magento"`, plus the store name, URL, language and currency
  - for **logged-in customers only**: the customer ID (`mag-<id>`), name and email. If a secret is set, it also passes an HMAC-SHA256 of the ID, computed on the server.
  - the cart total and item count
  - on the checkout success page, once per order: the order value, currency and order number
- Whatever the shopper types or uploads in the chat goes to Conferbot.
- The module sets no cookies of its own. It uses Magento's standard private-content (customer-data) storage.

## 5. Reviewer test instructions

Paste these into the technical review notes:

> 1. `composer require conferbot/module-chat` (or unzip into app/code/Conferbot/Chat), then `bin/magento module:enable Conferbot_Chat && bin/magento setup:upgrade && bin/magento cache:flush` (plus `setup:di:compile` and `setup:static-content:deploy -f` in production mode).
> 2. Admin: Stores > Configuration > Services > Conferbot. Paste the public demo Chatbot ID `691c970890527a0468f9b2c9` (a live "Startup Consultation Chatbot"; no Conferbot account needed), set Enable Chat = Yes, then Save Config and flush the cache.
> 3. Storefront: a chat bubble appears bottom right on every page. Click it and reply to the bot.
> 4. Log in as a customer and add a product: the customer's name, email and cart total are passed to the conversation. Pages stay cacheable (Varnish/FPC); the identity comes from the `conferbot` customer-data section.

## 6. Assets (in `docs/marketplace/assets/`)

| File | Size | Content |
| --- | --- | --- |
| `icon-512x512.png` | 512x512 | Conferbot logo (from the landing site) on white, with no Adobe/Magento marks. Adobe publishes no pixel size. |
| `user-guide.pdf` | A4, 3 pages | Required English documentation: install, configuration, cache/CSP behaviour, data sent, troubleshooting, uninstall, support. |
| Gallery images | **to capture** | At least 2: (1) Stores > Configuration > Services > Conferbot with an ID saved, (2) the storefront with the chat open. Use Luma, crop out the browser and URL bar, 1538x1022 suggested. |

## 7. Owner checklist

- [ ] Install 1.1.0 on a Magento 2.4.7+ instance in production mode with Varnish, and run phpcs Magento2. Fix anything found and release 1.1.1 if needed.
- [ ] Capture the 2 gallery images during that install.
- [ ] Create the Commerce Marketplace account (company, vendor name `conferbot`) and accept the Master and Development Terms.
- [ ] Add product: title, integration info, compatibility, OSL-3.0, Free.
- [ ] Technical submission: upload `conferbot_module-chat-<version>.zip`, `user-guide.pdf` and the release notes.
- [ ] Marketing submission: icon, gallery images, the descriptions above, categories.
- [ ] Respond to EQP feedback (malware, phpcs, install/Varnish, manual QA).

Owner time: about 3 hours including the install test, or about 45 minutes for the forms alone. Review: weeks (no SLA). Fees: none for a free extension.
