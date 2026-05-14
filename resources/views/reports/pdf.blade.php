<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Report</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #0f172a; }
            .muted { color: #64748b; }
            .h1 { font-size: 16px; font-weight: 700; margin: 0; }
            .h2 { font-size: 12px; font-weight: 700; margin: 18px 0 8px; }
            .row { width: 100%; }
            .kpi { width: 100%; border-collapse: collapse; }
            .kpi td { padding: 8px 10px; border: 1px solid #e2e8f0; vertical-align: top; }
            .kpi .label { font-size: 10px; color: #64748b; }
            .kpi .value { font-size: 14px; font-weight: 700; }
            .table { width: 100%; border-collapse: collapse; }
            .table th, .table td { border: 1px solid #e2e8f0; padding: 6px 8px; vertical-align: top; }
            .table th { background: #f8fafc; font-size: 10px; text-align: left; }
            .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 10px; border: 1px solid #e2e8f0; color: #334155; }
            .footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #64748b; }
        </style>
    </head>
    <body>
        @php
            $generatedAt = $meta['generated_at'] ?? now();
            $start = $meta['start_date'] ?? '';
            $end = $meta['end_date'] ?? '';

            $logoPath = public_path('Rumah Atsiri Indonesia Logogram Black.png');
            $logoData = null;
            if (file_exists($logoPath)) {
                $logoData = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
            }
        @endphp

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 52px; vertical-align: top;">
                    @if($logoData)
                        <img src="{{ $logoData }}" alt="Logo" style="width: 44px; height: 44px;">
                    @endif
                </td>
                <td style="vertical-align: top;">
                    <div class="h1">System Management Workstation</div>
                    <div class="muted">Weekly System Management Report (Log-based)</div>
                    <div class="muted" style="margin-top: 6px;">
                        Periode: <strong>{{ $start }}</strong> – <strong>{{ $end }}</strong>
                        @if(!empty($meta['system']))
                            · System: <strong>{{ $meta['system'] }}</strong>
                        @endif
                        @if(!empty($meta['feature']))
                            · Feature: <strong>{{ $meta['feature'] }}</strong>
                        @endif
                    </div>
                    <div class="muted" style="margin-top: 2px;">
                        Generated at:
                        <strong>{{ \Carbon\Carbon::parse($generatedAt)->locale('id')->translatedFormat('l, d F Y H:i') }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        <div class="h2">KPI</div>
        <table class="kpi">
            <tr>
                <td>
                    <div class="label">Total Logs</div>
                    <div class="value">{{ $kpis['total_logs'] ?? 0 }}</div>
                </td>
                <td>
                    <div class="label">Progress</div>
                    <div class="value">{{ $kpis['progress_logs'] ?? 0 }}</div>
                </td>
                <td>
                    <div class="label">Bug</div>
                    <div class="value">{{ $kpis['bug_logs'] ?? 0 }}</div>
                    <div class="muted" style="margin-top: 2px;">Resolved bug: {{ $kpis['resolved_bug_count'] ?? 0 }}</div>
                </td>
                <td>
                    <div class="label">Fix</div>
                    <div class="value">{{ $kpis['fix_logs'] ?? 0 }}</div>
                    <div class="muted" style="margin-top: 2px;">SLA on time: {{ $kpis['sla_on_time_rate'] ?? 0 }}%</div>
                </td>
            </tr>
        </table>

        <div class="h2">Bug Summary</div>
        <div class="muted">
            Total bug: <strong>{{ $bug_summary['total_bug'] ?? 0 }}</strong>
            · Resolved bug: <strong>{{ $bug_summary['resolved_bug'] ?? 0 }}</strong>
            · Resolved fix: <strong>{{ $bug_summary['resolved_fix'] ?? 0 }}</strong>
            · SLA on time: <strong>{{ $bug_summary['on_time'] ?? 0 }}</strong>
            · Late: <strong>{{ $bug_summary['late'] ?? 0 }}</strong>
        </div>

        <div class="h2">Log Table</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 16%;">Tanggal</th>
                    <th style="width: 14%;">System</th>
                    <th style="width: 14%;">Feature</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 36%;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $date = $log->logged_at
                            ? \Carbon\Carbon::parse($log->logged_at)->locale('id')->translatedFormat('l, d M Y')
                            : '-';
                        $status = $log->status ?: '-';
                        $type = $log->type ?: '-';
                        $refs = $log->references?->where('type', 'bug') ?? collect();
                        $refText = $type === 'fix' && $refs->count() > 0
                            ? 'Fix ('.$refs->count().' bug): '.$refs->pluck('id')->map(fn($id) => '#'.$id)->join(', ')
                            : null;
                    @endphp
                    <tr>
                        <td>{{ $date }}</td>
                        <td>{{ $log->system?->name ?? '-' }}</td>
                        <td>{{ $log->feature?->title ?? '-' }}</td>
                        <td><span class="badge">{{ $type }}</span></td>
                        <td><span class="badge">{{ $status }}</span></td>
                        <td>
                            <div><strong>{{ $log->title }}</strong></div>
                            @if($refText)
                                <div class="muted" style="margin-top: 2px;">{{ $refText }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">Belum ada aktivitas pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Generated by System Management Workstation
        </div>
    </body>
</html>
