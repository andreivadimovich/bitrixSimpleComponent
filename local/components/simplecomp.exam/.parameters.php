<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
    Bitrix\Main\Localization\Loc as Loc;

Loc::loadMessages(__FILE__);
try {
    $arComponentParameters = [
        'PARAMETERS' => [
            'CATALOG_ID' => [
                'NAME' => Loc::getMessage('CATALOG_ID'),
                'TYPE' => 'STRING',
            ],
            'NEWS_ID' => [
                'NAME' => Loc::getMessage('NEWS_ID'),
                'TYPE' => 'STRING',
            ],
            'UF_CODE' => [
                'NAME' => Loc::getMessage('UF_CODE'),
                'TYPE' => 'STRING',
                'DEFAULT' => 'UF_NEWS_LINK'
            ],
            'CACHE_TIME' => [
                'DEFAULT' => 3600
            ]
        ]
    ];

} catch (Main\LoaderException $e) {
    ShowError($e->getMessage());
}