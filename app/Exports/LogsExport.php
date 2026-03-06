<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogsExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs->map(function ($log) {
            return [
                'Date' => $log->logged_at->format('Y-m-d'),
                'System' => $log->system->name,
                'Title' => $log->title,
                'Type' => ucfirst($log->type),
                'Impact' => ucfirst($log->impact),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'System',
            'Title',
            'Type',
            'Impact'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [

            // Header styling
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],

        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;

                $lastRow = $sheet->getHighestRow();

                // Header background
                $sheet->getStyle('A1:E1')->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => 'F3F4F6',
                        ],
                    ],
                ]);

                // Border table
                $sheet->getStyle("A1:E{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin');

                // Align Type & Impact center
                $sheet->getStyle("D2:E{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal('center');

                // Freeze header row
                $sheet->freezePane('A2');

                // Auto filter
                $sheet->setAutoFilter("A1:E{$lastRow}");

                // Conditional formatting impact colors
                for ($i = 2; $i <= $lastRow; $i++) {

                    $impact = $sheet->getCell("E{$i}")->getValue();

                    if ($impact === 'Critical') {
                        $sheet->getStyle("E{$i}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => 'FECACA'],
                            ],
                        ]);
                    }

                    if ($impact === 'High') {
                        $sheet->getStyle("E{$i}")->applyFromArray([
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => 'FED7AA'],
                            ],
                        ]);
                    }

                    if ($impact === 'Medium') {
                        $sheet->getStyle("E{$i}")->applyFromArray([
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => 'FEF3C7'],
                            ],
                        ]);
                    }

                }

            }

        ];
    }
}