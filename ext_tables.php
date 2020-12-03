<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        if (TYPO3_MODE === 'BE') {

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'WpFalcleaner',
                'tools', // Make module a submodule of 'tools'
                'falcleaner', // Submodule key
                '', // Position
                [
                    \WEBprofil\WpFalcleaner\Controller\CleanController::class => 'list, duplicate, duplicateDelete',
                    \WEBprofil\WpFalcleaner\Controller\LogController::class => 'list',
                ],
                [
                    'access' => 'user,group',
                    'icon'   => 'EXT:wp_falcleaner/Resources/Public/Icons/user_mod_falcleaner.svg',
                    'labels' => 'LLL:EXT:wp_falcleaner/Resources/Private/Language/locallang_falcleaner.xlf',
                ]
            );

        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('wp_falcleaner', 'Configuration/TypoScript', 'FAL cleaner');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_wpfalcleaner_domain_model_log', 'EXT:wp_falcleaner/Resources/Private/Language/locallang_csh_tx_wpfalcleaner_domain_model_log.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_wpfalcleaner_domain_model_log');

    }
);
## EXTENSION BUILDER DEFAULTS END TOKEN - Everything BEFORE this line is overwritten with the defaults of the extension builder
