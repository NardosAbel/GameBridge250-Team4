import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import {
    Sparkles,
    TrendingUp,
    Heart,
    MessageCircle,
    Share2,
    Users,
    Gamepad2,
    Briefcase,
    Building2,
    CheckCircle2,
    ArrowUpRight
} from 'lucide-react';

interface CreatorInsights {
    creator_id: number;
    creator_name: string;
    gamer_tag: string;
    content_metrics: {
        total_posts: number;
        total_likes: number;
        total_comments: number;
        total_shares: number;
        total_engagements: number;
        avg_engagement_per_post: number;
    };
    audience_insights: {
        total_followers: number;
        primary_university: string;
        preferred_games: string[];
    };
    sponsored_opportunities: {
        id: number;
        title: string;
        price: number;
        currency: string;
        tier: string;
        duration_days: number;
    }[];
}

export default function CreatorDashboard() {
    const [insights, setInsights] = useState<CreatorInsights | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchInsights();
    }, []);

    const fetchInsights = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/creator/insights');
            const data = await res.json();
            if (data.success) {
                setInsights(data.data);
            }
        } catch (err) {
            console.error('Failed to load creator insights', err);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Creator Tools & Content Performance Analytics" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-pink-400 uppercase tracking-wider mb-1">
                            <Sparkles className="w-4 h-4" />
                            Creator Studio & Media Hub
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                            Creator Analytics & Brand Deals
                        </h1>
                    </div>

                    <Link href="/feed">
                        <Button variant="outline" className="text-xs">
                            Create New Post
                        </Button>
                    </Link>
                </div>

                {loading ? (
                    <div className="py-20 text-center text-slate-400 space-y-3">
                        <div className="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto" />
                        <p className="text-sm">Aggregating creator performance metrics...</p>
                    </div>
                ) : !insights ? (
                    <Card className="bg-slate-900/60 border-slate-800 p-8 text-center text-slate-400">
                        Failed to load creator analytics.
                    </Card>
                ) : (
                    <div className="space-y-8">
                        {/* Profile Bar */}
                        <div className="bg-gradient-to-r from-slate-900 via-slate-900 to-purple-950/40 rounded-2xl p-6 border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 text-white font-black text-xl flex items-center justify-center shadow-lg shadow-purple-900/40">
                                    {insights.gamer_tag.substring(0, 2).toUpperCase()}
                                </div>
                                <div>
                                    <h2 className="text-xl font-bold text-white flex items-center gap-2">
                                        {insights.gamer_tag}
                                        <Badge variant="success" className="text-[10px] uppercase font-bold">Creator</Badge>
                                    </h2>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Campus Esports Creator Affiliate • {insights.audience_insights.primary_university}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-6">
                                <div className="text-right sm:text-left">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Followers</div>
                                    <div className="text-2xl font-black text-white">{insights.audience_insights.total_followers.toLocaleString()}</div>
                                </div>
                                <div className="text-right sm:text-left">
                                    <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Avg Engagement</div>
                                    <div className="text-2xl font-black text-emerald-400">{insights.content_metrics.avg_engagement_per_post}</div>
                                </div>
                            </div>
                        </div>

                        {/* Content Engagement KPIs */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <TrendingUp className="w-3.5 h-3.5 text-blue-400" />
                                    Total Posts
                                </div>
                                <div className="text-2xl font-black text-white">
                                    {insights.content_metrics.total_posts}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">Collegiate clips & updates</div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Heart className="w-3.5 h-3.5 text-rose-400" />
                                    Total Likes
                                </div>
                                <div className="text-2xl font-black text-rose-400">
                                    {insights.content_metrics.total_likes.toLocaleString()}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">Community reactions</div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <MessageCircle className="w-3.5 h-3.5 text-cyan-400" />
                                    Comments
                                </div>
                                <div className="text-2xl font-black text-white">
                                    {insights.content_metrics.total_comments.toLocaleString()}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">Discussion threads</div>
                            </Card>

                            <Card className="bg-slate-900/60 border-slate-800 p-4">
                                <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Share2 className="w-3.5 h-3.5 text-amber-400" />
                                    Post Shares
                                </div>
                                <div className="text-2xl font-black text-amber-400">
                                    {insights.content_metrics.total_shares.toLocaleString()}
                                </div>
                                <div className="text-[10px] text-slate-500 mt-1">Organic social reach</div>
                            </Card>
                        </div>

                        {/* Sponsored Opportunities tailored for creators */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div className="lg:col-span-2 space-y-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                        <Briefcase className="w-4 h-4 text-emerald-400" />
                                        Sponsored Brand Deals For Creators
                                    </h3>
                                    <Link href="/sponsors" className="text-xs text-emerald-400 hover:underline">
                                        View All in Marketplace
                                    </Link>
                                </div>

                                <div className="space-y-3">
                                    {insights.sponsored_opportunities.length === 0 ? (
                                        <Card className="bg-slate-900/40 border-slate-800 p-6 text-center text-xs text-slate-400">
                                            No active creator packages currently open. Check back soon!
                                        </Card>
                                    ) : (
                                        insights.sponsored_opportunities.map((deal) => (
                                            <Card key={deal.id} className="bg-slate-900/60 border-slate-800 p-4 flex items-center justify-between hover:border-slate-700 transition-all">
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="info" className="uppercase text-[10px]">{deal.tier}</Badge>
                                                        <h4 className="font-bold text-white text-sm">{deal.title}</h4>
                                                    </div>
                                                    <div className="text-xs text-slate-400">
                                                        Duration: {deal.duration_days} days • Stream & Clip placements
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-4">
                                                    <div className="text-right">
                                                        <div className="text-sm font-black text-emerald-400">
                                                            {Number(deal.price).toLocaleString()} {deal.currency}
                                                        </div>
                                                        <div className="text-[10px] text-slate-500">Fixed compensation</div>
                                                    </div>

                                                    <Link href={`/sponsors/dashboard?package_id=${deal.id}`}>
                                                        <Button size="sm" variant="primary" className="text-xs">
                                                            Apply
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </Card>
                                        ))
                                    )}
                                </div>
                            </div>

                            {/* Audience Breakdown */}
                            <Card className="bg-slate-900/60 border-slate-800 h-fit">
                                <CardHeader className="pb-3">
                                    <h3 className="font-bold text-white text-base flex items-center gap-2">
                                        <Gamepad2 className="w-4 h-4 text-purple-400" />
                                        Top Game Communities
                                    </h3>
                                </CardHeader>
                                <CardBody className="space-y-3 pt-0">
                                    <p className="text-xs text-slate-400">
                                        Games where your audience is most engaged across campus leagues:
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {insights.audience_insights.preferred_games.map((game, idx) => (
                                            <span key={idx} className="px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700 text-xs font-semibold text-slate-200">
                                                {game}
                                            </span>
                                        ))}
                                    </div>

                                    <div className="pt-4 border-t border-slate-800">
                                        <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                                            Home Campus Network
                                        </div>
                                        <div className="text-sm font-bold text-white flex items-center gap-1.5">
                                            <Building2 className="w-4 h-4 text-emerald-400" />
                                            {insights.audience_insights.primary_university}
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
