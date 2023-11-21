<?php

namespace Dev\Site\Handlers;

use Bitrix\Iblock\IblockTable;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use CIBlockSection;
use CModule;
use Dev\Site\Helpers\IblockTree;
use Dev\Site\Helpers\Iblock as HelpersIblock;

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
        $ELEMENT_NAME = $arFields['NAME']; //имя элемента
        $ELEMENT_ID = $arFields['ID'];
        $IBLOCK_NAME = "";
        $IBLOCK_CODE = "";
        $USER_ID = $arFields['CREATED_BY'];
        $TIMESTAMP_X = "";
        $DATE_CREATE = "";
        $SECTION_ID = false;

        //Получим имя изменяемого Инфоблока
        $res = CIBlock::GetByID($IBLOCK_ID);
        if ($ar_res = $res->GetNext()) {
            $IBLOCK_NAME = $ar_res['NAME'];
            $IBLOCK_CODE = $ar_res['CODE'];

        }
        if ($IBLOCK_CODE == 'LOG') return; // выход из логирования при изменении инфоблока LOG

        //Получим параметры элемента. Дата создания, изменения, участие в документообороте
        $res = CIBlockElement::GetByID($ELEMENT_ID);
        if ($ar_res = $res->GetNext()) {
            $DATE_CREATE = $ar_res["DATE_CREATE"];
            $TIMESTAMP_X = $ar_res["TIMESTAMP_X"];
            //Не учитываем документооборот чтобы не запускать слушатели дважды
            if ($ar_res["WF_PARENT_ELEMENT_ID"] >= 1) return;
        }

        //CREATE LOG (IBLOCK -> SECTION -> ELEMENT)
        $IBLOCK_LOG_ID = HelpersIblock::getIblockIdByCode('LOG');
        $logSectionName = "{$IBLOCK_ID}_{$IBLOCK_NAME}";
        $elLog = new CIBlockElement;

        // поиск Раздела с именем по правилам логирования
        $resSectionId = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $IBLOCK_LOG_ID, 'NAME' => $logSectionName],
        );

        if ($sectionLog = $resSectionId->Fetch()) {
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
                $sectionLog_ID = false;
            }
        }

        //Поиск разделов логируемого элемента
        //Возможен вариант с помощью CIBlockSection::GetNavChain

        //Поиск через класс с рекурсией
        $elTree = (new IblockTree($IBLOCK_ID));
        $elSectionsArr = $elTree->getIblocSectionListForElement($ELEMENT_ID);

        $strSections = implode('->', $elSectionsArr);

        // добавляем элемент
        $arLoadProductArray = array(
            "MODIFIED_BY" => $USER_ID, // элемент изменен текущим пользователем
            "IBLOCK_SECTION_ID" => $sectionLog_ID,
            "IBLOCK_ID" => $IBLOCK_LOG_ID,
            "NAME" => $ELEMENT_ID,
            "CODE" => $ELEMENT_ID,
            "ACTIVE" => "Y",            // активен
            "PREVIEW_TEXT" => ($strSections) // элемент принадлежит разделам
                ?"$IBLOCK_NAME->$strSections->$ELEMENT_NAME"
                : "{$IBLOCK_NAME}->Корневой->{$ELEMENT_NAME}",
            "DATE_ACTIVE_FROM" => $TIMESTAMP_X, //$DATE_CREATE //TIMESTAMP_X
        );
        //Если такой элемент уже есть в логе, обновим его даные иначе создадим новый
        //Так как это логирование лучше сохранять элементы  как новые при каждом изменении, но в тз написано удаление и изменение

        $arFilter = Array("NAME"=>$ELEMENT_ID,"IBLOCK_ID" => $IBLOCK_LOG_ID);
        $res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nTopCount "=>1), ['NAME', 'ID']);

        if($ob = $res->GetNextElement())
        {
            $arFields = $ob->GetFields();
            //Элемент уже есть в логе
            $elLog->Update($arFields['ID'], $arLoadProductArray);
        } else {
            //Элемент еще не логировался
            if ($PRODUCT_ID = $elLog->Add($arLoadProductArray))
                echo "New ID: " . $PRODUCT_ID;
            else
                echo "Error: " . $elLog->LAST_ERROR;
        }
    }
}
