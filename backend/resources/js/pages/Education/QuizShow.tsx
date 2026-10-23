import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Modal } from '../../components/ui/Modal';
import {
    HelpCircle,
    CheckCircle2,
    XCircle,
    Award,
    RotateCcw,
    ArrowLeft,
    Clock,
    AlertCircle,
    Sparkles,
    ShieldCheck
} from 'lucide-react';

interface Question {
    id: string;
    question: string;
    question_am?: string;
    options: string[];
    options_am?: string[];
}

interface QuizDetails {
    id: number;
    uuid: string;
    course_id?: number;
    lesson_id?: number;
    title: string;
    title_am?: string;
    description?: string;
    description_am?: string;
    passing_score: number;
    max_retries: number;
    time_limit_minutes?: number;
    questions: Question[];
    user_stats: {
        attempts_count: number;
        has_passed: boolean;
        best_score?: number | null;
    };
}

interface SubmissionResult {
    score: number;
    passed: boolean;
    passing_score: number;
    feedback: Array<{
        question_id: string;
        user_selection: number;
        correct_index: number;
        is_correct: boolean;
        explanation?: string;
        explanation_am?: string;
    }>;
    certificate?: {
        certificate_number: string;
        verification_code: string;
    } | null;
}

interface Props {
    quizId: string | number;
}

