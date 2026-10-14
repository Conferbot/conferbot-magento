<?php
/**
 * Unit tests for Conferbot\Chat\Model\Loader.
 *
 * Runs under plain PHPUnit (no Magento installation) - see tests/bootstrap.php.
 */
declare(strict_types=1);

use Conferbot\Chat\Model\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    private const VALID_ID = '64a1b2c3d4e5f6a7b8c9d0e1';

    // -----------------------------------------------------------------
    // extractBotId
    // -----------------------------------------------------------------

    public function testExtractsBareId(): void
    {
        $this->assertSame(self::VALID_ID, Loader::extractBotId(self::VALID_ID));
    }

    public function testExtractsIdFromBareIdWithWhitespace(): void
    {
        $this->assertSame(self::VALID_ID, Loader::extractBotId("  \n" . self::VALID_ID . "\t "));
    }

    public function testExtractsIdFromFullEmbedSnippet(): void
    {
        $snippet = '<script>window.ConferbotWidget("' . self::VALID_ID . '", "live_chat", {});</script>';
        $this->assertSame(self::VALID_ID, Loader::extractBotId($snippet));
    }

    public function testExtractsIdFromDashboardUrl(): void
    {
        $url = 'https://app.conferbot.com/bots/' . self::VALID_ID . '/share?tab=embed';
        $this->assertSame(self::VALID_ID, Loader::extractBotId($url));
    }

    public function testExtractsUppercaseIdAsIs(): void
    {
        $upper = strtoupper(self::VALID_ID);
        $this->assertSame($upper, Loader::extractBotId($upper));
    }

    public function testExtractReturnsEmptyStringForGarbage(): void
    {
        $this->assertSame('', Loader::extractBotId('hello, where is my bot id?!'));
    }

    public function testExtractReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', Loader::extractBotId(''));
    }

    public function testExtractReturnsEmptyStringForTooShortHex(): void
    {
        $this->assertSame('', Loader::extractBotId('abc123'));
    }

    // -----------------------------------------------------------------
    // isValidBotId
    // -----------------------------------------------------------------

    public function testValidatesExact24Hex(): void
    {
        $this->assertTrue(Loader::isValidBotId(self::VALID_ID));
        $this->assertTrue(Loader::isValidBotId(strtoupper(self::VALID_ID)));
    }

    public function testRejectsInvalidIds(): void
    {
        $this->assertFalse(Loader::isValidBotId(''));
        $this->assertFalse(Loader::isValidBotId('abc'));
        $this->assertFalse(Loader::isValidBotId(self::VALID_ID . 'ff'), 'too long');
        $this->assertFalse(Loader::isValidBotId(substr(self::VALID_ID, 0, 23)), 'too short');
        $this->assertFalse(Loader::isValidBotId('g4a1b2c3d4e5f6a7b8c9d0e1'), 'non-hex character');
        $this->assertFalse(Loader::isValidBotId(' ' . self::VALID_ID), 'leading whitespace');
    }

    public function testDemoBotIdIsValid(): void
    {
        $this->assertSame('691c970890527a0468f9b2c9', Loader::DEMO_BOT_ID);
        $this->assertTrue(Loader::isValidBotId(Loader::DEMO_BOT_ID));
        $this->assertSame(Loader::DEMO_BOT_ID, Loader::extractBotId('try ' . Loader::DEMO_BOT_ID . ' now'));
    }

    // -----------------------------------------------------------------
    // buildOptions
    // -----------------------------------------------------------------

    private function store(): array
    {
        return [
            'name' => 'Acme Store',
            'url' => 'https://acme.example/',
            'language' => 'en_US',
            'currency' => 'USD',
        ];
    }

    public function testGuestOptionsHaveCustomDataButNoUser(): void
    {
        $options = Loader::buildOptions($this->store());

        $this->assertArrayNotHasKey('user', $options);
        $this->assertSame([
            'platform' => 'magento',
            'storeName' => 'Acme Store',
            'storeUrl' => 'https://acme.example/',
            'storeLanguage' => 'en_US',
            'storeCurrency' => 'USD',
        ], $options['customData']);
    }

    public function testCustomerOptionsIncludePrefixedUser(): void
    {
        $options = Loader::buildOptions($this->store(), [
            'id' => 42,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertSame([
            'id' => 'mag-42',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], $options['user']);
    }

    public function testCustomerWithEmptyIdIsTreatedAsGuest(): void
    {
        $options = Loader::buildOptions($this->store(), ['id' => 0, 'name' => 'Ghost', 'email' => 'g@x.com']);
        $this->assertArrayNotHasKey('user', $options);
    }

    public function testCartValuesAreCastToFloatAndInt(): void
    {
        $options = Loader::buildOptions($this->store(), null, [
            'total' => '129.9500',
            'itemCount' => '3.0000',
        ]);

        $this->assertSame(129.95, $options['customData']['cartTotal']);
        $this->assertSame(3, $options['customData']['cartItemCount']);
    }

    public function testNoCartMeansNoCartKeys(): void
    {
        $options = Loader::buildOptions($this->store(), null, null);

        $this->assertArrayNotHasKey('cartTotal', $options['customData']);
        $this->assertArrayNotHasKey('cartItemCount', $options['customData']);
    }

    public function testMissingStoreFieldsDefaultToEmptyStrings(): void
    {
        $options = Loader::buildOptions([]);

        $this->assertSame('magento', $options['customData']['platform']);
        $this->assertSame('', $options['customData']['storeName']);
        $this->assertSame('', $options['customData']['storeUrl']);
        $this->assertSame('', $options['customData']['storeLanguage']);
        $this->assertSame('', $options['customData']['storeCurrency']);
    }

    public function testCustomerNameIsTrimmed(): void
    {
        $options = Loader::buildOptions($this->store(), ['id' => 7, 'name' => '  Solo  ', 'email' => 's@x.com']);
        $this->assertSame('Solo', $options['user']['name']);
    }

    // -----------------------------------------------------------------
    // toJson - script-injection safety
    // -----------------------------------------------------------------

    public function testJsonIsScriptSafeForHostileCustomerName(): void
    {
        $options = Loader::buildOptions($this->store(), [
            'id' => 13,
            'name' => '</script><script>alert(1)</script>',
            'email' => 'evil@example.com',
        ]);

        $json = Loader::toJson($options);

        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertStringNotContainsString('</script', $json);

        $decoded = json_decode($json, true);
        $this->assertSame('</script><script>alert(1)</script>', $decoded['user']['name']);
    }

    public function testJsonEscapesQuotesApostrophesAndAmpersands(): void
    {
        $options = Loader::buildOptions($this->store(), [
            'id' => 5,
            'name' => 'O\'Brien & "Sons"',
            'email' => 'ob@example.com',
        ]);

        $json = Loader::toJson($options);

        $this->assertStringNotContainsString("'", $json);
        $this->assertStringNotContainsString('&', $json);
        $this->assertStringContainsString('\\u0027', $json);
        $this->assertStringContainsString('\\u0026', $json);
        $this->assertStringContainsString('\\u0022', $json);

        $decoded = json_decode($json, true);
        $this->assertSame('O\'Brien & "Sons"', $decoded['user']['name']);
    }

    public function testJsonRoundTripsFullOptions(): void
    {
        $options = Loader::buildOptions(
            $this->store(),
            ['id' => 99, 'name' => 'Full Round', 'email' => 'fr@example.com'],
            ['total' => 10.5, 'itemCount' => 2]
        );

        $decoded = json_decode(Loader::toJson($options), true);

        $this->assertSame('mag-99', $decoded['user']['id']);
        $this->assertSame('magento', $decoded['customData']['platform']);
        $this->assertSame(10.5, $decoded['customData']['cartTotal']);
        $this->assertSame(2, $decoded['customData']['cartItemCount']);
    }

    public function testLoggedInCustomerIsSignedWithTheWorkspaceSecret(): void
    {
        $customer = ['id' => 12, 'name' => 'Jane Doe', 'email' => 'jane@example.com'];
        $options = Loader::buildOptions([], $customer, null, 's3cret');
        $this->assertSame(hash_hmac('sha256', 'mag-12', 's3cret'), $options['user']['hash']);
        $this->assertArrayNotHasKey('hash', Loader::buildOptions([], $customer)['user']);
        $this->assertStringNotContainsString('s3cret', Loader::toJson($options));
    }

    public function testSuccessPageOrderIsAPurchaseOncePerOrder(): void
    {
        $events = Loader::events(['id' => 5, 'incrementId' => '000000005', 'total' => 49.5, 'currency' => 'EUR']);
        $this->assertSame([[
            'name' => 'purchase',
            'props' => ['value' => 49.5, 'currency' => 'EUR', 'order_id' => '000000005'],
            'once' => 'order-5',
        ]], $events);
        $this->assertSame('[]', Loader::toJson(Loader::events(null)));
    }
}
