import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import {
    Plus,
    Search,
    Lock,
} from 'lucide-react';

interface CommunityItem {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string;
    type: 'school' | 'game' | 'city' | 'venue' | 'interest' | 'creator';
    visibility: 'public' | 'private' | 'approval';
    avatar_url?: string;
    creator?: { id: number; name: string };
    members_count: number;
    is_member: boolean;
    membership_status?: string | null;
}

export default function CommunitiesIndex() {
    const { auth } = usePage<any>().props;
    const [communities, setCommunities] = useState<CommunityItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [typeFilter, setTypeFilter] = useState('');
    const [search, setSearch] = useState('');

    // Modal
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [type, setType] = useState('interest');
    const [visibility, setVisibility] = useState('public');
    const [submitting, setSubmitting] = useState(false);

    const fetchCommunities = async () => {
        setLoading(true);
        try {
            let url = `/api/v1/communities?search=${encodeURIComponent(search)}`;
            if (typeFilter) url += `&type=${typeFilter}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.data) {
                setCommunities(data.data);
            }
        } catch (err) {
            console.error('Failed to load communities', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCommunities();
    }, [typeFilter]);

    const handleCreateCommunity = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/communities', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({ name, description, type, visibility }),
            });
            if (res.ok) {
                setShowCreateModal(false);
                setName('');
                setDescription('');
                fetchCommunities();
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSubmitting(false);
        }
    };

    const handleJoin = async (id: number) => {
        if (!auth.user) return;
        try {
            const res = await fetch(`/api/v1/communities/${id}/join`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
            });
            if (res.ok) {
                fetchCommunities();
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <AppLayout>
            <Head title="Communities - Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
                    <div>
                        <h1 className="font-display text-4xl text-white">Communities</h1>
                        <p className="mt-2 text-sm text-slate-400">
                            Campus clubs, game groups, and city communities that already exist on GameBridge.
                        </p>
                    </div>

                    {auth.user && (
                        <Button
                            variant="primary"
                            onClick={() => setShowCreateModal(true)}
                            className="flex items-center gap-1.5 self-start sm:self-auto"
                        >
                            <Plus className="w-4 h-4" /> Start a Hub
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <div className="flex flex-col sm:flex-row gap-3">
                    <div className="relative flex-1">
                        <Search className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && fetchCommunities()}
                            placeholder="Search communities by name..."
                            className="min-h-11 w-full border border-slate-700 bg-transparent pl-9 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-lime-300 focus:outline-none"
                        />
                    </div>

                    <select
                        value={typeFilter}
                        onChange={(e) => setTypeFilter(e.target.value)}
                        className="min-h-11 border border-slate-700 bg-transparent px-3 text-sm text-slate-200 focus:border-lime-300 focus:outline-none"
                    >
                        <option value="">All Hub Types</option>
                        <option value="school">Campus / University</option>
                        <option value="game">Game Specific</option>
                        <option value="city">City / Region</option>
                        <option value="venue">Venue / LAN Cafe</option>
                        <option value="interest">Interest / Banter</option>
                        <option value="creator">Creator Fanbase</option>
                    </select>

                    <Button variant="outline" size="sm" onClick={fetchCommunities}>
                        Filter
                    </Button>
                </div>

                {/* Grid */}
                {loading ? (
                    <div className="py-12 text-center text-slate-500 text-sm">Loading communities...</div>
                ) : communities.length === 0 ? (
                    <div className="border-y border-slate-800 py-10">
                        <h2 className="text-base text-white">No communities match this view</h2>
                        <p className="mt-2 max-w-lg text-sm text-slate-400">Nothing in the directory matches these filters.</p>
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-800 border-y border-slate-800">
                        {communities.map((c) => (
                            <li key={c.id} className="grid gap-3 py-5 sm:grid-cols-[8rem_1fr_auto] sm:items-center">
                                <p className="font-display text-sm text-lime-300">
                                    {c.type}
                                    {c.visibility !== 'public' && (
                                        <Lock className="ml-2 inline h-3.5 w-3.5 text-slate-400" aria-label={c.visibility} />
                                    )}
                                </p>
                                <div>
                                    <Link href={`/communities/${c.id}`}>
                                        <h2 className="font-display text-2xl text-white">{c.name}</h2>
                                    </Link>
                                    <p className="mt-1 text-sm text-slate-400">
                                        {c.description || 'No description yet.'} · {c.members_count} members
                                    </p>
                                </div>
                                {c.is_member ? (
                                    <span className="text-sm text-lime-300">Joined</span>
                                ) : c.membership_status === 'pending' ? (
                                    <span className="text-sm text-amber-200">Pending</span>
                                ) : (
                                    <Button variant="outline" size="sm" onClick={() => handleJoin(c.id)}>
                                        {c.visibility === 'public' ? 'Join' : 'Request'}
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {/* Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                    <div className="w-full max-w-md space-y-4 border border-slate-800 bg-slate-950 p-6">
                        <div className="flex items-center justify-between">
                            <h3 className="font-display text-lg text-white">Start a community</h3>
                            <button type="button" onClick={() => setShowCreateModal(false)} className="min-h-11 text-sm text-slate-400 hover:text-white">Close</button>
                        </div>

                        <form onSubmit={handleCreateCommunity} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Hub Name</label>
                                <input
                                    type="text"
                                    required
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="e.g. AAU Dota 2 Club or Hawassa Esports"
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Hub Category</label>
                                <select
                                    value={type}
                                    onChange={(e) => setType(e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                >
                                    <option value="school">Campus / University</option>
                                    <option value="game">Game Specific</option>
                                    <option value="city">City / Region</option>
                                    <option value="venue">Venue / LAN Cafe</option>
                                    <option value="interest">Interest / General</option>
                                    <option value="creator">Creator Fanbase</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Access Privacy</label>
                                <select
                                    value={visibility}
                                    onChange={(e) => setVisibility(e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                >
                                    <option value="public">Public (Anyone can join)</option>
                                    <option value="approval">Approval (Moderator verifies campus ID)</option>
                                    <option value="private">Private (Invite only)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Description</label>
                                <textarea
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    rows={3}
                                    placeholder="What is this community for? Campus rules, meeting spots, etc."
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white resize-none"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => setShowCreateModal(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" size="sm" disabled={submitting || !name}>
                                    {submitting ? 'Creating...' : 'Launch Hub'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
