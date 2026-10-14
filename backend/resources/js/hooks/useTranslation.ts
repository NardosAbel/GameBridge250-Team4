import { useState, useEffect } from 'react';
import en from '../i18n/en.json';
import am from '../i18n/am.json';

type Locale = 'en' | 'am';

const dictionaries: Record<Locale, any> = { en, am };

export function useTranslation() {
    const [locale, setLocaleState] = useState<Locale>(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('locale') as Locale;
            if (saved && (saved === 'en' || saved === 'am')) {
                return saved;
            }
        }
        return 'en';
    });

    const setLocale = (newLocale: Locale) => {
        setLocaleState(newLocale);
        if (typeof window !== 'undefined') {
            localStorage.setItem('locale', newLocale);
            document.documentElement.lang = newLocale;
        }
    };

    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);

    const t = (path: string): string => {
        const parts = path.split('.');
        let current = dictionaries[locale] || dictionaries.en;

        for (const part of parts) {
            if (current && typeof current === 'object' && part in current) {
                current = current[part];
            } else {
                // Fallback to English
                let fallback = dictionaries.en;
                for (const fallbackPart of parts) {
                    if (fallback && typeof fallback === 'object' && fallbackPart in fallback) {
                        fallback = fallback[fallbackPart];
                    } else {
                        return path;
                    }
                }
                return typeof fallback === 'string' ? fallback : path;
            }
        }

        return typeof current === 'string' ? current : path;
    };

    return { t, locale, setLocale };
}
