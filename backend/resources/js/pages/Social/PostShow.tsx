import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';

export default function PostShow({ postId }: { postId: number }) {
    const [post, setPost] = useState<any>(null);
    const [error, setError] = useState('');

    useEffect(() => {
        fetch(`/api/v1/posts/${postId}`, { headers: { Accept: 'application/json' } })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    setError(body.message || 'This post is not available.');
                    return;
                }
                setPost(body.data);
            })
            .catch(() => setError('This post is not available.'));
    }, [postId]);

    return (
        <AppLayout>
            <Head title="Post" />
            <article className="mx-auto max-w-2xl px-4 py-10">
                <Link href="/feed" className="text-sm text-lime-300 hover:underline">Feed</Link>
                {error && <p className="mt-6 text-sm text-rose-300" role="alert">{error}</p>}
                {!post && !error && <p className="mt-6 text-sm text-slate-400">Loading post.</p>}
                {post && (
                    <div className="mt-6">
                        <h1 className="font-display text-2xl text-white">
                            {post.author?.handle || post.author?.name}
                        </h1>
                        {post.sponsored && <p className="mt-2 text-sm text-lime-300">Sponsored</p>}
                        <p className="mt-4 whitespace-pre-wrap text-sm leading-6">{post.content}</p>
                        {post.tournament && (
                            <Link href={`/tournaments/${post.tournament.id}`} className="mt-4 inline-block text-sm text-lime-300 hover:underline">
                                {post.tournament.title}
                            </Link>
                        )}
                        <p className="mt-6 text-sm text-slate-400">
                            {post.stats?.reactions_count ?? 0} likes · {post.stats?.comments_count ?? 0} comments
                        </p>
                    </div>
                )}
            </article>
        </AppLayout>
    );
}
