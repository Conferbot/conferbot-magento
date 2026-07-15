<?php
/**
 * Conferbot - AI Chatbot, Live Chat & Customer Support for Magento 2.
 *
 * @author    Conferbot <support@conferbot.com>
 * @copyright 2023-2026 Conferbot
 * @license   Open Software License (OSL 3.0)
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Conferbot_Chat',
    __DIR__
);
