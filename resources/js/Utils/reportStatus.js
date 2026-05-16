const TONE = {
    green: "bg-green-50 border-green-200 text-green-800",
    yellow: "bg-yellow-50 border-yellow-200 text-yellow-800",
    red: "bg-red-50 border-red-200 text-red-800",
    neutral: "bg-slate-50 border-slate-200 text-slate-700",
};

const toInt = (v) => {
    const n = Number(v);
    if (!Number.isFinite(n)) return 0;
    return Math.max(0, Math.trunc(n));
};

const clamp = (n, min, max) => Math.min(max, Math.max(min, n));

const pct = (numerator, denominator, decimals = 0) => {
    const n = Number(numerator);
    const d = Number(denominator);
    if (!Number.isFinite(n) || !Number.isFinite(d) || d <= 0) return null;
    const v = (n / d) * 100;
    const p = 10 ** decimals;
    return Math.round(v * p) / p;
};

const fmtPct = (v, decimals = 0) => {
    if (v === null || v === undefined) return "—";
    const n = Number(v);
    if (!Number.isFinite(n)) return "—";
    const fixed = decimals ? n.toFixed(decimals) : String(Math.round(n));
    return `${fixed}%`;
};

const fmtRatioX = (numerator, denominator, decimals = 1) => {
    const n = Number(numerator);
    const d = Number(denominator);
    if (!Number.isFinite(n) || !Number.isFinite(d) || d <= 0) return "—";
    const v = n / d;
    return `${v.toFixed(decimals)}x`;
};

const hashSeed = (s) => {
    const str = String(s ?? "");
    let h = 2166136261;
    for (let i = 0; i < str.length; i += 1) {
        h ^= str.charCodeAt(i);
        h = Math.imul(h, 16777619);
    }
    return h >>> 0;
};

const pick = (list, seed) => {
    if (!Array.isArray(list) || list.length === 0) return "";
    const i = Math.abs(seed) % list.length;
    return list[i];
};

const joinSentences = (parts) => {
    const s = (parts ?? []).filter(Boolean).map((p) => String(p).trim()).filter(Boolean);
    if (s.length === 0) return "";
    // Make sure each sentence ends with a dot.
    return s
        .map((p) => (/[.!?]$/.test(p) ? p : `${p}.`))
        .join(" ");
};

const fmtSigned = (n) => {
    const v = Number(n);
    if (!Number.isFinite(v)) return "—";
    const rounded = Math.round(v);
    if (rounded === 0) return "0";
    return rounded > 0 ? `+${rounded}` : `${rounded}`;
};

const safeText = (s) => String(s ?? "").trim();

const nonEmpty = (s) => {
    const t = safeText(s);
    return t.length ? t : null;
};

