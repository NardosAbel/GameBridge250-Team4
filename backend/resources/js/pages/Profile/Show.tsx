import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { Avatar } from '../../components/ui/Avatar';
import {
    Save,
    CheckCircle2,
} from 'lucide-react';
import { useTranslation } from '../../hooks/useTranslation';

interface ProfileProps {
    user: {
        id: number;
        uuid: string;
        name: string;
        email?: string;
        phone?: string;
        locale: string;
        roles: string[];
        email_verified: boolean;
        phone_verified: boolean;
    };
    passport: {
        tournament_count: number;
        verified_result_count: number;
        rating: number | null;
        rankings: Array<{ game: string; points: number; rank: number | null }>;
        results: Array<{
            tournament_id: number;
            title: string;
            status: string;
            placement: number | null;
            points_awarded: number | null;
        }>;
    };
    profile: {
        id: number;
        gamer_tag: string;
        bio?: string;
        avatar_url?: string;
        cover_url?: string;
        university?: string;
        school_id_number?: string;
        student_verified: boolean;
        platform_handles?: Record<string, string>;
        preferred_games?: string[];
        is_public: boolean;
    };
}

export default function Show({ user, profile, passport }: ProfileProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        gamer_tag: profile?.gamer_tag || '',
        bio: profile?.bio || '',
        university: profile?.university || '',
        school_id_number: profile?.school_id_number || '',
        is_public: profile?.is_public ?? true,
        locale: user.locale || 'en',
    });

    const [avatarUrl, setAvatarUrl] = useState(profile?.avatar_url || '');
    const [avatarError, setAvatarError] = useState('');
    const [uploadingAvatar, setUploadingAvatar] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/profile');
    };

    const uploadAvatar = async (file: File) => {
        setUploadingAvatar(true);
        setAvatarError('');
        const form = new FormData();
        form.append('avatar', file);
        const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
        const res = await fetch('/api/v1/profile/avatar', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: form,
        });
        const body = await res.json();
        setUploadingAvatar(false);
        if (!res.ok) {
            const field = body.errors?.avatar?.[0];
            setAvatarError(field || body.message || 'The picture was not saved.');
            return;
        }
        setAvatarUrl(body.data?.avatar_url || '');
    };

    return (
        <AppLayout>
            <Head title={`${data.gamer_tag || user.name} - Gamer Profile`} />

            <div className="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Profile Header Banner */}
                <header className="flex items-start gap-4 border-b border-slate-800 pb-6">
                    <Avatar name={data.gamer_tag || user.name} src={avatarUrl} size="lg" />
                    <div className="min-w-0">
                    <h1 className="break-words font-display text-3xl text-white sm:text-5xl">{data.gamer_tag || user.name}</h1>
                    <p className="mt-2 text-sm text-slate-300">
                        {user.name}
                        {data.university ? ` · ${data.university}` : ''}
                        {profile?.student_verified ? ' · Verified student' : ''}
                        {user.roles[0] ? ` · ${user.roles[0].replace('_', ' ')}` : ''}
                    </p>
                    {data.bio && <p className="mt-4 max-w-2xl text-sm leading-relaxed text-slate-300">{data.bio}</p>}
                    <p className="mt-4 text-sm text-slate-400">
                        {passport.tournament_count} entered · {passport.verified_result_count} confirmed results
                        {passport.rating !== null ? ` · rating ${passport.rating}` : ''}
                    </p>
                    <label className="mt-4 inline-flex min-h-11 cursor-pointer items-center text-sm text-lime-300">
                        {uploadingAvatar ? 'Uploading picture…' : 'Change profile picture'}
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="sr-only"
                            onChange={(event) => {
                                const file = event.target.files?.[0];
                                if (file) uploadAvatar(file);
                            }}
                        />
                    </label>
                    {avatarError && <p className="mt-2 text-sm text-rose-300" role="alert">{avatarError}</p>}
                    </div>
                </header>

                {/* Profile Edit Form & Info */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left: Settings Form */}
                    <div className="lg:col-span-2">
                        <Card className="p-6">
                            <h2 className="text-lg font-bold text-white mb-1">
                                Edit Profile & Gaming Identity
                            </h2>
                            <p className="text-xs text-slate-400 mb-6">
                                Update your in-game alias, campus affiliation, and public settings
                            </p>

                            <form onSubmit={submit} className="space-y-4">
                                <Input
                                    label={t('auth.gamer_tag')}
                                    type="text"
                                    value={data.gamer_tag}
                                    onChange={(e) => setData('gamer_tag', e.target.value)}
                                    error={errors.gamer_tag}
                                    placeholder="HabeshaSniper"
                                    required
                                />

                                <div className="space-y-1.5 text-left">
                                    <label className="block text-sm text-slate-300">
                                        {t('profile.bio')}
                                    </label>
                                    <textarea
                                        rows={3}
                                        value={data.bio}
                                        onChange={(e) => setData('bio', e.target.value)}
                                        className="w-full border border-slate-700 bg-transparent p-3 text-sm text-slate-100 focus:border-lime-300 focus:outline-none"
                                        placeholder="Tell other students what games you play, your ranks, and tournament experience..."
                                    />
                                    {errors.bio && <p className="text-xs text-rose-400">{errors.bio}</p>}
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <Input
                                        label={t('auth.university')}
                                        type="text"
                                        value={data.university}
                                        onChange={(e) => setData('university', e.target.value)}
                                        error={errors.university}
                                        placeholder="Addis Ababa University"
                                    />

                                    <Input
                                        label={t('profile.student_id')}
                                        type="text"
                                        value={data.school_id_number}
                                        onChange={(e) => setData('school_id_number', e.target.value)}
                                        error={errors.school_id_number}
                                        placeholder="UGR/1234/15"
                                        helperText="Used for university tournaments eligibility"
                                    />
                                </div>

                                <div className="pt-2 flex items-center justify-between border-t border-slate-800">
                                    <label className="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_public}
                                            onChange={(e) => setData('is_public', e.target.checked)}
                                            className="w-4 h-4 rounded border-slate-700 bg-slate-900 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                        />
                                        <span>{t('profile.visibility')}</span>
                                    </label>

                                    <div className="flex items-center gap-3">
                                        {recentlySuccessful && (
                                            <span className="text-xs text-emerald-400 font-medium flex items-center gap-1">
                                                <CheckCircle2 className="w-3.5 h-3.5" />
                                                Saved!
                                            </span>
                                        )}
                                        <Button
                                            type="submit"
                                            isLoading={processing}
                                            rightIcon={<Save className="w-4 h-4" />}
                                        >
                                            {t('common.save')}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </Card>
                    </div>

                    {/* Right: Verification Status & Stats */}
                    <div className="space-y-6">
                        <Card className="p-6">
                            <h3 className="text-sm font-bold text-slate-100 uppercase tracking-wider mb-3">
                                Verification Status
                            </h3>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between text-xs py-2 border-b border-slate-800">
                                    <span className="text-slate-400">Email Verification</span>
                                    {user.email_verified ? (
                                        <span className="text-emerald-400 font-bold">Verified</span>
                                    ) : (
                                        <span className="text-amber-400">{user.email ? 'Not verified' : 'Not provided'}</span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between text-xs py-2 border-b border-slate-800">
                                    <span className="text-slate-400">Phone Verification</span>
                                    {user.phone_verified ? (
                                        <span className="text-emerald-400 font-bold">Verified</span>
                                    ) : (
                                        <span className="text-amber-400">{user.phone ? 'Not verified' : 'Not provided'}</span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between text-xs py-2">
                                    <span className="text-slate-400">Student ID Check</span>
                                    {profile?.student_verified ? (
                                        <span className="text-emerald-400 font-bold">Official Student</span>
                                    ) : (
                                        <span className="text-slate-500">Unverified</span>
                                    )}
                                </div>
                            </div>
                        </Card>

                        <Card className="p-6 bg-slate-900/60">
                            <h3 className="text-sm font-bold text-slate-100 uppercase tracking-wider mb-3">
                                Quick Stats
                            </h3>
                            <div className="grid grid-cols-2 gap-3 text-center">
                                <div className="p-3 bg-slate-900 rounded-lg border border-slate-800">
                                    <div className="text-xl font-bold text-emerald-400">{passport.tournament_count}</div>
                                    <div className="text-[10px] text-slate-400 uppercase mt-0.5">Tournaments</div>
                                </div>
                                <div className="p-3 bg-slate-900 rounded-lg border border-slate-800">
                                    <div className="text-xl font-bold text-amber-400">{passport.rating ?? '—'}</div>
                                    <div className="text-[10px] text-slate-400 uppercase mt-0.5">Rating</div>
                                </div>
                            </div>
                            <p className="mt-3 text-[11px] text-slate-500">
                                {passport.verified_result_count} verified placement{passport.verified_result_count === 1 ? '' : 's'}. Pending registrations are not counted as results.
                            </p>
                            {profile?.is_public && (
                                <a href={`/players/${encodeURIComponent(profile.gamer_tag)}`} className="mt-3 inline-block text-xs font-semibold text-emerald-400 hover:underline">
                                    Public player passport
                                </a>
                            )}
                        </Card>

                        <Card className="p-6">
                            <h3 className="text-sm font-bold text-slate-100 uppercase tracking-wider mb-3">
                                Competition record
                            </h3>
                            {passport.results.length === 0 ? (
                                <p className="text-xs text-slate-400">No approved tournament registrations yet.</p>
                            ) : (
                                <ul className="space-y-2">
                                    {passport.results.map((result) => (
                                        <li key={result.tournament_id} className="flex items-center justify-between gap-3 text-xs border-b border-slate-800 py-2">
                                            <span className="text-slate-200">{result.title}</span>
                                            <span className="text-slate-400 shrink-0">
                                                {result.placement ? `Place ${result.placement}` : 'Result pending'}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
