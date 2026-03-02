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
// LOG TYPE
// ==============================

export const logTypeMap = {
    change: "bg-blue-100 text-blue-700",
    error: "bg-red-100 text-red-700",
    fix: "bg-green-100 text-green-700",
    maintenance: "bg-yellow-100 text-yellow-700",
    decision: "bg-purple-100 text-purple-700",
    deployment: "bg-amber-100 text-amber-700",
    idea: "bg-gray-100 text-gray-700",
};

export const logTypeLabel = {
    change: "Change",
    error: "Error",
    fix: "Fix",
    maintenance: "Maintenance",
    decision: "Decision",
    deployment: "Deployment",
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
