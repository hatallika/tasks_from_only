<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Context,
    Bitrix\Main\Loader,
    Bitrix\Iblock;
use Bitrix\Main\SystemException;


class CMyDevNewsList extends CBitrixComponent
{
    private mixed $arrFilter;
    private bool $bUSER_HAVE_ACCESS;
    private array $arNavigation;
    /**
     * @var array|mixed
     */
    private mixed $pagerParameters;
    private array $arFilter;
    private array $arNavParams;

    public function onPrepareComponentParams($arParams): array
    {
        global $DB;
        global $USER;
        if (!isset($arParams["CACHE_TIME"])) {
            $arParams["CACHE_TIME"] = 36000000;
        }

        $arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"] ?? '');
        if (empty($arParams["IBLOCK_TYPE"])) {
            $arParams["IBLOCK_TYPE"] = "news";
        }
        $arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"] ?? '');
        $arParams["PARENT_SECTION"] = (int)($arParams["PARENT_SECTION"] ?? 0);
        $arParams["PARENT_SECTION_CODE"] ??= '';
        $arParams["INCLUDE_SUBSECTIONS"] = ($arParams["INCLUDE_SUBSECTIONS"] ?? '') !== "N";
        $arParams["SET_LAST_MODIFIED"] = ($arParams["SET_LAST_MODIFIED"] ?? '') === "Y";
        //Первая сортировка
        $arParams["SORT_BY1"] = trim($arParams["SORT_BY1"] ?? '');
        if (empty($arParams["SORT_BY1"])) {
            $arParams["SORT_BY1"] = "ACTIVE_FROM";
        }
        if (
            !isset($arParams["SORT_ORDER1"])
            || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER1"])
        ) {
            $arParams["SORT_ORDER1"] = "DESC";
        }
        //Вторая сортировка
        $arParams["SORT_BY2"] = trim($arParams["SORT_BY2"] ?? '');
        if (empty($arParams["SORT_BY2"])) {
            if (mb_strtoupper($arParams["SORT_BY1"]) === 'SORT') {
                $arParams["SORT_BY2"] = "ID";
                $arParams["SORT_ORDER2"] = "DESC";
            } else {
                $arParams["SORT_BY2"] = "SORT";
            }
        }
        if (
            !isset($arParams["SORT_ORDER2"])
            || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER2"])
        ) {
            $arParams["SORT_ORDER2"] = "ASC";
        }
        //Фильтр
        $this->arrFilter = [];
        if (!empty($arParams["FILTER_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])) {
            $this->arrFilter = $GLOBALS[$arParams["FILTER_NAME"]] ?? [];
            if (!is_array($this->arrFilter)) {
                $this->arrFilter = [];
            }
        }

        $arParams["CHECK_DATES"] = ($arParams["CHECK_DATES"] ?? '') !== "N";

        //Пользовательские поля инфоблока - массив
        if (empty($arParams["FIELD_CODE"]) || !is_array($arParams["FIELD_CODE"])) {
            $arParams["FIELD_CODE"] = [];
        }

        foreach ($arParams["FIELD_CODE"] as $key => $val) {
            if (!$val) {
                unset($arParams["FIELD_CODE"][$key]);
            }
        }
        //Свойства инфоблока - массив
        if (empty($arParams["PROPERTY_CODE"]) || !is_array($arParams["PROPERTY_CODE"])) {
            $arParams["PROPERTY_CODE"] = array();
        }
        foreach ($arParams["PROPERTY_CODE"] as $key => $val) {
            if ($val === "") {
                unset($arParams["PROPERTY_CODE"][$key]);
            }
        }
        //URL страницы детального просмотра (по умолчанию - из настроек инфоблока)
        $arParams["DETAIL_URL"] = trim($arParams["DETAIL_URL"] ?? '');
        $arParams["SECTION_URL"] = trim($arParams["SECTION_URL"] ?? '');
        $arParams["IBLOCK_URL"] = trim($arParams["IBLOCK_URL"] ?? '');

        //Количество элементов на странице по умолчанию
        $arParams["NEWS_COUNT"] = (int)($arParams["NEWS_COUNT"] ?? 0);
        if ($arParams["NEWS_COUNT"] <= 0) {
            $arParams["NEWS_COUNT"] = 20;
        }

        //Кешировать при фильтре
        $arParams["CACHE_FILTER"] = ($arParams["CACHE_FILTER"] ?? '') === "Y";
        if (!$arParams["CACHE_FILTER"] && !empty($arrFilter)) {
            $arParams["CACHE_TIME"] = 0;
        }

        //
        $arParams["SET_TITLE"] = ($arParams["SET_TITLE"] ?? '') !== "N";
        $arParams["SET_BROWSER_TITLE"] = ($arParams["SET_BROWSER_TITLE"] ?? '') === 'N' ? 'N' : 'Y';
        $arParams["SET_META_KEYWORDS"] = ($arParams["SET_META_KEYWORDS"] ?? '') === 'N' ? 'N' : 'Y';
        $arParams["SET_META_DESCRIPTION"] = ($arParams["SET_META_DESCRIPTION"] ?? '') === 'N' ? 'N' : 'Y';
        $arParams["ADD_SECTIONS_CHAIN"] = ($arParams["ADD_SECTIONS_CHAIN"] ?? '') !== "N"; //Turn on by default
        $arParams["INCLUDE_IBLOCK_INTO_CHAIN"] = ($arParams["INCLUDE_IBLOCK_INTO_CHAIN"] ?? '') !== "N";
        $arParams["STRICT_SECTION_CHECK"] = ($arParams["STRICT_SECTION_CHECK"] ?? '') === "Y";
        $arParams["ACTIVE_DATE_FORMAT"] = trim($arParams["ACTIVE_DATE_FORMAT"] ?? '');
        if (empty($arParams["ACTIVE_DATE_FORMAT"])) {
            $arParams["ACTIVE_DATE_FORMAT"] = $DB->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));
        }
        $arParams["PREVIEW_TRUNCATE_LEN"] = (int)($arParams["PREVIEW_TRUNCATE_LEN"] ?? 0);
        $arParams["HIDE_LINK_WHEN_NO_DETAIL"] = ($arParams["HIDE_LINK_WHEN_NO_DETAIL"] ?? '') === "Y";

        $arParams["DISPLAY_TOP_PAGER"] = ($arParams["DISPLAY_TOP_PAGER"] ?? '') === "Y";
        $arParams["DISPLAY_BOTTOM_PAGER"] = ($arParams["DISPLAY_BOTTOM_PAGER"] ?? '') !== "N";
        $arParams["PAGER_TITLE"] = trim($arParams["PAGER_TITLE"] ?? '');
        $arParams["PAGER_SHOW_ALWAYS"] = ($arParams["PAGER_SHOW_ALWAYS"] ?? '') === "Y";
        $arParams["PAGER_TEMPLATE"] = trim($arParams["PAGER_TEMPLATE"] ?? '');
        $arParams["PAGER_DESC_NUMBERING"] = ($arParams["PAGER_DESC_NUMBERING"] ?? '') === "Y";
        $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] = (int)($arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] ?? 0);
        $arParams["PAGER_SHOW_ALL"] = ($arParams["PAGER_SHOW_ALL"] ?? '') === "Y";
        $arParams["PAGER_BASE_LINK_ENABLE"] ??= 'N';
        $arParams["PAGER_BASE_LINK"] ??= '';
        $arParams["INTRANET_TOOLBAR"] ??= '';
        $arParams["CHECK_PERMISSIONS"] = ($arParams["CHECK_PERMISSIONS"] ?? '') !== "N";
        $arParams["MESSAGE_404"] ??= '';
        $arParams["SET_STATUS_404"] ??= 'N';
        $arParams["SHOW_404"] ??= 'N';
        $arParams["FILE_404"] ??= '';

        //Навигация. Массив параметр - значеие
        if ($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"]) {
            $this->arNavParams = array(
                "nPageSize" => $arParams["NEWS_COUNT"],
                "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
                "bShowAll" => $arParams["PAGER_SHOW_ALL"],
            );
            $this->arNavigation = CDBResult::GetNavParams( $this->arNavParams);
            if ((int)$this->arNavigation["PAGEN"] === 0 && $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] > 0) {
                $arParams["CACHE_TIME"] = $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
            }
        } else {
            $this->arNavParams = array(
                "nTopCount" => $arParams["NEWS_COUNT"],
                "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
            );
            $this->arNavigation = false;
        }
        //Параметр отсутствует по умолчанию
        //Внешний массив с переменными для построения ссылок в постраничной навигации
        $this->pagerParameters = [];
        if (!empty($arParams["PAGER_PARAMS_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PAGER_PARAMS_NAME"])) {
            $this->pagerParameters = $GLOBALS[$arParams["PAGER_PARAMS_NAME"]] ?? [];
            if (!is_array($this->pagerParameters)) {
                $this->pagerParameters = array();
            }
        }
        // В составе сложного компонента, проверка доступа
        $arParams["USE_PERMISSIONS"] = ($arParams["USE_PERMISSIONS"] ?? '') === "Y";
        if (!is_array($arParams["GROUP_PERMISSIONS"] ?? null)) {
            $adminGroupCode = 1;
            $arParams["GROUP_PERMISSIONS"] = [$adminGroupCode];
        }

        $this->bUSER_HAVE_ACCESS = !$arParams["USE_PERMISSIONS"];
        if ($arParams["USE_PERMISSIONS"] && isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"])) {
            $arUserGroupArray = $USER->GetUserGroupArray();
            foreach ($arParams["GROUP_PERMISSIONS"] as $PERM) {
                if (in_array($PERM, $arUserGroupArray)) {
                    $this->bUSER_HAVE_ACCESS = true;
                    break;
                }
            }
        }

        $arParams["CACHE_GROUPS"] ??= '';

        return $arParams;
    }


    private function checkModules()
    {
        if (!Loader::includeModule('iblock'))
            throw new SystemException(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
    }

    public function executeComponent()
    {
        try {
            $this->checkModules();
            $this->getResult();
//            $this->includeComponentTemplate();

        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    private function getResult()
    {
        global $USER;
        global $APPLICATION;
        if ($this->startResultCache(false, array(
                ($this->arParams["CACHE_GROUPS"] === "N" ? false : $USER->GetGroups()),
                $this->bUSER_HAVE_ACCESS,
                $this->arNavigation,
                $this->arrFilter,
                $this->pagerParameters)
            )
        )
        {
            // Валидного кеша нет. Выбираем данные из базы в $arResult
            if (!Loader::includeModule("iblock")) {
                $this->abortResultCache();
                ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
                return;
            }
            //Список инфорамационных блоков
            if (is_numeric($this->arParams["IBLOCK_ID"])) {
                $rsIBlock = CIBlock::GetList(array(), array(
                    "ACTIVE" => "Y",
                    "ID" => $this->arParams["IBLOCK_ID"],
                ));
            } else {
                $rsIBlock = CIBlock::GetList(array(), array(
                    "ACTIVE" => "Y",
                    "CODE" => $this->arParams["IBLOCK_ID"],
                    "SITE_ID" => SITE_ID,
                ));
            }
            $arResult = $rsIBlock->GetNext();

            if (!$arResult) {
                $this->abortResultCache();
                Iblock\Component\Tools::process404(
                    trim($this->arParams["MESSAGE_404"]) ?: GetMessage("T_NEWS_NEWS_NA")
                    , true
                    , $this->arParams["SET_STATUS_404"] === "Y"
                    , $this->arParams["SHOW_404"] === "Y"
                    , $this->arParams["FILE_404"]
                );
                return;
            }

            $this->arResult["USER_HAVE_ACCESS"] = $this->bUSER_HAVE_ACCESS;
            //SELECT
            $arSelect = array_merge($this->arParams["FIELD_CODE"], array(
                "ID",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "NAME",
                "ACTIVE_FROM",
                "TIMESTAMP_X",
                "DETAIL_PAGE_URL",
                "LIST_PAGE_URL",
                "DETAIL_TEXT",
                "DETAIL_TEXT_TYPE",
                "PREVIEW_TEXT",
                "PREVIEW_TEXT_TYPE",
                "PREVIEW_PICTURE",
            ));

            $bGetProperty = !empty($arParams["PROPERTY_CODE"]);
            //WHERE
            $this->arFilter = array(
                "IBLOCK_ID" => $this->arResult["ID"],
                "IBLOCK_LID" => SITE_ID,
                "ACTIVE" => "Y",
                "CHECK_PERMISSIONS" => $this->arParams['CHECK_PERMISSIONS'] ? "Y" : "N",
            );
            if ($this->arParams["CHECK_DATES"])
                $this->arFilter["ACTIVE_DATE"] = "Y";

            // Разделы
            $PARENT_SECTION = CIBlockFindTools::GetSectionID(
                $this->arParams["PARENT_SECTION"],
                $this->arParams["PARENT_SECTION_CODE"],
                array(
                    "GLOBAL_ACTIVE" => "Y",
                    "IBLOCK_ID" => $this->arResult["ID"],
                )
            );

            if (
                $this->arParams["STRICT_SECTION_CHECK"]
                && (
                    $this->arParams["PARENT_SECTION"] > 0
                    || $this->arParams["PARENT_SECTION_CODE"] <> ''
                )
            ) {
                if ($PARENT_SECTION <= 0) {
                    $this->abortResultCache();
                    Iblock\Component\Tools::process404(
                        trim($this->arParams["MESSAGE_404"]) ?: GetMessage("T_NEWS_NEWS_NA")
                        , true
                        , $this->arParams["SET_STATUS_404"] === "Y"
                        , $this->arParams["SHOW_404"] === "Y"
                        , $this->arParams["FILE_404"]
                    );
                    return;
                }
            }

            $this->arParams["PARENT_SECTION"] = $PARENT_SECTION;

            //Если задан вывод элементов только с раздела инфоблока
            if ($this->arParams["PARENT_SECTION"] > 0) {
                $this->arFilter["SECTION_ID"] = $this->arParams["PARENT_SECTION"];
                if ($this->arParams["INCLUDE_SUBSECTIONS"])
                    $this->arFilter["INCLUDE_SUBSECTIONS"] = "Y";

                $this->arResult["SECTION"] = array("PATH" => array());
                $rsPath = CIBlockSection::GetNavChain(
                    $this->arResult["ID"],
                    $this->arParams["PARENT_SECTION"],
                    [
                        'ID',
                        'IBLOCK_ID',
                        'NAME',
                        'SECTION_PAGE_URL',
                    ]
                );
                $rsPath->SetUrlTemplates("", $this->arParams["SECTION_URL"], $this->arParams["IBLOCK_URL"]);
                while ($arPath = $rsPath->GetNext()) {
                    $ipropValues = new Iblock\InheritedProperty\SectionValues($this->arParams["IBLOCK_ID"], $arPath["ID"]);
                    $arPath["IPROPERTY_VALUES"] = $ipropValues->getValues();
                    $this->arResult["SECTION"]["PATH"][] = $arPath;
                }
                unset($arPath, $rsPath);

                $ipropValues = new Iblock\InheritedProperty\SectionValues($arResult["ID"], $this->arParams["PARENT_SECTION"]);
                $this->arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();
            } else {
                //Если не задан вывод с какого-то отдельного раздела
                $this->arResult["SECTION"] = false;
            }

            //Сортировка. Создание массива параметров сортировки
            //ORDER BY
            $arSort = array(
                $this->arParams["SORT_BY1"] => $this->arParams["SORT_ORDER1"],
                $this->arParams["SORT_BY2"] => $this->arParams["SORT_ORDER2"],
            );
            if (!array_key_exists("ID", $arSort))
                $arSort["ID"] = "DESC";
            //
            $shortSelect = array('ID', 'IBLOCK_ID');
            foreach (array_keys($arSort) as $index) {
                if (!in_array($index, $shortSelect)) {
                    $shortSelect[] = $index;
                }
            }

            $listPageUrl = '';
            $this->arResult["ITEMS"] = array();
            $this->arResult["ELEMENTS"] = array();
            $rsElement = CIBlockElement::GetList(
                $arSort, array_merge($this->arFilter, $this->arrFilter), false, $this->arNavParams, $shortSelect
            );
            while ($row = $rsElement->Fetch()) {
                $id = (int)$row['ID'];
                $this->arResult["ITEMS"][$id] = $row;
                $this->arResult["ELEMENTS"][] = $id;
            }
            unset($row);

            //
            if (!empty($this->arResult['ITEMS']))
            {
                $elementFilter = array(
                    "IBLOCK_ID" => $arResult["ID"],
                    "IBLOCK_LID" => SITE_ID,
                    "ID" => $this->arResult["ELEMENTS"]
                );
                if (isset($this->arrFilter['SHOW_NEW']))
                {
                    $elementFilter['SHOW_NEW'] = $this->arrFilter['SHOW_NEW'];
                }

                $obParser = new CTextParser;
                $iterator = CIBlockElement::GetList(array(), $elementFilter, false, false, $arSelect);
                $iterator->SetUrlTemplates($this->arParams["DETAIL_URL"], '', ($this->arParams["IBLOCK_URL"] ?? ''));
                while ($arItem = $iterator->GetNext())
                {
                    $arButtons = CIBlock::GetPanelButtons(
                        $arItem["IBLOCK_ID"],
                        $arItem["ID"],
                        0,
                        array("SECTION_BUTTONS" => false, "SESSID" => false)
                    );
                    $arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"] ?? '';
                    $arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"] ?? '';

                    if ($this->arParams["PREVIEW_TRUNCATE_LEN"] > 0)
                        $arItem["PREVIEW_TEXT"] = $obParser->html_cut($arItem["PREVIEW_TEXT"], $this->arParams["PREVIEW_TRUNCATE_LEN"]);

                    if ($arItem["ACTIVE_FROM"] <> '')
                        $arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(
                            $this->arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($arItem["ACTIVE_FROM"], CSite::GetDateFormat())
                        );
                    else
                        $arItem["DISPLAY_ACTIVE_FROM"] = "";

                    Iblock\InheritedProperty\ElementValues::queue($arItem["IBLOCK_ID"], $arItem["ID"]);

                    $arItem["FIELDS"] = array();

                    if ($bGetProperty)
                    {
                        $arItem["PROPERTIES"] = array();
                    }
                    $arItem["DISPLAY_PROPERTIES"] = array();

                    if ($this->arParams["SET_LAST_MODIFIED"])
                    {
                        $time = DateTime::createFromUserTime($arItem["TIMESTAMP_X"]);
                        if (
                            !isset($arResult["ITEMS_TIMESTAMP_X"])
                            || $time->getTimestamp() > $this->arResult["ITEMS_TIMESTAMP_X"]->getTimestamp()
                        )
                            $this->arResult["ITEMS_TIMESTAMP_X"] = $time;
                    }

                    if ($listPageUrl === '' && isset($arItem['~LIST_PAGE_URL']))
                    {
                        $listPageUrl = $arItem['~LIST_PAGE_URL'];
                    }

                    $id = (int)$arItem["ID"];
                    $this->arResult["ITEMS"][$id] = $arItem;
                }
                unset($obElement);
                unset($iterator);

                if ($bGetProperty)
                {
                    unset($elementFilter['IBLOCK_LID']);
                    CIBlockElement::GetPropertyValuesArray(
                        $this->arResult["ITEMS"],
                        $this->arResult["ID"],
                        $elementFilter
                    );
                }
            }

            $this->arResult['ITEMS'] = array_values($this->arResult['ITEMS']);

            foreach ($this->arResult["ITEMS"] as &$arItem)
            {
                if ($bGetProperty)
                {
                    foreach ($this->arParams["PROPERTY_CODE"] as $pid)
                    {
                        $prop = &$arItem["PROPERTIES"][$pid];
                        if (
                            (is_array($prop["VALUE"]) && count($prop["VALUE"]) > 0)
                            || (!is_array($prop["VALUE"]) && $prop["VALUE"] <> '')
                        )
                        {
                            $arItem["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arItem, $prop);
                        }
                    }
                }

                $ipropValues = new Iblock\InheritedProperty\ElementValues($arItem["IBLOCK_ID"], $arItem["ID"]);
                $arItem["IPROPERTY_VALUES"] = $ipropValues->getValues();
                Iblock\Component\Tools::getFieldImageData(
                    $arItem,
                    array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
                    Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
                    'IPROPERTY_VALUES'
                );

                foreach($this->arParams["FIELD_CODE"] as $code)
                    if(array_key_exists($code, $arItem))
                        $arItem["FIELDS"][$code] = $arItem[$code];
            }
            unset($arItem);
            if ($bGetProperty)
            {
                \CIBlockFormatProperties::clearCache();
            }

            $navComponentParameters = array();
            if ($this->arParams["PAGER_BASE_LINK_ENABLE"] === "Y")
            {
                $pagerBaseLink = trim($this->arParams["PAGER_BASE_LINK"]);
                if ($pagerBaseLink === "")
                {
                    if (
                        $this->arResult["SECTION"]
                        && $this->arResult["SECTION"]["PATH"]
                        && $this->arResult["SECTION"]["PATH"][0]
                        && $this->arResult["SECTION"]["PATH"][0]["~SECTION_PAGE_URL"]
                    )
                    {
                        $pagerBaseLink = $this->arResult["SECTION"]["PATH"][0]["~SECTION_PAGE_URL"];
                    }
                    elseif (
                        $listPageUrl !== ''
                    )
                    {
                        $pagerBaseLink = $listPageUrl;
                    }
                }

                if ($this->pagerParameters && isset($this->pagerParameters["BASE_LINK"]))
                {
                    $pagerBaseLink = $this->pagerParameters["BASE_LINK"];
                    unset($this->pagerParameters["BASE_LINK"]);
                }

                $navComponentParameters["BASE_LINK"] = CHTTP::urlAddParams($pagerBaseLink, $this->pagerParameters, array("encode"=>true));
            }

            $this->arResult["NAV_STRING"] = $rsElement->GetPageNavStringEx(
                $navComponentObject,
                $this->arParams["PAGER_TITLE"],
                $this->arParams["PAGER_TEMPLATE"],
                $this->arParams["PAGER_SHOW_ALWAYS"],
                $this,
                $navComponentParameters
            );
            $this->arResult["NAV_CACHED_DATA"] = null;
            $this->arResult["NAV_RESULT"] = $rsElement;
            $this->arResult["NAV_PARAM"] = $navComponentParameters;

            $this->setResultCacheKeys(array(
                "ID",
                "IBLOCK_TYPE_ID",
                "LIST_PAGE_URL",
                "NAV_CACHED_DATA",
                "NAME",
                "SECTION",
                "ELEMENTS",
                "IPROPERTY_VALUES",
                "ITEMS_TIMESTAMP_X",
            ));
            $this->includeComponentTemplate();
        }
        if(isset($arResult["ID"]))
        {
            $arTitleOptions = null;
            if($USER->IsAuthorized())
            {
                if(
                    $APPLICATION->GetShowIncludeAreas()
                    || (is_object($GLOBALS["INTRANET_TOOLBAR"]) && $this->arParams["INTRANET_TOOLBAR"]!=="N")
                    || $this->arParams["SET_TITLE"]
                )
                {
                    if(Loader::includeModule("iblock"))
                    {
                        $arButtons = CIBlock::GetPanelButtons(
                            $this->arResult["ID"],
                            0,
                            $this->arParams["PARENT_SECTION"],
                            array("SECTION_BUTTONS"=>false)
                        );

                        if($APPLICATION->GetShowIncludeAreas())
                            $this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));

                        if(
                            is_array($arButtons["intranet"])
                            && is_object($INTRANET_TOOLBAR)
                            && $this->arParams["INTRANET_TOOLBAR"]!=="N"
                        )
                        {
                            $APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
                            foreach($arButtons["intranet"] as $arButton)
                                $INTRANET_TOOLBAR->AddButton($arButton);
                        }

                        if($this->arParams["SET_TITLE"])
                        {
                            if (isset($arButtons["submenu"]["edit_iblock"]))
                            {
                                $arTitleOptions = [
                                    'ADMIN_EDIT_LINK' => $arButtons["submenu"]["edit_iblock"]["ACTION"],
                                    'PUBLIC_EDIT_LINK' => "",
                                    'COMPONENT_NAME' => $this->getName(),
                                ];
                            }
                        }
                    }
                }
            }

            $this->setTemplateCachedData($this->arResult["NAV_CACHED_DATA"]);

            $ipropertyExists = (!empty($this->arResult["IPROPERTY_VALUES"]) && is_array($this->arResult["IPROPERTY_VALUES"]));
            $iproperty = ($ipropertyExists ? $this->arResult["IPROPERTY_VALUES"] : array());

            if($this->arParams["SET_TITLE"])
            {
                if ($ipropertyExists && $iproperty["SECTION_PAGE_TITLE"] != "")
                    $APPLICATION->SetTitle($iproperty["SECTION_PAGE_TITLE"], $arTitleOptions);
                elseif(isset($this->arResult["NAME"]))
                    $APPLICATION->SetTitle($this->arResult["NAME"], $arTitleOptions);
            }

            if ($ipropertyExists)
            {
                if ($this->arParams["SET_BROWSER_TITLE"] === 'Y' && $iproperty["SECTION_META_TITLE"] != "")
                    $APPLICATION->SetPageProperty("title", $iproperty["SECTION_META_TITLE"], $arTitleOptions);

                if ($this->arParams["SET_META_KEYWORDS"] === 'Y' && $iproperty["SECTION_META_KEYWORDS"] != "")
                    $APPLICATION->SetPageProperty("keywords", $iproperty["SECTION_META_KEYWORDS"], $arTitleOptions);

                if ($this->arParams["SET_META_DESCRIPTION"] === 'Y' && $iproperty["SECTION_META_DESCRIPTION"] != "")
                    $APPLICATION->SetPageProperty("description", $iproperty["SECTION_META_DESCRIPTION"], $arTitleOptions);
            }

            if($this->arParams["INCLUDE_IBLOCK_INTO_CHAIN"] && isset($this->arResult["NAME"]))
            {
                if($this->arParams["ADD_SECTIONS_CHAIN"] && is_array($this->arResult["SECTION"]))
                    $APPLICATION->AddChainItem(
                        $this->arResult["NAME"]
                        ,$this->arParams["IBLOCK_URL"] <> ''? $this->arParams["IBLOCK_URL"]: $this->arResult["LIST_PAGE_URL"]
                    );
                else
                    $APPLICATION->AddChainItem($this->arResult["NAME"]);
            }

            if($this->arParams["ADD_SECTIONS_CHAIN"] && is_array($this->arResult["SECTION"]))
            {
                foreach($this->arResult["SECTION"]["PATH"] as $arPath)
                {
                    if ($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] != "")
                        $APPLICATION->AddChainItem($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"], $arPath["~SECTION_PAGE_URL"]);
                    else
                        $APPLICATION->AddChainItem($arPath["NAME"], $arPath["~SECTION_PAGE_URL"]);
                }
            }

            if ($this->arParams["SET_LAST_MODIFIED"] && $this->arResult["ITEMS_TIMESTAMP_X"])
            {
                Context::getCurrent()->getResponse()->setLastModified($this->arResult["ITEMS_TIMESTAMP_X"]);
            }

            unset($iproperty);
            unset($ipropertyExists);

//            return $this->arResult["ELEMENTS"];
        }





            if ($this->arParams['IBLOCK_ID']) {
                //задан ID инфоблока
                $this->getNewsListByIBlockId();

            } else {
                // получаем id инфоблоков по типу инфоблока
                $this->getArrResultItems();
            }
    }

    private function getNewsListByIBlockId()
    {
        // получение списка элементов как в bitrix:news.list
        var_dump($this->arParams['IBLOCK_ID']);
        var_dump($this->arParams);
    }

    private function getArrResultItems()
    {
        var_dump($this->arParams['IBLOCK_TYPE']);
        var_dump($this->arParams);
    }


}