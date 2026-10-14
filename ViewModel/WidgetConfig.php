<?php
/**
 * View model for the widget loader template.
 *
 * The loader sits on every page, and most pages are served from the full
 * page cache, shared by every visitor. So the page carries only the store's
 * context; the logged-in customer (signed) and the cart come per visitor
 * from the "conferbot" customer-data section (CustomerData\Conferbot), which
 * Magento loads privately and refreshes on cart and login changes.
 *
 * @author    Conferbot <support@conferbot.com>
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\ViewModel;

use Conferbot\Chat\Model\Loader;
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

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ResolverInterface */
    private $localeResolver;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver
    ) {
        $this->scopeConfig = $scopeConfig;
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
     * Widget options JSON (store context only - cache-safe), safe to inline inside a <script> block.
     */
    public function getOptionsJson(): string
    {
        return Loader::toJson(Loader::buildOptions($this->getStoreData()));
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
}
