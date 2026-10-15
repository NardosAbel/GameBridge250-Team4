import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    BarChart3,
    Download,
    Users,
    CheckCircle2,
    DollarSign,
    RotateCcw,
    AlertCircle,
    UserX,
    Filter,
    FileSpreadsheet
} from 'lucide-react';

interface ReportData {
    total_tickets_sold: number;
    total_checked_in: number;
    total_no_shows: number;
    total_refunded: number;
    check_in_rate: number;
    gross_revenue: number;
    by_tier: {
        ticket_type_id: number;
        name: string;
        sold: number;
        checked_in: number;
        revenue: number;
    }[];
    by_scanner: {
        scanner_id: number;
        scanner_name: string;
        total_scans: number;
        valid_scans: number;
    }[];
}

export default function TicketingReports() {
    const [report, setReport] = useState<ReportData | null>(null);
    const [loading, setLoading] = useState(true);
    const [eventId, setEventId] = useState('');
    const [tournamentId, setTournamentId] = useState('');

    const fetchReport = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (eventId) params.append('event_id', eventId);
            if (tournamentId) params.append('tournament_id', tournamentId);

            const res = await fetch(`/api/v1/tickets/reports?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                setReport(data.data);
            }
        } catch (e) {
            console.error('Failed to load ticketing report', e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchReport();
    }, [eventId, tournamentId]);

    const handleExportCsv = () => {
        const params = new URLSearchParams();
        if (eventId) params.append('event_id', eventId);
        if (tournamentId) params.append('tournament_id', tournamentId);

        window.open(`/api/v1/tickets/reports/export?${params.toString()}`, '_blank');
    };

    return (
        <AppLayout>
            <Head title="Ticketing & Gate Reports - Campus Esports Ethiopia" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                                <BarChart3 className="w-6 h-6" />
                            </span>
                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                Ticketing & Gate Analytics
                            </h1>
                        </div>
                        <p className="text-slate-400 text-sm">
                            Detailed attendance tracking, gate scanner throughput, revenue reconciliation, and CSV export.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            variant="primary"
                            onClick={handleExportCsv}
                            className="flex items-center gap-2 text-xs bg-emerald-600 hover:bg-emerald-500"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            Download CSV Report
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card className="bg-slate-900/60 border-slate-800 mb-8">
                    <CardBody className="p-4 flex flex-wrap items-center gap-4 text-xs">
                        <div className="flex items-center gap-2">
                            <Filter className="w-4 h-4 text-slate-400" />
                            <span className="font-semibold text-slate-300">Filter Scope:</span>
                        </div>

                        <div className="flex items-center gap-2">
                            <label className="text-slate-400">Event ID:</label>
                            <Input
                                placeholder="All Events"
                                value={eventId}
                                onChange={(e) => setEventId(e.target.value)}
                                className="w-32 bg-slate-950 border-slate-800 text-xs py-1.5"
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <label className="text-slate-400">Tournament ID:</label>
                            <Input
                                placeholder="All Tournaments"
                                value={tournamentId}
                                onChange={(e) => setTournamentId(e.target.value)}
                                className="w-32 bg-slate-950 border-slate-800 text-xs py-1.5"
                            />
                        </div>

                        <Button size="sm" variant="ghost" onClick={() => { setEventId(''); setTournamentId(''); }}>
                            Reset Filters
                        </Button>
                    </CardBody>
                </Card>

                {loading ? (
                    <div className="flex flex-col items-center justify-center py-20">
                        <div className="w-10 h-10 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                        <p className="mt-4 text-slate-400 text-sm">Calculating metrics...</p>
                    </div>
                ) : !report ? (
                    <Card className="text-center py-12 text-slate-500">Failed to load report data.</Card>
                ) : (
                    <div className="space-y-8">
                        {/* Summary Stat Cards */}
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                                <span className="text-[10px] text-slate-400 uppercase font-semibold block mb-1">
                                    Tickets Sold
                                </span>
                                <span className="text-2xl font-black text-white">{report.total_tickets_sold}</span>
                            </div>

                            <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                                <span className="text-[10px] text-emerald-400 uppercase font-semibold block mb-1">
                                    Checked In
                                </span>
                                <span className="text-2xl font-black text-emerald-400">{report.total_checked_in}</span>
                            </div>

                            <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                                <span className="text-[10px] text-cyan-400 uppercase font-semibold block mb-1">
                                    Attendance Rate
                                </span>
                                <span className="text-2xl font-black text-cyan-400">{report.check_in_rate}%</span>
                            </div>

                            <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                                <span className="text-[10px] text-slate-400 uppercase font-semibold block mb-1">
                                    No-Shows
                                </span>
                                <span className="text-2xl font-black text-slate-300">{report.total_no_shows}</span>
                            </div>

                            <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                                <span className="text-[10px] text-rose-400 uppercase font-semibold block mb-1">
                                    Refunded
                                </span>
                                <span className="text-2xl font-black text-rose-400">{report.total_refunded}</span>
                            </div>

                            <div className="p-4 rounded-2xl bg-slate-900/80 border border-emerald-500/30">
                                <span className="text-[10px] text-amber-400 uppercase font-semibold block mb-1">
                                    Gross Revenue
                                </span>
                                <span className="text-xl font-black text-white">{report.gross_revenue.toLocaleString()} ETB</span>
                            </div>
                        </div>

                        {/* Breakdown by Tier */}
                        <Card className="bg-slate-900/60 border-slate-800 overflow-hidden">
                            <CardHeader>
                                <h3 className="font-bold text-white text-base">Ticket Categories Breakdown</h3>
                            </CardHeader>
                            <CardBody className="p-0">
                                <table className="w-full text-left text-xs text-slate-300">
                                    <thead className="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th className="py-3 px-4">Tier Name</th>
                                            <th className="py-3 px-4">Sold</th>
                                            <th className="py-3 px-4">Checked In</th>
                                            <th className="py-3 px-4">Revenue (ETB)</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-800/60">
                                        {(report.by_tier || []).length === 0 ? (
                                            <tr>
                                                <td colSpan={4} className="py-6 text-center text-slate-500">
                                                    No category data available.
                                                </td>
                                            </tr>
                                        ) : (
                                            report.by_tier.map((tier) => (
                                                <tr key={tier.ticket_type_id} className="hover:bg-slate-800/30">
                                                    <td className="py-3 px-4 font-bold text-white">{tier.name}</td>
                                                    <td className="py-3 px-4">{tier.sold}</td>
                                                    <td className="py-3 px-4 text-emerald-400 font-semibold">{tier.checked_in}</td>
                                                    <td className="py-3 px-4 font-mono">{tier.revenue.toLocaleString()}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </CardBody>
                        </Card>

                        {/* Gate Scanner Throughput */}
                        <Card className="bg-slate-900/60 border-slate-800 overflow-hidden">
                            <CardHeader>
                                <h3 className="font-bold text-white text-base">Gate Scanner Staff Performance</h3>
                            </CardHeader>
                            <CardBody className="p-0">
                                <table className="w-full text-left text-xs text-slate-300">
                                    <thead className="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th className="py-3 px-4">Staff Member</th>
                                            <th className="py-3 px-4">Total Scans</th>
                                            <th className="py-3 px-4">Valid Entries</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-800/60">
                                        {(report.by_scanner || []).length === 0 ? (
                                            <tr>
                                                <td colSpan={3} className="py-6 text-center text-slate-500">
                                                    No gate scan records found for this period.
                                                </td>
                                            </tr>
                                        ) : (
                                            report.by_scanner.map((sc) => (
                                                <tr key={sc.scanner_id} className="hover:bg-slate-800/30">
                                                    <td className="py-3 px-4 font-bold text-white">{sc.scanner_name}</td>
                                                    <td className="py-3 px-4">{sc.total_scans}</td>
                                                    <td className="py-3 px-4 text-emerald-400 font-semibold">{sc.valid_scans}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </CardBody>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
