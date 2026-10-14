import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    Globe,
    Users,
    Plus,
    Search,
    Lock,
    ShieldCheck,
    CheckCircle,
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
                        <h1 className="text-2xl font-black text-white flex items-center gap-2">
                            <Globe className="w-6 h-6 text-cyan-400" />
                            Campus & Game Hubs
                        </h1>
                        <p className="text-sm text-slate-400">
                            Join campus gaming clubs, game guilds, city LAN groups, and creator fanbases.
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
                            className="w-full bg-slate-900 border border-slate-800 rounded-lg pl-9 pr-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
                        />
                    </div>

                    <select
                        value={typeFilter}
                        onChange={(e) => setTypeFilter(e.target.value)}
                        className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-cyan-500"
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
                    <div className="py-16 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                        <Globe className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-base font-bold text-slate-200">No communities found</h3>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Start the first gaming club for your university or favorite esports title!
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {communities.map((c) => (
                            <Card key={c.id} className="border border-slate-800 bg-slate-900/70 hover:border-slate-700 transition-all flex flex-col justify-between">
                                <CardBody className="p-5 space-y-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="w-12 h-12 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-500 flex items-center justify-center font-bold text-white text-base">
                                            {c.name.substring(0, 2).toUpperCase()}
                                        </div>
                                        <div className="flex items-center gap-1.5">
                                            <Badge variant="outline" className="text-[10px] capitalize">
                                                {c.type}
                                            </Badge>
                                            {c.visibility !== 'public' && (
                                                <span title={c.visibility}>
                                                    <Lock className="w-3.5 h-3.5 text-slate-400" />
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <Link href={`/communities/${c.id}`} className="block group">
                                            <h3 className="font-bold text-base text-white group-hover:text-cyan-400 transition-colors">
                                                {c.name}
                                            </h3>
                                        </Link>
                                        <p className="text-xs text-slate-400 mt-1.5 line-clamp-2">
                                            {c.description || 'No description provided.'}
                                        </p>
                                    </div>

                                    <div className="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                                        <span className="flex items-center gap-1 font-semibold text-slate-300">
                                            <Users className="w-3.5 h-3.5 text-cyan-400" />
                                            {c.members_count} members
                                        </span>

                                        {c.is_member ? (
                                            <span className="text-emerald-400 font-semibold flex items-center gap-1">
                                                <CheckCircle className="w-3.5 h-3.5" /> Joined
                                            </span>
                                        ) : c.membership_status === 'pending' ? (
                                            <span className="text-amber-400 text-xs">Pending Approval</span>
                                        ) : (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleJoin(c.id)}
                                                className="text-xs py-1 px-3"
                                            >
                                                {c.visibility === 'public' ? 'Join Hub' : 'Request'}
                                            </Button>
                                        )}
                                    </div>
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Globe className="w-5 h-5 text-cyan-400" /> Start a Campus Hub
                            </h3>
                            <button onClick={() => setShowCreateModal(false)} className="text-slate-400 hover:text-white">✕</button>
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
