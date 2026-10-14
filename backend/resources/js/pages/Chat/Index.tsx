import React, { useState, useEffect, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    MessageSquare,
    Send,
    Users,
    Globe,
    AtSign,
    Paperclip,
    Shield,
} from 'lucide-react';

interface ChannelItem {
    id: number;
    uuid: string;
    name: string;
    type: 'team' | 'community' | 'direct';
    team_id?: number;
    community_id?: number;
}

interface MessageItem {
    id: number;
    uuid: string;
    channel_id: number;
    user: {
        id: number;
        name: string;
        handle?: string;
        avatar_url?: string;
    };
    message: string;
    mentions: number[];
    attachment_url?: string;
    created_at: string;
}

export default function ChatIndex() {
    const { auth } = usePage<any>().props;
    const [channels, setChannels] = useState<ChannelItem[]>([]);
    const [activeChannel, setActiveChannel] = useState<ChannelItem | null>(null);
    const [messages, setMessages] = useState<MessageItem[]>([]);
    const [messageInput, setMessageInput] = useState('');
    const [loadingMessages, setLoadingMessages] = useState(false);
    const [sending, setSending] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    // Initial mock channels or fetch from teams/communities user is part of
    useEffect(() => {
        // Fetch teams user belongs to
        const initChannels = async () => {
            try {
                const res = await fetch('/api/v1/teams', { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.data?.length > 0) {
                    const firstTeam = data.data[0];
                    const chRes = await fetch(`/api/v1/chat/teams/${firstTeam.id}`, { headers: { Accept: 'application/json' } });
                    const chData = await chRes.json();
                    if (chData.data) {
                        setChannels([chData.data]);
                        setActiveChannel(chData.data);
                    }
                }
            } catch (err) {
                console.error(err);
            }
        };

        if (auth.user) {
            initChannels();
        }
    }, []);

    // Load messages when active channel changes
    useEffect(() => {
        if (!activeChannel) return;

        const loadMessages = async () => {
            setLoadingMessages(true);
            try {
                const res = await fetch(`/api/v1/chat/channels/${activeChannel.id}/messages`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                if (data.data) {
                    setMessages(data.data);
                }
            } catch (err) {
                console.error(err);
            } finally {
                setLoadingMessages(false);
            }
        };

        loadMessages();
    }, [activeChannel?.id]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const handleSendMessage = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeChannel || !messageInput.trim()) return;

        setSending(true);
        try {
            const res = await fetch(`/api/v1/chat/channels/${activeChannel.id}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({ message: messageInput }),
            });
            if (res.ok) {
                const newMsg = (await res.json()).data;
                setMessages([...messages, newMsg]);
                setMessageInput('');
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSending(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Chat - Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto px-4 py-8">
                <div className="border border-slate-800 rounded-2xl bg-slate-900/80 backdrop-blur-sm overflow-hidden grid grid-cols-1 md:grid-cols-4 h-[75vh]">
                    {/* Left Channels Sidebar */}
                    <div className="border-r border-slate-800 bg-slate-950/60 p-4 space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-bold text-white flex items-center gap-2">
                                <MessageSquare className="w-4 h-4 text-blue-400" />
                                Channels
                            </h2>
                            <span className="text-[10px] text-slate-500 uppercase tracking-widest font-bold">
                                Realtime
                            </span>
                        </div>

                        <div className="space-y-1">
                            <span className="text-[10px] uppercase font-bold text-slate-500 tracking-wider px-2">
                                Team & Community Hubs
                            </span>
                            {channels.length === 0 ? (
                                <p className="text-xs text-slate-500 px-2 py-3">
                                    Join a team or community hub to chat with teammates!
                                </p>
                            ) : (
                                channels.map((ch) => (
                                    <button
                                        key={ch.id}
                                        onClick={() => setActiveChannel(ch)}
                                        className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all text-left ${
                                            activeChannel?.id === ch.id
                                                ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30'
                                                : 'text-slate-400 hover:text-white hover:bg-slate-900'
                                        }`}
                                    >
                                        {ch.type === 'team' ? (
                                            <Users className="w-3.5 h-3.5" />
                                        ) : (
                                            <Globe className="w-3.5 h-3.5" />
                                        )}
                                        <span className="truncate">{ch.name || 'Channel'}</span>
                                    </button>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Right Message Feed */}
                    <div className="md:col-span-3 flex flex-col h-full bg-slate-950/40">
                        {/* Channel Header */}
                        <div className="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/40">
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-sm text-white">
                                    {activeChannel?.name || 'Select a Channel'}
                                </span>
                                {activeChannel && (
                                    <Badge variant="outline" className="text-[10px] uppercase">
                                        {activeChannel.type}
                                    </Badge>
                                )}
                            </div>
                            <span className="text-xs text-slate-500 flex items-center gap-1">
                                <AtSign className="w-3.5 h-3.5" /> Support @mentions
                            </span>
                        </div>

                        {/* Messages Area */}
                        <div className="flex-1 overflow-y-auto p-4 space-y-3">
                            {loadingMessages ? (
                                <div className="text-center text-xs text-slate-500 py-10">
                                    Loading messages...
                                </div>
                            ) : messages.length === 0 ? (
                                <div className="text-center text-xs text-slate-500 py-16">
                                    No messages yet. Say hello to your squad!
                                </div>
                            ) : (
                                messages.map((m) => (
                                    <div
                                        key={m.id}
                                        className="flex items-start gap-3 group p-2 rounded-lg hover:bg-slate-900/40 transition-colors"
                                    >
                                        <div className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-300 shrink-0">
                                            {m.user?.name?.substring(0, 2).toUpperCase() || 'U'}
                                        </div>
                                        <div className="flex-1 space-y-0.5">
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs font-bold text-slate-200">
                                                    {m.user?.name}
                                                </span>
                                                <span className="text-[10px] text-slate-500">
                                                    {new Date(m.created_at).toLocaleTimeString([], {
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-300 leading-relaxed">
                                                {m.message}
                                            </p>
                                        </div>
                                    </div>
                                ))
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Message Composer */}
                        <form onSubmit={handleSendMessage} className="p-3 border-t border-slate-800 flex items-center gap-2 bg-slate-900/40">
                            <input
                                type="text"
                                value={messageInput}
                                onChange={(e) => setMessageInput(e.target.value)}
                                placeholder="Message channel... use @handle to mention teammates"
                                className="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"
                            />
                            <Button
                                type="submit"
                                variant="primary"
                                size="sm"
                                disabled={sending || !messageInput.trim() || !activeChannel}
                            >
                                <Send className="w-3.5 h-3.5" />
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
