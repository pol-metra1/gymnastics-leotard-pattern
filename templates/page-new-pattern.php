<?php
/* Template Name: New Pattern */
get_header();
?>
<div class="glp-container">
    <h1>Новая выкройка купальника</h1>
    <form id="glp-pattern-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('glp_save_pattern', '_wpnonce'); ?>
        <input type="hidden" name="action" value="glp_generate_pattern">
        <!-- Информация о гимнастке -->
        <fieldset>
            <legend>Данные гимнастки</legend>
            <div class="form-row">
                <label>Имя <input type="text" name="name" required></label>
                <label>Возраст <input type="number" name="age" min="3" max="30" required></label>
            </div>
        </fieldset>
        <!-- Основные обхваты -->
        <fieldset>
            <legend>Обхваты (см)</legend>
            <div class="form-grid">
                <label>Обхват груди (Ог) <input type="number" step="0.1" name="Og" required></label>
                <label>Обхват талии (От) <input type="number" step="0.1" name="Ot" required></label>
                <label>Обхват бёдер (Об) <input type="number" step="0.1" name="Ob" required></label>
                <label>Обхват шеи (Ош) <input type="number" step="0.1" name="Osh" required></label>
                <label>Обхват руки (Ор) <input type="number" step="0.1" name="Or" required></label>
                <label>Обхват запястья (Озап) <input type="number" step="0.1" name="Ozap"></label>
                <label>Обхват по нижнему краю трусиков <input type="number" step="0.1" name="Onkt" required></label>
            </div>
        </fieldset>
        <!-- Длины и высоты -->
        <fieldset>
            <legend>Длины (см)</legend>
            <div class="form-grid">
                <label>Длина спины до талии (Дтс) <input type="number" step="0.1" name="Dts" required></label>
                <label>Длина переда до талии (Дтп) <input type="number" step="0.1" name="Dtp" required></label>
                <label>Длина плеча (Дп) <input type="number" step="0.1" name="Dp"></label>
                <label>Длина рукава (Др) <input type="number" step="0.1" name="Dr"></label>
                <label>Высота груди (Вг) <input type="number" step="0.1" name="Vg"></label>
                <label>Длина от талии до ластовицы спереди <input type="number" step="0.1" name="DtlsP" required></label>
                <label>Длина от талии до ластовицы сзади <input type="number" step="0.1" name="DtlsS" required></label>
                <label>Высота бока трусиков (Вбт) <input type="number" step="0.1" name="Vbt" required></label>
            </div>
        </fieldset>
        <!-- Ширины -->
        <fieldset>
            <legend>Ширины (см)</legend>
            <div class="form-grid">
                <label>Ширина плеч (Шп) <input type="number" step="0.1" name="Shp" required></label>
                <label>Ширина спины (Шс) <input type="number" step="0.1" name="Shs" required></label>
                <label>Ширина груди (Шг) <input type="number" step="0.1" name="Shg" required></label>
                <label>Центр груди (Цг) <input type="number" step="0.1" name="Cg"></label>
            </div>
        </fieldset>
        <!-- Прибавки и коэффициенты -->
        <fieldset>
            <legend>Прибавки (обычно отрицательные, см)</legend>
            <div class="form-grid">
                <label>Прибавка по груди (Пг) <input type="number" step="0.1" name="Pg" value="-14"></label>
                <label>Прибавка по талии (Пт) <input type="number" step="0.1" name="Pt" value="-12"></label>
                <label>Прибавка по бёдрам (Пб) <input type="number" step="0.1" name="Pb" value="-15"></label>
                <label>Прибавка к обхвату плеча (Поп) <input type="number" step="0.1" name="Pop" value="-3"></label>
                <label>Прибавка к ширине спины (Пшс) <input type="number" step="0.1" name="Pshs" value="-2"></label>
                <label>Прибавка к ширине переда (Пшг) <input type="number" step="0.1" name="Pshg" value="-2"></label>
                <label>Прибавка к длине талии спинки (Пдтс) <input type="number" step="0.1" name="Pdts" value="-1"></label>
            </div>
        </fieldset>
        <!-- Опции моделирования -->
        <fieldset>
            <legend>Моделирование</legend>
            <div class="form-row">
                <label>Тип рукава 
                    <select name="SleeveType">
                        <option value="none">Без рукава</option>
                        <option value="set-in">Втачной длинный</option>
                        <option value="short">Втачной короткий</option>
                    </select>
                </label>
                <label>Юбка <input type="checkbox" name="Skirt" value="1"></label>
                <label>Тип юбки 
                    <select name="SkirtType">
                        <option value="straight">Прямая</option>
                        <option value="half_sun">Полусолнце</option>
                        <option value="sun">Солнце</option>
                    </select>
                </label>
                <label>Длина юбки (см) <input type="number" step="0.1" name="SkirtLength" value="15"></label>
            </div>
        </fieldset>
        <button type="submit" class="glp-btn">Сгенерировать выкройку</button>
    </form>
</div>
<?php get_footer(); ?>
