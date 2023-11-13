<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?echo LANG_CHARSET;?>">
<meta property="og:image" content="<?$APPLICATION->ShowProperty('og:image');?>">
<?$APPLICATION->ShowMeta("keywords");?>
<?$APPLICATION->ShowMeta("description");?>
<title><?$APPLICATION->ShowTitle()?></title>
<?$APPLICATION->ShowHead()?>
</head>
<body>
<?$APPLICATION->ShowPanel();?>
<?$APPLICATION->IncludeComponent(
    "bitrix:breadcrumb",
    "",
    array(
        "PATH" => "",
        "SITE_ID" => "s1",
        "START_FROM" => "0"
    )
);
?>

<!-- #Begin_Article -->
