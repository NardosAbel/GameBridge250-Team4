import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    UserPlus,
    Users,
    Gamepad2,
    MapPin,
    GraduationCap,
    Clock,
    Monitor,
    Plus,
    Search,
    Shield,
} from 'lucide-react';

interface RecruitmentPostItem {
    id: number;
    uuid: string;
    type: 'lft' | 'lfp';
    title: string;
    description: string;
    rank_tier?: string;
    role?: string;
    location?: string;
    university?: string;
    age_group?: string;
    availability?: string;
    platform?: string;
    status: 'open' | 'closed';
    author: {
        id: number;
        name: string;
        handle?: string;
        avatar_url?: string;
    };
    game?: {
        id: number;
        name: string;
        slug: string;
    };
    team?: {
        id: number;
        name: string;
        tag: string;
    };
    created_at: string;
}

export default function RecruitmentIndex() {
    const { auth } = usePage<any>().props;
    const [posts, setPosts] = useState<RecruitmentPostItem[]>([]);
    const [loading, setLoading] = useState(true);

    // Filter states
    const [typeFilter, setTypeFilter] = useState<'lft' | 'lfp' | ''>('');
    const [platformFilter, setPlatformFilter] = useState('');
    const [universityFilter, setUniversityFilter] = useState('');

    // Modal state
    const [showModal, setShowModal] = useState(false);
    const [formType, setFormType] = useState<'lft' | 'lfp'>('lft');
    const [formTitle, setFormTitle] = useState('');
    const [formDescription, setFormDescription] = useState('');
    const [formGameId, setFormGameId] = useState(1);
    const [formRankTier, setFormRankTier] = useState('');
    const [formRole, setFormRole] = useState('');
    const [formPlatform, setFormPlatform] = useState('PC');
    const [formAvailability, setFormAvailability] = useState('Evenings & Weekends');
    const [submitting, setSubmitting] = useState(false);

    const fetchRecruitments = async () => {
        setLoading(true);
        try {
            let url = '/api/v1/recruitment?';
            if (typeFilter) url += `type=${typeFilter}&`;
            if (platformFilter) url += `platform=${platformFilter}&`;
            if (universityFilter) url += `university=${encodeURIComponent(universityFilter)}&`;

            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.data) {
                setPosts(data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchRecruitments();
    }, [typeFilter, platformFilter]);

    const handleCreateNotice = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/recruitment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({
                    type: formType,
                    title: formTitle,
                    description: formDescription,
                    game_id: formGameId,
                    rank_tier: formRankTier,
                    role: formRole,
                    platform: formPlatform,
                    availability: formAvailability,
                }),
            });
            if (res.ok) {
                setShowModal(false);
                setFormTitle('');
                setFormDescription('');
                fetchRecruitments();
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Recruitment Board - Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
                    <div>
                        <h1 className="text-2xl font-black text-white flex items-center gap-2">
                            <UserPlus className="w-6 h-6 text-pink-500" />
                            Campus Esports Recruitment Board
                        </h1>
                        <p className="text-sm text-slate-400">
                            Find your next collegiate team (LFT) or scout talented players for your campus roster (LFP).
                        </p>
                    </div>

                    {auth.user && (
                        <Button
                            variant="primary"
                            onClick={() => setShowModal(true)}
                            className="flex items-center gap-1.5 self-start sm:self-auto"
                        >
                            <Plus className="w-4 h-4" /> Post Notice
                        </Button>
                    )}
                </div>

                {/* Filter bar */}
                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center bg-slate-900 border border-slate-800 rounded-lg p-1">
                        <button
                            onClick={() => setTypeFilter('')}
                            className={`px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                                typeFilter === '' ? 'bg-pink-600 text-white' : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            All Notices
                        </button>
                        <button
                            onClick={() => setTypeFilter('lft')}
                            className={`px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                                typeFilter === 'lft' ? 'bg-pink-600 text-white' : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            Looking For Team (LFT)
                        </button>
                        <button
                            onClick={() => setTypeFilter('lfp')}
                            className={`px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                                typeFilter === 'lfp' ? 'bg-pink-600 text-white' : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            Looking For Players (LFP)
                        </button>
                    </div>

                    <select
                        value={platformFilter}
                        onChange={(e) => setPlatformFilter(e.target.value)}
                        className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-300 focus:outline-none"
                    >
                        <option value="">All Platforms</option>
                        <option value="PC">PC</option>
                        <option value="Mobile">Mobile</option>
                        <option value="PlayStation">PlayStation</option>
                        <option value="Xbox">Xbox</option>
                    </select>

                    <input
                        type="text"
                        value={universityFilter}
                        onChange={(e) => setUniversityFilter(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && fetchRecruitments()}
                        placeholder="Filter by University..."
                        className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none"
                    />
                </div>

                {/* Notices List */}
                {loading ? (
                    <div className="py-12 text-center text-slate-500 text-sm">Loading recruitment board...</div>
                ) : posts.length === 0 ? (
                    <div className="py-16 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                        <Users className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-base font-bold text-slate-200">No recruitment notices found</h3>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Post an LFT or LFP listing to find your esports squad.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {posts.map((post) => (
                            <Card key={post.id} className="border border-slate-800 bg-slate-900/70 hover:border-slate-700 transition-all">
                                <CardBody className="p-5 space-y-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-300">
                                                {post.author.name.substring(0, 2).toUpperCase()}
                                            </div>
                                            <div>
                                                <h3 className="font-bold text-sm text-white">{post.title}</h3>
                                                <span className="text-[11px] text-slate-400">
                                                    by {post.author.name} {post.team && `(${post.team.name})`}
                                                </span>
                                            </div>
                                        </div>

                                        <Badge
                                            variant={post.type === 'lft' ? 'info' : 'warning'}
                                            className="text-xs uppercase font-mono"
                                        >
                                            {post.type === 'lft' ? 'Looking For Team' : 'Recruiting Players'}
                                        </Badge>
                                    </div>

                                    <p className="text-xs text-slate-300 leading-relaxed">
                                        {post.description}
                                    </p>

                                    {/* Parameters Tags */}
                                    <div className="flex flex-wrap gap-2 text-[11px] text-slate-400 pt-2 border-t border-slate-800/60">
                                        {post.game && (
                                            <span className="flex items-center gap-1 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                                <Gamepad2 className="w-3 h-3 text-emerald-400" />
                                                {post.game.name}
                                            </span>
                                        )}
                                        {post.rank_tier && (
                                            <span className="flex items-center gap-1 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                                <Shield className="w-3 h-3 text-amber-400" />
                                                {post.rank_tier}
                                            </span>
                                        )}
                                        {post.role && (
                                            <span className="bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                                Role: {post.role}
                                            </span>
                                        )}
                                        {post.platform && (
                                            <span className="flex items-center gap-1 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                                <Monitor className="w-3 h-3 text-cyan-400" />
                                                {post.platform}
                                            </span>
                                        )}
                                        {post.university && (
                                            <span className="flex items-center gap-1 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                                <GraduationCap className="w-3 h-3 text-pink-400" />
                                                {post.university}
                                            </span>
                                        )}
                                        {post.availability && (
                                            <span className="flex items-center gap-1 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                                <Clock className="w-3 h-3 text-slate-500" />
                                                {post.availability}
                                            </span>
                                        )}
                                    </div>
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Create Notice Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <UserPlus className="w-5 h-5 text-pink-500" /> Post Recruitment Notice
                            </h3>
                            <button onClick={() => setShowModal(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>

                        <form onSubmit={handleCreateNotice} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Notice Type</label>
                                <select
                                    value={formType}
                                    onChange={(e) => setFormType(e.target.value as any)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                >
                                    <option value="lft">Looking for Team (Player looking to join a squad)</option>
                                    <option value="lfp">Looking for Players (Team captain scouting for roster)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Headline</label>
                                <input
                                    type="text"
                                    required
                                    value={formTitle}
                                    onChange={(e) => setFormTitle(e.target.value)}
                                    placeholder="e.g. Immortal Midlaner LFT for AAU Varsity"
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Preferred Role</label>
                                    <input
                                        type="text"
                                        value={formRole}
                                        onChange={(e) => setFormRole(e.target.value)}
                                        placeholder="e.g. Entry Fragger / Support"
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Rank Tier</label>
                                    <input
                                        type="text"
                                        value={formRankTier}
                                        onChange={(e) => setFormRankTier(e.target.value)}
                                        placeholder="e.g. Diamond / Divine"
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Platform</label>
                                    <select
                                        value={formPlatform}
                                        onChange={(e) => setFormPlatform(e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                    >
                                        <option value="PC">PC</option>
                                        <option value="Mobile">Mobile</option>
                                        <option value="PlayStation">PlayStation</option>
                                        <option value="Xbox">Xbox</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Availability</label>
                                    <input
                                        type="text"
                                        value={formAvailability}
                                        onChange={(e) => setFormAvailability(e.target.value)}
                                        placeholder="e.g. Daily 6PM-10PM"
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">Description & Experience</label>
                                <textarea
                                    required
                                    value={formDescription}
                                    onChange={(e) => setFormDescription(e.target.value)}
                                    rows={3}
                                    placeholder="Detail your competitive history, hero pool, communication habits, and goals..."
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white resize-none"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => setShowModal(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" size="sm" disabled={submitting || !formTitle || !formDescription}>
                                    {submitting ? 'Publishing...' : 'Publish Notice'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
