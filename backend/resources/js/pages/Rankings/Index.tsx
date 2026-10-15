import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    Trophy,
    Medal,
    Gamepad2,
    Calendar,
    Users,
    Shield,
    Camera,
    CheckCircle2,
    Sparkles,
    Search
} from 'lucide-react';

interface Game {
    id: number;
    name: string;
    slug: string;
}

interface Ranking {
    id: number;
    rank: number;
    points: number;
    rating: number;
    game: Game;
    season: { id: number; name: string } | null;
    participant: {
        type: 'team' | 'user';
        id: number;
        name: string;
        tag?: string;
        university?: string;
    };
}

export default function RankingsIndex() {
    const { auth } = usePage().props as any;
    const [rankings, setRankings] = useState<Ranking[]>([]);
    const [games, setGames] = useState<Game[]>([]);
    const [selectedGame, setSelectedGame] = useState<string>('');
    const [loading, setLoading] = useState(true);
    const [takingSnapshot, setTakingSnapshot] = useState(false);
    const [snapshotMsg, setSnapshotMsg] = useState<string | null>(null);

    const isAdmin = auth?.user?.roles?.some((r: string) => ['admin', 'super-admin'].includes(r));

    const fetchGames = async () => {
        try {
            const res = await fetch('/api/v1/games');
            const data = await res.json();
            if (data.data) {
                setGames(data.data);
                if (data.data.length > 0 && !selectedGame) {
                    setSelectedGame(String(data.data[0].id));
                }
            }
        } catch (e) {
            console.error(e);
        }
    };

    const fetchRankings = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (selectedGame) params.append('game_id', selectedGame);

            const res = await fetch(`/api/v1/rankings/leaderboards?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                setRankings(data.data);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchGames();
    }, []);

    useEffect(() => {
        if (selectedGame) {
            fetchRankings();
        }
    }, [selectedGame]);

    const handleCaptureSnapshot = async () => {
        if (!selectedGame) return;
        setTakingSnapshot(true);
        setSnapshotMsg(null);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/v1/rankings/snapshot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    game_id: Number(selectedGame),
                    type: 'global',
                }),
            });
            const data = await res.json();
            if (data.success) {
                setSnapshotMsg('Immutable ranking snapshot captured and archived.');
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setTakingSnapshot(false);
        }
    };

    const getRankBadge = (rank: number) => {
        if (rank === 1) return <span className="text-xl">🥇</span>;
        if (rank === 2) return <span className="text-xl">🥈</span>;
        if (rank === 3) return <span className="text-xl">🥉</span>;
        return <span className="font-mono text-sm font-bold text-slate-400">#{rank}</span>;
    };

    return (
        <AppLayout>
            <Head title="Rankings & Leaderboards — Campus Esports Ethiopia" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header */}
                <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/20 p-8 sm:p-12 shadow-2xl">
                    <div className="max-w-2xl space-y-4">
                        <Badge variant="outline" className="border-purple-500/30 text-purple-300 font-bold uppercase tracking-wider text-xs">
                            Season 2026 Competitive Standings
                        </Badge>
                        <h1 className="text-3xl sm:text-5xl font-black text-white tracking-tight">
                            National Collegiate Rankings
                        </h1>
                        <p className="text-slate-300 text-sm sm:text-base leading-relaxed">
                            Official leaderboards tracking tournament placements, competitive points, and Elo ratings across Ethiopian university esports.
                        </p>
                        {isAdmin && (
                            <div className="pt-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleCaptureSnapshot}
                                    disabled={takingSnapshot}
                                    className="flex items-center gap-1.5"
                                >
                                    <Camera className="w-4 h-4 text-purple-400" />
                                    {takingSnapshot ? 'Capturing...' : 'Capture Season Snapshot'}
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

                {snapshotMsg && (
                    <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4" />
                        {snapshotMsg}
                    </div>
                )}

                {/* Game Tabs */}
                <div className="flex items-center gap-3 overflow-x-auto pb-2">
                    {games.map((g) => (
                        <button
                            key={g.id}
                            onClick={() => setSelectedGame(String(g.id))}
                            className={`px-4 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap flex items-center gap-2 ${
                                selectedGame === String(g.id)
                                    ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/20'
                                    : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                            }`}
                        >
                            <Gamepad2 className="w-4 h-4" />
                            {g.name}
                        </button>
                    ))}
                </div>

                {/* Leaderboard Table */}
                <Card className="bg-slate-900/40 border-slate-800 overflow-hidden">
                    {loading ? (
                        <div className="p-12 text-center text-slate-400">Loading leaderboards...</div>
                    ) : rankings.length === 0 ? (
                        <div className="p-16 text-center space-y-3">
                            <Trophy className="w-12 h-12 text-slate-600 mx-auto" />
                            <h3 className="text-lg font-bold text-white">No Rankings Recorded Yet</h3>
                            <p className="text-sm text-slate-400 max-w-sm mx-auto">
                                Rankings are calculated automatically when tournaments in this game title finish.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-300">
                                <thead className="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th className="py-4 px-6 text-center w-16">Rank</th>
                                        <th className="py-4 px-6">Participant</th>
                                        <th className="py-4 px-6">Affiliation / Campus</th>
                                        <th className="py-4 px-6 text-center">Type</th>
                                        <th className="py-4 px-6 text-center">Elo Rating</th>
                                        <th className="py-4 px-6 text-right">Tournament Points</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-800">
                                    {rankings.map((r) => (
                                        <tr key={r.id} className="hover:bg-slate-800/30 transition-colors">
                                            <td className="py-4 px-6 text-center">
                                                {getRankBadge(r.rank)}
                                            </td>
                                            <td className="py-4 px-6 font-bold text-white flex items-center gap-2">
                                                {r.participant.name}
                                                {r.participant.tag && (
                                                    <span className="text-xs text-slate-400 font-normal">[{r.participant.tag}]</span>
                                                )}
                                            </td>
                                            <td className="py-4 px-6 text-slate-400">
                                                {r.participant.university || 'Independent'}
                                            </td>
                                            <td className="py-4 px-6 text-center">
                                                <Badge variant="outline" className="capitalize text-xs">
                                                    {r.participant.type}
                                                </Badge>
                                            </td>
                                            <td className="py-4 px-6 text-center font-mono font-semibold text-cyan-400">
                                                {r.rating}
                                            </td>
                                            <td className="py-4 px-6 text-right font-black text-amber-400 text-base">
                                                {r.points.toLocaleString()} pts
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
