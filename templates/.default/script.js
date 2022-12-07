let Course = (function() {

    const Bot = {
        botContainer: '#bot-container',
        botContainerInner: ".bot__content_inner",
        bot: ".bot",
        botButton: ".bot__button",
        botPreloader: ".bot__preloader",
        botLesson: ".bot__lesson",
        botButtonNextLesson: ".bot__button_next-lesson",
        botButtonFirstStart: ".button-first_start",
        botButtonFirstContinue: ".button-first_continue",
    };
    let init = function() {
        $(document).ready(function() {
            // при первой загрузке страницы, если есть learned lessons отобразим 2 кнопки (сначала хочет человек или продолжить с того же урока)
            if(BX.getCookie("learnedlessons")) {
                showTwoButtonsAtTheBeginning();
            } else {
                uploadNextLesson();
            }
            setTimeout(preloaderHideOpacity, 3000);
            setTimeout(preloaderHideDisplay, 4000);
        });
        $(document).on('click',Bot.botButtonNextLesson,uploadNextLesson);
        $(document).on('click',Bot.botButtonFirstStart,startFromTheBeginning);
        $(document).on('click',Bot.botButtonFirstContinue,continueStudying);
    };

    /* метод отображает единственный урок */
    let uploadNextLesson = function(e) {
        // покажем прилоудер при загрузке нового урока
        preloadShowOpactiy();
        preloadShowDisplay();

        let lesson;

        // если это не первая загрузка, то this.dataset не будет пустым
        if(this.dataset) {
            lesson = this.dataset.lesson;
            this.classList.remove("bot__button_next-lesson");

            // при нажатии <следующий> передадим в cookie идентификатор последнего урока, пройденного пользователем
            setCookieLastStudiedLesson(lesson);
        } else {
            lesson = 0;
        }



        BX.ajax.runComponentAction('DevConsult:bot','getlesson',{
            mode: 'class',
            data: {
                lesson: lesson
            },
        }).then(function(response) {
            // покажем сразу preloader
            preloadShowOpactiy();
            preloadShowDisplay();

            // добавим урок
            $(".bot__content_inner").append(response.data.LESSON_HTML);

            // получим DOM текущего добавленного урока
            let currentLesson = document.getElementById(response.data.LESSON_ID);

            // получим top и left позиции в браузере текущего загруженного урока
            let top = (currentLesson.offsetTop + 120);
            let left = currentLesson.offsetLeft;

            // скроллим до текущего загруженного элемента
            setTimeout(function() {
                window.scrollTo(
                    {top: top,left: left,behavior: 'smooth'}
                );
            }, 1000);

            // получим все bot__cell текущего currentLesson,
            // они при загрузке у нас должны быть невидимыми opacity = 0,
            // но должны показываться по очереди с небольшим запозданием каждый
            let botCells = $(currentLesson).find(".bot__cell");
            let timeCounter = 250;
            botCells.each(function() {
                setTimeout(
                    ()=>{ $(this).animate({opacity: 1},250) },
                    timeCounter
                );
                timeCounter += 250;
            });

            // скроем preloader после загрузки нового урока
            setTimeout(preloaderHideOpacity, 3000);
            setTimeout(preloaderHideDisplay, 3000);

        },function(response) {

            alert('Ошибка');
        });
}

    /* метод устанавливает cookie learnedlessons */
    let setCookieLastStudiedLesson = function(lesson) {
        // Получим текущий cookie learnedlessons и добавим новый урок в него
        if(!(BX.getCookie("learnedlessons"))) {
             // если learnedlessons не существует создадим переменную learnedlessons, массив с первым элементом lesson
             let learnedlessons = [];
             learnedlessons.push(lesson);
             BX.setCookie(
                 "learnedlessons",
                 JSON.stringify(learnedlessons),
                 {expires: 36000000}
             );
        } else {
             let learnedlessons = JSON.parse(BX.getCookie("learnedlessons"));
             learnedlessons.push(lesson);

             // меняем значение cookie learnedlessons, добавляя туда новый урок и вновь устанавливаем
             BX.setCookie(
                 "learnedlessons",
                 JSON.stringify(learnedlessons),
                 {expires: 36000000}
             );
        }
    }

    /* метод показывает 2 кнопки НАЧАТЬ СНАЧАЛА и ПРОДОЛЖИТЬ */
    let showTwoButtonsAtTheBeginning = function() {

       BX.ajax.runComponentAction('DevConsult:bot', 'showTwoButtonsAtTheBeginning', {
            mode: 'class', //это означает, что мы хотим вызывать действие из class.php
            data: {}
        }).then(function (response) {
            $(".bot__content_inner").append(response.data);
            $(".button-first").animate({
                opacity: 1,
                top: 0
            },750);
        }, function (response) {
            alert('При загрузке произошла ошибка, пожалуйста, обратитесь к администратору');
        });
    }

    /* метод обрабатывает нажатие на кнопку НАЧАТЬ СНАЧАЛА */
    let startFromTheBeginning = function() {
        BX.setCookie("learnedlessons","",{expires: "-1"});
        $(".bot__content_inner").html("");
        uploadNextLesson();
    }

    /* метод обрабатывает кнопку ПРОДОЛОЖИТЬ и показывает, все пройденные пользователем занятия */
    let continueStudying = function() {
        // покажем прилоудер
            preloadShowOpactiy();
            preloadShowDisplay();
        // проверить наличие идентификаторов уроков в cookie learnedlessons
            let learnedlessons = JSON.parse(BX.getCookie('learnedlessons'));
        // сделаем ajax запрос и получим все уроки с идентификаторами, заданными в cookie-learnedlessons
            BX.ajax.runComponentAction("DevConsult:bot","getLearnedLessons",{
                mode: 'class',
                data: {
                    learnedlessons: learnedlessons
                }
            }).then(function(response) {
                // скроем preloader
                    setTimeout(preloaderHideOpacity, 3000);
                    setTimeout(preloaderHideDisplay, 3000);
                // очистим все рабочее пространство и загрузим в него полученные уроки
                    $(Bot.botContainerInner).html("");
                    $(Bot.botContainerInner).append(response.data.LESSON_HTML);
                    // скроем все кнопки на следующий урок для всех уроков
                    $(Bot.botButton).removeClass("bot__button_next-lesson");

                    // нам необходимо отобразить кнопку для последнего урока, т.е. необходимо вычислить последний урок и к кнопке этого последнего урока дообавить класс bot__button_next-lesson
                    let lastLesson = $(Bot.botLesson)[($(Bot.botLesson).length - 1)];
                    let lastLessonButton = ($(lastLesson).find(".bot__button"))[0];
                    debugger;
                    lastLessonButton.classList.add("bot__button_next-lesson");

                    // получим все bot__cell всех уроков
                    // они при загрузке у нас должны быть невидимыми opacity = 0,
                    // но должны показываться по очереди с небольшим запозданием каждый
                    // если для одного урока мы показываем задержку 500, то для всех уроков 25
                    let botCells = $(".bot__cell");
                    let timeCounter = 25;
                    botCells.each(function() {
                        setTimeout(
                            ()=>{ $(this).animate({opacity: 1},25) },
                            timeCounter
                        );
                        timeCounter += 25;
                    });

                    // скроллим до текущего загруженного элемента
                    setTimeout(function() {
                        // получим DOM текущего добавленного урока
                        let currentLessonID = document.getElementById(response.data.LESSON_ID);

                        // получим top и left позиции в браузере текущего загруженного урока
                        let top = currentLessonID.offsetTop + 120;
                        let left = currentLessonID.offsetLeft;

                        window.scrollTo(
                            {top: top,left: left,behavior: 'smooth'}
                        );
                    }, 500);

            }),function(error) {
                alert('Проишозла ошибка');
            };
    }














    /* begin: show hide functions */
    let preloaderHideOpacity = function() {$(".bot__preloader").addClass("bot__preloader_hidden-opacity");}
    let preloaderHideDisplay = function() {$(".bot__preloader").addClass("bot__preloader_hidden-display");}

    let preloadShowOpactiy = function() {$(".bot__preloader").removeClass("bot__preloader_hidden-opacity");}
    let preloadShowDisplay = function() {$(".bot__preloader").removeClass("bot__preloader_hidden-display");}
    /* end: show hide functions */

    return {
        init: init
    };

})();

Course.init();
