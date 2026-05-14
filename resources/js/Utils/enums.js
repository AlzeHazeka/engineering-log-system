// ==============================
// SYSTEM STATUS
// ==============================

export const systemStatusMap = {
    active: "bg-green-100 text-green-700",
    maintenance: "bg-yellow-100 text-yellow-700",
    paused: "bg-gray-100 text-gray-700",
    deprecated: "bg-red-100 text-red-700",
};

export const systemStatusLabel = {
    active: "Active",
    maintenance: "Maintenance",
    paused: "Paused",
    deprecated: "Deprecated",
};

// ==============================
// SYSTEM KIND (APP/WEB vs SERVER)
// ==============================

export const systemKindMap = {
    app: "bg-slate-100 text-slate-700",
    server: "bg-indigo-100 text-indigo-700",
};

export const systemKindLabel = {
    app: "App / Web",
    server: "Server",
};

// ==============================
// SYSTEM LIFECYCLE STAGE
// ==============================

export const systemStageMap = {
    planning: "bg-gray-100 text-gray-700",
    development: "bg-blue-100 text-blue-700",
    production: "bg-green-100 text-green-700",
    maintenance: "bg-yellow-100 text-yellow-700",
};

export const systemStageLabel = {
    planning: "Planning",
    development: "Development",
    production: "Production",
    maintenance: "Maintenance",
};

// ==============================
// FEATURE STATUS
// ==============================

export const featureStatusMap = {
    planned: "bg-gray-100 text-gray-700",
    in_progress: "bg-blue-100 text-blue-700",
    done: "bg-green-100 text-green-700",
    on_hold: "bg-yellow-100 text-yellow-700",
};

export const featureStatusLabel = {
    planned: "Planned",
    in_progress: "In Progress",
    done: "Done",
    on_hold: "On Hold",
};

// ==============================
// FEATURE CATEGORY
// ==============================

export const featureCategoryLabel = {
    feature: "Feature",
    improvement: "Improvement",
    maintenance: "Maintenance",
};

// ==============================
// LOG TYPE
// ==============================

export const logTypeMap = {
    progress: "bg-blue-100 text-blue-700",
    bug: "bg-red-100 text-red-700",
    fix: "bg-green-100 text-green-700",
    deployment: "bg-amber-100 text-amber-700",
    maintenance: "bg-yellow-100 text-yellow-700",
    decision: "bg-purple-100 text-purple-700",
    idea: "bg-gray-100 text-gray-700",
};

export const logTypeLabel = {
    progress: "Progress",
    bug: "Bug",
    fix: "Fix",
    deployment: "Deployment",
    maintenance: "Maintenance",
    decision: "Decision",
    idea: "Idea",
};

// ==============================
// IMPACT
// ==============================

export const impactMap = {
    low: "bg-gray-100 text-gray-700",
    medium: "bg-blue-100 text-blue-700",
    high: "bg-orange-100 text-orange-700",
    critical: "bg-red-100 text-red-700",
};

export const impactLabel = {
    low: "Low",
    medium: "Medium",
    high: "High",
    critical: "Critical",
};

// ==============================
// LOG STATUS
// ==============================

export const logStatusMap = {
    open: "bg-orange-100 text-orange-700",
    resolved: "bg-green-100 text-green-700",
    ignored: "bg-gray-100 text-gray-700",
    on_progress: "bg-blue-100 text-blue-700",
    done: "bg-green-100 text-green-700",
};

export const logStatusLabel = {
    open: "Open",
    resolved: "Resolved",
    ignored: "Ignored",
    on_progress: "On Progress",
    done: "Done",
};

// ==============================
// SLA (Frontend Helper)
// ==============================
// Keep in sync with `config/sla.php`.
export const bugResolutionSlaDays = {
    low: 5,
    medium: 3,
    high: 2,
    critical: 1,
};
