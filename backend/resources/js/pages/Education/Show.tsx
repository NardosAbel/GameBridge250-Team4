import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import {
    GraduationCap,
    Clock,
    BookOpen,
    CheckCircle2,
    Lock,
    PlayCircle,
    FileText,
    HelpCircle,
    Award,
    Download,
    Share2,
    ArrowLeft,
    Globe,
    ShieldCheck
} from 'lucide-react';

interface LessonItem {
    id: number;
    uuid: string;
    title: string;
    title_am?: string;
    slug: string;
    lesson_type: string;
    order: number;
    duration_minutes: number;
    is_preview: boolean;
    is_downloadable: boolean;
    is_completed: boolean;
}

interface ModuleItem {
    id: number;
    uuid: string;
    title: string;
    title_am?: string;
    order: number;
    lessons: LessonItem[];
}

interface QuizItem {
    id: number;
    uuid: string;
    title: string;
    title_am?: string;
    passing_score: number;
    max_retries: number;
    time_limit_minutes?: number;
}

interface CourseDetails {
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
    instructor?: {
        id: number;
        name: string;
    };
    modules: ModuleItem[];
    quizzes: QuizItem[];
    enrollment?: {
        status: string;
        progress_percentage: number;
        last_accessed_lesson_id?: number;
        completed_at?: string;
    } | null;
}

interface Props {
    courseId: string | number;
}

