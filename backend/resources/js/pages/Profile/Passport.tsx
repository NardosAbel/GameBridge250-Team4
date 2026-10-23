import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Avatar } from '../../components/ui/Avatar';

interface PassportPageProps {
    player: {
        id?: number;
        name?: string;
        gamer_tag: string;
        avatar_url?: string | null;
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
            <div className="mx-auto max-w-3xl space-y-10 px-4 py-8">
                <header className="border-b border-slate-800 pb-6">
                    <div className="flex items-end justify-between gap-4">
                        <div className="flex min-w-0 items-center gap-4">
                            <Avatar name={player.gamer_tag} src={player.avatar_url} size="lg" />
                            <div className="min-w-0">
                            <h1 className="break-words font-display text-3xl text-white sm:text-5xl">{player.gamer_tag}</h1>
                            <p className="mt-2 text-sm text-slate-300">
                                {player.name}
                                {player.university ? ` · ${player.university}` : ''}
                                {player.student_verified ? ' · Verified student' : ''}
                            </p>
                            </div>
                        </div>
                        {auth.user && player.id && auth.user.id !== player.id && (
                            <button type="button" onClick={toggleFollow} className="min-h-11 shrink-0 bg-lime-300 px-4 text-sm font-medium text-slate-950">
                                {following ? 'Following' : 'Follow'}
                            </button>
                        )}
                    </div>
                    {player.bio && <p className="mt-4 max-w-2xl text-sm leading-relaxed text-slate-300">{player.bio}</p>}
                    <p className="mt-4 text-sm text-slate-400">
                        {followers} followers
                        {` · ${passport.tournament_count} entered`}
                        {` · ${passport.verified_result_count} confirmed results`}
                        {passport.rating !== null ? ` · rating ${passport.rating}` : ''}
                    </p>
                    {followError && <p className="mt-2 text-sm text-rose-300" role="alert">{followError}</p>}
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
