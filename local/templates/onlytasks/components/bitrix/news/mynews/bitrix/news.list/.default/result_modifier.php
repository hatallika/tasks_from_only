<?php
/**
 * Файл для вывода заголовков и вывода подразделов текущего раздела инфоблока
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*
 * Получаем информацию о текущем разделе инфоблока. Здесь возможны две ситуации:
 * - первая — мы имеем дело с обычным разделом инфоблока, который выводит список
 *   элементов этого раздела и всех его потомков
 * - вторая — мы имеем дело с корневым разделом инфоблока, который выводит список
 *   всех элементов, которые есть в инфоблоке
 * Для обоих случаев используется простой компонент bitrix:news.list и нам надо
 * как-то различать эти две ситуации. Но в любом случае у страницы должен быть
 * заголовок и список подразделов текущего раздела — для навигации. Потому как в
 * стандартном компоненте Битрикс этого не предусмотрено. Или я чего-то не понял.
 */

$arParams['PARENT_SECTION'] = intval($arParams['PARENT_SECTION']);

if ($arParams['PARENT_SECTION'] > 0) { // это обычный раздел инфоблока
    /*
     * Сначала получаем инфомацию о текущем разделе
     */
    $arResult['SECTION_DATA']['ROOT'] = false;
    // какие поля раздела инфоблока выбираем
    $arSelect = array(
        'NAME',
        'DESCRIPTION',
    );
    // условия выборки
    $arFilter = array(
        'IBLOCK_ID' => $arResult['ID'],      // идентификатор инфоблока
        'IBLOCK_ACTIVE' => 'Y',              // инфоблок должен быть активен
        'ID' => $arParams['PARENT_SECTION'], // идентификатор раздела
        'ACTIVE' => 'Y',                     // раздел должен быть активным
    );
    // сортировка
    $arSort = array(
        'SORT' => 'ASC'
    );
    // выполняем запрос к базе данных
    $dbResult = CIBlockSection::GetList(
        $arSort,
        $arFilter,
        false,
        $arSelect
    );
    if ($arSection = $dbResult->GetNext()) {
        $arResult['SECTION_DATA'] = $arSection;
    }
    /*
     * А потом информацию о подразделах текущего раздела
     */
    // какие поля подразделов выбираем
    $arSelect = array(
        'ID',
        'NAME',
        'DESCRIPTION',
        'SECTION_PAGE_URL'
    );
    // условия выборки подразделов
    $arFilter = array(
        'IBLOCK_ID' => $arResult['ID'], // идентификатор инфоблока
        'IBLOCK_ACTIVE' => 'Y',         // инфоблок должен быть активен
        'ACTIVE' => 'Y',                // только активные разделы
        'SECTION_ID' => $arParams['PARENT_SECTION']
    );
    // сортировка разделов
    $arSort = array(
        'SORT' => 'ASC'
    );
    // выполняем запрос к базе данных
    $dbResult = CIBlockSection::GetList(
        $arSort,
        $arFilter,
        false,
        $arSelect
    );
    // шаблон ссылки на страницу с содержимым подраздела
    $dbResult->SetUrlTemplates('', $arParams['SECTION_URL']);
    while ($arSection = $dbResult->GetNext()) {
        $arResult['SECTION_DATA']['CHILDS'][] = $arSection;
    }
} else { // это корень, делаем виртуальный раздел
    /*
     * Сначала получаем инфомацию о корневом разделе
     */
    $arResult['SECTION_DATA']['ROOT'] = true;
    // в качестве имени корневого раздела используем имя инфоблока
    $arResult['SECTION_DATA']['NAME'] = $arResult['NAME'];
    // в качестве описания корневого раздела используем описание инфоблока
    $arResult['SECTION_DATA']['DESCRIPTION'] = $arResult['DESCRIPTION'];
    /*
     * А потом информацию о подразделах корневого раздела
     */
    // какие поля подразделов выбираем
    $arSelect = array(
        'ID',
        'NAME',
        'DESCRIPTION',
        'SECTION_PAGE_URL'
    );
    // условия выборки подразделов
    $arFilter = array(
        'IBLOCK_ID' => $arResult['ID'], // идентификатор инфоблока
        'IBLOCK_ACTIVE' => 'Y',         // инфоблок должен быть активен
        'ACTIVE' => 'Y',                // только активные разделы
        'SECTION_ID' => false           // выбираем разделы верхнего уровня
    );
    // сортировка разделов
    $arSort = array(
        'SORT' => 'ASC'
    );
    // выполняем запрос к базе данных
    $dbResult = CIBlockSection::GetList(
        $arSort,
        $arFilter,
        false,
        $arSelect
    );
    // шаблон ссылки на страницу с содержимым подраздела
    $dbResult->SetUrlTemplates('', $arParams['SECTION_URL']);
    while ($arSection = $dbResult->GetNext()) {
        $arResult['SECTION_DATA']['CHILDS'][] = $arSection;
    }
}