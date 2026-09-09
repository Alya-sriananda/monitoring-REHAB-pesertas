import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export function useAutoRefresh() {
    useEffect(() => {
        // Handle browser back/forward buttons (popstate)
        // Inertia natively handles popstate, but by default it restores from its history cache.
        // We force a silent reload to ensure data is always fresh.
        const handlePopState = () => {
            router.reload({ preserveScroll: true, preserveState: true });
        };

        // Handle window focus (e.g. user switches tabs and comes back)
        // This ensures if they did something in another tab/SIPP, the data is fresh.
        const handleFocus = () => {
            router.reload({ preserveScroll: true, preserveState: true });
        };

        window.addEventListener('popstate', handlePopState);
        window.addEventListener('focus', handleFocus);

        return () => {
            window.removeEventListener('popstate', handlePopState);
            window.removeEventListener('focus', handleFocus);
        };
    }, []);
}
