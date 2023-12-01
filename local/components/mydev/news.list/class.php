<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Context,
    Bitrix\Main\Loader,
    Bitrix\Iblock;
use Bitrix\Main\SystemException;


class CMyDevNewsList extends CBitrixComponent
{
    private mixed $arrFilter;
    private bool $bUSER_HAVE_ACCESS;
    private mixed $arNavigation;
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
            $this->includeComponentTemplate();

        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    private function getResult()
    {
        global $USER;

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
            if ($this->arParams['IBLOCK_ID'] !== '') {
                //задан ID инфоблока
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
                $arRes = $rsIBlock->GetNext();

                if (!$arRes) {
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

                $this->arResult['IBLOCKS'][$arRes['ID']] = $arRes;


            } else {
                // получаем id инфоблоков по типу инфоблока
                $rsIBlock = CIBlock::GetList(array(), array(
                    "ACTIVE" => "Y",
                    "TYPE" => $this->arParams["IBLOCK_TYPE"],
                ));

                while ($arResult = $rsIBlock->GetNext()){
                    $this->arResult['IBLOCKS'][$arResult['ID']] = $arResult;
                }
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

            $bGetProperty = !empty($this->arParams["PROPERTY_CODE"]);
            //WHERE
            $this->arFilter = array(
                "IBLOCK_ID" => array_keys($this->arResult['IBLOCKS']),
                "IBLOCK_LID" => SITE_ID,
                "ACTIVE" => "Y",
                "CHECK_PERMISSIONS" => $this->arParams['CHECK_PERMISSIONS'] ? "Y" : "N",
            );
            if ($this->arParams["CHECK_DATES"])
                $this->arFilter["ACTIVE_DATE"] = "Y";


            //Сортировка. Создание массива параметров сортировки
            //ORDER BY
            $arSort = array(
                $this->arParams["SORT_BY1"] => $this->arParams["SORT_ORDER1"],
                $this->arParams["SORT_BY2"] => $this->arParams["SORT_ORDER2"],
            );
            if (!array_key_exists("ID", $arSort))
                $arSort["ID"] = "DESC";

            $listPageUrl = '';
            $arResult["ITEMS"] = array();
            $arResult["ELEMENTS"] = array();
            $rsElement = CIBlockElement::GetList($arSort, array_merge($this->arFilter , $this->arrFilter), false, $this->arNavParams, $this->shortSelect);
            while ($row = $rsElement->Fetch())
            {
                $id = (int)$row['ID'];
                $arResult["ITEMS"][$id] = $row;
                $arResult["ELEMENTS"][] = $id;
            }
            unset($row);

            if (!empty($arResult['ITEMS']))
            {
                $elementFilter = array(
                    "IBLOCK_ID" => $arResult["ID"],
                    "IBLOCK_LID" => SITE_ID,
                    "ID" => $arResult["ELEMENTS"]
                );
                if (isset($arrFilter['SHOW_NEW']))
                {
                    $elementFilter['SHOW_NEW'] = $arrFilter['SHOW_NEW'];
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
                        $arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat($this->arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($arItem["ACTIVE_FROM"], CSite::GetDateFormat()));
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
                            || $time->getTimestamp() > $arResult["ITEMS_TIMESTAMP_X"]->getTimestamp()
                        )
                            $arResult["ITEMS_TIMESTAMP_X"] = $time;
                    }

                    if ($listPageUrl === '' && isset($arItem['~LIST_PAGE_URL']))
                    {
                        $listPageUrl = $arItem['~LIST_PAGE_URL'];
                    }

                    $id = (int)$arItem["ID"];
                    $arResult["ITEMS"][$id] = $arItem;

                    $this->arResult["ITEMS"][$arItem['IBLOCK_ID']][$id] = $arItem;
                }

                unset($obElement);
                unset($iterator);

                if ($bGetProperty)
                {
                    unset($elementFilter['IBLOCK_LID']);
                    CIBlockElement::GetPropertyValuesArray(
                        $arResult["ITEMS"],
                        $arResult["ID"],
                        $elementFilter
                    );
                }
            }

//            $this->arResult['ITEMS'] = array_values($this->arResult['ITEMS']);

        }
    }

}