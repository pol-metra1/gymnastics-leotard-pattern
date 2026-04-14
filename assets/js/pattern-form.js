/**
 * pattern-form.js
 * Логика формы построения выкройки купальника.
 * - Клиентская валидация полей.
 * - Условное отображение дополнительных параметров (рукав, юбка).
 * - Сохранение введённых данных в sessionStorage.
 * - Подтверждение перед отправкой (предупреждение о незаполненных полях).
 */

(function() {
    'use strict';

    const form = document.getElementById('glp-pattern-form');
    if (!form) return;

    // Элементы управления условными полями
    const sleeveSelect = form.querySelector('select[name="SleeveType"]');
    const skirtCheckbox = form.querySelector('input[name="Skirt"]');
    const skirtOptions = form.querySelector('.skirt-options'); // предполагаем обёртку с классом

    // Поля, которые появляются при выборе рукава
    const sleeveFields = form.querySelectorAll('.sleeve-dependent');
    // Поля, которые появляются при выборе юбки
    const skirtFields = form.querySelectorAll('.skirt-dependent');

    // Сохранение данных формы в sessionStorage
    const STORAGE_KEY = 'glp_pattern_form_draft';

    /**
     * Сохранить текущие значения полей в sessionStorage
     */
    function saveFormDraft() {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            // Не сохраняем файлы, пароли и т.п. (здесь их нет)
            if (key !== '_wpnonce' && key !== 'action') {
                data[key] = value;
            }
        }
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    /**
     * Загрузить сохранённые данные и заполнить форму
     */
    function loadFormDraft() {
        const saved = sessionStorage.getItem(STORAGE_KEY);
        if (!saved) return;
        try {
            const data = JSON.parse(saved);
            for (let key in data) {
                const field = form.querySelector(`[name="${key}"]`);
                if (!field) continue;
                if (field.type === 'checkbox') {
                    field.checked = data[key] === '1' || data[key] === 'true';
                } else if (field.type === 'select-one') {
                    field.value = data[key];
                } else {
                    field.value = data[key];
                }
            }
            // После заполнения обновить видимость зависимых полей
            toggleDependentFields();
        } catch (e) {
            console.warn('Ошибка загрузки черновика формы', e);
        }
    }

    /**
     * Очистить черновик после успешной отправки
     */
    function clearFormDraft() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    /**
     * Показать/скрыть поля в зависимости от выбора рукава и юбки
     */
    function toggleDependentFields() {
        // Рукав
        const sleeveType = sleeveSelect ? sleeveSelect.value : 'none';
        const showSleeve = sleeveType !== 'none';
        sleeveFields.forEach(field => {
            const container = field.closest('.form-row') || field.closest('label') || field;
            container.style.display = showSleeve ? '' : 'none';
        });

        // Юбка
        const showSkirt = skirtCheckbox ? skirtCheckbox.checked : false;
        skirtFields.forEach(field => {
            const container = field.closest('.form-row') || field.closest('label') || field;
            container.style.display = showSkirt ? '' : 'none';
        });
        if (skirtOptions) {
            skirtOptions.style.display = showSkirt ? '' : 'none';
        }
    }

    /**
     * Валидация формы перед отправкой
     * @returns {boolean} true если форма валидна
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Сброс предыдущих ошибок
        form.querySelectorAll('.field-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        requiredFields.forEach(field => {
            // Проверяем только видимые поля (не скрытые)
            const container = field.closest('label') || field.parentNode;
            if (container && container.style.display === 'none') return;

            let value = field.value.trim();
            if (field.type === 'checkbox') {
                if (!field.checked) {
                    markFieldError(field, 'Это поле обязательно');
                    isValid = false;
                }
            } else if (!value) {
                markFieldError(field, 'Заполните это поле');
                isValid = false;
            } else if (field.type === 'number') {
                const num = parseFloat(value);
                const min = field.hasAttribute('min') ? parseFloat(field.min) : null;
                const max = field.hasAttribute('max') ? parseFloat(field.max) : null;
                if (isNaN(num)) {
                    markFieldError(field, 'Введите число');
                    isValid = false;
                } else if (min !== null && num < min) {
                    markFieldError(field, `Минимальное значение: ${min}`);
                    isValid = false;
                } else if (max !== null && num > max) {
                    markFieldError(field, `Максимальное значение: ${max}`);
                    isValid = false;
                }
            }
        });

        // Дополнительные проверки (например, возраст)
        const ageField = form.querySelector('input[name="age"]');
        if (ageField && ageField.value) {
            const age = parseInt(ageField.value, 10);
            if (age < 3 || age > 30) {
                markFieldError(ageField, 'Возраст должен быть от 3 до 30 лет');
                isValid = false;
            }
        }

        return isValid;
    }

    function markFieldError(field, message) {
        field.classList.add('error');
        const errorSpan = document.createElement('span');
        errorSpan.className = 'field-error';
        errorSpan.style.color = '#e91e63';
        errorSpan.style.fontSize = '12px';
        errorSpan.style.display = 'block';
        errorSpan.textContent = message;
        field.parentNode.appendChild(errorSpan);
    }

    // Обработчики событий
    function init() {
        // Загрузка черновика
        loadFormDraft();

        // Отслеживание изменений полей для сохранения черновика
        form.addEventListener('input', saveFormDraft);
        form.addEventListener('change', function(e) {
            saveFormDraft();
            if (e.target === sleeveSelect || e.target === skirtCheckbox) {
                toggleDependentFields();
            }
        });

        // Инициализация видимости
        toggleDependentFields();

        // Валидация при отправке
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                // Прокрутка к первой ошибке
                const firstError = form.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            // Если форма валидна, очищаем черновик перед отправкой
            clearFormDraft();
            // Можно показать индикатор загрузки
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Генерация...';
            }
        });

        // Предотвращение случайного ухода со страницы с несохранёнными данными
        window.addEventListener('beforeunload', function(e) {
            const formData = new FormData(form);
            let hasData = false;
            for (let pair of formData.entries()) {
                if (pair[1] && pair[0] !== '_wpnonce' && pair[0] !== 'action') {
                    hasData = true;
                    break;
                }
            }
            if (hasData) {
                e.preventDefault();
                e.returnValue = 'У вас есть несохранённые данные. Вы уверены, что хотите уйти?';
            }
        });
    }

    init();
})();
