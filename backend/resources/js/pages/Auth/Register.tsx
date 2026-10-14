import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { User, Mail, Phone, Lock, Tag, GraduationCap, ShieldAlert } from 'lucide-react';
import { useTranslation } from '../../hooks/useTranslation';

export default function Register() {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        gamer_tag: '',
        university: '',
        password: '',
        password_confirmation: '',
        is_minor: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const universities = [
        'Addis Ababa University (AAU)',
        'Addis Ababa Science & Technology University (AASTU)',
        'Adama Science and Technology University (ASTU)',
        'Hawassa University',
        'Jimma University',
        'Bahir Dar University',
        'Mekelle University',
        'Arba Minch University',
        'Haramaya University',
        'Gondar University',
        'Wollo University',
        'Other / High School',
    ];

    return (
        <AppLayout>
            <Head title="Create Player Account - Campus Esports Ethiopia" />

            <div className="py-12 px-4 sm:px-6 lg:px-8 max-w-lg mx-auto">
                <Card className="border-slate-800 bg-slate-900/90 shadow-2xl">
                    <div className="text-center mb-6">
                        <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                            <Tag className="w-6 h-6" />
                        </div>
                        <h1 className="text-2xl font-extrabold text-white">
                            {t('auth.create_account')}
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            {t('auth.register_sub')}
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <Input
                            label={t('auth.full_name')}
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            leftIcon={<User className="w-4 h-4" />}
                            placeholder="Abebe Kebede"
                            required
                        />

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Input
                                label="Email Address"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                leftIcon={<Mail className="w-4 h-4" />}
                                placeholder="abebe@gmail.com"
                            />

                            <Input
                                label="Phone Number"
                                type="tel"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                error={errors.phone}
                                leftIcon={<Phone className="w-4 h-4" />}
                                placeholder="+251911..."
                            />
                        </div>

                        <Input
                            label={t('auth.gamer_tag')}
                            type="text"
                            value={data.gamer_tag}
                            onChange={(e) => setData('gamer_tag', e.target.value)}
                            error={errors.gamer_tag}
                            leftIcon={<Tag className="w-4 h-4" />}
                            placeholder="e.g. HabeshaSniper"
                            helperText="Letters, numbers, underscores and hyphens only (3-30 chars)"
                            required
                        />

                        <div className="space-y-1.5 text-left">
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                                {t('auth.university')}
                            </label>
                            <div className="relative">
                                <select
                                    value={data.university}
                                    onChange={(e) => setData('university', e.target.value)}
                                    className="w-full rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-100 text-sm py-2.5 px-3.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                                >
                                    <option value="">Select your university / school...</option>
                                    {universities.map((uni, idx) => (
                                        <option key={idx} value={uni}>
                                            {uni}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {errors.university && (
                                <p className="text-xs text-rose-400">{errors.university}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Input
                                label={t('auth.password')}
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                leftIcon={<Lock className="w-4 h-4" />}
                                placeholder="Min. 8 characters"
                                required
                            />

                            <Input
                                label={t('auth.confirm_password')}
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                error={errors.password_confirmation}
                                leftIcon={<Lock className="w-4 h-4" />}
                                placeholder="Re-enter password"
                                required
                            />
                        </div>

                        <div className="flex items-start gap-2.5 pt-2">
                            <input
                                id="is_minor"
                                type="checkbox"
                                checked={data.is_minor}
                                onChange={(e) => setData('is_minor', e.target.checked)}
                                className="mt-1 w-4 h-4 rounded border-slate-700 bg-slate-900 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            />
                            <label htmlFor="is_minor" className="text-xs text-slate-300 leading-snug cursor-pointer">
                                {t('auth.is_minor')}
                            </label>
                        </div>

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full mt-4 font-bold"
                            isLoading={processing}
                        >
                            {t('auth.create_account')}
                        </Button>
                    </form>

                    <div className="mt-6 text-center text-xs text-slate-400 border-t border-slate-800 pt-4">
                        <span>{t('auth.already_have_account')} </span>
                        <Link href="/login" className="text-emerald-400 font-semibold hover:underline">
                            {t('common.login')}
                        </Link>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
