import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { router } from '@inertiajs/react';
import { Bell, BellOff, CheckCheck } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface NotificationItem {
    id: string;
    type: string;
    notifiable_id: number;
    notifiable_type: string;
    data: {
        type: string;
        title: string;
        message: string;
        url?: string;
    };
    read_at: string | null;
    created_at: string;
}

interface PaginatedNotifications {
    data: NotificationItem[];
    total: number;
}

function timeAgo(dateString: string): string {
    const diff = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000);
    if (diff < 60) { return 'hace un momento'; }
    if (diff < 3600) { return `hace ${Math.floor(diff / 60)} min`; }
    if (diff < 86400) { return `hace ${Math.floor(diff / 3600)} h`; }
    return `hace ${Math.floor(diff / 86400)} d`;
}

export function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [unreadCount, setUnreadCount] = useState(0);
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [loading, setLoading] = useState(false);
    const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const fetchUnreadCount = useCallback(() => {
        fetch(route('notifications.unread-count'))
            .then((r) => r.json())
            .then((data: { count: number }) => setUnreadCount(data.count))
            .catch(() => {});
    }, []);

    const fetchNotifications = useCallback(() => {
        setLoading(true);
        fetch(route('notifications.index'))
            .then((r) => r.json())
            .then((data: PaginatedNotifications) => {
                setNotifications(data.data);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    // Poll unread count every 60 seconds.
    useEffect(() => {
        fetchUnreadCount();
        pollRef.current = setInterval(fetchUnreadCount, 60_000);
        return () => { if (pollRef.current) { clearInterval(pollRef.current); } };
    }, [fetchUnreadCount]);

    // Load notifications when dropdown opens.
    useEffect(() => {
        if (open) {
            fetchNotifications();
        }
    }, [open, fetchNotifications]);

    const markRead = (notification: NotificationItem) => {
        if (notification.read_at) {
            if (notification.data.url) {
                router.visit(notification.data.url);
            }
            return;
        }

        fetch(route('notifications.read', notification.id), { method: 'PATCH', headers: { 'X-CSRF-TOKEN': getCsrfToken() } })
            .then(() => {
                setNotifications((prev) =>
                    prev.map((n) => (n.id === notification.id ? { ...n, read_at: new Date().toISOString() } : n)),
                );
                setUnreadCount((c) => Math.max(0, c - 1));
                if (notification.data.url) {
                    router.visit(notification.data.url);
                }
            })
            .catch(() => {});
    };

    const markAllRead = () => {
        fetch(route('notifications.read-all'), { method: 'PATCH', headers: { 'X-CSRF-TOKEN': getCsrfToken() } })
            .then(() => {
                setNotifications((prev) => prev.map((n) => ({ ...n, read_at: n.read_at ?? new Date().toISOString() })));
                setUnreadCount(0);
            })
            .catch(() => {});
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative" aria-label="Notificaciones">
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <span className="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-semibold text-destructive-foreground">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-80 p-0" sideOffset={8}>
                <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-sm font-semibold">Notificaciones</span>
                    {unreadCount > 0 && (
                        <button
                            onClick={markAllRead}
                            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <CheckCheck className="size-3.5" />
                            Marcar todas
                        </button>
                    )}
                </div>

                <DropdownMenuSeparator className="my-0" />

                <div className="max-h-[360px] overflow-y-auto">
                    {loading ? (
                        <div className="space-y-3 p-3">
                            {[1, 2, 3].map((i) => (
                                <div key={i} className="flex gap-3">
                                    <Skeleton className="size-2 mt-1.5 shrink-0 rounded-full" />
                                    <div className="flex-1 space-y-1.5">
                                        <Skeleton className="h-3.5 w-3/4" />
                                        <Skeleton className="h-3 w-full" />
                                        <Skeleton className="h-3 w-1/3" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : notifications.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 py-8 text-muted-foreground">
                            <BellOff className="size-8 opacity-40" />
                            <p className="text-sm">Sin notificaciones</p>
                        </div>
                    ) : (
                        <ul>
                            {notifications.map((n) => (
                                <li key={n.id}>
                                    <button
                                        className={`w-full text-left px-4 py-3 flex gap-3 hover:bg-muted/50 transition-colors border-b border-border/50 last:border-0 ${n.read_at ? 'opacity-60' : ''}`}
                                        onClick={() => markRead(n)}
                                    >
                                        <span
                                            className={`mt-1.5 size-2 shrink-0 rounded-full ${n.read_at ? 'bg-transparent border border-muted-foreground/30' : 'bg-primary'}`}
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium leading-tight truncate">{n.data.title}</p>
                                            <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{n.data.message}</p>
                                            <p className="text-[10px] text-muted-foreground/70 mt-1">{timeAgo(n.created_at)}</p>
                                        </div>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function getCsrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}