export const getBugFixMeetingStatus = ({ kpis, bug_summary, charts } = {}) => {
    const bugLogs = toInt(kpis?.bug_logs);
    const fixLogs = toInt(kpis?.fix_logs);
    const openBugCount = toInt(kpis?.open_bug_count);

    const resolvedFix = toInt(bug_summary?.resolved_fix);
    const onTimeResolved = toInt(bug_summary?.on_time);
    const lateResolved = toInt(bug_summary?.late);

    const slaOnTimeRate =
        typeof kpis?.bug_sla_on_time_rate === "number"
            ? clamp(kpis.bug_sla_on_time_rate, 0, 100)
            : pct(onTimeResolved, resolvedFix, 0) ?? 0;

    const criticalBugCount = toInt(charts?.impact_distribution?.critical ?? bug_summary?.critical_bug_count);
    const highImpactBugCount = toInt(charts?.impact_distribution?.high ?? bug_summary?.high_impact_bug_count);

    const fixRatePct = pct(fixLogs, bugLogs, 0);
    const fixRateX = bugLogs > 0 ? fmtRatioX(fixLogs, bugLogs, 1) : "—";

    const openBugRatePct = pct(openBugCount, bugLogs, 0);
    const lateRatePct = pct(lateResolved, resolvedFix, 0);
    const resolvedSharePct = pct(resolvedFix, fixLogs, 0);

    const lowSample = bugLogs + fixLogs + resolvedFix <= 3;
    const hasSlaSignal = resolvedFix > 0;

    const metrics = {
        bug_logs: bugLogs,
        fix_logs: fixLogs,
        open_bug_count: openBugCount,
        resolved_fix: resolvedFix,
        on_time_resolved: onTimeResolved,
        late_resolved: lateResolved,
        fix_rate_pct: fixRatePct,
        fix_rate_x: fixRateX,
        open_bug_rate_pct: openBugRatePct,
        sla_on_time_rate_pct: hasSlaSignal ? slaOnTimeRate : null,
        late_resolution_rate_pct: hasSlaSignal ? lateRatePct : null,
        resolved_share_pct: fixLogs > 0 ? resolvedSharePct : null,
        critical_bug_count: criticalBugCount || null,
        high_impact_bug_count: highImpactBugCount || null,
        low_sample: lowSample,
    };

    const keySeed = hashSeed(
        JSON.stringify({
            bugLogs,
            fixLogs,
            openBugCount,
            resolvedFix,
            lateResolved,
            slaOnTimeRate,
            criticalBugCount,
            highImpactBugCount,
        })
    );

    // Build signals (prioritized). Keep wording netral, berbasis angka/rasio.
    const signals = [];
    const addSignal = (key, priority, sentence) => {
        if (!sentence) return;
        signals.push({ key, priority, sentence });
    };

    if (bugLogs === 0 && fixLogs === 0) {
        return {
            tone: TONE.neutral,
            title: "Tidak ada aktivitas bug/fix",
            text: "Tidak ada log Bug/Fix pada periode ini.",
            metrics,
        };
    }

    if (criticalBugCount > 0) {
        addSignal(
            "critical_bug",
            100,
            `${criticalBugCount} bug ber-impact Critical tercatat pada periode ini`
        );
    }

    if (bugLogs > 0) {
        // Fix velocity / stability signal
        if (fixLogs === 0) {
            addSignal("fix_velocity", 80, `Belum ada fix log pada periode ini (bug: ${bugLogs})`);
        } else if (fixRatePct !== null) {
            const velocityLine = pick(
                [
                    `Fix coverage ${fmtPct(fixRatePct)} (${fixLogs} fix vs ${bugLogs} bug)`,
                    `Rasio fix/bug ${fixRateX} (${fixLogs}:${bugLogs})`,
                    `Fix vs bug ${fmtPct(fixRatePct)} (${fixLogs} fix, ${bugLogs} bug)`,
                ],
                keySeed + 11
            );
            addSignal("fix_velocity", 55, velocityLine);
        }

        // Backlog pressure (open bugs in-range)
        if (openBugCount === 0) {
            addSignal("backlog", 35, `Tidak ada bug open dari ${bugLogs} bug yang tercatat`);
        } else if (openBugRatePct !== null) {
            addSignal(
                "backlog",
                openBugRatePct >= 50 || openBugCount >= 10 ? 75 : 45,
                `Bug open ${openBugCount}/${bugLogs} (${fmtPct(openBugRatePct)})`
            );
        }
    } else if (fixLogs > 0) {
        // No bugs but fixes exist (likely recovery of earlier backlog)
        const fixPart =
            resolvedFix > 0
                ? `Ada ${fixLogs} fix log; ${resolvedFix} resolved`
                : `Ada ${fixLogs} fix log; belum ada yang resolved`;
        addSignal("no_new_bug", 40, `Tidak ada bug baru pada periode ini. ${fixPart}`);
    }

    // SLA health / late resolution rate (only meaningful when there are resolved fixes)
    if (!hasSlaSignal) {
        if (fixLogs > 0) {
            addSignal("sla", 30, "Belum ada fix yang berstatus resolved, sehingga evaluasi SLA belum terbentuk");
        }
    } else {
        const slaSentence = pick(
            [
                `SLA on-time ${fmtPct(slaOnTimeRate)} (${onTimeResolved}/${resolvedFix})`,
                `Penyelesaian on-time ${fmtPct(slaOnTimeRate)} (${onTimeResolved} dari ${resolvedFix} resolved)`,
                `Kepatuhan SLA ${fmtPct(slaOnTimeRate)} (${onTimeResolved}/${resolvedFix} on-time)`,
            ],
            keySeed + 29
        );
        addSignal("sla", slaOnTimeRate < 75 ? 90 : slaOnTimeRate < 90 ? 60 : 35, slaSentence);

        if (lateResolved > 0 && lateRatePct !== null) {
            addSignal(
                "late_rate",
                lateRatePct >= 35 ? 85 : 50,
                `Late SLA ${fmtPct(lateRatePct)} (${lateResolved}/${resolvedFix})`
            );
        }
    }

    if (highImpactBugCount > 0) {
        addSignal(
            "high_impact_bug",
            65,
            `${highImpactBugCount} bug ber-impact High tercatat pada periode ini`
        );
    }

    // Determine dominant signals (max 2) and tone
    signals.sort((a, b) => b.priority - a.priority);
    const top = signals.slice(0, 2).map((s) => s.sentence);

    let score = 0;
    if (criticalBugCount > 0) score -= 50;
    if (bugLogs > 0 && fixLogs === 0) score -= 35;
    if (fixRatePct !== null && bugLogs >= 4) {
        if (fixRatePct < 60) score -= 30;
        else if (fixRatePct < 85) score -= 15;
        else if (fixRatePct >= 120) score += 10;
    }
    if (openBugRatePct !== null && bugLogs >= 4) {
        if (openBugRatePct >= 60) score -= 25;
        else if (openBugRatePct >= 35) score -= 12;
        else if (openBugRatePct === 0) score += 6;
    }
    if (hasSlaSignal && resolvedFix >= 3) {
        if (slaOnTimeRate < 75) score -= 35;
        else if (slaOnTimeRate < 90) score -= 12;
        else score += 8;
        if (lateRatePct !== null && lateRatePct >= 40) score -= 12;
    }

    // Guard: small samples should not swing tone too aggressively.
    if (lowSample) score = Math.round(score * 0.6);

    const toneKey = score <= -40 ? "red" : score <= -15 ? "yellow" : "green";

    const title = (() => {
        const t = toneKey;
        const options = {
            green: [
                "Penanganan bug/fix stabil",
                "Fix berjalan konsisten",
                "SLA dan backlog relatif terkendali",
            ],
            yellow: [
                "Perlu pemantauan pada backlog/SLA",
                "Ada beberapa signal yang perlu ditindaklanjuti",
                "Fix berjalan, namun masih ada gap yang perlu dipantau",
            ],
            red: [
                "Perlu perhatian pada backlog/SLA",
                "Bug/fix memerlukan tindak lanjut prioritas",
                "Signal perlu perhatian meningkat pada periode ini",
            ],
        };
        return pick(options[t] ?? options.yellow, keySeed + 71);
    })();

    // Keep text short; use the top signals as factual sentences.
    const base = [];
    if (bugLogs > 0 && fixLogs > 0 && fixRatePct !== null) {
        // Provide a compact context lead for most cases.
        const lead = pick(
            [
                `Aktivitas bug ${bugLogs} dan fix ${fixLogs} tercatat`,
                `Periode ini mencatat ${bugLogs} bug dan ${fixLogs} fix`,
                `Terdapat ${bugLogs} bug serta ${fixLogs} fix pada periode ini`,
            ],
            keySeed + 3
        );
        base.push(lead);
    }

    const text = joinSentences([...base.slice(0, 1), ...top]);

    return {
        tone: TONE[toneKey],
        title,
        text,
        metrics,
    };
};

