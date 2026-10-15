import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    QrCode,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Wifi,
    WifiOff,
    RefreshCw,
    Camera,
    ShieldCheck,
    Search,
    History,
    Users
} from 'lucide-react';

interface QueuedScan {
    qr_payload: string;
    venue_id?: number;
    event_id?: number;
    device_id: string;
    scanned_at: string;
}

interface ScanResult {
    success: boolean;
    status: string;
    message: string;
    ticket?: any;
}

export default function TicketScanner() {
    const { auth } = usePage().props as any;
    const [qrInput, setQrInput] = useState('');
    const [venueId, setVenueId] = useState<string>('');
    const [eventId, setEventId] = useState<string>('');
    const [isOfflineMode, setIsOfflineMode] = useState(!navigator.onLine);
    const [queuedScans, setQueuedScans] = useState<QueuedScan[]>([]);
    const [recentScans, setRecentScans] = useState<any[]>([]);
    const [activeResult, setActiveResult] = useState<ScanResult | null>(null);
    const [syncLoading, setSyncLoading] = useState(false);
    const [syncSummary, setSyncSummary] = useState<string | null>(null);

    const deviceId = 'scanner-device-' + (auth.user?.id || 'demo');

    // Load offline queue from localStorage
    useEffect(() => {
        const stored = localStorage.getItem('cee_scanner_queue');
        if (stored) {
            try {
                setQueuedScans(JSON.parse(stored));
            } catch (e) {
                console.error(e);
            }
        }

        const handleOnline = () => setIsOfflineMode(false);
        const handleOffline = () => setIsOfflineMode(true);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    // Save to localStorage when queuedScans changes
    const saveQueue = (newQueue: QueuedScan[]) => {
        setQueuedScans(newQueue);
        localStorage.setItem('cee_scanner_queue', JSON.stringify(newQueue));
    };

    const handleScanSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!qrInput.trim()) return;

        const payload = qrInput.trim();
        const scanTimestamp = new Date().toISOString();

        if (isOfflineMode) {
            // Buffer locally for offline queue
            const item: QueuedScan = {
                qr_payload: payload,
                venue_id: venueId ? parseInt(venueId) : undefined,
                event_id: eventId ? parseInt(eventId) : undefined,
                device_id: deviceId,
                scanned_at: scanTimestamp,
            };

            const updated = [item, ...queuedScans];
            saveQueue(updated);

            setActiveResult({
                success: true,
                status: 'queued_offline',
                message: 'Scan recorded offline. Sync when connection is restored.',
            });

            setRecentScans((prev) => [
                {
                    status: 'queued',
                    message: 'Buffered Offline',
                    payload: payload.substring(0, 30) + '...',
                    time: new Date().toLocaleTimeString(),
                },
                ...prev.slice(0, 9),
            ]);

            setQrInput('');
            return;
        }

        // Online real-time validation
        try {
            const res = await fetch('/api/v1/tickets/scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    qr_payload: payload,
                    venue_id: venueId ? parseInt(venueId) : undefined,
                    event_id: eventId ? parseInt(eventId) : undefined,
                    device_id: deviceId,
                    scanned_at: scanTimestamp,
                }),
            });

            const data = await res.json();
            setActiveResult({
                success: data.success,
                status: data.status,
                message: data.message,
                ticket: data.ticket,
            });

            setRecentScans((prev) => [
                {
                    status: data.status,
                    message: data.message,
                    ticket: data.ticket,
                    time: new Date().toLocaleTimeString(),
                },
                ...prev.slice(0, 9),
            ]);

            if (data.success) {
                setQrInput('');
            }
        } catch (err: any) {
            console.error('Scan failed', err);
            // Fallback to offline buffering if network fails
            const item: QueuedScan = {
                qr_payload: payload,
                venue_id: venueId ? parseInt(venueId) : undefined,
                event_id: eventId ? parseInt(eventId) : undefined,
                device_id: deviceId,
                scanned_at: scanTimestamp,
            };
            saveQueue([item, ...queuedScans]);
            setActiveResult({
                success: false,
                status: 'network_queued',
                message: 'Network error. Scan saved to offline queue.',
            });
        }
    };

    const handleSyncQueue = async () => {
        if (queuedScans.length === 0) return;

        setSyncLoading(true);
        setSyncSummary(null);

        try {
            const res = await fetch('/api/v1/tickets/sync-scans', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    scans: queuedScans,
                }),
            });

            const data = await res.json();
            if (res.ok) {
                setSyncSummary(`Synced: ${data.data.synced} valid, ${data.data.duplicates} duplicates, ${data.data.invalid} rejected.`);
                saveQueue([]);
            } else {
                setSyncSummary(data.message || 'Sync failed');
            }
        } catch (e: any) {
            setSyncSummary(e.message || 'Sync network error');
        } finally {
            setSyncLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Gate QR Scanner - Offline Capable" />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header & Connectivity Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="p-2 rounded-xl bg-teal-500/10 text-teal-400">
                                <QrCode className="w-6 h-6" />
                            </span>
                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                Gate Entry Scanner
                            </h1>
                        </div>
                        <p className="text-slate-400 text-sm">
                            High-throughput QR scanner with offline signature validation and duplicate rejection.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setIsOfflineMode(!isOfflineMode)}
                            className={`px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition-colors ${
                                isOfflineMode
                                    ? 'bg-amber-950/60 border-amber-500/50 text-amber-300'
                                    : 'bg-emerald-950/60 border-emerald-500/50 text-emerald-300'
                            }`}
                        >
                            {isOfflineMode ? <WifiOff className="w-4 h-4" /> : <Wifi className="w-4 h-4" />}
                            {isOfflineMode ? 'Offline Mode (Local Queue)' : 'Online Real-time'}
                        </button>

                        <Link href="/ticketing/reports">
                            <Button variant="outline" size="sm" className="text-xs">
                                Attendance Reports &rarr;
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Offline Sync Bar */}
                {queuedScans.length > 0 && (
                    <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div className="flex items-center gap-2.5 text-xs font-medium">
                            <AlertTriangle className="w-5 h-5 text-amber-400 shrink-0" />
                            <span>
                                <strong>{queuedScans.length} offline scans</strong> waiting to be synced to the central database.
                            </span>
                        </div>

                        <Button
                            size="sm"
                            variant="primary"
                            onClick={handleSyncQueue}
                            disabled={syncLoading}
                            className="text-xs flex items-center gap-1.5 bg-amber-600 hover:bg-amber-500 text-slate-950 font-bold"
                        >
                            <RefreshCw className={`w-3.5 h-3.5 ${syncLoading ? 'animate-spin' : ''}`} />
                            {syncLoading ? 'Syncing...' : 'Sync Queued Scans'}
                        </Button>
                    </div>
                )}

                {syncSummary && (
                    <div className="p-3 rounded-xl bg-teal-500/10 border border-teal-500/30 text-teal-300 text-xs mb-6 flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4 shrink-0" />
                        <span>{syncSummary}</span>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Scanner Input & Result (2 cols) */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Scope Configuration */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardBody className="p-4 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label className="block text-slate-400 font-semibold mb-1">Venue ID Scope (Optional)</label>
                                    <Input
                                        placeholder="e.g. 1"
                                        value={venueId}
                                        onChange={(e) => setVenueId(e.target.value)}
                                        className="bg-slate-950 border-slate-800 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-400 font-semibold mb-1">Event ID Scope (Optional)</label>
                                    <Input
                                        placeholder="e.g. 1"
                                        value={eventId}
                                        onChange={(e) => setEventId(e.target.value)}
                                        className="bg-slate-950 border-slate-800 text-xs"
                                    />
                                </div>
                            </CardBody>
                        </Card>

                        {/* Scan Form / Camera Simulation */}
                        <Card className="bg-slate-900/80 border-slate-800">
                            <CardHeader>
                                <h3 className="font-bold text-white text-base flex items-center gap-2">
                                    <Camera className="w-5 h-5 text-teal-400" />
                                    Scan QR Code or Token
                                </h3>
                            </CardHeader>
                            <CardBody className="space-y-4">
                                <form onSubmit={handleScanSubmit} className="space-y-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-300 mb-1">
                                            Paste or Scan QR Payload / Reference Token
                                        </label>
                                        <textarea
                                            rows={3}
                                            autoFocus
                                            required
                                            placeholder='e.g. {"ref":"TIC-XXXXXXXX","sig":"..."} or TIC-XXXXXXXX'
                                            value={qrInput}
                                            onChange={(e) => setQrInput(e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs font-mono text-emerald-400 focus:outline-none focus:border-teal-500"
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        variant="primary"
                                        className="w-full py-3 text-sm font-bold shadow-lg shadow-teal-950/40"
                                    >
                                        Validate Entry Pass
                                    </Button>
                                </form>
                            </CardBody>
                        </Card>

                        {/* Real-time Result Card */}
                        {activeResult && (
                            <div
                                className={`p-6 rounded-2xl border text-center transition-all ${
                                    activeResult.success
                                        ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-300'
                                        : activeResult.status === 'duplicate'
                                        ? 'bg-amber-950/40 border-amber-500/60 text-amber-300'
                                        : 'bg-rose-950/40 border-rose-500/60 text-rose-300'
                                }`}
                            >
                                <div className="flex justify-center mb-3">
                                    {activeResult.success ? (
                                        <CheckCircle2 className="w-14 h-14 text-emerald-400 animate-bounce" />
                                    ) : activeResult.status === 'duplicate' ? (
                                        <AlertTriangle className="w-14 h-14 text-amber-400" />
                                    ) : (
                                        <XCircle className="w-14 h-14 text-rose-400" />
                                    )}
                                </div>

                                <h2 className="text-2xl font-black mb-1">
                                    {activeResult.success ? 'ENTRY GRANTED' : activeResult.status?.toUpperCase().replace(/_/g, ' ')}
                                </h2>
                                <p className="text-sm opacity-90 mb-4">{activeResult.message}</p>

                                {activeResult.ticket && (
                                    <div className="p-3 rounded-xl bg-slate-950/80 border border-slate-800/80 max-w-sm mx-auto text-left text-xs space-y-1 text-slate-200">
                                        <div><strong>Reference:</strong> {activeResult.ticket.reference_number}</div>
                                        <div><strong>Tier:</strong> {activeResult.ticket.ticket_type?.name}</div>
                                        <div><strong>Attendee:</strong> {activeResult.ticket.holder_name || activeResult.ticket.user?.name}</div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Right Col: Recent Scans & Stats */}
                    <div className="space-y-6">
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader>
                                <h3 className="font-bold text-white text-sm flex items-center gap-2">
                                    <History className="w-4 h-4 text-slate-400" />
                                    Recent Gate Activity
                                </h3>
                            </CardHeader>
                            <CardBody className="p-0">
                                {recentScans.length === 0 ? (
                                    <div className="p-6 text-center text-slate-500 text-xs">
                                        No scans recorded during this session.
                                    </div>
                                ) : (
                                    <div className="divide-y divide-slate-800/80 text-xs">
                                        {recentScans.map((s, idx) => (
                                            <div key={idx} className="p-3 flex items-center justify-between">
                                                <div>
                                                    <div className="font-semibold text-slate-200">
                                                        {s.ticket?.holder_name || s.payload || 'Gate Scan'}
                                                    </div>
                                                    <div className="text-[10px] text-slate-400">
                                                        {s.message}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <Badge
                                                        variant={
                                                            s.status === 'valid'
                                                                ? 'success'
                                                                : s.status === 'duplicate'
                                                                ? 'warning'
                                                                : 'danger'
                                                        }
                                                        className="text-[10px]"
                                                    >
                                                        {s.status}
                                                    </Badge>
                                                    <span className="block text-[10px] text-slate-500 mt-0.5">
                                                        {s.time}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardBody>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
