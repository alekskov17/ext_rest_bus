<?php
namespace Krayt\Apprest;

class RestMobile
{
    public static function OnRestServiceBuildDescription()
    {
        return array(
            'kraytapprest' => array(
                'kraytapprest.sites' => array(
                    'callback' => array(__CLASS__, 'sites'),
                    'options' => array(),
                ),
                'kraytapprest.check' => array(
                    'callback' => array(__CLASS__, 'check'),
                    'options' => array(),
                ),
                'kraytapprest.sections' => array(
                    'callback' => array(__CLASS__, 'sections'),
                    'options' => array(),
                ),
                'kraytapprest.catalog' => array(
                    'callback' => array(__CLASS__, 'catalog'),
                    'options' => array(),
                ),
                'kraytapprest.filter' => array(
                    'callback' => array(__CLASS__, 'filter'),
                    'options' => array(),
                ),
            )
        );
    }
    public static function sites($query, $n, \CRestServer $server)
    {
        if($query['error'])
        {
            throw new \Bitrix\Rest\RestException(
                'Message',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        $sites = \Bitrix\Main\SiteTable::getList([
            'select'=> ['LID',"NAME"],
            'filter' => ['ACTIVE' => "Y"]
        ])->fetchAll();

        return array('sites' => $sites);
    }
    public static function check($query, $n, \CRestServer $server)
    {
        if($query['error'])
        {
            throw new \Bitrix\Rest\RestException(
                'Message',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }

        return array('check' => true);
    }
    public  static function sections($query, $n, \CRestServer $server){
        if($query['error'])
        {
            throw new \Bitrix\Rest\RestException(
                'Message',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['iblockId']){
            throw new \Bitrix\Rest\RestException(
                'Not params iblockId',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        $sectionParent = 0;
        if($query['iblockSectionId'] > 0){
            $sectionParent = $query['iblockSectionId'];
        }
        $ids = [];
        $arSections = [];
        $sections = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID',"NAME","IBLOCK_SECTION_ID"],
            'filter' => [
                'ACTIVE' => "Y",
                "IBLOCK_ID" => $query['iblockId'],
                "IBLOCK_SECTION_ID" => $sectionParent
            ],
            'order'=>['SORT'=>"ASC"]
        ]);
        while($section = $sections->fetch()){
            $ids[] = $section['ID'];
            $arSections[] = [
                'name' => $section['NAME'],
                'id' => $section['ID'],
                'isParent' => false,
                'path' => "/sections/{$section['ID']}"
            ];
        }
        $sectionsChild = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID',"NAME","IBLOCK_SECTION_ID"],
            'filter' => [
                'ACTIVE' => "Y",
                "IBLOCK_ID" => $query['iblockId'],
                "IBLOCK_SECTION_ID" => $ids
            ],
            'order'=>['SORT'=>"ASC"]
        ]);
        if($sectionsChild->getSelectedRowsCount()){
            while ($child = $sectionsChild->fetch()){
                foreach ($arSections as &$s){
                    if($s['id'] == $child['IBLOCK_SECTION_ID']){
                        $s['isParent'] = true;
                    }
                }
            }
        }

        unset($ids,$sectionsChild);
        return array("itemsLetfMenu"=>$arSections);
    }
    public static function catalog($query, $n, \CRestServer $server){

        $page = 1;
        $product_perpage = 10;
        $sortName = 'sort';
        $sortOrder = "asc";
        if($query['error'])
        {
            throw new \Bitrix\Rest\RestException(
                'Message',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['lid']){
            throw new \Bitrix\Rest\RestException(
                'Not params lid',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['iblockId']){
            throw new \Bitrix\Rest\RestException(
                'Not params iblockId',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['sectionId']){
            throw new \Bitrix\Rest\RestException(
                'Not params sectionId',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['priceCode']){
            throw new \Bitrix\Rest\RestException(
                'Not params priceCode',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if($query['page'] > 0){
           $page = $query['page'];
        }
        if($query['cntElements'] > 0){
            $product_perpage = $query['cntElements'];
        }
        if($query['sort']){
            $sortName = $query['sort'];
        }
        if($query['order']){
            $sortOrder = $query['order'];
        }
        global $APPLICATION;
        $sectionRes = [];
        $sections = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID',"NAME","IBLOCK_SECTION_ID"],
            'filter' => [
                'ACTIVE' => "Y",
                "IBLOCK_ID" => $query['iblockId'],
                "ID" => $query['sectionId']
            ],
            'order'=>['SORT'=>"ASC"]
        ]);
        while($section = $sections->fetch()){
            $sectionRes = [
                'name' => $section['NAME'],
                'id' => $section['ID'],
                'isParent' => false,
                'path' => "/sections/{$section['ID']}"
            ];
        }
        $site = \Bitrix\Main\SiteTable::getById($query['lid'])->fetch();
        if(empty($site['SERVER_NAME'])){
            throw new \Bitrix\Rest\RestException(
                "Site {$query['lid']} SERVER_NAME empty",
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }

        if($query['filter'] && is_array($query['filter'])){
            foreach ($query['filter'] as $item){
                $_GET[$item['name']] = $item['val'];
            }
            $_GET['set_filter'] = "Y";
        }

        if($query['filter']){
            $arParams = [
                "IBLOCK_ID" => $query['iblockId'],
                "SECTION_ID" => $query['sectionId'],
                "PRICE_CODE" => [$query['priceCode']],
                "FILTER_NAME" => "FLITERCATALOG",
                "CACHE_TYPE" => "N",
                "CACHE_TIME" => 36000,
                "CACHE_GROUPS" => "N",
                "HIDE_NOT_AVAILABLE" => "Y",
                "PAGER_PARAMS_NAME" => 'action'
            ];

            $APPLICATION->IncludeComponent(
                "bitrix:catalog.smart.filter",
                "json",
                array(
                    "SEF_MODE" => "N",
                    "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                    "SECTION_ID" => $arParams['SECTION_ID'],
                    "FILTER_NAME" => $arParams["FILTER_NAME"],
                    "PRICE_CODE" => $arParams["PRICE_CODE"],
                    "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                    "CACHE_TIME" => $arParams["CACHE_TIME"],
                    "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                    "SAVE_IN_SESSION" => "N",
                    "XML_EXPORT" => "N",
                    'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
                    "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
                    "DISPLAY_ELEMENT_COUNT" => "Y",

                ),
                false,
                array('HIDE_ICONS' => 'Y')
            );
        }

        $arParams = [
            "SERVER_NAME" => $site['SERVER_NAME'],
            'IBLOCK_ID' => $query['iblockId'],
            "FILTER_NAME" => 'FLITERCATALOG',
            "PRICE_CODE" => [$query['priceCode']],
            "ADD_PICT_PROP" => $query['catalogPropPict'],
            "OFFER_ADD_PICT_PROP" => $query['offersPropPict'],
            "PRICE_VAT_INCLUDE" => "N",
            "CACHE_TYPE" => "N",
            "CACHE_TIME" => 36000,
            "CACHE_FILTER" => "Y",
            "CACHE_GROUPS" => "N",
            "USE_PRICE_COUNT" => "Y",
            "SHOW_PRICE_COUNT" => 1,
            "HIDE_NOT_AVAILABLE" => "N",
            "HIDE_NOT_AVAILABLE_OFFERS" => "N",
            'LIST_SHOW_SLIDER' => "Y",
            "OFFER_TREE_PROPS" => ""
        ];

        $GLOBALS['PAGEN_1'] = $page;

        $APPLICATION->IncludeComponent(
            "bitrix:catalog.section",
            "json",
            array(
                "ELEMENT_SORT_FIELD" => $sortName,
                "ELEMENT_SORT_ORDER" => $sortOrder,
                "ACTION_VARIABLE" => "action",
                "PAGER_BASE_LINK_ENABLE"=>"Y",
                "DISPLAY_TOP_PAGER" => "Y",
                "LAZY_LOAD" => "Y",
                "SEF_MODE" => "N",
                "SERVER_NAME" => $arParams['SERVER_NAME'],
                "SHOW_ALL_WO_SECTION" => "Y",
                "IBLOCK_TYPE" => "",
                "IBLOCK_ID" => $arParams['IBLOCK_ID'],
                "PROPERTY_CODE" => [],
                "PROPERTY_CODE_MOBILE" => [],
                "FILTER_NAME" => $arParams["FILTER_NAME"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "CACHE_FILTER" => $arParams["CACHE_FILTER"],
                "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                "PAGE_ELEMENT_COUNT" => $product_perpage,
                "PRICE_CODE" => $arParams['PRICE_CODE'],
                "USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
                "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
                "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
                "PRODUCT_PROPERTIES" => [],
                "OFFERS_LIMIT" => 0,
                "SECTION_ID" => $query['sectionId'],
                'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
                'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
                'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
                'SHOW_SLIDER' => $arParams['LIST_SHOW_SLIDER'],
                'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
                'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
                "USE_OFFER_NAME" => "Y",
                "PRODUCT_DISPLAY_MODE" => "Y",
                "OFFERS_FIELD_CODE" => ['NAME',"PREVIEW_PICTURE","DETAIL_PICTURE"]

            ),
            false,
            ["HIDE_ICONS"=>"Y"]
        );

        return ["section"=>$sectionRes,'products' => $GLOBALS['ITEMS'] ?? [], 'nav' => $GLOBALS['NAV']];
    }

    public static  function filter($query, $n, \CRestServer $server)
    {
        global  $APPLICATION;
        if(!$query['lid']){
            throw new \Bitrix\Rest\RestException(
                'Not params lid',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['iblockId']){
            throw new \Bitrix\Rest\RestException(
                'Not params iblockId',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['sectionId']){
            throw new \Bitrix\Rest\RestException(
                'Not params sectionId',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if(!$query['priceCode']){
            throw new \Bitrix\Rest\RestException(
                'Not params priceCode',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        if($query['filter'] && is_array($query['filter'])){
            foreach ($query['filter'] as $item){
                $_GET[$item['name']] = $item['val'];
            }
            $_GET['set_filter'] = "Y";
           
        }

        $arParams = [
            "IBLOCK_ID" => $query['iblockId'],
            "SECTION_ID" => $query['sectionId'],
            "PRICE_CODE" => [$query['priceCode']],
            "FILTER_NAME" => "FLITERCATALOG",
            "CACHE_TYPE" => "N",
            "CACHE_TIME" => 36000,
            "CACHE_GROUPS" => "N",
            "HIDE_NOT_AVAILABLE" => "Y",
            "PAGER_PARAMS_NAME" => 'action'
        ];

        $APPLICATION->IncludeComponent(
            "bitrix:catalog.smart.filter",
            "json",
            array(
                "SEF_MODE" => "N",
                "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                "SECTION_ID" => $arParams['SECTION_ID'],
                "FILTER_NAME" => $arParams["FILTER_NAME"],
                "PRICE_CODE" => $arParams["PRICE_CODE"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                "SAVE_IN_SESSION" => "N",
                "XML_EXPORT" => "N",
                'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
                "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
                "DISPLAY_ELEMENT_COUNT" => "Y",

            ),
            false,
            array('HIDE_ICONS' => 'Y')
        );
        return ['items' => $GLOBALS['FILTER_ITEMS'],'price' => $GLOBALS['PRICE']];
    }
}