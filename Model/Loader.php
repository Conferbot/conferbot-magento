<?php
/**
 * Pure widget-loader logic: bot ID parsing/validation and the options
 * payload for window.ConferbotWidget(botId, embedType, options).
 *
 * Deliberately dependency-free (no Magento imports, static methods only)
 * so the class loads standalone under plain PHPUnit - see tests/bootstrap.php.
 *
 * @author    Conferbot <support@conferbot.com>
 * @copyright 2023-2026 Conferbot
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\Model;

class Loader
{
    /** Public demo bot - works out of the box so merchants can try the widget instantly. */
    public const DEMO_BOT_ID = '691c970890527a0468f9b2c9';

    /** Default widget script location (Conferbot CDN). */
    public const DEFAULT_SCRIPT_URL = 'https://cdn.conferbot.com/dist/v1/widget.min.js';

    /**
     * Pulls the first 24-hex token out of whatever was pasted
     * (bare ID, full embed snippet, dashboard URL, extra whitespace).
     *
     * @param string $text
     * @return string extracted ID, or '' when none found
     */
    public static function extractBotId(string $text): string
    {
        if (preg_match('/[a-f0-9]{24}/i', $text, $m)) {
            return $m[0];
        }

        return '';
    }

    /**
     * Strict validation used at render time: exactly 24 hex characters.
     *
     * @param string $id
     * @return bool
     */
    public static function isValidBotId(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{24}$/i', $id);
    }

    /**
     * Builds the third argument for window.ConferbotWidget().
     *
     * options.user carries the logged-in customer identity; options.customData
     * carries store and cart context. Both become the visitor's context in
     * Conferbot conversations.
     *
     * @param array $store keys: name, url, language, currency
     * @param array|null $customer keys: id, name, email (null = guest)
     * @param array|null $cart keys: total, itemCount (null = no quote)
     * @param string $secret the workspace's identity verification secret ('' = off)
     * @return array widget options
     */
    public static function buildOptions(array $store, ?array $customer = null, ?array $cart = null, string $secret = ''): array
    {
        $options = ['customData' => [
            'platform' => 'magento',
            'storeName' => isset($store['name']) ? (string) $store['name'] : '',
            'storeUrl' => isset($store['url']) ? (string) $store['url'] : '',
            'storeLanguage' => isset($store['language']) ? (string) $store['language'] : '',
            'storeCurrency' => isset($store['currency']) ? (string) $store['currency'] : '',
        ]];

        if (is_array($customer) && !empty($customer['id'])) {
            $options['user'] = [
                'id' => 'mag-' . (int) $customer['id'],
                'name' => isset($customer['name']) ? trim((string) $customer['name']) : '',
                'email' => isset($customer['email']) ? (string) $customer['email'] : '',
            ];
            // Identity verification: the id signed with the workspace secret,
            // so Conferbot knows this is the store's own logged-in customer.
            if ($secret !== '') {
                $options['user']['hash'] = self::userHash($options['user']['id'], $secret);
            }
        }

        if (is_array($cart)) {
            if (isset($cart['total'])) {
                $options['customData']['cartTotal'] = (float) $cart['total'];
            }
            if (isset($cart['itemCount'])) {
                $options['customData']['cartItemCount'] = (int) $cart['itemCount'];
            }
        }

        return $options;
    }

    /**
     * HMAC-SHA256 of the signed value (the customer id) with the secret, hex - as Conferbot checks it.
     */
    public static function userHash(string $value, string $secret): string
    {
        return hash_hmac('sha256', $value, $secret);
    }

    /**
     * The order on the checkout success page as a purchase, reported once per order.
     *
     * @param array|null $order keys: id, incrementId, total, currency (null = no order)
     * @return array events: name, props, once
     */
    public static function events(?array $order): array
    {
        if (!is_array($order) || empty($order['id'])) {
            return [];
        }

        return [[
            'name' => 'purchase',
            'props' => [
                'value' => (float) $order['total'],
                'currency' => (string) $order['currency'],
                'order_id' => (string) ($order['incrementId'] ?? $order['id']),
            ],
            'once' => 'order-' . (int) $order['id'],
        ]];
    }

    /**
     * JSON safe to inline inside a <script> block: JSON_HEX_* neutralize
     * user-controlled values (customer name/email) in the HTML context.
     *
     * @param array $options
     * @return string
     */
    public static function toJson($options): string
    {
        return (string) json_encode($options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}
