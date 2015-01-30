# Grabber
Grab sites &amp; load to Drupal 7

## Работа чезез прокси
* Автоматическое обновление списка прокси серверов
* Случайный выбор прокси из списка
* Исключение нерабочих прокси

## Подстановка UserAgent
3990 разных реальных UserAgent
* Chrome
* Firefox
* Internet Explorer
* Safari
* Opera
 
## Многопоточность
Насколько это возможно реализована многопоточность средствами PHP. Сканер запускает сам себя несколько раз. При запуске выставляет флаг-файл и блокирует его, что позволяет определять запущен ли процесс. Когда скрипт завершает выполнение блокировка автоматически снимается.

Для работы с разными сайтами используются отдельные сканеры. Сканер - сайт. Они также запускатся параллельно.

## Загрузка в ноды
* Находится нода
* Определяются ее поля
** картинка - загружается файл
** таксономия - находится термин таксономии, подставляется id или добавляется новый
** field collection - заполняются соответствующие поля
** дата - дата
** текст - текст
** список - список
* Если ноды нет - создается. Есть - обновляется

## Комментарии
Аналогично загрузке в ноды. Находится нода. В ноде находится комментарий. Обновляется. Либо добавляется новый. Для комментариев поддерживаются все поля, что и для ноды. При необходимости автоматически создаются учетные записи пользователей - авторов комментариев.

## Кэширование
Изображения кэширутся. С целью ускорения повторной загрузки. Кэшируются внутренние объекты при работе граббера.

## Наполнители
Информация с разных источников автоматически объединяется.

Например, для сборщика с киносайтов источнком информации о фильме является kinopoisk.ru. Оттуда берется картинка, описание, трейлер. Но расписание в каждом кинотеатре разное. Расписание сеансов берется с сайта кинотеатра. Данные объединяются.

## Обход 30 секундного ограничения
Для обхода 30 секундного ограничения на выполнения PHP-скрипта используется очередь. Ссылки для извлечения данных сохраняются в очередь. Извлеченные данные также сохранются в очередь для последующей обработки, фильтрации.

## Оптимизация загрузки
Для одной ноды бывает несколько блоков данных с разных источников. Чтобы ускорить самую медленную часть процесса - загрузку в ноды - порции данных предназначенные для одной ноды объединяются.

Поля объединяются, пустые заполняются. 
field collectin аналогично.

## Сканеры
* Готовые
* Добавить
* Заказать
 
## Документация
Схема


![alt text](https://raw.githubusercontent.com/vital-fadeev/grabber/master/css/overview.png "Scheme")
