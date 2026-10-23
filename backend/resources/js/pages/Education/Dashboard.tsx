import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import {
    Flame,
    Award,
    BookOpen,
    CheckCircle2,
    Clock,
    Zap,
    PlayCircle,
    ExternalLink,
    ShieldCheck,
    Calendar,
    Building2,
    Sparkles
} from 'lucide-react';

interface DashboardData {
    streak: {
        current_streak: number;
        longest_streak: number;
        total_points: number;
        total_lessons_completed: number;
        total_quizzes_passed: number;
        last_activity_date?: string;
    };
    enrollments: Array<{
        id: number;
        uuid: string;
        status: string;
        progress_percentage: number;
        course: {
            id: number;
            uuid: string;
            title: string;
            title_am?: string;
            category: string;
            cover_image?: string;
            level: string;
        };
        lastAccessedLesson?: {
            id: number;
            uuid: string;
            title: string;
            title_am?: string;
            slug: string;
        } | null;
    }>;
    badges: Array<{
        id: number;
        slug: string;
        name: string;
        name_am?: string;
        description: string;
        description_am?: string;
        icon?: string;
        awarded_at: string;
    }>;
    certificates: Array<{
        id: number;
        uuid: string;
        certificate_number: string;
        verification_code: string;
        issued_at: string;
        course: {
            id: number;
            uuid: string;
            title: string;
            title_am?: string;
        };
    }>;
    assigned_paths: Array<{
        id: number;
        uuid: string;
        university_name?: string;
        due_date?: string;
        status: string;
        learningPath: {
            id: number;
            title: string;
            title_am?: string;
            target_university?: string;
            courses?: Array<{ id: number; title: string }>;
        };
    }>;
}