export default function CourseShow({ courseId }: Props) {
    const [course, setCourse] = useState<CourseDetails | null>(null);
    const [loading, setLoading] = useState(true);
    const [locale] = useState<'en' | 'am'>('en');
    const [enrolling, setEnrolling] = useState(false);

    useEffect(() => {
        fetchCourse();
    }, [courseId]);

    const fetchCourse = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/courses/${courseId}`);
            const json = await res.json();
            if (json.success) {
                setCourse(json.data);
            }
        } catch (err) {
            console.error('Failed to load course details', err);
        } finally {
            setLoading(false);
        }
    };

    const handleEnroll = async () => {
        if (!course) return;
        setEnrolling(true);
        try {
            const res = await fetch(`/api/v1/education/courses/${course.id}/enroll`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
            });
            const json = await res.json();
            if (json.success) {
                fetchCourse();
            }
        } catch (err) {
            console.error('Enrollment error', err);
        } finally {
            setEnrolling(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="max-w-5xl mx-auto px-4 py-12 animate-pulse space-y-6">
                    <div className="h-64 bg-slate-900 rounded-2xl" />
                    <div className="h-32 bg-slate-900 rounded-2xl" />
                </div>
            </AppLayout>
        );
    }

    if (!course) {
        return (
            <AppLayout>
                <div className="max-w-4xl mx-auto px-4 py-16 text-center text-white">
                    <h2 className="text-xl font-bold">Course Not Found</h2>
                    <Link href="/education" className="text-indigo-400 mt-4 inline-block">
                        ← Return to Learning Hub
                    </Link>
                </div>
            </AppLayout>
        );
    }

    const isEnrolled = !!course.enrollment;
    const progress = course.enrollment?.progress_percentage || 0;

    // Find next lesson to resume
    const firstIncompleteLesson = course.modules
        .flatMap((m) => m.lessons)
        .find((l) => !l.is_completed) || course.modules[0]?.lessons[0];

    return (
        <AppLayout>
            <Head title={`${locale === 'am' && course.title_am ? course.title_am : course.title} | Learning Hub`} />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Back button & Language toggle */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/education"
                        className="inline-flex items-center text-sm font-medium text-slate-400 hover:text-white transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1.5" />
                        {locale === 'am' ? 'ወደ ትምህርት ማዕከል ተመለስ' : 'Back to Learning Hub'}
                    </Link>

                </div>

                {/* Hero Header Card */}
                <div className="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="md:col-span-2 p-6 sm:p-8 space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge className="bg-indigo-600 text-white">
                                    {locale === 'am' ? course.category_label_am : course.category_label}
                                </Badge>
                                <Badge variant="outline" className="border-slate-700 text-slate-300">
                                    {course.level.toUpperCase()}
                                </Badge>
                                {course.is_offline_available && (
                                    <Badge className="bg-emerald-950 text-emerald-400 border border-emerald-800 flex items-center">
                                        <Download className="w-3 h-3 mr-1" />
                                        Offline Ready
                                    </Badge>
                                )}
                            </div>

                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                                {locale === 'am' && course.title_am ? course.title_am : course.title}
                            </h1>

                            <p className="text-slate-300 text-sm sm:text-base leading-relaxed">
                                {locale === 'am' && course.description_am ? course.description_am : course.description}
                            </p>

                            <div className="flex flex-wrap items-center gap-6 text-xs text-slate-400 pt-3 border-t border-slate-800">
                                <div className="flex items-center">
                                    <Clock className="w-4 h-4 mr-1.5 text-indigo-400" />
                                    <span>{course.estimated_duration_minutes} minutes</span>
                                </div>
                                <div className="flex items-center">
                                    <BookOpen className="w-4 h-4 mr-1.5 text-indigo-400" />
                                    <span>{course.modules.reduce((acc, m) => acc + m.lessons.length, 0)} Lessons</span>
                                </div>
                                <div className="flex items-center">
                                    <Award className="w-4 h-4 mr-1.5 text-amber-400" />
                                    <span>+{course.total_points} Points & Certificate</span>
                                </div>
                            </div>
                        </div>

                        {/* Side Action Column */}
                        <div className="p-6 bg-slate-950/60 border-t md:border-t-0 md:border-l border-slate-800 flex flex-col justify-between space-y-6">
                            <div className="space-y-4">
                                <div className="rounded-xl overflow-hidden h-36 bg-slate-800 relative">
                                    {course.cover_image ? (
                                        <img src={course.cover_image} alt={course.title} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 to-indigo-950">
                                            <GraduationCap className="w-12 h-12 text-indigo-400/40" />
                                        </div>
                                    )}
                                </div>

                                {isEnrolled && (
                                    <div className="space-y-1.5">
                                        <div className="flex justify-between text-xs text-slate-300">
                                            <span>Course Progress</span>
                                            <span className="font-bold text-indigo-400">{progress}%</span>
                                        </div>
                                        <div className="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                            <div
                                                className="bg-indigo-500 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${progress}%` }}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="space-y-3">
                                {isEnrolled ? (
                                    firstIncompleteLesson ? (
                                        <Link href={`/education/lessons/${firstIncompleteLesson.id}`} className="block">
                                            <Button className="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3">
                                                <PlayCircle className="w-4 h-4 mr-2" />
                                                {locale === 'am' ? 'ትምህርቱን ቀጥል' : 'Resume Lesson'}
                                            </Button>
                                        </Link>
                                    ) : (
                                        <Link href="/education/dashboard" className="block">
                                            <Button className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3">
                                                <Award className="w-4 h-4 mr-2" />
                                                {locale === 'am' ? 'ሰርተፊኬትህን ውሰድ' : 'Claim Certificate'}
                                            </Button>
                                        </Link>
                                    )
                                ) : (
                                    <Button
                                        onClick={handleEnroll}
                                        disabled={enrolling}
                                        className="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-3 shadow-lg"
                                    >
                                        {enrolling ? 'Enrolling...' : locale === 'am' ? 'በነጻ ተመዝገብ' : 'Enroll Now (Free)'}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Modules & Syllabus List */}
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-bold text-white flex items-center">
                            <BookOpen className="w-5 h-5 text-indigo-400 mr-2" />
                            {locale === 'am' ? 'የትምህርት ስርአተ ትምህርት' : 'Course Curriculum & Syllabus'}
                        </h2>
                    </div>

                    <div className="space-y-4">
                        {course.modules.map((module, mIdx) => (
                            <div key={module.id} className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow">
                                <div className="p-4 sm:p-5 bg-slate-850 border-b border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span className="text-xs font-semibold text-indigo-400 uppercase tracking-wider">
                                            Module {mIdx + 1}
                                        </span>
                                        <h3 className="text-base font-bold text-white">
                                            {locale === 'am' && module.title_am ? module.title_am : module.title}
                                        </h3>
                                    </div>
                                    <span className="text-xs text-slate-400">
                                        {module.lessons.length} {module.lessons.length === 1 ? 'lesson' : 'lessons'}
                                    </span>
                                </div>

                                <div className="divide-y divide-slate-800/80">
                                    {module.lessons.map((lesson) => {
                                        const canAccess = isEnrolled || lesson.is_preview;
                                        return (
                                            <div
                                                key={lesson.id}
                                                className="p-4 flex items-center justify-between hover:bg-slate-800/40 transition"
                                            >
                                                <div className="flex items-center space-x-3.5">
                                                    {lesson.is_completed ? (
                                                        <span className="p-2 bg-emerald-950 text-emerald-400 rounded-lg">
                                                            <CheckCircle2 className="w-4 h-4" />
                                                        </span>
                                                    ) : canAccess ? (
                                                        <span className="p-2 bg-indigo-950 text-indigo-400 rounded-lg">
                                                            {lesson.lesson_type === 'video' ? (
                                                                <PlayCircle className="w-4 h-4" />
                                                            ) : (
                                                                <FileText className="w-4 h-4" />
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span className="p-2 bg-slate-800 text-slate-500 rounded-lg">
                                                            <Lock className="w-4 h-4" />
                                                        </span>
                                                    )}

                                                    <div>
                                                        <div className="flex items-center space-x-2">
                                                            <h4
                                                                className={`text-sm font-semibold ${
                                                                    canAccess ? 'text-white' : 'text-slate-400'
                                                                }`}
                                                            >
                                                                {locale === 'am' && lesson.title_am ? lesson.title_am : lesson.title}
                                                            </h4>
                                                            {lesson.is_preview && (
                                                                <Badge className="bg-amber-950 text-amber-400 border border-amber-800 text-[10px] px-1.5 py-0.5">
                                                                    Free Preview
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center space-x-3 text-xs text-slate-500 mt-0.5">
                                                            <span>{lesson.duration_minutes} min</span>
                                                            <span>•</span>
                                                            <span className="capitalize">{lesson.lesson_type}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div>
                                                    {canAccess ? (
                                                        <Link href={`/education/lessons/${lesson.id}`}>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="border-slate-700 text-xs hover:bg-slate-700 text-slate-200"
                                                            >
                                                                {lesson.is_completed ? 'Review' : 'Start'}
                                                            </Button>
                                                        </Link>
                                                    ) : (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            disabled
                                                            className="text-slate-600 text-xs"
                                                        >
                                                            <Lock className="w-3.5 h-3.5 mr-1" />
                                                            Locked
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}

                        {/* Course Quiz & Final Assessment */}
                        {course.quizzes.length > 0 && (
                            <div className="bg-gradient-to-r from-purple-950/40 to-slate-900 border border-purple-800/40 rounded-2xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="flex items-center space-x-4">
                                    <div className="p-3 bg-purple-600/30 text-purple-300 rounded-xl border border-purple-500/30">
                                        <Award className="w-6 h-6 text-amber-400" />
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-white">
                                            {locale === 'am' ? 'የኮርሱ ማጠቃለያ ፈተና እና ሰርተፊኬት' : 'Course Final Assessment & Certificate'}
                                        </h3>
                                        <p className="text-xs text-slate-400">
                                            {locale === 'am'
                                                ? `ማለፊያ ነጥብ፡ ${course.quizzes[0].passing_score}% • የተረጋገጠ ሰርተፊኬት ተዘጋጅቷል`
                                                : `Passing Score: ${course.quizzes[0].passing_score}% • Verified certificate issued upon passing`}
                                        </p>
                                    </div>
                                </div>

                                <Link href={`/education/quizzes/${course.quizzes[0].id}`}>
                                    <Button className="bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs px-5 py-2.5">
                                        <HelpCircle className="w-4 h-4 mr-1.5" />
                                        {locale === 'am' ? 'ፈተናውን ውሰድ' : 'Take Final Quiz'}
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
