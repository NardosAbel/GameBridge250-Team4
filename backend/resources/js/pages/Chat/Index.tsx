import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';

interface Channel {
    id: number;
    name: string;
    type: string;
}

interface Message {
    id: number;
    message: string;
    created_at: string;
    user?: { name: string };
}

function csrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

export default function ChatIndex() {
    const [channels, setChannels] = useState<Channel[]>([]);
    const [active, setActive] = useState<Channel | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [draft, setDraft] = useState('');
    const [error, setError] = useState('');
    const [listOpen, setListOpen] = useState(true);
    const [sending, setSending] = useState(false);

    useEffect(() => {
        fetch('/api/v1/chat/mine', { headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((body) => {
                const rows = (body.data ?? []) as Channel[];
                setChannels(rows);
                setActive(rows[0] ?? null);
            })
            .catch(() => setError('Channels could not be loaded.'));
    }, []);

    useEffect(() => {
        if (!active) return;
        let cancelled = false;
        const load = async () => {
            const res = await fetch(`/api/v1/chat/channels/${active.id}/messages`, { headers: { Accept: 'application/json' } });
            const body = await res.json();
            if (!cancelled && res.ok) setMessages(body.data ?? []);
            if (!res.ok) setError(body.message || 'Messages are not available.');
        };
        load();
        const timer = window.setInterval(load, 8000);
        return () => {
            cancelled = true;
            window.clearInterval(timer);
        };
    }, [active?.id]);

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
            setError(body.message || 'Message was not sent.');
            return;
        }
        setDraft('');
        setMessages((current) => [...current, body.data]);
    };

    return (
        <AppLayout>
            <Head title="Messages" />
            <div className="mx-auto grid max-w-5xl min-h-[70vh] px-4 py-6 md:grid-cols-[16rem_minmax(0,1fr)]">
                <div className={`${listOpen ? 'block' : 'hidden'} md:block`}>
                    <h1 className="font-display text-2xl text-white">Messages</h1>
                    <p className="mt-2 text-sm text-slate-400">Saved channel history. New messages appear on the next refresh.</p>
                    {channels.length === 0 && (
                        <p className="mt-6 text-sm text-slate-400">
                            No channel is open yet. <Link href="/communities" className="text-lime-300 hover:underline">Join a community</Link> and open it to start one.
                        </p>
                    )}
                    <ul className="mt-4">
                        {channels.map((channel) => (
                            <li key={channel.id}>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setActive(channel);
                                        setListOpen(false);
                                    }}
                                    className={`block min-h-11 w-full text-left text-sm ${active?.id === channel.id ? 'text-lime-300' : 'text-slate-200'}`}
                                >
                                    {channel.name}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
                <section className={`${listOpen ? 'hidden' : 'flex'} flex-col md:flex`}>
                    <button type="button" className="mb-3 min-h-11 text-left text-sm text-lime-300 md:hidden" onClick={() => setListOpen(true)}>
                        All channels
                    </button>
                    <h2 className="font-display text-xl text-white">{active?.name ?? 'Choose a channel'}</h2>
                    {error && <p className="mt-3 text-sm text-rose-300" role="alert">{error}</p>}
                    <div className="mt-4 flex-1 space-y-3">
                        {messages.map((message) => (
                            <p key={message.id} className="text-sm">
                                <span className="text-slate-400">{message.user?.name ?? 'Player'}</span>
                                <span className="mt-1 block">{message.message}</span>
                            </p>
                        ))}
                    </div>
                    {active && (
                        <form onSubmit={send} className="mt-4 flex gap-2">
                            <label htmlFor="chat-message" className="sr-only">Message</label>
                            <input id="chat-message" value={draft} onChange={(event) => setDraft(event.target.value)} className="min-h-11 flex-1 border border-slate-700 bg-transparent px-3 text-sm" />
                            <button type="submit" disabled={sending} className="min-h-11 bg-lime-300 px-4 text-sm text-slate-950">{sending ? 'Sending…' : 'Send'}</button>
                        </form>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
