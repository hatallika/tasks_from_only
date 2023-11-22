<?php

namespace Dev\Site\Agents;

use Bitrix\Main\Loader;
use CIBlockElement;
use CIBlockSection;
use CModule;

class Iblock
{

    public static function clearOldLogs()
    {
        // Сортируем по дате изменения, удаляем все элементы после 10.
        // Можно рассмотреть вариант сделать запрос limit 10,count и удалить все
        if (Loader::includeModule('iblock')){
            $iblockId = \Dev\Site\Helpers\Iblock::getIblockIdByCode('LOG');
            $arOrder =  ["TIMESTAMP_X" => 'DESC'];
            $arSelect = array("ID", "IBLOCK_SECTION_ID");
            $arFilter = array('IBLOCK_ID'=> $iblockId);
            $res = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
            $i = 0;
            while ($ob = $res->GetNext()){
                if ($i < 10){
                    // пропустим первые 10 элементов при обходе
                    $i++;
                } else {
                    CIBlockElement::Delete($ob['ID']); // удаляем элемент
                    //Можно добавить удаление пустых разделов.
                }
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
