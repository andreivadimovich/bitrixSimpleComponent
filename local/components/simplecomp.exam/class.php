<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\Type\DateTime,
    Bitrix\Iblock\ElementTable,
    \Bitrix\Iblock\Elements\ElementCatalogTable,
    \Bitrix\Iblock\Model\Section,
    Bitrix\Main\Data\Cache;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Компонент для связи новостей и разделов каталога товаров.
 */
class SimpleCatalogComponent extends CBitrixComponent
{
    /**
     * Получение параметров настройки компонента
     * @param array $params
     * @return array
     */
    public function onPrepareComponentParams(array $params): array
    {
        $params = array_map('trim', $params);
        return [
            'CATALOG_ID' => $params['CATALOG_ID'],
            'NEWS_ID' => $params['NEWS_ID'],
            'UF_CODE' => $params['UF_CODE'],
            'CACHE_TIME' => $params['CACHE_TIME'],
            'CACHE_TYPE' => $params['CACHE_TYPE'],
        ];
    }

    public function executeComponent(): void
    {
        global $APPLICATION;
        try {
            if ($this->arParams['CACHE_TYPE'] !== 'N') {
                $this->cacheResult($APPLICATION->GetCurPage());
            } else {
                $this->arResult = $this->getResult();
            }

            $this->includeComponentTemplate();
            $APPLICATION->SetTitle(Loc::getMessage('PAGE_TITLE', ['counter' => $this->arResult['count']]));

        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
    }

    /**
     * @return array
     */
    protected function getResult(): array
    {
        try {
            $ufCode = $this->arParams['UF_CODE'];

            $news = self::getNews($this->arParams['NEWS_ID']);
            if (empty($news)) {
                throw new Exception(Loc::getMessage('NEWS_IS_EMPTY'));
            }

            $categoryList = self::getCategoryList($this->arParams['CATALOG_ID'], $ufCode);
            if (empty($categoryList)) {
                throw new Exception(Loc::getMessage('CATEGORY_LIST_EMPTY'));
            }

            $productList = self::getCatalogProducts($this->arParams['CATALOG_ID'], $categoryList, $ufCode);
            if (empty($productList)) {
                throw new Exception(Loc::getMessage('PRODUCT_IS_EMPTY'));
            }

            return self::linkCatalogToNews($news, $productList, $ufCode);

        } catch (Exception $e) {
            ShowError($e->getMessage(), __CLASS__);
        }
    }

    /**
     * Получение списка новостей
     * @param int $iblockId
     * @return array
     */
    protected static function getNews(int $iblockId): array
    {
        try {
            return ElementTable::getList([
                'select' => ['ID', 'NAME', 'TIMESTAMP_X'],
                'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y']
            ])->fetchAll();

        } catch (Exception $e) {
            ShowError($e->getMessage(), __CLASS__);
        }
    }

    /**
     * Разделы каталога товаров
     * @param int $iblockId
     * @param string $ufCode
     * @return array
     */
    protected static function getCategoryList(int $iblockId, string $ufCode): array
    {
        try {
            $entity = Section::compileEntityByIblock($iblockId);
            return $entity::getList([
                'select' => ['NAME', $ufCode, 'LEFT_MARGIN', 'RIGHT_MARGIN'],
                'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
            ])->fetchAll();

        } catch (Exception $e) {
            ShowError($e->getMessage(), __CLASS__);
        }
    }

    /**
     * Разделы каталога с товарами
     * @param int $iblockId
     * @param array $categoryList
     * @param string $ufCode
     * @return array
     */
    protected static function getCatalogProducts(int $iblockId, array $categoryList, string $ufCode): array
    {
        try {
            if (count($categoryList) == 0 || empty($iblockId)) {
                throw new Exception(Loc::getMessage('CATEGORY_LIST_EMPTY'));
            }

            $result = [];
            $result['count'] = 0;
            foreach ($categoryList as $category) {
                $productList = ElementCatalogTable::getList([
                    'select' => ['NAME', 'priceValue' => 'price.VALUE', 'vendorCodeValue' => 'vendorCode.VALUE',
                        'materialValue' => 'material.VALUE'],
                    'filter' => [
                        'IBLOCK_ID' => $iblockId,
                        '>=IBLOCK_SECTION.LEFT_MARGIN' => $category['LEFT_MARGIN'],
                        '<=IBLOCK_SECTION.RIGHT_MARGIN' => $category['RIGHT_MARGIN'],
                        'ACTIVE' => 'Y'
                    ],
                ]);

                $result[] = [
                    $ufCode => $category[$ufCode],
                    'category' => $category['NAME'],
                    'productList' => $productList->fetchAll()
                ];
                $result['count'] += $productList->getSelectedRowsCount();
            }

            return $result;

        } catch (Exception $e) {
            ShowError($e->getMessage(), __CLASS__);
        }
    }

    /**
     * Привязка товаров и категорий к новостям
     * @param array $news
     * @param array $productList
     * @param string $ufCode
     * @return array
     */
    protected static function linkCatalogToNews(array $news, array $productList, string $ufCode): array
    {
        $result = [];
        $catalogNews = [];
        $productItems = [];
        foreach ($news as $item) {
            $title = $item['NAME'];
            $date = new DateTime($item['TIMESTAMP_X']);

            foreach ($productList as $product) {
                if (in_array($item['ID'], $product[$ufCode])) {
                    $productItems = array_merge($product['productList'], $productItems);
                    $catalogNews[$title]['category'][] = $product['category'];
                }
            }
            $catalogNews[$title]['date'] = $date->format("d.m.Y");
            $catalogNews[$title]['categoryList'] = implode(',', $catalogNews[$title]['category']);
            $catalogNews[$title]['productList'] = $productItems;
            $productItems = [];
        }

        $result['count'] = $productList['count'];
        if (count($catalogNews) == 0) {
            $result['error'] = Loc::getMessage('EMPTY_RESULT');
        } else {
            $result['items'] = $catalogNews;
        }

        return $result;
    }

    /**
     * Кеширование arResult
     * @param string $currentPage
     */
    private function cacheResult(string $currentPage): void
    {
        $cache = Cache::createInstance();
        $cacheTime = intval($this->arParams['CACHE_TIME']);
        $cacheId = implode('|', [SITE_ID, $currentPage]);
        $cacheDir = '/' . SITE_ID . $this->GetRelativePath();

        if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {
            $this->arResult = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $this->arResult = $this->getResult();
            $cache->endDataCache($this->arResult);
        }
    }
}