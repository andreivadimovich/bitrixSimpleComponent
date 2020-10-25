<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("");
?><?$APPLICATION->IncludeComponent(
	"simplecomp.exam",
	"",
	Array(
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "Y",
		"CATALOG_ID" => "",
		"NEWS_ID" => "",
		"UF_CODE" => "UF_NEWS_LINK"
	)
);?><?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>