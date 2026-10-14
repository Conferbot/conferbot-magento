<?php
/**
 * The order on the checkout success page (not cached), as a purchase event
 * for the widget: Conferbot credits a sale to the chat that led to it.
 *
 * @author    Conferbot <support@conferbot.com>
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\ViewModel;

use Conferbot\Chat\Model\Loader;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class OrderEvents implements ArgumentInterface
{
    /** @var CheckoutSession */
    private $checkoutSession;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public function getEventsJson(): string
    {
        $order = null;
        try {
            $last = $this->checkoutSession->getLastRealOrder();
            if ($last && $last->getId()) {
                $order = [
                    'id' => (int) $last->getId(),
                    'incrementId' => (string) $last->getIncrementId(),
                    'total' => (float) $last->getGrandTotal(),
                    'currency' => (string) $last->getOrderCurrencyCode(),
                ];
            }
        } catch (\Exception $e) {
            $order = null;
        }

        return Loader::toJson(Loader::events($order));
    }
}
