import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    Trophy,
    Coins,
    Heart,
    ShieldCheck,
    ArrowLeft,
    CheckCircle2,
    DollarSign,
    Users,
    MessageSquare,
    Sparkles
} from 'lucide-react';

interface Contributor {
    id: number;
    donor_name: string;
    amount: number;
    is_anonymous: boolean;
    message: string | null;
    brand_logo: string | null;
    date: string | null;
}

interface PrizePoolProgress {
    tournament_id: number;
    tournament_title: string;
    current_prize_pool: number;
    total_contributed: number;
    contributions_count: number;
    currency: string;
    top_contributors: Contributor[];
}

export default function TournamentPrizePool({ tournamentId }: { tournamentId: number }) {
    const [progress, setProgress] = useState<PrizePoolProgress | null>(null);
    const [loading, setLoading] = useState(true);

    // Contribution form state
    const [amount, setAmount] = useState('500');
    const [donorName, setDonorName] = useState('');
    const [message, setMessage] = useState('');
    const [isAnonymous, setIsAnonymous] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');

    useEffect(() => {
        fetchProgress();
    }, [tournamentId]);

    const fetchProgress = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/prize-pool`);
            const data = await res.json();
            if (data.success) {
                setProgress(data.data);
            }
        } catch (err) {
            console.error('Failed to load prize pool data', err);
        } finally {
            setLoading(false);
        }
    };

    const handleContribute = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setSuccessMessage('');
        try {
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/prize-pool/contribute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: parseFloat(amount),
                    donor_name: donorName || undefined,
                    is_anonymous: isAnonymous,
                    message: message || undefined,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setSuccessMessage('Thank you! Your contribution has been added to the official prize pool.');
                setMessage('');
                fetchProgress();
            }
        } catch (err) {
            console.error('Failed to contribute to prize pool', err);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Head title={`Prize Pool Funding - ${progress?.tournament_title || 'Tournament'}`} />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Back to tournament */}
                <div>
                    <Link
                        href={`/tournaments/${tournamentId}`}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back to Tournament Overview
                    </Link>
                </div>

                {loading ? (
                    <div className="py-20 text-center text-slate-400 space-y-3">
                        <div className="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto" />
                        <p className="text-sm">Loading tournament prize pool funding ledger...</p>
                    </div>
                ) : !progress ? (
                    <Card className="bg-slate-900/60 border-slate-800 p-8 text-center text-slate-400">
                        Tournament not found or prize pool data unavailable.
                    </Card>
                ) : (
                    <div className="space-y-8">
                        {/* Hero Prize Pool Card */}
                        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-950/60 via-slate-900 to-emerald-950/60 border border-amber-500/30 p-8 text-center space-y-4 shadow-2xl">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold uppercase tracking-wider">
                                <Sparkles className="w-3.5 h-3.5" />
                                Transparent Prize Pool Funding
                            </div>

                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                                {progress.tournament_title}
                            </h1>

                            <div className="pt-2">
                                <div className="text-4xl sm:text-6xl font-black text-amber-400 tracking-tight">
                                    {Number(progress.current_prize_pool).toLocaleString()}{' '}
                                    <span className="text-2xl sm:text-3xl text-slate-400 font-bold">{progress.currency}</span>
                                </div>
                                <div className="text-xs text-slate-400 mt-2 flex items-center justify-center gap-4">
                                    <span>Community Contributions: <strong className="text-emerald-400">{Number(progress.total_contributed).toLocaleString()} {progress.currency}</strong></span>
                                    <span>•</span>
                                    <span>Total Backers: <strong className="text-white">{progress.contributions_count}</strong></span>
                                </div>
                            </div>
                        </div>

                        {successMessage && (
                            <div className="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                                {successMessage}
                            </div>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {/* Contribute Form */}
                            <Card className="bg-slate-900/60 border-slate-800">
                                <CardHeader className="pb-3">
                                    <h3 className="font-bold text-white text-base flex items-center gap-2">
                                        <Coins className="w-4 h-4 text-amber-400" />
                                        Boost This Tournament's Prize Pool
                                    </h3>
                                    <p className="text-xs text-slate-400">
                                        All contributions directly increment the verified tournament payout pool.
                                    </p>
                                </CardHeader>
                                <CardBody className="pt-0">
                                    <form onSubmit={handleContribute} className="space-y-4">
                                        <div>
                                            <label className="block text-xs font-semibold text-slate-300 mb-1">
                                                Contribution Amount ({progress.currency})
                                            </label>
                                            <Input
                                                required
                                                type="number"
                                                min="50"
                                                step="50"
                                                value={amount}
                                                onChange={(e) => setAmount(e.target.value)}
                                                className="bg-slate-950 border-slate-800 text-emerald-400 font-bold"
                                            />
                                            <div className="flex items-center gap-2 mt-2">
                                                {['200', '500', '1000', '5000'].map((preset) => (
                                                    <button
                                                        type="button"
                                                        key={preset}
                                                        onClick={() => setAmount(preset)}
                                                        className="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] font-semibold text-slate-300 transition-colors"
                                                    >
                                                        +{preset}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>

                                        <div>
                                            <label className="block text-xs font-semibold text-slate-300 mb-1">
                                                Donor / Sponsor Name (Optional)
                                            </label>
                                            <Input
                                                placeholder="e.g. AAU Gaming Guild or Brand"
                                                disabled={isAnonymous}
                                                value={donorName}
                                                onChange={(e) => setDonorName(e.target.value)}
                                                className="bg-slate-950 border-slate-800"
                                            />
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="anonymousCheck"
                                                checked={isAnonymous}
                                                onChange={(e) => setIsAnonymous(e.target.checked)}
                                                className="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-0"
                                            />
                                            <label htmlFor="anonymousCheck" className="text-xs text-slate-400 cursor-pointer">
                                                Contribute anonymously (hide identity on leaderboard)
                                            </label>
                                        </div>

                                        <div>
                                            <label className="block text-xs font-semibold text-slate-300 mb-1">
                                                Cheer / Shoutout Message
                                            </label>
                                            <textarea
                                                rows={2}
                                                placeholder="Good luck to all AAU and AASTU squads!"
                                                value={message}
                                                onChange={(e) => setMessage(e.target.value)}
                                                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            variant="primary"
                                            disabled={submitting}
                                            className="w-full text-xs font-bold py-2.5 shadow-lg shadow-emerald-950/60"
                                        >
                                            {submitting ? 'Processing Contribution...' : `Contribute ${amount} ${progress.currency}`}
                                        </Button>
                                    </form>
                                </CardBody>
                            </Card>

                            {/* Top Contributors Leaderboard */}
                            <Card className="bg-slate-900/60 border-slate-800">
                                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                    <div>
                                        <h3 className="font-bold text-white text-base">Top Backers & Sponsors</h3>
                                        <p className="text-xs text-slate-400">Leading supporters empowering the prize pool</p>
                                    </div>
                                    <Trophy className="w-5 h-5 text-amber-400" />
                                </CardHeader>
                                <CardBody className="pt-0">
                                    <div className="space-y-3">
                                        {progress.top_contributors.length === 0 ? (
                                            <div className="p-8 text-center text-xs text-slate-500 italic">
                                                Be the first sponsor or backer to boost this tournament's prize pool!
                                            </div>
                                        ) : (
                                            progress.top_contributors.map((contrib, idx) => (
                                                <div
                                                    key={contrib.id}
                                                    className="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between gap-3"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 font-black text-xs flex items-center justify-center border border-amber-500/20">
                                                            #{idx + 1}
                                                        </div>
                                                        <div>
                                                            <div className="font-bold text-xs text-white flex items-center gap-1.5">
                                                                {contrib.donor_name}
                                                                {!contrib.is_anonymous && (
                                                                    <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                                                                )}
                                                            </div>
                                                            {contrib.message && (
                                                                <p className="text-[11px] text-slate-400 italic mt-0.5">
                                                                    "{contrib.message}"
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <div className="text-right">
                                                        <div className="font-black text-xs text-emerald-400">
                                                            +{Number(contrib.amount).toLocaleString()} {progress.currency}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardBody>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
