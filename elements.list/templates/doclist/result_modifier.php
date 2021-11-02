<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

if (!empty($arParams['DOWNLOAD_DOC_ID'])) {
    $this->__component->abortCache();
    while (ob_get_level() > 2) {
        ob_end_clean();
    }
    if (is_numeric($arParams['DOWNLOAD_DOC_ID']) && ($id = intval($arParams['DOWNLOAD_DOC_ID'])) > 0) {
        $rsProperties  =  CIBlockElement::GetProperty(
            $arParams['IBLOCK_ID'],
            $id,
            array(),
            array('CODE' => $arParams['FILE_PROPERTY_CODE'],  'CHECK_PERMISSION' => 'Y')
        );
        if ($arProperty = $rsProperties->GetNext()) {
            if (!empty($arFile = CFile::GetFileArray(intval($arProperty["VALUE"])))) {
                CIBlockElement::CounterInc($id);
                set_time_limit(0);
                CFile::ViewByUser($arFile, array("force_download" => true));
            }
        }
    }
    $arResult['DOWNLOAD_FILE_NOT_FOUND'] = 'Y';
    \Bitrix\Iblock\Component\Tools::process404(
        Loc::getMessage("DOCLIST_LIST_FILE_NOT_FOUND"),
        true,
        true,
        false
    );
    return;
}
foreach ($arResult['ELEMENTS'] as &$arElement) :

    if (
        !empty($arElement['DISPLAY_TIMESTAMP_X'])
        &&
        !empty($arElement['DISPLAY_DATE_CREATE'])
        &&
        $arElement['DATE_CREATE'] !== $arElement['TIMESTAMP_X']
    ) {
        $arElement['DISPLAY_DATE_UPDATE'] = $arElement['DISPLAY_TIMESTAMP_X'];
    }

    if (empty($file = $arElement['DISPLAY_PROPERTIES'][$arParams['FILE_PROPERTY_CODE']]['FILE_VALUE']))
        continue;
    $arElement["DOCFILE"] = array(
        "FILE_ID" => $file["ID"],
        "FILE_TYPE" => mb_strtolower(substr(
            $file["FILE_NAME"],
            strrpos($file["FILE_NAME"], '.') + 1
        ), SITE_CHARSET),
        "FILE_SIZE" => \CFile::FormatSize($file["FILE_SIZE"], 2),
    );
endforeach;
