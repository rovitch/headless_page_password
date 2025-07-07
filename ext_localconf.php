<?php
defined('TYPO3') || die();

call_user_func(
    function ($extensionKey) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['headlesspagepassword'] = [
            'Rovitch\HeadlessPagePassword\ViewHelpers'
        ];
    },
    'headless_page_password'
);
