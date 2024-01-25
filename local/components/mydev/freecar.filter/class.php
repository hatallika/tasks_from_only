<?php

use Bitrix\Iblock\IblockTable;
use \Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Highloadblock as HL;

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
        return $arParams;
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

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    private function getResult()
    {
        global $USER;
        $userID = $USER->GetID();
        $userFullName = $USER->GetFullName();

        $iBlockPositionId = $this->getIblockIdByCode('POSITIONS');
        $iBlockCarsId = $this->getIblockIdByCode('CARS');

        Loader::includeModule("highloadblock");
        $hlbl = 4;
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        //Должность
        $rsUser = CUser::GetByID($userID);
        $arUser = $rsUser->Fetch();
        $jobPositionID = $arUser['UF_JOB_POSITION'];

        //Доступные уровни комфорта из должности
        $accessCategories = [];
        if (!is_null($jobPositionID)) {
            $res = CIBlockElement::GetProperty($iBlockPositionId, $jobPositionID, "sort", "asc", array("CODE" => "COMFORT_ACCESS"));
            while ($ob = $res->GetNext()) {
                $accessCategories[] = $ob['VALUE'];
            }
        }

        $startTime = $_GET['startDate'] ?? '';
        $endTime = $_GET['endDate'] ?? '';

        // Ввод даты пользователем, передача get параметров в компонент
        echo "<form action='' method='get'>";
        global $APPLICATION;
        $APPLICATION->IncludeComponent(
            "bitrix:main.calendar",
            ".default",
            array(
                "SHOW_INPUT" => "Y",
                "FORM_NAME" => "arrFilter_form",
                "INPUT_NAME" => "startDate",
                "INPUT_NAME_FINISH" => "endDate",
                "INPUT_VALUE" => $startTime,
                "INPUT_VALUE_FINISH" => $endTime,
                "SHOW_TIME" => "Y",
                "HIDE_TIMEBAR" => "Y",
                "INPUT_ADDITIONAL_ATTR" => "placeholder=\"дд.мм.гггг\""
            ),
            false
        );
        echo " <input type='submit' id='sendbutton' value='Выбрать авто'>";
        echo "</form>";

        //Список доступных машин по категори комфорта сотрудника
        $arSelect = array("ID", "IBLOCK_ID", "NAME", "PROPERTY_COMFORT.NAME", "PROPERTY_MODELS", "PROPERTY_DRIVER");
        $arFilter = array("IBLOCK_ID" => $iBlockCarsId, "PROPERTY_COMFORT" => $accessCategories);
        $res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);


        echo "<div class='h2'>Список авто, доступные для $userFullName</div>";
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $carId = $arFields['ID'];
            //Отфильтруем авто по доступной дате
            //Есть ли запись на текущее авто в HL блоке
            $highLoadBlock = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("ID" => "ASC"),
                "filter" => array(
                    'UF_TRIPCAR' => $carId,
                    array(
                        'LOGIC' => 'AND',
                        ">=UF_ENDTIME" => new \Bitrix\Main\Type\DateTime($_GET['startDate']),
                        "<=UF_STARTDATE" => new \Bitrix\Main\Type\DateTime($_GET['endDate']),
                    ),
                )
            ))->fetch();

            if(!$highLoadBlock){
                $arFields = $ob->GetFields();
                $arProperties = $ob->GetProperties();
                //Модель авто, уровень комфорта
                $res_ = CIBlockSection::GetByID($arProperties['MODELS']['VALUE']);
                $ar_res = $res_->GetNext();
                echo "Авто: {$arFields}  Марка авто: {$ar_res['NAME']} Уровень комфорта: {$arFields['PROPERTY_COMFORT_NAME']} ";
                //Водитель
                $rsUser = CUser::GetByID($arProperties['DRIVER']['VALUE']);
                $arUser = $rsUser->Fetch();
                $driverName = $arUser['NAME'] . ' ' . $arUser['LAST_NAME'];
                echo "Водитель $driverName </br>";
            }

        }

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
