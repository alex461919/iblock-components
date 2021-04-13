<?php

/**
 * @link http://bbc.bitrix.expert
 * @copyright Copyright © 2014-2015 Nik Samokhvalov
 * @license MIT
 */

namespace Bex\Bbc\Components;

use Bex\Bbc;
use Bitrix\Main;
use Bitrix\Iblock;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!Main\Loader::includeModule('bex.bbc')) return false;

/**
 * Component for show elements list
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class ElementsList extends Bbc\Basis
{
    use Bbc\Traits\Elements;

    protected $needModules = ['iblock'];

    protected $arAllSections = [];
    protected $arTreeSections = [];

    protected $checkParams = [
        'IBLOCK_TYPE' => ['type' => 'string'],
        'IBLOCK_ID' => ['type' => 'int']
    ];

    protected static function getTrimmedParams($arParams)
    {
        return array_map(function ($v) {
            if (gettype($v) === 'string') return trim($v);
            if (gettype($v) === 'array') return array_filter(self::getTrimmedParams($v));
            return $v;
        }, $arParams);
    }

    protected function checkParams()
    {
        $this->arParams = self::getTrimmedParams($this->arParams);

        if ($this->arParams['IBLOCK_ID'] <= 0) {
            throw new Main\ArgumentOutOfRangeException('IBLOCK_ID', 1);
        }

        if ($this->arParams['DISPLAY_UNPARENT'] === 'Y') {
        } else {

            if (empty($this->arParams['SECTION_ID']))
                throw new Main\ArgumentNullException('SECTION_ID');

            if (is_numeric($this->arParams['SECTION_ID'])) {
                $this->arParams['SECTION_ID'] = [$this->arParams['SECTION_ID']];
            }

            if (!is_array($this->arParams['SECTION_ID']))
                throw new Main\ArgumentTypeException('SECTION_ID');
        }
    }
    protected function executeProlog()
    {
        $this->arParams['RESULT_PROCESSING_MODE'] = 'EXTENDED';
    }

    protected function executeMain()
    {
        AddMessage2Log('start executeMain');

        if ($this->arParams['DISPLAY_UNPARENT'] === 'Y') {

            $this->filterParams['SECTION_ID'] = false;
            unset($this->filterParams['INCLUDE_SUBSECTIONS']);
        } elseif ($this->arParams['CHECK_GLOBAL_ACTIVE'] !== 'N') {
            $this->filterParams['SECTION_GLOBAL_ACTIVE'] = 'Y';
        }

        if ($this->arParams['CHECK_DATES'] !== 'N') {
            $this->filterParams['ACTIVE_DATE'] = 'Y';
        }
        unset($this->filterParams['IBLOCK_TYPE']);

        $this->filterParams['IBLOCK_LID'] = SITE_ID;

        if ($this->arParams['DISPLAY_UNPARENT'] !== 'Y') {
            $arSelect = array_merge(
                [
                    'IBLOCK_ID',
                    'ID',
                    'NAME',
                    'DEPTH_LEVEL',
                    'IBLOCK_SECTION_ID',
                    'LEFT_MARGIN',
                    'RIGHT_MARGIN'
                ],
                $this->arParams['SELECT_SECTION_USER_FIELDS'],
                $this->arParams['SELECT_SECTION_FIELDS']
            );

            $arOrder = [];
            if (strlen($this->arParams['SECTIONS_SORT_BY_1']) > 0) {
                $arOrder[$this->arParams['SECTIONS_SORT_BY_1']] = $this->arParams['SECTIONS_SORT_ORDER_1'] ?: 'ASC';
            }
            if (strlen($this->arParams['SECTIONS_SORT_BY_2']) > 0) {
                $arOrder[$this->arParams['SECTIONS_SORT_BY_2']] = $this->arParams['SECTIONS_SORT_ORDER_2'] ?: 'ASC';
            }

            $arFilter = [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            ];
            if ($this->arParams['CHECK_GLOBAL_ACTIVE'] !== 'N') {
                $arFilter['GLOBAL_ACTIVE'] = 'Y';
            }

            $entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->arParams['IBLOCK_ID']);
            $rsSections = $entity::getList(array(
                'order' => $arOrder,
                'filter' => array_merge($arFilter, ['ID' => $this->arParams['SECTION_ID']]),
                'select' => $arSelect,
            ));

            $this->arAllSections = [];
            $arFilter[0] = ['LOGIC' => 'OR'];
            while ($arSection = $rsSections->fetch()) {
                $arFilter[0][] = [
                    '>=LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
                    '<=RIGHT_MARGIN' => $arSection['RIGHT_MARGIN'],
                ];
                $this->arAllSections[$arSection['ID']] = array_merge(
                    $arSection,
                    [
                        'ELEMENTS'  => [],
                        'CHILDREN' => [],
                        'ELEMENTS_COUNT' => 0
                    ]
                );
            }
            if ($this->filterParams['INCLUDE_SUBSECTIONS'] === 'Y') {
                $rsSections = $entity::getList(array(
                    'order' => $arOrder,
                    'filter' => $arFilter,
                    'select' => $arSelect,
                ));
                $this->arAllSections = [];
                while ($arSection = $rsSections->fetch()) {
                    $this->arAllSections[$arSection['ID']] = array_merge(
                        $arSection,
                        [
                            'ELEMENTS'  => [],
                            'CHILDREN' => [],
                            'ELEMENTS_COUNT' => 0
                        ]
                    );
                }
            }
        }


        $rsElements = \CIBlockElement::GetList(
            $this->getParamsSort(),
            $this->getParamsFilters(),
            $this->getParamsGrouping(),
            $this->getParamsNavStart(),
            $this->getParamsSelected([
                "ACTIVE_FROM",
                "TIMESTAMP_X",
                "DETAIL_PAGE_URL",
                "LIST_PAGE_URL",
                "DETAIL_TEXT",
                "DETAIL_TEXT_TYPE",
                "PREVIEW_TEXT",
                "PREVIEW_TEXT_TYPE",
                "PREVIEW_PICTURE",
            ])
        );

        $this->arResult['ELEMENTS'] = [];

        $processingMethod = $this->getProcessingMethod();

        while ($element = $rsElements->$processingMethod()) {
            if ($arElement = $this->processingElementsResult($element)) {

                $ipropValues = new Iblock\InheritedProperty\ElementValues($arElement["IBLOCK_ID"], $arElement["ID"]);
                $arElement["IPROPERTY_VALUES"] = $ipropValues->getValues();
                Iblock\Component\Tools::getFieldImageData(
                    $arElement,
                    array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
                    Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
                    'IPROPERTY_VALUES'
                );

                if (!empty($this->arParams['SELECT_PROPS'])) {
                    foreach ($this->arParams['SELECT_PROPS'] as $propCode) {
                        if (!empty($arElement['PROPS'][$propCode]))
                            $arElement["DISPLAY_PROPERTIES"][$propCode] = \CIBlockFormatProperties::GetDisplayValue($arElement, $arElement['PROPS'][$propCode]);
                    }
                }
                $this->arResult['ELEMENTS'][$arElement['ID']] = $arElement;
            }
        }
        if ($this->arParams['SET_404'] === 'Y' && empty($this->arResult['ELEMENTS'])) {
            $this->return404();
        }
        if ($this->arParams['DISPLAY_UNPARENT'] !== 'Y') {
            $arGroups = self::getElementGroups(array_keys($this->arResult['ELEMENTS']));

            foreach ($this->arResult['ELEMENTS'] as $id => $arElement) {
                foreach ($arGroups[$id] as $group) {
                    if (isset($this->arAllSections[$group])) {
                        $this->arAllSections[$group]['ELEMENTS'][$id] = $arElement;
                        $this->arAllSections[$group]['ELEMENTS_COUNT']++;
                    }
                }
            }
            $this->arTreeSections = $this->getTreeSections();

            $setTotalCount = function (array &$arSection, int $level = 1) use (&$setTotalCount): int {
                $arSection['ELEMENTS_COUNT'] = $total = count($arSection['ELEMENTS']);
                $arSection['RELATIVE_DEPTH_LEVEL'] = $level;
                if (!empty($arSection['CHILDREN'])) {
                    foreach ($arSection['CHILDREN'] as &$child) {
                        $total += $setTotalCount($child, $level + 1);
                    }
                }
                $arSection['TOTAL_ELEMENTS_COUNT'] = $total;
                return $total;
            };

            foreach ($this->arTreeSections as $id => &$arSection) {
                $setTotalCount($arSection);
            }
            $this->arResult['TREE_SECTIONS'] = $this->arTreeSections;
            $this->arResult['SECTIONS'] = $this->getPlainSections();
        }

        $this->generateNav($rsElements);
        $this->setResultCacheKeys(['NAV_CACHED_DATA']);
    }


    protected static function getElementGroups(array $arKeyElements): array
    {
        $groups = [];
        $rsGroups = \CIBlockElement::GetElementGroups(
            $arKeyElements,
            true,
            array("ID", "IBLOCK_ID", "IBLOCK_ELEMENT_ID")
        );
        while ($arGroup = $rsGroups->Fetch()) {
            $groups[$arGroup["IBLOCK_ELEMENT_ID"]][] = $arGroup["ID"];
        }
        return  $groups;
    }

    protected  function getTreeSections(): array
    {
        $arCopy = [];
        foreach ($this->arAllSections as $arSection) {
            $arCopy[$arSection['ID']] = $arSection;
        }
        $unset = [];
        foreach ($arCopy as $id => &$arSection) {
            if ($arSection['IBLOCK_SECTION_ID'] && isset($arCopy[$arSection['IBLOCK_SECTION_ID']])) {
                $arCopy[$arSection['IBLOCK_SECTION_ID']]['CHILDREN'][$id] = &$arSection;
                $unset[] = $id;
            }
        }
        foreach ($unset as $k) {
            unset($arCopy[$k]);
        }
        return $arCopy;
    }

    protected  function getPlainSections(array $arSection = null): array
    {
        $arResult = [];
        if (!$arSection) {

            foreach ($this->arTreeSections as $k => $v) {
                $arResult += [$k => $v];
                $arResult += $this->getPlainSections($v);
            }
        } else {
            $arResult += [$arSection['ID'] => $arSection];
            if (!empty($arSection['CHILDREN'])) {
                foreach ($arSection['CHILDREN'] as $arChild) {
                    $arResult += $this->getPlainSections($arChild);
                }
            }
        }
        return $arResult;
    }
}
