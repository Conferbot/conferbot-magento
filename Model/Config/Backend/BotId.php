<?php
/**
 * Backend model for the Chatbot ID admin field.
 *
 * Merchants paste anything from a bare ID to the full embed snippet or a
 * dashboard URL; the 24-hex bot ID is extracted before save. When text was
 * entered but no ID could be found, save is rejected with a friendly message.
 *
 * @author    Conferbot <support@conferbot.com>
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Conferbot\Chat\Model\Config\Backend;

use Conferbot\Chat\Model\Loader;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class BotId extends Value
{
    /**
     * Extract the 24-hex bot ID from whatever was pasted.
     *
     * @return $this
     * @throws LocalizedException when text was entered but no ID was found
     */
    public function beforeSave()
    {
        $pasted = (string) $this->getValue();

        if (trim($pasted) === '') {
            $this->setValue('');

            return parent::beforeSave();
        }

        $botId = Loader::extractBotId($pasted);

        if ($botId === '') {
            throw new LocalizedException(__(
                'We could not find a Chatbot ID in what you pasted. It looks like a 24-character code, e.g. 64a1b2c3d4e5f6a7b8c9d0e1. In your Conferbot dashboard, open your bot, click "Share", then "Copy Bot ID".'
            ));
        }

        $this->setValue($botId);

        return parent::beforeSave();
    }
}
