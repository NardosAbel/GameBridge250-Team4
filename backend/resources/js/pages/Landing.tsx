import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../layouts/AppLayout';
import { Button } from '../components/ui/Button';
import { ArrowRight } from 'lucide-react';

interface FeaturedTournament {
    id: number;
    title: string;
    status: string;
    format: string;
    region: string;
    capacity: number;
    starts_at: string | null;
    prize_pool: string | number;
    currency: string;
    participants_count: number;
    game?: { name: string } | null;
    organizer?: { name: string } | null;
}

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
    featuredTournaments?: FeaturedTournament[];
}

const statusLabel: Record<string, string> = {
    published: 'Published',
    registration_open: 'Registration open',
    check_in: 'Check-in',
    live: 'Live',
};

const formatLabel: Record<string, string> = {
    single_elimination: 'Single elimination',
    round_robin: 'Round robin',
};

export default function Landing({
    stats = {
        totalPlayers: 0,
        totalTeams: 0,
        activeTournaments: 0,
        partnerUniversities: 0,
    },
    games = [],
    featuredTournaments = [],
}: LandingProps) {
    return (
        <AppLayout>
            <Head title="Campus competitions for Ethiopian universities" />

            <section className="border-b border-slate-800">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16 grid gap-12 lg:grid-cols-[1.1fr_0.9fr] lg:items-end">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">
                            GameBridge · Ethiopia
                        </p>
                        <h1 className="mt-4 text-4xl sm:text-5xl font-semibold tracking-tight text-white leading-[1.05]">
                            Find a campus competition and enter it under your own name.
                        </h1>
                        <p className="mt-5 max-w-xl text-base sm:text-lg text-slate-300 leading-relaxed">
                            GameBridge is the register for university tournaments: who is hosting, who has entered, and which results an organizer has confirmed.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href="/tournaments">
                                <Button size="lg" rightIcon={<ArrowRight className="w-4 h-4" />}>
                                    Browse competitions
                                </Button>
                            </Link>
                            <Link href="/register">
                                <Button variant="outline" size="lg">
                                    Create a player account
                                </Button>
                            </Link>
                        </div>
                        <dl className="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 border-t border-slate-800 pt-6">
                            <div>
                                <dt className="text-xs text-slate-400">Players</dt>
                                <dd className="mt-1 text-2xl font-semibold text-white">{stats.totalPlayers}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-slate-400">Teams</dt>
                                <dd className="mt-1 text-2xl font-semibold text-white">{stats.totalTeams}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-slate-400">Open events</dt>
                                <dd className="mt-1 text-2xl font-semibold text-white">{stats.activeTournaments}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-slate-400">Campuses</dt>
                                <dd className="mt-1 text-2xl font-semibold text-white">{stats.partnerUniversities}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="bg-slate-900 border border-slate-800">
                        <div className="flex items-baseline justify-between px-5 py-4 border-b border-slate-800">
                            <h2 className="text-sm font-semibold text-white">Open now</h2>
                            <Link href="/tournaments" className="text-xs font-medium text-amber-200 hover:text-white">
                                All competitions
                            </Link>
                        </div>
                        {featuredTournaments.length === 0 ? (
                            <div className="px-5 py-8">
                                <p className="text-sm text-slate-300">No competition is open for registration.</p>
                                <p className="mt-2 text-sm text-slate-400">
                                    Organizers publish from the tournament list. Players will see the event here once it is public.
                                </p>
                            </div>
                        ) : (
                            <ul>
                                {featuredTournaments.map((tournament) => (
                                    <li key={tournament.id} className="border-b border-slate-800 last:border-b-0">
                                        <Link href={`/tournaments/${tournament.id}`} className="block px-5 py-4 hover:bg-slate-800/60">
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="text-sm font-semibold text-white">{tournament.title}</p>
                                                    <p className="mt-1 text-xs text-slate-400">
                                                        {tournament.game?.name ?? 'Game not set'}
                                                        {' · '}
                                                        {tournament.region}
                                                        {' · '}
                                                        {formatLabel[tournament.format] ?? tournament.format}
                                                    </p>
                                                </div>
                                                <span className="shrink-0 text-[11px] font-medium uppercase tracking-wide text-amber-200">
                                                    {statusLabel[tournament.status] ?? tournament.status}
                                                </span>
                                            </div>
                                            <p className="mt-3 text-xs text-slate-400">
                                                {tournament.participants_count} of {tournament.capacity} entered
                                                {tournament.starts_at ? ` · starts ${new Date(tournament.starts_at).toLocaleDateString()}` : ''}
                                                {Number(tournament.prize_pool) > 0 ? ` · ${tournament.prize_pool} ${tournament.currency}` : ' · no prize pool'}
                                            </p>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </section>

            <section className="border-b border-slate-800">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid gap-8 md:grid-cols-3">
                    <div>
                        <h2 className="text-base font-semibold text-white">Enter once</h2>
                        <p className="mt-2 text-sm leading-relaxed text-slate-400">
                            A player account registers for an open event. The same person cannot take a second slot.
                        </p>
                    </div>
                    <div>
                        <h2 className="text-base font-semibold text-white">Organizers confirm results</h2>
                        <p className="mt-2 text-sm leading-relaxed text-slate-400">
                            Single elimination and round robin can be published. A match does not advance until an authorized organizer confirms it.
                        </p>
                    </div>
                    <div>
                        <h2 className="text-base font-semibold text-white">Passports show stored results</h2>
                        <p className="mt-2 text-sm leading-relaxed text-slate-400">
                            A public player page lists confirmed competition history. Private profiles stay hidden. Unconfirmed claims are not presented as verified.
                        </p>
                    </div>
                </div>
            </section>

            <section>
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-base font-semibold text-white">Games on the platform</h2>
                            <p className="mt-1 text-sm text-slate-400">Titles saved in the catalog, not a promise that every format is live.</p>
                        </div>
                    </div>
                    {games.length === 0 ? (
                        <p className="mt-6 text-sm text-slate-400">No games have been added yet.</p>
                    ) : (
                        <ul className="mt-6 divide-y divide-slate-800 border-y border-slate-800">
                            {games.map((game) => (
                                <li key={game.name} className="flex items-baseline justify-between gap-4 py-3">
                                    <span className="text-sm text-white">{game.name}</span>
                                    <span className="text-xs text-slate-400">{game.publisher}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </section>
        </AppLayout>
    );
}
