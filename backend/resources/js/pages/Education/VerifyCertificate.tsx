import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Badge } from '../../components/ui/Badge';
import {
    Award,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Search,
    Building2,
    Calendar,
    Sparkles,
    ArrowLeft
} from 'lucide-react';

interface VerifiedCert {
    certificate_number: string;
    verification_code: string;
    verification_hash: string;
    issued_at: string;
    student_name: string;
    university: string;
    course_title: string;
    course_title_am?: string;
    score: number;
    issuer: string;
}

export default function VerifyCertificate() {
    const [code, setCode] = useState('');
    const [verifying, setVerifying] = useState(false);
    const [cert, setCert] = useState<VerifiedCert | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const codeParam = urlParams.get('code');
        if (codeParam) {
            setCode(codeParam);
            performVerification(codeParam);
        }
    }, []);

    const performVerification = async (searchCode: string) => {
        if (!searchCode.trim()) return;
        setVerifying(true);
        setError(null);
        setCert(null);

        try {
            const res = await fetch(`/api/v1/certificates/verify/${encodeURIComponent(searchCode.trim())}`);
            const json = await res.json();
            if (json.success && json.data?.certificate) {
                setCert(json.data.certificate);
            } else {
                setError(json.message || 'No certificate found matching this verification code.');
            }
        } catch (err) {
            setError('Verification service unavailable. Please check the code and try again.');
        } finally {
            setVerifying(false);
        }
    };

    const handleFormSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        performVerification(code);
    };

    return (
        <AppLayout>
            <Head title="Public Certificate Verification Portal | Campus Esports Ethiopia" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
                <Link
                    href="/education"
                    className="inline-flex items-center text-sm font-medium text-slate-400 hover:text-white transition"
                >
                    <ArrowLeft className="w-4 h-4 mr-1.5" />
                    Back to Learning Hub
                </Link>

                <div className="text-center space-y-3">
                    <div className="w-16 h-16 bg-amber-500/10 text-amber-400 border border-amber-500/30 rounded-2xl flex items-center justify-center mx-auto shadow-lg">
                        <Award className="w-8 h-8" />
                    </div>
                    <h1 className="text-3xl font-extrabold text-white">Certificate Verification Portal</h1>
                    <p className="text-slate-400 text-sm max-w-md mx-auto">
                        Verify the authenticity of digital awareness credentials issued by Campus Esports Ethiopia.
                    </p>
                </div>

                {/* Search Bar */}
                <form onSubmit={handleFormSubmit} className="flex gap-3 bg-slate-900 p-2 rounded-2xl border border-slate-800 shadow-xl">
                    <div className="relative flex-1">
                        <Search className="absolute left-3.5 top-3 w-4 h-4 text-slate-400" />
                        <Input
                            placeholder="Enter Verification Code (e.g. CEE-V8K9-M2P4) or Certificate #"
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            className="pl-10 bg-transparent border-none text-white focus:ring-0 text-sm"
                        />
                    </div>
                    <Button
                        type="submit"
                        disabled={verifying || !code.trim()}
                        className="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6"
                    >
                        {verifying ? 'Verifying...' : 'Verify'}
                    </Button>
                </form>

                {/* Error Banner */}
                {error && (
                    <div className="p-4 bg-red-950/40 border border-red-800 rounded-2xl flex items-center space-x-3 text-red-300 text-sm">
                        <XCircle className="w-5 h-5 flex-shrink-0 text-red-400" />
                        <span>{error}</span>
                    </div>
                )}

                {/* Official Verified Certificate Card */}
                {cert && (
                    <div className="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border-2 border-amber-500/40 rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden space-y-6">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-5">
                            <div className="flex items-center space-x-3">
                                <span className="p-2.5 bg-emerald-950 text-emerald-400 border border-emerald-800 rounded-xl">
                                    <ShieldCheck className="w-6 h-6" />
                                </span>
                                <div>
                                    <Badge className="bg-emerald-900/80 text-emerald-300 border border-emerald-700">
                                        OFFICIALLY VERIFIED
                                    </Badge>
                                    <div className="text-xs text-slate-400 mt-1">Campus Esports Ethiopia Academy</div>
                                </div>
                            </div>

                            <span className="font-mono text-xs sm:text-sm font-bold text-amber-400 bg-amber-950/40 px-3 py-1.5 rounded-lg border border-amber-800/40">
                                {cert.verification_code}
                            </span>
                        </div>

                        <div className="space-y-4 text-center py-4">
                            <div className="text-xs uppercase tracking-widest text-slate-400 font-semibold">
                                This Credential Is Proudly Awarded To
                            </div>
                            <div className="text-2xl sm:text-3xl font-extrabold text-white">
                                {cert.student_name}
                            </div>
                            <div className="text-sm font-semibold text-indigo-300 flex items-center justify-center">
                                <Building2 className="w-4 h-4 mr-1.5 text-indigo-400" />
                                {cert.university}
                            </div>
                            <div className="max-w-md mx-auto text-xs text-slate-400">
                                for demonstrated mastery and successful completion of
                            </div>
                            <div className="text-lg sm:text-xl font-bold text-amber-200">
                                {cert.course_title}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-800 text-xs">
                            <div>
                                <span className="text-slate-400 block">Certificate #</span>
                                <span className="font-mono text-slate-200 font-semibold">{cert.certificate_number}</span>
                            </div>
                            <div>
                                <span className="text-slate-400 block">Issue Date</span>
                                <span className="text-slate-200 font-semibold">
                                    {new Date(cert.issued_at).toLocaleDateString()}
                                </span>
                            </div>
                            <div className="col-span-2 sm:col-span-1">
                                <span className="text-slate-400 block">Score / Grade</span>
                                <span className="text-emerald-400 font-extrabold">{cert.score}% (Pass with Distinction)</span>
                            </div>
                        </div>

                        <div className="p-3 bg-slate-950 rounded-xl border border-slate-800/60 text-[10px] text-slate-500 font-mono break-all">
                            SHA-256 Hash: {cert.verification_hash}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
