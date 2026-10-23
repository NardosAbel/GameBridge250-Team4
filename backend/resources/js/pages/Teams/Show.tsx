import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';

interface TeamMemberItem {
    id: number;
    user_id: number;
    name: string;
    handle?: string;
    role: string;
    joined_at: string;
}

interface PendingRequest {
    id: number;
    message?: string | null;
    user?: { id: number; name: string; gamer_tag?: string | null };
}

interface TeamDetail {
    id: number;
    name: string;
    tag: string;
    bio?: string;
    university?: string;
    location?: string;
    recruitment_status: string;
    game?: { name: string };
    leader?: { id: number; name: string };
    members: TeamMemberItem[];
    roster_history: Array<{ id: number; action: string; role: string; user?: { name: string }; created_at: string }>;
    viewer_request?: { id: number; status: string } | null;
    pending_requests?: PendingRequest[];
}

export default function TeamShow({ teamId }: { teamId: number }) {
    const { auth } = usePage<any>().props;
    const [team, setTeam] = useState<TeamDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [message, setMessage] = useState('');
    const [applying, setApplying] = useState(false);
    const [feedback, setFeedback] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const fetchTeam = async () => {
        setLoading(true);
        setLoadError(null);
        try {
            const res = await fetch(`/api/v1/teams/${teamId}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.data) {
                setTeam(null);
                setLoadError(data.message || 'This team could not be loaded.');
                return;
            }
            setTeam(data.data);
        } catch {
            setLoadError('This team could not be loaded.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTeam();
    }, [teamId]);

    const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content || '';

    const handleApply = async (event: React.FormEvent) => {
        event.preventDefault();
        if (!auth.user) return;
        setApplying(true);
        setError(null);
        setFeedback(null);
        try {
            const res = await fetch(`/api/v1/teams/${teamId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    user_id: auth.user.id,
                    type: 'join_request',
                    role: 'player',
                    message,
                }),
            });
            const data = await res.json();
            if (!res.ok) {
                setError(data.message || 'The application was not saved.');
                return;
            }
            setFeedback('Application sent. The captain still has to accept it.');
            setMessage('');
            fetchTeam();
        } catch {
            setError('The application was not saved.');
        } finally {
            setApplying(false);
        }
    };

    const review = async (invitationId: number, response: 'accepted' | 'declined') => {
        setError(null);
        setFeedback(null);
        const res = await fetch(`/api/v1/team-invitations/${invitationId}/respond`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ response }),
        });
        const data = await res.json();
        if (!res.ok) {
            setError(data.message || 'That decision was not saved.');
            return;
        }
        setFeedback(response === 'accepted' ? 'Player added to the roster.' : 'Application declined.');
        fetchTeam();
    };

    if (loading) {
        return <AppLayout><p className="max-w-3xl mx-auto px-4 py-16 text-sm text-slate-300" role="status">Loading team.</p></AppLayout>;
    }

    if (loadError || !team) {
        return (
            <AppLayout>
                <div className="max-w-3xl mx-auto px-4 py-16">
                    <h1 className="text-xl font-semibold text-white">Team unavailable</h1>
                    <p className="mt-2 text-sm text-slate-300">{loadError}</p>
                </div>
            </AppLayout>
        );
    }

    const isMember = team.members.some((member) => member.user_id === auth.user?.id);
    const isLeader = team.leader?.id === auth.user?.id;
    const recruiting = team.recruitment_status !== 'closed';
    const pending = team.viewer_request?.status === 'pending';

    return (
        <AppLayout>
            <Head title={`${team.name} — GameBridge`} />
            <div className="max-w-3xl mx-auto px-4 py-8 space-y-8">
                <header className="border-b border-slate-800 pb-6">
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-amber-200/80">{team.tag}</p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight text-white">{team.name}</h1>
                    <p className="mt-2 text-sm text-slate-300">
                        {team.game?.name ? `${team.game.name} · ` : ''}
                        {team.university || 'No campus listed'}
                        {team.location ? ` · ${team.location}` : ''}
                        {` · ${team.recruitment_status.replace(/_/g, ' ')}`}
                    </p>
                    {team.bio && <p className="mt-4 max-w-2xl text-sm leading-relaxed text-slate-300">{team.bio}</p>}
                </header>

                {feedback && <p className="text-sm text-amber-200" role="status">{feedback}</p>}
                {error && <p className="text-sm text-rose-300" role="alert">{error}</p>}

                {!auth.user && recruiting && (
                    <Link href="/login" className="inline-flex min-h-11 items-center text-sm text-amber-200 underline-offset-4 hover:underline">Sign in to apply</Link>
                )}

                {auth.user && !isMember && !isLeader && recruiting && !pending && (
                    <form onSubmit={handleApply} className="space-y-3 border-b border-slate-800 pb-6">
                        <label htmlFor="apply-note" className="block text-sm text-slate-200">Note for the captain</label>
                        <textarea
                            id="apply-note"
                            value={message}
                            onChange={(event) => setMessage(event.target.value)}
                            maxLength={500}
                            rows={3}
                            className="w-full border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                        <Button type="submit" variant="primary" disabled={applying}>
                            {applying ? 'Sending…' : 'Apply to join'}
                        </Button>
                    </form>
                )}

                {pending && <p className="text-sm text-slate-200" role="status">Your application is pending.</p>}
                {isMember && <p className="text-sm text-slate-200">You are on this roster.</p>}
                {!recruiting && !isMember && <p className="text-sm text-slate-300">This roster is closed.</p>}

                {(team.pending_requests?.length ?? 0) > 0 && (
                    <section>
                        <h2 className="text-base font-semibold text-white">Applications</h2>
                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                            {team.pending_requests?.map((request) => (
                                <li key={request.id} className="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-white">{request.user?.gamer_tag || request.user?.name}</p>
                                        {request.message && <p className="text-sm text-slate-400">{request.message}</p>}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button type="button" variant="primary" onClick={() => review(request.id, 'accepted')}>Accept</Button>
                                        <Button type="button" variant="outline" onClick={() => review(request.id, 'declined')}>Decline</Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section>
                    <h2 className="text-base font-semibold text-white">Roster</h2>
                    {team.members.length === 0 ? (
                        <p className="mt-2 text-sm text-slate-400">No active players are listed.</p>
                    ) : (
                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                            {team.members.map((member) => (
                                <li key={member.id} className="flex items-baseline justify-between gap-3 py-3 text-sm">
                                    <span className="text-white">{member.handle || member.name}</span>
                                    <span className="capitalize text-slate-400">{member.role}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {team.roster_history.length > 0 && (
                    <section>
                        <h2 className="text-base font-semibold text-white">Roster history</h2>
                        <ul className="mt-3 space-y-2 text-sm text-slate-300">
                            {team.roster_history.map((entry) => (
                                <li key={entry.id}>{entry.user?.name} {entry.action} as {entry.role}</li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
