<?php

use Bex\Bbc\Helpers\ComponentParameters;
use Bitrix\Iblock;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('bex.bbc')) return false;

Loc::loadMessages(__FILE__);

global $USER_FIELD_MANAGER;

function array_insert_after(array $array, $key, array $new)
{
    $keys = array_keys($array);
    $index = array_search($key, $keys);
    $pos = false === $index ? count($array) : $index + 1;

    return array_merge(array_slice($array, 0, $pos), $new, array_slice($array, $pos));
}

try {
    ComponentParameters::includeModules(['iblock']);

    $iblockTypes = CIBlockParameters::GetIBlockTypes([0 => Loc::getMessage('KMIAC_ELEMENTS_LIST_NOT_SELECTED')]);
    $iblocks = [[0 => Loc::getMessage('KMIAC_ELEMENTS_LIST_NOT_SELECTED')]];
    $sections = []; //[0 => ''];
    $property_UF = []; //[0 => '']; Loc::getMessage('KMIAC_ELEMENTS_LIST_NOT_SELECTED')

    $elementProperties = [];

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
    }

    if (isset($arCurrentValues['IBLOCK_ID']) && strlen($arCurrentValues['IBLOCK_ID'])) {
        $rsSections = Iblock\SectionTable::getList([
            'order' => [
                'LEFT_MARGIN' => 'asc',
            ],
            'filter' => [
                'IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'],
                'GLOBAL_ACTIVE' => 'Y'
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

        $rsProperties = CIBlockProperty::GetList(
            [
                'sort' => 'asc',
                'name' => 'asc'
            ],
            [
                'ACTIVE' => 'Y',
                'IBLOCK_ID' => $arCurrentValues['IBLOCK_ID']
            ]
        );

        while ($property = $rsProperties->Fetch()) {
            $elementProperties[$property['CODE']] = '[' . $property['CODE'] . '] ' . $property['NAME'];
        }

        $rsUserFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $arCurrentValues['IBLOCK_ID'] . '_SECTION', 0, LANGUAGE_ID);
        foreach ($rsUserFields as $FIELD_NAME => $arUserField) {
            //d($arUserField);
            $arUserField['LIST_COLUMN_LABEL'] = (string)$arUserField['LIST_COLUMN_LABEL'];

            $property_UF[$FIELD_NAME] =
                $arUserField['LIST_COLUMN_LABEL']
                ? '[' . $FIELD_NAME . ']' . $arUserField['LIST_COLUMN_LABEL']
                : $FIELD_NAME;
        }
        unset($rsUserFields);
    }

    $sortOrders = [
        'ASC' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_ORDER_ASC'),
        'DESC' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_ORDER_DESC')
    ];

    $arComponentParameters = [
        'GROUPS' => [

            'SECTIONS_LIST' => [
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_GROUP_SECTIONS_LIST'),
                'SORT' => '250'
            ],
            'ELEMENTS_LIST' => [
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_GROUP_ELEMENTS_LIST'),
                'SORT' => '260'
            ],
            'AJAX' => [
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_GROUP_AJAX')
            ],
            'SEO' => [
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_GROUP_SEO')
            ],
            'OTHERS' => [
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_GROUP_OTHERS')
            ]
        ],
        'PARAMETERS' => [
            'IBLOCK_TYPE' => [
                'PARENT' => 'BASE',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_IBLOCK_TYPE'),
                'TYPE' => 'LIST',
                'VALUES' => $iblockTypes,
                'DEFAULT' => '',
                'REFRESH' => 'Y'
            ],
            'IBLOCK_ID' => [
                'PARENT' => 'BASE',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_IBLOCK_ID'),
                'TYPE' => 'LIST',
                'VALUES' => $iblocks,
                'REFRESH' => 'Y'
            ],
            'CHECK_DATES' => [
                'PARENT' => 'DATA_SOURCE',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_CHECK_DATES'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'Y'
            ],
            'DISPLAY_UNPARENT' => [
                'PARENT' => 'DATA_SOURCE',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_DISPLAY_UNPARENT'),
                'TYPE' => 'CHECKBOX',
                'REFRESH' => 'Y',
                'DEFAULT' => 'Y'
            ],
            'SORT_BY_1' => [
                'PARENT' => 'ELEMENTS_LIST',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_BY_1'),
                'TYPE' => 'LIST',
                'VALUES' => CIBlockParameters::GetElementSortFields(),
                'DEFAULT' => 'SORT'
            ],
            'SORT_ORDER_1' => [
                'PARENT' => 'ELEMENTS_LIST',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_ORDER_1'),
                'TYPE' => 'LIST',
                'VALUES' => $sortOrders
            ],
            'SORT_BY_2' => [
                'PARENT' => 'ELEMENTS_LIST',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_BY_2'),
                'TYPE' => 'LIST',
                'VALUES' => CIBlockParameters::GetElementSortFields(),
                'DEFAULT' => 'SORT'
            ],
            'SORT_ORDER_2' => [
                'PARENT' => 'ELEMENTS_LIST',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_ORDER_2'),
                'TYPE' => 'LIST',
                'VALUES' => $sortOrders
            ],
            'SELECT_FIELDS' => CIBlockParameters::GetFieldCode(Loc::getMessage('KMIAC_ELEMENTS_LIST_FIELDS'), 'ELEMENTS_LIST'),
            'SELECT_PROPS' => [
                'PARENT' => 'ELEMENTS_LIST',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_PROPERTIES'),
                'TYPE' => 'LIST',
                'MULTIPLE' => 'Y',
                'VALUES' => $elementProperties,
                'ADDITIONAL_VALUES' => 'Y',
            ],
            /*
            'RESULT_PROCESSING_MODE' => [
                'PARENT' => 'DATA_SOURCE',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_RESULT_PROCESSING_MODE'),
                'TYPE' => 'LIST',
                'VALUES' => [
                    'DEFAULT' => Loc::getMessage('KMIAC_ELEMENTS_LIST_RESULT_PROCESSING_MODE_DEFAULT'),
                    'EXTENDED' => Loc::getMessage('KMIAC_ELEMENTS_LIST_RESULT_PROCESSING_MODE_EXTENDED')
                ]
            ],
            */
            'EX_FILTER_NAME' => [
                'PARENT' => 'DATA_SOURCE',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_EX_FILTER_NAME'),
                'TYPE' => 'STRING',
                'DEFAULT' => ''
            ],
            'PAGER_SAVE_SESSION' => [
                'PARENT' => 'PAGER_SETTINGS',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_NAV_SAVE_SESSION'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N'
            ],
            'ELEMENTS_COUNT' => [
                'PARENT' => 'PAGER_SETTINGS',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_ELEMENTS_COUNT'),
                'TYPE' => 'STRING',
                'DEFAULT' => '10'
            ],
            'USE_AJAX' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_USE_AJAX'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'Y'
            ],
            'AJAX_TYPE' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_AJAX_TYPE'),
                'TYPE' => 'LIST',
                'VALUES' => [
                    'DEFAULT' => Loc::getMessage('KMIAC_ELEMENTS_LIST_AJAX_TYPE_DEFAULT'),
                    'JSON' => Loc::getMessage('KMIAC_ELEMENTS_LIST_AJAX_TYPE_JSON')
                ]
            ],
            'AJAX_HEAD_RELOAD' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_AJAX_HEAD_RELOAD'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N'
            ],
            'AJAX_TEMPLATE_PAGE' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_AJAX_TEMPLATE_PAGE'),
                'TYPE' => 'STRING',
                'DEFAULT' => ''
            ],
            'AJAX_COMPONENT_ID' => [
                'PARENT' => 'AJAX',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_AJAX_COMPONENT_ID'),
                'TYPE' => 'STRING',
                'DEFAULT' => ''
            ],
            'SET_SEO_TAGS' => [
                'PARENT' => 'SEO',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SET_SEO_TAGS'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'Y'
            ],
            'ADD_SECTIONS_CHAIN' => [
                'PARENT' => 'SEO',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_ADD_SECTIONS_CHAIN'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'Y'
            ],
            'SET_404' => [
                'PARENT' => 'OTHERS',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SET_404'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N'
            ],
            'CHECK_PERMISSIONS' => [
                'PARENT' => 'OTHERS',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_CHECK_PERMISSIONS'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'Y'
            ],
            'DATE_FORMAT' => CIBlockParameters::GetDateFormat(
                Loc::getMessage('KMIAC_ELEMENTS_LIST_DATE_FORMAT'),
                'OTHERS'
            ),
            'CACHE_GROUPS' => [
                'PARENT' => 'CACHE_SETTINGS',
                'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_CACHE_GROUPS'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N'
            ],
            'CACHE_TIME' => [
                'DEFAULT' => 360000
            ]
        ]
    ];

    if ($arCurrentValues['DISPLAY_UNPARENT'] !== 'Y') {

        $arComponentParameters['PARAMETERS'] =
            array_insert_after(
                $arComponentParameters['PARAMETERS'],
                'DISPLAY_UNPARENT',
                [
                    'SECTION_ID' => [
                        'PARENT' => 'DATA_SOURCE',
                        'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SECTION_ID'),
                        'TYPE' => 'LIST',
                        'MULTIPLE' => 'Y',
                        'VALUES' => $sections,
                    ],
                    'INCLUDE_SUBSECTIONS' => [
                        'PARENT' => 'DATA_SOURCE',
                        'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_INCLUDE_SUBSECTIONS'),
                        'TYPE' => 'CHECKBOX',
                        'DEFAULT' => 'Y'
                    ],
                    'CHECK_GLOBAL_ACTIVE' => [
                        'PARENT' => 'DATA_SOURCE',
                        'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_CHECK_GLOBAL_ACTIVE'),
                        'TYPE' => 'CHECKBOX',
                        'DEFAULT' => 'Y'
                    ],
                ]
            );

        $arComponentParameters['PARAMETERS']['SECTIONS_SORT_BY_1'] = [
            'PARENT' => 'SECTIONS_LIST',
            'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_BY_1'),
            'TYPE' => 'LIST',
            'VALUES' => CIBlockParameters::GetSectionSortFields(),
            'DEFAULT' => 'SORT'
        ];
        $arComponentParameters['PARAMETERS']['SECTIONS_SORT_ORDER_1'] = [
            'PARENT' => 'SECTIONS_LIST',
            'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_ORDER_1'),
            'TYPE' => 'LIST',
            'VALUES' => $sortOrders
        ];
        $arComponentParameters['PARAMETERS']['SECTIONS_SORT_BY_2'] =  [
            'PARENT' => 'SECTIONS_LIST',
            'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_BY_2'),
            'TYPE' => 'LIST',
            'VALUES' => CIBlockParameters::GetSectionSortFields(),
            'DEFAULT' => 'SORT'
        ];
        $arComponentParameters['PARAMETERS']['SECTIONS_SORT_ORDER_2'] =  [
            'PARENT' => 'SECTIONS_LIST',
            'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_SORT_ORDER_2'),
            'TYPE' => 'LIST',
            'VALUES' => $sortOrders
        ];

        $arComponentParameters['PARAMETERS']['SELECT_SECTION_FIELDS'] =
            CIBlockParameters::GetSectionFieldCode(Loc::getMessage('KMIAC_ELEMENTS_LIST_FIELDS'), 'SECTIONS_LIST');
        $arComponentParameters['PARAMETERS']['SELECT_SECTION_USER_FIELDS'] = [
            'PARENT' => 'SECTIONS_LIST',
            'NAME' => Loc::getMessage('KMIAC_ELEMENTS_LIST_USER_FIELDS'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => $property_UF,
            'ADDITIONAL_VALUES' => 'Y'
        ];
    }

    CIBlockParameters::AddPagerSettings($arComponentParameters, Loc::getMessage('KMIAC_ELEMENTS_LIST_NAV_TITLE'), true, true);
} catch (Exception $e) {
    ShowError($e->getMessage());
}
