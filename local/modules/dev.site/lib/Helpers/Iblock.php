<?php

namespace Dev\Site\Helpers;

use Bitrix\Iblock\IblockTable;

class Iblock
{
    //Получить ID инфоблока по CODE // LOG - 29
    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
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
}