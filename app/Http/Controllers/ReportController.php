<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\System;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LogsExport;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index', [
            'systems' => System::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $logs = $this->buildQuery($request)->get();

        $meta = $this->buildMeta($request, $logs, $request->format);

        if ($request->format === 'excel') {
            return Excel::download(
                new LogsExport($logs),
                $meta['filename']
            );
        }

        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('exports.logs', [
                'logs' => $logs,
                'meta' => $meta,
            ])->setPaper('a4', 'portrait');

            return $pdf->download($meta['filename']);
        }

        abort(400);
    }

    private function buildQuery($request)
    {
        $query = Log::with('system');

        if ($request->scope === 'daily' && $request->date) {
            $query->whereDate('logged_at', $request->date);
        }

        if ($request->scope === 'monthly' && $request->month) {
            $date = Carbon::parse($request->month);

            $query->whereMonth('logged_at', $date->month)
                ->whereYear('logged_at', $date->year);
        }

        if ($request->scope === 'range' && $request->start_date && $request->end_date) {
            $query->whereBetween('logged_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        if ($request->system_id) {
            $query->where('system_id', $request->system_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->impact) {
            $query->where('impact', $request->impact);
        }

        return $query->latest();
    }

    private function buildMeta($request, $logs, $format)
    {
        $title = "Logs Report";
        $subtitle = "";

        if ($request->system_id) {
            $system = System::find($request->system_id);
            $title .= " – " . $system->name;
        } else {
            $title .= " – All Systems";
        }

        if ($request->scope === 'daily' && $request->date) {
            $subtitle = "Date: " . Carbon::parse($request->date)->format('d M Y');
        }

        if ($request->scope === 'monthly' && $request->month) {
            $subtitle = "Month: " . Carbon::parse($request->month)->format('F Y');
        }

        if ($request->scope === 'range' && $request->start_date && $request->end_date) {
            $subtitle = "Range: "
                . Carbon::parse($request->start_date)->format('d M Y')
                . " – "
                . Carbon::parse($request->end_date)->format('d M Y');
        }

        $total = $logs->count();
        $highCritical = $logs->whereIn('impact', ['high', 'critical'])->count();

        $extension = $format === 'excel' ? 'xlsx' : 'pdf';

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'total' => $total,
            'highCritical' => $highCritical,
            'filename' => Str::slug($title) . '.' . $extension,
        ];
    }
}