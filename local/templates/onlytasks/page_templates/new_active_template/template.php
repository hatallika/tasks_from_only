<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

CPageTemplate::IncludeLangFile(__FILE__);

class CMyNewPageTemplate
{
    function GetDescription()
    {
        return array(
            "name" => GetMessage("bt_wizard_name"),
            "description" => GetMessage("bt_wizard_title"),
        );
    }

// Описываем новый шаг мастера создания страницы
    function GetFormHtml()
    {

// Первый вопрос
        $s = '
<tr class="section">
    <td>'.GetMessage("BT_TYPE_1").'</td>
</tr>
';

// Варианты ответа на первый вопрос
        $s .= '
<tr>
    <td style="vertical-align: top; padding-top:10px">
        <input type="radio" name="BT_COL_1" value="1_1" id="BT_COL_1_1" checked>
        <label for="BT_COL_1_1">'.GetMessage("BT_COL_1_1").'</label><br>
    </td>
</tr>
<tr>
    <td style="padding-top:10px">
        <input type="radio" name="BT_COL_1" value="1_2" id="BT_COL_1_2">
        <label for="BT_COL_1_2">'.GetMessage("BT_COL_1_2").'</label><br>
    </td>
</tr>
';

// Второй вопрос
        $s .= '
<tr class="section">
    <td>'.GetMessage("BT_TYPE_2").'</td>
</tr>
';

// Варианты ответа на второй вопрос
        $s .= '
<tr>
    <td style="vertical-align: top; padding-top:10px">
        <input type="radio" name="BT_COL_2" value="2_1" id="BT_COL_2_1" checked>
        <label for="BT_COL_2_1">'.GetMessage("BT_COL_2_1").'</label><br>
    </td>
</tr>
<tr>
    <td style="padding-top:10px">
        <input type="radio" name="BT_COL_2" value="2_2" id="BT_COL_2_2">
        <label for="BT_COL_2_2">'.GetMessage("BT_COL_2_2").'</label><br>
    </td>
</tr>
';

        return $s;
    }

// Описываем шаблон в зависимости от выбранных вариантов
    function GetContent($arParams)
    {

// Начало шаблона Объявление
        $myNewHtml = '
<h3>Объявление!</h3>
<hr>
<br>
<table cellpadding="10" cellspacing="1" align="center" style="width: 500px;">
<tbody>
<tr>
    <td>';

// Изменение шаблона в зависимости от ответа на первой вопрос об изображении
        if (isset($_POST['BT_COL_1']))
        {
            switch ($_POST['BT_COL_1'])
            {
                case '1_1':
                {
                    $myNewHtml.= '
          <img width="131" src="/upload/warning.png" height="157">';
                    break;
                }
                case '1_2':
                {
                    $myNewHtml.= '
';
                    break;
                }
            }
        }


// Продолжение шаблона
        $myNewHtml .= '
    </td>
    <td>
         Внимание! Важная информация о [<i>внесите нужную информацию</i>].<br>
         ...
    </td>
</tr>
</tbody>
</table>
 <br>';

// Изменение шаблона в зависимости от ответа на второй вопрос об обратной связи
        if (isset($_POST['BT_COL_2']))
        {
            switch ($_POST['BT_COL_2'])
            {
                case '2_1':
                {
                    $myNewHtml.= '
<hr>
 <span style="color: #555555;"><i>Напишите нам, что Вы думаете об этом объявлении. Для этого воспользуйтесь формой обратной связи. Спасибо!</i></span><br>
 <br>
<?$APPLICATION->IncludeComponent(
    "bitrix:main.feedback",
    "",
    Array(
        "EMAIL_TO" => "sale@192.168.100.177",
        "EVENT_MESSAGE_ID" => array("7"),
        "OK_TEXT" => "Спасибо за Ваше мнение!",
        "REQUIRED_FIELDS" => array("NAME","EMAIL"),
        "USE_CAPTCHA" => "Y"
    )
);?>';
                    break;
                }
                case '2_2':
                {
                    $myNewHtml.= '
';
                    break;
                }
            }
        }
        $myNewHtml.= '
</div>
';

// Формируем готовый шаблон
        $s = '<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>';
        $s.= $myNewHtml;
        $s.= '<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>';
        return $s;
    }
}

$pageTemplate = new CMyNewPageTemplate;
?>
