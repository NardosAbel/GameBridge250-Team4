import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import {
    Activity,
    ShieldAlert,
    TrendingUp,
    Users,
    Trophy,
    Ticket,
    BookOpen,
    DollarSign,
    ToggleLeft,
    ToggleRight,
    Server,
    Download,
    CheckCircle2,
    Building2,
    Sparkles,
    AlertTriangle
} from 'lucide-react';

interface ExecutiveMetrics {
    users: {
        total_users: number;
        verified_students: number;
        verification_rate: number;
        active_7d: number;
        active_30d: number;
    };
    tournaments: {
        total: number;
        completed: number;
        completion_rate: number;
        total_matches: number;
        completed_matches: number;
        match_completion_rate: number;
        total_disputes: number;
        dispute_rate: number;
    };
    ticketing: {
        tickets_sold: number;
        tickets_attended: number;
        attendance_rate: number;
        gross_revenue: number;
        currency: string;
    };
    venues: {
        total_venues: number;
        verified_venues: number;
    };
    universities: {
        university: string;
        student_count: number;
    }[];
    learning: {
        published_courses: number;
        total_enrollments: number;
        completed_enrollments: number;
        completion_rate: number;
        certificates_issued: number;
    };
    sponsorship: {
        total_deals: number;
        active_deals: number;
        total_volume: number;
        currency: string;
    };
    system_health: {
        status: string;
        php_version: string;
        laravel_version: string;
        server_time: string;
    };
}

interface FeatureFlag {
    id: number;
    key: string;
    name: string;
    description: string | null;
    is_enabled: boolean;
}

