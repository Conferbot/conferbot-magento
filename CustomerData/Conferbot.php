<?php
/**
 * The "conferbot" customer-data section: what the widget knows about this
 * visitor, loaded privately per visitor (never in the cached page) and
 * refreshed by Magento after cart and login changes (etc/frontend/sections.xml).
 *
 * { user?: {id, name, email, hash?}, cartTotal?, cartItemCount? }
 *
 * @author    Conferbot <support@conferbot.com>
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\CustomerData;

use Conferbot\Chat\Model\Loader;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Conferbot implements SectionSourceInterface
{
    private const XML_PATH_IDENTITY_SECRET = 'conferbot/general/identity_secret';

    /** @var CustomerSession */
    private $customerSession;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var EncryptorInterface */
    private $encryptor;

    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    public function getSectionData(): array
    {
        $options = Loader::buildOptions([], $this->getCustomerData(), $this->getCartData(), $this->getSecret());
        $data = [];
        if (isset($options['user'])) {
            $data['user'] = $options['user'];
        }
        foreach (['cartTotal', 'cartItemCount'] as $key) {
            if (isset($options['customData'][$key])) {
                $data[$key] = $options['customData'][$key];
            }
        }

        return $data;
    }

    /** The workspace's identity verification secret, stored encrypted ('' = off). */
    private function getSecret(): string
    {
        $stored = (string) $this->scopeConfig->getValue(self::XML_PATH_IDENTITY_SECRET, ScopeInterface::SCOPE_STORE);

        return $stored === '' ? '' : trim((string) $this->encryptor->decrypt($stored));
    }

    /**
     * @return array|null keys: id, name, email (null when guest)
     */
    private function getCustomerData(): ?array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return null;
        }
        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return null;
        }

        return [
            'id' => (int) $customer->getId(),
            'name' => trim((string) $customer->getFirstname() . ' ' . (string) $customer->getLastname()),
            'email' => (string) $customer->getEmail(),
        ];
    }

    /**
     * @return array|null keys: total, itemCount (null when no quote exists)
     */
    private function getCartData(): ?array
    {
        try {
            if (!$this->checkoutSession->getQuoteId()) {
                return null;
            }
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            // Quote loading can fail during early checkout states; context data is optional.
            return null;
        }
        if (!$quote || !$quote->getId()) {
            return null;
        }

        return [
            'total' => (float) $quote->getGrandTotal(),
            'itemCount' => (int) $quote->getItemsQty(),
        ];
    }
}
