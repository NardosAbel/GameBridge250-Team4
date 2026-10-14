import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { LogIn, User, Lock } from 'lucide-react';
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

            <div className="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
                <Card className="border-slate-800 bg-slate-900/90 shadow-2xl">
                    <div className="text-center mb-6">
                        <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                            <LogIn className="w-6 h-6" />
                        </div>
                        <h1 className="text-2xl font-extrabold text-white">
                            {t('auth.welcome_back')}
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            {t('auth.login_sub')}
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 text-xs font-medium text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 p-3 rounded-lg text-center">
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
                            leftIcon={<User className="w-4 h-4" />}
                            placeholder="Email or +2519..."
                            required
                        />

                        <Input
                            label={t('auth.password')}
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            leftIcon={<Lock className="w-4 h-4" />}
                            placeholder="Enter password"
                            required
                        />

                        <div className="flex items-center justify-between text-xs pt-1">
                            <label className="flex items-center gap-2 text-slate-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="w-4 h-4 rounded border-slate-700 bg-slate-900 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                />
                                <span>{t('auth.remember_me')}</span>
                            </label>

                            <Link
                                href="/forgot-password"
                                className="text-emerald-400 hover:text-emerald-300 hover:underline font-medium"
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

                    <div className="mt-6 text-center text-xs text-slate-400 border-t border-slate-800 pt-4">
                        <span>{t('auth.dont_have_account')} </span>
                        <Link href="/register" className="text-emerald-400 font-semibold hover:underline">
                            {t('common.register')}
                        </Link>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
