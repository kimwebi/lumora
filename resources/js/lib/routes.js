export const routes = {
    home: '/',
    login: '/login',
    loginStore: '/login',
    register: '/register',
    registerStore: '/register',
    logout: '/logout',
    passwordEmail: '/forgot-password',
    passwordReset: '/reset-password',
    dashboard: '/dashboard',
    generate: '/generate',
    generations: '/generations',
    generationsStore: '/generations',
    creations: '/creations',
    billing: '/billing',
    billingPurchase: '/billing/purchase',
    settings: '/settings',
    settingsProfile: '/settings/profile',
    settingsPassword: '/settings/password',
    settingsAccount: '/settings/account',
    admin: '/admin',
    adminReconcile: '/admin/reconcile',
    adminReports: '/admin/reports',
    adminReportsSync: '/admin/reports/sync',
    adminImages: '/admin/images',
    adminImagesStore: '/admin/images',
    terms: '/terms',
    privacy: '/privacy',
    guide: '/guide',
    developerGuide: '/developer-guide',
};

export function generationStatusUrl(uuid) {
    return `${routes.generations}/${uuid}/status`;
}

export function generationUrl(uuid) {
    return `${routes.generations}/${uuid}`;
}

export function adminReportExportUrl(format, days) {
    return `${routes.adminReports}/export/${format}?days=${encodeURIComponent(days)}`;
}

export function adminImageRefetchUrl(uuid) {
    return `${routes.adminImages}/${encodeURIComponent(uuid)}/refetch`;
}
