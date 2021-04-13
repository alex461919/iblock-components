<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

$arComponentDescription = array(
    'NAME' => Loc::getMessage('SECTIONS_LIST_NAME'),
    'DESCRIPTION' => Loc::getMessage('SECTIONS_LIST_DESCRIPTION'),
    'SORT' => 20,
    'PATH' => array(
        'ID' => 'KMIAC',
        'SORT' => 10,
        'CHILD' => array(
            'ID' => 'iblock',
            'NAME' => Loc::getMessage('IBLOCK_CHILD_GROUP'),
            'SORT' => 10
        )
    )
);
