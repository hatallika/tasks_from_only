<?php

use Dev\Site\Handlers\Iblock;

//CModule::AddAutoloadClasses(
//    '', // не указываем имя модуля
//    array(
//        // ключ - имя класса с простанством имен, значение - путь относительно корня сайта к файлу
//        'Only\Site\Handlers\Iblock' => '/local/modules/dev.site/lib/Handlers/Iblock.php',
//    )
//);
//
//AddEventHandler('iblock', 'OnBeforeIBlockElementAdd', Array(Iblock::class, "addLog"));

CModule::IncludeModule("dev.site");
dev_site_autoload(Iblock::class);
AddEventHandler('iblock', 'OnBeforeIBlockElementAdd', Array(Iblock::class, "addLog"));



