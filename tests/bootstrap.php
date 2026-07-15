<?php
/**
 * Plain PHPUnit bootstrap - no Magento installation required.
 *
 * Model/Loader.php is deliberately dependency-free (pure static class,
 * no Magento imports), so it loads standalone.
 */
declare(strict_types=1);

define('CONFERBOT_TEST_SUITE', true);

require_once dirname(__DIR__) . '/Model/Loader.php';
