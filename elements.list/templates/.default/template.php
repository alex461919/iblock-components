<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
?>

<div id="<?= $arParams['AJAX_COMPONENT_ID'] ?>">
    <? if ($arParams['DISPLAY_TOP_PAGER'] === 'Y') : ?>
        <div data-ajax-links>
            <?= $arResult['NAV_STRING'] ?>
        </div>
    <? endif ?>

    <? if (!empty($arResult['ELEMENTS'])) : ?>
        <ul>
            <? foreach ($arResult['ELEMENTS'] as $arElement) : ?>
                <li>
                    <div class="text-info"><?= "[{$arElement['ID']}] {$arElement['NAME']}" ?></div>
                </li>
            <? endforeach ?>
        </ul>
    <? endif ?>


    <? function showSection($arSection)
    {
    ?>
        <h6>
            <?= "[{$arSection['ID']}] level: {$arSection['DEPTH_LEVEL']} relative_level: {$arSection['RELATIVE_DEPTH_LEVEL']} count: {$arSection['ELEMENTS_COUNT']} total: {$arSection['TOTAL_ELEMENTS_COUNT']} <br /> {$arSection['NAME']}" ?>
        </h6>

        <? if (!empty($arSection['ELEMENTS'])) : ?>
            <? foreach ($arSection['ELEMENTS'] as $arElement) : ?>
                <div class="text-info"><?= "[{$arElement['ID']}] {$arElement['NAME']}" ?></div>
            <? endforeach; ?>
        <? endif; ?>

        <? if (!empty($arSection['CHILDREN'])) : ?>
            <ul>
                <? foreach ($arSection['CHILDREN'] as $arChildSection) : ?>
                    <li class="my-3">
                        <? showSection($arChildSection) ?>
                    </li>
                <? endforeach; ?>
            </ul>
    <? endif;
    } ?>

    <ul>
        <? foreach ($arResult['TREE_SECTIONS'] as $arSection) : ?>
            <li>
                <? showSection($arSection); ?>
            </li>
        <? endforeach; ?>
    </ul>


    <br>--------------------------------------------------------------------------<br>


    <?
    $depthLevel = 0;
    foreach ($arResult['SECTIONS'] as $arSection) {

        if ($arSection['RELATIVE_DEPTH_LEVEL'] > $depthLevel) {
            echo "<ul type='none' class=''>\n";
        }
        if ($arSection['RELATIVE_DEPTH_LEVEL'] < $depthLevel) {
            while ($arSection["DEPTH_LEVEL"] < $depthLevel) {
                echo "</li>\n</ul>\n";
                $depthLevel--;
            }
            echo "</li>\n";
        }
        if ($arSection['RELATIVE_DEPTH_LEVEL'] == $depthLevel) {
            echo "</li>\n";
        }
        echo "<li>\n";
    ?>
        <h6>
            <?= "[{$arSection['ID']}] level: {$arSection['DEPTH_LEVEL']} relative_level: {$arSection['RELATIVE_DEPTH_LEVEL']} count: {$arSection['ELEMENTS_COUNT']} total: {$arSection['TOTAL_ELEMENTS_COUNT']} <br /> {$arSection['NAME']}" ?>
        </h6>
        <? if (!empty($arSection['ELEMENTS'])) : ?>
            <ul>
                <? foreach ($arSection['ELEMENTS'] as $arElement) : ?>
                    <li>
                        <div class="text-info"><?= "[{$arElement['ID']}] {$arElement['NAME']}" ?></div>
                    </li>
                <? endforeach ?>
            </ul>
        <? endif ?>

    <?
        while ($arSection["RELATIVE_DEPTH_LEVEL"] < $depthLevel) {
            echo "</li>\n</ul>\n";
            $depthLevel--;
        }
        $depthLevel = $arSection['RELATIVE_DEPTH_LEVEL'];
    }
    ?>

    <? if ($arParams['DISPLAY_BOTTOM_PAGER'] === 'Y') : ?>
        <div data-ajax-links">
            <?= $arResult['NAV_STRING'] ?>
        </div>
    <? endif ?>
</div>

<script>
    BX.ready(function() {
        const ajaxParams = {
            AJAX_PARAM_NAME: '<?= $arParams['AJAX_PARAM_NAME'] ?>',
            AJAX_COMPONENT_ID: '<?= $arParams['AJAX_COMPONENT_ID'] ?>',
            AJAX_REQUEST_PARAMS: '<?= $arResult['AJAX_REQUEST_PARAMS'] ?>'
        };
        console.log('ajaxParams: ', ajaxParams)
        typeof window.setAjaxListeners === 'function' && setAjaxListeners(
            (p) => ajaxParams[p],
            () => {
                console.log('Success')
            });
    });
</script>