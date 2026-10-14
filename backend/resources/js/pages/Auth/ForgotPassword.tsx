import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { KeyRound, Mail, ArrowLeft } from 'lucide-react';
import { useTranslation } from '../../hooks/useTranslation';

export default function ForgotPassword() {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        recipient: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <AppLayout>
            <Head title="Forgot Password - Campus Esports Ethiopia" />

            <div className="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
                <Card className="border-slate-800 bg-slate-900/90 shadow-2xl">
                    <div className="text-center mb-6">
                        <div className="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto mb-3">
                            <KeyRound className="w-6 h-6" />
                        </div>
                        <h1 className="text-2xl font-extrabold text-white">
                            Reset Password
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            Enter your verified email or phone number to receive a 6-digit password reset OTP.
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <Input
                            label={t('auth.email_or_phone')}
                            type="text"
                            value={data.recipient}
                            onChange={(e) => setData('recipient', e.target.value)}
                            error={errors.recipient}
                            leftIcon={<Mail className="w-4 h-4" />}
                            placeholder="Email or +2519..."
                            required
                        />

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full mt-2 font-bold"
                            isLoading={processing}
                        >
                            Send Reset Code
                        </Button>
                    </form>

                    <div className="mt-6 text-center text-xs text-slate-400 border-t border-slate-800 pt-4">
                        <Link href="/login" className="text-emerald-400 font-semibold hover:underline inline-flex items-center gap-1.5">
                            <ArrowLeft className="w-3.5 h-3.5" />
                            <span>Back to Log In</span>
                        </Link>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
