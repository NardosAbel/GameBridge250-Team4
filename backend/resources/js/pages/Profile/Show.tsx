import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import {
    User,
    ShieldCheck,
    GraduationCap,
    Gamepad2,
    Globe,
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

export default function Show({ user, profile }: ProfileProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        gamer_tag: profile?.gamer_tag || '',
        bio: profile?.bio || '',
        university: profile?.university || '',
        school_id_number: profile?.school_id_number || '',
        is_public: profile?.is_public ?? true,
        locale: user.locale || 'en',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/profile');
    };

    return (
        <AppLayout>
            <Head title={`${data.gamer_tag || user.name} - Gamer Profile`} />

            <div className="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Profile Header Banner */}
                <div className="relative rounded-2xl overflow-hidden bg-gradient-to-r from-emerald-900/60 via-slate-900 to-indigo-900/60 border border-slate-800 p-8 shadow-xl">
                    <div className="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                        <div className="w-24 h-24 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-slate-950 flex items-center justify-center font-extrabold text-3xl shadow-2xl">
                            {data.gamer_tag.substring(0, 2).toUpperCase() || user.name.substring(0, 2).toUpperCase()}
                        </div>

                        <div className="space-y-2 flex-1">
                            <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <h1 className="text-3xl font-extrabold text-white">
                                    {data.gamer_tag || user.name}
                                </h1>
                                {profile?.student_verified ? (
                                    <Badge variant="success" size="md" className="flex items-center gap-1">
                                        <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                                        <span>Verified Student</span>
                                    </Badge>
                                ) : (
                                    <Badge variant="neutral" size="md">
                                        Student ID Pending
                                    </Badge>
                                )}
                                {user.roles[0] && (
                                    <Badge variant="accent" size="md">
                                        {user.roles[0].replace('_', ' ')}
                                    </Badge>
                                )}
                            </div>

                            <p className="text-sm text-slate-300 font-medium">
                                {user.name} • {data.university || 'No campus selected'}
                            </p>

                            <p className="text-xs text-slate-400 max-w-xl">
                                {data.bio || 'No bio written yet. Share your gaming journey and achievements.'}
                            </p>
                        </div>
                    </div>
                </div>

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
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                                        {t('profile.bio')}
                                    </label>
                                    <textarea
                                        rows={3}
                                        value={data.bio}
                                        onChange={(e) => setData('bio', e.target.value)}
                                        className="w-full rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-100 text-sm p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
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
                                    {user.email ? (
                                        <span className="text-emerald-400 font-bold">Verified</span>
                                    ) : (
                                        <span className="text-amber-400">Not provided</span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between text-xs py-2 border-b border-slate-800">
                                    <span className="text-slate-400">Phone Verification</span>
                                    {user.phone ? (
                                        <span className="text-emerald-400 font-bold">Verified</span>
                                    ) : (
                                        <span className="text-amber-400">Not provided</span>
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
                                    <div className="text-xl font-bold text-emerald-400">0</div>
                                    <div className="text-[10px] text-slate-400 uppercase mt-0.5">Tournaments</div>
                                </div>
                                <div className="p-3 bg-slate-900 rounded-lg border border-slate-800">
                                    <div className="text-xl font-bold text-amber-400">1000</div>
                                    <div className="text-[10px] text-slate-400 uppercase mt-0.5">Rating</div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
