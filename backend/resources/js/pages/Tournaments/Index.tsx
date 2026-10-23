import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import {
    Search,
    Plus,
    AlertCircle,
} from 'lucide-react';

interface Game {
    id: number;
    name: string;
    slug: string;
}

interface Tournament {
    id: number;
    uuid: string;
    title: string;
    slug: string;
    platform: string;
    region: string;
    format: string;
    match_format: string;
    event_type: string;
    entry_type: string;
    entry_fee: number;
    currency: string;
    prize_pool: number;
    capacity: number;
    status: string;
    participants_count: number;
    starts_at: string;
    game: Game;
    banner_url?: string;
    organizer: {
        id: number;
        name: string;
    };
}

export default function TournamentIndex() {
    const { auth } = usePage().props as any;
    const [tournaments, setTournaments] = useState<Tournament[]>([]);
    const [games, setGames] = useState<Game[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [selectedGame, setSelectedGame] = useState('');
    const [selectedFormat, setSelectedFormat] = useState('');
    const [selectedStatus, setSelectedStatus] = useState('');
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [form, setForm] = useState({
        title: '',
        game_id: '',
        platform: 'Cross-platform',
        region: 'Nationwide',
        format: 'single_elimination',
        match_format: 'bo1',
        event_type: 'online',
        entry_type: 'free',
        entry_fee: '0',
        prize_pool: '0',
        capacity: '16',
        rules: '1. Standard competitive rules apply.\n2. Respect all participants.\n3. Matches must be started within 15 minutes of scheduled time.',
        starts_at: new Date(Date.now() + 86400000 * 3).toISOString().slice(0, 16),
        registration_start_at: new Date().toISOString().slice(0, 16),
        registration_end_at: new Date(Date.now() + 86400000 * 2).toISOString().slice(0, 16),
    });

    const isOrganizer = auth?.user?.roles?.some((r: string) => ['organizer', 'super_admin'].includes(r));

    const fetchTournaments = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (selectedGame) params.append('game_id', selectedGame);
            if (selectedFormat) params.append('format', selectedFormat);
            if (selectedStatus) params.append('status', selectedStatus);

            const res = await fetch(`/api/v1/tournaments?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                const payload = data.data;
                setTournaments(Array.isArray(payload) ? payload : (payload?.data ?? []));
            }
        } catch (e) {
            console.error('Failed to load tournaments', e);
        } finally {
            setLoading(false);
        }
    };

    const fetchGames = async () => {
        try {
            const res = await fetch('/api/v1/games');
            const data = await res.json();
            if (data.data) {
                setGames(data.data);
                if (data.data.length > 0 && !form.game_id) {
                    setForm(prev => ({ ...prev, game_id: String(data.data[0].id) }));
                }
            }
        } catch (e) {
            console.error('Failed to load games', e);
        }
    };

    useEffect(() => {
        fetchTournaments();
        fetchGames();
    }, [search, selectedGame, selectedFormat, selectedStatus]);

    const handleCreate = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setError(null);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/v1/tournaments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    ...form,
                    game_id: Number(form.game_id),
                    entry_fee: Number(form.entry_fee),
                    prize_pool: Number(form.prize_pool),
                    capacity: Number(form.capacity),
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Failed to create tournament.');
            }

            setShowCreateModal(false);
            fetchTournaments();
        } catch (err: any) {
            setError(err.message);
        } finally {
            setSubmitting(false);
        }
    };

    const getStatusVariant = (status: string): "neutral" | "success" | "warning" | "danger" | "info" => {
        switch (status) {
            case 'live': return 'danger';
            case 'registration_open': return 'success';
            case 'check_in': return 'warning';
            case 'completed': return 'neutral';
            default: return 'info';
        }
    };

    const formatLabel = (format: string) => {
        switch (format) {
            case 'single_elimination': return 'Single Elimination';
            case 'double_elimination': return 'Double Elimination';
            case 'round_robin': return 'Round Robin';
            case 'swiss': return 'Swiss System';
            case 'group_knockout': return 'Groups + Knockout';
            default: return format;
        }
    };

    return (
        <AppLayout>
            <Head title="Tournaments — Campus Esports Ethiopia" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Hero Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between border-b border-slate-800 pb-6">
                    <div className="max-w-2xl">
                        <h1 className="text-3xl sm:text-4xl font-semibold tracking-tight text-white">
                            Competitions
                        </h1>
                        <p className="mt-2 text-sm sm:text-base text-slate-300 leading-relaxed">
                            Published events with an open registration window. Drafts stay with the organizer until they are published.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        {isOrganizer && (
                            <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                                <Plus className="w-4 h-4" />
                                Host a tournament
                            </Button>
                        )}
                        <Link href="/rankings">
                            <Button variant="outline">Rankings</Button>
                        </Link>
                    </div>
                </div>

                {/* Filters & Search */}
                <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 sm:p-6 backdrop-blur-md space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {/* Search Input */}
                        <div className="relative">
                            <Search className="w-4 h-4 absolute left-3 top-3.5 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search tournaments..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-9 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
                            />
                        </div>

                        {/* Game Filter */}
                        <select
                            value={selectedGame}
                            onChange={(e) => setSelectedGame(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">All Games</option>
                            {games.map(g => (
                                <option key={g.id} value={g.id}>{g.name}</option>
                            ))}
                        </select>

                        {/* Format Filter */}
                        <select
                            value={selectedFormat}
                            onChange={(e) => setSelectedFormat(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">All Formats</option>
                            <option value="single_elimination">Single Elimination</option>
                            <option value="double_elimination">Double Elimination</option>
                            <option value="round_robin">Round Robin</option>
                            <option value="swiss">Swiss System</option>
                            <option value="group_knockout">Groups + Knockout</option>
                        </select>

                        {/* Status Filter */}
                        <select
                            value={selectedStatus}
                            onChange={(e) => setSelectedStatus(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="registration_open">Registration Open</option>
                            <option value="check_in">Check-in Active</option>
                            <option value="live">Live Now</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                {/* Tournament Grid */}
                {loading ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {[1, 2, 3, 4, 5, 6].map(i => (
                            <div key={i} className="h-72 bg-slate-900/40 rounded-2xl animate-pulse border border-slate-800" />
                        ))}
                    </div>
                ) : tournaments.length === 0 ? (
                    <div className="py-12 border border-slate-800 px-6">
                        <h2 className="text-lg font-semibold text-white">No competitions match this view</h2>
                        <p className="mt-2 text-sm text-slate-400 max-w-lg">
                            {isOrganizer
                                ? 'Nothing is published for these filters. Host a tournament to create the first one. It stays a draft until you publish it.'
                                : 'Organizers have not published a competition that matches these filters.'}
                        </p>
                        {isOrganizer && (
                            <Button className="mt-5" onClick={() => setShowCreateModal(true)}>
                                Host a tournament
                            </Button>
                        )}
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-800 border-y border-slate-800">
                        {tournaments.map((t) => (
                            <li key={t.id}>
                                <Link href={`/tournaments/${t.id}`} className="grid gap-3 py-5 sm:grid-cols-[1fr_auto] sm:items-center hover:bg-slate-900/40 px-1">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="text-base font-semibold text-white">{t.title}</h2>
                                            <Badge variant={getStatusVariant(t.status)}>
                                                {t.status.replace(/_/g, ' ')}
                                            </Badge>
                                        </div>
                                        <p className="mt-1 text-sm text-slate-400">
                                            {t.game?.name || 'Game not set'}
                                            {' · '}
                                            {formatLabel(t.format)}
                                            {' · '}
                                            {t.match_format.toUpperCase()}
                                            {' · '}
                                            {t.region}
                                        </p>
                                        <p className="mt-2 text-xs text-slate-500">
                                            {t.organizer?.name ? `Hosted by ${t.organizer.name}` : 'Organizer not listed'}
                                            {t.starts_at ? ` · ${new Date(t.starts_at).toLocaleDateString()}` : ''}
                                        </p>
                                    </div>
                                    <div className="text-sm text-slate-300 sm:text-right">
                                        <div>{t.participants_count} / {t.capacity} entered</div>
                                        <div className="text-xs text-slate-500 mt-1">
                                            {t.prize_pool > 0 ? `${Number(t.prize_pool).toLocaleString()} ${t.currency}` : 'No prize pool'}
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {/* Host Tournament Modal */}
            <Modal
                isOpen={showCreateModal}
                onClose={() => setShowCreateModal(false)}
                title="Host New Tournament"
            >
                <form onSubmit={handleCreate} className="space-y-4 max-h-[75vh] overflow-y-auto px-1">
                    {error && (
                        <div className="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-center gap-2">
                            <AlertCircle className="w-4 h-4" />
                            {error}
                        </div>
                    )}

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Tournament Title</label>
                        <Input
                            required
                            placeholder="e.g. Addis Ababa Varsity PUBG Mobile Cup"
                            value={form.title}
                            onChange={(e) => setForm({ ...form, title: e.target.value })}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Game Title</label>
                            <select
                                required
                                value={form.game_id}
                                onChange={(e) => setForm({ ...form, game_id: e.target.value })}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
                            >
                                {games.map(g => (
                                    <option key={g.id} value={g.id}>{g.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Platform</label>
                            <select
                                value={form.platform}
                                onChange={(e) => setForm({ ...form, platform: e.target.value })}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
                            >
                                <option value="Mobile">Mobile</option>
                                <option value="PC">PC</option>
                                <option value="Console">Console</option>
                                <option value="Cross-platform">Cross-platform</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Tournament Format</label>
                            <select
                                value={form.format}
                                onChange={(e) => setForm({ ...form, format: e.target.value })}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
                            >
                                <option value="single_elimination">Single Elimination</option>
                                <option value="double_elimination">Double Elimination</option>
                                <option value="round_robin">Round Robin</option>
                                <option value="swiss">Swiss System</option>
                                <option value="group_knockout">Groups + Knockout</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Match Format</label>
                            <select
                                value={form.match_format}
                                onChange={(e) => setForm({ ...form, match_format: e.target.value })}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
                            >
                                <option value="bo1">Best of 1 (BO1)</option>
                                <option value="bo3">Best of 3 (BO3)</option>
                                <option value="bo5">Best of 5 (BO5)</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Max Capacity</label>
                            <Input
                                type="number"
                                required
                                min="2"
                                max="256"
                                value={form.capacity}
                                onChange={(e) => setForm({ ...form, capacity: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Prize Pool (ETB)</label>
                            <Input
                                type="number"
                                min="0"
                                value={form.prize_pool}
                                onChange={(e) => setForm({ ...form, prize_pool: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Entry Fee (ETB)</label>
                            <Input
                                type="number"
                                min="0"
                                value={form.entry_fee}
                                onChange={(e) => setForm({ ...form, entry_fee: e.target.value })}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Tournament Start Date</label>
                        <Input
                            type="datetime-local"
                            required
                            value={form.starts_at}
                            onChange={(e) => setForm({ ...form, starts_at: e.target.value })}
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Rules & Guidelines</label>
                        <textarea
                            rows={3}
                            value={form.rules}
                            onChange={(e) => setForm({ ...form, rules: e.target.value })}
                            className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div className="pt-2 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setShowCreateModal(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" disabled={submitting}>
                            {submitting ? 'Creating...' : 'Create Tournament'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
