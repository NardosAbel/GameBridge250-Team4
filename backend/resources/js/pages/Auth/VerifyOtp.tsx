import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Card } from '../../components/ui/Card';
import { ShieldCheck, RefreshCw } from 'lucide-react';
import { useTranslation } from '../../hooks/useTranslation';

interface VerifyOtpProps {
    recipient?: string;
    type?: string;
    devCode?: string;
}

export default function VerifyOtp({
    recipient = '',
    type = 'email_verification',
    devCode = '',
}: VerifyOtpProps) {
    const { t } = useTranslation();
    const [countdown, setCountdown] = useState(60);

    const { data, setData, post, processing, errors } = useForm({
        recipient: recipient,
        type: type,
        code: devCode || '',
    });

    useEffect(() => {
        let timer: any;
        if (countdown > 0) {
            timer = setTimeout(() => setCountdown(countdown - 1), 1000);
        }
        return () => clearTimeout(timer);
    }, [countdown]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/verify-otp');
    };

    const handleResend = () => {
        if (countdown > 0) return;
        router.post('/resend-otp', { recipient: data.recipient, type: data.type }, {
            onSuccess: () => setCountdown(60),
        });
    };

    return (
        <AppLayout>
            <Head title="Verify Account - Campus Esports Ethiopia" />

            <div className="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
                <Card className="border-slate-800 bg-slate-900/90 shadow-2xl">
                    <div className="text-center mb-6">
                        <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                            <ShieldCheck className="w-6 h-6" />
                        </div>
                        <h1 className="text-2xl font-extrabold text-white">
                            {t('auth.verify_otp_title')}
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            {t('auth.verify_otp_sub')}
                        </p>
                        {data.recipient && (
                            <div className="mt-2 text-xs font-semibold text-emerald-400 bg-slate-800/80 py-1.5 px-3 rounded-full inline-block">
                                {data.recipient}
                            </div>
                        )}
                    </div>

                    {devCode && (
                        <div className="mb-4 text-xs bg-amber-500/10 border border-amber-500/20 text-amber-300 p-3 rounded-lg text-center">
                            Testing Mode Code: <span className="font-mono font-bold">{devCode}</span>
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <Input
                            label="6-Digit Verification Code"
                            type="text"
                            maxLength={6}
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            error={errors.code}
                            placeholder="123456"
                            className="text-center tracking-widest text-lg font-mono font-bold"
                            required
                        />

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full mt-2 font-bold"
                            isLoading={processing}
                        >
                            {t('auth.verify_code')}
                        </Button>
                    </form>

                    <div className="mt-6 text-center text-xs text-slate-400 border-t border-slate-800 pt-4 flex items-center justify-between">
                        <span>Didn't receive the code?</span>
                        <button
                            type="button"
                            onClick={handleResend}
                            disabled={countdown > 0}
                            className="text-emerald-400 hover:text-emerald-300 font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1 cursor-pointer"
                        >
                            <RefreshCw className="w-3.5 h-3.5" />
                            <span>
                                {countdown > 0 ? `Resend in ${countdown}s` : t('auth.resend_code')}
                            </span>
                        </button>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
