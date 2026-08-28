<?php
/**
 * Exportador de planes de entrenamiento a PDF.
 *
 * Reproduce el formato de los planes impresos originales (5K/10K/21K/42K):
 * tabla apaisada con columnas SEMANA | LUNES | ... | DOMINGO y una fila por
 * semana con el detalle de cada sesion.
 *
 * Dos modos:
 *  - exportMultiWeek(): una fila por semana, varias semanas por pagina (vista mensual/plan completo)
 *  - exportWeekDetail(): una pagina por semana con los bloques completos (vista detallada)
 *
 * Requiere FPDF (includes/fpdf.php) y TrainingStructure.
 */
require_once __DIR__ . '/fpdf.php';
require_once __DIR__ . '/../models/TrainingStructure.php';

class PDFPlanExporter extends FPDF
{
    /** Ancho de la columna SEMANA. */
    private const COL_WEEK = 18;

    private $athleteName = '';
    private $coachName = '';
    private $planTitle = '';

    public function __construct($orientation = 'L', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetAutoPageBreak(true, 12);
        $this->SetMargins(8, 10, 8);
    }

    public function setMeta($athleteName, $coachName, $planTitle)
    {
        $this->athleteName = $athleteName;
        $this->coachName = $coachName;
        $this->planTitle = $planTitle;
    }

