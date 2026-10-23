import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';

interface Channel {
    id: number;
    name: string;
    community_id?: number;
}

interface Message {
    id: number;
    message: string;
    created_at: string;
    user?: { id: number; name: string };
}

function csrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

export default function CommunityShow({ communityId }: { communityId: number }) {
    const { auth } = usePage<{ auth: { user: { id: number } | null } }>().props;
    const [community, setCommunity] = useState<any>(null);
    const [error, setError] = useState('');
    const [channels, setChannels] = useState<Channel[]>([]);
    const [channelError, setChannelError] = useState('');
    const [active, setActive] = useState<Channel | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [draft, setDraft] = useState('');
    const [channelName, setChannelName] = useState('');
    const [sending, setSending] = useState(false);
    const [joining, setJoining] = useState(false);
    const [showChannels, setShowChannels] = useState(true);

    const loadCommunity = async () => {
        const res = await fetch(`/api/v1/communities/${communityId}`, { headers: { Accept: 'application/json' } });
        const body = await res.json();
        if (!res.ok) {
            setError(body.message || 'This community is not available.');
            return;
        }
        setCommunity(body.data);
    };

    const loadChannels = async () => {
        const res = await fetch(`/api/v1/chat/communities/${communityId}/channels`, { headers: { Accept: 'application/json' } });
        const body = await res.json();
        if (res.status === 403) {
            setChannelError(body.message || 'Join this community to read its channels.');
            setChannels([]);
            return;
        }
        if (!res.ok) {
            setChannelError(body.message || 'Channels could not be loaded.');
            return;
        }
        setChannelError('');
        setChannels(body.data ?? []);
        setActive((current) => current ?? body.data?.[0] ?? null);
    };

    useEffect(() => {
        loadCommunity().catch(() => setError('This community is not available.'));
        if (auth.user) loadChannels().catch(() => setChannelError('Channels could not be loaded.'));
    }, [communityId]);

    useEffect(() => {
        if (!active) return;
        let cancelled = false;
        const loadMessages = async () => {
            const res = await fetch(`/api/v1/chat/channels/${active.id}/messages`, { headers: { Accept: 'application/json' } });
            const body = await res.json();
            if (!cancelled && res.ok) setMessages(body.data ?? []);
        };
        loadMessages();
        const timer = window.setInterval(loadMessages, 8000);
        return () => {
            cancelled = true;
            window.clearInterval(timer);
        };
    }, [active?.id]);

    const join = async () => {
        setJoining(true);
        const res = await fetch(`/api/v1/communities/${communityId}/join`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });
        const body = await res.json();
        setJoining(false);
        if (!res.ok) {
            setError(body.message || 'Could not join.');
            return;
        }
        await loadCommunity();
        await loadChannels();
    };

    const send = async (event: React.FormEvent) => {
        event.preventDefault();
        if (!active || !draft.trim()) return;
        setSending(true);
        const res = await fetch(`/api/v1/chat/channels/${active.id}/messages`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ message: draft.trim() }),
        });
        const body = await res.json();
        setSending(false);
        if (!res.ok) {
            setChannelError(body.message || 'Message was not sent.');
            return;
        }
        setDraft('');
        setMessages((current) => [...current, body.data]);
    };

    const addChannel = async (event: React.FormEvent) => {
        event.preventDefault();
        const res = await fetch(`/api/v1/chat/communities/${communityId}/channels`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ name: channelName.trim() }),
        });
        const body = await res.json();
        if (!res.ok) {
            setChannelError(body.message || 'Channel was not created.');
            return;
        }
        setChannelName('');
        setChannels((current) => current.some((channel) => channel.id === body.data.id) ? current : [...current, body.data]);
        setActive(body.data);
        setShowChannels(false);
    };

    if (!community && !error) {
        return <AppLayout><p className="px-4 py-16 text-sm text-slate-400">Loading community.</p></AppLayout>;
    }

    return (
        <AppLayout>
            <Head title={community?.name ?? 'Community'} />
            <div className="mx-auto max-w-6xl px-4 py-6">
                <Link href="/communities" className="text-sm text-lime-300 hover:underline">Communities</Link>
                {error && <p className="mt-4 text-sm text-rose-300" role="alert">{error}</p>}
                {community && (
                    <>
                        <header className="mt-4 flex flex-wrap items-end justify-between gap-4 border-b border-slate-800 pb-4">
                            <div>
                                <h1 className="font-display text-3xl text-white">{community.name}</h1>
                                <p className="mt-2 max-w-2xl text-sm text-slate-400">{community.description || 'A GameBridge community.'}</p>
                                <p className="mt-2 text-xs text-slate-500">{community.members_count} members · {community.visibility}</p>
                            </div>
                            {auth.user && !community.is_member && (
                                <button type="button" onClick={join} disabled={joining} className="min-h-11 bg-lime-300 px-4 text-sm font-medium text-slate-950">
                                    {joining ? 'Joining…' : community.visibility === 'public' ? 'Join' : 'Request to join'}
                                </button>
                            )}
                        </header>

                        <div className="mt-4 grid min-h-[28rem] md:grid-cols-[14rem_minmax(0,1fr)]">
                            <div className={`${showChannels ? 'block' : 'hidden'} border-slate-800 md:block md:border-r md:pr-4`}>
                                <h2 className="text-sm text-slate-400">Channels</h2>
                                {channelError && <p className="mt-3 text-sm text-slate-400">{channelError}</p>}
                                <ul className="mt-3">
                                    {channels.map((channel) => (
                                        <li key={channel.id}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setActive(channel);
                                                    setShowChannels(false);
                                                }}
                                                className={`block min-h-11 w-full text-left text-sm ${active?.id === channel.id ? 'text-lime-300' : 'text-slate-200'}`}
                                            >
                                                {channel.name}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                                {community.can_manage && (
                                    <form onSubmit={addChannel} className="mt-4 space-y-2">
                                        <label htmlFor="channel-name" className="text-xs text-slate-500">New channel</label>
                                        <input
                                            id="channel-name"
                                            value={channelName}
                                            onChange={(event) => setChannelName(event.target.value)}
                                            maxLength={40}
                                            className="min-h-11 w-full border border-slate-700 bg-transparent px-3 text-sm"
                                        />
                                        <button type="submit" className="min-h-11 text-sm text-lime-300">Add channel</button>
                                    </form>
                                )}
                            </div>
                            <section className={`${showChannels ? 'hidden' : 'flex'} min-h-[24rem] flex-col md:flex`}>
                                <button type="button" className="mb-2 min-h-11 text-left text-sm text-lime-300 md:hidden" onClick={() => setShowChannels(true)}>
                                    Channels
                                </button>
                                <h2 className="font-display text-xl text-white">{active?.name ?? 'Conversation'}</h2>
                                <div className="mt-4 flex-1 space-y-3 overflow-y-auto">
                                    {messages.map((message) => (
                                        <p key={message.id} className="text-sm">
                                            <span className="text-slate-400">{message.user?.name ?? 'Player'}</span>
                                            <span className="text-slate-600"> · {new Date(message.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>
                                            <span className="mt-1 block text-slate-100">{message.message}</span>
                                        </p>
                                    ))}
                                    {active && messages.length === 0 && <p className="text-sm text-slate-500">No messages in this channel yet.</p>}
                                </div>
                                {active && (
                                    <form onSubmit={send} className="mt-4 flex gap-2">
                                        <label htmlFor="message" className="sr-only">Message</label>
                                        <input
                                            id="message"
                                            value={draft}
                                            onChange={(event) => setDraft(event.target.value)}
                                            className="min-h-11 flex-1 border border-slate-700 bg-transparent px-3 text-sm"
                                        />
                                        <button type="submit" disabled={sending} className="min-h-11 bg-lime-300 px-4 text-sm text-slate-950">
                                            {sending ? 'Sending…' : 'Send'}
                                        </button>
                                    </form>
                                )}
                            </section>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
