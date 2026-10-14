import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Gamepad2,
    Trophy,
    Users,
    Building2,
    BookOpen,
    Menu,
    X,
    LogOut,
    User as UserIcon,
    ShieldCheck,
    CheckCircle2,
    AlertCircle,
} from 'lucide-react';
import { LanguageSwitcher } from '../components/ui/LanguageSwitcher';
import { ThemeSwitcher } from '../components/ui/ThemeSwitcher';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { useTranslation } from '../hooks/useTranslation';

interface AuthUser {
    id: number;
    uuid: string;
    name: string;
    email?: string;
    phone?: string;
    roles: string[];
    profile?: {
        gamer_tag: string;
        avatar_url?: string;
        university?: string;
        student_verified: boolean;
    };
}

interface PageProps {
    auth: {
        user: AuthUser | null;
    };
    appName: string;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: any;
}

export const AppLayout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const { t } = useTranslation();
    const { auth, appName, flash } = usePage<PageProps>().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen flex flex-col bg-slate-950 text-slate-100 transition-colors">
            {/* Top Navigation */}
            <header className="sticky top-0 z-40 w-full border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    {/* Logo */}
                    <div className="flex items-center gap-3">
                        <Link href="/" className="flex items-center gap-2.5 group">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-amber-500 flex items-center justify-center shadow-lg shadow-emerald-950/60 group-hover:scale-105 transition-transform">
                                <Gamepad2 className="w-6 h-6 text-slate-950" />
                            </div>
                            <div>
                                <span className="font-extrabold text-base tracking-tight text-white block">
                                    Campus Esports
                                </span>
                                <span className="text-[10px] font-semibold text-emerald-400 uppercase tracking-widest block -mt-1">
                                    Ethiopia
                                </span>
                            </div>
                        </Link>
                    </div>

                    {/* Desktop Navigation Links */}
                    <nav className="hidden md:flex items-center gap-1">
                        <Link
                            href="/"
                            className="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-900 transition-colors"
                        >
                            {t('common.platform_title')}
                        </Link>
                        <Link
                            href="/tournaments"
                            className="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-900 transition-colors flex items-center gap-1.5"
                        >
                            <Trophy className="w-4 h-4 text-amber-400" />
                            {t('common.tournaments')}
                        </Link>
                        <Link
                            href="/teams"
                            className="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-900 transition-colors flex items-center gap-1.5"
                        >
                            <Users className="w-4 h-4 text-emerald-400" />
                            {t('common.teams')}
                        </Link>
                        <Link
                            href="/venues"
                            className="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-900 transition-colors flex items-center gap-1.5"
                        >
                            <Building2 className="w-4 h-4 text-indigo-400" />
                            {t('common.venues')}
                        </Link>
                        <Link
                            href="/learning"
                            className="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-900 transition-colors flex items-center gap-1.5"
                        >
                            <BookOpen className="w-4 h-4 text-teal-400" />
                            {t('common.learning')}
                        </Link>
                    </nav>

                    {/* Right Utilities (Theme, Lang, Auth) */}
                    <div className="hidden md:flex items-center gap-3">
                        <LanguageSwitcher />
                        <ThemeSwitcher />

                        {auth.user ? (
                            <div className="flex items-center gap-3 pl-2 border-l border-slate-800">
                                <Link
                                    href="/profile"
                                    className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-700 transition-colors"
                                >
                                    <div className="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
                                        {auth.user.profile?.gamer_tag?.substring(0, 2).toUpperCase() || auth.user.name.substring(0, 2).toUpperCase()}
                                    </div>
                                    <div className="text-left">
                                        <div className="text-xs font-bold text-slate-100 flex items-center gap-1">
                                            {auth.user.profile?.gamer_tag || auth.user.name}
                                            {auth.user.profile?.student_verified && (
                                                <span title="Verified University Student">
                                                    <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                                                </span>
                                            )}
                                        </div>
                                        {auth.user.roles[0] && (
                                            <span className="text-[10px] text-slate-400 capitalize">
                                                {auth.user.roles[0].replace('_', ' ')}
                                            </span>
                                        )}
                                    </div>
                                </Link>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleLogout}
                                    title={t('common.logout')}
                                    className="p-2 text-slate-400 hover:text-rose-400"
                                >
                                    <LogOut className="w-4 h-4" />
                                </Button>
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 pl-2 border-l border-slate-800">
                                <Link href="/login">
                                    <Button variant="ghost" size="sm">
                                        {t('common.login')}
                                    </Button>
                                </Link>
                                <Link href="/register">
                                    <Button variant="primary" size="sm">
                                        {t('common.register')}
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </div>

                    {/* Mobile hamburger */}
                    <div className="flex md:hidden items-center gap-2">
                        <LanguageSwitcher />
                        <button
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="p-2 rounded-lg bg-slate-900 text-slate-300 hover:text-white"
                            aria-label="Toggle navigation menu"
                        >
                            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                        </button>
                    </div>
                </div>

                {/* Mobile Menu Dropdown */}
                {mobileMenuOpen && (
                    <div className="md:hidden border-b border-slate-800 bg-slate-900/95 px-4 pt-2 pb-6 space-y-3">
                        <Link
                            href="/"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            {t('common.platform_title')}
                        </Link>
                        <Link
                            href="/tournaments"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            {t('common.tournaments')}
                        </Link>
                        <Link
                            href="/teams"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            {t('common.teams')}
                        </Link>
                        <Link
                            href="/venues"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            {t('common.venues')}
                        </Link>
                        <Link
                            href="/learning"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            {t('common.learning')}
                        </Link>

                        <div className="pt-4 border-t border-slate-800 flex items-center justify-between">
                            <ThemeSwitcher />
                            {auth.user ? (
                                <div className="flex items-center gap-3">
                                    <Link href="/profile" className="text-sm font-bold text-emerald-400">
                                        {auth.user.profile?.gamer_tag || auth.user.name}
                                    </Link>
                                    <Button variant="danger" size="sm" onClick={handleLogout}>
                                        {t('common.logout')}
                                    </Button>
                                </div>
                            ) : (
                                <div className="flex items-center gap-2">
                                    <Link href="/login">
                                        <Button variant="outline" size="sm">
                                            {t('common.login')}
                                        </Button>
                                    </Link>
                                    <Link href="/register">
                                        <Button variant="primary" size="sm">
                                            {t('common.register')}
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </header>

            {/* Flash Alerts */}
            {flash?.success && (
                <div className="bg-emerald-500/10 border-b border-emerald-500/30 text-emerald-300 px-4 py-3 flex items-center justify-center gap-2 text-sm font-medium">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                    <span>{flash.success}</span>
                </div>
            )}
            {flash?.error && (
                <div className="bg-rose-500/10 border-b border-rose-500/30 text-rose-300 px-4 py-3 flex items-center justify-center gap-2 text-sm font-medium">
                    <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
                    <span>{flash.error}</span>
                </div>
            )}

            {/* Main Page Content */}
            <main className="flex-1 w-full">
                {children}
            </main>

            {/* Footer */}
            <footer className="border-t border-slate-900 bg-slate-950/90 py-10 text-xs text-slate-400">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div className="space-y-3">
                        <div className="flex items-center gap-2">
                            <div className="w-7 h-7 rounded-lg bg-emerald-500 flex items-center justify-center text-slate-950 font-black text-xs">
                                ET
                            </div>
                            <span className="font-bold text-sm text-slate-200">Campus Esports Ethiopia</span>
                        </div>
                        <p className="text-slate-400 leading-relaxed">
                            Connecting Ethiopian university gamers, official university clubs, gaming zones, and sponsors on a high-performance tournament network.
                        </p>
                    </div>

                    <div>
                        <h4 className="font-semibold text-slate-200 mb-3 uppercase tracking-wider text-[11px]">
                            Universities Network
                        </h4>
                        <ul className="space-y-2">
                            <li>Addis Ababa University (AAU)</li>
                            <li>AASTU & ASTU</li>
                            <li>Hawassa University</li>
                            <li>Jimma University</li>
                            <li>Bahir Dar University</li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-semibold text-slate-200 mb-3 uppercase tracking-wider text-[11px]">
                            Platform
                        </h4>
                        <ul className="space-y-2">
                            <li><Link href="/tournaments" className="hover:text-emerald-400">Tournaments & Brackets</Link></li>
                            <li><Link href="/venues" className="hover:text-emerald-400">Gaming Zones & LAN Venues</Link></li>
                            <li><Link href="/learning" className="hover:text-emerald-400">Digital Literacy & Safety</Link></li>
                            <li><Link href="/tickets" className="hover:text-emerald-400">Local Ticketing (Telebirr/Chapa)</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="font-semibold text-slate-200 mb-3 uppercase tracking-wider text-[11px]">
                            Standards & Compliance
                        </h4>
                        <div className="space-y-2">
                            <p>WCAG 2.1 AA Compliant</p>
                            <p>Minor Safeguarding & Anti-Cheat</p>
                            <p>Low-bandwidth & Offline Ready</p>
                            <Badge variant="success" size="sm" className="mt-2">Phase 1 Foundation</Badge>
                        </div>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-6 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p>© {new Date().getFullYear()} Campus Esports Ethiopia. All rights reserved.</p>
                    <div className="flex items-center gap-4">
                        <span>English / አማርኛ</span>
                        <span>•</span>
                        <span>PostgreSQL / Redis / Reverb</span>
                    </div>
                </div>
            </footer>
        </div>
    );
};
