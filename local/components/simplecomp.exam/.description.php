<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc as Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('NAME'),
    'DESCRIPTION' => Loc::getMessage('DESCRIPTION'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'simplecomp.exam',
        'NAME' => Loc::getMessage('GROUP'),
    ],
];