import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    Users,
    Shield,
    Plus,
    Search,
    MapPin,
    GraduationCap,
    CheckCircle,
    Gamepad2,
} from 'lucide-react';

interface TeamItem {
    id: number;
    uuid: string;
    name: string;
    tag: string;
    slug: string;
    bio?: string;
    logo_url?: string;
    university?: string;
    location?: string;
    recruitment_status: 'open' | 'closed' | 'by_application';
    achievements: string[];
    is_active: boolean;
    leader?: {
        id: number;
        name: string;
        handle?: string;
    };
    game?: {
        id: number;
        name: string;
        slug: string;
    };
    members_count: number;
}

export default function TeamsIndex() {
    const { auth } = usePage<any>().props;
    const [teams, setTeams] = useState<TeamItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [recruitmentFilter, setRecruitmentFilter] = useState('');

    // Modal state for creating team
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [formName, setFormName] = useState('');
    const [formTag, setFormTag] = useState('');
    const [formBio, setFormBio] = useState('');
    const [formUniversity, setFormUniversity] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const fetchTeams = async () => {
        setLoading(true);
        try {
            let url = `/api/v1/teams?search=${encodeURIComponent(search)}`;
            if (recruitmentFilter) url += `&recruitment_status=${recruitmentFilter}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.data) {
                setTeams(data.data);
            }
        } catch (err) {
            console.error('Failed to load teams', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTeams();
    }, [recruitmentFilter]);

    const handleCreateTeam = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/teams', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({
                    name: formName,
                    tag: formTag,
                    bio: formBio,
                    university: formUniversity,
                    recruitment_status: 'open',
                }),
            });
            if (res.ok) {
                setShowCreateModal(false);
                setFormName('');
                setFormTag('');
                setFormBio('');
                setFormUniversity('');
                fetchTeams();
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Teams - Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
                    <div>
                        <h1 className="text-2xl font-black text-white flex items-center gap-2">
                            <Users className="w-6 h-6 text-emerald-400" />
                            Campus Esports Teams & Rosters
                        </h1>
                        <p className="text-sm text-slate-400">
                            Discover Ethiopian university teams, recruit players, and prepare for official tournaments.
                        </p>
                    </div>

                    {auth.user && (
                        <Button
                            variant="primary"
                            onClick={() => setShowCreateModal(true)}
                            className="flex items-center gap-1.5 self-start sm:self-auto"
                        >
                            <Plus className="w-4 h-4" /> Form a Team
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
                            onKeyDown={(e) => e.key === 'Enter' && fetchTeams()}
                            placeholder="Search teams by name, clan tag, or university..."
                            className="w-full bg-slate-900 border border-slate-800 rounded-lg pl-9 pr-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <select
                        value={recruitmentFilter}
                        onChange={(e) => setRecruitmentFilter(e.target.value)}
                        className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-emerald-500"
                    >
                        <option value="">All Recruitment Statuses</option>
                        <option value="open">Open Recruitment</option>
                        <option value="by_application">By Application</option>
                        <option value="closed">Roster Full</option>
                    </select>

                    <Button variant="outline" size="sm" onClick={fetchTeams}>
                        Filter
                    </Button>
                </div>

                {/* Team Grid */}
                {loading ? (
                    <div className="py-12 text-center text-slate-500 text-sm">Loading teams...</div>
                ) : teams.length === 0 ? (
                    <div className="py-16 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                        <Shield className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-base font-bold text-slate-200">No teams found</h3>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Be the first campus captain to form an esports team and represent your university!
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {teams.map((team) => (
                            <Link key={team.id} href={`/teams/${team.id}`} className="group block">
                                <Card className="border border-slate-800 bg-slate-900/70 hover:border-slate-700 hover:bg-slate-900 transition-all h-full">
                                    <CardBody className="p-5 flex flex-col justify-between h-full space-y-4">
                                        <div>
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-12 h-12 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-slate-950 text-base shadow-md">
                                                        {team.tag}
                                                    </div>
                                                    <div>
                                                        <h3 className="font-bold text-base text-white group-hover:text-emerald-400 transition-colors">
                                                            {team.name}
                                                        </h3>
                                                        <span className="text-xs font-semibold text-slate-400">
                                                            [{team.tag}]
                                                        </span>
                                                    </div>
                                                </div>

                                                <Badge
                                                    variant={team.recruitment_status === 'open' ? 'success' : 'outline'}
                                                    className="text-[10px] capitalize"
                                                >
                                                    {team.recruitment_status.replace('_', ' ')}
                                                </Badge>
                                            </div>

                                            {team.bio && (
                                                <p className="text-xs text-slate-400 mt-3 line-clamp-2">
                                                    {team.bio}
                                                </p>
                                            )}
                                        </div>

                                        <div className="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                                            <div className="flex items-center gap-1.5">
                                                <GraduationCap className="w-4 h-4 text-slate-500" />
                                                <span>{team.university || 'Independent'}</span>
                                            </div>

                                            <div className="flex items-center gap-1 font-semibold text-emerald-400">
                                                <Users className="w-3.5 h-3.5" />
                                                <span>{team.members_count} Players</span>
                                            </div>
                                        </div>
                                    </CardBody>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            {/* Create Team Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Users className="w-5 h-5 text-emerald-400" /> Create Campus Team
                            </h3>
                            <button
                                onClick={() => setShowCreateModal(false)}
                                className="text-slate-400 hover:text-white"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleCreateTeam} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Team Name
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={formName}
                                    onChange={(e) => setFormName(e.target.value)}
                                    placeholder="e.g. AAU Cyber Vipers"
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Clan Tag (2-8 chars)
                                </label>
                                <input
                                    type="text"
                                    required
                                    maxLength={8}
                                    value={formTag}
                                    onChange={(e) => setFormTag(e.target.value.toUpperCase())}
                                    placeholder="e.g. ACV"
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white uppercase"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    University / Campus
                                </label>
                                <input
                                    type="text"
                                    value={formUniversity}
                                    onChange={(e) => setFormUniversity(e.target.value)}
                                    placeholder="e.g. Addis Ababa University (AAU)"
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Team Bio / Mission
                                </label>
                                <textarea
                                    value={formBio}
                                    onChange={(e) => setFormBio(e.target.value)}
                                    rows={3}
                                    placeholder="Brief background, target games, or practice schedule..."
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white resize-none"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowCreateModal(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    variant="primary"
                                    size="sm"
                                    disabled={submitting || !formName || !formTag}
                                >
                                    {submitting ? 'Creating...' : 'Create Team'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
