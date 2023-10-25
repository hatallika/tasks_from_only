<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<? if ($arResult["isFormNote"] === "Y"): ?>
    <?= $arResult["FORM_NOTE"] ?>
<? else: ?>
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
            <div class="contact-form__form-inputs">
                <? foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion): ?>
                    <? if ($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'text' || $arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'email'): ?>
                        <div class="input contact-form__input"><label class="input__label"
                                                                      for="<?= "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}" ?>">
                                <div class="input__label-text"><?= $arQuestion['CAPTION'] ?></div>
                                <input class="input__input <?= (($arResult['FORM_ERRORS'][$FIELD_SID]) && ($arResult["isFormErrors"] == "Y")) ? "invalid" : "" ?>"
                                       type="<?= $arQuestion['STRUCTURE'][0]['FIELD_TYPE'] ?>"
                                       id="<?= "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}" ?>"
                                       name="<?= "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}" ?>"
                                       value="<?= $arQuestion['STRUCTURE'][0]['VALUE'] ?>"
                                       required="">
                                <div class="input__notification"><?= $arResult['FORM_ERRORS'][$FIELD_SID] ?></div>

                            </label></div>
                    <? elseif ($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'hidden'): ?>
                        <?= $arQuestion["HTML_CODE"] ?>
                    <? endif; ?>
                <? endforeach; ?>
            </div>
            <div class="contact-form__form-message">
                <? foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion): ?>
                    <? if ($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'textarea'): ?>
                        <div class="input"><label class="input__label"
                                                  for="<?= "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}" ?>">
                                <div class="input__label-text">Сообщение</div>
                                <textarea
                                        class="input__input <?= (($arResult['FORM_ERRORS'][$FIELD_SID]) && ($arResult["isFormErrors"] == "Y")) ? "invalid" : "" ?>"
                                        type="text"
                                        id="<?= "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}" ?>"
                                        name="<?= "form_{$arQuestion['STRUCTURE'][0]['FIELD_TYPE']}_{$arQuestion['STRUCTURE'][0]['ID']}" ?>"
                                        value="<?= $arQuestion['STRUCTURE'][0]['VALUE'] ?>"></textarea>
                                <div class="input__notification"><?= $arResult['FORM_ERRORS'][$FIELD_SID] ?></div>
                            </label></div>
                    <? endif ?>
                <? endforeach; ?>
            </div>
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
        <!--        --><?php //var_dump($arResult);?>
        <?= $arResult["FORM_FOOTER"] ?>
    </div>
<? endif; ?>