import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../layouts/AppLayout';
import { Button } from '../components/ui/Button';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import {
    Trophy,
    Users,
    Building2,
    BookOpen,
    ShieldCheck,
    ArrowRight,
    Gamepad2,
    Calendar,
    Sparkles,
} from 'lucide-react';
import { useTranslation } from '../hooks/useTranslation';

interface DashboardProps {
    auth: {
        user: {
            id: number;
            uuid: string;
            name: string;
            email?: string;
            phone?: string;
            roles: string[];
            profile?: {
                gamer_tag: string;
                university?: string;
                student_verified: boolean;
            };
        };
    };
    recentTournaments?: Array<{
        id: number;
        title: string;
        game: string;
        format: string;
        status: string;
        date: string | null;
    }>;
}

export default function Dashboard({
    auth,
    recentTournaments = [],
}: DashboardProps) {
    const { t } = useTranslation();
    const user = auth.user;

    return (
        <AppLayout>
            <Head title="Player Dashboard - Campus Esports Ethiopia" />

            <div className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Welcome Card */}
                <div className="rounded-2xl p-6 sm:p-8 bg-gradient-to-r from-emerald-950/80 via-slate-900 to-indigo-950/80 border border-slate-800 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div className="space-y-2 text-center sm:text-left">
                        <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <span className="text-xs uppercase font-extrabold text-emerald-400 tracking-wider">
                                Player Command Center
                            </span>
                            {user.profile?.student_verified && (
                                <Badge variant="success" size="sm" className="flex items-center gap-1">
                                    <ShieldCheck className="w-3 h-3 text-emerald-400" />
                                    <span>Verified Campus Student</span>
                                </Badge>
                            )}
                        </div>

                        <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                            Welcome back, <span className="text-emerald-400">{user.profile?.gamer_tag || user.name}</span>
                        </h1>

                        <p className="text-xs sm:text-sm text-slate-300">
                            {user.profile?.university || 'Set your university to represent your campus in national leagues.'}
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href="/profile">
                            <Button variant="outline" size="md">
                                Edit Profile
                            </Button>
                        </Link>
                        <Link href="/tournaments">
                            <Button size="md" rightIcon={<ArrowRight className="w-4 h-4" />}>
                                Explore Tournaments
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Quick Action Navigation Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card hover className="p-5 flex flex-col justify-between">
                        <div>
                            <div className="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center mb-4">
                                <Trophy className="w-5 h-5" />
                            </div>
                            <h3 className="font-bold text-slate-100 text-base">Tournaments</h3>
                            <p className="text-xs text-slate-400 mt-1">
                                Register for campus qualifiers, view brackets, and report match results.
                            </p>
                        </div>
                        <Link href="/tournaments" className="mt-4 text-xs font-semibold text-emerald-400 hover:underline flex items-center gap-1">
                            Browse Brackets <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </Card>

                    <Card hover className="p-5 flex flex-col justify-between">
                        <div>
                            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4">
                                <Users className="w-5 h-5" />
                            </div>
                            <h3 className="font-bold text-slate-100 text-base">Campus Teams</h3>
                            <p className="text-xs text-slate-400 mt-1">
                                Form rosters with fellow university students or recruit new players.
                            </p>
                        </div>
                        <Link href="/teams" className="mt-4 text-xs font-semibold text-emerald-400 hover:underline flex items-center gap-1">
                            Manage Teams <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </Card>

                    <Card hover className="p-5 flex flex-col justify-between">
                        <div>
                            <div className="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-4">
                                <Building2 className="w-5 h-5" />
                            </div>
                            <h3 className="font-bold text-slate-100 text-base">Gaming Zones</h3>
                            <p className="text-xs text-slate-400 mt-1">
                                Discover local LAN centers in Addis Ababa, Hawassa, and Bahir Dar.
                            </p>
                        </div>
                        <Link href="/venues" className="mt-4 text-xs font-semibold text-emerald-400 hover:underline flex items-center gap-1">
                            Find LAN Zones <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </Card>

                    <Card hover className="p-5 flex flex-col justify-between">
                        <div>
                            <div className="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center mb-4">
                                <BookOpen className="w-5 h-5" />
                            </div>
                            <h3 className="font-bold text-slate-100 text-base">Digital Literacy</h3>
                            <p className="text-xs text-slate-400 mt-1">
                                Complete game dev courses and cyber-hygiene certificates.
                            </p>
                        </div>
                        <Link href="/education" className="mt-4 text-xs font-semibold text-emerald-400 hover:underline flex items-center gap-1">
                            Start Course <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </Card>
                </div>

                {/* Live & Upcoming Tournaments */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-bold text-white">Upcoming Campus Tournaments</h2>
                            <p className="text-xs text-slate-400">Official competitions opening for team registration</p>
                        </div>
                        <Link href="/tournaments">
                            <Button variant="ghost" size="sm">
                                View Full Calendar
                            </Button>
                        </Link>
                    </div>

                    <div className="space-y-3">
                        {recentTournaments.length === 0 ? (
                            <Card className="p-6 text-sm text-slate-300">
                                No published tournaments are open yet.
                                <Link href="/tournaments" className="ml-2 font-semibold text-emerald-400 hover:underline">
                                    Open the tournament list
                                </Link>
                            </Card>
                        ) : recentTournaments.map((tourney) => (
                            <Card key={tourney.id} hover className="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div className="flex items-start gap-3.5">
                                    <div className="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <Gamepad2 className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4 className="font-bold text-slate-100 text-sm">{tourney.title}</h4>
                                        <div className="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-400">
                                            <span>{tourney.game}</span>
                                            <span>•</span>
                                            <span className="capitalize">{tourney.format}</span>
                                            {tourney.date && (
                                                <>
                                                    <span>•</span>
                                                    <span className="text-emerald-400">{tourney.date}</span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                                    <Badge variant="success" size="sm" className="capitalize">
                                        {tourney.status}
                                    </Badge>
                                    <Link href={`/tournaments/${tourney.id}`}>
                                        <Button size="sm" variant="outline">
                                            Details
                                        </Button>
                                    </Link>
                                </div>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
