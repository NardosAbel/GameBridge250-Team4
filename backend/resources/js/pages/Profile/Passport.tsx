import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '../../components/ui/Badge';
import { Card } from '../../components/ui/Card';

interface PassportPageProps {
    player: {
        name?: string;
        gamer_tag: string;
        university?: string;
        bio?: string;
        student_verified: boolean;
    };
    passport: {
        tournament_count: number;
        verified_result_count: number;
        rating: number | null;
        rankings: Array<{ game: string; points: number; rank: number | null }>;
        results: Array<{
            tournament_id: number;
            title: string;
            placement: number | null;
            points_awarded: number | null;
        }>;
    };
}

export default function Passport({ player, passport }: PassportPageProps) {
    return (
        <main className="min-h-screen bg-slate-950 text-slate-100">
            <Head title={`${player.gamer_tag} | Player Passport`} />
            <div className="max-w-3xl mx-auto px-4 py-10 space-y-6">
                <Link href="/" className="text-xs font-semibold text-emerald-400 hover:underline">
                    GameBridge
                </Link>
                <header className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-3xl font-extrabold">{player.gamer_tag}</h1>
                        {player.student_verified && <Badge variant="success">Verified student</Badge>}
                    </div>
                    <p className="text-sm text-slate-300">{player.name}{player.university ? ` · ${player.university}` : ''}</p>
                    {player.bio && <p className="text-sm text-slate-400">{player.bio}</p>}
                </header>

                <section className="grid grid-cols-3 gap-3">
                    <Card className="p-4 text-center">
                        <div className="text-2xl font-bold text-emerald-400">{passport.tournament_count}</div>
                        <div className="text-[11px] uppercase text-slate-400">Registrations</div>
                    </Card>
                    <Card className="p-4 text-center">
                        <div className="text-2xl font-bold text-amber-400">{passport.verified_result_count}</div>
                        <div className="text-[11px] uppercase text-slate-400">Verified results</div>
                    </Card>
                    <Card className="p-4 text-center">
                        <div className="text-2xl font-bold">{passport.rating ?? '—'}</div>
                        <div className="text-[11px] uppercase text-slate-400">Rating</div>
                    </Card>
                </section>

                <Card className="p-6">
                    <h2 className="text-sm font-bold uppercase tracking-wider mb-3">Recorded results</h2>
                    {passport.results.length === 0 ? (
                        <p className="text-sm text-slate-400">No approved competition record yet.</p>
                    ) : (
                        <ul className="space-y-2">
                            {passport.results.map((result) => (
                                <li key={result.tournament_id} className="flex justify-between gap-3 text-sm border-b border-slate-800 py-2">
                                    <span>{result.title}</span>
                                    <span className="text-slate-400">
                                        {result.placement ? `Place ${result.placement}` : 'Pending'}
                                        {result.points_awarded ? ` · ${result.points_awarded} pts` : ''}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                {passport.rankings.length > 0 && (
                    <Card className="p-6">
                        <h2 className="text-sm font-bold uppercase tracking-wider mb-3">Season rankings</h2>
                        <ul className="space-y-2">
                            {passport.rankings.map((ranking) => (
                                <li key={ranking.game} className="flex justify-between text-sm">
                                    <span>{ranking.game}</span>
                                    <span className="text-slate-400">{ranking.points} pts{ranking.rank ? ` · rank ${ranking.rank}` : ''}</span>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}
            </div>
        </main>
    );
}
