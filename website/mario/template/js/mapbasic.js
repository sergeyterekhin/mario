function searchmap(){
var address=document.getElementById('addressdelivery').value;
if(!/^[0-9]+$/.test(address)) alert("Такого заказа нет!");
else ymaps.ready(['AnimatedLine']).then(init);
}

function init(ymaps) {
    // Создаем карту.
    var myMap = new ymaps.Map("map", {
        center: [55.030204, 82.920430],
        zoom: 14
    }, {
        searchControlProvider: 'yandex#search'
    });
    // Создаем ломаные линии.
    var firstAnimatedLine = new ymaps.AnimatedLine([
        [55.024975, 82.920953],
        [55.025077, 82.922044],
        [55.048884, 82.915690],
        [55.048513, 82.912015],
        [55.049008, 82.912174]
    ], {}, {
        // Задаем цвет.
        strokeColor: "#ED4543",
        // Задаем ширину линии.
        strokeWidth: 5,
        // Задаем длительность анимации.
        animationTime: 5000
    });
    // Добавляем линии на карту.
    myMap.geoObjects.add(firstAnimatedLine);
    // Создаем метки.
    var firstPoint = new ymaps.Placemark([55.024975, 82.920953], {}, {
        preset: 'islands#redRapidTransitCircleIcon'
    });
    var secondPoint = new ymaps.Placemark([55.049008, 82.912174], {}, {
        preset: 'islands#blueMoneyCircleIcon'
    });
    // Функция анимации пути.
    function playAnimation() {
        // Убираем вторую линию.
        // Добавляем первую метку на карту.
        myMap.geoObjects.add(firstPoint);
        // Анимируем первую линию.
        firstAnimatedLine.animate()
            // После окончания анимации первой линии добавляем вторую метку на карту и анимируем вторую линию.
            .then(function() {
                myMap.geoObjects.add(secondPoint);
                return ymaps.vow.delay(null, 2000);
            })
            .then(function() {
                // Удаляем метки с карты.
                myMap.geoObjects.remove(firstPoint);
                myMap.geoObjects.remove(secondPoint);
                // Убираем вторую линию.
                firstAnimatedLine.reset();
                // Перезапускаем анимацию.
                alert("ваш заказ прибыл!");
                myMap.destroy();
            });
    }
    // Запускаем анимацию пути.
    playAnimation();
}
