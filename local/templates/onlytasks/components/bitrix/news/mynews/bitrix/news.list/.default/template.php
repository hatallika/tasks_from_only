<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<h1><?= $arResult['SECTION_DATA']['NAME']; ?></h1>
<?php if (!empty($arResult['SECTION_DATA']['CHILDS'])): /* подразделы текущего раздела */ ?>
    <ul class="blog-subsections">
        <?php foreach ($arResult['SECTION_DATA']['CHILDS'] as $arSection): ?>
            <li><a href="<?= $arSection['SECTION_PAGE_URL'] ?>"><?= $arSection['NAME'] ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php else:?>
    <a href="<?=$arParams['IBLOCK_URL']?>">Вернуться к разделам</a>


<?php endif; ?>

<div id="barba-wrapper">

    <div class="article-list">

        <? if ($arParams["DISPLAY_TOP_PAGER"]): ?>
            <?= $arResult["NAV_STRING"] ?><br/>
        <? endif; ?>

        <? foreach ($arResult["ITEMS"] as $key => $arItem): ?>
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
            ?>


            <a class="article-item article-list__item" href="<?= (
                !$arParams["HIDE_LINK_WHEN_NO_DETAIL"]
                || ($arItem["DETAIL_TEXT"] && $arResult["USER_HAVE_ACCESS"])
            )
                ? $arItem["DETAIL_PAGE_URL"]
                : "#" ?>"
               id="<?= $this->GetEditAreaId($arItem['ID']); ?>"
               data-anim="anim-3">
                <div class="article-item__background">
                    <? if ($arParams["DISPLAY_PICTURE"] != "N" && is_array($arItem["PREVIEW_PICTURE"])): ?>
                        <img src="<?= $arItem["PREVIEW_PICTURE"]["SRC"] ?>"
                             alt="<?= $arItem["PREVIEW_PICTURE"]["ALT"] ?>"/>
                    <? else: ?>
                        <img src="<?= "$templateFolder/images/article-item-bg-" . $key % 6 + 1 . ".jpg" ?>"
                             data-src="xxxHTMLLINKxxx0.39186223192351520.41491856731872767xxx"
                             alt="bg-article-<?=$key % 6 + 1?>"/>
                    <? endif ?>
                </div>
                <div class="article-item__wrapper">
                    <div class="article-item__title"><? echo $arItem["NAME"] ?></div>
                    <div class="article-item__content">
                        <? if ($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]): ?>
                            <? echo $arItem["PREVIEW_TEXT"]; ?>
                        <? endif; ?>
                    </div>
                </div>
            </a>

        <? endforeach; ?>
        <? if ($arParams["DISPLAY_BOTTOM_PAGER"]): ?>
            <br/><?= $arResult["NAV_STRING"] ?>
        <? endif; ?>
    </div>
</div>
