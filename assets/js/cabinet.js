/**
 * cabinet.js
 * Логика личного кабинета с историей выкроек.
 * - Подтверждение удаления выкройки (если реализовано).
 * - AJAX-загрузка дополнительных страниц (опционально).
 * - Копирование ссылки на скачивание.
 */

(function() {
    'use strict';

    const cabinetContainer = document.querySelector('.glp-cabinet');
    if (!cabinetContainer) return;

    // Обработка кнопок удаления (если добавлены)
    const deleteButtons = document.querySelectorAll('.glp-delete-pattern');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const patternId = this.dataset.id;
            if (!patternId) return;
            
            if (confirm('Вы уверены, что хотите удалить эту выкройку? Это действие нельзя отменить.')) {
                // AJAX запрос на удаление (требуется обработчик на сервере)
                fetch(glp_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'glp_delete_pattern',
                        pattern_id: patternId,
                        nonce: glp_ajax.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Удаляем строку из таблицы
                        const row = btn.closest('tr');
                        if (row) row.remove();
                        // Если таблица пуста, показать сообщение
                        const tbody = document.querySelector('.glp-table tbody');
                        if (tbody && tbody.children.length === 0) {
                            cabinetContainer.innerHTML = '<p>У вас пока нет сохранённых выкроек.</p>';
                        }
                    } else {
                        alert(data.data || 'Ошибка при удалении');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Произошла ошибка. Попробуйте позже.');
                });
            }
        });
    });

    // Копирование ссылки на скачивание (если есть кнопка "Поделиться")
    const copyLinks = document.querySelectorAll('.glp-copy-link');
    copyLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            if (!url) return;
            
            navigator.clipboard.writeText(url).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Скопировано!';
                setTimeout(() => { this.textContent = originalText; }, 2000);
            }).catch(err => {
                // Fallback
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('Ссылка скопирована в буфер обмена');
            });
        });
    });

    // Сортировка таблицы (если требуется) – можно реализовать позже
})();
