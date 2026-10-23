import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Heart, ImagePlus, MessageCircle, Link2 } from 'lucide-react';
import { AppLayout } from '../../layouts/AppLayout';

interface PostItem {
    id: number;
    content: string;
    type: string;
    sponsored: boolean;
    created_at: string;
    author: { id: number; name: string; handle?: string };
    media: Array<{ id: number; media_url: string; media_type: string }>;
    tournament?: { id: number; title: string } | null;
    community?: { id: number; name: string } | null;
    team?: { id: number; name: string } | null;
    stats: { reactions_count: number; comments_count: number };
    user_reaction?: string | null;
}

interface TournamentOption {
    id: number;
    title: string;
}

function csrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

function when(iso: string): string {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

export default function Feed() {
    const { auth } = usePage<{ auth: { user: { id: number; roles: string[] } | null } }>().props;
    const roles = auth.user?.roles ?? [];
    const canAnnounce = roles.some((role) => ['organizer', 'super_admin'].includes(role));
    const canSponsor = roles.some((role) => ['sponsor', 'super_admin'].includes(role));

    const [posts, setPosts] = useState<PostItem[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [content, setContent] = useState('');
    const [tournamentId, setTournamentId] = useState('');
    const [postType, setPostType] = useState<'text' | 'announcement'>('text');
    const [sponsored, setSponsored] = useState(false);
    const [image, setImage] = useState<File | null>(null);
    const [tournaments, setTournaments] = useState<TournamentOption[]>([]);
    const [submitting, setSubmitting] = useState(false);
    const [formError, setFormError] = useState('');
    const [notice, setNotice] = useState('');
    const [openComments, setOpenComments] = useState<number | null>(null);
    const [comments, setComments] = useState<Record<number, Array<{ id: number; content: string; author?: { name: string } }>>>({});
    const [commentText, setCommentText] = useState('');

    const load = async (nextPage = 1, append = false) => {
        setLoading(true);
        setError('');
        try {
            const res = await fetch(`/api/v1/posts?feed=personalized&per_page=10&page=${nextPage}`, {
                headers: { Accept: 'application/json' },
            });
            const body = await res.json();
            if (!res.ok) {
                setError(body.message || 'The feed could not be loaded.');
                return;
            }
            const rows = (body.data ?? []) as PostItem[];
            setPosts((current) => (append ? [...current, ...rows] : rows));
            setPage(body.meta?.current_page ?? nextPage);
            setLastPage(body.meta?.last_page ?? 1);
        } catch {
            setError('The feed could not be loaded.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        fetch('/api/v1/tournaments', { headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((body) => {
                const rows = (body.data ?? []) as Array<{ id: number; title: string }>;
                setTournaments(rows.map((row) => ({ id: row.id, title: row.title })));
            })
            .catch(() => setTournaments([]));
    }, []);

    const publish = async (event: React.FormEvent) => {
        event.preventDefault();
        setFormError('');
        setNotice('');
        if (!content.trim()) {
            setFormError('Write something before posting.');
            return;
        }
        setSubmitting(true);
        const form = new FormData();
        form.append('content', content.trim());
        form.append('type', postType);
        form.append('visibility', 'public');
        if (tournamentId) form.append('tournament_id', tournamentId);
        if (sponsored) form.append('sponsored', '1');
        if (image) form.append('media[0]', image);
        try {
            const res = await fetch('/api/v1/posts', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: form,
            });
            const body = await res.json();
            if (!res.ok) {
                setFormError(body.message || 'The post was not saved.');
                return;
            }
            setContent('');
            setImage(null);
            setSponsored(false);
            setTournamentId('');
            setNotice('Posted.');
            await load();
        } catch {
            setFormError('The post was not saved.');
        } finally {
            setSubmitting(false);
        }
    };

    const toggleLike = async (post: PostItem) => {
        const res = await fetch(`/api/v1/interactions/reaction/post/${post.id}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ type: 'like' }),
        });
        if (!res.ok) return;
        const body = await res.json();
        setPosts((current) => current.map((item) => {
            if (item.id !== post.id) return item;
            const liked = body.status === 'created' || body.status === 'updated';
            const delta = body.status === 'created' ? 1 : body.status === 'removed' ? -1 : 0;
            return {
                ...item,
                user_reaction: liked ? 'like' : null,
                stats: { ...item.stats, reactions_count: Math.max(0, item.stats.reactions_count + delta) },
            };
        }));
    };

    const openThread = async (postId: number) => {
        setOpenComments(postId);
        const res = await fetch(`/api/v1/posts/${postId}/comments`, { headers: { Accept: 'application/json' } });
        const body = await res.json();
        if (res.ok) setComments((current) => ({ ...current, [postId]: body.data ?? [] }));
    };

    const sendComment = async (postId: number) => {
        if (!commentText.trim()) return;
        const res = await fetch(`/api/v1/posts/${postId}/comments`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ content: commentText.trim() }),
        });
        const body = await res.json();
        if (!res.ok) {
            setFormError(body.message || 'The comment was not saved.');
            return;
        }
        setCommentText('');
        setComments((current) => ({ ...current, [postId]: [...(current[postId] ?? []), body.data] }));
        setPosts((current) => current.map((item) => item.id === postId
            ? { ...item, stats: { ...item.stats, comments_count: item.stats.comments_count + 1 } }
            : item));
    };

    const copyLink = async (postId: number) => {
        const url = `${window.location.origin}/posts/${postId}`;
        await navigator.clipboard.writeText(url);
        setNotice('Link copied.');
    };

    const remove = async (postId: number) => {
        const res = await fetch(`/api/v1/posts/${postId}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });
        if (res.ok) setPosts((current) => current.filter((item) => item.id !== postId));
    };

    return (
        <AppLayout>
            <Head title="Feed" />
            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-8 lg:grid-cols-[minmax(0,40rem)_16rem]">
                <div>
                    <header className="mb-6">
                        <p className="font-display text-sm text-lime-300">GameBridge</p>
                        <h1 className="font-display text-3xl text-white">Feed</h1>
                        <p className="mt-2 text-sm text-slate-400">Recent posts from people and communities you can actually see. Counts come from saved likes and comments.</p>
                    </header>

                    <form onSubmit={publish} className="border-y border-slate-800 py-5">
                        <label htmlFor="post-content" className="sr-only">Post</label>
                        <textarea
                            id="post-content"
                            value={content}
                            onChange={(event) => setContent(event.target.value)}
                            rows={3}
                            maxLength={5000}
                            placeholder="Share a result, a scrim, or a tournament update."
                            className="w-full resize-y bg-transparent text-base text-white outline-none placeholder:text-slate-500"
                        />
                        <div className="mt-3 flex flex-wrap items-center gap-3">
                            <label className="text-sm text-slate-400">
                                <span className="sr-only">Tournament</span>
                                <select
                                    value={tournamentId}
                                    onChange={(event) => setTournamentId(event.target.value)}
                                    className="bg-transparent text-sm text-slate-200"
                                    aria-label="Link a tournament"
                                >
                                    <option value="">No tournament</option>
                                    {tournaments.map((tournament) => (
                                        <option key={tournament.id} value={tournament.id}>{tournament.title}</option>
                                    ))}
                                </select>
                            </label>
                            {canAnnounce && (
                                <label className="flex items-center gap-2 text-sm text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={postType === 'announcement'}
                                        onChange={(event) => setPostType(event.target.checked ? 'announcement' : 'text')}
                                    />
                                    Organizer announcement
                                </label>
                            )}
                            {canSponsor && (
                                <label className="flex items-center gap-2 text-sm text-slate-300">
                                    <input type="checkbox" checked={sponsored} onChange={(event) => setSponsored(event.target.checked)} />
                                    Sponsored
                                </label>
                            )}
                            <label className="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-slate-300">
                                <ImagePlus className="h-4 w-4" aria-hidden="true" />
                                Image
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    className="sr-only"
                                    onChange={(event) => setImage(event.target.files?.[0] ?? null)}
                                />
                            </label>
                            {image && <span className="text-xs text-slate-400">{image.name}</span>}
                            <button
                                type="submit"
                                disabled={submitting}
                                className="ml-auto min-h-11 bg-lime-300 px-4 text-sm font-medium text-slate-950 disabled:opacity-60"
                            >
                                {submitting ? 'Posting…' : 'Post'}
                            </button>
                        </div>
                        {formError && <p className="mt-3 text-sm text-rose-300" role="alert">{formError}</p>}
                        {notice && <p className="mt-3 text-sm text-lime-300" role="status">{notice}</p>}
                    </form>

                    {error && <p className="mt-6 text-sm text-rose-300" role="alert">{error}</p>}
                    {loading && posts.length === 0 && <p className="mt-6 text-sm text-slate-400">Loading posts.</p>}
                    {!loading && posts.length === 0 && (
                        <p className="mt-8 text-sm text-slate-400">No posts yet. Publish the first note, or follow a player from their passport.</p>
                    )}

                    <ol className="divide-y divide-slate-800">
                        {posts.map((post) => (
                            <li key={post.id} className="py-6">
                                <div className="flex items-baseline justify-between gap-3">
                                    {post.author.handle ? (
                                        <Link href={`/players/${post.author.handle}`} className="font-medium text-white hover:underline">{post.author.handle}</Link>
                                    ) : (
                                        <span className="font-medium text-white">{post.author.name}</span>
                                    )}
                                    <time className="text-xs text-slate-500" dateTime={post.created_at}>{when(post.created_at)}</time>
                                </div>
                                <div className="mt-1 flex flex-wrap gap-2 text-xs text-slate-400">
                                    {post.sponsored && <span className="text-lime-300">Sponsored</span>}
                                    {post.type === 'announcement' && <span>Announcement</span>}
                                    {post.team && <span>{post.team.name}</span>}
                                    {post.community && <Link href={`/communities/${post.community.id}`}>{post.community.name}</Link>}
                                </div>
                                <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-100">{post.content}</p>
                                {post.tournament && (
                                    <Link href={`/tournaments/${post.tournament.id}`} className="mt-3 inline-block text-sm text-lime-300 hover:underline">
                                        {post.tournament.title}
                                    </Link>
                                )}
                                {post.media.filter((file) => file.media_type === 'image').map((file) => (
                                    <img key={file.id} src={file.media_url} alt="" className="mt-3 max-h-96 w-full object-cover" />
                                ))}
                                <div className="mt-4 flex flex-wrap items-center gap-2">
                                    <button type="button" onClick={() => toggleLike(post)} className="inline-flex min-h-11 items-center gap-2 px-2 text-sm text-slate-300">
                                        <Heart className={`h-4 w-4 ${post.user_reaction === 'like' ? 'fill-lime-300 text-lime-300' : ''}`} aria-hidden="true" />
                                        {post.stats.reactions_count} likes
                                    </button>
                                    <button type="button" onClick={() => openThread(post.id)} className="inline-flex min-h-11 items-center gap-2 px-2 text-sm text-slate-300">
                                        <MessageCircle className="h-4 w-4" aria-hidden="true" />
                                        {post.stats.comments_count} comments
                                    </button>
                                    <button type="button" onClick={() => copyLink(post.id)} className="inline-flex min-h-11 items-center gap-2 px-2 text-sm text-slate-300">
                                        <Link2 className="h-4 w-4" aria-hidden="true" />
                                        Copy link
                                    </button>
                                    {auth.user?.id === post.author.id && (
                                        <button type="button" onClick={() => remove(post.id)} className="min-h-11 px-2 text-sm text-rose-300">Delete</button>
                                    )}
                                </div>
                                {openComments === post.id && (
                                    <div className="mt-3 space-y-3 border-l border-slate-800 pl-4">
                                        {(comments[post.id] ?? []).map((comment) => (
                                            <p key={comment.id} className="text-sm text-slate-200">
                                                <span className="text-slate-400">{comment.author?.name || (comment as { user?: { name?: string } }).user?.name || 'Player'}: </span>
                                                {comment.content}
                                            </p>
                                        ))}
                                        <form
                                            className="flex gap-2"
                                            onSubmit={(event) => {
                                                event.preventDefault();
                                                sendComment(post.id);
                                            }}
                                        >
                                            <label className="sr-only" htmlFor={`comment-${post.id}`}>Comment</label>
                                            <input
                                                id={`comment-${post.id}`}
                                                value={commentText}
                                                onChange={(event) => setCommentText(event.target.value)}
                                                className="min-h-11 flex-1 border border-slate-700 bg-transparent px-3 text-sm"
                                            />
                                            <button type="submit" className="min-h-11 px-3 text-sm text-lime-300">Reply</button>
                                        </form>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ol>
                    {page < lastPage && (
                        <button type="button" onClick={() => load(page + 1, true)} className="mt-4 min-h-11 text-sm text-lime-300">
                            {loading ? 'Loading…' : 'Older posts'}
                        </button>
                    )}
                </div>
                <aside className="hidden lg:block">
                    <p className="text-sm text-slate-400">Competitions stay on their own pages. A like does not change a ranking.</p>
                    <Link href="/tournaments" className="mt-4 inline-block text-sm text-lime-300 hover:underline">Browse competitions</Link>
                    <Link href="/communities" className="mt-2 block text-sm text-lime-300 hover:underline">Find a community</Link>
                </aside>
            </div>
        </AppLayout>
    );
}
