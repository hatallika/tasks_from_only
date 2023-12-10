<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
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
        if (!isset($arParams["CACHE_TIME"])) {
            $arParams["CACHE_TIME"] = 36000000;
        }

        $arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"] ?? '');
        $arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"] ?? '');
        $arParams["IBLOCK_CODE"] = trim($arParams["IBLOCK_CODE"]);
        $arParams["PARENT_SECTION"] = (int)($arParams["PARENT_SECTION"] ?? 0);
        $arParams["PARENT_SECTION_CODE"] ??= '';
        $arParams["INCLUDE_SUBSECTIONS"] = ($arParams["INCLUDE_SUBSECTIONS"] ?? '') !== "N";
        $arParams["SET_LAST_MODIFIED"] = ($arParams["SET_LAST_MODIFIED"] ?? '') === "Y";
        //параметры постраничной навигации
        $arParams['DISPLAY_TOP_PAGER'] = $arParams['DISPLAY_TOP_PAGER'] == 'Y';
        $arParams['DISPLAY_BOTTOM_PAGER'] = $arParams['DISPLAY_BOTTOM_PAGER'] == 'Y';
        // поясняющий текст для постраничной навигации
        $arParams['PAGER_TITLE'] = trim($arParams['PAGER_TITLE']);
        $arParams['PAGER_SHOW_ALWAYS'] = $arParams['PAGER_SHOW_ALWAYS'] == 'Y';
        // имя шаблона постраничной навигации
        $arParams['PAGER_TEMPLATE'] = trim($arParams['PAGER_TEMPLATE']);
        // показывать ссылку «Все элементы», с помощью которой можно показать все элементы списка?
        $arParams['PAGER_SHOW_ALL'] = $arParams['PAGER_SHOW_ALL'] == 'Y';

        if (empty($arParams['SORT_BY']))
            $arParams['SORT_BY'] = 'SORT';
        if (empty($arParams['SORT_ORDER']))
            $arParams['SORT_ORDER'] = 'ASC';

        //Фильтр по глобальной переменной
        $this->arrFilter = [];

        if (!empty($arParams["FILTER_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])) {
            $this->arrFilter = $GLOBALS[$arParams["FILTER_NAME"]] ?? [];
            if (!is_array($this->arrFilter)) {
                $this->arrFilter = [];
            }
        }
        //Использовать фильтр по полям

        if ($arParams['USE_FILTER'] === 'Y') {
            if ($arParams['FILTER_NAME'] == '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams['FILTER_NAME'])) {
                $arParams['FILTER_NAME'] = 'arrFilter';
            }
        } else {
            $arParams['FILTER_NAME'] = '';
        }
        $arParams['FILTER_FIELD_CODE'] ??= [];
        $arParams['FILTER_FIELD_CODE'] = is_array($arParams['FILTER_FIELD_CODE']) ? $arParams['FILTER_FIELD_CODE'] : [];
        $arParams['FILTER_PROPERTY_CODE'] ??= [];
        $arParams['FILTER_PROPERTY_CODE'] = is_array($arParams['FILTER_PROPERTY_CODE']) ? $arParams['FILTER_PROPERTY_CODE'] : [];


        //Фильтр кастомный - содержание строки в названии элемента.
        var_dump($arParams["FILTER_ELEMENTS_NAME_STR"]);
        if (!($this->checkStr($arParams["FILTER_ELEMENTS_NAME_STR"]))) {
            ShowError("Спецсимволы не разрешены");
        }

        if (!empty($arParams["FILTER_ELEMENTS_NAME_STR"]) && $this->checkStr($arParams["FILTER_ELEMENTS_NAME_STR"])) {
            $this->arrFilter ['NAME'] = "%" . $arParams["FILTER_ELEMENTS_NAME_STR"] . "%";
        }

        $arParams["CHECK_DATES"] = ($arParams["CHECK_DATES"] ?? '') !== "N";
        $arParams["DETAIL_URL"] = trim($arParams["DETAIL_URL"] ?? '');
        $arParams["SECTION_URL"] = trim($arParams["SECTION_URL"] ?? '');
        $arParams["IBLOCK_URL"] = trim($arParams["IBLOCK_URL"] ?? '');

        $arParams["NEWS_COUNT"] = (int)($arParams["NEWS_COUNT"] ?? 0);
        if ($arParams["NEWS_COUNT"] <= 0) {
            $arParams["NEWS_COUNT"] = 20;
        }
        $arParams["CACHE_FILTER"] = ($arParams["CACHE_FILTER"] ?? '') === "Y";
        if (!$arParams["CACHE_FILTER"] && !empty($arrFilter)) {
            $arParams["CACHE_TIME"] = 0;
        }
        //Постраничная навигация.
        if ($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"]) {
            $this->arNavParams = array(
                "nPageSize" => $arParams["NEWS_COUNT"],
                "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
                "bShowAll" => $arParams["PAGER_SHOW_ALL"],
            );

            $this->arNavigation = CDBResult::GetNavParams($this->arNavParams);
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

        //Внешний массив с переменными для построения ссылок в постраничной навигации
        $this->pagerParameters = [];
        if (!empty($arParams["PAGER_PARAMS_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PAGER_PARAMS_NAME"])) {
            $this->pagerParameters = $GLOBALS[$arParams["PAGER_PARAMS_NAME"]] ?? [];
            if (!is_array($this->pagerParameters)) {
                $this->pagerParameters = array();
            }
        }

        $arParams["USE_PERMISSIONS"] = ($arParams["USE_PERMISSIONS"] ?? '') === "Y";
        if (!is_array($arParams["GROUP_PERMISSIONS"] ?? null)) {
            $adminGroupCode = 1;
            $arParams["GROUP_PERMISSIONS"] = [$adminGroupCode];
        }

        $this->bUSER_HAVE_ACCESS = !$arParams["USE_PERMISSIONS"];
        if ($arParams["USE_PERMISSIONS"] && isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"])) {
            $arUserGroupArray = $GLOBALS['USER']->GetUserGroupArray();
            foreach ($arParams["GROUP_PERMISSIONS"] as $PERM) {
                if (in_array($PERM, $arUserGroupArray)) {
                    $this->bUSER_HAVE_ACCESS = true;
                    break;
                }
            }
        }
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
        //Подключение фильра из готового компонента catalog:filter
        if ($this->arParams["USE_FILTER"] == "Y") {
            $res = $this->getCatalogFilter();
            $GLOBALS['APPLICATION']->AddViewContent('myContentBlockName', $res);
        }

        $this->arParams["CACHE_GROUPS"] ??= '';

        if ($this->startResultCache(false, array(
                ($this->arParams["CACHE_GROUPS"] === "N" ? false : $GLOBALS["USER"]->GetGroups()),
                $this->bUSER_HAVE_ACCESS,
                $this->arNavigation,
                $this->arrFilter,
                $this->pagerParameters
            )
        )
        ) {
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
                // получаем id всех инфоблоков выбранного типа
                $rsIBlock = CIBlock::GetList(array(), array(
                    "ACTIVE" => "Y",
                    "TYPE" => $this->arParams["IBLOCK_TYPE"],
                ));

                while ($arResult = $rsIBlock->GetNext()) {
                    $this->arResult['IBLOCKS'][$arResult['ID']] = $arResult;
                }
            }

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

            $shortSelect = array('ID', 'IBLOCK_ID');
            foreach (array_keys($arSort) as $index) {
                if (!in_array($index, $shortSelect)) {
                    $shortSelect[] = $index;
                }
            }

            $listPageUrl = '';
            $arResult["ITEMS"] = array();
            $arResult["ELEMENTS"] = array();
            $rsElement = CIBlockElement::GetList($arSort, array_merge($this->arFilter, $this->arrFilter, ($GLOBALS['arrFilter']) ?? []), false, $this->arNavParams, $shortSelect);
            while ($row = $rsElement->Fetch()) {
                $id = (int)$row['ID'];
                $arResult["ITEMS"][$id] = $row;
                $arResult["ELEMENTS"][] = $id;
            }
            unset($row);

            if (!empty($arResult['ITEMS'])) {
                $elementFilter = array(
                    "IBLOCK_ID" => $arResult["ID"],
                    "IBLOCK_LID" => SITE_ID,
                    "ID" => $arResult["ELEMENTS"]
                );
                if (isset($arrFilter['SHOW_NEW'])) {
                    $elementFilter['SHOW_NEW'] = $arrFilter['SHOW_NEW'];
                }

                $obParser = new CTextParser;
                $iterator = CIBlockElement::GetList(array(), $elementFilter, false, false);
                $iterator->SetUrlTemplates($this->arParams["DETAIL_URL"], '', ($this->arParams["IBLOCK_URL"] ?? ''));
                while ($arItem = $iterator->GetNext()) {
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

                    if ($bGetProperty) {
                        $arItem["PROPERTIES"] = array();
                    }
                    $arItem["DISPLAY_PROPERTIES"] = array();

                    if ($this->arParams["SET_LAST_MODIFIED"]) {
                        $time = DateTime::createFromUserTime($arItem["TIMESTAMP_X"]);
                        if (
                            !isset($arResult["ITEMS_TIMESTAMP_X"])
                            || $time->getTimestamp() > $arResult["ITEMS_TIMESTAMP_X"]->getTimestamp()
                        )
                            $arResult["ITEMS_TIMESTAMP_X"] = $time;
                    }

                    if ($listPageUrl === '' && isset($arItem['~LIST_PAGE_URL'])) {
                        $listPageUrl = $arItem['~LIST_PAGE_URL'];
                    }

                    $id = (int)$arItem["ID"];
                    $arResult["ITEMS"][$id] = $arItem;

                    $this->arResult["ITEMS"][$arItem['IBLOCK_ID']][$id] = $arItem;
                }

                unset($obElement);
                unset($iterator);

                if ($bGetProperty) {
                    unset($elementFilter['IBLOCK_LID']);
                    CIBlockElement::GetPropertyValuesArray(
                        $arResult["ITEMS"],
                        $arResult["ID"],
                        $elementFilter
                    );
                }
            }

            $navComponentParameters = array();
            if ($this->arParams["PAGER_BASE_LINK_ENABLE"] === "Y") {
                $pagerBaseLink = trim($this->arParams["PAGER_BASE_LINK"]);
                if ($pagerBaseLink === "") {
                    if (
                        $arResult["SECTION"]
                        && $arResult["SECTION"]["PATH"]
                        && $arResult["SECTION"]["PATH"][0]
                        && $arResult["SECTION"]["PATH"][0]["~SECTION_PAGE_URL"]
                    ) {
                        $pagerBaseLink = $arResult["SECTION"]["PATH"][0]["~SECTION_PAGE_URL"];
                    } elseif (
                        $listPageUrl !== ''
                    ) {
                        $pagerBaseLink = $listPageUrl;
                    }
                }

                if ($this->pagerParameters && isset($this->pagerParameters["BASE_LINK"])) {
                    $pagerBaseLink = $this->pagerParameters["BASE_LINK"];
                    unset($this->pagerParameters["BASE_LINK"]);
                }

                $navComponentParameters["BASE_LINK"] = CHTTP::urlAddParams($pagerBaseLink, $this->pagerParameters, array("encode" => true));
            }
            $this->arResult["NAV_STRING"] = $rsElement->GetPageNavStringEx(
                $navComponentObject,
                $this->arParams["PAGER_TITLE"],
                $this->arParams["PAGER_TEMPLATE"],
                $this->arParams["PAGER_SHOW_ALWAYS"],
                $this,
                $navComponentParameters
            );

        }
    }

    private function checkStr($str): bool
    {
        return preg_match('/^[а-яА-ЯёЁa-zA-Z0-9]+$/u', $str);
    }

    private function getCatalogFilter()
    {
        ob_start();
        $GLOBALS[$this->arParams["FILTER_NAME"]] = [];
        $res = $GLOBALS['APPLICATION']->IncludeComponent(
            "bitrix:catalog.filter",
            "",
            [
                "IBLOCK_TYPE" => $this->arParams["IBLOCK_TYPE"],
                "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
                "FILTER_NAME" => "arrFilter",
                "FIELD_CODE" => $this->arParams["FILTER_FIELD_CODE"],
                "PROPERTY_CODE" => $this->arParams["FILTER_PROPERTY_CODE"],
                "CACHE_TYPE" => $this->arParams["CACHE_TYPE"],
                "CACHE_TIME" => $this->arParams["CACHE_TIME"],
                "CACHE_GROUPS" => $this->arParams["CACHE_GROUPS"],
                "PAGER_PARAMS_NAME" => $this->arParams["PAGER_PARAMS_NAME"],
            ],
            $GLOBALS['component'],
            ['HIDE_ICONS' => 'Y'],
            true,
        );

        $this->parametersValidate($res['ITEMS']);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    private function parametersValidate(mixed $items)
    {
        foreach ($items as $key => $value) {
            switch ($key) {
                case "ID":
                    if (!($value['INPUT_VALUE']['LEFT'] == '' || is_numeric($value['INPUT_VALUE']['LEFT']))) {
                        ShowError('ID должно быть числом');
                    }
                    if (!($value['INPUT_VALUE']['RIGHT'] == '' || is_numeric($value['INPUT_VALUE']['RIGHT']))) {
                        ShowError('ID должно быть числом');
                    }
                    break;
                case "NAME":
                    if (!($this->checkStr($value['INPUT_VALUE']))) {
                        ShowError('Название может содержать только буквы и цифры');
                    }
            }
        }
    }
}