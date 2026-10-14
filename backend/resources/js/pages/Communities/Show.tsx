import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    Globe,
    Users,
    MessageSquare,
    Pin,
    Flame,
    Lock,
    Send,
    Plus,
} from 'lucide-react';

interface CommunityDetail {
    id: number;
    name: string;
    slug: string;
    description?: string;
    type: string;
    visibility: string;
    creator?: { id: number; name: string };
    members_count: number;
    is_member: boolean;
    membership_status?: string | null;
}

export default function CommunityShow({ communityId }: { communityId: number }) {
    const { auth } = usePage<any>().props;
    const [community, setCommunity] = useState<CommunityDetail | null>(null);
    const [posts, setPosts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    // Discussion composer
    const [content, setContent] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const fetchCommunityData = async () => {
        setLoading(true);
        try {
            const [commRes, postRes] = await Promise.all([
                fetch(`/api/v1/communities/${communityId}`, { headers: { Accept: 'application/json' } }),
                fetch(`/api/v1/communities/${communityId}/posts`, { headers: { Accept: 'application/json' } }),
            ]);

            const commData = await commRes.json();
            const postData = await postRes.json();

            if (commData.data) setCommunity(commData.data);
            if (postData.data) setPosts(postData.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCommunityData();
    }, [communityId]);

    const handlePost = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!content.trim()) return;

        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/posts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({
                    content,
                    community_id: communityId,
                    type: 'text',
                    visibility: 'community',
                }),
            });
            if (res.ok) {
                setContent('');
                fetchCommunityData();
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSubmitting(false);
        }
    };

    if (loading || !community) {
        return (
            <AppLayout>
                <div className="py-24 text-center text-slate-500">Loading community...</div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`${community.name} - Campus Esports Ethiopia`} />

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">
                {/* Header */}
                <div className="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 md:p-8 backdrop-blur-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="flex items-center gap-5">
                        <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-cyan-600 to-blue-500 flex items-center justify-center font-bold text-white text-2xl shadow-lg">
                            {community.name.substring(0, 2).toUpperCase()}
                        </div>
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-black text-white">{community.name}</h1>
                                <Badge variant="outline" className="text-xs capitalize">
                                    {community.type}
                                </Badge>
                                {community.visibility !== 'public' && (
                                    <Lock className="w-4 h-4 text-slate-400" />
                                )}
                            </div>
                            <p className="text-xs text-slate-400 max-w-xl">
                                {community.description || 'Welcome to this community hub! Join to participate in discussions and campus tournaments.'}
                            </p>
                            <div className="flex items-center gap-3 text-xs text-slate-400 pt-1">
                                <span className="flex items-center gap-1 font-semibold text-cyan-400">
                                    <Users className="w-3.5 h-3.5" /> {community.members_count} Members
                                </span>
                                <span>• Creator: {community.creator?.name}</span>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href="/chat">
                            <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                                <MessageSquare className="w-4 h-4 text-cyan-400" /> Hub Chat
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Discussions Feed */}
                <div className="space-y-4">
                    {/* Post to this hub */}
                    {auth.user && (
                        <Card className="border border-slate-800 bg-slate-900/60">
                            <CardBody className="p-4">
                                <form onSubmit={handlePost} className="space-y-3">
                                    <textarea
                                        value={content}
                                        onChange={(e) => setContent(e.target.value)}
                                        placeholder={`Post an announcement, poll, or discussion in ${community.name}...`}
                                        rows={2}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 resize-none"
                                    />
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            variant="primary"
                                            size="sm"
                                            disabled={submitting || !content.trim()}
                                        >
                                            <Send className="w-3.5 h-3.5 mr-1" /> Post to Hub
                                        </Button>
                                    </div>
                                </form>
                            </CardBody>
                        </Card>
                    )}

                    {/* Posts list */}
                    {posts.length === 0 ? (
                        <div className="py-12 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                            <MessageSquare className="w-8 h-8 text-slate-600 mx-auto mb-2" />
                            <p className="text-xs text-slate-400">No discussions yet in this community.</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {posts.map((p) => (
                                <Card key={p.id} className="border border-slate-800 bg-slate-900/70">
                                    <CardBody className="p-4 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <div className="w-7 h-7 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-300">
                                                    {p.author.name.substring(0, 2).toUpperCase()}
                                                </div>
                                                <span className="text-xs font-bold text-white">
                                                    {p.author.name}
                                                </span>
                                                <span className="text-[10px] text-slate-500">
                                                    {new Date(p.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                            {p.pinned && (
                                                <span className="text-[10px] text-amber-400 font-bold flex items-center gap-1">
                                                    <Pin className="w-3 h-3" /> Pinned
                                                </span>
                                            )}
                                        </div>

                                        <p className="text-xs text-slate-200 leading-relaxed">
                                            {p.content}
                                        </p>
                                    </CardBody>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
