import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../layouts/AppLayout';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { Card } from '../components/ui/Card';
import {
    Gamepad2,
    Trophy,
    GraduationCap,
    ShieldCheck,
    Zap,
    QrCode,
    Sparkles,
    Users,
    ArrowRight,
    MapPin,
} from 'lucide-react';
import { useTranslation } from '../hooks/useTranslation';

interface LandingProps {
    stats?: {
        totalPlayers: number;
        totalTeams: number;
        activeTournaments: number;
        partnerUniversities: number;
    };
    games?: Array<{
        name: string;
        category: string;
        publisher: string;
    }>;
}

export default function Landing({
    stats = {
        totalPlayers: 0,
        totalTeams: 0,
        activeTournaments: 0,
        partnerUniversities: 0,
    },
    games = [],
}: LandingProps) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title="Integrated Gaming & Esports Platform for Ethiopia" />

            {/* Hero Section */}
            <section className="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-32 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
                {/* Background glowing decorations */}
                <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
                <div className="absolute top-1/3 right-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                    <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold mb-6 animate-pulse">
                        <Sparkles className="w-3.5 h-3.5" />
                        <span>Ethiopia's Official University Esports & Gaming Ecosystem</span>
                    </div>

                    <h1 className="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight sm:leading-none">
                        Elevate Ethiopian <span className="bg-gradient-to-r from-emerald-400 via-teal-300 to-amber-400 bg-clip-text text-transparent">Esports & Gaming</span>
                    </h1>

                    <p className="mt-6 text-base sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                        Compete in verified collegiate tournaments, represent your campus, gain verified student gamer credentials, and learn digital awareness skills.
                    </p>

                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Link href="/register">
                            <Button size="lg" className="shadow-lg shadow-emerald-500/20 font-bold" rightIcon={<ArrowRight className="w-4 h-4" />}>
                                {t('common.register')}
                            </Button>
                        </Link>
                        <Link href="/tournaments">
                            <Button variant="outline" size="lg">
                                {t('common.tournaments')}
                            </Button>
                        </Link>
                    </div>

                    {/* Quick Stats Grid */}
                    <div className="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                        <Card className="p-4 bg-slate-900/60 text-center border-slate-800">
                            <div className="text-2xl sm:text-3xl font-extrabold text-emerald-400">
                                {stats.totalPlayers}
                            </div>
                            <div className="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">
                                Registered Gamers
                            </div>
                        </Card>
                        <Card className="p-4 bg-slate-900/60 text-center border-slate-800">
                            <div className="text-2xl sm:text-3xl font-extrabold text-teal-400">
                                {stats.totalTeams}
                            </div>
                            <div className="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">
                                Campus Teams
                            </div>
                        </Card>
                        <Card className="p-4 bg-slate-900/60 text-center border-slate-800">
                            <div className="text-2xl sm:text-3xl font-extrabold text-amber-400">
                                {stats.activeTournaments}
                            </div>
                            <div className="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">
                                Open competitions
                            </div>
                        </Card>
                        <Card className="p-4 bg-slate-900/60 text-center border-slate-800">
                            <div className="text-2xl sm:text-3xl font-extrabold text-indigo-400">
                                {stats.partnerUniversities}
                            </div>
                            <div className="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">
                                Campuses listed
                            </div>
                        </Card>
                    </div>
                </div>
            </section>

            {/* Core Pillars Section */}
            <section className="py-20 bg-slate-950 border-t border-slate-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <Badge variant="primary" size="md">Platform Features</Badge>
                        <h2 className="text-3xl sm:text-4xl font-bold text-white mt-3">
                            Built for Low-Latency, Trust & Local Realities
                        </h2>
                        <p className="text-slate-400 mt-3 text-sm sm:text-base">
                            Engineered for intermittent connectivity, local Ethiopian payment gateways (Telebirr & Chapa), and transparent bracket execution.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card hover className="p-6">
                            <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-5">
                                <Trophy className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-100">Automated Brackets & Dispute Proof</h3>
                            <p className="text-slate-400 text-sm mt-2 leading-relaxed">
                                Single/double elimination, round-robin, and Swiss brackets with screenshot verification, match refereeing, and real-time live updates.
                            </p>
                        </Card>

                        <Card hover className="p-6">
                            <div className="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center mb-5">
                                <QrCode className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-100">Offline-Ready QR Entry & Ticketing</h3>
                            <p className="text-slate-400 text-sm mt-2 leading-relaxed">
                                Buy passes with Telebirr or Chapa. Signed QR tokens validate instantaneously at gaming zones, even during local internet downtime.
                            </p>
                        </Card>

                        <Card hover className="p-6">
                            <div className="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-5">
                                <GraduationCap className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-100">Collegiate Network & Digital Skills</h3>
                            <p className="text-slate-400 text-sm mt-2 leading-relaxed">
                                Verified student status across AAU, AASTU, ASTU, Hawassa, and Jimma. Earn verified digital safety and game dev certificates.
                            </p>
                        </Card>
                    </div>
                </div>
            </section>

            {/* Supported Competitive Titles */}
            <section className="py-16 bg-slate-900/40 border-t border-slate-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row items-center justify-between gap-4 mb-10">
                        <div>
                            <h2 className="text-2xl font-bold text-white">Supported Competitive Titles</h2>
                            <p className="text-sm text-slate-400">Games saved on the platform</p>
                        </div>
                        <Link href="/tournaments">
                            <Button variant="ghost" size="sm" rightIcon={<ArrowRight className="w-4 h-4" />}>
                                View All Games
                            </Button>
                        </Link>
                    </div>

                    {games.length === 0 ? (
                        <p className="text-sm text-slate-400">No games have been added yet.</p>
                    ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        {games.map((game, idx) => (
                            <Card key={idx} hover className="p-4 text-center">
                                <div className="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center mx-auto mb-3 text-emerald-400">
                                    <Gamepad2 className="w-5 h-5" />
                                </div>
                                <h4 className="font-bold text-sm text-slate-100 truncate">{game.name}</h4>
                                <span className="text-[11px] text-slate-400 block mt-0.5">{game.category}</span>
                            </Card>
                        ))}
                    </div>
                    )}
                </div>
            </section>

            {/* Call to action */}
            <section className="py-20 bg-gradient-to-r from-emerald-950/40 via-slate-950 to-indigo-950/40 border-t border-slate-900">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl sm:text-4xl font-extrabold text-white">
                        Ready to Compete for Your University?
                    </h2>
                    <p className="text-slate-300 mt-4 text-base max-w-xl mx-auto">
                        Create your official gamer tag, join your campus team, and participate in our upcoming inter-university qualifiers.
                    </p>
                    <div className="mt-8 flex justify-center gap-4">
                        <Link href="/register">
                            <Button size="lg" className="font-bold">
                                Create Free Player Account
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </AppLayout>
    );
}
