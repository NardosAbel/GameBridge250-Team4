import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Menu,
    X,
    LogOut,
    ShieldCheck,
    CheckCircle2,
    AlertCircle,
} from 'lucide-react';
import { ThemeSwitcher } from '../components/ui/ThemeSwitcher';
import { Button } from '../components/ui/Button';
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
    const page = usePage<PageProps>();
    const { auth, flash } = page.props;
    const currentPath = page.url.split('?')[0];
    const navClass = (href: string) => `min-h-11 px-3 py-2 text-sm ${currentPath === href || currentPath.startsWith(`${href}/`) ? 'text-lime-300' : 'text-slate-200 hover:text-white'}`;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen flex flex-col bg-slate-950 text-slate-100 transition-colors">
            {/* Accessible Skip to Content Link (WCAG 2.1 AA) */}
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:bg-emerald-500 focus:text-slate-950 focus:font-bold focus:rounded-xl focus:shadow-xl focus:outline-none"
            >
                Skip to main content
            </a>

            {/* Top Navigation */}
            <header className="sticky top-0 z-40 w-full border-b border-slate-800 bg-slate-950">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    {/* Logo */}
                    <div className="flex items-center gap-3">
                        <Link href="/" className="flex items-center gap-3">
                            <span className="flex h-8 w-8 items-center justify-center border border-lime-300 font-display text-xs text-lime-300">
                                GB
                            </span>
                            <span>
                                <span className="block font-display text-sm leading-none text-white">
                                    GameBridge
                                </span>
                                <span className="text-[11px] text-slate-400 block mt-1">
                                    Campus Ethiopia
                                </span>
                            </span>
                        </Link>
                    </div>

                    {/* Desktop Navigation Links */}
                    <nav className="hidden md:flex items-center gap-1" aria-label="Primary">
                        {auth.user && (
                            <Link href="/feed" className={navClass('/feed')}>Feed</Link>
                        )}
                        <Link href="/tournaments" className={navClass('/tournaments')}>Competitions</Link>
                        <Link href="/communities" className={navClass('/communities')}>Communities</Link>
                        <Link href="/teams" className={navClass('/teams')}>{t('common.teams')}</Link>
                        <details className="relative">
                            <summary className="cursor-pointer list-none px-3 py-2 text-sm text-slate-400 hover:text-white">
                                More
                            </summary>
                            <div className="absolute right-0 z-50 mt-1 w-44 border border-slate-700 bg-slate-950 py-1">
                                <Link href="/rankings" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Rankings</Link>
                                <Link href="/venues" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Venues</Link>
                                <Link href="/education" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Learning</Link>
                                <Link href="/recruitment" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Recruitment</Link>
                                {auth.user && (
                                    <>
                                        <Link href="/notifications" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Notifications</Link>
                                        <Link href="/chat" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Messages</Link>
                                        <Link href="/my-tickets" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Tickets</Link>
                                        <Link href="/scanner" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Gate scan</Link>
                                        {auth.user.roles.some((r: string) => ['super_admin', 'moderator'].includes(r)) && (
                                            <Link href="/moderation/queue" className="block px-3 py-2 text-sm text-slate-200 hover:bg-slate-900">Moderation</Link>
                                        )}
                                    </>
                                )}
                            </div>
                        </details>
                    </nav>

                    {/* Right Utilities (Theme, Lang, Auth) */}
                    <div className="hidden md:flex items-center gap-3">
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
                        <ThemeSwitcher />
                        <button
                            type="button"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="inline-flex min-h-11 min-w-11 items-center justify-center text-slate-200"
                            aria-label="Toggle navigation menu"
                            aria-expanded={mobileMenuOpen}
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
                        {auth.user && (
                            <Link href="/feed" onClick={() => setMobileMenuOpen(false)} className="block min-h-11 px-3 py-2 text-sm text-slate-200">
                                Feed
                            </Link>
                        )}
                        <Link
                            href="/tournaments"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block min-h-11 px-3 py-2 text-sm text-slate-200"
                        >
                            {t('common.tournaments')}
                        </Link>
                        <Link href="/communities" onClick={() => setMobileMenuOpen(false)} className="block min-h-11 px-3 py-2 text-sm text-slate-200">
                            Communities
                        </Link>
                        {auth.user && (
                            <Link href="/notifications" onClick={() => setMobileMenuOpen(false)} className="block min-h-11 px-3 py-2 text-sm text-slate-200">
                                Notifications
                            </Link>
                        )}
                        <Link
                            href="/rankings"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            Rankings
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
                            Venues
                        </Link>
                        <Link
                            href="/education"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                        >
                            Learning Hub
                        </Link>
                        {auth.user && (
                            <>
                                <Link
                                    href="/my-tickets"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                                >
                                    My Tickets
                                </Link>
                                <Link
                                    href="/scanner"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800"
                                >
                                    Gate QR Scanner
                                </Link>
                            </>
                        )}

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
            <main id="main-content" className="flex-1 w-full pb-20 md:pb-0" tabIndex={-1}>
                {children}
            </main>

            {auth.user && (
                <nav aria-label="Mobile" className="fixed inset-x-0 bottom-0 z-40 border-t border-slate-800 bg-slate-950 md:hidden">
                    <div className="grid grid-cols-5">
                        {[
                            ['/feed', 'Feed'],
                            ['/tournaments', 'Events'],
                            ['/communities', 'Hubs'],
                            ['/notifications', 'Alerts'],
                            ['/profile', 'Profile'],
                        ].map(([href, label]) => (
                            <Link key={href} href={href} className={`flex min-h-14 items-center justify-center text-xs ${currentPath === href || currentPath.startsWith(`${href}/`) ? 'text-lime-300' : 'text-slate-300'}`}>
                                {label}
                            </Link>
                        ))}
                    </div>
                </nav>
            )}

            {/* Footer */}
            <footer className="border-t border-slate-800 py-8 text-sm text-slate-400">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p>GameBridge · Campus esports for Ethiopia</p>
                    <nav aria-label="Footer" className="flex flex-wrap gap-x-4 gap-y-2">
                        <Link href="/feed" className="hover:text-white">Feed</Link>
                        <Link href="/tournaments" className="hover:text-white">Competitions</Link>
                        <Link href="/communities" className="hover:text-white">Communities</Link>
                        <Link href="/teams" className="hover:text-white">Teams</Link>
                        <Link href="/venues" className="hover:text-white">Venues</Link>
                        <Link href="/education" className="hover:text-white">Learning</Link>
                        <Link href="/my-tickets" className="hover:text-white">Tickets</Link>
                    </nav>
                </div>
            </footer>
        </div>
    );
};
