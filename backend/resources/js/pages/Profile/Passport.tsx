import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Badge } from '../../components/ui/Badge';

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
        <AppLayout>
            <Head title={`${player.gamer_tag} | Player Passport`} />
            <div className="max-w-3xl mx-auto px-4 py-10 space-y-8">
                <header className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-3xl font-semibold tracking-tight">{player.gamer_tag}</h1>
                        {player.student_verified && <Badge variant="success">Verified student</Badge>}
                    </div>
                    <p className="text-sm text-slate-300">{player.name}{player.university ? ` · ${player.university}` : ''}</p>
                    {player.bio && <p className="text-sm text-slate-400">{player.bio}</p>}
                </header>

                <dl className="grid grid-cols-3 gap-4 border-y border-slate-800 py-4 text-sm">
                    <div>
                        <dt className="text-slate-500">Entered</dt>
                        <dd className="mt-1 text-lg text-white">{passport.tournament_count}</dd>
                    </div>
                    <div>
                        <dt className="text-slate-500">Confirmed results</dt>
                        <dd className="mt-1 text-lg text-white">{passport.verified_result_count}</dd>
                    </div>
                    <div>
                        <dt className="text-slate-500">Rating</dt>
                        <dd className="mt-1 text-lg text-white">{passport.rating ?? '—'}</dd>
                    </div>
                </dl>

                <section>
                    <h2 className="text-base font-semibold text-white">Recorded results</h2>
                    {passport.results.length === 0 ? (
                        <p className="mt-2 text-sm text-slate-400">No confirmed placement is on record.</p>
                    ) : (
                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                            {passport.results.map((result) => (
                                <li key={result.tournament_id} className="flex justify-between gap-3 py-3 text-sm">
                                    <Link href={`/tournaments/${result.tournament_id}`} className="text-white underline-offset-4 hover:underline">
                                        {result.title}
                                    </Link>
                                    <span className="text-slate-400">
                                        {result.placement ? `Place ${result.placement}` : 'No place yet'}
                                        {result.points_awarded ? ` · ${result.points_awarded} pts` : ''}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {passport.rankings.length > 0 && (
                    <section>
                        <h2 className="text-base font-semibold text-white">Season rankings</h2>
                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                            {passport.rankings.map((ranking) => (
                                <li key={ranking.game} className="flex justify-between py-3 text-sm">
                                    <span>{ranking.game}</span>
                                    <span className="text-slate-400">{ranking.points} pts{ranking.rank ? ` · rank ${ranking.rank}` : ''}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
