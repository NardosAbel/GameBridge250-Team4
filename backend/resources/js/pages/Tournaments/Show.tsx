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
    const [loadError, setLoadError] = useState<string | null>(null);
    const [participants, setParticipants] = useState<any[] | null>(null);
    const [participantsError, setParticipantsError] = useState<string | null>(null);

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
        setLoading(true);
        setLoadError(null);
        try {
            const res = await fetch(`/api/v1/tournaments/${tournamentId}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                setTournament(null);
                setLoadError(data.message || 'This competition could not be loaded.');
                return;
            }
            setTournament(data.data.tournament);
            setBrackets(data.data.brackets || []);
            setStandings(data.data.standings || []);
        } catch {
            setTournament(null);
            setLoadError('This competition could not be loaded.');
        } finally {
            setLoading(false);
        }
    };

    const fetchParticipants = async () => {
        setParticipantsError(null);
        try {
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/participants`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                setParticipantsError(data.message || 'Participants could not be loaded.');
                return;
            }
            const rows = Array.isArray(data.data) ? data.data : data.data?.data;
            setParticipants(Array.isArray(rows) ? rows : []);
        } catch {
            setParticipantsError('Participants could not be loaded.');
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
        setParticipants(null);
    }, [tournamentId]);

    useEffect(() => {
        if (activeTab === 'participants' && participants === null) {
            fetchParticipants();
        }
    }, [activeTab, tournamentId]);

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
            setParticipants(null);
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

    if (loading) {
        return (
            <AppLayout>
                <div className="max-w-6xl mx-auto px-4 py-16 text-sm text-slate-300" role="status">
                    Loading this competition.
                </div>
            </AppLayout>
        );
    }

    if (loadError || !tournament) {
        return (
            <AppLayout>
                <div className="max-w-6xl mx-auto px-4 py-16">
                    <h1 className="text-xl font-semibold text-white">Competition unavailable</h1>
                    <p className="mt-2 text-sm text-slate-300">{loadError || 'This competition could not be loaded.'}</p>
                    <Button className="mt-4" variant="outline" onClick={fetchTournament}>Try again</Button>
                </div>
            </AppLayout>
        );
    }

    const isOrganizer = auth?.user && (
        tournament.organizer?.id === auth.user.id ||
        auth.user.roles?.includes('super_admin')
    );
    const isTeamEntry = tournament.entry_type === 'team';
    const registration = tournament.viewer_registration as { status: string } | null;
    const registrationOpen = tournament.status === 'registration_open';
    const isFull = Number(tournament.capacity) > 0 && Number(tournament.participants_count) >= Number(tournament.capacity);
    const eligibilityIssue = typeof tournament.viewer_eligibility === 'string' && tournament.viewer_eligibility
        ? tournament.viewer_eligibility
        : null;
    const pointAwards = tournament.points_distribution && typeof tournament.points_distribution === 'object'
        ? Object.entries(tournament.points_distribution as Record<string, number>)
        : [];
    const formatName = String(tournament.format || '').replace(/_/g, ' ');
    const statusName = String(tournament.status || '').replace(/_/g, ' ');

    return (
        <AppLayout>
            <Head title={`${tournament.title} — Campus Esports Ethiopia`} />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <header className="border-b border-slate-800 pb-8">
                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200/80">
                        {tournament.game?.name || 'Competition'}
                    </p>
                    <div className="mt-3 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-2xl">
                            <h1 className="text-3xl sm:text-4xl font-semibold tracking-tight text-white">
                                {tournament.title}
                            </h1>
                            <p className="mt-3 text-sm text-slate-300">
                                {formatName}
                                {tournament.match_format ? ` · ${String(tournament.match_format).toUpperCase()}` : ''}
                                {tournament.region ? ` · ${tournament.region}` : ''}
                                {` · ${statusName}`}
                            </p>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            {registration ? (
                                <p className="text-sm text-amber-200" role="status">
                                    You are registered ({registration.status.replace(/_/g, ' ')}).
                                </p>
                            ) : registrationOpen && !auth?.user ? (
                                <Link href="/login">
                                    <Button variant="primary">Sign in to register</Button>
                                </Link>
                            ) : registrationOpen && isFull ? (
                                <p className="text-sm text-slate-300">This competition is full.</p>
                            ) : registrationOpen && eligibilityIssue ? (
                                <p className="text-sm text-slate-300" role="status">{eligibilityIssue}</p>
                            ) : registrationOpen ? (
                                <Button variant="primary" onClick={() => setShowRegisterModal(true)}>
                                    {isTeamEntry ? 'Register team' : 'Register'}
                                </Button>
                            ) : (
                                <p className="text-sm text-slate-300">Registration is closed.</p>
                            )}

                            {registration && ['registration_closed', 'check_in'].includes(tournament.status) && (
                                <Button variant="secondary" onClick={handleTournamentCheckIn}>
                                    Check in
                                </Button>
                            )}

                            {isOrganizer && (
                                <Link href={`/tournaments/${tournament.id}/manage`}>
                                    <Button variant="outline">Manage tournament</Button>
                                </Link>
                            )}

                            {tournament.stream_url && (
                                <a href={tournament.stream_url} target="_blank" rel="noopener noreferrer">
                                    <Button variant="outline">Watch stream</Button>
                                </a>
                            )}
                        </div>
                    </div>

                    <dl className="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-4 text-sm">
                        {tournament.organizer?.name && (
                            <div>
                                <dt className="text-slate-500">Organizer</dt>
                                <dd className="mt-1 text-white">{tournament.organizer.name}</dd>
                            </div>
                        )}
                        <div>
                            <dt className="text-slate-500">Entered</dt>
                            <dd className="mt-1 text-white">{tournament.participants_count} of {tournament.capacity}</dd>
                        </div>
                        {tournament.starts_at && (
                            <div>
                                <dt className="text-slate-500">Starts</dt>
                                <dd className="mt-1 text-white">{new Date(tournament.starts_at).toLocaleString()}</dd>
                            </div>
                        )}
                        {tournament.registration_end_at && (
                            <div>
                                <dt className="text-slate-500">Registration deadline</dt>
                                <dd className="mt-1 text-white">{new Date(tournament.registration_end_at).toLocaleString()}</dd>
                            </div>
                        )}
                        {Number(tournament.prize_pool) > 0 && (
                            <div>
                                <dt className="text-slate-500">Prize pool</dt>
                                <dd className="mt-1 text-white">{Number(tournament.prize_pool).toLocaleString()} {tournament.currency}</dd>
                            </div>
                        )}
                        {tournament.platform && (
                            <div>
                                <dt className="text-slate-500">Platform</dt>
                                <dd className="mt-1 text-white">{tournament.platform}</dd>
                            </div>
                        )}
                    </dl>
                    {tournament.rules && (
                        <p className="mt-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-slate-300">{tournament.rules}</p>
                    )}
                </header>

                <div role="tablist" aria-label="Competition sections" className="flex gap-1 overflow-x-auto border-b border-slate-800 text-sm">
                    {([
                        ['bracket', 'Bracket'],
                        ...( ['round_robin', 'swiss', 'group_knockout'].includes(tournament.format) ? [['standings', 'Standings']] : []),
                        ['matches', 'Matches'],
                        ['participants', 'Players'],
                        ['rules', 'Rules'],
                    ] as Array<[typeof activeTab, string]>).map(([id, label]) => (
                        <button
                            key={id}
                            type="button"
                            role="tab"
                            id={`tab-${id}`}
                            aria-selected={activeTab === id}
                            aria-controls={`panel-${id}`}
                            onClick={() => setActiveTab(id)}
                            className={`min-h-11 whitespace-nowrap border-b-2 px-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200 ${
                                activeTab === id ? 'border-amber-200 text-white' : 'border-transparent text-slate-400 hover:text-white'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {/* Tab Content: Bracket */}
                {activeTab === 'bracket' && (
                    <div id="panel-bracket" role="tabpanel" aria-labelledby="tab-bracket" className="space-y-6">
                        {brackets.length === 0 ? (
                            <div className="border border-slate-800 px-5 py-8">
                                <h2 className="text-base font-semibold text-white">No matches yet</h2>
                                <p className="mt-2 max-w-lg text-sm text-slate-400">
                                    The organizer generates the bracket from the players who are actually registered. This page does not show matches or winners until those records exist.
                                </p>
                            </div>
                        ) : (
                            brackets.map((b) => (
                                <Card key={b.id} className="bg-slate-900/40 border-slate-800 overflow-x-auto p-6">
                                    <div className="flex flex-col items-stretch gap-8 md:min-w-[720px] md:flex-row md:gap-12">
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
                                                                <button
                                                                    type="button"
                                                                    key={node.id}
                                                                    disabled={!m}
                                                                    onClick={() => m && openMatchDetails(m.id)}
                                                                    className={`w-full p-3 rounded-xl border text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200 disabled:cursor-default ${
                                                                        m?.status === 'live'
                                                                            ? 'bg-red-500/10 border-red-500/50'
                                                                            : m?.status === 'completed'
                                                                            ? 'bg-slate-950/80 border-slate-800 hover:border-slate-700'
                                                                            : 'bg-slate-950/40 border-slate-800/60 hover:border-amber-200/40'
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
                                                                </button>
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
                    <div id="panel-standings" role="tabpanel" aria-labelledby="tab-standings">
                    {standings.length === 0 ? (
                        <p className="text-sm text-slate-300">No standings have been calculated for this competition.</p>
                    ) : (
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
                    </div>
                )}

                {activeTab === 'matches' && (
                    <div id="panel-matches" role="tabpanel" aria-labelledby="tab-matches" className="space-y-3">
                        {brackets.flatMap(b => b.nodes.map((n: any) => n.match).filter(Boolean)).length === 0 ? (
                            <p className="text-sm text-slate-300">No matches have been scheduled.</p>
                        ) : brackets.flatMap(b => b.nodes.map((n: any) => n.match).filter(Boolean)).map((m: any) => (
                            <button
                                type="button"
                                key={m.id}
                                onClick={() => openMatchDetails(m.id)}
                                className="w-full border border-slate-800 p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-left hover:border-amber-200/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200"
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
                                    <span className="text-xs uppercase tracking-wide text-slate-300">{m.status}</span>
                                </div>
                            </button>
                        ))}
                    </div>
                )}

                {activeTab === 'participants' && (
                    <div id="panel-participants" role="tabpanel" aria-labelledby="tab-participants">
                        {participantsError && <p className="text-sm text-slate-300">{participantsError}</p>}
                        {participants === null && !participantsError && <p className="text-sm text-slate-300" role="status">Loading players.</p>}
                        {participants && participants.length === 0 && <p className="text-sm text-slate-300">Nobody has entered yet.</p>}
                        {participants && participants.length > 0 && (
                            <ul className="divide-y divide-slate-800 border border-slate-800">
                                {participants.map((entry) => (
                                    <li key={entry.id} className="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-white">
                                                {entry.team?.name || entry.user?.gamer_tag || entry.participant_name || 'Player'}
                                            </p>
                                            <p className="text-xs text-slate-400">
                                                {entry.team?.university || entry.user?.university || entry.status}
                                            </p>
                                        </div>
                                        <p className="text-xs text-slate-300">
                                            {entry.seed ? `Seed ${entry.seed}` : 'Unseeded'}
                                            {entry.placement ? ` · Place ${entry.placement}` : ''}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                {activeTab === 'rules' && (
                    <div id="panel-rules" role="tabpanel" aria-labelledby="tab-rules" className="max-w-3xl space-y-6">
                        <div>
                            <h2 className="text-base font-semibold text-white">Rules</h2>
                            <p className="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-300">
                                {tournament.rules || 'The organizer has not published rules.'}
                            </p>
                        </div>
                        {pointAwards.length > 0 && (
                            <div>
                                <h2 className="text-base font-semibold text-white">Points</h2>
                                <dl className="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {pointAwards.map(([place, points]) => (
                                        <div key={place}>
                                            <dt className="text-xs text-slate-500">Place {place}</dt>
                                            <dd className="text-sm text-white">{points}</dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>
                        )}
                    </div>
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
                title={isTeamEntry ? 'Register a team' : 'Register'}
            >
                <form onSubmit={handleRegister} className="space-y-4">
                    {registerError && (
                        <div className="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-center gap-2">
                            <AlertCircle className="w-4 h-4" />
                            {registerError}
                        </div>
                    )}

                    {isTeamEntry && userTeams.length === 0 && (
                        <p className="text-sm text-slate-300">This competition takes a team. Create a team before registering.</p>
                    )}

                    {isTeamEntry && userTeams.length > 0 && (
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1" htmlFor="team-entry">Team</label>
                            <select
                                id="team-entry"
                                required
                                value={selectedTeamId}
                                onChange={(e) => setSelectedTeamId(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
                            >
                                <option value="">Choose a team</option>
                                {userTeams.map(t => (
                                    <option key={t.id} value={t.id}>{t.name} ({t.tag})</option>
                                ))}
                            </select>
                        </div>
                    )}
                    {!isTeamEntry && (
                        <p className="text-sm text-slate-300">
                            This enters {auth?.user?.profile?.gamer_tag || auth?.user?.name} as an individual player.
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
                        <Button type="submit" variant="primary" disabled={registering || (isTeamEntry && !selectedTeamId)}>
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
