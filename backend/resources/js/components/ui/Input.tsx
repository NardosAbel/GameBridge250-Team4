import React, { useId } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    helperText?: string;
    leftIcon?: React.ReactNode;
    rightIcon?: React.ReactNode;
}

export const Input: React.FC<InputProps> = ({
    label,
    error,
    helperText,
    leftIcon,
    rightIcon,
    className,
    id: customId,
    disabled,
    ...props
}) => {
    const generatedId = useId();
    const id = customId || generatedId;
    const errorId = `${id}-error`;
    const helperId = `${id}-helper`;

    return (
        <div className="w-full space-y-1.5 text-left">
            {label && (
                <label htmlFor={id} className="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                    {label}
                </label>
            )}
            <div className="relative flex items-center">
                {leftIcon && (
                    <div className="absolute left-3.5 flex items-center pointer-events-none text-slate-400">
                        {leftIcon}
                    </div>
                )}
                <input
                    id={id}
                    disabled={disabled}
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? errorId : helperText ? helperId : undefined}
                    className={twMerge(
                        clsx(
                            'w-full rounded-lg bg-slate-900 border text-slate-100 placeholder-slate-500 text-sm py-2.5 px-3.5 transition-colors',
                            'focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500',
                            leftIcon ? 'pl-10' : '',
                            rightIcon ? 'pr-10' : '',
                            error ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/40' : 'border-slate-800 hover:border-slate-700',
                            disabled ? 'opacity-60 cursor-not-allowed bg-slate-950' : '',
                            className
                        )
                    )}
                    {...props}
                />
                {rightIcon && (
                    <div className="absolute right-3.5 flex items-center text-slate-400">
                        {rightIcon}
                    </div>
                )}
            </div>
            {error && (
                <p id={errorId} className="text-xs font-medium text-rose-400 animate-fadeIn" role="alert">
                    {error}
                </p>
            )}
            {!error && helperText && (
                <p id={helperId} className="text-xs text-slate-400">
                    {helperText}
                </p>
            )}
        </div>
    );
};
