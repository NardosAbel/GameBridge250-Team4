import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    ShieldAlert,
    AlertTriangle,
    CheckCircle,
    UserX,
    Trash2,
    Eye,
    Clock,
    FileText,
} from 'lucide-react';

interface ReportItem {
    id: number;
    uuid: string;
    reporter: {
        id: number;
        name: string;
        handle?: string;
    };
    reportable_type: string;
    reportable_id: number;
    reason: string;
    details?: string;
    status: string;
    created_at: string;
}

export default function ModerationQueue() {
    const { auth } = usePage<any>().props;
    const [reports, setReports] = useState<ReportItem[]>([]);
    const [loading, setLoading] = useState(true);

    // Action modal
    const [selectedReport, setSelectedReport] = useState<ReportItem | null>(null);
    const [actionType, setActionType] = useState<string>('warning');
    const [actionReason, setActionReason] = useState<string>('');
    const [submitting, setSubmitting] = useState(false);
    const [successMsg, setSuccessMsg] = useState('');

    const fetchQueue = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/moderation/queue', {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.data) {
                setReports(data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchQueue();
    }, []);

    const handleExecuteAction = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedReport) return;

        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/moderation/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({
                    report_id: selectedReport.id,
                    target_user_id: selectedReport.reporter.id, // Fallback if user report, else target author
                    action: actionType,
                    reason: actionReason || `Action taken for reason: ${selectedReport.reason}`,
                    content_id: selectedReport.reportable_type !== 'User' ? selectedReport.reportable_id : null,
                    content_type: selectedReport.reportable_type.toLowerCase(),
                }),
            });
            if (res.ok) {
                setSuccessMsg(`Moderation action '${actionType}' executed and recorded in audit log.`);
                setSelectedReport(null);
                setActionReason('');
                fetchQueue();
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Moderation Queue - Campus Esports Ethiopia" />

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">
                <div className="flex items-center justify-between border-b border-slate-800 pb-5">
                    <div>
                        <h1 className="text-2xl font-black text-white flex items-center gap-2">
                            <ShieldAlert className="w-6 h-6 text-rose-500" />
                            Moderation & Trust Queue
                        </h1>
                        <p className="text-sm text-slate-400">
                            Review community reports, take disciplinary actions, and preserve competitive integrity.
                        </p>
                    </div>

                    <Badge variant="warning" className="text-xs">
                        {reports.length} Pending Cases
                    </Badge>
                </div>

                {successMsg && (
                    <div className="p-3 rounded-lg bg-emerald-950/60 border border-emerald-800 text-xs text-emerald-300">
                        {successMsg}
                    </div>
                )}

                {/* Queue list */}
                {loading ? (
                    <div className="py-12 text-center text-slate-500 text-sm">Loading moderation queue...</div>
                ) : reports.length === 0 ? (
                    <div className="py-16 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                        <CheckCircle className="w-10 h-10 text-emerald-500 mx-auto mb-3" />
                        <h3 className="text-base font-bold text-slate-200">Moderation Queue Clear!</h3>
                        <p className="text-xs text-slate-500 mt-1">No reported items require review.</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {reports.map((report) => (
                            <Card key={report.id} className="border border-slate-800 bg-slate-900/70">
                                <CardBody className="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-xs">
                                            <Badge variant="outline" className="capitalize">
                                                {report.reportable_type} #{report.reportable_id}
                                            </Badge>
                                            <span className="text-slate-400">
                                                Reported by <strong className="text-white">{report.reporter?.name}</strong>
                                            </span>
                                            <span className="text-[10px] text-slate-500">
                                                • {new Date(report.created_at).toLocaleString()}
                                            </span>
                                        </div>

                                        <div>
                                            <h4 className="font-bold text-sm text-white">
                                                Reason: {report.reason}
                                            </h4>
                                            {report.details && (
                                                <p className="text-xs text-slate-300 mt-1 italic bg-slate-950 p-2 rounded border border-slate-800">
                                                    "{report.details}"
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 self-end md:self-center">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedReport(report);
                                                setActionType('warning');
                                            }}
                                            className="text-xs text-amber-400 border-amber-900/50 hover:bg-amber-950/30"
                                        >
                                            Take Action
                                        </Button>
                                    </div>
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Action Dialog */}
            {selectedReport && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <ShieldAlert className="w-5 h-5 text-rose-500" /> Disciplinary Action
                            </h3>
                            <button onClick={() => setSelectedReport(null)} className="text-slate-400 hover:text-white">✕</button>
                        </div>

                        <form onSubmit={handleExecuteAction} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Select Action</label>
                                <select
                                    value={actionType}
                                    onChange={(e) => setActionType(e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                >
                                    <option value="warning">Official Warning</option>
                                    <option value="delete_content">Remove Content</option>
                                    <option value="mute">Mute User (Temporary)</option>
                                    <option value="suspend">Suspend Account</option>
                                    <option value="ban">Permanent Platform Ban</option>
                                    <option value="dismiss">Dismiss Report (No Violation)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Audit Log Justification / Notes</label>
                                <textarea
                                    required
                                    value={actionReason}
                                    onChange={(e) => setActionReason(e.target.value)}
                                    rows={3}
                                    placeholder="Enter administrative rationale to record in audit trail..."
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white resize-none"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => setSelectedReport(null)}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" size="sm" disabled={submitting || !actionReason.trim()}>
                                    {submitting ? 'Applying...' : 'Confirm Action'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
