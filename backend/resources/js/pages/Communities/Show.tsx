import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Avatar } from '../../components/ui/Avatar';

interface Channel {
    id: number;
    name: string;
    description?: string | null;
    community_id?: number;
}

interface Message {
    id: number;
    message: string;
    created_at: string;
    user?: { id: number; name: string; handle?: string | null; avatar_url?: string | null };
}

interface ServerChip {
    id: number;
    name: string;
    avatar_url?: string | null;
}

function csrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

function clock(iso: string): string {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

export default function CommunityShow({ communityId }: { communityId: number }) {
    const { auth } = usePage<{ auth: { user: { id: number } | null } }>().props;
    const [community, setCommunity] = useState<any>(null);
    const [servers, setServers] = useState<ServerChip[]>([]);
    const [error, setError] = useState('');
    const [channels, setChannels] = useState<Channel[]>([]);
    const [channelError, setChannelError] = useState('');
    const [messageError, setMessageError] = useState('');
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
            setActive(null);
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
        fetch('/api/v1/communities', { headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((body) => {
                const rows = Array.isArray(body.data) ? body.data : (body.data?.data ?? []);
                setServers(rows.slice(0, 12));
            })
            .catch(() => setServers([]));
    }, [communityId]);

    useEffect(() => {
        if (!active) return;
        let cancelled = false;
        const loadMessages = async () => {
            const res = await fetch(`/api/v1/chat/channels/${active.id}/messages`, { headers: { Accept: 'application/json' } });
            const body = await res.json();
            if (cancelled) return;
            if (!res.ok) {
                setMessageError(body.message || 'Messages could not be loaded.');
                return;
            }
            setMessageError('');
            setMessages(body.data ?? []);
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
        setMessageError('');
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
            setMessageError(body.message || 'Message was not sent.');
            return;
        }
        setDraft('');
        setMessages((current) => (current.some((message) => message.id === body.data.id) ? current : [...current, body.data]));
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
            <div className="flex min-h-[calc(100dvh-3.5rem)] md:min-h-[calc(100dvh-1px)]">
                <nav aria-label="Communities" className="hidden w-16 shrink-0 flex-col items-center gap-2 border-r border-slate-800 py-3 md:flex">
                    {servers.map((server) => (
                        <Link
                            key={server.id}
                            href={`/communities/${server.id}`}
                            title={server.name}
                            className={`flex h-11 w-11 items-center justify-center border ${server.id === Number(communityId) ? 'border-lime-300' : 'border-transparent'}`}
                        >
                            <Avatar name={server.name} src={server.avatar_url} size="md" />
                        </Link>
                    ))}
                    <Link href="/communities" className="mt-2 text-center text-[10px] text-slate-400 hover:text-white">All</Link>
                </nav>

                {community && (
                    <>
                        <aside className={`${showChannels ? 'flex' : 'hidden'} min-w-0 flex-1 flex-col border-slate-800 md:flex md:w-60 md:flex-none md:border-r`}>
                            <div className="border-b border-slate-800 px-4 py-4">
                                <Link href="/communities" className="text-xs text-lime-300 md:hidden">Communities</Link>
                                <h1 className="font-display text-xl text-white">{community.name}</h1>
                                <p className="mt-1 text-xs text-slate-400">{community.members_count} {Number(community.members_count) === 1 ? 'member' : 'members'} · {community.visibility}</p>
                                {auth.user && !community.is_member && (
                                    <button type="button" onClick={join} disabled={joining} className="mt-3 min-h-11 bg-lime-300 px-3 text-sm font-medium text-slate-950">
                                        {joining ? 'Joining…' : community.visibility === 'public' ? 'Join' : 'Request to join'}
                                    </button>
                                )}
                            </div>
                            <div className="flex-1 overflow-y-auto px-3 py-3">
                                <p className="px-2 text-xs text-slate-500">Text channels</p>
                                {channelError && <p className="mt-3 px-2 text-sm text-slate-400" role="status">{channelError}</p>}
                                <ul className="mt-2">
                                    {channels.map((channel) => (
                                        <li key={channel.id}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setActive(channel);
                                                    setShowChannels(false);
                                                }}
                                                className={`block min-h-11 w-full px-2 text-left text-sm ${active?.id === channel.id ? 'bg-slate-900 text-lime-300' : 'text-slate-200'}`}
                                            >
                                                # {channel.name}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                                {channels.length === 0 && !channelError && <p className="px-2 text-sm text-slate-500">No channels yet.</p>}
                                {community.can_manage && community.is_member && (
                                    <form onSubmit={addChannel} className="mt-4 space-y-2 px-2">
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
                        </aside>

                        <section className={`${showChannels ? 'hidden' : 'flex'} min-w-0 flex-1 flex-col md:flex`}>
                            <header className="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
                                <button type="button" className="min-h-11 text-sm text-lime-300 md:hidden" onClick={() => setShowChannels(true)}>
                                    Channels
                                </button>
                                <div>
                                    <h2 className="font-display text-lg text-white">{active ? `# ${active.name}` : 'Conversation'}</h2>
                                    {community.description && <p className="text-xs text-slate-400">{community.description}</p>}
                                </div>
                            </header>
                            <div className="flex-1 space-y-4 overflow-y-auto px-4 py-4">
                                {messageError && <p className="text-sm text-rose-300" role="alert">{messageError}</p>}
                                {messages.map((message) => (
                                    <article key={message.id} className="flex gap-3">
                                        <Avatar name={message.user?.handle || message.user?.name || 'Player'} src={message.user?.avatar_url} size="sm" />
                                        <div>
                                            <p className="text-sm">
                                                <span className="text-white">{message.user?.handle || message.user?.name || 'Player'}</span>
                                                <span className="ml-2 text-xs text-slate-500">{clock(message.created_at)}</span>
                                            </p>
                                            <p className="mt-1 whitespace-pre-wrap text-sm text-slate-100">{message.message}</p>
                                        </div>
                                    </article>
                                ))}
                                {active && messages.length === 0 && !messageError && (
                                    <p className="text-sm text-slate-500">No messages in this channel yet.</p>
                                )}
                                {!active && !channelError && <p className="text-sm text-slate-500">Choose a channel.</p>}
                            </div>
                            {active && (
                                <form onSubmit={send} className="flex gap-2 border-t border-slate-800 px-4 py-3">
                                    <label htmlFor="message" className="sr-only">Message</label>
                                    <input
                                        id="message"
                                        value={draft}
                                        onChange={(event) => setDraft(event.target.value)}
                                        placeholder={`Message #${active.name}`}
                                        className="min-h-11 flex-1 border border-slate-700 bg-transparent px-3 text-sm"
                                    />
                                    <button type="submit" disabled={sending} className="min-h-11 bg-lime-300 px-4 text-sm text-slate-950">
                                        {sending ? 'Sending…' : 'Send'}
                                    </button>
                                </form>
                            )}
                        </section>
                    </>
                )}
                {error && !community && <p className="px-4 py-16 text-sm text-rose-300" role="alert">{error}</p>}
            </div>
        </AppLayout>
    );
}
