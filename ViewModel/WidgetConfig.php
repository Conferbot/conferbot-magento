<?php
/**
 * View model for the widget loader template.
 *
 * Reads the module configuration, the logged-in customer (customer session)
 * and the active quote (checkout session), and hands the template everything
 * it needs: bot ID, embed type, script URL and the pre-encoded options JSON.
 * All option building/encoding is delegated to the pure Loader class.
 *
 * @author    Conferbot <support@conferbot.com>
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\ViewModel;

use Conferbot\Chat\Model\Loader;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class WidgetConfig implements ArgumentInterface
{
    private const XML_PATH_ENABLED = 'conferbot/general/enabled';
    private const XML_PATH_BOT_ID = 'conferbot/general/bot_id';
    private const XML_PATH_EMBED_TYPE = 'conferbot/general/embed_type';
    private const XML_PATH_SCRIPT_URL = 'conferbot/general/script_url';
    private const XML_PATH_STORE_NAME = 'general/store_information/name';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var CustomerSession */
    private $customerSession;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ResolverInterface */
    private $localeResolver;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getBotId(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_BOT_ID, ScopeInterface::SCOPE_STORE));
    }

    /**
     * True only when the widget should actually render: enabled + strictly valid bot ID.
     */
    public function shouldRender(): bool
    {
        return $this->isEnabled() && Loader::isValidBotId($this->getBotId());
    }

    public function getEmbedType(): string
    {
        $embedType = (string) $this->scopeConfig->getValue(self::XML_PATH_EMBED_TYPE, ScopeInterface::SCOPE_STORE);

        return in_array($embedType, ['live_chat', 'popup_chat'], true) ? $embedType : 'live_chat';
    }

    public function getScriptUrl(): string
    {
        $url = trim((string) $this->scopeConfig->getValue(self::XML_PATH_SCRIPT_URL, ScopeInterface::SCOPE_STORE));

        return $url !== '' ? $url : Loader::DEFAULT_SCRIPT_URL;
    }

    /**
     * Widget options JSON, safe to inline inside a <script> block.
     */
    public function getOptionsJson(): string
    {
        return Loader::toJson(Loader::buildOptions(
            $this->getStoreData(),
            $this->getCustomerData(),
            $this->getCartData()
        ));
    }

    /**
     * @return array keys: name, url, language, currency
     */
    private function getStoreData(): array
    {
        try {
            $store = $this->storeManager->getStore();
        } catch (\Exception $e) {
            return ['name' => '', 'url' => '', 'language' => '', 'currency' => ''];
        }

        $storeName = (string) $this->scopeConfig->getValue(self::XML_PATH_STORE_NAME, ScopeInterface::SCOPE_STORE);
        if ($storeName === '') {
            $storeName = (string) $store->getName();
        }

        return [
            'name' => $storeName,
            'url' => (string) $store->getBaseUrl(UrlInterface::URL_TYPE_WEB),
            'language' => (string) $this->localeResolver->getLocale(),
            'currency' => (string) $store->getCurrentCurrencyCode(),
        ];
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
