<?php

namespace Dev\Site\Handlers;

use Bitrix\Iblock\IblockTable;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use CIBlockSection;
use CModule;
use Dev\Site\Helpers\IblockTree;

class Iblock
{
    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    static public function addLog(&$arFields)
    {
        $IBLOCK_ID = $arFields['IBLOCK_ID'];
        $ACTIVE_FROM = $arFields['ACTIVE_FROM'];
        $ELEMENT_NAME = $arFields['NAME']; //имя элемента
        $ELEMENT_ID = $arFields['ID'];
        $IBLOCK_NAME = "";
        $IBLOCK_CODE = "";
        $USER_ID = $arFields['CREATED_BY'];
        $DATE_CREATE = "";
        $SECTION_ID = false;

        //Получим имя изменяемого раздела
        $res = CIBlock::GetByID($IBLOCK_ID);
        if ($ar_res = $res->GetNext()) {
            $IBLOCK_NAME = $ar_res['NAME'];
            $IBLOCK_CODE = $ar_res['CODE'];

        }
        if ($IBLOCK_CODE == 'LOG') return; // выход из логирования при изменении инфоблока LOG

        //Получим дату создания
        $res = CIBlockElement::GetByID($ELEMENT_ID);
        if ($ar_res = $res->GetNext()) {
            $DATE_CREATE = $ar_res["DATE_CREATE"];
            //Не учитываем документооборот
            if ($ar_res["WF_PARENT_ELEMENT_ID"] >= 1) return;
        }

        //CREATE LOG (SECTION, ELEMENT)
        $IBLOCK_LOG_ID = self::getIblockIdByCode('LOG');
        $logSectionName = "{$IBLOCK_ID}_{$IBLOCK_NAME}";

        $el = new CIBlockElement;

        // поиск Раздела с именем по правилам логирования
        $resSectionId = CIBlockSection::GetList(
            array(),
            array('IBLOCK_ID' => $IBLOCK_LOG_ID, 'NAME' => $logSectionName));
        $sectionLog = $resSectionId->Fetch();

        if ($sectionLog) {
            //Раздел уже есть
            $sectionLog_ID = $sectionLog['ID'];
        } else {
            //Создаем раздел инфоблока с именем и кодом логируемого инфоблока
            CModule::IncludeModule('iblock');
            $bs = new CIBlockSection;
            $arLoadSectionArray = array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => $IBLOCK_LOG_ID,
                "NAME" => $logSectionName,
                "CODE" => $IBLOCK_CODE,
                "SORT" => 100,
            );
            if ($newSection = $bs->Add($arLoadSectionArray)) {
                var_dump("ID новой секции: " . $newSection);
                $sectionLog_ID = $newSection;
            } else {
                var_dump("Error: " . $bs->LAST_ERROR);
            }
        }

        //Получим цепочку разделов логируемого элемента

        //Вариант с помощью CIBlockSection::GetNavChain

//        $groups = CIBlockElement::GetElementGroups($ELEMENT_ID, true);
//        $arrSections = [];
//        while ($ar_group = $groups->Fetch()) {
//            $chain = CIBlockSection::GetNavChain($ar_group['IBLOCK_ID'], $ar_group['ID']);
//
//            while ($arNav = $chain->GetNext()) {
//                $arrSections[] = $arNav['NAME'];
//            }
//        }
//        $strSections = implode('->', $arrSections);

        //Вариант через класс с рекурсией

        $elTree = (new IblockTree($IBLOCK_ID));
        $elSectionsArr = $elTree->getIblocSectionListForElement($ELEMENT_ID);

        $strSections = implode('->', $elSectionsArr);

        // добавляем элемент
        $arLoadProductArray = array(
            "MODIFIED_BY" => $USER_ID, // элемент изменен текущим пользователем
            "IBLOCK_SECTION_ID" => $sectionLog_ID,   // элемент лежит в корне раздела
            "IBLOCK_ID" => $IBLOCK_LOG_ID,
            "NAME" => $ELEMENT_ID,
            "CODE" => $ELEMENT_ID,
            "ACTIVE" => "Y",            // активен
            "PREVIEW_TEXT" => "$IBLOCK_NAME->$strSections->$ELEMENT_NAME",
            "DATE_ACTIVE_FROM" => $DATE_CREATE,
        );

        if ($PRODUCT_ID = $el->Add($arLoadProductArray))
            echo "New ID: " . $PRODUCT_ID;
        else
            echo "Error: " . $el->LAST_ERROR;
    }

    //Получить ID инфоблока по CODE
    static function getIblockIdByCode(string $iblockCode, int $cacheTime = 86400000): int
    {
        $iblock = IblockTable::getList([
            'filter' => [
                '=CODE' => $iblockCode
            ],
            'select' => ['ID'],
            'limit' => 1,
            'cache' => [
                'ttl' => $cacheTime
            ]
        ])->fetch();

        return ($iblock['ID'] > 0) ? $iblock['ID'] : 0;
    }

    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }


        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }

}
