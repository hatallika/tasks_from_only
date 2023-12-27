<?php
global $DB;
$db_type = strtolower($DB->type);

\Bitrix\Main\Loader::registerAutoLoadClasses('phpdevorg.cprop', [
    'CIBlockPropertyCProp' => 'lib/CIBlockPropertyCProp.php',
    'CCustomTypeHtml' => 'lib/CCustomTypeHtml.php',
    'CCustomTypeComplex' => 'lib/CCustomTypeComplex.php',
    'CCustomTypeMulty' => 'lib/CCustomTypeMulty.php',

]);