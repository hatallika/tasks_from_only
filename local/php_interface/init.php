<?php

use Dev\Site\Handlers\Iblock;
// Вариант 1 истопльзовать автолоадер битрикс

//CModule::AddAutoloadClasses(
//    '',
//    [
//       'Only\Site\Handlers\Iblock' => '/local/modules/dev.site/lib/Handlers/Iblock.php',
//    ]
//);
// Вариант 2 - Использовать автолоадер из модуля.

CModule::IncludeModule("dev.site");
dev_site_autoload(Iblock::class);
AddEventHandler('iblock', 'OnAfterIBlockElementAdd', Array(Iblock::class, "addLog"));
AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', Array(Iblock::class, "addLog"));



