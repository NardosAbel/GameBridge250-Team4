import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import {
    Trophy,
    Calendar,
    Users,
    Gamepad2,
    Shield,
    CheckCircle2,
    AlertCircle,
    Tv,
    Lock,
    Key,
    Clock,
    FileText,
    ArrowRight,
    Swords,
    Share2,
    Flag,
    Award
} from 'lucide-react';

interface Props {
    tournamentId: number;
}

export default function TournamentShow({ tournamentId }: Props) {
    const { auth } = usePage().props as any;
    const [tournament, setTournament] = useState<any>(null);
    const [brackets, setBrackets] = useState<any[]>([]);
    const [standings, setStandings] = useState<any[]>([]);
    const [activeTab, setActiveTab] = useState<'bracket' | 'standings' | 'matches' | 'participants' | 'rules'>('bracket');
    const [loading, setLoading] = useState(true);

    // Selected Match Modal
    const [selectedMatch, setSelectedMatch] = useState<any>(null);
    const [matchLoading, setMatchLoading] = useState(false);
    const [score1, setScore1] = useState('0');
    const [score2, setScore2] = useState('0');
    const [notes, setNotes] = useState('');
    const [evidenceUrl, setEvidenceUrl] = useState('');
    const [submittingResult, setSubmittingResult] = useState(false);
    const [actionMessage, setActionMessage] = useState<string | null>(null);

    // Registration Modal
    const [showRegisterModal, setShowRegisterModal] = useState(false);
    const [userTeams, setUserTeams] = useState<any[]>([]);
    const [selectedTeamId, setSelectedTeamId] = useState<string>('');
    const [inviteCode, setInviteCode] = useState('');
    const [registering, setRegistering] = useState(false);
    const [registerError, setRegisterError] = useState<string | null>(null);

    // Dispute Modal
    const [showDisputeModal, setShowDisputeModal] = useState(false);
    const [disputeReason, setDisputeReason] = useState('');

    const fetchTournament = async () => {
        try {
            const res = await fetch(`/api/v1/tournaments/${tournamentId}`);
            const data = await res.json();
            if (data.success) {
                setTournament(data.data.tournament);
                setBrackets(data.data.brackets || []);
                setStandings(data.data.standings || []);
            }
        } catch (e) {
            console.error('Failed to load tournament', e);
        } finally {
            setLoading(false);
        }
    };

    const fetchUserTeams = async () => {
        if (!auth?.user) return;
        try {
            const res = await fetch('/api/v1/teams');
            const data = await res.json();
            if (data.data) {
                setUserTeams(data.data);
            }
        } catch (e) {
            console.error(e);
        }
    };

    useEffect(() => {
        fetchTournament();
        fetchUserTeams();
    }, [tournamentId]);

    const handleRegister = async (e: React.FormEvent) => {
        e.preventDefault();
        setRegistering(true);
        setRegisterError(null);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    team_id: selectedTeamId ? Number(selectedTeamId) : null,
                    invitation_code: inviteCode || null,
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Registration failed.');
            }

            setShowRegisterModal(false);
            fetchTournament();
        } catch (err: any) {
            setRegisterError(err.message);
        } finally {
            setRegistering(false);
        }
    };

    const handleTournamentCheckIn = async () => {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/check-in`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
            });
            const data = await res.json();
            if (data.success) {
                alert('Checked in successfully!');
                fetchTournament();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const openMatchDetails = async (matchId: number) => {
        setMatchLoading(true);
        setSelectedMatch(null);
        setActionMessage(null);
        try {
            const res = await fetch(`/api/v1/matches/${matchId}`);
            const data = await res.json();
            if (data.success) {
                setSelectedMatch(data.data);
                setScore1(String(data.data.participant1_score || 0));
                setScore2(String(data.data.participant2_score || 0));
            }
        } catch (e) {
            console.error(e);
        } finally {
            setMatchLoading(false);
        }
    };

    const handleMatchCheckIn = async () => {
        if (!selectedMatch) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/matches/${selectedMatch.id}/check-in`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
            });
            const data = await res.json();
            if (data.success) {
                setSelectedMatch(data.data);
                setActionMessage('Ready-up confirmed! Waiting for match start.');
                fetchTournament();
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleSubmitResult = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMatch) return;
        setSubmittingResult(true);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/matches/${selectedMatch.id}/result`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    participant1_score: Number(score1),
                    participant2_score: Number(score2),
                    notes,
                    evidence: evidenceUrl ? [{ type: 'screenshot', url: evidenceUrl, description: 'Scoreboard screenshot' }] : [],
                }),
            });

            const data = await res.json();
            if (data.success) {
                setActionMessage('Result submitted! Awaiting opponent confirmation.');
                openMatchDetails(selectedMatch.id);
                fetchTournament();
            } else {
                alert(data.message);
            }
        } catch (err: any) {
            alert(err.message);
        } finally {
            setSubmittingResult(false);
        }
    };

    const handleConfirmResult = async () => {
        if (!selectedMatch) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/matches/${selectedMatch.id}/confirm`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
            });
            const data = await res.json();
            if (data.success) {
                setActionMessage('Result confirmed! Winner has been advanced in the bracket.');
                openMatchDetails(selectedMatch.id);
                fetchTournament();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleOpenDispute = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMatch) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/matches/${selectedMatch.id}/disputes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    reason: disputeReason,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setShowDisputeModal(false);
                setActionMessage('Dispute opened! Official referee will inspect the match.');
                openMatchDetails(selectedMatch.id);
                fetchTournament();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    };

    if (loading || !tournament) {
        return (
            <AppLayout>
                <div className="max-w-7xl mx-auto px-4 py-16 text-center text-slate-400">
                    Loading tournament details...
                </div>
            </AppLayout>
        );
    }

    const isOrganizer = auth?.user && (
        tournament.organizer?.id === auth.user.id ||
        auth.user.roles?.includes('super-admin')
    );

    return (
        <AppLayout>
            <Head title={`${tournament.title} — Campus Esports Ethiopia`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Tournament Header Banner */}
                <div className="relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-800 p-6 sm:p-10 shadow-2xl">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div className="space-y-3 max-w-2xl">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" className="bg-emerald-500/10 text-emerald-400 border-emerald-500/20 font-bold">
                                    {tournament.game?.name}
                                </Badge>
                                <Badge variant="info">
                                    {tournament.format.replace('_', ' ').toUpperCase()}
                                </Badge>
                                <Badge variant="warning">
                                    {tournament.match_format.toUpperCase()}
                                </Badge>
                                <Badge variant={tournament.status === 'live' ? 'danger' : 'neutral'} className="capitalize">
                                    {tournament.status.replace('_', ' ')}
                                </Badge>
                            </div>

                            <h1 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
                                {tournament.title}
                            </h1>

                            <div className="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-400">
                                <span className="flex items-center gap-1.5">
                                    <Trophy className="w-4 h-4 text-amber-400" />
                                    Prize: <strong className="text-white">{tournament.prize_pool > 0 ? `${tournament.prize_pool.toLocaleString()} ${tournament.currency}` : 'Certificate'}</strong>
                                </span>
                                <span>•</span>
                                <span className="flex items-center gap-1.5">
                                    <Users className="w-4 h-4 text-cyan-400" />
                                    {tournament.participants_count} / {tournament.capacity} registered
                                </span>
                                <span>•</span>
                                <span className="flex items-center gap-1.5">
                                    <Calendar className="w-4 h-4 text-indigo-400" />
                                    Starts: {new Date(tournament.starts_at).toLocaleString()}
                                </span>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
                            {tournament.status === 'registration_open' && auth?.user && (
                                <Button variant="primary" onClick={() => setShowRegisterModal(true)} className="flex-1 md:flex-none">
                                    Register Team
                                </Button>
                            )}

                            {['registration_closed', 'check_in'].includes(tournament.status) && (
                                <Button variant="secondary" onClick={handleTournamentCheckIn} className="flex-1 md:flex-none">
                                    Check-In
                                </Button>
                            )}

                            {isOrganizer && (
                                <Link href={`/tournaments/${tournament.id}/manage`}>
                                    <Button variant="outline" className="flex items-center gap-2">
                                        <Shield className="w-4 h-4 text-emerald-400" />
                                        Organizer Dashboard
                                    </Button>
                                </Link>
                            )}

                            {tournament.stream_url && (
                                <a href={tournament.stream_url} target="_blank" rel="noopener noreferrer">
                                    <Button variant="danger" className="flex items-center gap-2">
                                        <Tv className="w-4 h-4" />
                                        Watch Stream
                                    </Button>
                                </a>
                            )}
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="border-b border-slate-800 flex items-center gap-4 overflow-x-auto text-sm font-semibold">
                    <button
                        onClick={() => setActiveTab('bracket')}
                        className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap ${
                            activeTab === 'bracket' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        <Swords className="w-4 h-4" />
                        Bracket
                    </button>
                    {['round_robin', 'swiss', 'group_knockout'].includes(tournament.format) && (
                        <button
                            onClick={() => setActiveTab('standings')}
                            className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap ${
                                activeTab === 'standings' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                            }`}
                        >
                            <Trophy className="w-4 h-4" />
                            Standings
                        </button>
                    )}
                    <button
                        onClick={() => setActiveTab('matches')}
                        className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap ${
                            activeTab === 'matches' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        <Clock className="w-4 h-4" />
                        Match Schedule
                    </button>
                    <button
                        onClick={() => setActiveTab('participants')}
                        className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap ${
                            activeTab === 'participants' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        <Users className="w-4 h-4" />
                        Participants
                    </button>
                    <button
                        onClick={() => setActiveTab('rules')}
                        className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap ${
                            activeTab === 'rules' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        <FileText className="w-4 h-4" />
                        Rules & Info
                    </button>
                </div>

                {/* Tab Content: Bracket */}
                {activeTab === 'bracket' && (
                    <div className="space-y-6">
                        {brackets.length === 0 ? (
                            <div className="text-center py-16 bg-slate-900/30 rounded-2xl border border-dashed border-slate-800">
                                <Swords className="w-12 h-12 text-slate-600 mx-auto mb-3" />
                                <h3 className="text-lg font-bold text-white">Bracket Not Generated Yet</h3>
                                <p className="text-slate-400 text-sm max-w-sm mx-auto mt-1">
                                    The tournament bracket will be automatically published once registration closes and seeds are finalized.
                                </p>
                            </div>
                        ) : (
                            brackets.map((b) => (
                                <Card key={b.id} className="bg-slate-900/40 border-slate-800 overflow-x-auto p-6">
                                    <div className="min-w-[800px] flex items-start gap-12">
                                        {/* Group nodes by round */}
                                        {Array.from(new Set(b.nodes.map((n: any) => n.round))).sort().map((roundNum: any) => {
                                            const roundNodes = b.nodes.filter((n: any) => n.round === roundNum);
                                            return (
                                                <div key={roundNum} className="flex-1 space-y-4">
                                                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 text-center pb-2 border-b border-slate-800">
                                                        {roundNum === 100 ? 'Grand Final' : roundNum === 99 ? 'Lower Bracket Final' : `Round ${roundNum}`}
                                                    </h4>
                                                    <div className="space-y-6">
                                                        {roundNodes.map((node: any) => {
                                                            const m = node.match;
                                                            return (
                                                                <div
                                                                    key={node.id}
                                                                    onClick={() => m && openMatchDetails(m.id)}
                                                                    className={`p-3 rounded-xl border transition-all cursor-pointer ${
                                                                        m?.status === 'live'
                                                                            ? 'bg-red-500/10 border-red-500/50 shadow-lg shadow-red-500/10'
                                                                            : m?.status === 'completed'
                                                                            ? 'bg-slate-950/80 border-slate-800 hover:border-slate-700'
                                                                            : 'bg-slate-950/40 border-slate-800/60 hover:border-emerald-500/40'
                                                                    }`}
                                                                >
                                                                    <div className="space-y-1.5 text-xs">
                                                                        {/* Participant 1 */}
                                                                        <div className={`flex items-center justify-between p-1.5 rounded-lg ${
                                                                            m?.winner_id && m.winner_id === m.participant1_id
                                                                                ? 'bg-emerald-500/20 text-emerald-300 font-bold'
                                                                                : 'text-slate-300'
                                                                        }`}>
                                                                            <span className="truncate max-w-[120px]">{m?.participant1_name || 'TBD'}</span>
                                                                            <span className="font-mono">{m?.participant1_score ?? '-'}</span>
                                                                        </div>

                                                                        {/* Participant 2 */}
                                                                        <div className={`flex items-center justify-between p-1.5 rounded-lg ${
                                                                            m?.winner_id && m.winner_id === m.participant2_id
                                                                                ? 'bg-emerald-500/20 text-emerald-300 font-bold'
                                                                                : 'text-slate-300'
                                                                        }`}>
                                                                            <span className="truncate max-w-[120px]">{m?.participant2_name || (node.is_bye ? 'BYE' : 'TBD')}</span>
                                                                            <span className="font-mono">{m?.participant2_score ?? '-'}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </Card>
                            ))
                        )}
                    </div>
                )}

                {/* Tab Content: Standings */}
                {activeTab === 'standings' && (
                    <Card className="bg-slate-900/40 border-slate-800 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-300">
                                <thead className="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th className="py-3 px-4">Rank</th>
                                        <th className="py-3 px-4">Participant</th>
                                        <th className="py-3 px-4 text-center">MP</th>
                                        <th className="py-3 px-4 text-center">W</th>
                                        <th className="py-3 px-4 text-center">L</th>
                                        <th className="py-3 px-4 text-center">D</th>
                                        <th className="py-3 px-4 text-center">Diff</th>
                                        <th className="py-3 px-4 text-right">Points</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-800">
                                    {standings.map((s: any) => (
                                        <tr key={s.participant_id} className="hover:bg-slate-800/30">
                                            <td className="py-3 px-4 font-bold text-white">#{s.rank}</td>
                                            <td className="py-3 px-4 font-semibold text-emerald-400">{s.participant_name}</td>
                                            <td className="py-3 px-4 text-center">{s.matches_played}</td>
                                            <td className="py-3 px-4 text-center text-emerald-400 font-bold">{s.wins}</td>
                                            <td className="py-3 px-4 text-center text-red-400">{s.losses}</td>
                                            <td className="py-3 px-4 text-center">{s.draws}</td>
                                            <td className="py-3 px-4 text-center font-mono">{s.round_difference > 0 ? `+${s.round_difference}` : s.round_difference}</td>
                                            <td className="py-3 px-4 text-right font-black text-white">{s.points}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Tab Content: Match Schedule */}
                {activeTab === 'matches' && (
                    <div className="space-y-4">
                        {brackets.flatMap(b => b.nodes.map((n: any) => n.match).filter(Boolean)).map((m: any) => (
                            <Card
                                key={m.id}
                                onClick={() => openMatchDetails(m.id)}
                                className="bg-slate-900/40 border-slate-800 hover:border-emerald-500/40 p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 cursor-pointer transition-all"
                            >
                                <div className="flex items-center gap-4">
                                    <Badge variant="outline" className="font-mono text-xs">
                                        Round {m.round}
                                    </Badge>
                                    <div className="font-semibold text-white">
                                        <span className={m.winner_id === m.participant1_id ? 'text-emerald-400 font-bold' : ''}>
                                            {m.participant1_name}
                                        </span>
                                        <span className="mx-2 text-slate-500">vs</span>
                                        <span className={m.winner_id === m.participant2_id ? 'text-emerald-400 font-bold' : ''}>
                                            {m.participant2_name}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-3">
                                    <span className="font-mono text-sm bg-slate-950 px-3 py-1 rounded-lg border border-slate-800">
                                        {m.participant1_score} - {m.participant2_score}
                                    </span>
                                    <Badge variant={m.status === 'live' ? 'danger' : m.status === 'completed' ? 'success' : 'info'}>
                                        {m.status.toUpperCase()}
                                    </Badge>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Tab Content: Rules & Info */}
                {activeTab === 'rules' && (
                    <Card className="bg-slate-900/40 border-slate-800 p-6 space-y-6">
                        <div className="space-y-2">
                            <h3 className="text-lg font-bold text-white">Tournament Rules</h3>
                            <div className="text-slate-300 text-sm whitespace-pre-line leading-relaxed bg-slate-950 p-4 rounded-xl border border-slate-800">
                                {tournament.rules || 'Standard fair play rules apply.'}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <h3 className="text-lg font-bold text-white">Points Distribution</h3>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-center">
                                    <span className="text-xs text-amber-400 font-bold block">1st Place</span>
                                    <span className="text-lg font-black text-white">{tournament.points_distribution?.[1] || 100} pts</span>
                                </div>
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-center">
                                    <span className="text-xs text-slate-300 font-bold block">2nd Place</span>
                                    <span className="text-lg font-black text-white">{tournament.points_distribution?.[2] || 60} pts</span>
                                </div>
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-center">
                                    <span className="text-xs text-slate-400 font-bold block">3rd Place</span>
                                    <span className="text-lg font-black text-white">{tournament.points_distribution?.[3] || 40} pts</span>
                                </div>
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-center">
                                    <span className="text-xs text-slate-500 font-bold block">4th Place</span>
                                    <span className="text-lg font-black text-white">{tournament.points_distribution?.[4] || 20} pts</span>
                                </div>
                            </div>
                        </div>
                    </Card>
                )}
            </div>

            {/* Match Room & Result Submission Modal */}
            <Modal
                isOpen={!!selectedMatch}
                onClose={() => setSelectedMatch(null)}
                title={`Match Details: ${selectedMatch?.participant1_name || 'TBD'} vs ${selectedMatch?.participant2_name || 'TBD'}`}
            >
                {selectedMatch && (
                    <div className="space-y-5">
                        {actionMessage && (
                            <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm flex items-center gap-2">
                                <CheckCircle2 className="w-4 h-4" />
                                {actionMessage}
                            </div>
                        )}

                        {/* Match Room Credentials (Restricted) */}
                        {selectedMatch.room ? (
                            <div className="p-4 bg-emerald-950/20 border border-emerald-500/30 rounded-2xl space-y-2">
                                <div className="flex items-center gap-2 text-emerald-400 font-bold text-xs uppercase tracking-wider">
                                    <Key className="w-4 h-4" />
                                    Restricted Game Room Credentials
                                </div>
                                <div className="grid grid-cols-2 gap-2 text-sm text-slate-300">
                                    <div>Room ID: <strong className="text-white font-mono">{selectedMatch.room.room_id}</strong></div>
                                    <div>Password: <strong className="text-white font-mono">{selectedMatch.room.room_password || 'None'}</strong></div>
                                    <div>Server: <strong className="text-white">{selectedMatch.room.room_server || 'Default'}</strong></div>
                                </div>
                                {selectedMatch.room.room_instructions && (
                                    <p className="text-xs text-slate-400 pt-1 italic">{selectedMatch.room.room_instructions}</p>
                                )}
                            </div>
                        ) : (
                            <div className="p-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-400 text-xs flex items-center gap-2">
                                <Lock className="w-4 h-4" />
                                Game room credentials are restricted to players and officials.
                            </div>
                        )}

                        {/* Ready-up status */}
                        <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800 text-xs">
                            <span>Ready Status:</span>
                            <div className="flex gap-2">
                                <Badge variant={selectedMatch.participant1_ready ? 'success' : 'neutral'}>
                                    {selectedMatch.participant1_name}: {selectedMatch.participant1_ready ? 'Ready' : 'Not Ready'}
                                </Badge>
                                <Badge variant={selectedMatch.participant2_ready ? 'success' : 'neutral'}>
                                    {selectedMatch.participant2_name}: {selectedMatch.participant2_ready ? 'Ready' : 'Not Ready'}
                                </Badge>
                            </div>
                        </div>

                        {/* Ready Up Button */}
                        {selectedMatch.status === 'ready' && (
                            <Button variant="primary" onClick={handleMatchCheckIn} className="w-full">
                                Confirm Ready Up for Match
                            </Button>
                        )}

                        {/* Result Submission & Opponent Confirmation */}
                        {selectedMatch.status !== 'completed' && (
                            <form onSubmit={handleSubmitResult} className="space-y-4 pt-3 border-t border-slate-800">
                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Submit Match Score</h4>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="text-xs text-slate-300 block mb-1">{selectedMatch.participant1_name} Score</label>
                                        <Input
                                            type="number"
                                            min="0"
                                            value={score1}
                                            onChange={(e) => setScore1(e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs text-slate-300 block mb-1">{selectedMatch.participant2_name} Score</label>
                                        <Input
                                            type="number"
                                            min="0"
                                            value={score2}
                                            onChange={(e) => setScore2(e.target.value)}
                                        />
                                    </div>
                                </div>

                                <Input
                                    placeholder="Evidence Screenshot URL (optional)"
                                    value={evidenceUrl}
                                    onChange={(e) => setEvidenceUrl(e.target.value)}
                                />

                                <div className="flex gap-3">
                                    <Button type="submit" variant="primary" disabled={submittingResult} className="flex-1">
                                        {submittingResult ? 'Submitting...' : 'Submit Score'}
                                    </Button>

                                    {selectedMatch.latest_result?.status === 'submitted' && (
                                        <Button type="button" variant="secondary" onClick={handleConfirmResult} className="flex-1">
                                            Confirm Opponent Score
                                        </Button>
                                    )}
                                </div>
                            </form>
                        )}

                        {/* Dispute Option */}
                        <div className="pt-2 border-t border-slate-800 flex justify-end">
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={() => setShowDisputeModal(true)}
                                className="flex items-center gap-1.5"
                            >
                                <Flag className="w-3.5 h-3.5" />
                                Dispute Match
                            </Button>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Registration Modal */}
            <Modal
                isOpen={showRegisterModal}
                onClose={() => setShowRegisterModal(false)}
                title="Register for Tournament"
            >
                <form onSubmit={handleRegister} className="space-y-4">
                    {registerError && (
                        <div className="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-center gap-2">
                            <AlertCircle className="w-4 h-4" />
                            {registerError}
                        </div>
                    )}

                    {userTeams.length > 0 ? (
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Select Team to Register</label>
                            <select
                                value={selectedTeamId}
                                onChange={(e) => setSelectedTeamId(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
                            >
                                <option value="">Register as Individual Gamer</option>
                                {userTeams.map(t => (
                                    <option key={t.id} value={t.id}>{t.name} ({t.tag})</option>
                                ))}
                            </select>
                        </div>
                    ) : (
                        <p className="text-xs text-slate-400">
                            Registering as individual athlete <strong>{auth?.user?.name}</strong>.
                        </p>
                    )}

                    {tournament.entry_type === 'invitation' && (
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Invitation Code</label>
                            <Input
                                required
                                placeholder="Enter invitation code"
                                value={inviteCode}
                                onChange={(e) => setInviteCode(e.target.value)}
                            />
                        </div>
                    )}

                    <div className="pt-3 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setShowRegisterModal(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" disabled={registering}>
                            {registering ? 'Registering...' : 'Confirm Registration'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Dispute Modal */}
            <Modal
                isOpen={showDisputeModal}
                onClose={() => setShowDisputeModal(false)}
                title="Open Match Dispute"
            >
                <form onSubmit={handleOpenDispute} className="space-y-4">
                    <p className="text-xs text-slate-400 leading-relaxed">
                        Opening a dispute will freeze bracket progression until a tournament referee or organizer reviews the evidence and makes an official ruling.
                    </p>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Reason for Dispute</label>
                        <textarea
                            required
                            rows={3}
                            placeholder="Explain the issue (e.g. opponent disconnected, false score submitted, unapproved roster substitution)..."
                            value={disputeReason}
                            onChange={(e) => setDisputeReason(e.target.value)}
                            className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm"
                        />
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setShowDisputeModal(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="danger">
                            Submit Dispute
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
