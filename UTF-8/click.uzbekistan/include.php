<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

// Validate commerce modules exists.
try {
    if (! Loader::includeModule('sale') || ! Loader::includeModule('catalog')) {
        return;
    }
} catch (LoaderException $e) {
    return;
}

// Get version.
include 'install/version.php';

/** @var string CLIENT_NAME The client name. */
if (! defined('CLIENT_NAME')) {
    define('CLIENT_NAME', '1C Bitrix');
}

/** @var string CLIENT_VERSION The client version. */
if (! defined('CLIENT_VERSION')) {
    define('CLIENT_VERSION', $arModuleVersion['VERSION']);
}

unset($arModuleVersion);
