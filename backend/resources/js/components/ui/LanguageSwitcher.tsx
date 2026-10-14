import React from 'react';
import { Languages } from 'lucide-react';
import { useTranslation } from '../../hooks/useTranslation';

export const LanguageSwitcher: React.FC = () => {
    const { locale, setLocale } = useTranslation();

    return (
        <button
            onClick={() => setLocale(locale === 'en' ? 'am' : 'en')}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 transition-colors cursor-pointer select-none"
            title={locale === 'en' ? 'Switch to Amharic (አማርኛ)' : 'Switch to English'}
            aria-label="Toggle language"
        >
            <Languages className="w-3.5 h-3.5 text-emerald-400" />
            <span>{locale === 'en' ? 'አማርኛ' : 'English'}</span>
        </button>
    );
};
