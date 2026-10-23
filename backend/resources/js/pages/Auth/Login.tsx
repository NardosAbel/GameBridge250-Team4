import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { useTranslation } from '../../hooks/useTranslation';

export default function Login({ status }: { status?: string }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AppLayout>
            <Head title="Log In - Campus Esports Ethiopia" />

            <div className="mx-auto max-w-md px-4 py-16">
                <div>
                    <p className="font-display text-sm text-lime-300">GameBridge</p>
                    <h1 className="mt-2 font-display text-4xl text-white">
                        {t('auth.welcome_back')}
                    </h1>
                    <p className="mt-2 text-sm text-slate-400">
                        {t('auth.login_sub')}
                    </p>
                </div>

                    {status && (
                        <div className="mb-4 text-sm text-lime-300">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <Input
                            label={t('auth.email_or_phone')}
                            type="text"
                            value={data.login}
                            onChange={(e) => setData('login', e.target.value)}
                            error={errors.login}
                            placeholder="Email or +2519..."
                            required
                        />

                        <Input
                            label={t('auth.password')}
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            placeholder="Password"
                            required
                        />

                        <div className="flex items-center justify-between text-xs pt-1">
                            <label className="flex items-center gap-2 text-slate-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 border-slate-600 bg-transparent accent-lime-300"
                                />
                                <span>{t('auth.remember_me')}</span>
                            </label>

                            <Link
                                href="/forgot-password"
                                className="text-lime-300 hover:underline"
                            >
                                {t('auth.forgot_password')}
                            </Link>
                        </div>

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full mt-4 font-bold"
                            isLoading={processing}
                        >
                            {t('common.login')}
                        </Button>
                    </form>

                    <p className="text-sm text-slate-400">
                        <span>{t('auth.dont_have_account')} </span>
                        <Link href="/register" className="text-lime-300 hover:underline">
                            {t('common.register')}
                        </Link>
                    </p>
            </div>
        </AppLayout>
    );
}