export const getProgressMeetingStatus = ({ kpis } = {}) => {
    const total = toInt(kpis?.progress_logs);
    const onProgress = toInt(kpis?.progress_on_progress);
    const done = toInt(kpis?.progress_done);

    const completionRate = pct(done, total, 0);
    const activeWorkloadRate = pct(onProgress, total, 0);
    const lowSample = total <= 2;

    const metrics = {
        progress_logs: total,
        progress_on_progress: onProgress,
        progress_done: done,
        completion_rate_pct: completionRate,
        active_workload_pct: activeWorkloadRate,
        low_sample: lowSample,
    };

    if (!total) {
        return {
            tone: TONE.neutral,
            title: "Tidak ada aktivitas progress",
            text: "Belum ada progress log pada periode ini.",
            metrics,
        };
    }

    const seed = hashSeed(JSON.stringify({ total, onProgress, done }));

    // Signals
    const signals = [];
    const add = (priority, sentence) => {
        if (!sentence) return;
        signals.push({ priority, sentence });
    };

    if (completionRate !== null) {
        add(
            completionRate >= 70 ? 35 : completionRate >= 40 ? 60 : 90,
            `Completion ${fmtPct(completionRate)} (${done}/${total} done)`
        );
    }

    if (activeWorkloadRate !== null) {
        add(
            activeWorkloadRate >= 70 ? 70 : activeWorkloadRate >= 50 ? 55 : 30,
            `On Progress ${fmtPct(activeWorkloadRate)} (${onProgress}/${total})`
        );
    }

    // Stagnation: meaningful when workload dominates but completion is low.
    if (onProgress >= 5 && done === 0) {
        add(100, `Workload aktif tinggi (${onProgress} On Progress) namun belum ada yang done`);
    } else if (onProgress >= 6 && completionRate !== null && completionRate <= 20) {
        add(85, `Banyak pekerjaan aktif (${onProgress} On Progress) dengan completion ${fmtPct(completionRate)}`);
    }

    signals.sort((a, b) => b.priority - a.priority);
    const top = signals.slice(0, 2).map((s) => s.sentence);

    // Score/tone
    let score = 0;
    if (completionRate !== null) {
        if (completionRate >= 75) score += 18;
        else if (completionRate >= 50) score += 6;
        else if (completionRate <= 25) score -= 18;
        else score -= 6;
    }
    if (activeWorkloadRate !== null) {
        if (activeWorkloadRate >= 75 && (completionRate ?? 0) <= 25) score -= 20; // active-heavy + low completion
        else if (activeWorkloadRate >= 75) score -= 8;
    }
    if (onProgress >= 5 && done === 0) score -= 30;

    if (lowSample) score = Math.round(score * 0.6);

    const toneKey = score <= -35 ? "red" : score <= -10 ? "yellow" : "green";

    const title = (() => {
        const options = {
            green: [
                "Delivery progress stabil",
                "Penyelesaian progress mendominasi",
                "Progress bergerak ke status Done",
            ],
            yellow: [
                "Progress aktif dan perlu dipantau",
                "Workload berjalan, completion perlu dijaga",
                "Sebagian pekerjaan masih On Progress",
            ],
            red: [
                "Completion masih rendah",
                "Workload aktif tertahan",
                "Progress perlu perhatian pada penyelesaian",
            ],
        };
        return pick(options[toneKey] ?? options.yellow, seed + 101);
    })();

    const lead = pick(
        [
            `Total progress ${total} item`,
            `Tercatat ${total} progress pada periode ini`,
            `Aktivitas progress: ${total} item`,
        ],
        seed + 7
    );

    const text = joinSentences([lead, ...top]);

    return {
        tone: TONE[toneKey],
        title,
        text,
        metrics,
    };
};

