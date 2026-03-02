<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LogsExport implements FromCollection, WithHeadings
{
    protected Collection $logs;

    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs->map(function ($log) {
            return [
                'Date' => $log->logged_at,
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
            'Impact',
        ];
    }
}