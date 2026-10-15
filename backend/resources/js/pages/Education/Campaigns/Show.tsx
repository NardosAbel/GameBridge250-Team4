import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';
import {
    ShieldCheck,
    Share2,
    Download,
    Award,
    Building2,
    Calendar,
    ArrowLeft,
    CheckCircle2,
    Globe,
    ExternalLink
} from 'lucide-react';

interface CampaignData {
    id: number;
    uuid: string;
    title: string;
    title_am?: string;
    slug: string;
    category: string;
    description?: string;
    description_am?: string;
    banner_url?: string;
    content_blocks?: Array<{
        type: string;
        title: string;
        items?: string[];
        body?: string;
    }>;
    shareable_assets?: Array<{
        type: string;
        title: string;
        download_url: string;
    }>;
    linked_course_id?: number;
    linked_quiz_id?: number;
    linkedCourse?: {
        id: number;
        title: string;
    } | null;
    linkedQuiz?: {
        id: number;
        title: string;
    } | null;
    target_institutions?: string[];
    analytics?: {
        views_count?: number;
        shares_count?: number;
        quiz_completions?: number;
    };
}

interface Props {
    campaignId: string | number;
}

export default function CampaignShow({ campaignId }: Props) {
    const [campaign, setCampaign] = useState<CampaignData | null>(null);
    const [loading, setLoading] = useState(true);
    const [locale, setLocale] = useState<'en' | 'am'>('en');
    const [copied, setCopied] = useState(false);
    const [shareCount, setShareCount] = useState(0);

    useEffect(() => {
        fetchCampaign();
    }, [campaignId]);

    const fetchCampaign = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/campaigns/${campaignId}`);
            const json = await res.json();
            if (json.success) {
                setCampaign(json.data);
                setShareCount(json.data.analytics?.shares_count || 0);
            }
        } catch (err) {
            console.error('Failed to load campaign', err);
        } finally {
            setLoading(false);
        }
    };

    const handleShare = async (platform: string) => {
        if (!campaign) return;
        try {
            const res = await fetch(`/api/v1/campaigns/${campaign.id}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ platform }),
            });
            const json = await res.json();
            if (json.success) {
                setShareCount(json.data.analytics.shares_count);
            }
        } catch (e) {
            console.error('Share record error', e);
        }

        const shareUrl = window.location.href;
        if (platform === 'copy') {
            navigator.clipboard.writeText(shareUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } else if (platform === 'telegram') {
            window.open(`https://t.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(campaign.title)}`, '_blank');
        } else if (platform === 'twitter') {
            window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(campaign.title)}`, '_blank');
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="max-w-4xl mx-auto px-4 py-12 animate-pulse space-y-6">
                    <div className="h-64 bg-slate-900 rounded-2xl" />
                </div>
            </AppLayout>
        );
    }

    if (!campaign) {
        return (
            <AppLayout>
                <div className="max-w-4xl mx-auto px-4 py-16 text-center text-white">
                    <h2 className="text-xl font-bold">Campaign Not Found</h2>
                    <Link href="/education" className="text-indigo-400 mt-4 inline-block">
                        ← Back to Hub
                    </Link>
                </div>
            </AppLayout>
        );
    }

    const title = locale === 'am' && campaign.title_am ? campaign.title_am : campaign.title;
    const desc = locale === 'am' && campaign.description_am ? campaign.description_am : campaign.description;

    return (
        <AppLayout>
            <Head title={`${title} | Digital Awareness Campaign`} />

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header controls */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/education"
                        className="inline-flex items-center text-sm font-medium text-slate-400 hover:text-white transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1.5" />
                        Back to Learning Hub
                    </Link>

                    <div className="flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700">
                        <button
                            onClick={() => setLocale('en')}
                            className={`px-3 py-1 rounded text-xs font-semibold ${locale === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-400'}`}
                        >
                            EN
                        </button>
                        <button
                            onClick={() => setLocale('am')}
                            className={`px-3 py-1 rounded text-xs font-semibold ${locale === 'am' ? 'bg-indigo-600 text-white' : 'text-slate-400'}`}
                        >
                            አማርኛ
                        </button>
                    </div>
                </div>

                {/* Hero Banner Card */}
                <div className="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                    <div className="h-64 sm:h-80 w-full relative overflow-hidden bg-slate-800">
                        {campaign.banner_url ? (
                            <img src={campaign.banner_url} alt={campaign.title} className="w-full h-full object-cover" />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-950 to-slate-900">
                                <ShieldCheck className="w-20 h-20 text-indigo-500/30" />
                            </div>
                        )}
                        <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent" />
                        <div className="absolute bottom-6 left-6 right-6 space-y-2">
                            <Badge className="bg-indigo-600 text-white">
                                {campaign.category.replace('_', ' ').toUpperCase()}
                            </Badge>
                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                                {title}
                            </h1>
                        </div>
                    </div>

                    <div className="p-6 sm:p-8 space-y-6">
                        <p className="text-slate-300 text-base sm:text-lg leading-relaxed">
                            {desc}
                        </p>

                        {/* Targeted Universities */}
                        {campaign.target_institutions && campaign.target_institutions.length > 0 && (
                            <div className="space-y-2">
                                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center">
                                    <Building2 className="w-3.5 h-3.5 mr-1 text-indigo-400" />
                                    Participating Campus Chapters
                                </h4>
                                <div className="flex flex-wrap gap-2">
                                    {campaign.target_institutions.map((inst, i) => (
                                        <Badge key={i} variant="outline" className="border-slate-700 text-slate-300">
                                            {inst}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Content Blocks */}
                        {campaign.content_blocks?.map((block, idx) => (
                            <div key={idx} className="p-6 bg-slate-950 rounded-2xl border border-slate-800 space-y-4">
                                <h3 className="text-lg font-bold text-white flex items-center">
                                    <ShieldCheck className="w-5 h-5 text-emerald-400 mr-2" />
                                    {block.title}
                                </h3>
                                {block.items && (
                                    <ul className="space-y-2">
                                        {block.items.map((item, itemIdx) => (
                                            <li key={itemIdx} className="flex items-start text-sm text-slate-300">
                                                <CheckCircle2 className="w-4 h-4 text-indigo-400 mr-2 mt-0.5 flex-shrink-0" />
                                                <span>{item}</span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ))}

                        {/* Share & Call-to-action */}
                        <div className="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div className="flex items-center space-x-2">
                                <Button
                                    size="sm"
                                    onClick={() => handleShare('telegram')}
                                    className="bg-sky-600 hover:bg-sky-500 text-white text-xs"
                                >
                                    Share on Telegram
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() => handleShare('twitter')}
                                    className="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs"
                                >
                                    Share on X
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleShare('copy')}
                                    className="border-slate-700 text-slate-300 text-xs"
                                >
                                    {copied ? 'Link Copied!' : 'Copy Link'}
                                </Button>
                                <span className="text-xs text-slate-500 ml-2">
                                    {shareCount} total shares
                                </span>
                            </div>

                            {campaign.linked_quiz_id && (
                                <Link href={`/education/quizzes/${campaign.linked_quiz_id}`}>
                                    <Button className="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 text-white font-bold text-xs">
                                        <Award className="w-4 h-4 mr-1.5 text-amber-300" />
                                        Take Campaign Quiz
                                    </Button>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
