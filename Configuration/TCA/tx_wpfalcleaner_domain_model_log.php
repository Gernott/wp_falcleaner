<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:wp_falcleaner/Resources/Private/Language/locallang_db.xlf:tx_wpfalcleaner_domain_model_log',
        'label' => 'filename',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'enablecolumns' => [
        ],
        'searchFields' => 'filename',
        'iconfile' => 'EXT:wp_falcleaner/Resources/Public/Icons/tx_wpfalcleaner_domain_model_log.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'filename, reason',
    ],
    'types' => [
        '1' => ['showitem' => 'filename, reason'],
    ],
    'columns' => [

        'filename' => [
            'exclude' => false,
            'label' => 'LLL:EXT:wp_falcleaner/Resources/Private/Language/locallang_db.xlf:tx_wpfalcleaner_domain_model_log.filename',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'reason' => [
            'exclude' => false,
            'label' => 'LLL:EXT:wp_falcleaner/Resources/Private/Language/locallang_db.xlf:tx_wpfalcleaner_domain_model_log.reason',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-- Label --', 0],
                ],
                'size' => 1,
                'maxitems' => 1,
                'eval' => 'required'
            ],
        ],
    
    ],
];
