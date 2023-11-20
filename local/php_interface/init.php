<?php

use Dev\Site\Handlers\Iblock;
// Вариант 1
//CModule::AddAutoloadClasses(
//    '',
//    array(
//        'Only\Site\Handlers\Iblock' => '/local/modules/dev.site/lib/Handlers/Iblock.php',
//    )
//);
//
//AddEventHandler('iblock', 'OnAfterIBlockElementAdd', Array(Iblock::class, "addLog"));

// Вариант 2
CModule::IncludeModule("dev.site");
dev_site_autoload(Iblock::class);
AddEventHandler('iblock', 'OnAfterIBlockElementAdd', Array(Iblock::class, "addLog"));



