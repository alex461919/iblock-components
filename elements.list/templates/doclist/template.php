<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

if (!function_exists('lcfirst_utf8')) {
    function lcfirst_utf8($str)
    {
        return mb_substr(mb_strtolower($str, 'utf-8'), 0, 1, 'utf-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'utf-8');
    }
}
$getElementBlock = function ($arItem = null): string {

    if (!$arItem || empty($arItem['DOCFILE'])) return '';

    $iconTitle = $arItem['NAME'];
    $iconAlt = $arItem['NAME'];
    if (is_array($arItem["PREVIEW_PICTURE"])) {
        if ($arItem["PREVIEW_PICTURE"]["WIDTH"] > 49 || $arItem["PREVIEW_PICTURE"]["HEIGHT"] > 51) {
            $iconResizeIMG = \CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], array('width' => 49, 'height' => 51), BX_RESIZE_IMAGE_PROPORTIONAL, false);
            $iconPath = $iconResizeIMG["src"];
        } else {
            $iconPath = $arItem["PREVIEW_PICTURE"]["SRC"];
        }
        $iconTitle = $arItem["PREVIEW_PICTURE"]["TITLE"];
        $iconAlt = $arItem["PREVIEW_PICTURE"]["ALT"];
    } else {
        $extPath = $this->__folder . '/images/' . $arItem['DOCFILE']["FILE_TYPE"] . '.png';
        $iconPath = file_exists($_SERVER["DOCUMENT_ROOT"] . $extPath)
            ? $extPath
            : $this->__folder . '/images/icon.png';
    }
    ob_start();
?>
    <article class="bt-row">
        <div class="bt-d-none bt-d-lg-block bt-col-auto">
            <img src="<?= $iconPath ?>" title="<?= $iconTitle ?>" alt="<?= $iconAlt ?>">
        </div>
        <div class="bt-col">

            <h3 class="bt-h6 bt-mb-1">
                <a href="?EID=<?= $arItem["ID"] ?>"><b><?= $arItem["NAME"] ?></b></a>
            </h3>

            <? if ($this->__component->arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"] != '') : ?>
                <div class="">
                    <?= $arItem["PREVIEW_TEXT"]; ?>
                </div>
            <? endif; ?>

            <div class="bt-mt-1">
                <a class="doclist-download-link" href="?EID=<?= $arItem["ID"] ?>"><?= Loc::getMessage("DOCLIST_DOWNLOAD_FILE") ?></a>
                <span class="doclist-size">
                    (<?= $arItem['DOCFILE']["FILE_TYPE"] . (($this->__component->arParams["DISPLAY_SIZE"] != "N") ? ", {$arItem['DOCFILE']["FILE_SIZE"]}" : "") ?>)
                </span>
            </div>

            <? if (($this->__component->arParams["DISPLAY_DATE"] != "N" && ($arItem["DISPLAY_DATE_CREATE"] || $arItem["DISPLAY_TIMESTAMP_X"])) || $this->__component->arParams["DISPLAY_DOWNLOAD_COUNT"] != "N") : ?>
                <div class="doclist-date">
                    <?
                    if ($this->__component->arParams["DISPLAY_DATE"] != "N" && ($arItem["DISPLAY_DATE_CREATE"] || $arItem["DISPLAY_TIMESTAMP_X"])) :
                        if (isset($arItem["DISPLAY_DATE_UPDATE"])) {
                            echo Loc::getMessage('DOCLIST_SHOW_DATA_UPDATE')  . ' ' . $arItem['DISPLAY_DATE_UPDATE'];
                        } else {
                            echo Loc::getMessage('DOCLIST_SHOW_DATA_CREATE')  . ' ' . $arItem['DISPLAY_DATE_CREATE'];
                        }
                        echo ($this->__component->arParams["DISPLAY_DOWNLOAD_COUNT"] != "N") ? " " : "";
                    endif;

                    if ($this->__component->arParams["DISPLAY_DOWNLOAD_COUNT"] != "N") : ?>
                        (<?= lcfirst_utf8(Loc::getMessage("DOCLIST_SHOW_DATA_DOWNLOAD")) ?>:
                        <? if ($arItem["SHOW_COUNTER"] == '') {
                            echo '0)';
                        } else {
                            echo $arItem["SHOW_COUNTER"] . ')';
                        } ?>
                    <? endif;
                    ?>
                </div>
            <? endif ?>
        </div>
    </article>
<?
    return ob_get_clean();
}
?>

<? if ($arResult['DOWNLOAD_FILE_NOT_FOUND'] !== 'Y') : ?>
    <div <?= !empty($arParams['AJAX_COMPONENT_ID']) ? "id={$arParams['AJAX_COMPONENT_ID']}" : '' ?>>
        <? if ($arParams["DISPLAY_TOP_PAGER"] && $arResult["NAV_STRING"]) : ?>
            <div class="bt-text-center bt-mb-5 bt-pb-3" data-ajax-links><?= $arResult["NAV_STRING"] ?></div>
        <? endif; ?>

        <ul type="none" class="bt-pl-0">
            <? foreach ($arResult['ELEMENTS'] as $arElement) :  ?>
                <? if (!empty($arElement['DOCFILE'])) : ?>
                    <li class="bt-py-3 doclist-item">
                        <?= $getElementBlock($arElement)  ?>
                    </li>
                <? endif ?>
            <? endforeach; ?>
        </ul>

        <? if ($arParams["DISPLAY_BOTTOM_PAGER"] && $arResult["NAV_STRING"]) : ?>
            <div class="bt-text-center bt-mt-5 bt-pt-3" data-ajax-links><?= $arResult["NAV_STRING"] ?></div>
        <? endif; ?>


    </div>

    <script>
        BX.ready(function() {
            const ajaxParams = {
                AJAX_PARAM_NAME: '<?= $arParams['AJAX_PARAM_NAME'] ?>',
                AJAX_COMPONENT_ID: '<?= $arParams['AJAX_COMPONENT_ID'] ?>',
                AJAX_REQUEST_PARAMS: '<?= $arResult['AJAX_REQUEST_PARAMS'] ?>'
            };
            typeof window.setAjaxListeners === 'function' && setAjaxListeners(
                (p) => ajaxParams[p],
                () => {
                    console.log('Success')
                });
        });
    </script>
<? endif; ?>