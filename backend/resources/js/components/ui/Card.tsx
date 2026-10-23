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
                    'border border-slate-800 bg-transparent',
                    hover ? 'hover:border-slate-600' : '',
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
