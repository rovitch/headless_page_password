<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

call_user_func(function () {
    ExtensionManagementUtility::addStaticFile(
        'headless_page_password',
        'Configuration/TypoScript',
        'Headless Page Password'
    );
});
