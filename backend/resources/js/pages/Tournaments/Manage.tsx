import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
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
    CheckCircle2,
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
    const [activeTab, setActiveTab] = useState<'overview' | 'seeding' | 'rooms' | 'disputes' | 'staff' | 'fraud'>('overview');
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
                body: JSON.stringify({ seeds }),
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
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight text-white">{tournament.title}</h1>
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
                    <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4" />
                        {actionMsg}
                    </div>
                )}

                {/* Tabs */}
                <div className="border-b border-slate-800 flex items-center gap-4 text-sm font-semibold overflow-x-auto">
                    <button
                        onClick={() => setActiveTab('overview')}
                        className={`pb-3 px-1 border-b-2 transition-colors ${
                            activeTab === 'overview' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Lifecycle & Status
                    </button>
                    <button
                        onClick={() => setActiveTab('seeding')}
                        className={`pb-3 px-1 border-b-2 transition-colors ${
                            activeTab === 'seeding' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Seeding ({participants.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('disputes')}
                        className={`pb-3 px-1 border-b-2 transition-colors flex items-center gap-1.5 ${
                            activeTab === 'disputes' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
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
                            activeTab === 'staff' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-white'
                        }`}
                    >
                        Officials & Staff
                    </button>
                </div>

                {/* Tab: Overview / Status Controls */}
                {activeTab === 'overview' && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Card className="bg-slate-900/40 border-slate-800 p-6 space-y-4">
                            <h3 className="text-lg font-bold text-white">Tournament Lifecycle Control</h3>
                            <p className="text-xs text-slate-400">
                                Advance the tournament stage through its official competitive lifecycle.
                            </p>

                            <div className="grid grid-cols-2 gap-3 pt-2">
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
                        </Card>

                        <Card className="bg-slate-900/40 border-slate-800 p-6 space-y-3 text-xs text-slate-300">
                            <h3 className="text-lg font-bold text-white">Summary Metrics</h3>
                            <div className="grid grid-cols-2 gap-3 pt-2">
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="text-slate-400 block mb-1">Format</span>
                                    <strong className="text-white text-sm">{tournament.format}</strong>
                                </div>
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="text-slate-400 block mb-1">Match Type</span>
                                    <strong className="text-white text-sm">{tournament.match_format.toUpperCase()}</strong>
                                </div>
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="text-slate-400 block mb-1">Registered</span>
                                    <strong className="text-white text-sm">{participants.length} / {tournament.capacity}</strong>
                                </div>
                                <div className="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="text-slate-400 block mb-1">Open Disputes</span>
                                    <strong className="text-white text-sm">{disputes.filter(d => d.status === 'open').length}</strong>
                                </div>
                            </div>
                        </Card>
                    </div>
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
                            {participants.map((p) => (
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
                                    <div key={d.id} className="p-4 bg-slate-950 rounded-xl border border-slate-800 flex justify-between items-start gap-4">
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
                                className="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
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
                        <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-xs text-slate-300">
                            <strong className="text-white block mb-1">Dispute Reason:</strong>
                            {selectedDispute.reason}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Ruling Decision</label>
                            <select
                                value={disputeAction}
                                onChange={(e) => setDisputeAction(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm"
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
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm"
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
