<?php

use Bex\Bbc\Helpers\ComponentParameters;
use Bitrix\Iblock;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('bex.bbc')) return false;

global $USER_FIELD_MANAGER;

Loc::loadMessages(__FILE__);

try {
    ComponentParameters::includeModules(['iblock']);

    $iblockTypes = CIBlockParameters::GetIBlockTypes([0 => Loc::getMessage('SECTIONS_LIST_NOT_SELECTED')]);
    $iblocks = [0 => Loc::getMessage('SECTIONS_LIST_NOT_SELECTED')];
    $sections = [0 => Loc::getMessage('SECTIONS_LIST_ALL_SELECTED')];
    $property_UF = []; //[0 => '']; Loc::getMessage('SECTIONS_LIST_NOT_SELECTED')

    if (isset($arCurrentValues['IBLOCK_TYPE']) && strlen($arCurrentValues['IBLOCK_TYPE'])) {
        $rsIblocks = Iblock\IblockTable::getList([
            'order' => [
                'SORT' => 'ASC',
                'NAME' => 'ASC'
            ],
            'filter' => [
                'IBLOCK_TYPE_ID' => $arCurrentValues['IBLOCK_TYPE'],
                'ACTIVE' => 'Y'
            ],
            'select' => [
                'ID',
                'NAME'
            ]
        ]);

        while ($iblock = $rsIblocks->fetch()) {
            $iblocks[$iblock['ID']] = $iblock['NAME'];
        }
        unset($rsIblocks);
    }

    if (
        isset($arCurrentValues['IBLOCK_ID'])
        && is_numeric($arCurrentValues['IBLOCK_ID'])
        && intval($arCurrentValues['IBLOCK_ID']) > 0
    ) {
        $rsSections = Iblock\SectionTable::getList([
            'order' => [
                'LEFT_MARGIN' => 'asc',
                'SORT' => 'ASC',
                'NAME' => 'ASC'
            ],
            'filter' => [
                'IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'],
                'ACTIVE' => 'Y'
            ],
            'select' => [
                'ID',
                'NAME',
                'DEPTH_LEVEL'
            ]
        ]);

        while ($arSection = $rsSections->fetch()) {
            //Битриксы включают по числовым ключам. Сделаем строковые.
            $sections[$arSection['ID'] . ' '] = str_repeat(' . ', intval($arSection['DEPTH_LEVEL']) - 1)   . " [{$arSection['ID']}] {$arSection['NAME']}";
        }
        unset($rsSections);

        $rsUserFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $arCurrentValues['IBLOCK_ID'] . '_SECTION', 0, LANGUAGE_ID);

        foreach ($rsUserFields as $FIELD_NAME => $arUserField) {
            d($arUserField);
            $arUserField['LIST_COLUMN_LABEL'] = (string)$arUserField['LIST_COLUMN_LABEL'];

            $property_UF[$FIELD_NAME] =
                $arUserField['LIST_COLUMN_LABEL']
                ? '[' . $FIELD_NAME . ']' . $arUserField['LIST_COLUMN_LABEL']
                : $FIELD_NAME;
        }
        unset($rsUserFields);
    }

    $sortOrders = [
        'ASC' => Loc::getMessage('SECTIONS_LIST_SORT_ORDER_ASC'),
        'DESC' => Loc::getMessage('SECTIONS_LIST_SORT_ORDER_DESC')
    ];

    $arComponentParameters = [
        'GROUPS' => [
            'SORT' => [
                'NAME' => Loc::getMessage('SECTIONS_LIST_GROUP_SORT')
            ],
            'AJAX' => [
                'NAME' => Loc::getMessage('SECTIONS_LIST_GROUP_AJAX')
            ],
            'OTHERS' => [
                'NAME' => Loc::getMessage('SECTIONS_LIST_GROUP_OTHERS')
            ]
        ],
        'PARAMETERS' => [
            'IBLOCK_TYPE' => [
                'PARENT' => 'BASE',
                'NAME' => Loc::getMessage('SECTIONS_LIST_IBLOCK_TYPE'),
                'TYPE' => 'LIST',
                'VALUES' => $iblockTypes,
                'DEFAULT' => '',
                'REFRESH' => 'Y'
            ],
            'IBLOCK_ID' => [
                'PARENT' => 'BASE',
                'NAME' => Loc::getMessage('SECTIONS_LIST_IBLOCK_ID'),
                'TYPE' => 'LIST',
                'VALUES' => $iblocks,
                'REFRESH' => 'Y'
            ],
            'SECTION_ID' => [
                'PARENT' => 'BASE',
                'NAME' => Loc::getMessage('SECTIONS_LIST_SECTION_ID'),
                'TYPE' => 'LIST',
                'VALUES' => $sections
            ],
            'SORT_BY_1' =>            [
                'PARENT' => 'SORT',
                'NAME' => Loc::getMessage('SECTIONS_LIST_SORT_BY_1'),
                'TYPE' => 'LIST',
                'VALUES' => CIBlockParameters::GetSectionSortFields()
            ],
            'SORT_ORDER_1' => [
                'PARENT' => 'SORT',
                'NAME' => Loc::getMessage('SECTIONS_LIST_SORT_ORDER_1'),
                'TYPE' => 'LIST',
                'VALUES' => $sortOrders
            ],
            'SORT_BY_2' => [
                'PARENT' => 'SORT',
                'NAME' => Loc::getMessage('SECTIONS_LIST_SORT_BY_2'),
                'TYPE' => 'LIST',
                'VALUES' => CIBlockParameters::GetSectionSortFields()
            ],
            'SORT_ORDER_2' => [
                'PARENT' => 'SORT',
                'NAME' => Loc::getMessage('SECTIONS_LIST_SORT_ORDER_2'),
                'TYPE' => 'LIST',
                'VALUES' => $sortOrders
            ],
            'TOP_DEPTH' => [
                'PARENT' => 'DATA_SOURCE',
                'NAME' => Loc::getMessage('SECTIONS_LIST_TOP_DEPTH'),
                'TYPE' => 'STRING',
                'DEFAULT' => '2'
            ],
            'SELECT_FIELDS' => CIBlockParameters::GetSectionFieldCode(Loc::getMessage('SECTIONS_LIST_FIELDS'), 'DATA_SOURCE'),
            'SELECT_USER_FIELDS' => [
                'PARENT' => 'DATA_SOURCE',
                'NAME' => Loc::getMessage('SECTIONS_LIST_USER_FIELDS'),
                'TYPE' => 'LIST',
                'MULTIPLE' => 'Y',
                'VALUES' => $property_UF,
                'ADDITIONAL_VALUES' => 'Y'
            ],
            'EX_FILTER_NAME' => [
                'PARENT' => 'BASE',
                'NAME' => Loc::getMessage('SECTIONS_LIST_EX_FILTER_NAME'),
                'TYPE' => 'STRING',
                'DEFAULT' => ''
            ],
            'USE_AJAX' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('SECTIONS_LIST_USE_AJAX'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'Y'
            ],
            'AJAX_TYPE' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('SECTIONS_LIST_AJAX_TYPE'),
                'TYPE' => 'LIST',
                'VALUES' => [
                    'DEFAULT' => Loc::getMessage('SECTIONS_LIST_AJAX_TYPE_DEFAULT'),
                    'JSON' => Loc::getMessage('SECTIONS_LIST_AJAX_TYPE_JSON')
                ]
            ],
            'AJAX_HEAD_RELOAD' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('SECTIONS_LIST_AJAX_HEAD_RELOAD'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N'
            ],
            'AJAX_TEMPLATE_PAGE' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('SECTIONS_LIST_AJAX_TEMPLATE_PAGE'),
                'TYPE' => 'STRING',
                'DEFAULT' => ''
            ],
            'AJAX_COMPONENT_ID' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('SECTIONS_LIST_AJAX_COMPONENT_ID'),
                'TYPE' => 'STRING',
                'DEFAULT' => ''
            ],
            'DATE_FORMAT' => CIBlockParameters::GetDateFormat(
                Loc::getMessage('SECTIONS_LIST_DATE_FORMAT'),
                'OTHERS'
            ),
            'CACHE_GROUPS' => [
                'PARENT' => 'CACHE_SETTINGS',
                'NAME' => Loc::getMessage('SECTIONS_LIST_CACHE_GROUPS'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N'
            ],
            'CACHE_TIME' => [
                'DEFAULT' => 360000
            ]
        ]
    ];

    //CIBlockParameters::AddPagerSettings($arComponentParameters, Loc::getMessage('SECTIONS_LIST_NAV_TITLE'), true, true);
} catch (Exception $e) {
    ShowError($e->getMessage());
}
