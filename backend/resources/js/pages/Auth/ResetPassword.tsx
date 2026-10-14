import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { Lock, KeyRound } from 'lucide-react';
import { useTranslation } from '../../hooks/useTranslation';

interface ResetPasswordProps {
    recipient?: string;
}

export default function ResetPassword({ recipient = '' }: ResetPasswordProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        recipient: recipient,
        code: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/reset-password', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AppLayout>
            <Head title="Reset Password - Campus Esports Ethiopia" />

            <div className="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
                <Card className="border-slate-800 bg-slate-900/90 shadow-2xl">
                    <div className="text-center mb-6">
                        <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                            <KeyRound className="w-6 h-6" />
                        </div>
                        <h1 className="text-2xl font-extrabold text-white">
                            {t('auth.reset_password_title')}
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            {t('auth.reset_password_sub')}
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <Input
                            label="Email or Phone Number"
                            type="text"
                            value={data.recipient}
                            onChange={(e) => setData('recipient', e.target.value)}
                            error={errors.recipient}
                            required
                        />

                        <Input
                            label="6-Digit Reset Code"
                            type="text"
                            maxLength={6}
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            error={errors.code}
                            placeholder="123456"
                            className="font-mono tracking-widest text-center"
                            required
                        />

                        <Input
                            label="New Password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            leftIcon={<Lock className="w-4 h-4" />}
                            placeholder="Min. 8 characters"
                            required
                        />

                        <Input
                            label="Confirm New Password"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            error={errors.password_confirmation}
                            leftIcon={<Lock className="w-4 h-4" />}
                            placeholder="Repeat new password"
                            required
                        />

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full mt-2 font-bold"
                            isLoading={processing}
                        >
                            Reset Password & Sign In
                        </Button>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
