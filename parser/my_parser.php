<?php

use Bitrix\Main\Loader;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}
Loader::includeModule('iblock');

// Предположим есть интерфейс предлагающий параметры парсинга
$IBLOCK_ID = 28;

$fileName = "vacancy.csv";
$fileDir = "";
$encoding = "UTF-8";
$separator = ",";
$nameColumn = 3;

$vacancy = new CIBlockElement;

// Выбрали поля соответствия CSV заголовки и поля IBlock (в интерфейсе)
$fieldsPair = [
    'Тип занятости:' => "ACTIVITY",
    'Сфера деятельности' => 'FIELD',
    'Комбинат' => 'OFFICE',
    'Местоположение' => 'LOCATION',
    'Требования' => 'REQUIRE',
    'Обязанности' => 'DUTY',
    'Условия работы' => 'CONDITIONS',
    'Кому направить резюме (e-mail)' => 'EMAIL',
    'Категория позиции' => 'TYPE',
    'Зарплата' => 'SALARY_VALUE',
    'График работы' => 'SCHEDULE',
];

// Получим значения полей включая и типы списки с их значениями, запишем ID таких значений
$rsProp = CIBlockPropertyEnum::GetList(
    ["SORT" => "ASC", "VALUE" => "ASC"],
    ['IBLOCK_ID' => $IBLOCK_ID]
);
// зависимости полей, когда одно поле генерирует значение другого поля и правила генерации
$fieldsDependence = [
    'SALARY_VALUE' => ['SALARY_TYPE', 'salary_type_rule'],
    'DATE' => ['DATE', 'date_rule']
];

$rules = [
    'salary_type_rule' => function ($value) {
        if ($value == "-") return ["", "="];
        if ($value == "по договоренности") {
            return ["", "договорная"];
        } else {
            $arSalary = explode(' ', $value);
            $val = str_replace(["руб.", " "], ["", ""], trim($value));
            if ($arSalary[0] == 'от' || $arSalary[0] == 'до') {
                $type = $arSalary[0];
                array_splice($arSalary, 0, 1);
                $val = implode(' ', $arSalary);
            } else {
                $type = '=';
            }
            return [$val, $type];
        }
    },
    'date_rule' => function ($value) {
        // искуственная зависимость
        return [date('d.m.Y'), date('d.m.Y')];
    }
];

$arProps = [];
while ($arProp = $rsProp->Fetch()) {
    $key = trim($arProp['VALUE']);
    $arProps[$arProp['PROPERTY_CODE']][$key] = $arProp['ID'];
}

$arrCode = [];
$properties = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", "IBLOCK_ID" => $IBLOCK_ID));
while ($prop_fields = $properties->GetNext()) {
    $arrCode[] = $prop_fields["CODE"];
}

// удаление старых элементов в таблице
$rsElements = CIBlockElement::GetList([], ['IBLOCK_ID' => $IBLOCK_ID], false, false, ['ID']);
while ($element = $rsElements->GetNext()) {
    CIBlockElement::Delete($element['ID']);
}

?>
<h1>Импорт вакансий из источника .csv в инфоблок ID=<?= $IBLOCK_ID ?></h1>
<p>
    Источник: <?= "{$fileDir}{$fileName}" ?><br>
    Кодировка: <?= $encoding ?><br>
    Разделитель: <?= $separator ?>
</p>
<div>загрузка ...</div>
<?php
//Получение данных в массив с ключами - имена столбцов
$handle = fopen("{$fileDir}{$fileName}", "r");
if ($handle) {
    $row = 0;
    $keys = [];
    $data = [];
    while (($buffer = fgetcsv($handle, 1000, $separator)) !== false) {
        $row++;
        if ($row == 1) {
            $keys = $buffer;
        } else {
            $el = [];
            foreach ($buffer as $key => $item) {
                $item = trim($item);
                if (stripos($item, '•') !== false) {
                    $item = explode('•', $item);
                    array_splice($item, 0, 1);
                    //очистка item списка от пробелов
                    $item = array_map(fn($el) => trim($el), $item);
                }
                //подготовка полученных значений для записи
                $listCode = $fieldsPair[$keys[$key]];
                if ($listCode) {
                    //если есть список значений в таком виде (CODE) поля
                    //сравним схожесть значений из списка поля со значением из файла
                    foreach ($arProps[$listCode] as $listItem => $idListItem) {
                        if (stripos($item, $listItem) !== false || stripos($listItem, $item) !== false) {
                            $item = $idListItem;
                            break;
                        }
                        //TODO можно добавить правило находящее элементы по опр. степени схожести
                    }
                }
                $el[$keys[$key]] = $item;
            }
            $data[] = $el;
        }
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
    //Запись в Инфоблок
    $PROP = [];
    //Преобразуем элементы файла под запись в Инфоблок
    foreach ($data as $key => $el) {
        //для каждого свойства инфоблока получим значения из файла
        foreach ($arrCode as $code) {
            $PROP[$code] = $el[array_flip($fieldsPair)[$code]];

            //дополнительная обработка полей
            //если от свойства засисит значение другого свойства, применим правила заданные ранее в предположительном интерфейсе.
            if ($fieldsDependence[$code]) {
                //применим правила из базы правил rules
                $rule = $fieldsDependence[$code][1];
                [$val, $type] = ($rules[$rule]($PROP[$code]));
                $PROP[$code] = $val;
                $PROP[$fieldsDependence[$code][0]] = $type;
            }
        }

        $arLoadElement = [
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_ID" => $IBLOCK_ID,
            "IBLOCK_SECTION_ID" => false,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $el[$keys[$nameColumn]],
            "ACTIVE" => end($data) ? 'Y' : 'N',
        ];

        // Запись элемента в инфоблок
        if ($PRODUCT_ID = $vacancy->Add($arLoadElement)) {
            echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
        } else {
            echo "Error: " . $vacancy->LAST_ERROR . '<br>';
        }
    }
}
?>