export default function QuizShow({ quizId }: Props) {
    const [quiz, setQuiz] = useState<QuizDetails | null>(null);
    const [loading, setLoading] = useState(true);
    const [locale] = useState<'en' | 'am'>('en');
    const [selectedAnswers, setSelectedAnswers] = useState<Record<string, number>>({});
    const [submitting, setSubmitting] = useState(false);
    const [result, setResult] = useState<SubmissionResult | null>(null);
    const [showCertModal, setShowCertModal] = useState(false);

    useEffect(() => {
        fetchQuiz();
    }, [quizId]);

    const fetchQuiz = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/education/quizzes/${quizId}`);
            const json = await res.json();
            if (json.success) {
                setQuiz(json.data);
            }
        } catch (err) {
            console.error('Failed to load quiz', err);
        } finally {
            setLoading(false);
        }
    };

    const handleSelectOption = (questionId: string, optionIndex: number) => {
        if (result) return; // Locked once submitted
        setSelectedAnswers((prev) => ({
            ...prev,
            [questionId]: optionIndex,
        }));
    };

    const handleSubmit = async () => {
        if (!quiz) return;
        setSubmitting(true);
        try {
            const res = await fetch(`/api/v1/education/quizzes/${quiz.id}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ answers: selectedAnswers }),
            });
            const json = await res.json();
            if (json.success) {
                setResult(json.data);
                if (json.data.certificate) {
                    setShowCertModal(true);
                }
            } else {
                alert(json.message || 'Failed to submit quiz.');
            }
        } catch (err) {
            console.error('Submission error', err);
        } finally {
            setSubmitting(false);
        }
    };

    const handleRetry = () => {
        setResult(null);
        setSelectedAnswers({});
        fetchQuiz();
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="max-w-3xl mx-auto px-4 py-12 animate-pulse space-y-6">
                    <div className="h-8 w-1/3 bg-slate-900 rounded" />
                    <div className="h-64 bg-slate-900 rounded-2xl" />
                </div>
            </AppLayout>
        );
    }

    if (!quiz) {
        return (
            <AppLayout>
                <div className="max-w-3xl mx-auto px-4 py-16 text-center text-white">
                    <h2 className="text-xl font-bold">Quiz Not Found</h2>
                    <Link href="/education" className="text-indigo-400 mt-4 inline-block">
                        ← Return to Learning Hub
                    </Link>
                </div>
            </AppLayout>
        );
    }

    const title = locale === 'am' && quiz.title_am ? quiz.title_am : quiz.title;
    const allAnswered = quiz.questions.length > 0 && Object.keys(selectedAnswers).length === quiz.questions.length;

    return (
        <AppLayout>
            <Head title={`${title} | Knowledge Check`} />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header Controls */}
                <div className="flex items-center justify-between">
                    <Link
                        href={quiz.course_id ? `/education/courses/${quiz.course_id}` : '/education'}
                        className="inline-flex items-center text-sm font-medium text-slate-400 hover:text-white transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1.5" />
                        {locale === 'am' ? 'ተመለስ' : 'Return'}
                    </Link>

                </div>

                {/* Quiz Title Card */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <Badge className="bg-purple-900/80 text-purple-300 border border-purple-700">
                            Knowledge Assessment
                        </Badge>
                        <div className="text-xs text-slate-400 flex items-center space-x-3">
                            <span>Passing Score: <strong className="text-emerald-400">{quiz.passing_score}%</strong></span>
                            <span>•</span>
                            <span>Max Retries: <strong>{quiz.max_retries === 0 ? 'Unlimited' : quiz.max_retries}</strong></span>
                        </div>
                    </div>

                    <h1 className="text-2xl font-extrabold text-white">{title}</h1>
                    {quiz.description && (
                        <p className="text-sm text-slate-400">
                            {locale === 'am' && quiz.description_am ? quiz.description_am : quiz.description}
                        </p>
                    )}
                </div>

                {/* Result Feedback Banner */}
                {result && (
                    <div
                        className={`p-6 rounded-2xl border ${
                            result.passed
                                ? 'bg-emerald-950/50 border-emerald-800 text-emerald-200'
                                : 'bg-red-950/50 border-red-800 text-red-200'
                        } space-y-3 shadow-xl`}
                    >
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-3">
                                {result.passed ? (
                                    <CheckCircle2 className="w-8 h-8 text-emerald-400" />
                                ) : (
                                    <XCircle className="w-8 h-8 text-red-400" />
                                )}
                                <div>
                                    <h3 className="text-lg font-bold">
                                        {result.passed
                                            ? locale === 'am' ? 'እንኳን ደስ አለዎት! ፈተናውን አልፈዋል!' : 'Congratulations! You Passed!'
                                            : locale === 'am' ? 'ማለፊያ ነጥብ አላገኙም' : 'Quiz Not Passed'}
                                    </h3>
                                    <p className="text-xs opacity-80">
                                        Your Score: <span className="font-extrabold text-base">{result.score}%</span> (Passing score: {result.passing_score}%)
                                    </p>
                                </div>
                            </div>

                            {!result.passed && (
                                <Button
                                    onClick={handleRetry}
                                    variant="outline"
                                    className="border-red-700 bg-red-900/30 hover:bg-red-900/60 text-white text-xs"
                                >
                                    <RotateCcw className="w-3.5 h-3.5 mr-1.5" />
                                    {locale === 'am' ? 'እንደገና ሞክር' : 'Retry Quiz'}
                                </Button>
                            )}
                        </div>

                        {result.certificate && (
                            <div className="pt-2 flex items-center justify-between border-t border-emerald-800/60">
                                <span className="text-xs text-emerald-300 font-semibold flex items-center">
                                    <Sparkles className="w-4 h-4 mr-1.5 text-amber-300" />
                                    Official Certificate Generated: {result.certificate.verification_code}
                                </span>
                                <Button
                                    size="sm"
                                    onClick={() => setShowCertModal(true)}
                                    className="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold"
                                >
                                    View Certificate
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                {/* Questions List */}
                <div className="space-y-6">
                    {quiz.questions.map((q, qIndex) => {
                        const feedbackItem = result?.feedback.find((f) => f.question_id === q.id);
                        const selectedOption = selectedAnswers[q.id];

                        const qText = locale === 'am' && q.question_am ? q.question_am : q.question;
                        const optionsList = locale === 'am' && q.options_am && q.options_am.length > 0 ? q.options_am : q.options;

                        return (
                            <div
                                key={q.id}
                                className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <h3 className="text-base font-bold text-white flex items-start">
                                        <span className="text-indigo-400 font-extrabold mr-2.5">
                                            {qIndex + 1}.
                                        </span>
                                        <span>{qText}</span>
                                    </h3>

                                    {feedbackItem && (
                                        <span className="flex-shrink-0">
                                            {feedbackItem.is_correct ? (
                                                <Badge className="bg-emerald-950 text-emerald-400 border border-emerald-800">
                                                    Correct
                                                </Badge>
                                            ) : (
                                                <Badge className="bg-red-950 text-red-400 border border-red-800">
                                                    Incorrect
                                                </Badge>
                                            )}
                                        </span>
                                    )}
                                </div>

                                <div className="space-y-2.5">
                                    {optionsList.map((opt, oIdx) => {
                                        const isSelected = selectedOption === oIdx;
                                        let optionClass = 'border-slate-800 bg-slate-950/60 text-slate-300 hover:border-slate-700';

                                        if (isSelected) {
                                            optionClass = 'border-indigo-600 bg-indigo-950/40 text-white';
                                        }

                                        if (feedbackItem) {
                                            if (oIdx === feedbackItem.correct_index) {
                                                optionClass = 'border-emerald-600 bg-emerald-950/40 text-emerald-200 font-semibold';
                                            } else if (isSelected && !feedbackItem.is_correct) {
                                                optionClass = 'border-red-600 bg-red-950/40 text-red-200 line-through';
                                            }
                                        }

                                        return (
                                            <button
                                                key={oIdx}
                                                type="button"
                                                disabled={!!result}
                                                onClick={() => handleSelectOption(q.id, oIdx)}
                                                className={`w-full text-left p-3.5 rounded-xl border text-sm transition flex items-center justify-between ${optionClass}`}
                                            >
                                                <span>{opt}</span>
                                                {feedbackItem && oIdx === feedbackItem.correct_index && (
                                                    <CheckCircle2 className="w-4 h-4 text-emerald-400 flex-shrink-0 ml-2" />
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>

                                {feedbackItem && feedbackItem.explanation && (
                                    <div className="p-3 bg-slate-950 rounded-xl border border-slate-800/80 text-xs text-slate-400">
                                        <strong className="text-slate-300">Explanation: </strong>
                                        {locale === 'am' && feedbackItem.explanation_am ? feedbackItem.explanation_am : feedbackItem.explanation}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Submit Actions */}
                {!result && (
                    <div className="p-4 bg-slate-900 border border-slate-800 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="text-xs text-slate-400">
                            {Object.keys(selectedAnswers).length} of {quiz.questions.length} answered
                        </div>

                        <Button
                            onClick={handleSubmit}
                            disabled={!allAnswered || submitting}
                            className="w-full sm:w-auto bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold px-8 py-3 shadow-lg"
                        >
                            {submitting
                                ? 'Grading Assessment...'
                                : locale === 'am' ? 'ፈተናውን አስረክብ' : 'Submit Assessment'}
                        </Button>
                    </div>
                )}

                {/* Certificate Award Modal */}
                {result?.certificate && (
                    <Modal
                        isOpen={showCertModal}
                        onClose={() => setShowCertModal(false)}
                        title="Official Certificate Earned!"
                    >
                        <div className="space-y-5 text-center p-4">
                            <div className="p-4 bg-gradient-to-br from-amber-500/20 to-orange-500/10 rounded-2xl border border-amber-500/30 inline-block mx-auto">
                                <Award className="w-16 h-16 text-amber-400 mx-auto" />
                            </div>

                            <div>
                                <h3 className="text-xl font-bold text-white">Digital Awareness Achievement</h3>
                                <p className="text-xs text-slate-400 mt-1">
                                    You have successfully completed all lessons and passed the final evaluation.
                                </p>
                            </div>

                            <div className="p-4 bg-slate-950 rounded-xl border border-slate-800 text-left space-y-2">
                                <div className="text-xs text-slate-400">Certificate Number:</div>
                                <div className="font-mono text-sm text-indigo-300 font-bold">
                                    {result.certificate.certificate_number}
                                </div>
                                <div className="text-xs text-slate-400 pt-1">Verification Code:</div>
                                <div className="font-mono text-base text-amber-400 font-extrabold tracking-wider">
                                    {result.certificate.verification_code}
                                </div>
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Link href="/verify-certificate" className="flex-1">
                                    <Button variant="outline" className="w-full border-slate-700 text-slate-200">
                                        Verify Portal
                                    </Button>
                                </Link>
                                <Link href="/education/dashboard" className="flex-1">
                                    <Button className="w-full bg-indigo-600 hover:bg-indigo-500 text-white">
                                        Go to Dashboard
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </Modal>
                )}
            </div>
        </AppLayout>
    );
}
