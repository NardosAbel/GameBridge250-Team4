import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import {
    Shield,
    Swords,
    Users,
    Key,
    Flag,
    AlertTriangle,
    SlidersHorizontal,
    UserCheck,
    Lock,
    Save,
    RotateCcw
} from 'lucide-react';

interface Props {
    tournamentId: number;
}

export default function TournamentManage({ tournamentId }: Props) {
    const { auth } = usePage().props as any;
    const [tournament, setTournament] = useState<any>(null);
    const [participants, setParticipants] = useState<any[]>([]);
    const [disputes, setDisputes] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<'overview' | 'applications' | 'seeding' | 'rooms' | 'disputes' | 'staff' | 'fraud'>('overview');
    const [reviewingId, setReviewingId] = useState<number | null>(null);
    const [statusUpdating, setStatusUpdating] = useState(false);
    const [actionMsg, setActionMsg] = useState<string | null>(null);

    // Seeding State
    const [seeds, setSeeds] = useState<{ [regId: number]: number }>({});
    const [savingSeeds, setSavingSeeds] = useState(false);

    // Room Configuration Modal
    const [selectedMatch, setSelectedMatch] = useState<any>(null);
    const [roomId, setRoomId] = useState('');
    const [roomPassword, setRoomPassword] = useState('');
    const [roomServer, setRoomServer] = useState('');
    const [roomInstructions, setRoomInstructions] = useState('');

    // Dispute Resolution Modal
    const [selectedDispute, setSelectedDispute] = useState<any>(null);
    const [disputeAction, setDisputeAction] = useState('confirm_result');
    const [resolutionNotes, setResolutionNotes] = useState('');
    const [overrideScore1, setOverrideScore1] = useState('0');
    const [overrideScore2, setOverrideScore2] = useState('0');
    const [overrideWinnerId, setOverrideWinnerId] = useState<number | null>(null);

    // Staff Modal
    const [staffUserId, setStaffUserId] = useState('');
    const [staffRole, setStaffRole] = useState('referee');

    const fetchAll = async () => {
        try {
            const [tRes, pRes, dRes] = await Promise.all([
                fetch(`/api/v1/tournaments/${tournamentId}`),
                fetch(`/api/v1/tournaments/${tournamentId}/participants`),
                fetch(`/api/v1/disputes?tournament_id=${tournamentId}`),
            ]);

            const tData = await tRes.json();
            const pData = await pRes.json();
            const dData = await dRes.json();

            if (tData.success) setTournament(tData.data.tournament);
            if (pData.success) {
                setParticipants(pData.data);
                const initialSeeds: { [id: number]: number } = {};
                pData.data.forEach((p: any, idx: number) => {
                    initialSeeds[p.id] = p.seed || (idx + 1);
                });
                setSeeds(initialSeeds);
            }
            if (dData.success) setDisputes(dData.data);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchAll();
    }, [tournamentId]);

    const handleUpdateStatus = async (newStatus: string) => {
        setStatusUpdating(true);
        setActionMsg(null);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({ status: newStatus }),
            });
            const data = await res.json();
            if (data.success) {
                setActionMsg(`Status updated to ${newStatus.replace('_', ' ')}.`);
                fetchAll();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setStatusUpdating(false);
        }
    };

    const approvedEntrants = participants.filter((participant) => participant.status === 'approved');
    const pendingApplications = participants.filter((participant) => participant.status === 'pending');

    const reviewApplication = async (registrationId: number, decision: 'approved' | 'rejected') => {
        setReviewingId(registrationId);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/registrations/${registrationId}/review`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({ decision }),
            });
            const data = await res.json();
            setActionMsg(data.message || (res.ok ? 'Application updated.' : 'The application could not be updated.'));
            if (res.ok) fetchAll();
        } catch (e) {
            console.error(e);
            setActionMsg('The application could not be updated.');
        } finally {
            setReviewingId(null);
        }
    };

    const handleSaveSeeding = async () => {
        setSavingSeeds(true);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/seeding`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    seeds: Object.fromEntries(
                        approvedEntrants.map((participant) => [participant.id, seeds[participant.id]])
                    ),
                }),
            });
            const data = await res.json();
            if (data.success) {
                setActionMsg('Seeds saved successfully.');
                fetchAll();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setSavingSeeds(false);
        }
    };

    const handleGenerateBracket = async () => {
        if (!confirm('Are you sure you want to generate the bracket and launch the tournament?')) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/bracket`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
            });
            const data = await res.json();
            if (data.success) {
                alert('Tournament bracket generated! Tournament is now live.');
                fetchAll();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleSaveRoom = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMatch) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/matches/${selectedMatch.id}/room`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    room_password: roomPassword,
                    room_server: roomServer,
                    room_instructions: roomInstructions,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setSelectedMatch(null);
                setActionMsg('Room credentials updated.');
                fetchAll();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleResolveDispute = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedDispute) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/disputes/${selectedDispute.id}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    action_taken: disputeAction,
                    resolution_notes: resolutionNotes,
                    participant1_score: Number(overrideScore1),
                    participant2_score: Number(overrideScore2),
                    winner_id: overrideWinnerId,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setSelectedDispute(null);
                setActionMsg('Dispute resolved. Bracket progression unblocked.');
                fetchAll();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleAssignStaff = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/v1/tournaments/${tournamentId}/staff`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: JSON.stringify({
                    user_id: Number(staffUserId),
                    role: staffRole,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setStaffUserId('');
                setActionMsg('Staff role assigned.');
                fetchAll();
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
                    Loading organizer controls...
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`Manage: ${tournament.title} — Campus Esports Ethiopia`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header */}
                <div className="flex flex-col gap-4 border-b border-slate-800 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm capitalize text-slate-400">{String(tournament.status).replace(/_/g, ' ')}</p>
                        <h1 className="mt-1 font-display text-4xl text-white">{tournament.title}</h1>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href={`/tournaments/${tournament.id}`}>
                            <Button variant="outline" size="sm">
                                View Public Page
                            </Button>
                        </Link>
                        <Button
                            variant="primary"
                            size="sm"
                            onClick={handleGenerateBracket}
                            className="flex items-center gap-1.5"
                        >
                            <Swords className="w-4 h-4" />
                            Generate Brackets
                        </Button>
                    </div>
                </div>

                {actionMsg && (
                    <p className="text-sm text-lime-300" role="status">{actionMsg}</p>
                )}

                {/* Tabs */}
                <div className="border-b border-slate-800 flex items-center gap-4 text-sm font-semibold overflow-x-auto">
                    <button
                        onClick={() => setActiveTab('overview')}
                        className={`pb-3 px-1 border-b-2 transition-colors ${
                            activeTab === 'overview' ? 'border-lime-300 text-lime-300' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Lifecycle & Status
                    </button>
                    <button
                        onClick={() => setActiveTab('applications')}
                        className={`pb-3 px-1 border-b-2 transition-colors ${
                            activeTab === 'applications' ? 'border-lime-300 text-lime-300' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Applications ({pendingApplications.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('seeding')}
                        className={`pb-3 px-1 border-b-2 transition-colors ${
                            activeTab === 'seeding' ? 'border-lime-300 text-lime-300' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Seeding ({approvedEntrants.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('disputes')}
                        className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-1.5 ${
                            activeTab === 'disputes' ? 'border-lime-300 text-lime-300' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Disputes
                        {disputes.filter(d => d.status === 'open').length > 0 && (
                            <Badge variant="danger" className="text-[10px] px-1.5 py-0">
                                {disputes.filter(d => d.status === 'open').length}
                            </Badge>
                        )}
                    </button>
                    <button
                        onClick={() => setActiveTab('staff')}
                        className={`pb-3 px-1 border-b-2 transition-colors ${
                            activeTab === 'staff' ? 'border-lime-300 text-lime-300' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Officials & Staff
                    </button>
                </div>

                {/* Tab: Overview / Status Controls */}
                {activeTab === 'overview' && (
                    <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_16rem]">
                        <section className="space-y-4">
                            <h2 className="font-display text-xl text-white">Status</h2>
                            <p className="text-sm text-slate-400">
                                Current stage: {String(tournament.status).replace(/_/g, ' ')}.
                            </p>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={statusUpdating}
                                    onClick={() => handleUpdateStatus('published')}
                                >
                                    Publish
                                </Button>
                                <Button
                                    variant="primary"
                                    size="sm"
                                    disabled={statusUpdating}
                                    onClick={() => handleUpdateStatus('registration_open')}
                                >
                                    Open Registration
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={statusUpdating}
                                    onClick={() => handleUpdateStatus('registration_closed')}
                                >
                                    Close Registration
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={statusUpdating}
                                    onClick={() => handleUpdateStatus('check_in')}
                                >
                                    Start Check-in
                                </Button>
                                <Button
                                    variant="danger"
                                    size="sm"
                                    disabled={statusUpdating}
                                    onClick={() => handleUpdateStatus('live')}
                                >
                                    Go Live
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={statusUpdating}
                                    onClick={() => handleUpdateStatus('completed')}
                                >
                                    End Tournament
                                </Button>
                            </div>
                        </section>
                        <dl className="space-y-3 border-t border-slate-800 pt-4 text-sm lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                            <div>
                                <dt className="text-slate-500">Format</dt>
                                <dd className="text-white">{String(tournament.format).replace(/_/g, ' ')}</dd>
                            </div>
                            <div>
                                <dt className="text-slate-500">Match</dt>
                                <dd className="text-white">{tournament.match_format.toUpperCase()}</dd>
                            </div>
                            <div>
                                <dt className="text-slate-500">Entered</dt>
                                <dd className="text-white">{approvedEntrants.length} approved of {tournament.capacity}</dd>
                            </div>
                            <div>
                                <dt className="text-slate-500">Open disputes</dt>
                                <dd className="text-white">{disputes.filter(d => d.status === 'open').length}</dd>
                            </div>
                        </dl>
                    </div>
                )}

                {activeTab === 'applications' && (
                    <section className="space-y-8">
                        {(['pending', 'approved', 'rejected'] as const).map((status) => {
                            const rows = participants.filter((participant) => participant.status === status);
                            const label = status === 'pending' ? 'Pending requests' : status === 'approved' ? 'Approved entrants' : 'Rejected requests';
                            return (
                                <div key={status}>
                                    <h3 className="font-display text-xl text-white">{label}</h3>
                                    {rows.length === 0 ? (
                                        <p className="mt-3 text-sm text-slate-500">None.</p>
                                    ) : (
                                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                                            {rows.map((participant) => (
                                                <li key={participant.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                                    <div>
                                                        <p className="text-sm text-white">{participant.participant_name}</p>
                                                        <p className="text-xs text-slate-400">
                                                            {participant.user?.university || participant.team?.university || 'No campus listed'}
                                                            {participant.created_at ? ` · ${new Date(participant.created_at).toLocaleString()}` : ''}
                                                        </p>
                                                    </div>
                                                    {status === 'pending' && (
                                                        <div className="flex gap-2">
                                                            <Button size="sm" variant="primary" disabled={reviewingId === participant.id} onClick={() => reviewApplication(participant.id, 'approved')}>
                                                                Approve
                                                            </Button>
                                                            <Button size="sm" variant="outline" disabled={reviewingId === participant.id} onClick={() => reviewApplication(participant.id, 'rejected')}>
                                                                Reject
                                                            </Button>
                                                        </div>
                                                    )}
                                                    {status !== 'pending' && (
                                                        <span className={`text-xs ${status === 'approved' ? 'text-lime-300' : 'text-rose-300'}`}>
                                                            {status === 'approved' ? 'Approved' : 'Not approved'}
                                                        </span>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            );
                        })}
                    </section>
                )}

                {/* Tab: Seeding */}
                {activeTab === 'seeding' && (
                    <Card className="bg-slate-900/40 border-slate-800 p-6 space-y-4">
                        <div className="flex justify-between items-center">
                            <div>
                                <h3 className="text-lg font-bold text-white">Participant Seeding</h3>
                                <p className="text-xs text-slate-400">
                                    Assign seed rankings (1 = top seed). Lower seeds will be paired against higher seeds in bracket generation.
                                </p>
                            </div>
                            <Button
                                variant="primary"
                                size="sm"
                                onClick={handleSaveSeeding}
                                disabled={savingSeeds}
                                className="flex items-center gap-1.5"
                            >
                                <Save className="w-4 h-4" />
                                {savingSeeds ? 'Saving...' : 'Save Seeds'}
                            </Button>
                        </div>

                        <div className="divide-y divide-slate-800">
                            {approvedEntrants.map((p) => (
                                <div key={p.id} className="py-3 flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <Badge variant="outline" className="font-mono text-xs">
                                            #{seeds[p.id] || p.seed || '-'}
                                        </Badge>
                                        <div>
                                            <strong className="text-white text-sm">{p.participant_name}</strong>
                                            <span className="text-xs text-slate-400 block">
                                                Registered: {new Date(p.created_at).toLocaleDateString()}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="w-24">
                                        <Input
                                            type="number"
                                            min="1"
                                            max="512"
                                            value={seeds[p.id] ?? ''}
                                            onChange={(e) => setSeeds({ ...seeds, [p.id]: Number(e.target.value) })}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}

                {/* Tab: Disputes */}
                {activeTab === 'disputes' && (
                    <Card className="bg-slate-900/40 border-slate-800 p-6 space-y-4">
                        <h3 className="text-lg font-bold text-white">Match Disputes</h3>

                        {disputes.length === 0 ? (
                            <p className="text-sm text-slate-400 py-6 text-center">No disputes raised for this tournament.</p>
                        ) : (
                            <div className="space-y-3">
                                {disputes.map((d) => (
                                    <div key={d.id} className="flex items-start justify-between gap-4 border-b border-slate-800 py-4">
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <Badge variant={d.status === 'open' ? 'danger' : 'success'}>
                                                    {d.status.toUpperCase()}
                                                </Badge>
                                                <strong className="text-white text-sm">Match #{d.match_id}</strong>
                                            </div>
                                            <p className="text-xs text-slate-300 italic">"{d.reason}"</p>
                                            <span className="text-[11px] text-slate-500 block">Raised by {d.raised_by?.name}</span>
                                        </div>

                                        {d.status === 'open' && (
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                onClick={() => {
                                                    setSelectedDispute(d);
                                                    setResolutionNotes('');
                                                }}
                                            >
                                                Review & Rule
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </Card>
                )}

                {/* Tab: Staff Assignment */}
                {activeTab === 'staff' && (
                    <Card className="bg-slate-900/40 border-slate-800 p-6 space-y-6">
                        <div>
                            <h3 className="text-lg font-bold text-white">Assign Officials & Staff</h3>
                            <p className="text-xs text-slate-400">
                                Appoint verified referees, scorekeepers, casters, and tournament observers.
                            </p>
                        </div>

                        <form onSubmit={handleAssignStaff} className="flex flex-col sm:flex-row gap-3">
                            <Input
                                required
                                placeholder="Enter User ID"
                                value={staffUserId}
                                onChange={(e) => setStaffUserId(e.target.value)}
                                className="flex-1"
                            />
                            <select
                                value={staffRole}
                                onChange={(e) => setStaffRole(e.target.value)}
                                className="min-h-11 border border-slate-700 bg-transparent px-3 text-sm text-white"
                            >
                                <option value="referee">Official Referee</option>
                                <option value="scorekeeper">Scorekeeper</option>
                                <option value="co_organizer">Co-Organizer</option>
                                <option value="caster">Caster / Streamer</option>
                                <option value="observer">Observer</option>
                                <option value="support">Support Staff</option>
                            </select>
                            <Button type="submit" variant="primary">
                                Assign Staff
                            </Button>
                        </form>

                        <div className="pt-2 divide-y divide-slate-800">
                            {tournament.staff?.map((s: any) => (
                                <div key={s.id} className="py-2.5 flex justify-between items-center text-sm">
                                    <span className="text-white font-medium">{s.name || `User #${s.user_id}`}</span>
                                    <Badge variant="outline" className="capitalize">
                                        {s.role.replace('_', ' ')}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}
            </div>

            {/* Dispute Resolution Modal */}
            <Modal
                isOpen={!!selectedDispute}
                onClose={() => setSelectedDispute(null)}
                title={`Resolve Dispute on Match #${selectedDispute?.match_id}`}
            >
                {selectedDispute && (
                    <form onSubmit={handleResolveDispute} className="space-y-4">
                        <div className="border border-slate-800 p-3 text-sm text-slate-300">
                            <strong className="text-white block mb-1">Dispute Reason:</strong>
                            {selectedDispute.reason}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Ruling Decision</label>
                            <select
                                value={disputeAction}
                                onChange={(e) => setDisputeAction(e.target.value)}
                                className="min-h-11 w-full border border-slate-700 bg-transparent px-3 text-sm text-white"
                            >
                                <option value="confirm_result">Confirm Result (Uphold Score)</option>
                                <option value="overturn_result">Overturn Result (Set New Score/Winner)</option>
                                <option value="rematch">Order Rematch (Reset Score & Readiness)</option>
                                <option value="disqualify_participant">Disqualify Offending Participant</option>
                            </select>
                        </div>

                        {disputeAction === 'overturn_result' && (
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="text-xs text-slate-300 block mb-1">Participant 1 Score</label>
                                    <Input
                                        type="number"
                                        value={overrideScore1}
                                        onChange={(e) => setOverrideScore1(e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="text-xs text-slate-300 block mb-1">Participant 2 Score</label>
                                    <Input
                                        type="number"
                                        value={overrideScore2}
                                        onChange={(e) => setOverrideScore2(e.target.value)}
                                    />
                                </div>
                            </div>
                        )}

                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Official Resolution Notes</label>
                            <textarea
                                required
                                rows={3}
                                placeholder="State the ruling rationale..."
                                value={resolutionNotes}
                                onChange={(e) => setResolutionNotes(e.target.value)}
                                className="w-full border border-slate-700 bg-transparent p-3 text-sm text-white"
                            />
                        </div>

                        <div className="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="outline" onClick={() => setSelectedDispute(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary">
                                Execute Ruling
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>
        </AppLayout>
    );
}
