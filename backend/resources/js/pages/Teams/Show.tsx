import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    Users,
    Shield,
    MessageSquare,
    UserPlus,
    Trophy,
    GraduationCap,
    MapPin,
    Crown,
    Settings,
    Clock,
    UserMinus,
    ArrowRightLeft,
} from 'lucide-react';

interface TeamMemberItem {
    id: number;
    user_id: number;
    name: string;
    handle?: string;
    avatar_url?: string;
    role: 'owner' | 'captain' | 'coach' | 'manager' | 'player' | 'substitute';
    status: string;
    joined_at: string;
}

interface RosterHistoryItem {
    id: number;
    action: string;
    role: string;
    user: { id: number; name: string };
    created_at: string;
}

interface TeamDetail {
    id: number;
    name: string;
    tag: string;
    bio?: string;
    university?: string;
    location?: string;
    recruitment_status: string;
    achievements: string[];
    is_active: boolean;
    leader?: { id: number; name: string; handle?: string };
    game?: { id: number; name: string; slug: string };
    members: TeamMemberItem[];
    roster_history: RosterHistoryItem[];
}

export default function TeamShow({ teamId }: { teamId: number }) {
    const { auth } = usePage<any>().props;
    const [team, setTeam] = useState<TeamDetail | null>(null);
    const [loading, setLoading] = useState(true);

    // Apply or Invite state
    const [applyMessage, setApplyMessage] = useState('');
    const [applying, setApplying] = useState(false);
    const [actionMsg, setActionMsg] = useState('');

    const fetchTeam = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/teams/${teamId}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.data) {
                setTeam(data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTeam();
    }, [teamId]);

    const handleApply = async () => {
        if (!auth.user) return;
        setApplying(true);
        try {
            const res = await fetch(`/api/v1/teams/${teamId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({
                    user_id: auth.user.id,
                    type: 'join_request',
                    role: 'player',
                    message: applyMessage,
                }),
            });
            if (res.ok) {
                setActionMsg('Join application submitted to team captain!');
                setApplyMessage('');
            }
        } catch (err) {
            console.error(err);
        } finally {
            setApplying(false);
        }
    };

    const isMember = team?.members.some((m) => m.user_id === auth.user?.id);
    const isLeader = team?.leader?.id === auth.user?.id;

    if (loading || !team) {
        return (
            <AppLayout>
                <div className="py-24 text-center text-slate-500">Loading team profile...</div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`${team.name} [${team.tag}] - Campus Esports Ethiopia`} />

            <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
                {/* Team Hero Header */}
                <div className="relative rounded-2xl overflow-hidden border border-slate-800 bg-slate-900/80 p-6 md:p-8 backdrop-blur-sm">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="flex items-center gap-5">
                            <div className="w-20 h-20 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-amber-500 flex items-center justify-center font-black text-slate-950 text-2xl shadow-xl">
                                {team.tag}
                            </div>
                            <div className="space-y-1">
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl md:text-3xl font-black text-white">
                                        {team.name}
                                    </h1>
                                    <Badge variant="outline" className="text-xs uppercase font-mono">
                                        [{team.tag}]
                                    </Badge>
                                </div>
                                <div className="flex flex-wrap items-center gap-3 text-xs text-slate-400">
                                    {team.university && (
                                        <span className="flex items-center gap-1">
                                            <GraduationCap className="w-3.5 h-3.5 text-emerald-400" />
                                            {team.university}
                                        </span>
                                    )}
                                    {team.location && (
                                        <span className="flex items-center gap-1">
                                            <MapPin className="w-3.5 h-3.5 text-slate-400" />
                                            {team.location}
                                        </span>
                                    )}
                                    <span className="capitalize text-emerald-400 font-semibold">
                                        Recruitment: {team.recruitment_status.replace('_', ' ')}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Top CTA */}
                        <div className="flex items-center gap-3">
                            <Link href="/chat">
                                <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                                    <MessageSquare className="w-4 h-4 text-blue-400" /> Team Chat
                                </Button>
                            </Link>

                            {!isMember && auth.user && team.recruitment_status !== 'closed' && (
                                <Button
                                    variant="primary"
                                    size="sm"
                                    onClick={handleApply}
                                    disabled={applying}
                                    className="flex items-center gap-1.5"
                                >
                                    <UserPlus className="w-4 h-4" /> {applying ? 'Submitting...' : 'Apply to Roster'}
                                </Button>
                            )}
                        </div>
                    </div>

                    {actionMsg && (
                        <div className="mt-4 p-3 rounded-lg bg-emerald-950/40 border border-emerald-800 text-xs text-emerald-300">
                            {actionMsg}
                        </div>
                    )}
                </div>

                {/* Team Bio & Achievements */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-2 space-y-6">
                        {/* Roster Card */}
                        <Card className="border border-slate-800 bg-slate-900/70">
                            <CardHeader className="p-4 border-b border-slate-800 flex items-center justify-between">
                                <h3 className="font-bold text-white text-base flex items-center gap-2">
                                    <Users className="w-5 h-5 text-emerald-400" />
                                    Active Roster ({team.members.length})
                                </h3>
                                <Badge variant="outline" className="text-xs">
                                    Roles: Captain, Coach, Manager, Player, Sub
                                </Badge>
                            </CardHeader>
                            <CardBody className="p-4">
                                <div className="divide-y divide-slate-800/80">
                                    {team.members.map((member) => (
                                        <div
                                            key={member.id}
                                            className="py-3 flex items-center justify-between first:pt-0 last:pb-0"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-300">
                                                    {member.name.substring(0, 2).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="text-sm font-bold text-white">
                                                            {member.name}
                                                        </span>
                                                        {member.role === 'owner' && (
                                                            <Crown className="w-3.5 h-3.5 text-amber-400" />
                                                        )}
                                                    </div>
                                                    <span className="text-[11px] text-slate-400">
                                                        Joined {new Date(member.joined_at).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            </div>

                                            <Badge
                                                variant={
                                                    member.role === 'owner' || member.role === 'captain'
                                                        ? 'warning'
                                                        : member.role === 'coach' || member.role === 'manager'
                                                        ? 'info'
                                                        : 'outline'
                                                }
                                                className="text-xs uppercase font-mono"
                                            >
                                                {member.role}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>

                        {/* Roster History Timeline */}
                        <Card className="border border-slate-800 bg-slate-900/70">
                            <CardHeader className="p-4 border-b border-slate-800">
                                <h3 className="font-bold text-white text-base flex items-center gap-2">
                                    <Clock className="w-5 h-5 text-teal-400" />
                                    Roster History & Transfers
                                </h3>
                            </CardHeader>
                            <CardBody className="p-4">
                                {team.roster_history?.length === 0 ? (
                                    <p className="text-xs text-slate-500">No transfer history recorded yet.</p>
                                ) : (
                                    <div className="space-y-3">
                                        {team.roster_history.map((h) => (
                                            <div
                                                key={h.id}
                                                className="flex items-center justify-between text-xs p-2.5 rounded-lg bg-slate-950/60 border border-slate-800/80"
                                            >
                                                <div className="flex items-center gap-2">
                                                    <ArrowRightLeft className="w-3.5 h-3.5 text-emerald-400" />
                                                    <span className="font-semibold text-white">
                                                        {h.user?.name}
                                                    </span>
                                                    <span className="text-slate-400">
                                                        {h.action} as <strong className="text-slate-200">{h.role}</strong>
                                                    </span>
                                                </div>
                                                <span className="text-[10px] text-slate-500">
                                                    {new Date(h.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardBody>
                        </Card>
                    </div>

                    {/* Sidebar: Bio & Achievements */}
                    <div className="space-y-6">
                        <Card className="border border-slate-800 bg-slate-900/70">
                            <CardHeader className="p-4 border-b border-slate-800">
                                <h3 className="font-bold text-white text-sm">About Team</h3>
                            </CardHeader>
                            <CardBody className="p-4 space-y-3 text-xs text-slate-300">
                                <p className="leading-relaxed">
                                    {team.bio || 'No official team biography published yet.'}
                                </p>

                                <div className="pt-3 border-t border-slate-800 space-y-2">
                                    <div className="flex justify-between">
                                        <span className="text-slate-400">Status</span>
                                        <span className="text-emerald-400 font-semibold">Active Roster</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-400">Campus</span>
                                        <span className="text-white">{team.university || 'Independent'}</span>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>

                        <Card className="border border-slate-800 bg-slate-900/70">
                            <CardHeader className="p-4 border-b border-slate-800 flex items-center justify-between">
                                <h3 className="font-bold text-white text-sm flex items-center gap-1.5">
                                    <Trophy className="w-4 h-4 text-amber-400" /> Trophy Room
                                </h3>
                            </CardHeader>
                            <CardBody className="p-4">
                                {team.achievements?.length > 0 ? (
                                    <div className="flex flex-wrap gap-2">
                                        {team.achievements.map((ach, idx) => (
                                            <Badge key={idx} variant="warning" className="text-xs">
                                                🏆 {ach}
                                            </Badge>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-xs text-slate-500">
                                        No tournament trophies claimed yet. Compete in upcoming campus scrims!
                                    </p>
                                )}
                            </CardBody>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
