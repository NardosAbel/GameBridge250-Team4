import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    Briefcase,
    Sparkles,
    ShieldCheck,
    TrendingUp,
    ExternalLink,
    Filter,
    CheckCircle2,
    DollarSign,
    Target,
    Layers,
    Search
} from 'lucide-react';

interface BrandProfile {
    id: number;
    company_name: string;
    tagline: string | null;
    industry: string;
    logo_url: string | null;
    website_url: string | null;
    verified: boolean;
}

interface SponsorshipPackage {
    id: number;
    uuid: string;
    title: string;
    target_type: string;
    tier: string;
    price: number;
    currency: string;
    benefits: string[];
    deliverables: { title: string; deliverable_type: string }[] | string[];
    duration_days: number;
    audience_estimate: number;
    creator?: {
        name: string;
    };
}

export default function SponsorsIndex() {
    const [packages, setPackages] = useState<SponsorshipPackage[]>([]);
    const [brands, setBrands] = useState<BrandProfile[]>([]);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<'packages' | 'brands'>('packages');
    const [targetTypeFilter, setTargetTypeFilter] = useState('');
    const [tierFilter, setTierFilter] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        fetchData();
    }, [targetTypeFilter, tierFilter, searchQuery]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const pkgParams = new URLSearchParams();
            if (targetTypeFilter) pkgParams.append('target_type', targetTypeFilter);
            if (tierFilter) pkgParams.append('tier', tierFilter);

            const [pkgRes, brandRes] = await Promise.all([
                fetch(`/api/v1/sponsors/packages?${pkgParams.toString()}`),
                fetch(`/api/v1/sponsors/brands?search=${encodeURIComponent(searchQuery)}`)
            ]);

            const pkgData = await pkgRes.json();
            const brandData = await brandRes.json();

            if (pkgData.success) {
                setPackages(pkgData.data.data || pkgData.data || []);
            }
            if (brandData.success) {
                setBrands(brandData.data.data || brandData.data || []);
            }
        } catch (err) {
            console.error('Failed to load sponsorship data', err);
        } finally {
            setLoading(false);
        }
    };

    const getTierBadge = (tier: string) => {
        switch (tier.toLowerCase()) {
            case 'title':
                return <Badge variant="warning" className="uppercase font-bold tracking-wider">Title Sponsor</Badge>;
            case 'presenting':
                return <Badge variant="info" className="uppercase font-bold tracking-wider">Presenting</Badge>;
            case 'gold':
                return <Badge variant="warning">Gold Tier</Badge>;
            case 'silver':
                return <Badge variant="neutral">Silver Tier</Badge>;
            case 'bronze':
                return <Badge variant="outline">Bronze Tier</Badge>;
            default:
                return <Badge variant="neutral">{tier}</Badge>;
        }
    };

    return (
        <AppLayout>
            <Head title="Sponsorship Marketplace & Brand Partnerships" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Hero Header */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-950/70 via-slate-900 to-amber-950/40 border border-emerald-500/20 p-8 sm:p-10 shadow-2xl">
                    <div className="max-w-3xl space-y-4">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold uppercase tracking-wider">
                            <Sparkles className="w-3.5 h-3.5" />
                            Campus Esports Ethiopia Brand Ecosystem
                        </div>
                        <h1 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Sponsorship Marketplace & Brand Partnerships
                        </h1>
                        <p className="text-slate-300 text-base leading-relaxed">
                            Connect national telecommunications, tech brands, and regional sponsors with verified university collegiate tournaments, student esports clubs, and digital awareness campaigns.
                        </p>
                        <div className="flex flex-wrap items-center gap-4 pt-2">
                            <Link href="/sponsors/dashboard">
                                <Button variant="primary" className="flex items-center gap-2 shadow-lg shadow-emerald-900/40">
                                    <TrendingUp className="w-4 h-4" />
                                    Sponsor Performance Dashboard
                                </Button>
                            </Link>
                            <Link href="/creator/dashboard">
                                <Button variant="outline" className="flex items-center gap-2 border-slate-700 hover:border-slate-500">
                                    <Briefcase className="w-4 h-4 text-amber-400" />
                                    Creator Opportunities
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Tabs & Filters */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-800 pb-4">
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setActiveTab('packages')}
                            className={`px-4 py-2 rounded-xl text-sm font-semibold transition-all ${
                                activeTab === 'packages'
                                    ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-900'
                            }`}
                        >
                            <div className="flex items-center gap-2">
                                <Layers className="w-4 h-4" />
                                Sponsorship Packages ({packages.length})
                            </div>
                        </button>
                        <button
                            onClick={() => setActiveTab('brands')}
                            className={`px-4 py-2 rounded-xl text-sm font-semibold transition-all ${
                                activeTab === 'brands'
                                    ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-900'
                            }`}
                        >
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="w-4 h-4" />
                                Verified Brands & Partners ({brands.length})
                            </div>
                        </button>
                    </div>

                    {activeTab === 'packages' ? (
                        <div className="flex items-center gap-3 w-full sm:w-auto">
                            <select
                                value={targetTypeFilter}
                                onChange={(e) => setTargetTypeFilter(e.target.value)}
                                className="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">All Target Types</option>
                                <option value="tournament">Tournaments</option>
                                <option value="team">Teams</option>
                                <option value="player">Individual Players</option>
                                <option value="venue">Venues</option>
                                <option value="creator">Content Creators</option>
                                <option value="awareness_campaign">Awareness Campaigns</option>
                                <option value="platform">Platform-Wide</option>
                            </select>

                            <select
                                value={tierFilter}
                                onChange={(e) => setTierFilter(e.target.value)}
                                className="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">All Tiers</option>
                                <option value="title">Title</option>
                                <option value="presenting">Presenting</option>
                                <option value="gold">Gold</option>
                                <option value="silver">Silver</option>
                                <option value="bronze">Bronze</option>
                            </select>
                        </div>
                    ) : (
                        <div className="w-full sm:w-64">
                            <Input
                                placeholder="Search brands..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="bg-slate-900 border-slate-800 text-xs"
                            />
                        </div>
                    )}
                </div>

                {/* Content Render */}
                {loading ? (
                    <div className="py-20 text-center text-slate-400 space-y-3">
                        <div className="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto" />
                        <p className="text-sm">Loading sponsorship marketplace...</p>
                    </div>
                ) : activeTab === 'packages' ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {packages.map((pkg) => (
                            <Card key={pkg.id} className="bg-slate-900/60 border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                                <CardHeader className="space-y-3 pb-3">
                                    <div className="flex items-center justify-between">
                                        {getTierBadge(pkg.tier)}
                                        <Badge variant="outline" className="capitalize text-[11px] border-slate-700">
                                            {pkg.target_type.replace('_', ' ')}
                                        </Badge>
                                    </div>
                                    <h3 className="text-lg font-bold text-white tracking-tight">
                                        {pkg.title}
                                    </h3>
                                    <div className="flex items-baseline gap-1 text-emerald-400">
                                        <span className="text-2xl font-black">
                                            {Number(pkg.price).toLocaleString()}
                                        </span>
                                        <span className="text-xs font-semibold text-slate-400">{pkg.currency}</span>
                                        <span className="text-xs text-slate-500">/ {pkg.duration_days} days</span>
                                    </div>
                                </CardHeader>
                                <CardBody className="space-y-4 pt-0">
                                    {pkg.audience_estimate > 0 && (
                                        <div className="flex items-center gap-2 text-xs text-slate-300 bg-slate-950/60 px-3 py-2 rounded-lg border border-slate-800">
                                            <Target className="w-3.5 h-3.5 text-amber-400" />
                                            <span>Est. Audience Reach: <strong className="text-white">{pkg.audience_estimate.toLocaleString()}+ students</strong></span>
                                        </div>
                                    )}

                                    {pkg.benefits && pkg.benefits.length > 0 && (
                                        <div className="space-y-1.5">
                                            <h4 className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                                Key Deliverables & Benefits
                                            </h4>
                                            <ul className="space-y-1 text-xs text-slate-300">
                                                {pkg.benefits.slice(0, 4).map((b, idx) => (
                                                    <li key={idx} className="flex items-center gap-1.5">
                                                        <CheckCircle2 className="w-3 h-3 text-emerald-400 shrink-0" />
                                                        <span className="truncate">{b}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    <div className="pt-4 border-t border-slate-800/80">
                                        <Link href={`/sponsors/dashboard?package_id=${pkg.id}`}>
                                            <Button variant="primary" className="w-full text-xs font-semibold py-2">
                                                Apply For Partnership
                                            </Button>
                                        </Link>
                                    </div>
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {brands.map((brand) => (
                            <Card key={brand.id} className="bg-slate-900/60 border-slate-800">
                                <CardHeader className="flex flex-row items-center gap-4">
                                    <div className="w-12 h-12 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-base text-emerald-400 overflow-hidden">
                                        {brand.logo_url ? (
                                            <img src={brand.logo_url} alt={brand.company_name} className="w-full h-full object-cover" />
                                        ) : (
                                            brand.company_name.substring(0, 2).toUpperCase()
                                        )}
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-1.5">
                                            <h3 className="font-bold text-white text-base">{brand.company_name}</h3>
                                            {brand.verified && (
                                                <ShieldCheck className="w-4 h-4 text-emerald-400" aria-label="Verified Brand Partner" />
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-400">{brand.industry}</p>
                                    </div>
                                </CardHeader>
                                <CardBody className="space-y-3 pt-0">
                                    {brand.tagline && (
                                        <p className="text-xs text-slate-300 italic">"{brand.tagline}"</p>
                                    )}
                                    {brand.website_url && (
                                        <a
                                            href={brand.website_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-xs text-emerald-400 hover:underline flex items-center gap-1"
                                        >
                                            Official Website
                                            <ExternalLink className="w-3 h-3" />
                                        </a>
                                    )}
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
