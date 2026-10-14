import React from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

interface CardProps extends React.HTMLAttributes<HTMLDivElement> {
    hover?: boolean;
}

export const Card: React.FC<CardProps> = ({ children, className, hover = false, ...props }) => {
    return (
        <div
            className={twMerge(
                clsx(
                    'bg-slate-900/90 border border-slate-800 rounded-xl backdrop-blur-sm shadow-xl shadow-black/20',
                    hover ? 'transition-all duration-200 hover:border-slate-700 hover:shadow-2xl hover:-translate-y-0.5' : '',
                    className
                )
            )}
            {...props}
        >
            {children}
        </div>
    );
};

export const CardHeader: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ children, className, ...props }) => {
    return (
        <div className={twMerge('p-4 border-b border-slate-800', className)} {...props}>
            {children}
        </div>
    );
};

export const CardBody: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ children, className, ...props }) => {
    return (
        <div className={twMerge('p-4', className)} {...props}>
            {children}
        </div>
    );
};
