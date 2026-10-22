import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import {
    GraduationCap,
    Search,
    ShieldCheck,
    AlertTriangle,
    HeartPulse,
    Brain,
    Code,
    Video,
    Briefcase,
    Database,
    Zap,
    Download,
    Award,
    CheckCircle2,
    Clock,
    BookOpen,
    Flame,
    Share2,
    Globe
} from 'lucide-react';

interface Course {
    id: number;
    uuid: string;
    title: string;
    title_am?: string;
    slug: string;
    category: string;
    category_label: string;
    category_label_am: string;
    level: string;
    description?: string;
    description_am?: string;
    cover_image?: string;
    estimated_duration_minutes: number;
    total_points: number;
    is_offline_available: boolean;
    language_support?: string[];
    lessons_count: number;
    modules_count: number;
    enrollment?: {
        status: string;
        progress_percentage: number;
    } | null;
}

interface Campaign {
    id: number;
    uuid: string;
    title: string;
    title_am?: string;
    slug: string;
    category: string;
    description?: string;
    description_am?: string;
    banner_url?: string;
    status: string;
    analytics?: {
        views_count?: number;
        shares_count?: number;
        quiz_completions?: number;
    };
}

export default function EducationIndex() {
    const [courses, setCourses] = useState<Course[]>([]);
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [loading, setLoading] = useState(true);
    const [locale, setLocale] = useState<'en' | 'am'>('en');
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [selectedLevel, setSelectedLevel] = useState<string>('all');

    const categories = [
        { id: 'all', label: 'All Categories', labelAm: 'ሁሉም ምድቦች', icon: BookOpen },
        { id: 'online_safety', label: 'Online Safety', labelAm: 'የመስመር ላይ ደህንነት', icon: ShieldCheck },
        { id: 'scam_awareness', label: 'Scam Awareness', labelAm: 'የማጭበርበር ግንዛቤ', icon: AlertTriangle },
        { id: 'cyberbullying_prevention', label: 'Anti-Cyberbullying', labelAm: 'የሳይበር ትንኮሳ መከላከል', icon: ShieldCheck },
        { id: 'healthy_gaming', label: 'Healthy Gaming', labelAm: 'ጤናማ ጌሚንግ', icon: HeartPulse },
        { id: 'digital_literacy', label: 'Digital Literacy', labelAm: 'ዲጂታል ማንበብና መጻፍ', icon: Globe },
        { id: 'ai_basics', label: 'AI Basics', labelAm: 'የአርቴፊሻል ኢንተለጀንስ መሰረቶች', icon: Brain },
        { id: 'coding', label: 'Coding & Dev', labelAm: 'ኮዲንግ እና ልማት', icon: Code },
        { id: 'content_creation', label: 'Content Creation', labelAm: 'የይዘት ፈጠራ', icon: Video },
        { id: 'esports_careers', label: 'Esports Careers', labelAm: 'የኢስፖርት ሙያዎች', icon: Briefcase },
        { id: 'data_skills', label: 'Data Skills', labelAm: 'የዳታ ችሎታዎች', icon: Database },
        { id: 'digital_entrepreneurship', label: 'Entrepreneurship', labelAm: 'ዲጂታል ስራ ፈጠራ', icon: Zap },
    ];

    useEffect(() => {
        fetchCoursesAndCampaigns();
    }, [selectedCategory, selectedLevel, searchQuery]);

    const fetchCoursesAndCampaigns = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (selectedCategory !== 'all') params.append('category', selectedCategory);
            if (selectedLevel !== 'all') params.append('level', selectedLevel);
            if (searchQuery) params.append('search', searchQuery);

            const [coursesRes, campaignsRes] = await Promise.all([
                fetch(`/api/v1/courses?${params.toString()}`),
                fetch('/api/v1/campaigns')
            ]);

            const coursesData = await coursesRes.json();
            const campaignsData = await campaignsRes.json();

            if (coursesData.success) {
                setCourses(coursesData.data.data || coursesData.data);
            }
            if (campaignsData.success) {
                setCampaigns(campaignsData.data.data || campaignsData.data);
            }
        } catch (err) {
            console.error('Failed to load courses or campaigns', err);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Digital Awareness & Learning Hub | Campus Esports Ethiopia" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header & Language Toggle */}
                <div className="bg-gradient-to-r from-indigo-900 via-slate-900 to-purple-950 rounded-2xl p-6 sm:p-10 border border-indigo-800/40 shadow-2xl relative overflow-hidden">
                    <div className="absolute top-0 right-0 -mr-16 -mt-16 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div className="flex items-center space-x-3 mb-2">
                                <span className="p-2 bg-indigo-600/30 text-indigo-400 rounded-lg border border-indigo-500/30">
                                    <GraduationCap className="w-6 h-6" />
                                </span>
                                <Badge variant="accent" className="bg-indigo-500/20 text-indigo-300 border-indigo-500/30">
                                    {locale === 'am' ? 'ዲጂታል አካዳሚ' : 'Digital Awareness Academy'}
                                </Badge>
                            </div>
                            <h1 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                                {locale === 'am'
                                    ? 'የዲጂታል ግንዛቤ እና የቴክኖሎጂ ትምህርት ማዕከል'
                                    : 'Digital Awareness & Learning Hub'}
                            </h1>
                            <p className="mt-2 text-slate-300 max-w-2xl text-base sm:text-lg">
                                {locale === 'am'
                                    ? 'የመስመር ላይ ደህንነትን፣ የማጭበርበር መከላከያን፣ ጤናማ ጌሚንግን እና የኢስፖርት ሙያዎችን በነጻ ይማሩ፤ የተረጋገጡ ሰርተፊኬቶችን ያግኙ።'
                                    : 'Empowering Ethiopian students with cybersecurity, healthy gaming practices, and digital technology skills with verified certificates.'}
                            </p>
                        </div>

                        {/* Top Action Pills: Dashboard & Lang Selector */}
                        <div className="flex flex-wrap items-center gap-3">
                            <div className="flex items-center bg-slate-800/80 rounded-xl p-1 border border-slate-700">
                                <button
                                    onClick={() => setLocale('en')}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${locale === 'en' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'}`}
                                >
                                    English
                                </button>
                                <button
                                    onClick={() => setLocale('am')}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${locale === 'am' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'}`}
                                >
                                    አማርኛ
                                </button>
                            </div>

                            <Link href="/education/dashboard">
                                <Button className="bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium shadow hover:opacity-90">
                                    <Flame className="w-4 h-4 mr-2 text-white" />
                                    {locale === 'am' ? 'የትምህርት ዳሽቦርድ' : 'My Learning Dashboard'}
                                </Button>
                            </Link>

                            <Link href="/verify-certificate">
                                <Button variant="outline" className="border-slate-700 hover:bg-slate-800 text-slate-200">
                                    <Award className="w-4 h-4 mr-2 text-amber-400" />
                                    {locale === 'am' ? 'ሰርተፊኬት አረጋግጥ' : 'Verify Certificate'}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Featured Awareness Campaigns */}
                {campaigns.length > 0 && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-xl font-bold text-white flex items-center">
                                <ShieldCheck className="w-5 h-5 text-indigo-400 mr-2" />
                                {locale === 'am' ? 'ሀገር አቀፍ የግንዛቤ ዘመቻዎች' : 'National Awareness Campaigns'}
                            </h2>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {campaigns.map((camp) => (
                                <Link
                                    key={camp.id}
                                    href={`/education/campaigns/${camp.id}`}
                                    className="group block bg-slate-900 border border-slate-800 hover:border-indigo-600/60 rounded-xl overflow-hidden transition shadow-lg"
                                >
                                    <div className="h-44 w-full relative overflow-hidden bg-slate-800">
                                        {camp.banner_url ? (
                                            <img
                                                src={camp.banner_url}
                                                alt={camp.title}
                                                className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-950 to-slate-900">
                                                <ShieldCheck className="w-16 h-16 text-indigo-500/40" />
                                            </div>
                                        )}
                                        <div className="absolute top-3 left-3">
                                            <Badge className="bg-indigo-600/90 text-white backdrop-blur-md">
                                                {camp.category.replace('_', ' ').toUpperCase()}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div className="p-5 space-y-2">
                                        <h3 className="text-lg font-bold text-white group-hover:text-indigo-400 transition">
                                            {locale === 'am' && camp.title_am ? camp.title_am : camp.title}
                                        </h3>
                                        <p className="text-slate-400 text-sm line-clamp-2">
                                            {locale === 'am' && camp.description_am ? camp.description_am : camp.description}
                                        </p>
                                        <div className="pt-2 flex items-center justify-between text-xs text-slate-500 border-t border-slate-800/80">
                                            <span className="flex items-center">
                                                <Share2 className="w-3.5 h-3.5 mr-1 text-slate-400" />
                                                {camp.analytics?.shares_count || 0} shares
                                            </span>
                                            <span className="text-indigo-400 font-semibold flex items-center group-hover:translate-x-1 transition">
                                                {locale === 'am' ? 'ዘመቻውን ይመልከቱ →' : 'Explore Campaign →'}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Filters, Categories & Search */}
                <div className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between items-center">
                        <div className="relative w-full sm:w-96">
                            <Search className="absolute left-3.5 top-3 w-4 h-4 text-slate-400" />
                            <Input
                                placeholder={locale === 'am' ? 'ትምህርቶችን ፈልግ...' : 'Search courses, safety guides...'}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10 bg-slate-900 border-slate-700 text-white"
                            />
                        </div>

                        <div className="flex items-center space-x-2 w-full sm:w-auto">
                            <span className="text-xs text-slate-400 font-medium whitespace-nowrap">
                                {locale === 'am' ? 'ደረጃ:' : 'Difficulty:'}
                            </span>
                            <select
                                value={selectedLevel}
                                onChange={(e) => setSelectedLevel(e.target.value)}
                                className="bg-slate-900 border border-slate-700 rounded-lg text-xs text-slate-200 px-3 py-2 focus:ring-indigo-500"
                            >
                                <option value="all">{locale === 'am' ? 'ሁሉም ደረጃዎች' : 'All Levels'}</option>
                                <option value="beginner">{locale === 'am' ? 'ጀማሪ (Beginner)' : 'Beginner'}</option>
                                <option value="intermediate">{locale === 'am' ? 'መካከለኛ (Intermediate)' : 'Intermediate'}</option>
                                <option value="advanced">{locale === 'am' ? 'ከፍተኛ (Advanced)' : 'Advanced'}</option>
                            </select>
                        </div>
                    </div>

                    {/* Category Tabs Scrollable */}
                    <div className="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-slate-700">
                        {categories.map((cat) => {
                            const IconComponent = cat.icon;
                            const isSelected = selectedCategory === cat.id;
                            return (
                                <button
                                    key={cat.id}
                                    onClick={() => setSelectedCategory(cat.id)}
                                    className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-medium whitespace-nowrap transition ${
                                        isSelected
                                            ? 'bg-indigo-600 text-white shadow-md'
                                            : 'bg-slate-900 text-slate-400 hover:text-slate-200 hover:bg-slate-800 border border-slate-800'
                                    }`}
                                >
                                    <IconComponent className="w-3.5 h-3.5" />
                                    <span>{locale === 'am' ? cat.labelAm : cat.label}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Courses Catalog Grid */}
                {loading ? (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
                        {[1, 2, 3, 4, 5, 6].map((i) => (
                            <div key={i} className="h-72 bg-slate-900 rounded-2xl border border-slate-800" />
                        ))}
                    </div>
                ) : courses.length === 0 ? (
                    <div className="text-center py-16 bg-slate-900/60 rounded-2xl border border-dashed border-slate-800">
                        <BookOpen className="w-12 h-12 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-lg font-bold text-white">
                            {locale === 'am' ? 'ምንም ትምህርት አልተገኘም' : 'No courses found in this category'}
                        </h3>
                        <p className="text-slate-400 text-sm mt-1">
                            {locale === 'am'
                                ? 'እባክዎ የተለየ ምድብ ይምረጡ ወይም ፍለጋዎን ይቀይሩ።'
                                : 'Try adjusting your search criteria or explore other categories.'}
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {courses.map((course) => {
                            const isEnrolled = !!course.enrollment;
                            const progress = course.enrollment?.progress_percentage || 0;

                            return (
                                <div
                                    key={course.id}
                                    className="bg-slate-900 border border-slate-800 hover:border-indigo-600/50 rounded-2xl overflow-hidden flex flex-col justify-between transition duration-300 shadow-md group"
                                >
                                    <div>
                                        {/* Cover Image */}
                                        <div className="h-44 w-full relative overflow-hidden bg-slate-800">
                                            {course.cover_image ? (
                                                <img
                                                    src={course.cover_image}
                                                    alt={course.title}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 to-indigo-950">
                                                    <GraduationCap className="w-14 h-14 text-indigo-500/30" />
                                                </div>
                                            )}

                                            <div className="absolute top-3 left-3 flex gap-2">
                                                <Badge className="bg-slate-900/90 text-white backdrop-blur-md border border-slate-700">
                                                    {course.level.toUpperCase()}
                                                </Badge>
                                                {course.is_offline_available && (
                                                    <span
                                                        title="Available for offline download"
                                                        className="p-1.5 bg-emerald-950/80 text-emerald-400 border border-emerald-800/80 rounded-md backdrop-blur-md"
                                                    >
                                                        <Download className="w-3.5 h-3.5" />
                                                    </span>
                                                )}
                                            </div>

                                            <div className="absolute top-3 right-3">
                                                <Badge className="bg-indigo-600/90 text-white border-none shadow">
                                                    +{course.total_points} PTS
                                                </Badge>
                                            </div>
                                        </div>

                                        {/* Body */}
                                        <div className="p-5 space-y-3">
                                            <div className="text-xs font-semibold text-indigo-400 uppercase tracking-wider">
                                                {locale === 'am' ? course.category_label_am : course.category_label}
                                            </div>

                                            <h3 className="text-lg font-bold text-white group-hover:text-indigo-300 transition line-clamp-2">
                                                {locale === 'am' && course.title_am ? course.title_am : course.title}
                                            </h3>

                                            <p className="text-slate-400 text-sm line-clamp-2">
                                                {locale === 'am' && course.description_am ? course.description_am : course.description}
                                            </p>

                                            <div className="flex items-center space-x-4 text-xs text-slate-400 pt-2 border-t border-slate-800">
                                                <span className="flex items-center">
                                                    <Clock className="w-3.5 h-3.5 mr-1 text-slate-500" />
                                                    {course.estimated_duration_minutes}m
                                                </span>
                                                <span className="flex items-center">
                                                    <BookOpen className="w-3.5 h-3.5 mr-1 text-slate-500" />
                                                    {course.lessons_count} {locale === 'am' ? 'ትምህርቶች' : 'lessons'}
                                                </span>
                                                <span className="flex items-center">
                                                    <Globe className="w-3.5 h-3.5 mr-1 text-slate-500" />
                                                    EN / AM
                                                </span>
                                            </div>

                                            {/* Progress Bar if enrolled */}
                                            {isEnrolled && (
                                                <div className="pt-2">
                                                    <div className="flex justify-between text-xs text-slate-400 mb-1">
                                                        <span>{locale === 'am' ? 'የትምህርት ሂደት' : 'Progress'}</span>
                                                        <span className="font-semibold text-indigo-400">{progress}%</span>
                                                    </div>
                                                    <div className="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                        <div
                                                            className="bg-indigo-500 h-1.5 rounded-full transition-all duration-300"
                                                            style={{ width: `${progress}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Action Footer */}
                                    <div className="p-5 pt-0">
                                        <Link href={`/education/courses/${course.id}`} className="block">
                                            <Button
                                                className={`w-full font-semibold ${
                                                    isEnrolled
                                                        ? 'bg-slate-800 hover:bg-slate-700 text-indigo-300'
                                                        : 'bg-indigo-600 hover:bg-indigo-500 text-white'
                                                }`}
                                            >
                                                {isEnrolled
                                                    ? locale === 'am' ? 'ትምህርቱን ቀጥል' : 'Resume Course'
                                                    : locale === 'am' ? 'ትምህርቱን ጀምር (ነጻ)' : 'Start Course (Free)'}
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
