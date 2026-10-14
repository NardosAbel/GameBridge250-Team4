import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    Flame,
    MessageCircle,
    Share2,
    Bookmark,
    MoreVertical,
    Send,
    Image,
    Video,
    BarChart2,
    ShieldCheck,
    Trophy,
    Heart,
    Pin,
    AlertTriangle,
    Eye,
    Plus,
    X,
} from 'lucide-react';

interface MediaItem {
    id: number;
    media_url: string;
    media_type: 'image' | 'video' | 'clip';
    thumbnail_url?: string;
}

interface PollOption {
    text: string;
    votes: number;
}

interface PostItem {
    id: number;
    uuid: string;
    author: {
        id: number;
        name: string;
        handle?: string;
        avatar_url?: string;
        is_verified?: boolean;
    };
    title?: string;
    content: string;
    type: 'text' | 'clip' | 'livestream' | 'announcement' | 'poll';
    visibility: string;
    location_tag?: string;
    hashtags: string[];
    poll_data?: {
        question?: string;
        options: PollOption[];
        voters?: Record<number, number>;
    };
    user_voted_option?: number | null;
    pinned: boolean;
    media: MediaItem[];
    game?: { id: number; name: string; slug: string };
    community?: { id: number; name: string; slug: string };
    team?: { id: number; name: string; tag: string; slug: string };
    stats: {
        reactions_count: number;
        comments_count: number;
        shares_count: number;
    };
    user_reaction?: string | null;
    is_bookmarked: boolean;
    created_at: string;
}

