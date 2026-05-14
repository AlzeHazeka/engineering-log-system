export const formatDate = (dateString) => {
    if (!dateString) return "";
    const d = new Date(dateString);
    if (Number.isNaN(d.getTime())) return "";
    return new Intl.DateTimeFormat("id-ID", {
        year: "numeric",
        month: "short",
        day: "2-digit",
    }).format(d);
};

export const formatDayDate = (dateString) => {
    if (!dateString) return "";
    const d = new Date(dateString);
    if (Number.isNaN(d.getTime())) return "";
    return new Intl.DateTimeFormat("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    }).format(d);
};

export const timeAgo = (dateString) => {
    // Backward-compatible alias used across the UI.
    // Product decision: avoid relative/approximate time ("kemarin", "minggu lalu", etc.)
    // and show the explicit Indonesian day + date instead.
    return formatDayDate(dateString);
};
