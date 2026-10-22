import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    TrendingUp,
    Download,
    Eye,
    MousePointer,
    DollarSign,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    FileText,
    ExternalLink,
    ShieldCheck,
    Layers,
    AlertCircle
} from 'lucide-react';

interface Deliverable {
    id: number;
    title: string;
    description: string | null;
    deliverable_type: string;
    status: 'pending' | 'submitted' | 'verified' | 'rejected';
    due_date: string | null;
    evidence_url: string | null;
    evidence_notes: string | null;
}

interface Sponsorship {
    id: number;
    contract_number: string;
    package_name: string;
    target_type: string;
    amount: number;
    currency: string;
    status: string;
    total_impressions: number;
    total_clicks: number;
    brand_profile?: {
        company_name: string;
        logo_url: string | null;
    };
    deliverables: Deliverable[];
}

interface ReportMetrics {
    sponsorship_id: number;
    contract_number: string;
    brand_name: string;
    package_title: string;
    investment_amount: number;
    currency: string;
    status: string;
    total_impressions: number;
    total_clicks: number;
    click_through_rate: number;
    cpm: number;
    cpc: number;
    estimated_media_value: number;
    deliverables: {
        total: number;
        verified: number;
        fulfillment_rate: number;
    };
}

export default function SponsorsDashboard() {
    const [sponsorships, setSponsorships] = useState<Sponsorship[]>([]);
    const [selectedSponsorship, setSelectedSponsorship] = useState<Sponsorship | null>(null);
    const [report, setReport] = useState<ReportMetrics | null>(null);
    const [loading, setLoading] = useState(true);

    // Deliverable evidence state
    const [selectedDeliverable, setSelectedDeliverable] = useState<Deliverable | null>(null);
    const [evidenceUrl, setEvidenceUrl] = useState('');
    const [evidenceNotes, setEvidenceNotes] = useState('');
    const [submittingEvidence, setSubmittingEvidence] = useState(false);

    useEffect(() => {
        fetchSponsorships();
    }, []);

    const fetchSponsorships = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/sponsorships');
            const data = await res.json();
            if (data.success && data.data) {
                const list = data.data.data || data.data || [];
                setSponsorships(list);
                if (list.length > 0) {
                    selectSponsorship(list[0]);
                }
            }
        } catch (err) {
            console.error('Failed to fetch sponsorships', err);
        } finally {
            setLoading(false);
        }
    };

    const selectSponsorship = async (sponsorship: Sponsorship) => {
        setSelectedSponsorship(sponsorship);
        try {
            const res = await fetch(`/api/v1/sponsorships/${sponsorship.id}/report`);
            const data = await res.json();
            if (data.success) {
                setReport(data.data);
            }
        } catch (err) {
            console.error('Failed to load report', err);
        }
    };

    const handleSubmitEvidence = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedDeliverable) return;

        setSubmittingEvidence(true);
        try {
            const res = await fetch(`/api/v1/sponsorships/deliverables/${selectedDeliverable.id}/evidence`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    evidence_url: evidenceUrl,
                    evidence_notes: evidenceNotes,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setSelectedDeliverable(null);
                setEvidenceUrl('');
                setEvidenceNotes('');
                fetchSponsorships();
            }
        } catch (err) {
            console.error('Failed to submit evidence', err);
        } finally {
            setSubmittingEvidence(false);
        }
    };

    const getDeliverableStatusBadge = (status: string) => {
        switch (status) {
            case 'verified':
                return <Badge variant="success" className="flex items-center gap-1"><CheckCircle2 className="w-3 h-3" /> Verified</Badge>;
            case 'submitted':
                return <Badge variant="info" className="flex items-center gap-1"><Clock className="w-3 h-3" /> Under Review</Badge>;
            case 'rejected':
                return <Badge variant="danger" className="flex items-center gap-1"><AlertCircle className="w-3 h-3" /> Revision Needed</Badge>;
            default:
                return <Badge variant="neutral" className="flex items-center gap-1"><Clock className="w-3 h-3" /> Pending</Badge>;
        }
    };

    return (
        <AppLayout>
            <Head title="Sponsor Performance Dashboard & Deliverables Tracker" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-1">
                            <TrendingUp className="w-4 h-4" />
                            ROI & Campaign Performance
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                            Sponsor Analytics & Contract Deliverables
                        </h1>
                    </div>

                    {selectedSponsorship && (
                        <div className="flex items-center gap-3">
                            <a
                                href={`/api/v1/sponsorships/${selectedSponsorship.id}/report/export`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Button variant="primary" className="flex items-center gap-2 shadow-lg shadow-emerald-900/40">
                                    <Download className="w-4 h-4" />
                                    Export CSV Report
                                </Button>
                            </a>
                        </div>
                    )}
                </div>

                {loading ? (
                    <div className="py-20 text-center text-slate-400 space-y-3">
                        <div className="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto" />
                        <p className="text-sm">Loading active brand campaigns...</p>
                    </div>
                ) : sponsorships.length === 0 ? (
                    <Card className="bg-slate-900/60 border-slate-800 p-12 text-center space-y-4">
                        <FileText className="w-12 h-12 text-slate-600 mx-auto" />
                        <h3 className="text-lg font-bold text-white">No Active Sponsorship Contracts</h3>
                        <p className="text-sm text-slate-400 max-w-md mx-auto">
                            You currently don't have any registered sponsorship contracts. Browse available collegiate tournament and club packages in the marketplace.
                        </p>
                        <Link href="/sponsors">
                            <Button variant="primary" className="mt-2">
                                Explore Sponsorship Packages
                            </Button>
                        </Link>
                    </Card>
                ) : (
                    <div className="space-y-8">
                        {/* Contract Switcher Tabs */}
                        <div className="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800/80">
                            {sponsorships.map((s) => (
                                <button
                                    key={s.id}
                                    onClick={() => selectSponsorship(s)}
                                    className={`px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 ${
                                        selectedSponsorship?.id === s.id
                                            ? 'bg-slate-800 text-emerald-400 border border-emerald-500/30 shadow-md'
                                            : 'text-slate-400 hover:text-white hover:bg-slate-900/60'
                                    }`}
                                >
                                    <span>{s.contract_number}</span>
                                    <span className="text-[10px] text-slate-500 uppercase tracking-wider">({s.package_name})</span>
                                </button>
                            ))}
                        </div>

                        {/* Executive ROI KPI Cards */}
                        {report && (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                                <Card className="bg-slate-900/60 border-slate-800 p-4">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <Eye className="w-3.5 h-3.5 text-blue-400" />
                                        Impressions
                                    </div>
                                    <div className="text-xl font-black text-white">
                                        {report.total_impressions.toLocaleString()}
                                    </div>
                                    <div className="text-[10px] text-slate-500 mt-1">Total media views</div>
                                </Card>

                                <Card className="bg-slate-900/60 border-slate-800 p-4">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <MousePointer className="w-3.5 h-3.5 text-emerald-400" />
                                        Total Clicks
                                    </div>
                                    <div className="text-xl font-black text-emerald-400">
                                        {report.total_clicks.toLocaleString()}
                                    </div>
                                    <div className="text-[10px] text-slate-500 mt-1">CTR: {report.click_through_rate}%</div>
                                </Card>

                                <Card className="bg-slate-900/60 border-slate-800 p-4">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <TrendingUp className="w-3.5 h-3.5 text-amber-400" />
                                        Cost / Mille
                                    </div>
                                    <div className="text-xl font-black text-white">
                                        {report.cpm} <span className="text-xs font-normal text-slate-400">{report.currency}</span>
                                    </div>
                                    <div className="text-[10px] text-slate-500 mt-1">CPM Benchmark</div>
                                </Card>

                                <Card className="bg-slate-900/60 border-slate-800 p-4">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <DollarSign className="w-3.5 h-3.5 text-purple-400" />
                                        Cost / Click
                                    </div>
                                    <div className="text-xl font-black text-white">
                                        {report.cpc} <span className="text-xs font-normal text-slate-400">{report.currency}</span>
                                    </div>
                                    <div className="text-[10px] text-slate-500 mt-1">CPC Efficiency</div>
                                </Card>

                                <Card className="bg-slate-900/60 border-slate-800 p-4">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <Layers className="w-3.5 h-3.5 text-teal-400" />
                                        Est. Media Value
                                    </div>
                                    <div className="text-xl font-black text-emerald-400">
                                        {report.estimated_media_value.toLocaleString()} <span className="text-xs font-normal text-slate-400">{report.currency}</span>
                                    </div>
                                    <div className="text-[10px] text-slate-500 mt-1">Earned PR value</div>
                                </Card>

                                <Card className="bg-slate-900/60 border-slate-800 p-4">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-pink-400" />
                                        Deliverables
                                    </div>
                                    <div className="text-xl font-black text-white">
                                        {report.deliverables.fulfillment_rate}%
                                    </div>
                                    <div className="text-[10px] text-slate-500 mt-1">{report.deliverables.verified}/{report.deliverables.total} verified</div>
                                </Card>
                            </div>
                        )}

                        {/* Deliverables Fulfillment Table */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader className="flex flex-row items-center justify-between pb-3">
                                <div>
                                    <h3 className="text-lg font-bold text-white">Contract Deliverables & Audit Trail</h3>
                                    <p className="text-xs text-slate-400">Submit proof URLs and stream recordings for verification</p>
                                </div>
                                <div className="text-xs text-slate-400 font-medium">
                                    Contract ID: <span className="text-emerald-400 font-mono font-bold">{selectedSponsorship?.contract_number}</span>
                                </div>
                            </CardHeader>
                            <CardBody className="pt-0">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead className="bg-slate-950/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                            <tr>
                                                <th className="py-3 px-4">Deliverable</th>
                                                <th className="py-3 px-4">Type</th>
                                                <th className="py-3 px-4">Status</th>
                                                <th className="py-3 px-4">Proof Evidence</th>
                                                <th className="py-3 px-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                            {selectedSponsorship?.deliverables.map((deliv) => (
                                                <tr key={deliv.id} className="hover:bg-slate-800/30 transition-colors">
                                                    <td className="py-3 px-4 font-semibold text-white">
                                                        {deliv.title}
                                                        {deliv.description && (
                                                            <div className="text-[11px] text-slate-400 font-normal mt-0.5">
                                                                {deliv.description}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-4 capitalize">
                                                        {deliv.deliverable_type.replace('_', ' ')}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        {getDeliverableStatusBadge(deliv.status)}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        {deliv.evidence_url ? (
                                                            <a
                                                                href={deliv.evidence_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="text-emerald-400 hover:underline flex items-center gap-1 font-mono text-[11px]"
                                                            >
                                                                View Proof
                                                                <ExternalLink className="w-3 h-3" />
                                                            </a>
                                                        ) : (
                                                            <span className="text-slate-500 italic">No proof uploaded</span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-4 text-right">
                                                        {deliv.status !== 'verified' && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    setSelectedDeliverable(deliv);
                                                                    setEvidenceUrl(deliv.evidence_url || '');
                                                                    setEvidenceNotes(deliv.evidence_notes || '');
                                                                }}
                                                                className="text-xs"
                                                            >
                                                                Submit Evidence
                                                            </Button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardBody>
                        </Card>

                        {/* Evidence Upload Modal / Drawer */}
                        {selectedDeliverable && (
                            <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
                                <Card className="w-full max-w-lg bg-slate-900 border-slate-700 shadow-2xl">
                                    <CardHeader className="border-b border-slate-800 pb-3">
                                        <h3 className="font-bold text-white text-base">
                                            Submit Deliverable Evidence: {selectedDeliverable.title}
                                        </h3>
                                        <p className="text-xs text-slate-400">
                                            Provide screenshot URL, broadcast clip, or live stream timestamp.
                                        </p>
                                    </CardHeader>
                                    <CardBody>
                                        <form onSubmit={handleSubmitEvidence} className="space-y-4">
                                            <div>
                                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                                    Evidence URL (Image, Stream, or Document)
                                                </label>
                                                <Input
                                                    required
                                                    type="url"
                                                    placeholder="https://clips.twitch.tv/..."
                                                    value={evidenceUrl}
                                                    onChange={(e) => setEvidenceUrl(e.target.value)}
                                                    className="bg-slate-950 border-slate-800"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                                    Notes or Timestamp Reference
                                                </label>
                                                <textarea
                                                    rows={3}
                                                    placeholder="Logo shown at Grand Finals overlay between 01:23:00 - 02:15:00..."
                                                    value={evidenceNotes}
                                                    onChange={(e) => setEvidenceNotes(e.target.value)}
                                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                                                />
                                            </div>

                                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    onClick={() => setSelectedDeliverable(null)}
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    variant="primary"
                                                    disabled={submittingEvidence}
                                                >
                                                    {submittingEvidence ? 'Submitting...' : 'Submit Evidence'}
                                                </Button>
                                            </div>
                                        </form>
                                    </CardBody>
                                </Card>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