// Returns 2–4 insight cards (short, data-driven). Avoid empty/no-op statements.
// Shape: [{ key, tone, title, text }]
export const getOperationalInsights = ({ kpis, bug_summary, charts } = {}) => {
    const bugLogs = toInt(kpis?.bug_logs);
    const fixLogs = toInt(kpis?.fix_logs);
    const openBugCount = toInt(kpis?.open_bug_count);

    const progressTotal = toInt(kpis?.progress_logs);
    const progressOn = toInt(kpis?.progress_on_progress);
    const progressDone = toInt(kpis?.progress_done);

    const resolvedFix = toInt(bug_summary?.resolved_fix);
    const onTimeResolved = toInt(bug_summary?.on_time);
    const lateResolved = toInt(bug_summary?.late);

    const slaOnTimeRate = resolvedFix > 0 ? clamp(pct(onTimeResolved, resolvedFix, 0) ?? 0, 0, 100) : null;
    const lateRate = resolvedFix > 0 ? clamp(pct(lateResolved, resolvedFix, 0) ?? 0, 0, 100) : null;

    const fixRate = bugLogs > 0 ? clamp(pct(fixLogs, bugLogs, 0) ?? 0, 0, 999) : null;
    const openBugRate = bugLogs > 0 ? clamp(pct(openBugCount, bugLogs, 0) ?? 0, 0, 100) : null;

    const progressCompletion = progressTotal > 0 ? clamp(pct(progressDone, progressTotal, 0) ?? 0, 0, 100) : null;
    const progressWorkload = progressTotal > 0 ? clamp(pct(progressOn, progressTotal, 0) ?? 0, 0, 100) : null;

    const criticalBugCount = toInt(charts?.impact_distribution?.critical ?? bug_summary?.critical_bug_count);
    const highImpactBugCount = toInt(charts?.impact_distribution?.high ?? bug_summary?.high_impact_bug_count);

    const seed = hashSeed(
        JSON.stringify({
            bugLogs,
            fixLogs,
            openBugCount,
            resolvedFix,
            lateResolved,
            progressTotal,
            progressOn,
            progressDone,
            criticalBugCount,
            highImpactBugCount,
        })
    );

    const insights = [];
    const add = ({ key, score, tone, title, text }) => {
        const t = nonEmpty(text);
        const tt = nonEmpty(title);
        if (!t || !tt) return;
        insights.push({ key, score, tone, title: tt, text: t });
    };

    // 1) Critical / High impact signal (top priority)
    if (criticalBugCount > 0) {
        add({
            key: "critical_bug",
            score: 100,
            tone: "red",
            title: "Impact tinggi tercatat",
            text: `${criticalBugCount} bug ber-impact Critical muncul pada periode ini.`,
        });
    } else if (highImpactBugCount >= 2) {
        add({
            key: "high_impact_bug",
            score: 85,
            tone: "yellow",
            title: "Konsentrasi issue berdampak tinggi",
            text: `${highImpactBugCount} bug ber-impact High tercatat pada periode ini.`,
        });
    }

    // 1.5) Activity dominance / focus (avoid repeating KPI numbers)
    const totalActivity = bugLogs + fixLogs + progressTotal;
    const progressShare = totalActivity > 0 ? progressTotal / totalActivity : 0;
    const bugShare = totalActivity > 0 ? bugLogs / totalActivity : 0;

    if (totalActivity > 0) {
        if (progressShare >= 0.6 && progressTotal >= 3) {
            add({
                key: "focus_progress",
                score: 62,
                tone: "neutral",
                title: "Fokus aktivitas pada progress",
                text: pick(
                    [
                        "Periode ini lebih banyak berfokus pada progress development.",
                        "Aktivitas periode ini didominasi pekerjaan progress.",
                        "Fokus utama periode ini berada pada penyelesaian progress.",
                    ],
                    seed + 21
                ),
            });
        } else if (bugShare <= 0.2 && progressTotal > 0 && bugLogs > 0) {
            add({
                key: "bug_not_dominant",
                score: 45,
                tone: "neutral",
                title: "Bug handling tidak dominan",
                text: pick(
                    [
                        "Bug handling tidak menjadi aktivitas dominan pada periode ini.",
                        "Aktivitas bug tidak mendominasi dibanding pekerjaan lain pada periode ini.",
                    ],
                    seed + 22
                ),
            });
        }
    }

    // 2) Backlog pressure (avoid empty "tidak ada bug open")
    if (openBugCount >= 3 || (openBugRate !== null && openBugRate >= 35)) {
        add({
            key: "backlog_pressure",
            score: openBugRate !== null && openBugRate >= 60 ? 90 : 70,
            tone: openBugRate !== null && openBugRate >= 60 ? "red" : "yellow",
            title: "Backlog bug masih aktif",
            text: openBugRate !== null
                ? `Bug open ${openBugCount}/${bugLogs} (${fmtPct(openBugRate)}); perlu dipantau hingga turun.`
                : `Bug open ${openBugCount} item; perlu dipantau hingga turun.`,
        });
    }

    // 3) SLA health (only when there is signal)
    if (resolvedFix >= 3 && slaOnTimeRate !== null) {
        if (slaOnTimeRate < 75) {
            add({
                key: "sla_drop",
                score: 92,
                tone: "red",
                title: "Kepatuhan SLA perlu perhatian",
                text: pick(
                    [
                        "Kepatuhan SLA menurun; sebagian penyelesaian melewati target.",
                        "Sebagian penyelesaian melewati target SLA dan perlu tindak lanjut.",
                    ],
                    seed + 31
                ),
            });
        } else if (slaOnTimeRate < 90) {
            add({
                key: "sla_watch",
                score: 68,
                tone: "yellow",
                title: "SLA masih perlu dipantau",
                text: pick(
                    [
                        "Penyelesaian cukup on-track, namun masih ada sebagian yang melewati target SLA.",
                        "SLA relatif stabil, tetapi masih ada keterlambatan yang perlu dipantau.",
                    ],
                    seed + 32
                ),
            });
        } else if (lateResolved > 0) {
            add({
                key: "sla_good_with_late",
                score: 40,
                tone: "green",
                title: "SLA relatif stabil",
                text: pick(
                    [
                        "SLA relatif stabil; hanya sebagian kecil penyelesaian yang melewati target.",
                        "Kepatuhan SLA terlihat stabil pada periode ini.",
                    ],
                    seed + 33
                ),
            });
        }
    }

    // 4) Fix velocity vs new bugs (recovery / gap)
    if (bugLogs >= 4 && fixRate !== null) {
        if (fixRate < 60) {
            add({
                key: "fix_gap",
                score: 80,
                tone: "yellow",
                title: "Fix tertinggal dari bug baru",
                text: pick(
                    [
                        "Periode ini bug baru lebih cepat dibanding penyelesaiannya; closure perlu dijaga agar backlog tidak naik.",
                        "Laju penyelesaian masih tertinggal dari bug baru; perlu fokus pada closure.",
                    ],
                    seed + 41
                ),
            });
        } else if (fixRate >= 120) {
            add({
                key: "recovery",
                score: 55,
                tone: "green",
                title: "Recovery penyelesaian terlihat",
                text: pick(
                    [
                        "Laju penyelesaian terlihat melampaui bug baru pada periode ini.",
                        "Recovery penyelesaian cukup kuat; closure bergerak lebih cepat dari penambahan bug.",
                    ],
                    seed + 42
                ),
            });
        }
    } else if (bugLogs === 0 && fixLogs >= 3) {
        add({
            key: "no_new_bug",
            score: 50,
            tone: "green",
            title: "Tidak ada bug baru",
            text: resolvedFix > 0
                ? `Tidak ada bug baru; terdapat ${resolvedFix}/${fixLogs} fix yang terselesaikan pada periode ini.`
                : `Tidak ada bug baru; terdapat ${fixLogs} fix log pada periode ini.`,
        });
    }

    // 5) Progress delivery / stagnation
    if (progressTotal > 0 && progressCompletion !== null && progressWorkload !== null) {
        if (progressOn >= 5 && progressDone === 0) {
            add({
                key: "progress_stagnation",
                score: 88,
                tone: "red",
                title: "Penyelesaian progress tertahan",
                text: pick(
                    [
                        "Banyak progress masih tertahan di On Progress; penyelesaian perlu dipercepat.",
                        "Workload aktif tinggi, namun completion belum terbentuk pada periode ini.",
                    ],
                    seed + 51
                ),
            });
        } else if (progressWorkload >= 70 && progressCompletion <= 25 && progressTotal >= 6) {
            add({
                key: "progress_watch",
                score: 72,
                tone: "yellow",
                title: "Workload aktif mendominasi",
                text: pick(
                    [
                        "Workload aktif mendominasi; dorong beberapa item ke status Done untuk menjaga flow.",
                        "Banyak pekerjaan sudah dimulai, namun closure masih perlu dijaga.",
                    ],
                    seed + 52
                ),
            });
        } else if (progressCompletion >= 70 && progressTotal >= 4) {
            add({
                key: "progress_delivery",
                score: 45,
                tone: "green",
                title: "Delivery progress stabil",
                text: pick(
                    [
                        "Mayoritas aktivitas progress berhasil mencapai status Done pada periode ini.",
                        "Penyelesaian progress mendominasi aktivitas periode ini.",
                        "Delivery progress terlihat stabil; sebagian besar item berhasil ditutup.",
                    ],
                    seed + 53
                ),
            });
        }
    }

    // Pick 2–4 best insights, with mild diversity by key group
    insights.sort((a, b) => b.score - a.score);
    const picked = [];
    const usedGroups = new Set();
    const groupOf = (k) => {
        if (k.startsWith("sla")) return "sla";
        if (k.includes("backlog")) return "backlog";
        if (k.includes("fix")) return "fix";
        if (k.includes("progress")) return "progress";
        if (k.includes("impact")) return "impact";
        return "other";
    };

    for (const it of insights) {
        const g = groupOf(it.key);
        if (usedGroups.has(g) && picked.length < 2) {
            // Allow duplicates early if we still need minimum insights.
        } else if (usedGroups.has(g) && picked.length >= 2) {
            continue;
        }
        picked.push(it);
        usedGroups.add(g);
        if (picked.length >= 4) break;
    }

    // Ensure minimum 2 insights: add a neutral, observational snapshot (avoid raw counts-heavy repetition).
    if (picked.length < 2) {
        if (totalActivity > 0) {
            const activityLine = pick(
                [
                    "Aktivitas periode ini relatif merata antara progress dan penanganan issue.",
                    "Aktivitas periode ini berjalan stabil tanpa anomali dominan yang menonjol.",
                    "Aktivitas periode ini cenderung normal; tidak ada satu area yang sangat mendominasi.",
                ],
                seed + 17
            );
            picked.push({
                key: "activity_snapshot",
                score: 8,
                tone: "neutral",
                title: "Catatan aktivitas",
                text: activityLine,
            });
        }
    }

    if (picked.length === 0) {
        picked.push({
            key: "no_activity",
            score: 0,
            tone: "neutral",
            title: "Tidak ada aktivitas",
            text: "Periode ini tidak mencatat aktivitas bug/fix maupun progress.",
        });
    }

    // Map tones to UI classes (subtle)
    const toneClass = {
        green: "border-green-200 bg-green-50 text-green-900",
        yellow: "border-yellow-200 bg-yellow-50 text-yellow-900",
        red: "border-red-200 bg-red-50 text-red-900",
        neutral: "border-slate-200 bg-slate-50 text-slate-900",
    };

    return picked
        .slice(0, 4)
        .map((i) => ({
            key: i.key,
            tone: toneClass[i.tone] ?? toneClass.neutral,
            title: i.title,
            text: i.text,
        }));
};

