<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc as Loc;

Loc::loadMessages(__FILE__);

if (!isset($arResult['items']) || empty($arResult['items'])) {
    echo $arResult['error'];
} else {
    foreach ($arResult['items'] as $title => $product) {
        echo "<div><b>{$title}</b> - {$product['date']} ({$product['categoryList']}) <br/>";
        foreach ($product['productList'] as $key => $item) {
            echo "{$item['NAME']} - {$item['priceValue']}, {$item['materialValue']}, {$item['vendorCodeValue']}<br />";
        }
        echo "</div><hr/>";
    }
}