<?php

use Dev\Site\Handlers\Iblock;
//Task5
// Вариант 1 истопльзовать автолоадер битрикс

//CModule::AddAutoloadClasses(
//    '',
//    [
//       'Only\Site\Handlers\Iblock' => '/local/modules/dev.site/lib/Handlers/Iblock.php',
//    ]
//);

// Вариант 2 - Использовать автолоадер из модуля.

//CModule::IncludeModule("dev.site");
//
////dev_site_autoload(Iblock::class);
//AddEventHandler('iblock', 'OnAfterIBlockElementAdd', Array(Iblock::class, "addLog"));
//AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', Array(Iblock::class, "addLog"));
////Можно разместить в функции index.php модуля, чтобы запуск шел после установки.
//CAgent::AddAgent("\\Dev\\Site\\Agents\\Iblock::clearOldLogs();", "dev.site", "N", 3600);



