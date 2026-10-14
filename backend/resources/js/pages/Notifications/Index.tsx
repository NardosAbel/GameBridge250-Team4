import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    Bell,
    Check,
    CheckCheck,
    Settings,
    Mail,
    Smartphone,
    Radio,
} from 'lucide-react';

interface NotificationItem {
    id: string;
    type: string;
    data: {
        title: string;
        message: string;
        type?: string;
        action_url?: string;
    };
    read_at?: string | null;
    created_at: string;
}

interface Preferences {
    in_app: boolean;
    email: boolean;
    sms: boolean;
    push: boolean;
}

export default function NotificationsIndex() {
    const { auth } = usePage<any>().props;
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(true);

    // Preferences state
    const [showPrefs, setShowPrefs] = useState(false);
    const [prefs, setPrefs] = useState<Preferences>({
        in_app: true,
        email: true,
        sms: false,
        push: true,
    });
    const [savingPrefs, setSavingPrefs] = useState(false);

    const fetchNotifications = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/notifications', {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.data) {
                setNotifications(data.data);
                setUnreadCount(data.unread_count || 0);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const fetchPreferences = async () => {
        try {
            const res = await fetch('/api/v1/notifications/preferences', {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.data) {
                setPrefs({
                    in_app: data.data.in_app,
                    email: data.data.email,
                    sms: data.data.sms,
                    push: data.data.push,
                });
            }
        } catch (err) {
            console.error(err);
        }
    };

    useEffect(() => {
        fetchNotifications();
        fetchPreferences();
    }, []);

    const markAsRead = async (id: string) => {
        try {
            await fetch(`/api/v1/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
            });
            fetchNotifications();
        } catch (err) {
            console.error(err);
        }
    };

    const markAllAsRead = async () => {
        try {
            await fetch('/api/v1/notifications/read-all', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
            });
            fetchNotifications();
        } catch (err) {
            console.error(err);
        }
    };

    const handleSavePreferences = async () => {
        setSavingPrefs(true);
        try {
            await fetch('/api/v1/notifications/preferences', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify(prefs),
            });
            setShowPrefs(false);
        } catch (err) {
            console.error(err);
        } finally {
            setSavingPrefs(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Notifications - Campus Esports Ethiopia" />

            <div className="max-w-4xl mx-auto px-4 py-8 space-y-6">
                <div className="flex items-center justify-between border-b border-slate-800 pb-5">
                    <div>
                        <h1 className="text-2xl font-black text-white flex items-center gap-2">
                            <Bell className="w-6 h-6 text-amber-400" />
                            Notifications
                        </h1>
                        <p className="text-sm text-slate-400">
                            Stay notified about team invitations, match mentions, comments, and results.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        {unreadCount > 0 && (
                            <Button variant="outline" size="sm" onClick={markAllAsRead}>
                                <CheckCheck className="w-4 h-4 mr-1.5" /> Mark All Read
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowPrefs(!showPrefs)}
                            className="flex items-center gap-1.5"
                        >
                            <Settings className="w-4 h-4" /> Preferences
                        </Button>
                    </div>
                </div>

                {/* Preferences Drawer */}
                {showPrefs && (
                    <Card className="border border-slate-800 bg-slate-900/80 p-5 space-y-4">
                        <h3 className="font-bold text-sm text-white">Multi-Channel Delivery Channels</h3>
                        <p className="text-xs text-slate-400">
                            Configure how you receive tournament notices, recruitment alerts, and match schedules.
                        </p>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <label className="flex items-center gap-3 p-3 rounded-lg border border-slate-800 bg-slate-950 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={prefs.in_app}
                                    onChange={(e) => setPrefs({ ...prefs, in_app: e.target.checked })}
                                    className="rounded border-slate-700 bg-slate-900 text-emerald-500"
                                />
                                <div className="text-xs">
                                    <span className="font-bold text-white block">In-App Notifications</span>
                                    <span className="text-slate-400">Bell alerts on site</span>
                                </div>
                            </label>

                            <label className="flex items-center gap-3 p-3 rounded-lg border border-slate-800 bg-slate-950 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={prefs.email}
                                    onChange={(e) => setPrefs({ ...prefs, email: e.target.checked })}
                                    className="rounded border-slate-700 bg-slate-900 text-emerald-500"
                                />
                                <div className="text-xs">
                                    <span className="font-bold text-white block flex items-center gap-1">
                                        <Mail className="w-3.5 h-3.5 text-blue-400" /> Email Notifications
                                    </span>
                                    <span className="text-slate-400">Digest and urgent match calls</span>
                                </div>
                            </label>

                            <label className="flex items-center gap-3 p-3 rounded-lg border border-slate-800 bg-slate-950 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={prefs.push}
                                    onChange={(e) => setPrefs({ ...prefs, push: e.target.checked })}
                                    className="rounded border-slate-700 bg-slate-900 text-emerald-500"
                                />
                                <div className="text-xs">
                                    <span className="font-bold text-white block flex items-center gap-1">
                                        <Radio className="w-3.5 h-3.5 text-purple-400" /> Push Notifications
                                    </span>
                                    <span className="text-slate-400">Browser and mobile push</span>
                                </div>
                            </label>

                            <label className="flex items-center gap-3 p-3 rounded-lg border border-slate-800 bg-slate-950 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={prefs.sms}
                                    onChange={(e) => setPrefs({ ...prefs, sms: e.target.checked })}
                                    className="rounded border-slate-700 bg-slate-900 text-emerald-500"
                                />
                                <div className="text-xs">
                                    <span className="font-bold text-white block flex items-center gap-1">
                                        <Smartphone className="w-3.5 h-3.5 text-emerald-400" /> SMS Match Alerts
                                    </span>
                                    <span className="text-slate-400">High-priority Ethiopian SMS</span>
                                </div>
                            </label>
                        </div>

                        <div className="flex justify-end gap-2 pt-2">
                            <Button size="sm" variant="outline" onClick={() => setShowPrefs(false)}>
                                Cancel
                            </Button>
                            <Button size="sm" variant="primary" onClick={handleSavePreferences} disabled={savingPrefs}>
                                {savingPrefs ? 'Saving...' : 'Save Preferences'}
                            </Button>
                        </div>
                    </Card>
                )}

                {/* Notifications List */}
                {loading ? (
                    <div className="py-12 text-center text-slate-500 text-sm">Loading alerts...</div>
                ) : notifications.length === 0 ? (
                    <div className="py-16 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                        <Bell className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-base font-bold text-slate-200">All caught up!</h3>
                        <p className="text-xs text-slate-500 mt-1">No unread notifications at this time.</p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {notifications.map((n) => (
                            <div
                                key={n.id}
                                className={`p-4 rounded-xl border transition-all flex items-start justify-between gap-4 ${
                                    !n.read_at
                                        ? 'bg-slate-900/90 border-emerald-500/40 shadow-sm'
                                        : 'bg-slate-950/60 border-slate-800/80 text-slate-400'
                                }`}
                            >
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <h4 className="text-sm font-bold text-white">{n.data?.title}</h4>
                                        {!n.read_at && (
                                            <Badge variant="success" className="text-[9px] py-0 px-1.5">
                                                New
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="text-xs text-slate-300">{n.data?.message}</p>
                                    <span className="text-[10px] text-slate-500 block">
                                        {new Date(n.created_at).toLocaleString()}
                                    </span>
                                </div>

                                {!n.read_at && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => markAsRead(n.id)}
                                        className="text-xs p-1 text-slate-400 hover:text-emerald-400 shrink-0"
                                        title="Mark as Read"
                                    >
                                        <Check className="w-4 h-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
