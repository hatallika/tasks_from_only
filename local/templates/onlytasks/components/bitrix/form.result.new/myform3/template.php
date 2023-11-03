<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
if ($arResult["isFormNote"] !== "Y") {
    //Универсальный шаблон формы, с неизвестным количеством полей.
    //В рамках шаблона, сгруппируем пользовательские вопросы на блоки: узкие поля типа text, email в одной стороне, широкие в другой.
    //если после широкого поля ввода задано снова узкое, формируем новый блок из левых и правых полей
    // text, email | textarea
    // text, text, text | textarea
    $strIndex = 0;
    $prev = 'left';
    $current = 'left';
    $strArr = [[], []]; // textInputs on the right, textarea - on the left

    foreach ($arResult['QUESTIONS'] as $FIELD_SID => $arQuestion) {

        $obj = [
            'name' => "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}",
            'caption' => $arQuestion['CAPTION'] . ((($arQuestion['REQUIRED']) === 'Y') ? '*' : ''),
            'class' => (($arResult['FORM_ERRORS'][$FIELD_SID]) && ($arResult["isFormErrors"] == "Y")) ? "invalid" : "",
            'type' => $arQuestion['STRUCTURE'][0]['FIELD_TYPE'],
            'value' => $arQuestion['STRUCTURE'][0]['VALUE'],
            'error' => $arResult['FORM_ERRORS'][$FIELD_SID],
            'html' => $arQuestion["HTML_CODE"],
        ];

        if ($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'text' || $arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'email') {
            $current = 'left';
            if ($current !== $prev) {
                $strIndex++;
            }
            $strArr[$strIndex][0][] = $obj;
        } else {
            $current = 'right';
            $strArr[$strIndex][1][] = $obj;
        }
        $prev = $current;
    }
}
?>
<? if ($arResult["isFormNote"] !== "Y"): ?>
    <div class="contact-form">
        <?= $arResult["FORM_HEADER"] ?>
        <input type="hidden" name="web_form_submit" value="Y">
        <div class="contact-form__head">
            <? if ($arResult["isFormDescription"] == "Y" || $arResult["isFormTitle"] == "Y"): ?>
                <? if ($arResult["isFormTitle"]): ?>
                    <div class="contact-form__head-title"><?= $arResult["FORM_TITLE"] ?></div>
                <? endif; ?>
                <? if ($arResult["isFormDescription"]): ?>
                    <div class="contact-form__head-text"><?= $arResult["FORM_DESCRIPTION"] ?></div>
                <? endif; ?>
            <? endif; ?>
        </div>
        <div class="contact-form__form">

            <? foreach ($strArr as $str): ?>
                <div class="contact-form__form-inputs">
                    <? foreach ($str[0] as $obj): ?>
                        <div class="input contact-form__input">
                            <label class="input__label" for="<?= $obj['name'] ?>">
                                <div class="input__label-text">
                                    <?= $obj['caption'] ?>
                                </div>
                                <input class="input__input <?= $obj['class'] ?>"
                                       type="<?= $obj['type'] ?>"
                                       id="<?= $obj['name'] ?>"
                                       name="<?= $obj['name'] ?>"
                                       value="<?= $obj['value'] ?>"
                                       required="">
                                <div class="input__notification"><?= $obj['error'] ?></div>
                            </label>
                        </div>
                    <? endforeach; ?>
                </div>
                <div class="contact-form__form-message">
                    <? foreach ($str[1] as $obj): ?>
                        <? if ($obj['type'] == 'textarea'): ?>
                            <div class="input">
                                <label class="input__label" for="<?= $obj['name'] ?>">
                                    <div class="input__label-text">
                                        <?= $obj['caption'] ?>
                                    </div>
                                    <textarea
                                            class="input__input  <?= $obj['class'] ?>"
                                            type="<?= $obj['type'] ?>"
                                            id="<?= $obj['name'] ?>"
                                            name="<?= $obj['name'] ?>"
                                            value="<?= $obj['value'] ?>">
                                    </textarea>
                                    <div class="input__notification"><?= $arResult['FORM_ERRORS'][$FIELD_SID] ?></div>
                                </label>
                            </div>
                        <? elseif ($obj['type'] === 'hide'): ?>
                            <?= $obj['html'] ?>
                        <? elseif ($obj['type'] === 'radio' || $obj['type'] === 'checkbox'): ?>
                            <div class="select">
                                <div class="input__label-text">
                                    <?= $obj['caption'] ?>
                                </div>
                                <?= $obj['html'] ?>
                            </div>
                        <? elseif ($obj['type'] === 'dropdown'): ?>
                            <div class="select">
                                <div class="input__label-text">
                                    <?= $obj['caption'] ?>
                                </div>
                                <?= $obj['html'] ?>
                            </div>
                        <? endif; ?>
                    <? endforeach; ?>
                </div>
            <? endforeach; ?>

            <div class="contact-form__bottom">
                <div class="contact-form__bottom-policy">Нажимая &laquo;Отправить&raquo;, Вы&nbsp;подтверждаете, что
                    ознакомлены, полностью согласны и&nbsp;принимаете условия &laquo;Согласия на&nbsp;обработку
                    персональных
                    данных&raquo;.
                </div>
                <button class="form-button contact-form__bottom-button" data-success="Отправлено"
                        data-error="Ошибка отправки">
                    <div class="form-button__title"><?= $arResult["arForm"]["BUTTON"] ?></div>
                </button>
            </div>
        </div>
        <?= $arResult["FORM_FOOTER"] ?>
    </div>
<? else: ?>
    <div><?= $arResult["FORM_NOTE"] ?></div>
<? endif; ?>
