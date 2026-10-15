import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import {
    PlayCircle,
    FileText,
    CheckCircle2,
    Download,
    ArrowLeft,
    ArrowRight,
    Globe,
    Clock,
    Award,
    ShieldAlert,
    ExternalLink,
    HelpCircle
} from 'lucide-react';

interface LessonData {
    id: number;
    uuid: string;
    course_id: number;
    module_id?: number;
    title: string;
    title_am?: string;
    slug: string;
    lesson_type: string;
    content: string;
    content_am?: string;
    media_url?: string;
    video_embed_url?: string;
    pdf_url?: string;
    interactive_payload?: any;
    order: number;
    duration_minutes: number;
    is_preview: boolean;
    is_downloadable: boolean;
    progress?: {
        status: string;
        last_position_seconds: number;
        time_spent_seconds: number;
        completed_at?: string;
    } | null;
    navigation: {
        previous?: { id: number; uuid: string; title: string; title_am?: string; slug: string } | null;
        next?: { id: number; uuid: string; title: string; title_am?: string; slug: string } | null;
    };
}

interface Props {
    lessonId: string | number;
}

export default function LessonShow({ lessonId }: Props) {
    const [lesson, setLesson] = useState<LessonData | null>(null);
    const [loading, setLoading] = useState(true);
    const [locale, setLocale] = useState<'en' | 'am'>('en');
    const [completing, setCompleting] = useState(false);
    const [isCompleted, setIsCompleted] = useState(false);
    const [downloadedOffline, setDownloadedOffline] = useState(false);

    useEffect(() => {
        fetchLesson();
    }, [lessonId]);

    const fetchLesson = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/education/lessons/${lessonId}`);
            const json = await res.json();
            if (json.success) {
                setLesson(json.data);
                setIsCompleted(json.data.progress?.status === 'completed');
            }
        } catch (err) {
            console.error('Failed to load lesson', err);
            // Check offline storage fallback
            const offlineData = localStorage.getItem(`offline_lesson_${lessonId}`);
            if (offlineData) {
                try {
                    const parsed = JSON.parse(offlineData);
                    setLesson(parsed);
                    setDownloadedOffline(true);
                } catch (e) {
                    console.error('Error parsing offline lesson', e);
                }
            }
        } finally {
            setLoading(false);
        }
    };

    const handleComplete = async () => {
        if (!lesson) return;
        setCompleting(true);
        try {
            const res = await fetch(`/api/v1/education/lessons/${lesson.id}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ time_spent_seconds: 120 }),
            });
            const json = await res.json();
            if (json.success) {
                setIsCompleted(true);
                if (lesson.navigation.next) {
                    router.visit(`/education/lessons/${lesson.navigation.next.id}`);
                }
            }
        } catch (err) {
            console.error('Failed to complete lesson', err);
        } finally {
            setCompleting(false);
        }
    };

    const handleDownloadOffline = async () => {
        if (!lesson) return;
        try {
            const res = await fetch(`/api/v1/education/lessons/${lesson.id}/offline-package`);
            const json = await res.json();
            if (json.success) {
                localStorage.setItem(`offline_lesson_${lesson.id}`, JSON.stringify(json.data));
                setDownloadedOffline(true);
                alert('Lesson successfully saved to offline storage! You can access this without an internet connection.');
            }
        } catch (err) {
            console.error('Download offline error', err);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="max-w-4xl mx-auto px-4 py-12 animate-pulse space-y-6">
                    <div className="h-8 w-1/3 bg-slate-900 rounded" />
                    <div className="h-96 bg-slate-900 rounded-2xl" />
                </div>
            </AppLayout>
        );
    }

    if (!lesson) {
        return (
            <AppLayout>
                <div className="max-w-4xl mx-auto px-4 py-16 text-center text-white">
                    <h2 className="text-xl font-bold">Lesson Not Found</h2>
                    <Link href="/education" className="text-indigo-400 mt-4 inline-block">
                        ← Return to Hub
                    </Link>
                </div>
            </AppLayout>
        );
    }

    const title = locale === 'am' && lesson.title_am ? lesson.title_am : lesson.title;
    const content = locale === 'am' && lesson.content_am ? lesson.content_am : lesson.content;

    return (
        <AppLayout>
            <Head title={`${title} | Lesson Viewer`} />

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Top Nav: Back to course & Controls */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <Link
                        href={`/education/courses/${lesson.course_id}`}
                        className="inline-flex items-center text-sm font-medium text-slate-400 hover:text-white transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1.5" />
                        {locale === 'am' ? 'ወደ ኮርሱ ተመለስ' : 'Back to Course Syllabus'}
                    </Link>

                    <div className="flex items-center space-x-3 w-full sm:w-auto justify-end">
                        {lesson.is_downloadable && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleDownloadOffline}
                                className={`text-xs border-slate-700 ${
                                    downloadedOffline ? 'bg-emerald-950/60 text-emerald-400 border-emerald-800' : 'text-slate-300'
                                }`}
                            >
                                <Download className="w-3.5 h-3.5 mr-1.5" />
                                {downloadedOffline
                                    ? locale === 'am' ? 'ከመስመር ውጭ ተቀምጧል' : 'Saved Offline'
                                    : locale === 'am' ? 'ከመስመር ውጭ አውርድ' : 'Download for Offline'}
                            </Button>
                        )}

                        <div className="flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700">
                            <button
                                onClick={() => setLocale('en')}
                                className={`px-2.5 py-1 rounded text-xs font-semibold ${locale === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-400'}`}
                            >
                                EN
                            </button>
                            <button
                                onClick={() => setLocale('am')}
                                className={`px-2.5 py-1 rounded text-xs font-semibold ${locale === 'am' ? 'bg-indigo-600 text-white' : 'text-slate-400'}`}
                            >
                                አማርኛ
                            </button>
                        </div>
                    </div>
                </div>

                {/* Lesson Header */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-3">
                    <div className="flex items-center space-x-3">
                        <Badge className="bg-indigo-600 text-white capitalize">
                            {lesson.lesson_type}
                        </Badge>
                        <span className="flex items-center text-xs text-slate-400">
                            <Clock className="w-3.5 h-3.5 mr-1 text-slate-500" />
                            {lesson.duration_minutes} min duration
                        </span>
                        {isCompleted && (
                            <span className="flex items-center text-xs text-emerald-400 font-semibold">
                                <CheckCircle2 className="w-3.5 h-3.5 mr-1" />
                                {locale === 'am' ? 'ተጠናቋል' : 'Completed'}
                            </span>
                        )}
                    </div>

                    <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
                        {title}
                    </h1>
                </div>

                {/* Video Player if video */}
                {lesson.lesson_type === 'video' && lesson.video_embed_url && (
                    <div className="bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden aspect-video shadow-2xl">
                        <iframe
                            src={lesson.video_embed_url}
                            title={title}
                            className="w-full h-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                        />
                    </div>
                )}

                {/* PDF Viewer Link if PDF */}
                {lesson.lesson_type === 'pdf' && lesson.pdf_url && (
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center space-y-4">
                        <FileText className="w-12 h-12 text-indigo-400 mx-auto" />
                        <h3 className="text-lg font-bold text-white">Download PDF Study Guide</h3>
                        <p className="text-sm text-slate-400 max-w-md mx-auto">
                            Comprehensive handbook with visual infographics for offline reading and campus study groups.
                        </p>
                        <a href={lesson.pdf_url} target="_blank" rel="noopener noreferrer">
                            <Button className="bg-indigo-600 hover:bg-indigo-500 text-white">
                                <Download className="w-4 h-4 mr-2" />
                                Open & Download PDF
                            </Button>
                        </a>
                    </div>
                )}

                {/* Article Reader Content */}
                {content && (
                    <div className="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 text-slate-200 leading-relaxed text-base sm:text-lg">
                        <div
                            className="prose prose-invert max-w-none space-y-4"
                            dangerouslySetInnerHTML={{
                                __html: content.replace(/\n\n/g, '<br/><br/>').replace(/## (.*)/g, '<h2 class="text-xl font-bold text-white mt-4">$1</h2>').replace(/### (.*)/g, '<h3 class="text-lg font-semibold text-indigo-300 mt-2">$1</h3>')
                            }}
                        />
                    </div>
                )}

                {/* Interactive Payload / Quick Exercise */}
                {lesson.interactive_payload && (
                    <div className="bg-indigo-950/40 border border-indigo-800/40 rounded-2xl p-6 space-y-4">
                        <div className="flex items-center space-x-2 text-indigo-300 font-bold text-base">
                            <ShieldAlert className="w-5 h-5 text-amber-400" />
                            <span>{locale === 'am' ? 'የተግባር ልምምድ (Interactive Scenario)' : 'Hands-on Safety Scenario'}</span>
                        </div>
                        <p className="text-sm text-slate-300">
                            {lesson.interactive_payload.scenario || 'Apply your digital awareness skills to solve this real-world security challenge.'}
                        </p>
                    </div>
                )}

                {/* Bottom Navigation & Complete Action */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        {lesson.navigation.previous ? (
                            <Link href={`/education/lessons/${lesson.navigation.previous.id}`}>
                                <Button variant="outline" size="sm" className="border-slate-700 text-slate-300">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    {locale === 'am' ? 'ቀዳሚ ትምህርት' : 'Previous Lesson'}
                                </Button>
                            </Link>
                        ) : (
                            <span />
                        )}
                    </div>

                    <Button
                        onClick={handleComplete}
                        disabled={completing}
                        className={`font-semibold px-6 py-2.5 ${
                            isCompleted
                                ? 'bg-slate-800 text-emerald-400 border border-emerald-800 hover:bg-slate-750'
                                : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-lg'
                        }`}
                    >
                        <CheckCircle2 className="w-4 h-4 mr-2" />
                        {completing
                            ? 'Marking Complete...'
                            : isCompleted
                            ? locale === 'am' ? 'ተጠናቋል (ቀጣይ ሂድ)' : 'Completed (Next)'
                            : locale === 'am' ? 'አጠናቅቄያለሁ' : 'Mark as Complete & Next'}
                    </Button>

                    <div>
                        {lesson.navigation.next ? (
                            <Link href={`/education/lessons/${lesson.navigation.next.id}`}>
                                <Button variant="outline" size="sm" className="border-slate-700 text-slate-300">
                                    {locale === 'am' ? 'ቀጣይ ትምህርት' : 'Next Lesson'}
                                    <ArrowRight className="w-4 h-4 ml-2" />
                                </Button>
                            </Link>
                        ) : (
                            <Link href={`/education/courses/${lesson.course_id}`}>
                                <Button variant="outline" size="sm" className="border-indigo-700 text-indigo-300">
                                    {locale === 'am' ? 'የኮርሱ ፈተና' : 'Course Quiz'}
                                    <HelpCircle className="w-4 h-4 ml-2" />
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