export default function EducationDashboard() {
    const [data, setData] = useState<DashboardData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDashboard();
    }, []);

    const fetchDashboard = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/education/dashboard');
            const json = await res.json();
            if (json.success) {
                setData(json.data);
            }
        } catch (err) {
            console.error('Failed to load dashboard', err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="max-w-6xl mx-auto px-4 py-12 animate-pulse space-y-6">
                    <div className="h-44 bg-slate-900 rounded-2xl" />
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="h-64 bg-slate-900 rounded-2xl" />
                        <div className="h-64 bg-slate-900 rounded-2xl" />
                        <div className="h-64 bg-slate-900 rounded-2xl" />
                    </div>
                </div>
            </AppLayout>
        );
    }

    const streak = data?.streak;

    return (
        <AppLayout>
            <Head title="Learner Progress & Badges Dashboard | Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Streak & Metrics Banner */}
                <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 relative z-10">
                        {/* Daily Streak Card */}
                        <div className="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
                            <div className="p-3 bg-orange-600/20 text-orange-400 rounded-xl border border-orange-500/30">
                                <Flame className="w-8 h-8" />
                            </div>
                            <div>
                                <div className="text-2xl font-black text-white flex items-center">
                                    {streak?.current_streak || 0}
                                    <span className="text-xs text-orange-400 ml-1.5 font-bold uppercase">Days</span>
                                </div>
                                <div className="text-xs text-slate-400">Current Daily Streak</div>
                                <div className="text-[10px] text-slate-500 mt-0.5">
                                    Longest: {streak?.longest_streak || 0} days
                                </div>
                            </div>
                        </div>

                        {/* Total Points */}
                        <div className="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
                            <div className="p-3 bg-indigo-600/20 text-indigo-400 rounded-xl border border-indigo-500/30">
                                <Zap className="w-8 h-8" />
                            </div>
                            <div>
                                <div className="text-2xl font-black text-white">
                                    {streak?.total_points || 0}
                                </div>
                                <div className="text-xs text-slate-400">Academy Points</div>
                                <div className="text-[10px] text-slate-500 mt-0.5">Rank: Digital Scholar</div>
                            </div>
                        </div>

                        {/* Lessons Completed */}
                        <div className="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
                            <div className="p-3 bg-emerald-600/20 text-emerald-400 rounded-xl border border-emerald-500/30">
                                <CheckCircle2 className="w-8 h-8" />
                            </div>
                            <div>
                                <div className="text-2xl font-black text-white">
                                    {streak?.total_lessons_completed || 0}
                                </div>
                                <div className="text-xs text-slate-400">Lessons Completed</div>
                                <div className="text-[10px] text-slate-500 mt-0.5">Verified Knowledge</div>
                            </div>
                        </div>

                        {/* Quizzes Passed */}
                        <div className="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
                            <div className="p-3 bg-amber-600/20 text-amber-400 rounded-xl border border-amber-500/30">
                                <Award className="w-8 h-8" />
                            </div>
                            <div>
                                <div className="text-2xl font-black text-white">
                                    {streak?.total_quizzes_passed || 0}
                                </div>
                                <div className="text-xs text-slate-400">Quizzes Passed</div>
                                <div className="text-[10px] text-slate-500 mt-0.5">
                                    {data?.certificates.length || 0} Certificates
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* University-Assigned Learning Paths */}
                {data?.assigned_paths && data.assigned_paths.length > 0 && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-bold text-white flex items-center">
                            <Building2 className="w-5 h-5 text-indigo-400 mr-2" />
                            University Assigned Learning Paths
                        </h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {data.assigned_paths.map((assign) => (
                                <div
                                    key={assign.id}
                                    className="p-5 bg-indigo-950/40 border border-indigo-800/40 rounded-2xl flex flex-col justify-between space-y-4 shadow"
                                >
                                    <div>
                                        <div className="flex items-center justify-between">
                                            <Badge className="bg-indigo-600 text-white">
                                                {assign.university_name || 'Assigned Cohort'}
                                            </Badge>
                                            {assign.due_date && (
                                                <span className="flex items-center text-xs text-amber-300">
                                                    <Calendar className="w-3.5 h-3.5 mr-1" />
                                                    Due: {new Date(assign.due_date).toLocaleDateString()}
                                                </span>
                                            )}
                                        </div>
                                        <h3 className="text-lg font-bold text-white mt-2">
                                            {assign.learningPath.title}
                                        </h3>
                                        <p className="text-xs text-slate-400 mt-1">
                                            {assign.learningPath.courses?.length || 0} Mandatory courses in this path
                                        </p>
                                    </div>

                                    {assign.learningPath.courses?.[0]?.id ? (
                                        <Link href={`/education/courses/${assign.learningPath.courses[0].id}`}>
                                            <Button size="sm" className="w-full bg-indigo-600 hover:bg-indigo-500 text-white">
                                                Start Assigned Curriculum
                                            </Button>
                                        </Link>
                                    ) : (
                                        <p className="text-xs text-slate-400">No course is attached to this assignment.</p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Active Courses Grid */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-bold text-white flex items-center">
                            <BookOpen className="w-5 h-5 text-indigo-400 mr-2" />
                            My Enrolled Courses
                        </h2>
                        <Link href="/education" className="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
                            Browse All Courses →
                        </Link>
                    </div>

                    {data?.enrollments.length === 0 ? (
                        <div className="text-center py-12 bg-slate-900 border border-dashed border-slate-800 rounded-2xl">
                            <BookOpen className="w-10 h-10 text-slate-600 mx-auto mb-2" />
                            <p className="text-slate-400 text-sm">You haven't enrolled in any courses yet.</p>
                            <Link href="/education">
                                <Button size="sm" className="mt-3 bg-indigo-600 hover:bg-indigo-500 text-white">
                                    Explore Courses
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            {data?.enrollments.map((enroll) => (
                                <div
                                    key={enroll.id}
                                    className="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between space-y-4 shadow hover:border-slate-700 transition"
                                >
                                    <div className="space-y-3">
                                        <div className="flex justify-between items-start">
                                            <Badge variant="outline" className="border-slate-700 text-slate-300 text-xs">
                                                {enroll.course.category.replace('_', ' ')}
                                            </Badge>
                                            <span className="text-xs font-bold text-indigo-400">
                                                {enroll.progress_percentage}%
                                            </span>
                                        </div>

                                        <h3 className="text-base font-bold text-white line-clamp-1">
                                            {enroll.course.title}
                                        </h3>

                                        <div className="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                className="bg-indigo-500 h-1.5 rounded-full"
                                                style={{ width: `${enroll.progress_percentage}%` }}
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <Link
                                            href={
                                                enroll.lastAccessedLesson
                                                    ? `/education/lessons/${enroll.lastAccessedLesson.id}`
                                                    : `/education/courses/${enroll.course.id}`
                                            }
                                        >
                                            <Button size="sm" className="w-full bg-slate-800 hover:bg-slate-700 text-slate-200">
                                                <PlayCircle className="w-3.5 h-3.5 mr-1.5 text-indigo-400" />
                                                Resume Learning
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Badges Showcase */}
                <div className="space-y-4">
                    <h2 className="text-xl font-bold text-white flex items-center">
                        <Sparkles className="w-5 h-5 text-amber-400 mr-2" />
                        Achievements & Badges
                    </h2>

                    {data?.badges.length === 0 ? (
                        <div className="text-center py-8 bg-slate-900/60 border border-dashed border-slate-800 rounded-2xl text-slate-400 text-xs">
                            Complete lessons and score 100% on quizzes to unlock your first achievement badge!
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            {data?.badges.map((b) => (
                                <div
                                    key={b.id}
                                    className="bg-slate-900 border border-slate-800 rounded-2xl p-4 text-center space-y-2 shadow group hover:border-amber-500/40 transition"
                                >
                                    <div className="w-12 h-12 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-xl flex items-center justify-center mx-auto group-hover:scale-110 transition">
                                        <Award className="w-6 h-6" />
                                    </div>
                                    <h4 className="text-sm font-bold text-white">{b.name}</h4>
                                    <p className="text-[11px] text-slate-400 line-clamp-2">{b.description}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Certificates Wallet */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-bold text-white flex items-center">
                            <Award className="w-5 h-5 text-amber-400 mr-2" />
                            Official Verified Certificates
                        </h2>
                        <Link href="/verify-certificate" className="text-xs text-amber-400 hover:text-amber-300 font-medium">
                            Public Verification Tool →
                        </Link>
                    </div>

                    {data?.certificates.length === 0 ? (
                        <div className="text-center py-8 bg-slate-900/60 border border-dashed border-slate-800 rounded-2xl text-slate-400 text-xs">
                            No certificates issued yet. Pass course quizzes with 70%+ to earn verifiable credentials.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {data?.certificates.map((cert) => (
                                <div
                                    key={cert.id}
                                    className="p-5 bg-gradient-to-br from-slate-900 to-indigo-950/30 border border-slate-800 hover:border-amber-500/40 rounded-2xl flex items-center justify-between shadow"
                                >
                                    <div className="space-y-1">
                                        <div className="text-xs font-mono text-amber-400 font-bold">
                                            {cert.verification_code}
                                        </div>
                                        <h4 className="text-base font-bold text-white">{cert.course.title}</h4>
                                        <p className="text-[11px] text-slate-400">
                                            Issued on {new Date(cert.issued_at).toLocaleDateString()}
                                        </p>
                                    </div>

                                    <Link href={`/verify-certificate?code=${cert.verification_code}`}>
                                        <Button size="sm" variant="outline" className="border-slate-700 text-slate-200 text-xs">
                                            <ExternalLink className="w-3.5 h-3.5 mr-1" />
                                            Verify
                                        </Button>
                                    </Link>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
