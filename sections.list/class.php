<?php

/**
 * @link http://bbc.bitrix.expert
 * @copyright Copyright © 2014-2015 Nik Samokhvalov
 * @license MIT
 */

namespace Bex\Bbc\Components;

use Bitrix\Iblock;
use Bitrix\Main;

use Bex\Bbc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('bex.bbc')) return false;

/**
 * Component for show elements list
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class SectionList extends Bbc\Basis
{
    use Bbc\Traits\Elements;

    protected $needModules = ['iblock'];

    protected $checkParams = [
        'IBLOCK_TYPE' => ['type' => 'string'],
        'IBLOCK_ID' => ['type' => 'int']
    ];
    protected $arAllSections = [];

    protected function getSortedSections(int $parentID = null): array
    {
        $result = [];
        foreach ($this->arAllSections as $arSection) {
            if ((!$parentID && !$arSection['IBLOCK_SECTION_ID'])
                || ($parentID == $arSection['IBLOCK_SECTION_ID'])
            ) {
                $result[$arSection['ID']] = $arSection;
                foreach ($this->getSortedSections($arSection['ID']) as $s) {
                    $result[$s['ID']] = $s;
                }
            }
        }
        return $result;
    }

    protected static function trimParams(&$arParams)
    {
        array_walk($arParams, function (&$arParam) {
            if (gettype($arParam) === 'string') $arParam = trim($arParam);
            if (gettype($arParam) === 'array') self::trimParams($arParam);
        });
    }
    protected function checkParams()
    {
        self::trimParams($this->arParams);

        if (is_numeric($this->arParams['IBLOCK_TYPE'])) {
            throw new Main\ArgumentTypeException('IBLOCK_TYPE', 'string');
        }
        if (intval($this->arParams['IBLOCK_ID'] <= 0)) {
            throw new Main\ArgumentTypeException('IBLOCK_ID', 'integer');
        }
        $this->arParams['IBLOCK_ID'] = intval($this->arParams['IBLOCK_ID']);

        if (!empty($this->arParams['SECTION_ID'])) {
            if (!is_numeric($this->arParams['SECTION_ID'])) {
                throw new Main\ArgumentTypeException('SECTION_ID', 'integer');
            }
            $this->arParams['SECTION_ID'] = intval($this->arParams['SECTION_ID']);
        } else {
            $this->arParams['SECTION_ID'] = 0;
        }

        if (!empty($this->arParams['TOP_DEPTH'])) {
            if (!is_numeric($this->arParams['TOP_DEPTH'])) {
                throw new Main\ArgumentTypeException('TOP_DEPTH', 'integer');
            }
            $this->arParams['TOP_DEPTH'] = intval($this->arParams['TOP_DEPTH']);
        } else {
            $this->arParams['TOP_DEPTH'] = 0;
        }
    }

    protected function executeProlog()
    {
        $this->filterParams = array_merge(
            $this->filterParams,
            [
                'GLOBAL_ACTIVE' => 'Y',
            ],
            $this->arParams["TOP_DEPTH"] ? ['<=DEPTH_LEVEL' =>  $this->arParams["TOP_DEPTH"]] : []
        );

        if ($this->arParams['SECTION_ID'] > 0) {
            $rsSections = \CIBlockSection::GetByID($this->arParams['SECTION_ID']);
            if ($arSection = $rsSections->GetNext()) {
                $this->arResult['SECTION'] = $arSection;
                $this->filterParams = array_merge(
                    $this->filterParams,
                    [
                        'LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
                        'RIGHT_MARGIN' => $arSection['RIGHT_MARGIN']
                    ],
                    $this->arParams["TOP_DEPTH"] ? ['<=DEPTH_LEVEL' =>  $arSection['DEPTH_LEVEL'] +  $this->arParams["TOP_DEPTH"]] : []

                );
                unset($this->filterParams['SECTION_ID']);
            }
        }
    }

    protected function executeMain()
    {
        d($this->filterParams);

        $rsSections = \CIBlockSection::GetList(
            array_merge(['DEPTH_LEVEL' => 'asc'], $this->getParamsSort()),
            $this->getParamsFilters(),
            false,
            $this->getParamsSelected(array_merge(
                array_filter($this->arParams['SELECT_USER_FIELDS']),
                ['DEPTH_LEVEL', 'SECTION_PAGE_URL']
            )),
            false
        );

        while ($arSection = $rsSections->GetNext()) {
            $arSection['RELATIVE_DEPTH_LEVEL'] = $arSection['DEPTH_LEVEL']
                - (is_array($this->arResult['SECTION']) ? $this->arResult['SECTION']['DEPTH_LEVEL'] : 0);
            $this->arAllSections[$arSection['ID']] = $arSection;
        }
        //------------------------------------------------------------------------------------------
        // Сортируем секции
        $this->arResult['SECTIONS'] = $this->getSortedSections($this->arParams['SECTION_ID']);

        d($this->arResult);
    }
}