// Derived ratios/percentages for executive-lite reading (no raw duplicates).
// Shape: [{ key, label, value, hint, tone }]
export const getDerivedMetrics = ({ kpis, bug_summary, charts } = {}) => {
    const bugLogs = toInt(kpis?.bug_logs);
    const fixLogs = toInt(kpis?.fix_logs);
    const openBugCount = toInt(kpis?.open_bug_count);

    const progressTotal = toInt(kpis?.progress_logs);
    const progressOn = toInt(kpis?.progress_on_progress);
    const progressDone = toInt(kpis?.progress_done);

    const resolvedFix = toInt(bug_summary?.resolved_fix);
    const onTimeResolved = toInt(bug_summary?.on_time);
    const lateResolved = toInt(bug_summary?.late);

    const criticalBugCount = toInt(charts?.impact_distribution?.critical ?? bug_summary?.critical_bug_count);

    const progressCompletion = progressTotal > 0 ? pct(progressDone, progressTotal, 0) : null;
    const activeWorkload = progressTotal > 0 ? pct(progressOn, progressTotal, 0) : null;

    const fixVelocity = bugLogs > 0 ? pct(fixLogs, bugLogs, 0) : null;
    const backlogPressure = bugLogs > 0 ? pct(openBugCount, bugLogs, 0) : null;

    const slaCompliance = resolvedFix > 0 ? pct(onTimeResolved, resolvedFix, 0) : null;
    const lateRate = resolvedFix > 0 ? pct(lateResolved, resolvedFix, 0) : null;

    const deliveryBalance = progressOn > 0 ? pct(progressDone, progressOn, 0) : null; // done per on_progress (as %)

    const makeTone = (kind) => {
        if (kind === "good") return "text-green-700";
        if (kind === "warn") return "text-yellow-800";
        if (kind === "risk") return "text-red-700";
        return "text-slate-700";
    };

    const completionMetric = (() => {
        if (progressTotal === 0) {
            return {
                key: "progress_completion",
                label: "Progress Completion",
                value: "No Activity",
                hint: "Tidak ada progress log pada periode ini",
                tone: makeTone("neutral"),
            };
        }

        const v = Math.round(progressCompletion ?? 0);
        const hint =
            v >= 70
                ? "Mayoritas progress mencapai Done"
                : v >= 40
                ? "Sebagian progress sudah selesai"
                : "Penyelesaian masih rendah";
        return {
            key: "progress_completion",
            label: "Progress Completion",
            value: `${v}%`,
            hint,
            tone: makeTone(v >= 70 ? "good" : v >= 40 ? "warn" : "risk"),
        };
    })();

    const workloadMetric = (() => {
        if (progressTotal === 0) {
            return {
                key: "active_workload",
                label: "Active Workload",
                value: "Idle",
                hint: "Tidak ada progress aktif pada periode ini",
                tone: makeTone("neutral"),
            };
        }

        const v = Math.round(activeWorkload ?? 0);
        const hint =
            v <= 25
                ? "Workload aktif relatif rendah"
                : v <= 60
                ? "Workload aktif berjalan stabil"
                : "Workload aktif mendominasi";
        return {
            key: "active_workload",
            label: "Active Workload",
            value: `${v}%`,
            hint,
            tone: makeTone(v <= 60 ? "neutral" : "warn"),
        };
    })();

    const fixVelocityMetric = (() => {
        if (bugLogs === 0) {
            return {
                key: "fix_velocity",
                label: "Fix Velocity",
                value: "No Bug Activity",
                hint: "Tidak ada bug log pada periode ini",
                tone: makeTone("neutral"),
            };
        }

        const v = Math.round(fixVelocity ?? 0);
        const hint =
            v >= 120
                ? "Closure lebih cepat dari bug baru"
                : v >= 85
                ? "Penyelesaian relatif seimbang"
                : v >= 60
                ? "Fix sedikit tertinggal"
                : "Fix tertinggal dari bug baru";
        return {
            key: "fix_velocity",
            label: "Fix Velocity",
            value: `${v}%`,
            hint,
            tone: makeTone(v >= 85 ? "good" : v >= 60 ? "warn" : "risk"),
        };
    })();

    const slaMetric = (() => {
        if (resolvedFix === 0) {
            return {
                key: "sla_compliance",
                label: "SLA Compliance",
                value: "Not Available",
                hint: "Belum ada fix resolved pada periode ini",
                tone: makeTone("neutral"),
            };
        }

        const v = Math.round(slaCompliance ?? 0);
        const hint =
            v >= 90
                ? "Kepatuhan SLA stabil"
                : v >= 75
                ? "SLA perlu dipantau"
                : "SLA perlu perhatian";
        return {
            key: "sla_compliance",
            label: "SLA Compliance",
            value: `${v}%`,
            hint: lateRate !== null && lateResolved > 0 ? `${hint} · Late ${Math.round(lateRate)}%` : hint,
            tone: makeTone(v >= 90 ? "good" : v >= 75 ? "warn" : "risk"),
        };
    })();

    // Keep layout balanced (4 cards). Critical/high-impact stay in insights (not duplicated here).
    return [completionMetric, workloadMetric, fixVelocityMetric, slaMetric];
};
