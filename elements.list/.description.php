<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

$arComponentDescription = array(
    'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_NAME'),
    'DESCRIPTION' => Loc::getMessage('KMIAC_ELEMENTS_LIST_DESCRIPTION'),
    'SORT' => 20,
    'PATH' => array(
        'ID' => 'kmiac',
        'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_GROUP'),
        'SORT' => 10,
        'CHILD' => array(
            'ID' => 'elements',
            'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_CHILD_GROUP'),
            'SORT' => 10
        )
    )
);