    /** Convierte texto UTF-8 a Latin-1 para las fuentes core de FPDF. */
    private function txt($s)
    {
        $s = (string) $s;
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $s) : $converted;
    }

    private function dayHeader()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(150);

        $days = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
        $w = $this->dayColWidth();

        $this->Cell(self::COL_WEEK, 7, $this->txt('SEMANA'), 1, 0, 'C', true);
        foreach ($days as $d) {
            $this->Cell($w, 7, $this->txt($d), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    private function dayColWidth()
    {
        return ($this->GetPageWidth() - $this->lMargin - $this->rMargin - self::COL_WEEK) / 7;
    }

    private function pageHeader()
    {
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(0);
        $this->Cell(0, 8, $this->txt($this->planTitle), 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(90);
        $meta = [];
        if ($this->athleteName !== '') {
            $meta[] = 'Atleta: ' . $this->athleteName;
        }
        if ($this->coachName !== '') {
            $meta[] = 'Entrenador: ' . $this->coachName;
        }
        $meta[] = 'Generado: ' . date('d/m/Y');
        $this->Cell(0, 5, $this->txt(implode('   |   ', $meta)), 0, 1, 'C');
        $this->Ln(2);
    }

    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130);
        $this->Cell(0, 8, $this->txt('RUNCOACH - Pagina ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    /**
     * Vista tipo plan impreso: una fila por semana, celdas con el resumen
     * de cada dia. $weeks = [ ['label' => '1', 'days' => [0..6 => workout|null]] ]
     * donde cada workout es un array con 'description' y 'structure'.
     */
    public function exportMultiWeek(array $weeks)
    {
        $this->AliasNbPages();
        $this->AddPage();
        $this->pageHeader();
        $this->dayHeader();

        $w = $this->dayColWidth();

        foreach ($weeks as $week) {
            $maxLines = 1;
            foreach ($week['days'] as $dayIdx => $dayWorkouts) {
                if (empty($dayWorkouts)) {
                    continue;
                }
                $maxLines = max($maxLines, min(6, $this->countSummaryLines($dayWorkouts, $w)));
            }

            $rowH = $maxLines * 4 + 4;

            // Evitar fila cortada entre paginas: si no entra completa, nueva pagina.
            if ($this->GetY() + $rowH > $this->GetPageHeight() - $this->bMargin - 12) {
                $this->AddPage();
                $this->pageHeader();
                $this->dayHeader();
            }

            $y0 = $this->GetY();

            // Celda semana (borde + texto centrado verticalmente)
            $this->SetDrawColor(150);
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(0);
            $x = $this->GetX();
            $this->Rect($x, $y0, self::COL_WEEK, $rowH);
            $this->SetXY($x, $y0 + ($rowH - 5) / 2);
            $this->Cell(self::COL_WEEK, 5, $this->txt((string) $week['label']), 0, 0, 'C');
            $this->SetXY($x + self::COL_WEEK, $y0);

            // Celdas por dia: borde con Rect + texto con MultiCell sin borde
            foreach ($week['days'] as $dayIdx => $dayWorkouts) {
                $cx = $this->GetX();
                $this->Rect($cx, $y0, $w, $rowH);

                if (!empty($dayWorkouts)) {
                    $lines = $this->summaryLines($dayWorkouts, $w);

                    $this->SetFont('Arial', '', 7);
                    $this->SetTextColor(30);
                    $this->SetXY($cx + 1, $y0 + 1.5);
                    $this->MultiCell($w - 2, 3.6, $this->txt($lines), 0, 'L');
                }

                $this->SetXY($cx + $w, $y0);
            }
            $this->SetXY($this->lMargin, $y0 + $rowH);
        }
    }

    /**
     * Vista detallada: una pagina por semana, cada dia como seccion con
     * los bloques completos de cada sesion. Cada entrada de 'days' es una
     * LISTA de workouts (un dia puede tener varias sesiones).
     */
    public function exportWeekDetail(array $week, array $tipsById = [])
    {
        $this->AliasNbPages();
        $this->AddPage();
        $this->pageHeader();

        $dayNames = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        $blockLabels = TrainingStructure::BLOCKS;
        unset($blockLabels['notes']);

        foreach ($week['days'] as $dayIdx => $dayWorkouts) {
            $date = '';
            if (!empty($week['dates'][$dayIdx])) {
                $date = ' (' . (new DateTime($week['dates'][$dayIdx]))->format('d/m') . ')';
            }

            if ($this->GetY() > $this->GetPageHeight() - 60) {
                $this->AddPage();
            }

            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(255);
            $this->SetFillColor(70, 110, 200);
            $this->Cell(0, 7, $this->txt($dayNames[$dayIdx] . $date), 1, 1, 'L', true);

            if (empty($dayWorkouts)) {
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(120);
                $this->Cell(0, 6, $this->txt('Descanso'), 1, 1, 'C');
                $this->Ln(3);
                continue;
            }

            // Cada sesion del dia se renderiza como su propia seccion.
            foreach ($dayWorkouts as $workout) {
                $structure = TrainingStructure::parse($workout['structure'] ?? null);

                // Titulo de la sesion
                $this->SetFont('Arial', 'B', 9);
                $this->SetTextColor(0);
                $this->Cell(0, 6, $this->txt(($workout['type'] ?? '') . ' - ' . ($workout['description'] ?? 'Sesion')), 1, 1, 'L');

                foreach ($blockLabels as $key => $label) {
                    $content = trim((string) ($structure[$key] ?? ''));
                    if ($content === '') {
                        continue;
                    }
                    if ($this->GetY() > $this->GetPageHeight() - 30) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'B', 8);
                    $this->SetTextColor(50);
                    $this->SetFillColor(242, 245, 250);
                    $this->Cell(0, 5, $this->txt($label), 1, 1, 'L', true);
                    $this->SetFont('Arial', '', 8);
                    $this->SetTextColor(30);
                    $this->MultiCell(0, 4, $this->txt($content), 1, 'L');
                }

                if (!empty($structure['notes'])) {
                    if ($this->GetY() > $this->GetPageHeight() - 30) {
                        $this->AddPage();
                    }
                    $this->SetFont('Arial', 'I', 8);
                    $this->SetTextColor(110);
                    $this->MultiCell(0, 4, $this->txt('Notas: ' . $structure['notes']), 0, 'L');
                }
            }
            $this->Ln(4);
        }

        // Tips asociados a la semana
        if ($tipsById) {
            if ($this->GetY() > $this->GetPageHeight() - 45) {
                $this->AddPage();
            }
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(146, 88, 10);
            $this->Cell(0, 7, $this->txt('Consejos para el plan'), 0, 1, 'L');

            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(60);
            foreach ($tipsById as $tip) {
                $title = is_array($tip) ? ($tip['title'] ?? '') : '';
                $content = is_array($tip) ? ($tip['content'] ?? '') : (string) $tip;
                if ($this->GetY() > $this->GetPageHeight() - 30) {
                    $this->AddPage();
                }
                $this->SetTextColor(60);
                $this->SetFont('Arial', 'B', 8);
                $this->MultiCell(0, 4, $this->txt('- ' . $title), 0, 'L');
                $this->SetFont('Arial', '', 8);
                $this->MultiCell(0, 4, $this->txt($content), 0, 'L');
                $this->Ln(1.5);
            }
        }
    }

    /** Estimacion de lineas de resumen para una celda (para calcular altura). */
    private function countSummaryLines(array $dayWorkouts, $w)
    {
        $text = $this->summaryLines($dayWorkouts, $w);
        return substr_count($text, "\n") + 1;
    }

    /** Texto resumen de una celda: main_set + warm_up abreviado + strength abreviado.
     * $dayWorkouts es la lista de sesiones del dia. */
    private function summaryLines(array $dayWorkouts, $w)
    {
        $chars = (int) floor($w / 1.25); // aprox chars por linea a 7pt
        $parts = [];

        foreach ($dayWorkouts as $workout) {
            $structure = TrainingStructure::parse($workout['structure'] ?? null);

            $main = trim((string) ($structure['main_set'] ?? ''));
            $warm = trim((string) ($structure['warm_up'] ?? ''));
            $strength = trim((string) ($structure['strength'] ?? ''));

            if ($main !== '') {
                $parts[] = '- ' . $this->wrap($main, $chars, 3);
            } elseif (!empty($workout['description'])) {
                $parts[] = '- ' . $this->wrap((string) $workout['description'], $chars, 1);
            }

            if ($warm !== '') {
                $parts[] = '  ' . $this->wrap($warm, $chars, 1);
            }

            if ($strength !== '') {
                $parts[] = '  ' . $this->wrap($strength, $chars, 1);
            }
        }

        if (empty($parts)) {
            $parts[] = '-';
        }

        return implode("\n", $parts);
    }

    /** Corta un texto a maxLines lineas de ~$chars caracteres con elipsis. */
    private function wrap($text, $chars, $maxLines)
    {
        $words = preg_split('/\s+/u', trim($text));
        $lines = [''];
        foreach ($words as $word) {
            $current = end($lines);
            if (strlen($current) + strlen($word) + 1 > $chars) {
                $lines[] = $word;
            } else {
                $lines[count($lines) - 1] = trim($current . ' ' . $word);
            }
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = mb_substr($lines[$maxLines - 1], 0, max(0, $chars - 1)) . '…';
        }
        return implode("\n", $lines);
    }

    public function outputPDF($filename)
    {
        return $this->Output('D', $filename, false);
    }
}