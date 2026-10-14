import React from 'react';
import { Loader2 } from 'lucide-react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
    size?: 'sm' | 'md' | 'lg';
    isLoading?: boolean;
    leftIcon?: React.ReactNode;
    rightIcon?: React.ReactNode;
}

export const Button: React.FC<ButtonProps> = ({
    children,
    variant = 'primary',
    size = 'md',
    isLoading = false,
    leftIcon,
    rightIcon,
    className,
    disabled,
    ...props
}) => {
    const baseStyles = 'inline-flex items-center justify-center font-medium transition-colors duration-150 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none cursor-pointer';

    const variants = {
        primary: 'bg-emerald-600 hover:bg-emerald-500 text-white focus:ring-emerald-500 focus:ring-offset-slate-900 shadow-sm shadow-emerald-950/50',
        secondary: 'bg-indigo-600 hover:bg-indigo-500 text-white focus:ring-indigo-500 focus:ring-offset-slate-900 shadow-sm',
        outline: 'border border-slate-700 hover:border-slate-500 text-slate-200 hover:bg-slate-800 focus:ring-slate-400 focus:ring-offset-slate-900',
        ghost: 'text-slate-300 hover:bg-slate-800/60 hover:text-white focus:ring-slate-400 focus:ring-offset-slate-900',
        danger: 'bg-rose-600 hover:bg-rose-500 text-white focus:ring-rose-500 focus:ring-offset-slate-900 shadow-sm',
    };

    const sizes = {
        sm: 'text-xs px-3 py-1.5 gap-1.5',
        md: 'text-sm px-4 py-2 gap-2',
        lg: 'text-base px-6 py-3 gap-2.5',
    };

    return (
        <button
            className={twMerge(clsx(baseStyles, variants[variant], sizes[size], className))}
            disabled={disabled || isLoading}
            {...props}
        >
            {isLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
            ) : leftIcon ? (
                <span className="shrink-0">{leftIcon}</span>
            ) : null}
            <span>{children}</span>
            {!isLoading && rightIcon && <span className="shrink-0">{rightIcon}</span>}
        </button>
    );
};
