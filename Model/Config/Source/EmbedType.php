<?php
/**
 * Source model for the embed type admin field.
 *
 * @author    Conferbot <support@conferbot.com>
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class EmbedType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'live_chat', 'label' => __('Floating chat bubble (recommended)')],
            ['value' => 'popup_chat', 'label' => __('Popup window')],
        ];
    }
}
