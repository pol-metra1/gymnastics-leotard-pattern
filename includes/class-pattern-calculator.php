<?php
class GLP_Pattern_Calculator {
    public static function calculate_coordinates($m) {
        $mm = function($cm) { return $cm * 10; };
        // Извлечение всех мерок
        $Og = $m['Og']; $Pg = $m['Pg']; $Dts = $m['Dts']; $Dtp = $m['Dtp'];
        $Shs = $m['Shs']; $Shg = $m['Shg']; $Shp = $m['Shp']; $Osh = $m['Osh'];
        $Vg = $m['Vg']; $Cg = $m['Cg']; $Dr = $m['Dr']; $Or = $m['Or'];
        $Ozap = $m['Ozap']; $Vbt = $m['Vbt']; $Onkt = $m['Onkt'];
        $DtlsP = $m['DtlsP']; $DtlsS = $m['DtlsS'];
        $Pshs = $m['Pshs']; $Pshg = $m['Pshg']; $Pop = $m['Pop']; $Pdts = $m['Pdts'];
        // ... остальные параметры
        // Расчёт базовых ширин (см)
        $Sg = ($Og + $Pg) / 2;
        $ShSp = $Shs + $Pshs;
        $ShPr = ($Og / 4) - 2;
        $ShPered = $Sg - ($ShSp + $ShPr);
        $VyisotaSetki = $Dts + $Dtp + $Pdts;
        // Точки сетки
        $points = [
            'grid' => [
                'A0' => [0, 0],
                'A1' => [$mm($Sg), 0],
                'A2' => [$mm($Sg), $mm($VyisotaSetki)],
                'A3' => [0, $mm($VyisotaSetki)],
                'B1' => [$mm($ShSp), 0],
                'B2' => [$mm($ShSp + $ShPr), 0],
                'Y_talii' => $mm($Dts + $Pdts),
                'Y_grudi' => $mm($Dts + $Pdts - ($Og/10 + 10.5)),
                'Y_beder' => $mm($Dts + $Pdts + 20),
            ],
            'back' => [],
            'front' => [],
            'pants' => [],
            'sleeve' => [],
            'skirt_back' => [],
            'skirt_front' => [],
        ];
        // Горловина спинки
        $Shgorl = $mm(($Osh / 6) + 0.5);
        $GlgorlSp = $mm(2.5);
        $points['back']['A0'] = [0, 0];
        $points['back']['A2'] = [$Shgorl, 0];
        $points['back']['A5'] = [0, $GlgorlSp];
        // Плечо спинки
        $NaklPlechaSp = $mm(($Shp / 2) + 2);
        $Dp_mm = $mm($m['Dp'] ?? $Shp);
        $points['back']['P1'] = [$Shgorl + $Dp_mm, $NaklPlechaSp];
        // Пройма спинки
        $Y_pr = ($points['grid']['Y_grudi'] + $points['grid']['Y_talii']) / 2;
        $points['back']['P2'] = [$mm($ShSp) - $mm(1.5), $Y_pr];
        $points['back']['P3'] = [$mm($ShSp), $points['grid']['Y_grudi']];
        $points['back']['B3'] = [$mm($ShSp), $mm($VyisotaSetki)];
        $points['back']['B5'] = [$mm($ShSp), $mm($VyisotaSetki + $Vbt)];
        // Перед
        $X0_per = $mm($ShSp + $ShPr);
        $points['front']['C0'] = [$X0_per, 0];
        $points['front']['C2'] = [$X0_per + $Shgorl, 0];
        $GlgorlPer = $mm(($Osh / 6) + 1.5);
        $points['front']['C5'] = [$X0_per, $GlgorlPer];
        $NaklPlechaPer = $mm(($Shp / 2) + 1);
        $points['front']['P1_'] = [$X0_per + $Shgorl + $Dp_mm, $NaklPlechaPer];
        $points['front']['P2_'] = [$X0_per + $mm($ShPered) + $mm(1.5), $Y_pr];
        $points['front']['P3_'] = [$X0_per + $mm($ShPered), $points['grid']['Y_grudi']];
        $points['front']['B4'] = [$X0_per + $mm($ShPered), $mm($VyisotaSetki)];
        $points['front']['B6'] = [$X0_per + $mm($ShPered), $mm($VyisotaSetki + $Vbt)];
        // Трусики (перед)
        $Onkt4 = $mm(($Onkt / 4) - 2);
        $points['pants']['T1'] = $points['front']['B6'];
        $points['pants']['T2'] = [$points['front']['B6'][0] - $Onkt4, $points['front']['B6'][1] + $mm($DtlsP)];
        $points['pants']['T3'] = [$points['front']['B6'][0] - $mm($Onkt/4), $points['pants']['T2'][1]];
        // Трусики (спинка)
        $Onkt4_back = $mm(($Onkt / 4) - 1);
        $points['pants']['T4'] = $points['back']['B5'];
        $points['pants']['T5'] = [$points['back']['B5'][0] + $Onkt4_back, $points['back']['B5'][1] + $mm($DtlsS)];
        $points['pants']['T6'] = [$points['back']['B5'][0] + $mm($Onkt/4), $points['pants']['T5'][1]];
        // Рукав (если выбран)
        if (!empty($m['SleeveType']) && $m['SleeveType'] !== 'none') {
            $Vok = $mm(($Og / 10) + 5);
            $Shruk = $mm(($Or + $Pop) / 2);
            $Dr_mm = ($m['SleeveType'] === 'short') ? $mm(20) : $mm($Dr);
            $points['sleeve']['R0'] = [0, 0];
            $points['sleeve']['R1'] = [$Shruk, $Vok];
            $points['sleeve']['R2'] = [-$Shruk, $Vok];
            $points['sleeve']['R3'] = [$Shruk, $Dr_mm];
            $points['sleeve']['R4'] = [-$Shruk, $Dr_mm];
        }
        // Юбка (если выбрана)
        if (!empty($m['Skirt']) && $m['Skirt'] == true) {
            $Pt_skirt = isset($m['Pt_skirt']) ? $m['Pt_skirt'] : $m['Pt'];
            $L_skirt = $mm($m['SkirtLength']);
            $W_top = $mm(($m['Ot'] + $Pt_skirt) / 4);
            $K = ($m['SkirtType'] === 'sun') ? 2.0 : (($m['SkirtType'] === 'half_sun') ? 1.5 : 1.0);
            $W_bottom = $W_top * $K;
            // Центры
            $X_center_front = ($points['grid']['B2'][0] + $points['front']['C0'][0]) / 2;
            $X_center_back = $points['grid']['B1'][0] / 2;
            $Y_talia = $points['grid']['Y_talii'];
            $points['skirt_front'] = [
                'TL' => [$X_center_front - $W_top/2, $Y_talia],
                'TR' => [$X_center_front + $W_top/2, $Y_talia],
                'BR' => [$X_center_front + $W_bottom/2, $Y_talia + $L_skirt],
                'BL' => [$X_center_front - $W_bottom/2, $Y_talia + $L_skirt],
            ];
            $points['skirt_back'] = [
                'TL' => [$X_center_back - $W_top/2, $Y_talia],
                'TR' => [$X_center_back + $W_top/2, $Y_talia],
                'BR' => [$X_center_back + $W_bottom/2, $Y_talia + $L_skirt],
                'BL' => [$X_center_back - $W_bottom/2, $Y_talia + $L_skirt],
            ];
        }
        return $points;
    }

    public static function get_pattern_dimensions($points) {
        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = PHP_FLOAT_MIN;
        foreach ($points as $part => $partPoints) {
            if (!is_array($partPoints)) continue;
            foreach ($partPoints as $point) {
                if (!is_array($point) || count($point) < 2) continue;
                $minX = min($minX, $point[0]);
                $maxX = max($maxX, $point[0]);
                $minY = min($minY, $point[1]);
                $maxY = max($maxY, $point[1]);
            }
        }
        return ['width' => $maxX - $minX, 'height' => $maxY - $minY];
    }
}
