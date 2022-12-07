<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/*
 * @var $componentPath
 */
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
Loader::includeModule("iblock");

class Bot extends \CBitrixComponent implements Controllerable {
	public $IBLOCK_ID = 21;		// Инфоблок, в котором содержатся все курсы
    public $IBLOCK_TYPE = "education";
	public $COURSE_ID = 20;		// Корневой раздел инфоблока курсов
	public $IBLOCK_API_CODE = 'courses'; // Символьный код api для инфоблока

    public function configureActions()
    {

    }

    public function showTwoButtonsAtTheBeginningAction() {
        $lessonHtml = "<div class='start-or-continue'>";
            $lessonHtml .= "<p>Здравствуйте, хотите пройти обучение сначала или продолжить?</p>";
        $lessonHtml .= "</div>";

        $lessonHtml .= '<button class="button-first button-first_start">';
            $lessonHtml .= "Начать сначала";
        $lessonHtml .= '</button>';

        $lessonHtml .= '<button class="button-first button-first_continue">';
            $lessonHtml .= "Продолжить обучение";
        $lessonHtml .= '</button>';

        return $lessonHtml;
    }

    public function getlessonAction($lesson) {
        // в переменной $lesson содержится идентификатор урока, который нужно получить из базы и добавить
        // если эта переменная отсутствует, то берем первый урок, скорее всего это первая загрузка

        if($lesson == 0) {
            $lesson = \Bitrix\Iblock\ElementTable::getList([
                'select' => ['ID'],
                'filter' => [
                    'IBLOCK_ID' => $this->IBLOCK_ID,
                    'IBLOCK_SECTION_ID' => $this->COURSE_ID,
                    'PREVIEW_TEXT' => 'первый урок'
                ]
            ])->fetch()['ID'];
        }

        $lessonArray = [];
        $lessonArray = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['*'],
            'filter' => [
                'ACTIVE' => 'Y',
                'ID' => $lesson,
                'IBLOCK_ID' => $this->IBLOCK_ID,
                'IBLOCK_SECTION_ID' => $this->COURSE_ID,
            ],
            'limit' => 1
        ])->fetchAll()[0];

        $dbProperties = \CIBlockElement::GetProperty(
            $this->IBLOCK_ID,
            $lessonArray['ID'],
            [],
            []
        );

        $properties = [];
        while($prop = $dbProperties -> Fetch()) {
            $properties[$prop['CODE']][] = $prop;
        }

        $lessonArray['PROPERTIES'] = $properties;


        // создание html вывода урока
        $lessonHtml = "<div id='" . $lessonArray['CODE'] . "_" . $lessonArray['ID'] . "' class='bot__lesson'>";
            // messages
            foreach($lessonArray['PROPERTIES']['MESSAGE'] as $message)
            {
                $pattern = '/<img/';
                $patterntwo = '/vk.com/';

                if(preg_match($pattern,$message['VALUE']['TEXT'])) {
                    $lessonHtml .= '<div class="bot__cell bot__message-img">';
                        $lessonHtml .= $message['VALUE']['TEXT'];
                    $lessonHtml .= '</div>';
                } elseif(preg_match($patterntwo,$message['VALUE']['TEXT'])) {
                    $lessonHtml .= '
                        <div class="bot__cell bot__message-img relation">
                            <div class="relation__ratio"></div>
                            <iframe src="' . $message['VALUE']['TEXT'] . '" allow="autoplay; encrypted-media; picture-in-picture;" frameborder="0" speed="x2" disallowfullscreen></iframe>
                        </div>  
                    ';
                } else {
                    $lessonHtml .= '<div class="bot__cell bot__message">';
                        $lessonHtml .= $message['VALUE']['TEXT'];
                    $lessonHtml .= '</div>';
                }


            }

            if($lessonArray['PROPERTIES']['SHOW_VK_TELEGRAM_LINKS'][0]['VALUE_ENUM'] === "Y") {
                // создадим ссылки на телеграм канал и vk группу, если это не самый первый урок
                $lessonHtml .= "<div class='bot_social bot__message bot__social'>";
                $lessonHtml .= "<span>Что-то не получилось? Пишите в наше дружное сообщество: </span>";
                $lessonHtml .= "<a target='_blank' href='https://vk.com/club214584917'><img style='width: 32px;' alt='vk' src='" . $this->getPath() . "/templates/.default/images/vk.svg' /></a>";
                $lessonHtml .= "<a target='_blank' href='https://t.me/bitrix24LearningBot'><img style='width: 32px;' alt='телеграм' src='" . $this->getPath() . "/templates/.default/images/telegram.svg' /></a>";
                $lessonHtml .= "</div>";
            }

            // ссылка на редактирование для админа
            global $USER;
            if($USER->IsAdmin() && $USER->IsAuthorized()) {
                $linkEdit = "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . $this->IBLOCK_ID . "&type=" . $this->IBLOCK_TYPE . "&ID=" . $lessonArray['ID'];
                $linkAdd = "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . $this->IBLOCK_ID . "&type=" . $this->IBLOCK_TYPE;
                $lessonHtml .= "<div><a target='_blank' href='" . $linkEdit . "'>Редактировать урок</a><br/><br/></div>";
                $lessonHtml .= "<div><a target='_blank' href='" . $linkAdd . "'>Добавить урок</a><br/><br/></div>";
            }



        // buttons
            if(count($lessonArray['PROPERTIES']['BUTTON_NAME_LINK_FOR_LESSON'][0]['VALUE'])>0)
            {
                foreach($lessonArray['PROPERTIES']['BUTTON_NAME_LINK_FOR_LESSON'] as $button)
                {
                    $lessonHtml .= '<div class="bot__cell bot__buttons">';
                        $lessonHtml .= '<button data-lesson="' . $button['DESCRIPTION'] . '" class="bot__button bot__button_next-lesson">';
                            $lessonHtml .= $button['VALUE'];
                        $lessonHtml .= '</button>';
                    $lessonHtml .= '</div>';
                    if(!$button['DESCRIPTION']) {
                        $lessonHtml .= "<div>У этого урока еще нет продолжения</div>";
                    }
                }
            }
        $lessonHtml .= "</div>";

        $arResult['LESSON_PROPERTIES'] = $lessonArray['PROPERTIES'];
        $arResult['LESSON_ID'] = $lessonArray['CODE'] . "_" . $lessonArray['ID'];
        $arResult['LESSON_HTML'] = $lessonHtml;
        return $arResult;
    }

    public function getLearnedLessonsAction($learnedlessons) {
        $lessonsHtml = "";
        $result = ["LESSON_HTML" => "", "LESSON_ID" => ""];

        foreach($learnedlessons as $singlelesson) {
            $singleHtmlLesson = $this->getlessonAction($singlelesson);
            $lessonsHtml .= $singleHtmlLesson['LESSON_HTML'];
            $result['LESSON_ID'] = $singleHtmlLesson['LESSON_ID'];
        }

        $result['LESSON_HTML'] = $lessonsHtml;
        return $result;
    }

	public function executeComponent() {

     $this->includeComponentTemplate();
	}
}
