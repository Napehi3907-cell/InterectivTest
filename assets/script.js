document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы
    const eyesCanvas = document.getElementById('eyes-canvas');
    const earsCanvas = document.getElementById('ears-canvas');
    const noseCanvas = document.getElementById('nose-canvas');

    const infoItems = document.querySelectorAll('#info-items li');
    const checkBtn = document.getElementById('check-btn');
    const resetBtn = document.getElementById('reset-btn');
    const saveBtn = document.getElementById('save-btn');
    const results = document.getElementById('results');
    const scoreText = document.getElementById('score-text');
    const medal = document.getElementById('medal');

    // Переменные для отслеживания соединений
    let connections = {};
    let selectedOrgan = null;
    let isDrawing = false;
    let ctxEyes, ctxEars, ctxNose;

    // Инициализация канвасов
    function initCanvases() {
        ctxEyes = eyesCanvas.getContext('2d');
        ctxEars = earsCanvas.getContext('2d');
        ctxNose = noseCanvas.getContext('2d');

        // Устанавливаем стили по умолчанию
        [ctxEyes, ctxEars, ctxNose].forEach(ctx => {
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
        });
    }

    initCanvases();

    // Функция для рисования на канвасе
    function setupDrawing(canvas, ctx) {
        let lastX = 0;
        let lastY = 0;

        function startDrawing(e) {
            isDrawing = true;
            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        function draw(e) {
            if (!isDrawing) return;
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();
            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        function stopDrawing() {
            isDrawing = false;
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
    }

    // Настройка рисования для каждого канваса
    setupDrawing(eyesCanvas, ctxEyes);
    setupDrawing(earsCanvas, ctxEars);
    setupDrawing(noseCanvas, ctxNose);

    // Логика соединения органов чувств с видами информации
    infoItems.forEach(item => {
        item.addEventListener('click', function() {
            if (selectedOrgan) {
                const organId = selectedOrgan.id.replace('-canvas', '');
                connections[organId] = this.getAttribute('data-info');
                this.classList.add('selected');
                selectedOrgan = null;
                drawConnection(organId, this.getAttribute('data-info'));
            }
        });
    });

    // Обработчики для выбора органа
    [eyesCanvas, earsCanvas, noseCanvas].forEach(canvas => {
        canvas.addEventListener('click', function() {
            selectedOrgan = this;
            // Сбрасываем выделение с других элементов
            infoItems.forEach(item => item.classList.remove('selected'));
        });
    });

    // Функция отрисовки соединений (стрелок)
    function drawConnection(organ, infoType) {
        const organBlock = document.getElementById(`${organ}-block`);
        const infoItem = Array.from(infoItems).find(item =>
            item.getAttribute('data-info') === infoType
        );

        if (!organBlock || !infoItem) return;

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.classList.add('connection-line');
        document.body.appendChild(svg);

        const organRect = organBlock.getBoundingClientRect();
        const infoRect = infoItem.getBoundingClientRect();

        const startX = organRect.right;
        const startY = organRect.top + organRect.height / 2;
        const endX = infoRect.left;
        const endY = infoRect.top + infoRect.height / 2;

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', startX);
        line.setAttribute('y1', startY);
        line.setAttribute('x2', endX);
        line.setAttribute('y2', endY);
        svg.appendChild(line);
    }

    // Проверка правильности выполнения задания
    checkBtn.addEventListener('click', function() {
        let score = 0;
        const correctConnections = {
            'eyes': 'visual',
            'ears': 'sound',
            'nose': 'smell'
        };

        // Проверка соединений
        Object.keys(connections).forEach(organ => {
            if (connections[organ] === correctConnections[organ]) {
                score++;
                // Помечаем правильные ответы
                Array.from(infoItems).forEach(item => {
                    if (item.getAttribute('data-info') === connections[organ]) {
                item.classList.add('correct');
            }
                });
            } else {
                Array.from(infoItems).forEach(item => {
                    if (item.getAttribute('data-info') === connections[organ]) {
                item.classList.add('incorrect');
            }
                });
            }
        });

        // Проверка рисунков (упрощённая — считаем, что если что‑то нарисовано, то правильно)
        [ctxEyes, ctxEars, ctxNose].forEach(ctx => {
            const imageData = ctx.getImageData(0, 0, ctx.canvas.width, ctx.canvas.height);
            const data = imageData.data;
            let hasDrawing = false;
            for (let i = 0; i < data.length; i += 4) {
                if (data[i] !== 0 || data[i + 1] !== 0 || data[i + 2] !== 0) {
                    hasDrawing = true;
            break;
                }
            }
            if (hasDrawing) score++;
        });

        // Оценка аккуратности (если нет пересекающихся стрелок)
        if (Object.keys(connections).length === 3) {
            score++; // Упрощённая логика — считаем, что стрелок не пересекаются
        }

        // Отображение результатов
        scoreText.textContent = `Ваш результат: ${score} из 7 баллов`;

        // Медаль в зависимости от результата
        if (score >= 6) {
            medal.textContent = '🏆 Золотая медаль';
            medal.style.color = 'gold';
        } else if (score >= 4) {
            medal.textContent = '🥈 Серебряная медаль';
            medal.style.color = 'silver';
        } else {
            medal.textContent = '🥉 Бронзовая медаль';
            medal.style.color = '#cd7f32';
        }

        results.style.display = 'block';
    });

    // Сброс задания
    resetBtn.addEventListener('click', function() {
        // Очищаем канвасы
        [ctxEyes, ctxEars, ctxNose].forEach(ctx => {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        });
        // Сбрасываем соединения
        connections = {};
        selectedOrgan = null;
        // Убираем выделение и классы
        infoItems.forEach(item => {
            item.classList.remove('selected', 'correct', 'incorrect');
        });
        // Удаляем все стрелки
        document.querySelectorAll('.connection-line').forEach(el => el.remove());
        // Скрываем результаты
        results.style.display = 'none';
    });

    // Сохранение работы
    saveBtn.addEventListener('click', function() {
        alert('Работа сохранена! (В реальной реализации здесь будет код для сохранения изображения)');
    });
});
