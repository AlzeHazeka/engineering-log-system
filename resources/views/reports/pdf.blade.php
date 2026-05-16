<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Engineering Operational Report</title>
        <style>
            * { box-sizing: border-box; }
            @page { margin: 18mm 16mm 20mm 16mm; }
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #0f172a; line-height: 1.35; }
            .muted { color: #64748b; }
            .small { font-size: 10px; }
            .h1 { font-size: 16px; font-weight: 700; margin: 0; }
            .h2 { font-size: 12px; font-weight: 700; margin: 18px 0 8px; }
            .h3 { font-size: 11px; font-weight: 700; margin: 0 0 6px; }
            .subtle { color: #334155; }
            .divider { height: 1px; background: #e2e8f0; margin: 12px 0; }
            .section { page-break-inside: avoid; }

            /* Cards / tables (DomPDF-safe) */
            .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; background: #ffffff; }
            .card-muted { background: #f8fafc; }

            .kpi { width: 100%; border-collapse: separate; border-spacing: 8px; table-layout: fixed; }
            .kpi td { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; vertical-align: top; }
            .kpi .label { font-size: 10px; color: #64748b; }
            .kpi .value { font-size: 15px; font-weight: 700; margin-top: 2px; }
            .kpi .hint { margin-top: 4px; font-size: 10px; color: #64748b; }

            .grid { width: 100%; border-collapse: separate; border-spacing: 8px; table-layout: fixed; }
            .grid td { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; vertical-align: top; }

            .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; border: 1px solid #e2e8f0; color: #334155; background: #ffffff; }
            .pill-green { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
            .pill-yellow { border-color: #fde68a; background: #fffbeb; color: #854d0e; }
            .pill-red { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
            .pill-slate { border-color: #e2e8f0; background: #f8fafc; color: #334155; }

            .two-col { width: 100%; border-collapse: separate; border-spacing: 10px; table-layout: fixed; }
            .two-col td { vertical-align: top; }

            .table { width: 100%; border-collapse: collapse; }
            .table th, .table td { border: 1px solid #e2e8f0; padding: 7px 8px; vertical-align: top; }
            .table th { background: #f8fafc; font-size: 10px; text-align: left; color: #334155; }
            .table td { font-size: 10px; }
            .table .title { font-size: 10.5px; font-weight: 700; color: #0f172a; }
            .table .meta { margin-top: 2px; color: #64748b; }
            .table tr { page-break-inside: avoid; }

            .footer-note { margin-top: 14px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 9.5px; color: #64748b; }
            .page-break { page-break-before: always; }
            .nowrap { white-space: nowrap; }
        </style>
    </head>
    <body>
        @php
            $generatedAt = $meta['generated_at'] ?? now();
            $start = $meta['start_date'] ?? '';
            $end = $meta['end_date'] ?? '';
            $appName = 'System Management Workstation';
            $reportTitle = 'Engineering Operational Report';

            $logoPath = public_path('Rumah Atsiri Indonesia Logogram Black.png');
            $logoData = null;
            if (file_exists($logoPath)) {
                $logoData = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
            }

            $n = fn ($v) => is_numeric($v) ? (int) $v : 0;
            $pct = function ($num, $den) {
                $num = (float) $num;
                $den = (float) $den;
                if ($den <= 0) return null;
                return (int) round(($num / $den) * 100);
            };

            $kTotal = $n($kpis['total_logs'] ?? 0);
            $kBug = $n($kpis['bug_logs'] ?? 0);
            $kFix = $n($kpis['fix_logs'] ?? 0);
            $kProgress = $n($kpis['progress_logs'] ?? 0);
            $kProgressOn = $n($kpis['progress_on_progress'] ?? 0);
            $kProgressDone = $n($kpis['progress_done'] ?? 0);
            $kCompletion = $n($kpis['completion_rate'] ?? 0);
            $kOpenBug = $n($kpis['open_bug_count'] ?? 0);
            $kResolvedBug = $n($kpis['resolved_bug_count'] ?? 0);
            $kSlaOnTimeRate = $n($kpis['sla_on_time_rate'] ?? 0);
            $kSlaOnTime = $n($kpis['sla_on_time'] ?? 0);
            $kSlaLate = $n($kpis['sla_late'] ?? 0);

            $progressCompletion = $pct($kProgressDone, max(1, $kProgress)) ?? 0;
            $progressWorkload = $pct($kProgressOn, max(1, $kProgress)) ?? 0;
            $fixVelocity = $kBug > 0 ? $pct($kFix, $kBug) : null;
            $backlogPressure = $kBug > 0 ? $pct($kOpenBug, $kBug) : null;
            $slaCompliance = ($kSlaOnTime + $kSlaLate) > 0 ? $pct($kSlaOnTime, ($kSlaOnTime + $kSlaLate)) : null;
            $lateRate = ($kSlaOnTime + $kSlaLate) > 0 ? $pct($kSlaLate, ($kSlaOnTime + $kSlaLate)) : null;
            $deliveryBalanceRate = $kProgressOn > 0 ? $pct($kProgressDone, max(1, $kProgressOn)) : null;

            $impact = $charts['impact_distribution'] ?? [];
            $impactCritical = $n($impact['critical'] ?? 0);
            $impactHigh = $n($impact['high'] ?? 0);
            $impactMedium = $n($impact['medium'] ?? 0);
            $impactLow = $n($impact['low'] ?? 0);

            $tone = function ($key) {
                return match ($key) {
                    'green' => 'pill pill-green',
                    'yellow' => 'pill pill-yellow',
                    'red' => 'pill pill-red',
                    default => 'pill pill-slate',
                };
            };

            $fmtDate = fn ($dt, $fmt = 'l, d M Y') => $dt
                ? \Carbon\Carbon::parse($dt)->locale('id')->translatedFormat($fmt)
                : '-';

            $execSummary = function () use ($kTotal, $kProgress, $kBug, $kFix, $kCompletion, $kSlaOnTimeRate, $kProgressDone, $kProgressOn, $kOpenBug, $impactCritical, $impactHigh) {
                if ($kTotal === 0) {
                    return 'Pada periode report ini tidak terdapat aktivitas log yang tercatat untuk filter yang dipilih.';
                }

                $dominant = $kProgress >= max(1, ($kBug + $kFix)) ? 'Aktivitas didominasi progress development.' : 'Aktivitas issue handling dan progress berjalan beriringan.';

                $delivery = $kProgress > 0
                    ? ($kProgressDone >= $kProgressOn ? 'Sebagian besar progress bergerak menuju status Done.' : 'Sebagian pekerjaan masih aktif di On Progress dan perlu dipantau.')
                    : 'Tidak ada progress activity pada periode ini.';

                $issue = ($kBug + $kFix) > 0
                    ? "Pada sisi issue handling, terdapat {$kBug} bug dan {$kFix} fix."
                    : "Pada sisi issue handling, tidak ada aktivitas bug/fix yang tercatat.";

                $sla = $kFix > 0
                    ? "SLA on-time berada di {$kSlaOnTimeRate}% pada fix yang resolved."
                    : "SLA belum dapat dievaluasi karena belum ada fix yang resolved.";

                $backlog = $kOpenBug > 0
                    ? "Backlog bug masih aktif (open: {$kOpenBug}) dan perlu dipantau."
                    : "Backlog bug tidak terlihat dominan pada periode ini.";

                $severity = ($impactCritical + $impactHigh) > 0
                    ? "Terdapat issue berdampak tinggi yang perlu perhatian (Critical/High: ".($impactCritical + $impactHigh).")."
                    : null;

                $lines = [
                    "Selama periode report, tercatat {$kTotal} aktivitas log.",
                    $dominant,
                    "Completion rate tercatat {$kCompletion}%.",
                    $delivery,
                    $issue,
                    $sla,
                    $backlog,
                    $severity,
                ];

                return collect($lines)->filter()->implode(' ');
            };

            $insights = function () use ($kTotal, $kOpenBug, $backlogPressure, $slaCompliance, $lateRate, $kProgress, $kProgressOn, $kProgressDone, $impactCritical, $impactHigh, $fixVelocity) {
                if ($kTotal === 0) {
                    return [
                        ['tone' => 'neutral', 'title' => 'No Activity', 'text' => 'Tidak ada aktivitas yang tercatat pada periode ini untuk filter yang dipilih.'],
                    ];
                }

                $out = [];
                if ($impactCritical > 0) {
                    $out[] = ['tone' => 'red', 'title' => 'Issue berdampak tinggi', 'text' => 'Terdapat issue ber-impact Critical pada periode ini; pastikan tindak lanjut prioritas.'];
                } elseif ($impactHigh >= 2) {
                    $out[] = ['tone' => 'yellow', 'title' => 'Konsentrasi impact tinggi', 'text' => 'Beberapa bug ber-impact High tercatat; perlu dipantau untuk mencegah eskalasi.'];
                }

                if (($backlogPressure ?? 0) >= 60 || $kOpenBug >= 10) {
                    $out[] = ['tone' => 'red', 'title' => 'Backlog perlu perhatian', 'text' => 'Backlog bug masih aktif dan cukup menekan; fokus pada penurunan open bug.'];
                } elseif (($backlogPressure ?? 0) >= 35 || $kOpenBug >= 3) {
                    $out[] = ['tone' => 'yellow', 'title' => 'Backlog masih aktif', 'text' => 'Backlog bug masih aktif; perlu dipantau hingga tren menurun.'];
                }

                if ($slaCompliance !== null && ($slaCompliance < 75)) {
                    $out[] = ['tone' => 'red', 'title' => 'SLA perlu perhatian', 'text' => 'Sebagian penyelesaian melewati target SLA; perlu perbaikan pada alur resolution.'];
                } elseif ($slaCompliance !== null && ($slaCompliance < 90)) {
                    $out[] = ['tone' => 'yellow', 'title' => 'SLA perlu dipantau', 'text' => 'SLA relatif stabil, namun masih ada keterlambatan yang perlu dipantau.'];
                }

                if ($kProgress > 0) {
                    if ($kProgressOn >= 5 && $kProgressDone === 0) {
                        $out[] = ['tone' => 'red', 'title' => 'Eksekusi tertahan', 'text' => 'Workload aktif tinggi, namun penyelesaian progress belum terbentuk pada periode ini.'];
                    } elseif ($kProgressDone >= $kProgressOn && $kProgress >= 4) {
                        $out[] = ['tone' => 'green', 'title' => 'Delivery stabil', 'text' => 'Penyelesaian progress mendominasi; delivery terlihat stabil pada periode ini.'];
                    } elseif ($kProgressOn > $kProgressDone) {
                        $out[] = ['tone' => 'yellow', 'title' => 'Workload aktif mendominasi', 'text' => 'Banyak pekerjaan sudah dimulai dan masih aktif; jaga closure agar tidak menumpuk.'];
                    }
                }

                if ($fixVelocity !== null && $fixVelocity >= 120) {
                    $out[] = ['tone' => 'green', 'title' => 'Recovery penyelesaian', 'text' => 'Laju penyelesaian fix melampaui bug baru; recovery terlihat baik.'];
                } elseif ($fixVelocity !== null && $fixVelocity < 60) {
                    $out[] = ['tone' => 'yellow', 'title' => 'Fix tertinggal', 'text' => 'Penyelesaian fix masih tertinggal dari bug baru; prioritaskan closure untuk menjaga backlog.'];
                }

                return collect($out)->take(4)->values()->all();
            };

            $derivedMetrics = function () use ($kProgress, $kBug, $kFix, $kSlaOnTime, $kSlaLate, $kProgressOn, $progressCompletion, $progressWorkload, $fixVelocity, $slaCompliance, $backlogPressure, $lateRate, $deliveryBalanceRate) {
                return [
                    [
                        'label' => 'Progress Completion',
                        'value' => $kProgress === 0 ? 'No Activity' : $progressCompletion.'%',
                        'hint' => $kProgress === 0 ? 'Tidak ada progress log pada periode ini' : ($progressCompletion >= 70 ? 'Mayoritas progress mencapai Done' : ($progressCompletion >= 40 ? 'Sebagian progress sudah selesai' : 'Penyelesaian masih rendah')),
                        'tone' => $kProgress === 0 ? 'neutral' : ($progressCompletion >= 70 ? 'green' : ($progressCompletion >= 40 ? 'yellow' : 'red')),
                    ],
                    [
                        'label' => 'Active Workload',
                        'value' => $kProgress === 0 ? 'Idle' : $progressWorkload.'%',
                        'hint' => $kProgress === 0 ? 'Tidak ada progress aktif pada periode ini' : ($progressWorkload <= 25 ? 'Workload aktif relatif rendah' : ($progressWorkload <= 60 ? 'Workload aktif berjalan stabil' : 'Workload aktif mendominasi')),
                        'tone' => $kProgress === 0 ? 'neutral' : ($progressWorkload >= 75 ? 'yellow' : 'neutral'),
                    ],
                    [
                        'label' => 'Fix Velocity',
                        'value' => $kBug === 0 ? 'No Bug Activity' : ($fixVelocity ?? 0).'%',
                        'hint' => $kBug === 0 ? 'Tidak ada bug log pada periode ini' : (($fixVelocity ?? 0) >= 120 ? 'Closure lebih cepat dari bug baru' : (($fixVelocity ?? 0) >= 85 ? 'Penyelesaian relatif seimbang' : (($fixVelocity ?? 0) >= 60 ? 'Fix sedikit tertinggal' : 'Fix tertinggal dari bug baru'))),
                        'tone' => $kBug === 0 ? 'neutral' : (($fixVelocity ?? 0) >= 85 ? 'green' : (($fixVelocity ?? 0) >= 60 ? 'yellow' : 'red')),
                    ],
                    [
                        'label' => 'SLA Compliance',
                        'value' => ($kSlaOnTime + $kSlaLate) === 0 ? 'Not Available' : ($slaCompliance ?? 0).'%',
                        'hint' => ($kSlaOnTime + $kSlaLate) === 0 ? 'Belum ada fix resolved pada periode ini' : (($slaCompliance ?? 0) >= 90 ? 'Kepatuhan SLA stabil' : (($slaCompliance ?? 0) >= 75 ? 'SLA perlu dipantau' : 'SLA perlu perhatian')).(($lateRate ?? 0) > 0 ? ' · Late '.($lateRate ?? 0).'%' : ''),
                        'tone' => ($kSlaOnTime + $kSlaLate) === 0 ? 'neutral' : (($slaCompliance ?? 0) >= 90 ? 'green' : (($slaCompliance ?? 0) >= 75 ? 'yellow' : 'red')),
                    ],
                    [
                        'label' => 'Backlog Pressure',
                        'value' => $kBug === 0 ? 'Stable' : ($backlogPressure ?? 0).'%',
                        'hint' => $kBug === 0 ? 'Tidak ada bug activity pada periode ini' : 'Proporsi bug open terhadap bug tercatat',
                        'tone' => $kBug === 0 ? 'neutral' : (($backlogPressure ?? 0) >= 60 ? 'red' : (($backlogPressure ?? 0) >= 35 ? 'yellow' : 'neutral')),
                    ],
                    [
                        'label' => 'Delivery Balance',
                        'value' => $kProgressOn === 0 ? 'Not Available' : ($deliveryBalanceRate ?? 0).'%',
                        'hint' => $kProgressOn === 0 ? 'Tidak ada progress aktif untuk dibandingkan' : 'Perbandingan Done terhadap On Progress',
                        'tone' => $kProgressOn === 0 ? 'neutral' : (($deliveryBalanceRate ?? 0) >= 75 ? 'green' : (($deliveryBalanceRate ?? 0) >= 40 ? 'yellow' : 'red')),
                    ],
                ];
            };

            $svgBar = function ($labelA, $valueA, $labelB, $valueB) {
                $max = max(1, (int) max($valueA, $valueB));
                $w = 240; $h = 82;
                $barW = 80; $gap = 40;
                $scale = 48 / $max;
                $aH = (int) round($valueA * $scale);
                $bH = (int) round($valueB * $scale);
                $baseY = 58;
                $aX = 40;
                $bX = $aX + $barW + $gap;
                $x2 = $w - 16;
                $aValX = $aX + $barW - 2;
                $bValX = $bX + $barW - 2;
                $aValY = $baseY - $aH - 4;
                $bValY = $baseY - $bH - 4;
                $aY = $baseY - $aH;
                $bY = $baseY - $bH;
                $labelY = $baseY + 16;
                return <<<SVG
<svg width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}" xmlns="http://www.w3.org/2000/svg">
  <rect x="0" y="0" width="{$w}" height="{$h}" fill="#ffffff"/>
  <line x1="24" y1="{$baseY}" x2="{$x2}" y2="{$baseY}" stroke="#e2e8f0" stroke-width="1"/>
  <rect x="{$aX}" y="{$aY}" width="{$barW}" height="{$aH}" fill="#111827"/>
  <rect x="{$bX}" y="{$bY}" width="{$barW}" height="{$bH}" fill="#64748b"/>
  <text x="{$aX}" y="{$labelY}" font-size="9" fill="#334155">{$labelA}</text>
  <text x="{$bX}" y="{$labelY}" font-size="9" fill="#334155">{$labelB}</text>
  <text x="{$aValX}" y="{$aValY}" text-anchor="end" font-size="9" fill="#334155">{$valueA}</text>
  <text x="{$bValX}" y="{$bValY}" text-anchor="end" font-size="9" fill="#334155">{$valueB}</text>
</svg>
SVG;
            };

            $svgDonut = function ($aLabel, $aVal, $bLabel, $bVal) {
                $total = max(1, (int) ($aVal + $bVal));
                $aPct = ($aVal / $total) * 100;
                $circ = 2 * pi() * 18;
                $dashA = ($aPct / 100) * $circ;
                $dashB = $circ - $dashA;
                $aValTxt = (int) $aVal;
                $bValTxt = (int) $bVal;
                return <<<SVG
<svg width="240" height="82" viewBox="0 0 240 82" xmlns="http://www.w3.org/2000/svg">
  <rect x="0" y="0" width="240" height="82" fill="#ffffff"/>
  <g transform="translate(44,41)">
    <circle r="18" fill="none" stroke="#e2e8f0" stroke-width="8"/>
    <circle r="18" fill="none" stroke="#111827" stroke-width="8" stroke-dasharray="{$dashA} {$dashB}" stroke-dashoffset="0" transform="rotate(-90)"/>
  </g>
  <text x="76" y="30" font-size="9" fill="#334155">{$aLabel}</text>
  <text x="160" y="30" font-size="9" fill="#334155">{$bLabel}</text>
  <text x="76" y="46" font-size="13" font-weight="700" fill="#0f172a">{$aValTxt}</text>
  <text x="160" y="46" font-size="13" font-weight="700" fill="#0f172a">{$bValTxt}</text>
</svg>
SVG;
            };
        @endphp

        {{-- Page number (DomPDF) --}}
        <script type="text/php">
            if (isset($pdf)) {
                $pdf->page_text(16, 815, "{{ $appName }} · {{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i') }}", null, 8, [100,116,139]);
                $pdf->page_text(520, 815, "Page {PAGE_NUM} / {PAGE_COUNT}", null, 8, [100,116,139]);
            }
        </script>

        {{-- 1) REPORT HEADER --}}
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 52px; vertical-align: top;">
                    @if($logoData)
                        <img src="{{ $logoData }}" alt="Logo" style="width: 44px; height: 44px;">
                    @endif
                </td>
                <td style="vertical-align: top;">
                    <div class="h1">{{ $reportTitle }}</div>
                    <div class="muted">Generated from engineering activity logs</div>
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
                <td style="width: 170px; text-align: right; vertical-align: top;">
                    <div class="small muted">Application</div>
                    <div style="font-weight: 700;">{{ $appName }}</div>
                    <div class="small muted" style="margin-top: 6px;">Report Scope</div>
                    <div class="small subtle">Progress · Bug · Fix</div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- 2) KPI OVERVIEW --}}
        <div class="h2">KPI Overview</div>
        <table class="kpi">
            <tr>
                <td>
                    <div class="label">Total Logs</div>
                    <div class="value">{{ $kTotal }}</div>
                    <div class="hint">Snapshot total aktivitas pada periode ini</div>
                </td>
                <td>
                    <div class="label">Bug Logs</div>
                    <div class="value">{{ $kBug }}</div>
                    <div class="hint">Open: {{ $kOpenBug }} · Resolved: {{ $kResolvedBug }}</div>
                </td>
                <td>
                    <div class="label">Fix Logs</div>
                    <div class="value">{{ $kFix }}</div>
                    <div class="hint">SLA on-time: {{ $kSlaOnTimeRate }}%</div>
                </td>
                <td>
                    <div class="label">Completion Rate</div>
                    <div class="value">{{ $kCompletion }}%</div>
                    <div class="hint">{{ $kProgressDone }} Done · {{ $bug_summary['resolved_fix'] ?? 0 }} Fix resolved</div>
                </td>
                <td>
                    <div class="label">Progress Status</div>
                    <div class="value">{{ $kProgress }}</div>
                    <div class="hint">{{ $kProgressOn }} On Progress · {{ $kProgressDone }} Done</div>
                </td>
            </tr>
        </table>

        {{-- 3) EXECUTIVE SUMMARY --}}
        <div class="h2">Executive Summary</div>
        <div class="card card-muted">
            <div class="subtle">{{ $execSummary() }}</div>
        </div>

        {{-- 4) BUG & FIX OPERATIONAL SUMMARY --}}
        <div class="h2">Bug & Fix Operational Summary</div>
        <table class="two-col">
            <tr>
                <td style="width: 58%;">
                    <div class="card">
                        <div class="h3">Issue management snapshot</div>
                        <div class="muted small" style="margin-bottom: 8px;">
                            Fokus pada backlog, SLA, severity, dan laju penyelesaian.
                        </div>

                        <div>
                            <span class="{{ $tone($impactCritical > 0 ? 'red' : ($impactHigh >= 2 ? 'yellow' : 'neutral')) }}">
                                Severity: {{ $impactCritical > 0 ? 'Critical present' : ($impactHigh >= 2 ? 'High concentration' : 'No high severity signal') }}
                            </span>
                            <span class="{{ $tone(($slaCompliance !== null && $slaCompliance < 75) ? 'red' : (($slaCompliance !== null && $slaCompliance < 90) ? 'yellow' : 'neutral')) }}" style="margin-left: 6px;">
                                SLA: {{ $slaCompliance === null ? 'Not available' : $slaCompliance.'% on-time' }}
                            </span>
                            <span class="{{ $tone(($backlogPressure !== null && $backlogPressure >= 60) ? 'red' : (($backlogPressure !== null && $backlogPressure >= 35) ? 'yellow' : 'neutral')) }}" style="margin-left: 6px;">
                                Backlog: {{ $kOpenBug > 0 ? 'Active' : 'Stable' }}
                            </span>
                        </div>

                        <div style="margin-top: 10px;" class="subtle">
                            @php
                                $bugFixNarrative = [];
                                if (($kBug + $kFix) === 0) {
                                    $bugFixNarrative[] = 'Tidak ada aktivitas bug/fix pada periode ini.';
                                } else {
                                    if ($fixVelocity !== null) {
                                        $bugFixNarrative[] = $fixVelocity >= 120
                                            ? 'Laju penyelesaian fix terlihat melampaui bug baru (recovery signal).'
                                            : ($fixVelocity >= 85 ? 'Laju fix relatif seimbang dengan bug baru.' : 'Penyelesaian fix masih tertinggal dari bug baru dan perlu dijaga.');
                                    }
                                    if ($kOpenBug > 0) {
                                        $bugFixNarrative[] = 'Backlog bug masih aktif dan perlu dipantau hingga turun.';
                                    } else {
                                        $bugFixNarrative[] = 'Backlog bug tidak menjadi signal dominan pada periode ini.';
                                    }
                                    if ($slaCompliance !== null) {
                                        $bugFixNarrative[] = $slaCompliance >= 90
                                            ? 'Kepatuhan SLA terlihat stabil.'
                                            : ($slaCompliance >= 75 ? 'SLA masih perlu dipantau.' : 'SLA resolution perlu perhatian pada alur penyelesaian.');
                                    } else {
                                        $bugFixNarrative[] = 'SLA belum dapat dievaluasi karena belum ada fix resolved.';
                                    }
                                }
                            @endphp
                            {!! collect($bugFixNarrative)->map(fn($s) => '<div>• '.$s.'</div>')->implode('') !!}
                        </div>
                    </div>
                </td>
                <td style="width: 42%;">
                    <div class="card">
                        <div class="h3">Key metrics</div>
                        <table style="width:100%; border-collapse: collapse;">
                            <tr>
                                <td class="muted small">Bug</td>
                                <td style="text-align:right; font-weight:700;">{{ $kBug }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">Resolved bug</td>
                                <td style="text-align:right; font-weight:700;">{{ $kResolvedBug }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">Open bug</td>
                                <td style="text-align:right; font-weight:700;">{{ $kOpenBug }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">Fix resolved</td>
                                <td style="text-align:right; font-weight:700;">{{ $bug_summary['resolved_fix'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">SLA on-time</td>
                                <td style="text-align:right; font-weight:700;">{{ $kSlaOnTimeRate }}%</td>
                            </tr>
                            <tr>
                                <td class="muted small">Late (count)</td>
                                <td style="text-align:right; font-weight:700;">{{ $kSlaLate }}</td>
                            </tr>
                        </table>
                        <div class="divider"></div>
                        <div class="h3">Impact summary</div>
                        <div class="muted small">Critical: <strong>{{ $impactCritical }}</strong> · High: <strong>{{ $impactHigh }}</strong> · Medium: <strong>{{ $impactMedium }}</strong> · Low: <strong>{{ $impactLow }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- 5) PROGRESS OPERATIONAL SUMMARY --}}
        <div class="h2">Progress Operational Summary</div>
        <table class="two-col">
            <tr>
                <td style="width: 58%;">
                    <div class="card">
                        <div class="h3">Delivery narrative</div>
                        <div class="muted small" style="margin-bottom: 8px;">
                            Fokus pada delivery, workload, dan execution signal.
                        </div>

                        <div>
                            <span class="{{ $tone($kProgress === 0 ? 'neutral' : ($progressCompletion >= 70 ? 'green' : ($progressCompletion >= 40 ? 'yellow' : 'red'))) }}">
                                Delivery: {{ $kProgress === 0 ? 'No activity' : ($progressCompletion >= 70 ? 'Stable' : ($progressCompletion >= 40 ? 'Mixed' : 'Needs attention')) }}
                            </span>
                            <span class="{{ $tone($kProgress === 0 ? 'neutral' : ($progressWorkload >= 75 ? 'yellow' : 'neutral')) }}" style="margin-left: 6px;">
                                Workload: {{ $kProgress === 0 ? 'Idle' : ($progressWorkload >= 75 ? 'High active load' : 'Balanced') }}
                            </span>
                        </div>

                        <div style="margin-top: 10px;" class="subtle">
                            @php
                                $progressNarrative = [];
                                if ($kProgress === 0) {
                                    $progressNarrative[] = 'Tidak ada progress activity pada periode ini.';
                                } else {
                                    $progressNarrative[] = $kProgressDone >= $kProgressOn
                                        ? 'Mayoritas aktivitas progress berhasil mencapai status Done.'
                                        : 'Sebagian pekerjaan masih berada pada status On Progress dan perlu dipantau.';

                                    if ($kProgressOn === 0) {
                                        $progressNarrative[] = 'Tidak terdapat backlog progress aktif pada akhir periode report.';
                                    } elseif ($kProgressOn >= 6 && $kProgressDone <= 2) {
                                        $progressNarrative[] = 'Workload aktif cukup tinggi dan closure perlu dijaga agar tidak menumpuk.';
                                    } else {
                                        $progressNarrative[] = 'Workload aktif bergerak normal; jaga konsistensi penutupan item.';
                                    }

                                    if (($kBug + $kFix) > 0 && $kProgress > ($kBug + $kFix)) {
                                        $progressNarrative[] = 'Delivery progress mendominasi dibanding issue handling pada periode ini.';
                                    }
                                }
                            @endphp
                            {!! collect($progressNarrative)->map(fn($s) => '<div>• '.$s.'</div>')->implode('') !!}
                        </div>
                    </div>
                </td>
                <td style="width: 42%;">
                    <div class="card">
                        <div class="h3">Progress snapshot</div>
                        <table style="width:100%; border-collapse: collapse;">
                            <tr>
                                <td class="muted small">Total progress</td>
                                <td style="text-align:right; font-weight:700;">{{ $kProgress }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">Done</td>
                                <td style="text-align:right; font-weight:700;">{{ $kProgressDone }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">On Progress</td>
                                <td style="text-align:right; font-weight:700;">{{ $kProgressOn }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">Completion</td>
                                <td style="text-align:right; font-weight:700;">{{ $kProgress === 0 ? '—' : $progressCompletion.'%' }}</td>
                            </tr>
                            <tr>
                                <td class="muted small">Active workload</td>
                                <td style="text-align:right; font-weight:700;">{{ $kProgress === 0 ? '—' : $progressWorkload.'%' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- 6) OPERATIONAL INSIGHTS --}}
        <div class="h2">Operational Insights</div>
        @php $ins = $insights(); @endphp
        <table class="grid">
            <tr>
                @foreach($ins as $i)
                    @php
                        $t = $i['tone'] ?? 'neutral';
                        $cls = $t === 'green' ? 'pill pill-green' : ($t === 'yellow' ? 'pill pill-yellow' : ($t === 'red' ? 'pill pill-red' : 'pill pill-slate'));
                    @endphp
                    <td style="width: {{ floor(100 / max(1, count($ins))) }}%;">
                        <div class="small muted" style="margin-bottom: 4px;">{{ $i['title'] }}</div>
                        <div class="subtle">{{ $i['text'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>

        {{-- 7) OPERATIONAL METRICS --}}
        <div class="h2">Operational Metrics</div>
        @php $dm = $derivedMetrics(); @endphp
        <table class="grid">
            @foreach(array_chunk($dm, 3) as $row)
                <tr>
                    @foreach($row as $m)
                        <td style="width: 33.33%;">
                            <div class="label muted small">{{ $m['label'] }}</div>
                            <div style="font-size: 14px; font-weight: 700; margin-top: 2px;">
                                {{ $m['value'] }}
                                @php
                                    $tag = match ($m['tone'] ?? 'neutral') {
                                        'green' => 'OK',
                                        'yellow' => 'Watch',
                                        'red' => 'Attention',
                                        default => 'Note',
                                    };
                                @endphp
                                <span class="{{ $tone($m['tone'] ?? 'neutral') }}" style="margin-left: 6px; vertical-align: middle;">
                                    {{ $tag }}
                                </span>
                            </div>
                            <div class="hint muted small">{{ $m['hint'] }}</div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>

        {{-- 8) VISUAL SUMMARY --}}
        <div class="h2">Visual Summary</div>
        <table class="grid">
            <tr>
                <td style="width: 50%;">
                    <div class="h3">Bug vs Fix</div>
                    <div class="muted small" style="margin-bottom: 6px;">Issue vs resolution ratio (period)</div>
                    {!! $svgBar('Bug', $charts['bug_vs_fix']['bug'] ?? 0, 'Fix', $charts['bug_vs_fix']['fix'] ?? 0) !!}
                </td>
                <td style="width: 50%;">
                    <div class="h3">SLA On-Time vs Late</div>
                    <div class="muted small" style="margin-bottom: 6px;">Resolved fixes (period)</div>
                    {!! $svgDonut('On-time', $charts['sla_on_time']['on_time'] ?? 0, 'Late', $charts['sla_on_time']['late'] ?? 0) !!}
                </td>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <div class="h3">Progress Status</div>
                    <div class="muted small" style="margin-bottom: 6px;">On Progress vs Done (period)</div>
                    {!! $svgDonut('On Prog', $charts['progress_status']['on_progress'] ?? 0, 'Done', $charts['progress_status']['done'] ?? 0) !!}
                </td>
                <td style="width: 50%;">
                    <div class="h3">Issue Impact Distribution</div>
                    <div class="muted small" style="margin-bottom: 6px;">Bug impact breakdown</div>
                    @php
                        $impactTotal = max(1, $impactCritical + $impactHigh + $impactMedium + $impactLow + $n($impact['unknown'] ?? 0));
                        $bar = function ($label, $val) use ($impactTotal) {
                            $w = (int) round(($val / $impactTotal) * 160);
                            $val = (int) $val;
                            return '<div style="margin-bottom:6px;"><span class="muted small" style="display:inline-block;width:60px;">'.$label.'</span><span style="display:inline-block;width:160px;height:8px;background:#e2e8f0;border-radius:6px;vertical-align:middle;"><span style="display:inline-block;width:'.$w.'px;height:8px;background:#111827;border-radius:6px;"></span></span><span class="muted small" style="margin-left:8px;">'.$val.'</span></div>';
                        };
                    @endphp
                    {!! $bar('Critical', $impactCritical) !!}
                    {!! $bar('High', $impactHigh) !!}
                    {!! $bar('Medium', $impactMedium) !!}
                    {!! $bar('Low', $impactLow) !!}
                </td>
            </tr>
        </table>

        {{-- 9) DETAILED ACTIVITY LOG --}}
        <div class="h2 page-break">Detailed Activity Log</div>
        <div class="muted small" style="margin-bottom: 8px;">
            Log diurutkan dari aktivitas terbaru. Maksimal 300 entri per export.
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 14%;">System</th>
                    <th style="width: 14%;">Feature</th>
                    <th style="width: 9%;">Type</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Impact</th>
                    <th style="width: 28%;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $date = $log->logged_at ? \Carbon\Carbon::parse($log->logged_at)->locale('id')->translatedFormat('d M Y') : '-';
                        $status = $log->status ?: '-';
                        $type = $log->type ?: '-';
                        $impactVal = $log->impact ?: '-';
                        $refs = $log->references?->where('type', 'bug') ?? collect();
                        $refText = $type === 'fix' && $refs->count() > 0
                            ? 'Fix ('.$refs->count().' bug): '.$refs->pluck('id')->map(fn($id) => '#'.$id)->join(', ')
                            : null;
                        $slaText = null;
                        if ($type === 'fix' && $status === 'resolved') {
                            $slaText = $log->isResolvedOnTime() === true ? 'On-time' : ($log->isResolvedOnTime() === false ? 'Late' : null);
                        }
                        $impactTone = match ($impactVal) {
                            'critical' => 'pill pill-red',
                            'high' => 'pill pill-yellow',
                            'medium' => 'pill pill-slate',
                            'low' => 'pill pill-slate',
                            default => 'pill pill-slate',
                        };
                        $typeTone = match ($type) {
                            'bug' => 'pill pill-red',
                            'fix' => 'pill pill-green',
                            'progress' => 'pill pill-slate',
                            default => 'pill pill-slate',
                        };
                        $statusTone = match ($status) {
                            'open' => 'pill pill-yellow',
                            'resolved' => ($slaText === 'Late' ? 'pill pill-yellow' : 'pill pill-green'),
                            'done' => 'pill pill-green',
                            'on_progress' => 'pill pill-yellow',
                            default => 'pill pill-slate',
                        };
                    @endphp
                    <tr>
                        <td>{{ $date }}</td>
                        <td>{{ $log->system?->name ?? '-' }}</td>
                        <td>{{ $log->feature?->title ?? '-' }}</td>
                        <td><span class="{{ $typeTone }}">{{ $type }}</span></td>
                        <td>
                            <span class="{{ $statusTone }}">{{ $status }}</span>
                            @if($slaText)
                                <div class="meta">SLA: {{ $slaText }}</div>
                            @endif
                        </td>
                        <td><span class="{{ $impactTone }}">{{ $impactVal }}</span></td>
                        <td>
                            <div class="title">{{ $log->title }}</div>
                            @if($refText)
                                <div class="meta">{{ $refText }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">Belum ada aktivitas pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- 10) REPORT NOTES / FOOTER --}}
        <div class="footer-note">
            <div>Report ini dibuat otomatis berdasarkan activity log pada periode terpilih.</div>
            <div>Data mencakup progress, bug, dan fix activity dari seluruh sistem yang terfilter.</div>
        </div>
    </body>
</html>
