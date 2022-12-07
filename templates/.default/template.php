<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arResult array
 * @var $templateFolder
 * @var $APPLICATION
 */
use \Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
Extension::load('ui.bootstrap4');

$assets = Asset::getInstance();
$assets -> addCss($templateFolder . '/assets/fancybox.min.css');
$assets -> addJs(  $templateFolder . '/assets/fancybox.min.js');
?>
<div id="bot-container" class="bot">
    <?php /* BEGIN: BOT__HEADER */ ?>
        <div class="bot__header">
            <h1>
                <span>
                    <img src="<?= $templateFolder . '/images/robot.svg'; ?>"
                         alt="Обучающий бот Bitrix24" />
                            Обучающий бот Bitrix24
                </span>
            </h1>
        </div>
    <?php /* end: bot__header */ ?>
    <?php /* BEGIN: BOT__CONTENT */ ?>
        <div class="bot__content">
            <div class="bot__content_inner">
                <?php /* первый урок подгружается ajax */?>
            </div>
        </div>
    <?php /* end: bot__content */ ?>
    <?php /* BEGIN: PRELOADER */?>
        <div class="bot__preloader">
            <img src="<?= $templateFolder . '/images/balls_preloader.svg'?>" alt="Бот загружается" />
        </div>
    <?php /* end: preloader */?>
</div>
<?php $APPLICATION->SetTitle("Обучающий бот Bitrix24"); ?>