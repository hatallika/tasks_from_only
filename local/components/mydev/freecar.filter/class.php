<?php

use Bitrix\Iblock\IblockTable;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use Bitrix\Main\SystemException;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

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
        $hlbl = 4; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();


        //Должность
        $rsUser = CUser::GetByID($userID);
        $arUser = $rsUser->Fetch();
        $jobPositionID = $arUser['UF_JOB_POSITION'];

        //доступные категории машин согласно должности
        $accessCategories = [];
        if (!is_null($jobPositionID)) {
            //получим доступные уровни комфорта из инфоблока Должности
            $res = CIBlockElement::GetProperty($iBlockPositionId, $jobPositionID, "sort", "asc", array("CODE" => "COMFORT_ACCESS"));
            while ($ob = $res->GetNext()) {
                $accessCategories[] = $ob['VALUE'];
            }
        }

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
                "INPUT_VALUE" => "",
                "INPUT_VALUE_FINISH" => "",
                "SHOW_TIME" => "Y",
                "HIDE_TIMEBAR" => "Y",
                "INPUT_ADDITIONAL_ATTR" => "placeholder=\"дд.мм.гггг\""
            ),
            false
        );
        echo " <input type='submit' id='sendbutton' value='Выбрать авто'>";
        echo "</form>";

        $startTime = $_GET['startDate'] ?? '';
        $endTime = $_GET['endDate'] ?? '';

        global $DB;
        if (!empty($_GET['startDate']) && !empty($_GET['endDate'])) {
//            $first = $DB->FormatDate($_GET['startDate'],"DD.MM.YYYY HH:MI:SS", "YYYY-MM-DD HH:MI:SS");
//            $last = $DB->FormatDate($_GET['endDate'],"DD.MM.YYYY HH:MI:SS", "YYYY-MM-DD HH:MI:SS");
            $first = new \Bitrix\Main\Type\DateTime($_GET['startDate']);
            $last = new \Bitrix\Main\Type\DateTime($_GET['endDate']);
        }

        var_dump($first, $last);

        //Получим список всех доступных машин по категори комфорта сотрудника (без учета тайминга)
        $arSelect = array("ID", "IBLOCK_ID", "NAME", "PROPERTY_COMFORT.NAME", "PROPERTY_MODELS", "PROPERTY_DRIVER");
        $arFilter = array("IBLOCK_ID" => $iBlockCarsId, "PROPERTY_COMFORT" => $accessCategories);
        $res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        Loader::includeModule("highloadblock");

        echo "<div class='h2'> Список авто, доступные для $userFullName</div>";
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $carId = $arFields['ID'];
            //Проверить на дату
            //Есть ли запись на текущее авто в HL блоке
            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("ID" => "ASC"),
                "filter" => array(
                    'UF_TRIPCAR' => $carId,
                    array(
                        'LOGIC' => 'OR',
                        array(
                            'LOGIC' => 'AND',
                            ">=UF_STARTDATE" => new \Bitrix\Main\Type\DateTime($_GET['startDate']),
                            "<=UF_STARTDATE" => new \Bitrix\Main\Type\DateTime($_GET['endDate']),
                        ),
                        array(
                            'LOGIC' => 'AND',
                            ">=UF_ENDTIME" => new \Bitrix\Main\Type\DateTime($_GET['startDate']),
                            "<=UF_ENDTIME" => new \Bitrix\Main\Type\DateTime($_GET['endDate']),
                        ),
                    ),
                )
            ));

            while ($arData = $rsData->Fetch()) {
                //Авто занято
                var_dump($arData);
            }


            $arProperties = $ob->GetProperties();
            var_dump($arFields);
//            var_dump($arProperties);
            //Модель авто, уровень комфорта
            $res_ = CIBlockSection::GetByID($arProperties['MODELS']['VALUE']);
            $ar_res = $res_->GetNext();
            var_dump($ar_res['NAME'], "Уровень комфорта: {$arFields['PROPERTY_COMFORT_NAME']}");
            //Водитель
            $rsUser = CUser::GetByID($arProperties['DRIVER']['VALUE']);
            $arUser = $rsUser->Fetch();
            $driverName = $arUser['NAME'] . ' ' . $arUser['LAST_NAME'];
            var_dump($driverName);
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
