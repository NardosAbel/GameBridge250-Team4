import React from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    variant?: 'primary' | 'success' | 'warning' | 'danger' | 'neutral' | 'accent' | 'outline' | 'info';
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
        primary: 'text-lime-300',
        success: 'text-lime-300',
        warning: 'text-amber-200',
        danger: 'text-rose-300',
        neutral: 'text-slate-400',
        accent: 'text-lime-300',
        outline: 'text-slate-400',
        info: 'text-slate-300',
    };

    const sizes = {
        sm: 'text-[11px] px-2 py-0.5',
        md: 'text-xs px-2.5 py-1',
    };

    return (
        <span
            className={twMerge(
                clsx(
                    'inline-flex items-center font-medium',
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
