import React from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    variant?: 'primary' | 'success' | 'warning' | 'danger' | 'neutral' | 'accent';
    size?: 'sm' | 'md';
}

export const Badge: React.FC<BadgeProps> = ({
    children,
    variant = 'primary',
    size = 'md',
    className,
    ...props
}) => {
    const variants = {
        primary: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
        success: 'bg-teal-500/15 text-teal-300 border-teal-500/30',
        warning: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        danger: 'bg-rose-500/15 text-rose-300 border-rose-500/30',
        neutral: 'bg-slate-800 text-slate-300 border-slate-700',
        accent: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
    };

    const sizes = {
        sm: 'text-[11px] px-2 py-0.5',
        md: 'text-xs px-2.5 py-1',
    };

    return (
        <span
            className={twMerge(
                clsx(
                    'inline-flex items-center font-medium rounded-full border tracking-wide uppercase',
                    variants[variant],
                    sizes[size],
                    className
                )
            )}
            {...props}
        >
            {children}
        </span>
    );
};