export default function Feed() {
    const { auth } = usePage<any>().props;
    const [feedType, setFeedType] = useState<'personalized' | 'explore'>('personalized');
    const [posts, setPosts] = useState<PostItem[]>([]);
    const [loading, setLoading] = useState(true);

    // Composer state
    const [content, setContent] = useState('');
    const [postType, setPostType] = useState<'text' | 'poll' | 'clip'>('text');
    const [pollOptions, setPollOptions] = useState<string[]>(['', '']);
    const [locationTag, setLocationTag] = useState('');
    const [mediaFiles, setMediaFiles] = useState<File[]>([]);
    const [submitting, setSubmitting] = useState(false);

    // Comments open per post
    const [openComments, setOpenComments] = useState<Record<number, boolean>>({});
    const [commentTexts, setCommentTexts] = useState<Record<number, string>>({});
    const [postComments, setPostComments] = useState<Record<number, any[]>>({});

    // Fetch feed
    const fetchFeed = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/posts?feed=${feedType}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.data) {
                setPosts(data.data);
            }
        } catch (err) {
            console.error('Failed to load feed', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchFeed();
    }, [feedType]);

    // Handle Create Post
    const handleCreatePost = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!content.trim()) return;

        setSubmitting(true);
        const formData = new FormData();
        formData.append('content', content);
        formData.append('type', postType);
        if (locationTag) formData.append('location_tag', locationTag);

        if (postType === 'poll') {
            pollOptions.filter((opt) => opt.trim()).forEach((opt, idx) => {
                formData.append(`poll_options[${idx}]`, opt);
            });
        }

        mediaFiles.forEach((file, idx) => {
            formData.append(`media[${idx}]`, file);
        });

        try {
            const res = await fetch('/api/v1/posts', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: formData,
            });
            if (res.ok) {
                setContent('');
                setPostType('text');
                setPollOptions(['', '']);
                setLocationTag('');
                setMediaFiles([]);
                fetchFeed();
            }
        } catch (err) {
            console.error(err);
        } finally {
            setSubmitting(false);
        }
    };

    // Reaction
    const handleReaction = async (postId: number, reactionType: 'like' | 'fire' | 'trophy' | 'gg') => {
        try {
            const res = await fetch(`/api/v1/interactions/reaction/post/${postId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({ type: reactionType }),
            });
            if (res.ok) {
                fetchFeed();
            }
        } catch (err) {
            console.error(err);
        }
    };

    // Poll Vote
    const handleVote = async (postId: number, optionIndex: number) => {
        try {
            const res = await fetch(`/api/v1/posts/${postId}/poll/vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({ option_index: optionIndex }),
            });
            if (res.ok) {
                fetchFeed();
            }
        } catch (err) {
            console.error(err);
        }
    };

    // Load comments
    const toggleComments = async (postId: number) => {
        const isOpen = !openComments[postId];
        setOpenComments({ ...openComments, [postId]: isOpen });
        if (isOpen && !postComments[postId]) {
            try {
                const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                setPostComments({ ...postComments, [postId]: data.data || [] });
            } catch (err) {
                console.error(err);
            }
        }
    };

    // Submit comment
    const handleAddComment = async (postId: number) => {
        const text = commentTexts[postId];
        if (!text || !text.trim()) return;

        try {
            const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                },
                body: JSON.stringify({ content: text }),
            });
            if (res.ok) {
                const newComment = (await res.json()).data;
                setPostComments({
                    ...postComments,
                    [postId]: [newComment, ...(postComments[postId] || [])],
                });
                setCommentTexts({ ...commentTexts, [postId]: '' });
                fetchFeed();
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <AppLayout>
            <Head title="Gamer Feed - Campus Esports Ethiopia" />

            <div className="max-w-4xl mx-auto px-4 py-8 space-y-6">
                {/* Feed Header & Tabs */}
                <div className="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div>
                        <h1 className="text-2xl font-black text-white flex items-center gap-2">
                            <Flame className="w-6 h-6 text-orange-500" />
                            Campus Esports Community Feed
                        </h1>
                        <p className="text-sm text-slate-400">
                            Live highlights, discussions, campus rivalries, and recruitment.
                        </p>
                    </div>

                    <div className="flex items-center bg-slate-900 border border-slate-800 rounded-lg p-1">
                        <button
                            onClick={() => setFeedType('personalized')}
                            className={`px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                                feedType === 'personalized'
                                    ? 'bg-emerald-500 text-slate-950 shadow-sm'
                                    : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            Personalized
                        </button>
                        <button
                            onClick={() => setFeedType('explore')}
                            className={`px-3 py-1.5 rounded-md text-xs font-bold transition-all ${
                                feedType === 'explore'
                                    ? 'bg-emerald-500 text-slate-950 shadow-sm'
                                    : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            Explore
                        </button>
                    </div>
                </div>

                {/* Post Composer */}
                {auth.user ? (
                    <Card className="border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
                        <CardBody className="p-4 space-y-4">
                            <div className="flex items-start gap-3">
                                <div className="w-10 h-10 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm shrink-0">
                                    {auth.user.name.substring(0, 2).toUpperCase()}
                                </div>
                                <div className="flex-1 space-y-3">
                                    <textarea
                                        value={content}
                                        onChange={(e) => setContent(e.target.value)}
                                        placeholder="What's happening on campus? Share clips, scrims, or start a poll (#dota2 #pubgm #fifa)..."
                                        rows={3}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors resize-none"
                                    />

                                    {/* Poll Builder */}
                                    {postType === 'poll' && (
                                        <div className="space-y-2 p-3 bg-slate-950/80 rounded-lg border border-slate-800">
                                            <span className="text-xs font-semibold text-slate-300">Poll Options:</span>
                                            {pollOptions.map((opt, idx) => (
                                                <div key={idx} className="flex items-center gap-2">
                                                    <input
                                                        type="text"
                                                        value={opt}
                                                        onChange={(e) => {
                                                            const updated = [...pollOptions];
                                                            updated[idx] = e.target.value;
                                                            setPollOptions(updated);
                                                        }}
                                                        placeholder={`Option ${idx + 1}`}
                                                        className="flex-1 bg-slate-900 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-white"
                                                    />
                                                    {pollOptions.length > 2 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setPollOptions(pollOptions.filter((_, i) => i !== idx))}
                                                            className="text-slate-500 hover:text-rose-400"
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            ))}
                                            {pollOptions.length < 5 && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    type="button"
                                                    onClick={() => setPollOptions([...pollOptions, ''])}
                                                    className="text-xs"
                                                >
                                                    <Plus className="w-3.5 h-3.5 mr-1" /> Add Option
                                                </Button>
                                            )}
                                        </div>
                                    )}

                                    {/* Action Bar */}
                                    <div className="flex items-center justify-between pt-2 border-t border-slate-800/80">
                                        <div className="flex items-center gap-2">
                                            <label className="cursor-pointer p-2 rounded-lg bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-emerald-400 transition-colors">
                                                <Image className="w-4 h-4" />
                                                <input
                                                    type="file"
                                                    accept="image/*,video/*"
                                                    multiple
                                                    className="hidden"
                                                    onChange={(e) => {
                                                        if (e.target.files) {
                                                            setMediaFiles(Array.from(e.target.files));
                                                        }
                                                    }}
                                                />
                                            </label>

                                            <button
                                                type="button"
                                                onClick={() => setPostType(postType === 'poll' ? 'text' : 'poll')}
                                                className={`p-2 rounded-lg transition-colors ${
                                                    postType === 'poll'
                                                        ? 'bg-emerald-500/20 text-emerald-400'
                                                        : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-emerald-400'
                                                }`}
                                                title="Add Poll"
                                            >
                                                <BarChart2 className="w-4 h-4" />
                                            </button>

                                            <input
                                                type="text"
                                                value={locationTag}
                                                onChange={(e) => setLocationTag(e.target.value)}
                                                placeholder="Location (e.g. AAU 4 Kilo)"
                                                className="bg-slate-950 border border-slate-800 rounded px-2.5 py-1 text-xs text-slate-300 w-36"
                                            />

                                            {mediaFiles.length > 0 && (
                                                <span className="text-[11px] text-emerald-400 font-semibold">
                                                    {mediaFiles.length} file(s) attached
                                                </span>
                                            )}
                                        </div>

                                        <Button
                                            variant="primary"
                                            size="sm"
                                            disabled={submitting || !content.trim()}
                                            onClick={handleCreatePost}
                                            className="px-4"
                                        >
                                            <Send className="w-3.5 h-3.5 mr-1.5" />
                                            {submitting ? 'Posting...' : 'Post'}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardBody>
                    </Card>
                ) : (
                    <div className="p-4 rounded-xl border border-slate-800 bg-slate-900 text-center">
                        <p className="text-sm text-slate-400">
                            <a href="/login" className="text-emerald-400 font-bold hover:underline">
                                Log in
                            </a>{' '}
                            or{' '}
                            <a href="/register" className="text-emerald-400 font-bold hover:underline">
                                sign up
                            </a>{' '}
                            to post, react, and connect with campus gamers.
                        </p>
                    </div>
                )}

                {/* Posts List */}
                {loading ? (
                    <div className="py-12 text-center text-slate-500 text-sm">Loading feed...</div>
                ) : posts.length === 0 ? (
                    <div className="py-16 text-center border border-slate-800/80 rounded-xl bg-slate-900/30">
                        <Flame className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-base font-bold text-slate-200">No posts yet</h3>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Be the first gamer to share your match clip, university news, or banter!
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {posts.map((post) => {
                            const totalVotes =
                                post.poll_data?.options?.reduce((acc, opt) => acc + (opt.votes || 0), 0) || 0;

                            return (
                                <Card
                                    key={post.id}
                                    className="border border-slate-800/80 bg-slate-900/70 backdrop-blur-sm overflow-hidden"
                                >
                                    <CardBody className="p-5 space-y-4">
                                        {/* Post Author & Header */}
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-bold text-slate-950 text-sm">
                                                    {post.author.name.substring(0, 2).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="font-bold text-sm text-white">
                                                            {post.author.name}
                                                        </span>
                                                        {post.author.is_verified && (
                                                            <ShieldCheck className="w-4 h-4 text-emerald-400" />
                                                        )}
                                                        {post.author.handle && (
                                                            <span className="text-xs text-slate-400">
                                                                @{post.author.handle}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-2 text-[11px] text-slate-500">
                                                        <span>{new Date(post.created_at).toLocaleDateString()}</span>
                                                        {post.location_tag && <span>• {post.location_tag}</span>}
                                                        {post.game && (
                                                            <Badge variant="outline" className="text-[10px] py-0 px-1.5">
                                                                {post.game.name}
                                                            </Badge>
                                                        )}
                                                        {post.pinned && (
                                                            <span className="flex items-center gap-1 text-amber-400 font-semibold">
                                                                <Pin className="w-3 h-3" /> Pinned
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Post Content */}
                                        <div className="text-sm text-slate-200 leading-relaxed whitespace-pre-line">
                                            {post.content}
                                        </div>

                                        {/* Hashtags */}
                                        {post.hashtags?.length > 0 && (
                                            <div className="flex flex-wrap gap-1.5">
                                                {post.hashtags.map((tag, idx) => (
                                                    <span key={idx} className="text-xs text-emerald-400 font-medium hover:underline cursor-pointer">
                                                        #{tag}
                                                    </span>
                                                ))}
                                            </div>
                                        )}

                                        {/* Poll Display */}
                                        {post.type === 'poll' && post.poll_data?.options && (
                                            <div className="space-y-2 p-3 rounded-lg bg-slate-950/70 border border-slate-800">
                                                <div className="text-xs font-semibold text-slate-400">
                                                    Poll • {totalVotes} {totalVotes === 1 ? 'vote' : 'votes'}
                                                </div>
                                                {post.poll_data.options.map((opt, idx) => {
                                                    const pct = totalVotes > 0 ? Math.round((opt.votes / totalVotes) * 100) : 0;
                                                    const isSelected = post.user_voted_option === idx;

                                                    return (
                                                        <button
                                                            key={idx}
                                                            disabled={!auth.user || post.user_voted_option !== null}
                                                            onClick={() => handleVote(post.id, idx)}
                                                            className={`w-full relative overflow-hidden text-left p-2.5 rounded-lg border text-xs font-medium transition-all ${
                                                                isSelected
                                                                    ? 'border-emerald-500 bg-emerald-950/30 text-white'
                                                                    : 'border-slate-800 bg-slate-900 text-slate-300 hover:border-slate-700'
                                                            }`}
                                                        >
                                                            <div
                                                                className="absolute top-0 left-0 bottom-0 bg-emerald-500/20"
                                                                style={{ width: `${pct}%` }}
                                                            />
                                                            <div className="relative z-10 flex items-center justify-between">
                                                                <span>{opt.text}</span>
                                                                <span className="font-bold">{pct}%</span>
                                                            </div>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {/* Media Attachments */}
                                        {post.media?.length > 0 && (
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg overflow-hidden">
                                                {post.media.map((m) => (
                                                    <div key={m.id} className="relative aspect-video bg-black rounded-lg overflow-hidden border border-slate-800">
                                                        {m.media_type === 'video' ? (
                                                            <video src={m.media_url} controls className="w-full h-full object-contain" />
                                                        ) : (
                                                            <img src={m.media_url} alt="Attachment" className="w-full h-full object-cover" />
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        {/* Action Buttons: Reactions, Comments, Share, Bookmark */}
                                        <div className="flex items-center justify-between pt-3 border-t border-slate-800/80 text-xs text-slate-400">
                                            <div className="flex items-center gap-1 sm:gap-2">
                                                <button
                                                    onClick={() => handleReaction(post.id, 'fire')}
                                                    className={`px-2 py-1 rounded flex items-center gap-1 transition-colors ${
                                                        post.user_reaction === 'fire'
                                                            ? 'bg-orange-500/20 text-orange-400 font-bold'
                                                            : 'hover:bg-slate-800 text-slate-400'
                                                    }`}
                                                    title="Fire"
                                                >
                                                    <Flame className="w-4 h-4 text-orange-400" />
                                                    <span>{post.stats.reactions_count}</span>
                                                </button>

                                                <button
                                                    onClick={() => handleReaction(post.id, 'trophy')}
                                                    className={`px-2 py-1 rounded flex items-center gap-1 transition-colors ${
                                                        post.user_reaction === 'trophy'
                                                            ? 'bg-amber-500/20 text-amber-400 font-bold'
                                                            : 'hover:bg-slate-800 text-slate-400'
                                                    }`}
                                                    title="Trophy"
                                                >
                                                    <Trophy className="w-4 h-4 text-amber-400" />
                                                </button>

                                                <button
                                                    onClick={() => toggleComments(post.id)}
                                                    className="px-2 py-1 rounded flex items-center gap-1 hover:bg-slate-800 text-slate-400 transition-colors"
                                                >
                                                    <MessageCircle className="w-4 h-4 text-blue-400" />
                                                    <span>{post.stats.comments_count}</span>
                                                </button>
                                            </div>

                                            <div className="flex items-center gap-1">
                                                <button
                                                    onClick={async () => {
                                                        await fetch(`/api/v1/interactions/bookmark/post/${post.id}`, {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                                                            },
                                                        });
                                                        fetchFeed();
                                                    }}
                                                    className={`p-1.5 rounded transition-colors ${
                                                        post.is_bookmarked
                                                            ? 'text-emerald-400 bg-emerald-500/10'
                                                            : 'hover:bg-slate-800 text-slate-400'
                                                    }`}
                                                    title="Bookmark"
                                                >
                                                    <Bookmark className="w-4 h-4" />
                                                </button>

                                                <button
                                                    onClick={async () => {
                                                        await fetch(`/api/v1/interactions/share/post/${post.id}`, {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                                                            },
                                                        });
                                                        fetchFeed();
                                                    }}
                                                    className="p-1.5 rounded hover:bg-slate-800 text-slate-400 transition-colors"
                                                    title="Share"
                                                >
                                                    <Share2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        {/* Comments Drawer */}
                                        {openComments[post.id] && (
                                            <div className="pt-3 border-t border-slate-800/60 space-y-3">
                                                {auth.user && (
                                                    <div className="flex items-center gap-2">
                                                        <input
                                                            type="text"
                                                            value={commentTexts[post.id] || ''}
                                                            onChange={(e) =>
                                                                setCommentTexts({
                                                                    ...commentTexts,
                                                                    [post.id]: e.target.value,
                                                                })
                                                            }
                                                            placeholder="Write a comment..."
                                                            className="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500"
                                                        />
                                                        <Button
                                                            size="sm"
                                                            onClick={() => handleAddComment(post.id)}
                                                            className="text-xs"
                                                        >
                                                            Reply
                                                        </Button>
                                                    </div>
                                                )}

                                                <div className="space-y-2">
                                                    {(postComments[post.id] || []).map((c: any) => (
                                                        <div
                                                            key={c.id}
                                                            className="p-2.5 rounded-lg bg-slate-950/80 border border-slate-800/80 text-xs space-y-1"
                                                        >
                                                            <div className="flex items-center justify-between">
                                                                <span className="font-bold text-slate-200">
                                                                    {c.user?.name}
                                                                </span>
                                                                <span className="text-[10px] text-slate-500">
                                                                    {new Date(c.created_at).toLocaleTimeString([], {
                                                                        hour: '2-digit',
                                                                        minute: '2-digit',
                                                                    })}
                                                                </span>
                                                            </div>
                                                            <p className="text-slate-300">{c.content}</p>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </CardBody>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
