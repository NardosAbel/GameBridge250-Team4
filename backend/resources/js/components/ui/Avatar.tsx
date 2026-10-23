import React, { useState } from 'react';

const sizes = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-16 w-16 text-xl',
};

export function Avatar({
    name,
    src,
    size = 'md',
}: {
    name: string;
    src?: string | null;
    size?: keyof typeof sizes;
}) {
    const [failed, setFailed] = useState(false);
    const initials = (name || 'GB').replace(/[^A-Za-z0-9]/g, '').slice(0, 2).toUpperCase() || 'GB';

    if (src && !failed) {
        return (
            <img
                src={src}
                alt=""
                onError={() => setFailed(true)}
                className={`${sizes[size]} shrink-0 object-cover`}
            />
        );
    }

    return (
        <span className={`${sizes[size]} inline-flex shrink-0 items-center justify-center bg-slate-900 font-display text-lime-300`}>
            {initials}
        </span>
    );
}
