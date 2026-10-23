import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Badge } from '../../components/ui/Badge';

interface PassportPageProps {
    player: {
        id?: number;
        name?: string;
        gamer_tag: string;
        university?: string;
        bio?: string;
        student_verified: boolean;
    };
    passport: {
        tournament_count: number;
        verified_result_count: number;
        rating: number | null;
        rankings: Array<{ game: string; points: number; rank: number | null }>;
        results: Array<{
            tournament_id: number;
            title: string;
            placement: number | null;
            points_awarded: number | null;
        }>;
    };
    social?: {
        followers_count: number;
        viewer_following: boolean;
        posts: Array<{ id: number; content: string; sponsored: boolean; created_at: string }>;
    };
}

export default function Passport({ player, passport, social }: PassportPageProps) {
    const { auth } = usePage<{ auth: { user: { id: number } | null } }>().props;
    const [following, setFollowing] = useState(social?.viewer_following ?? false);
    const [followers, setFollowers] = useState(social?.followers_count ?? 0);
    const [followError, setFollowError] = useState('');

    const toggleFollow = async () => {
        if (!player.id) return;
        const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
        const res = await fetch(`/api/v1/interactions/follow/user/${player.id}`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
        });
        const body = await res.json();
        if (!res.ok) {
            setFollowError(body.message || 'Follow could not be saved.');
            return;
        }
        setFollowing(Boolean(body.is_following));
        setFollowers((count) => count + (body.is_following ? 1 : -1));
    };
    return (
        <AppLayout>
            <Head title={`${player.gamer_tag} | Player Passport`} />
            <div className="max-w-3xl mx-auto px-4 py-10 space-y-8">
                <header className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="font-display text-3xl text-white">{player.gamer_tag}</h1>
                        {player.student_verified && <Badge variant="success">Verified student</Badge>}
                    </div>
                    <p className="text-sm text-slate-300">{player.name}{player.university ? ` · ${player.university}` : ''}</p>
                    {player.bio && <p className="text-sm text-slate-400">{player.bio}</p>}
                    <p className="text-sm text-slate-400">{followers} followers</p>
                    {auth.user && player.id && auth.user.id !== player.id && (
                        <button type="button" onClick={toggleFollow} className="min-h-11 border border-lime-300 px-4 text-sm text-lime-300">
                            {following ? 'Following' : 'Follow'}
                        </button>
                    )}
                    {followError && <p className="text-sm text-rose-300" role="alert">{followError}</p>}
                </header>

                {social && (
                    <section>
                        <h2 className="text-base font-semibold text-white">Posts</h2>
                        {social.posts.length === 0 ? (
                            <p className="mt-2 text-sm text-slate-400">No public posts yet.</p>
                        ) : (
                            <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                                {social.posts.map((post) => (
                                    <li key={post.id} className="py-3 text-sm">
                                        {post.sponsored && <span className="mr-2 text-lime-300">Sponsored</span>}
                                        <Link href={`/posts/${post.id}`} className="text-white hover:underline">{post.content}</Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                )}

                <dl className="grid grid-cols-3 gap-4 border-y border-slate-800 py-4 text-sm">
                    <div>
                        <dt className="text-slate-500">Entered</dt>
                        <dd className="mt-1 text-lg text-white">{passport.tournament_count}</dd>
                    </div>
                    <div>
                        <dt className="text-slate-500">Confirmed results</dt>
                        <dd className="mt-1 text-lg text-white">{passport.verified_result_count}</dd>
                    </div>
                    <div>
                        <dt className="text-slate-500">Rating</dt>
                        <dd className="mt-1 text-lg text-white">{passport.rating ?? '—'}</dd>
                    </div>
                </dl>

                <section>
                    <h2 className="text-base font-semibold text-white">Recorded results</h2>
                    {passport.results.length === 0 ? (
                        <p className="mt-2 text-sm text-slate-400">No confirmed placement is on record.</p>
                    ) : (
                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                            {passport.results.map((result) => (
                                <li key={result.tournament_id} className="flex justify-between gap-3 py-3 text-sm">
                                    <Link href={`/tournaments/${result.tournament_id}`} className="text-white underline-offset-4 hover:underline">
                                        {result.title}
                                    </Link>
                                    <span className="text-slate-400">
                                        {result.placement ? `Place ${result.placement}` : 'No place yet'}
                                        {result.points_awarded ? ` · ${result.points_awarded} pts` : ''}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {passport.rankings.length > 0 && (
                    <section>
                        <h2 className="text-base font-semibold text-white">Season rankings</h2>
                        <ul className="mt-3 divide-y divide-slate-800 border-y border-slate-800">
                            {passport.rankings.map((ranking) => (
                                <li key={ranking.game} className="flex justify-between py-3 text-sm">
                                    <span>{ranking.game}</span>
                                    <span className="text-slate-400">{ranking.points} pts{ranking.rank ? ` · rank ${ranking.rank}` : ''}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
