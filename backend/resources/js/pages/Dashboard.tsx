import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../layouts/AppLayout';
import { Button } from '../components/ui/Button';

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
    const user = auth.user;

    return (
        <AppLayout>
            <Head title="Dashboard — Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-10">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight text-white">
                            {user.profile?.gamer_tag || user.name}
                        </h1>
                        <p className="mt-2 text-sm text-slate-300">
                            {user.profile?.university || 'No campus is saved on this profile.'}
                            {user.profile?.student_verified ? ' · Student record verified' : ''}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href="/profile"><Button variant="outline">Profile</Button></Link>
                        <Link href="/tournaments"><Button variant="primary">Competitions</Button></Link>
                    </div>
                </header>

                <nav aria-label="Your places" className="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                    <Link href="/teams" className="text-slate-200 underline-offset-4 hover:underline">Teams</Link>
                    <Link href="/venues" className="text-slate-200 underline-offset-4 hover:underline">Venues</Link>
                    <Link href="/education" className="text-slate-200 underline-offset-4 hover:underline">Learning</Link>
                    <Link href="/rankings" className="text-slate-200 underline-offset-4 hover:underline">Rankings</Link>
                </nav>

                <section className="space-y-4">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-white">Open competitions</h2>
                        <Link href="/tournaments" className="text-sm text-amber-200 underline-offset-4 hover:underline">
                            All competitions
                        </Link>
                    </div>

                    {recentTournaments.length === 0 ? (
                        <p className="text-sm text-slate-300">No published competitions are listed yet.</p>
                    ) : (
                        <ul className="divide-y divide-slate-800 border-y border-slate-800">
                            {recentTournaments.map((tourney) => (
                                <li key={tourney.id}>
                                    <Link href={`/tournaments/${tourney.id}`} className="flex flex-col gap-1 py-4 sm:flex-row sm:items-baseline sm:justify-between hover:text-amber-200">
                                        <span>
                                            <span className="block text-base text-white">{tourney.title}</span>
                                            <span className="text-sm text-slate-400">
                                                {tourney.game} · {tourney.format.replace(/_/g, ' ')}
                                                {tourney.date ? ` · ${tourney.date}` : ''}
                                            </span>
                                        </span>
                                        <span className="text-sm capitalize text-slate-300">{tourney.status.replace(/_/g, ' ')}</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
