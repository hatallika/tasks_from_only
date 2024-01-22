<?php

use Bitrix\Iblock\IblockTable;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use Bitrix\Main\SystemException;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class FreeCarFilter extends CBitrixComponent
{

    private function checkModules()
    {
        if (!Loader::includeModule('iblock'))
            throw new SystemException(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
    }

    public function onPrepareComponentParams($arParams): array
    {
        // тут пишем логику обработки параметров, дополнение параметрами по умолчанию
        return $arParams;
    }

    public function executeComponent()
    {
        try {
            $this->checkModules();
            $this->getResult();

        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    private function getResult()
    {
        global $USER;
        $userID = $USER->GetID();
        $iBlockPositionId = $this->getIblockIdByCode('POSITIONS');
        $iBlockCarsId = $this->getIblockIdByCode('CARS');


        $rsUser = CUser::GetByID($userID);
        $arUser = $rsUser->Fetch();
        $userFullName = $USER->GetFullName();

        $jobPositionID = $arUser['UF_JOB_POSITION'];

        //доступные категории машин
        $accessCategories = [];
        if (!is_null($jobPositionID)) {
            //получим доступные уровни комфорта из инфоблока Должности

            $res = CIBlockElement::GetProperty($iBlockPositionId, $jobPositionID, "sort", "asc", array("CODE" => "COMFORT_ACCESS"));
            while ($ob = $res->GetNext()) {
                $accessCategories[] = $ob['VALUE'];
            }

        }

        global $arrFilter;
        global $DB;
        if (!empty($_GET['DATE_FROM']) && !empty($_GET['DATE_TO'])) {
            $first = $DB->FormatDate($_GET['DATE_FROM'],"DD.MM.YYYY HH:MI:SS", "YYYY-MM-DD HH:MI:SS");
            $last = $DB->FormatDate($_GET['DATE_TO'],"DD.MM.YYYY HH:MI:SS", "YYYY-MM-DD HH:MI:SS");
            var_dump($first, $last);
        }


        //выведем список всех доступных машин с указанными категориями (пока без учета тайминга)
        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_COMFORT.NAME", "PROPERTY_MODELS", "PROPERTY_DRIVER.");
        $arFilter = Array("IBLOCK_ID"=>$iBlockCarsId, "PROPERTY_COMFORT" => $accessCategories);
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

        global $APPLICATION;
        $APPLICATION->IncludeComponent(
            "bitrix:main.calendar",
            ".default",
            array(
                "SHOW_INPUT" => "Y",
                "FORM_NAME" => "arrFilter_form",
                "INPUT_NAME" => "DATE_FROM",
                "INPUT_NAME_FINISH" => "DATE_TO",
                "INPUT_VALUE" => "",
                "INPUT_VALUE_FINISH" => "",
                "SHOW_TIME" => "Y",
                "HIDE_TIMEBAR" => "Y",
                "INPUT_ADDITIONAL_ATTR" => "placeholder=\"дд.мм.гггг\""
            ),
            false
        );


        echo "<div class='h2'> Список авто, доступные для $userFullName</div>";
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();

            $arProperties = $ob->GetProperties();
//            var_dump($arFields);
//            var_dump($arProperties);
            $res_ = CIBlockSection::GetByID($arProperties['MODELS']['VALUE']);
            $ar_res = $res_->GetNext();
            var_dump($ar_res['NAME']);
            $rsUser = CUser::GetByID($arProperties['DRIVER']['VALUE']);
            $arUser = $rsUser->Fetch();
            $userName = $arUser['NAME'] . ' ' . $arUser['LAST_NAME'];
            var_dump($userName);
        }

        $startTime = $_GET["starttime"];
        $endTime = $_GET["endtime"];

    }

    private function getIblockIdByCode(string $iblockCode, int $cacheTime = 86400000): int
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
}