export default function AdminDashboard() {
    const [metrics, setMetrics] = useState<ExecutiveMetrics | null>(null);
    const [featureFlags, setFeatureFlags] = useState<FeatureFlag[]>([]);
    const [loading, setLoading] = useState(true);
    const [togglingKey, setTogglingKey] = useState<string | null>(null);

    useEffect(() => {
        fetchAdminData();
    }, []);

    const fetchAdminData = async () => {
        setLoading(true);
        try {
            const [overviewRes, flagsRes] = await Promise.all([
                fetch('/api/v1/admin/overview'),
                fetch('/api/v1/admin/feature-flags'),
            ]);

            const overviewData = await overviewRes.json();
            const flagsData = await flagsRes.json();

            if (overviewData.success && overviewData.data) {
                setMetrics(overviewData.data.metrics);
            }
            if (flagsData.success && flagsData.data) {
                setFeatureFlags(flagsData.data);
            }
        } catch (err) {
            console.error('Failed to load admin data', err);
        } finally {
            setLoading(false);
        }
    };

    const toggleFeatureFlag = async (flag: FeatureFlag) => {
        setTogglingKey(flag.key);
        try {
            const res = await fetch(`/api/v1/admin/feature-flags/${flag.key}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    is_enabled: !flag.is_enabled,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setFeatureFlags((prev) =>
                    prev.map((f) => (f.key === flag.key ? { ...f, is_enabled: !f.is_enabled } : f))
                );
            }
        } catch (err) {
            console.error('Failed to toggle flag', err);
        } finally {
            setTogglingKey(null);
        }
    };

    return (
        <AppLayout>
            <Head title="Platform Executive Analytics & Administration Dashboard" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-1">
                            <Activity className="w-4 h-4" />
                            Platform Operations & Intelligence
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                            Executive Platform Analytics & Control Center
                        </h1>
                    </div>

                    <div className="flex items-center gap-3">
                        <a href="/api/v1/admin/analytics/export" target="_blank" rel="noreferrer">
                            <Button variant="primary" className="flex items-center gap-2 shadow-lg shadow-emerald-900/40">
                                <Download className="w-4 h-4" />
                                Export Platform CSV
                            </Button>
                        </a>
                    </div>
                </div>

                {loading ? (
                    <div className="py-20 text-center text-slate-400 space-y-3">
                        <div className="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto" />
                        <p className="text-sm">Calculating executive metrics across platform clusters...</p>
                    </div>
                ) : !metrics ? (
                    <Card className="bg-slate-900/60 border-slate-800 p-8 text-center text-slate-400">
                        Failed to load executive platform analytics. Ensure you have super_admin permissions.
                    </Card>
                ) : (
                    <div className="space-y-8">
                        {/* Domain High-Level KPI Matrix */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Users className="w-3.5 h-3.5 text-blue-400" />
                                    Active Users (MAU)
                                </div>
                                <div className="text-xl font-black text-white">
                                    {metrics.users.active_30d.toLocaleString()}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">
                                    Total: {metrics.users.total_users.toLocaleString()}
                                </div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Building2 className="w-3.5 h-3.5 text-emerald-400" />
                                    Student Verified
                                </div>
                                <div className="text-xl font-black text-emerald-400">
                                    {metrics.users.verification_rate}%
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">
                                    {metrics.users.verified_students} verified students
                                </div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Trophy className="w-3.5 h-3.5 text-amber-400" />
                                    Tournaments
                                </div>
                                <div className="text-xl font-black text-white">
                                    {metrics.tournaments.total}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">
                                    {metrics.tournaments.completion_rate}% completed
                                </div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Ticket className="w-3.5 h-3.5 text-purple-400" />
                                    Tickets & Gate
                                </div>
                                <div className="text-xl font-black text-white">
                                    {metrics.ticketing.tickets_sold.toLocaleString()}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">
                                    Gate Attendance: {metrics.ticketing.attendance_rate}%
                                </div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <BookOpen className="w-3.5 h-3.5 text-indigo-400" />
                                    Learning Hub
                                </div>
                                <div className="text-xl font-black text-white">
                                    {metrics.learning.total_enrollments}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">
                                    {metrics.learning.certificates_issued} certificates issued
                                </div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <DollarSign className="w-3.5 h-3.5 text-teal-400" />
                                    Sponsorship Vol.
                                </div>
                                <div className="text-xl font-black text-emerald-400">
                                    {metrics.sponsorship.total_volume.toLocaleString()} <span className="text-xs font-normal text-slate-400">{metrics.sponsorship.currency}</span>
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">
                                    {metrics.sponsorship.active_deals} active contracts
                                </div>
                            </Card>
                        </div>

                        {/* Middle Section: University Leaderboard & Match Dispute Quality */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* University Network Leaderboard */}
                            <Card className="bg-slate-900/60 border-slate-800">
                                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                    <div>
                                        <h3 className="font-bold text-white text-base">Collegiate University Participation</h3>
                                        <p className="text-xs text-slate-400">Top ranked academic institutions by registered verified players</p>
                                    </div>
                                    <Building2 className="w-5 h-5 text-emerald-400" />
                                </CardHeader>
                                <CardBody className="pt-0">
                                    <div className="space-y-3">
                                        {metrics.universities.length === 0 ? (
                                            <p className="text-xs text-slate-500 italic">No university profiles registered yet.</p>
                                        ) : (
                                            metrics.universities.map((uni, idx) => (
                                                <div key={idx} className="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 font-bold text-xs flex items-center justify-center">
                                                            {idx + 1}
                                                        </div>
                                                        <span className="font-semibold text-xs text-white">{uni.university}</span>
                                                    </div>
                                                    <Badge variant="outline" className="text-emerald-400 border-slate-700 font-mono text-[11px]">
                                                        {uni.student_count} Students
                                                    </Badge>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardBody>
                            </Card>

                            {/* Competitive Engine Integrity & Disputes */}
                            <Card className="bg-slate-900/60 border-slate-800">
                                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                    <div>
                                        <h3 className="font-bold text-white text-base">Competitive Engine & Dispute Rate</h3>
                                        <p className="text-xs text-slate-400">Fair play and referee resolution health metrics</p>
                                    </div>
                                    <Trophy className="w-5 h-5 text-amber-400" />
                                </CardHeader>
                                <CardBody className="space-y-4 pt-0">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                                            <div className="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Total Matches</div>
                                            <div className="text-xl font-black text-white mt-1">{metrics.tournaments.total_matches.toLocaleString()}</div>
                                            <div className="text-[10px] text-emerald-400 mt-0.5">{metrics.tournaments.match_completion_rate}% completion rate</div>
                                        </div>

                                        <div className="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                                            <div className="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Match Dispute Rate</div>
                                            <div className="text-xl font-black text-rose-400 mt-1">{metrics.tournaments.dispute_rate}%</div>
                                            <div className="text-[10px] text-slate-500 mt-0.5">{metrics.tournaments.total_disputes} disputes registered</div>
                                        </div>
                                    </div>

                                    {/* System Health */}
                                    <div className="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-semibold text-slate-300 flex items-center gap-2">
                                                <Server className="w-4 h-4 text-emerald-400" />
                                                Core Infrastructure Status
                                            </span>
                                            <Badge variant="success" className="uppercase text-[10px]">
                                                {metrics.system_health.status}
                                            </Badge>
                                        </div>
                                        <div className="text-[11px] text-slate-400 grid grid-cols-2 gap-2 pt-1">
                                            <div>PHP: <strong className="text-slate-200">{metrics.system_health.php_version}</strong></div>
                                            <div>Laravel: <strong className="text-slate-200">{metrics.system_health.laravel_version}</strong></div>
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        </div>

                        {/* Feature Flags Management */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                <div>
                                    <h3 className="font-bold text-white text-base">Platform Feature Flags & Rollout Control</h3>
                                    <p className="text-xs text-slate-400">Instantly enable or disable capabilities platform-wide</p>
                                </div>
                                <Sparkles className="w-5 h-5 text-teal-400" />
                            </CardHeader>
                            <CardBody className="pt-0">
                                <div className="divide-y divide-slate-800/80">
                                    {featureFlags.map((flag) => (
                                        <div key={flag.id} className="py-3 flex items-center justify-between gap-4">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <h4 className="font-bold text-white text-sm">{flag.name}</h4>
                                                    <span className="text-[10px] font-mono text-slate-500">[{flag.key}]</span>
                                                </div>
                                                {flag.description && (
                                                    <p className="text-xs text-slate-400 mt-0.5">{flag.description}</p>
                                                )}
                                            </div>

                                            <button
                                                disabled={togglingKey === flag.key}
                                                onClick={() => toggleFeatureFlag(flag)}
                                                className="transition-transform active:scale-95 disabled:opacity-50"
                                            >
                                                {flag.is_enabled ? (
                                                    <ToggleRight className="w-8 h-8 text-emerald-400 hover:text-emerald-300" />
                                                ) : (
                                                    <ToggleLeft className="w-8 h-8 text-slate-600 hover:text-slate-500" />
                                                )}
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
