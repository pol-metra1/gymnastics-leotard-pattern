<?php
require_once(dirname(__FILE__) . '/tcpdf/tcpdf.php');

class GLP_PDF_Generator {
    private $points;
    private $patternWidth;
    private $patternHeight;
    private $tileGrid;
    private $pdf;

    public function __construct($points, $width, $height, $tileGrid) {
        $this->points = $points;
        $this->patternWidth = $width;
        $this->patternHeight = $height;
        $this->tileGrid = $tileGrid;
    }

    public function generate() {
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        $cols = $this->tileGrid['cols'];
        $rows = $this->tileGrid['rows'];
        $tileWidth = $this->tileGrid['tile_width'];
        $tileHeight = $this->tileGrid['tile_height'];
        $margin = $this->tileGrid['margin'];

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $this->pdf->AddPage();
                $pageNum = $row * $cols + $col + 1;
                $totalPages = $this->tileGrid['total_pages'];
                $tileLeft = $col * $tileWidth;
                $tileTop = $row * $tileHeight;
                $this->drawTile($tileLeft, $tileTop, $tileWidth, $tileHeight, $margin);
                $this->addTileInfo($col, $row, $cols, $rows, $pageNum, $totalPages);
                $this->addRegistrationMarks($col, $row, $cols, $rows, $margin);
            }
        }
        return $this->pdf;
    }

    private function drawTile($tileLeft, $tileTop, $tileWidth, $tileHeight, $margin) {
        $this->pdf->StartTransform();
        $this->pdf->Transform([
            'x' => $margin,
            'y' => $margin,
            'tm' => [1, 0, 0, 1, -$tileLeft, -$tileTop]
        ]);
        $this->drawAllPatternLines();
        $this->pdf->StopTransform();
    }

    private function drawAllPatternLines() {
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->drawPolygon($this->points['back'] ?? []);
        $this->drawPolygon($this->points['front'] ?? []);
        $this->drawPolygon($this->points['pants'] ?? []);
        if (!empty($this->points['sleeve'])) $this->drawPolygon($this->points['sleeve']);
        if (!empty($this->points['skirt_front'])) $this->drawPolygon($this->points['skirt_front']);
        if (!empty($this->points['skirt_back'])) $this->drawPolygon($this->points['skirt_back']);
    }

    private function drawPolygon($points) {
        if (empty($points)) return;
        $first = reset($points);
        $this->pdf->StartPath();
        $this->pdf->MoveTo($first[0], $first[1]);
        foreach ($points as $point) {
            $this->pdf->LineTo($point[0], $point[1]);
        }
        $this->pdf->ClosePath();
        $this->pdf->DrawPath('S');
    }

    private function addTileInfo($col, $row, $cols, $rows, $pageNum, $totalPages) {
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->SetTextColor(100, 100, 100);
        $info = "Лист $pageNum из $totalPages  |  Ряд ".($row+1).", Колонка ".($col+1)."  |  Сетка {$cols}×{$rows}";
        $this->pdf->SetXY(5, 5);
        $this->pdf->Cell(0, 5, $info, 0, 0, 'L');
        $this->pdf->SetXY(190, 5);
        $this->pdf->Cell(0, 5, '↑ ВЕРХ', 0, 0, 'R');
        $this->pdf->SetXY(5, 285);
        $this->pdf->SetFont('helvetica', 'I', 7);
        $this->pdf->Cell(0, 5, 'Вырежьте по контуру, совместите метки и склейте', 0, 0, 'C');
    }

    private function addRegistrationMarks($col, $row, $cols, $rows, $margin) {
        // Упрощённая версия: метки по краям
        $this->pdf->SetLineWidth(0.2);
        $pageW = 210; $pageH = 297; $size = 5;
        if ($col > 0) {
            $this->crossMark($margin, $pageH*0.25, $size, 'left');
            $this->crossMark($margin, $pageH*0.5, $size, 'left');
            $this->crossMark($margin, $pageH*0.75, $size, 'left');
        }
        if ($col < $cols-1) {
            $this->crossMark($pageW - $margin, $pageH*0.25, $size, 'right');
            $this->crossMark($pageW - $margin, $pageH*0.5, $size, 'right');
            $this->crossMark($pageW - $margin, $pageH*0.75, $size, 'right');
        }
        if ($row > 0) {
            $this->crossMark($pageW*0.25, $margin, $size, 'top');
            $this->crossMark($pageW*0.5, $margin, $size, 'top');
            $this->crossMark($pageW*0.75, $margin, $size, 'top');
        }
        if ($row < $rows-1) {
            $this->crossMark($pageW*0.25, $pageH - $margin, $size, 'bottom');
            $this->crossMark($pageW*0.5, $pageH - $margin, $size, 'bottom');
            $this->crossMark($pageW*0.75, $pageH - $margin, $size, 'bottom');
        }
    }

    private function crossMark($x, $y, $size, $pos) {
        $h = $size/2;
        switch($pos) {
            case 'left': $this->pdf->Line($x, $y-$h, $x+$size, $y); $this->pdf->Line($x, $y+$h, $x+$size, $y); break;
            case 'right': $this->pdf->Line($x, $y-$h, $x-$size, $y); $this->pdf->Line($x, $y+$h, $x-$size, $y); break;
            case 'top': $this->pdf->Line($x-$h, $y, $x, $y+$size); $this->pdf->Line($x+$h, $y, $x, $y+$size); break;
            case 'bottom': $this->pdf->Line($x-$h, $y, $x, $y-$size); $this->pdf->Line($x+$h, $y, $x, $y-$size); break;
        }
        $this->pdf->Circle($x, $y, $size*1.5);
    }
}
