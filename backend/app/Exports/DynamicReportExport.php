<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DynamicReportExport implements FromArray, WithHeadings, WithCustomStartCell, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    protected $data;
    protected $headings;
    protected $title;
    protected $company;
    protected $columnFormats;

    /**
     * @param array $data Los datos a exportar (cada fila es un array).
     * @param array $headings Los nombres de las columnas.
     * @param string $title El título del reporte (ej: REPORTE DE ASISTENCIA).
     * @param string $company El nombre de la empresa.
     * @param array $columnFormats Formatos de columna (ej: ['C' => NumberFormat::FORMAT_CURRENCY_USD]).
     */
    public function __construct(array $data, array $headings, string $title, string $company = 'Mi Empresa S.A.', array $columnFormats = [])
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->title = $title;
        $this->company = $company;
        $this->columnFormats = $columnFormats;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function startCell(): string
    {
        return 'A5'; // Empezamos en A5 para dejar espacio al título, empresa y fecha
    }

    public function columnFormats(): array
    {
        return $this->columnFormats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $columnCount = count($this->headings);
                
                if ($columnCount === 0) return;
                
                $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);

                // 1. ENCABEZADO DINÁMICO
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->mergeCells("A3:{$lastColumnLetter}3");

                $sheet->setCellValue('A1', mb_strtoupper($this->title));
                $sheet->setCellValue('A2', mb_strtoupper($this->company));
                $sheet->setCellValue('A3', 'Fecha de Emisión: ' . date('d/m/Y H:i:s'));

                // Estilo Título Principal
                $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Estilo Empresa y Fecha
                $sheet->getStyle("A2:{$lastColumnLetter}3")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['argb' => 'FF555555'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // 2. ESTILO DE CABECERAS DE TABLA (Fila 5)
                $headingsRange = "A5:{$lastColumnLetter}5";
                $sheet->getStyle($headingsRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF2E75B6'], // Azul suave profesional
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 3. FILTROS Y FREEZE PANES
                $sheet->setAutoFilter($headingsRange);
                $sheet->freezePane('A6');

                // 4. BORDES PARA TODA LA TABLA
                $dataRange = "A5:{$lastColumnLetter}{$highestRow}";
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // 5. ZEBRA STRIPING Y FILA DE TOTALES
                for ($row = 6; $row <= $highestRow; $row++) {
                    
                    // Zebra striping: Filas alternadas (Gris muy claro)
                    if ($row % 2 == 0) {
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFF2F2F2'],
                            ],
                        ]);
                    }

                    // 6. FILA DE TOTALES (Detección automática)
                    // Si la primera columna dice "Total"
                    $firstCellVal = (string)$sheet->getCell("A{$row}")->getValue();
                    if (stripos($firstCellVal, 'Total') !== false) {
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FF000000']
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFD9E1F2'], // Fondo azul claro para totales
                            ],
                            'borders' => [
                                'top' => [
                                    'borderStyle' => Border::BORDER_DOUBLE,
                                    'color' => ['argb' => 'FF000000'],
                                ],
                            ]
                        ]);
                    }
                }
            },
        ];
    }
}
