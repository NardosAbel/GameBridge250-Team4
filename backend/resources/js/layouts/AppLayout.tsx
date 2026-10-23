import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    House,
    LogOut,
    Medal,
    MapPin,
    Menu,
    MessageCircle,
    MessagesSquare,
    Ticket,
    Trophy,
    UserRound,
    Users,
    X,
} from 'lucide-react';
import { ThemeSwitcher } from '../components/ui/ThemeSwitcher';
import { Button } from '../components/ui/Button';
import { Avatar } from '../components/ui/Avatar';
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
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const displayName = auth.user?.profile?.gamer_tag || auth.user?.name || 'Player';

    const active = (href: string) => currentPath === href || (href !== '/' && currentPath.startsWith(`${href}/`));
    const itemClass = (href: string) => `flex min-h-11 items-center gap-3 border-l-2 px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lime-300 ${
        active(href) ? 'border-lime-300 text-lime-300' : 'border-transparent text-slate-300 hover:text-white'
    }`;

    const primary = [
        ...(auth.user ? [{ href: '/feed', label: 'Feed', icon: House }] : []),
        { href: '/tournaments', label: 'Competitions', icon: Trophy },
        { href: '/communities', label: 'Communities', icon: MessagesSquare },
        { href: '/teams', label: t('common.teams') || 'Teams', icon: Users },
        ...(auth.user ? [{ href: '/notifications', label: 'Notifications', icon: Bell }] : []),
        ...(auth.user ? [{ href: '/profile', label: 'Profile', icon: UserRound }] : []),
    ];

    const secondary = [
        { href: '/rankings', label: 'Rankings' },
        { href: '/venues', label: 'Venues' },
        { href: '/education', label: 'Learning' },
        ...(auth.user ? [
            { href: '/chat', label: 'Messages' },
            { href: '/my-tickets', label: 'Tickets' },
        ] : []),
    ];

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 md:grid md:grid-cols-[15rem_minmax(0,1fr)]">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:bg-lime-300 focus:px-4 focus:py-2 focus:font-medium focus:text-slate-950 focus:outline-none"
            >
                Skip to main content
            </a>

            <aside className="sticky top-0 hidden h-screen flex-col border-r border-slate-800 px-3 py-5 md:flex">
                <Link href="/" className="mb-6 flex items-center gap-3 px-3">
                    <span className="flex h-9 w-9 items-center justify-center border border-lime-300 font-display text-xs text-lime-300">GB</span>
                    <span>
                        <span className="block font-display text-base leading-none text-white">GameBridge</span>
                        <span className="mt-1 block text-xs text-slate-400">Campus Ethiopia</span>
                    </span>
                </Link>
                <nav aria-label="Primary" className="flex flex-1 flex-col gap-1">
                    {primary.map(({ href, label, icon: Icon }) => (
                        <Link key={href} href={href} className={itemClass(href)}>
                            <Icon className="h-4 w-4" aria-hidden="true" />
                            {label}
                        </Link>
                    ))}
                    <p className="mt-6 px-3 text-xs text-slate-500">More</p>
                    <Link href="/rankings" className={itemClass('/rankings')}>
                        <Medal className="h-4 w-4" aria-hidden="true" />
                        Rankings
                    </Link>
                    <Link href="/venues" className={itemClass('/venues')}>
                        <MapPin className="h-4 w-4" aria-hidden="true" />
                        Venues
                    </Link>
                    <Link href="/education" className={itemClass('/education')}>
                        <BookOpen className="h-4 w-4" aria-hidden="true" />
                        Learning
                    </Link>
                    {auth.user && (
                        <Link href="/chat" className={itemClass('/chat')}>
                            <MessageCircle className="h-4 w-4" aria-hidden="true" />
                            Messages
                        </Link>
                    )}
                    {auth.user && (
                        <Link href="/my-tickets" className={itemClass('/my-tickets')}>
                            <Ticket className="h-4 w-4" aria-hidden="true" />
                            Tickets
                        </Link>
                    )}
                </nav>
                <div className="mt-4 border-t border-slate-800 pt-4">
                    <div className="mb-3 px-3"><ThemeSwitcher /></div>
                    {auth.user ? (
                        <div className="flex items-center gap-2 px-2">
                            <Link href="/profile" className="flex min-w-0 flex-1 items-center gap-2">
                                <Avatar name={displayName} src={auth.user.profile?.avatar_url} size="sm" />
                                <span className="truncate text-sm text-white">{displayName}</span>
                            </Link>
                            <button type="button" onClick={handleLogout} className="inline-flex min-h-11 min-w-11 items-center justify-center text-slate-400 hover:text-rose-300" title={t('common.logout')}>
                                <LogOut className="h-4 w-4" />
                            </button>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-2 px-3">
                            <Link href="/login"><Button variant="outline" className="w-full">{t('common.login')}</Button></Link>
                            <Link href="/register"><Button variant="primary" className="w-full">{t('common.register')}</Button></Link>
                        </div>
                    )}
                </div>
            </aside>

            <div className="flex min-w-0 flex-col">
                <header className="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-slate-800 bg-slate-950 px-4 md:hidden">
                    <Link href="/" className="flex items-center gap-2">
                        <span className="flex h-8 w-8 items-center justify-center border border-lime-300 font-display text-xs text-lime-300">GB</span>
                        <span className="font-display text-sm text-white">GameBridge</span>
                    </Link>
                    <div className="flex items-center gap-1">
                        <ThemeSwitcher />
                        <button
                            type="button"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="inline-flex min-h-11 min-w-11 items-center justify-center text-slate-200"
                            aria-label="Toggle navigation menu"
                            aria-expanded={mobileMenuOpen}
                        >
                            {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                    </div>
                </header>

                {mobileMenuOpen && (
                    <div className="border-b border-slate-800 bg-slate-950 px-4 py-3 md:hidden">
                        {secondary.map((link) => (
                            <Link key={link.href} href={link.href} onClick={() => setMobileMenuOpen(false)} className="block min-h-11 py-2 text-sm text-slate-200">
                                {link.label}
                            </Link>
                        ))}
                        {auth.user ? (
                            <button type="button" onClick={handleLogout} className="min-h-11 text-sm text-rose-300">{t('common.logout')}</button>
                        ) : (
                            <Link href="/login" className="block min-h-11 py-2 text-sm text-lime-300">{t('common.login')}</Link>
                        )}
                    </div>
                )}

                {flash?.success && (
                    <div className="border-b border-slate-800 px-4 py-3 text-center text-sm text-lime-300" role="status">{flash.success}</div>
                )}
                {flash?.error && (
                    <div className="border-b border-slate-800 px-4 py-3 text-center text-sm text-rose-300" role="alert">{flash.error}</div>
                )}

                <main id="main-content" className="flex-1 w-full pb-20 md:pb-8" tabIndex={-1}>
                    {children}
                </main>

                <footer className="hidden border-t border-slate-800 px-6 py-6 text-sm text-slate-400 md:block">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p>GameBridge · Campus esports for Ethiopia</p>
                        <nav aria-label="Footer" className="flex flex-wrap gap-x-4 gap-y-2">
                            <Link href="/feed" className="hover:text-white">Feed</Link>
                            <Link href="/tournaments" className="hover:text-white">Competitions</Link>
                            <Link href="/communities" className="hover:text-white">Communities</Link>
                            <Link href="/teams" className="hover:text-white">Teams</Link>
                        </nav>
                    </div>
                </footer>
            </div>

            <nav aria-label="Mobile" className="fixed inset-x-0 bottom-0 z-40 border-t border-slate-800 bg-slate-950 md:hidden">
                <div className="grid grid-cols-5">
                    {[
                        auth.user
                            ? { href: '/feed', label: 'Feed', icon: House }
                            : { href: '/', label: 'Home', icon: House },
                        { href: '/tournaments', label: 'Events', icon: Trophy },
                        { href: '/communities', label: 'Clubs', icon: MessagesSquare },
                        { href: '/teams', label: 'Teams', icon: Users },
                        auth.user
                            ? { href: '/profile', label: 'Profile', icon: UserRound }
                            : { href: '/login', label: 'Sign in', icon: UserRound },
                    ].map(({ href, label, icon: Icon }) => (
                        <Link key={href} href={href} className={`flex min-h-14 flex-col items-center justify-center gap-1 text-[11px] ${active(href) ? 'text-lime-300' : 'text-slate-400'}`}>
                            <Icon className="h-5 w-5" aria-hidden="true" />
                            {label}
                        </Link>
                    ))}
                </div>
            </nav>
        </div>
    );
};
