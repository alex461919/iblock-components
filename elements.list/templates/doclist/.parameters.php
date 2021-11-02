<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arTemplateParameters = array(
    'FILE_PROPERTY_CODE' => array(
        'PARENT' => 'DATA_SOURCE',
        "NAME" => Loc::getMessage('DOCLIST_LIST_FILE_PROPERTY_CODE'),
        "TYPE" => "LIST",
        "VALUES" => array_filter(array_combine($arCurrentValues['SELECT_PROPS'], $arCurrentValues['SELECT_PROPS'])),
    ),
    'DOWNLOAD_DOC_ID' => array(
        'PARENT' => 'DATA_SOURCE',
        'NAME' => GetMessage('DOCLIST_LIST_DOWNLOAD_DOC_ID'),
        'TYPE' => 'STRING',
        'DEFAULT' => '={$_REQUEST["EID"]}'
    ),
    'DISPLAY_DATE' => [
        'PARENT' => 'ADDITIONAL_SETTINGS',
        'NAME' => Loc::getMessage('DOCLIST_LIST_DESC_DISPLAY_DATE'),
        'TYPE' => 'CHECKBOX',
        'DEFAULT' => 'Y'
    ],
    'DISPLAY_DOWNLOAD_COUNT' => [
        'PARENT' => 'ADDITIONAL_SETTINGS',
        'NAME' => Loc::getMessage('DOCLIST_LIST_DESC_DOWNLOAD_COUNT'),
        'TYPE' => 'CHECKBOX',
        'DEFAULT' => 'Y'
    ],
    'DISPLAY_SIZE' => [
        'PARENT' => 'ADDITIONAL_SETTINGS',
        'NAME' => Loc::getMessage('DOCLIST_LIST_DESC_DISPLAY_SIZE'),
        'TYPE' => 'CHECKBOX',
        'DEFAULT' => 'Y'
    ]

);
