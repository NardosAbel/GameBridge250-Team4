import React, { useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { Button } from './Button';

interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children: React.ReactNode;
}

const FOCUSABLE = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

export const Modal: React.FC<ModalProps> = ({
    isOpen,
    onClose,
    title,
    description,
    children,
}) => {
    const panelRef = useRef<HTMLDivElement>(null);
    const previouslyFocused = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const active = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const panel = panelRef.current;
        // Only restore focus to the opener when it lives outside this dialog.
        previouslyFocused.current = active && panel && !panel.contains(active) ? active : null;

        const focusable = () => Array.from(panel?.querySelectorAll<HTMLElement>(FOCUSABLE) ?? []);
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const nodes = focusable();
            if (nodes.length === 0) {
                event.preventDefault();
                return;
            }

            const first = nodes[0];
            const last = nodes[nodes.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', handleKeyDown);

        // Focus after listeners are registered so an immediate close still restores.
        const initial =
            focusable().find((el) => el.getAttribute('aria-label') !== 'Close dialog') ??
            focusable()[0] ??
            panel;
        initial?.focus();

        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', handleKeyDown);
            const previous = previouslyFocused.current;
            if (previous && document.contains(previous)) {
                previous.focus();
            }
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fadeIn"
            role="presentation"
            onClick={onClose}
        >
            <div
                ref={panelRef}
                className="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl text-left"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div>
                        <h3 id="modal-title" className="text-lg font-bold text-slate-100">
                            {title}
                        </h3>
                        {description && (
                            <p className="text-xs text-slate-400 mt-0.5">{description}</p>
                        )}
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onClose}
                        aria-label="Close dialog"
                        className="p-1 text-slate-400 hover:text-slate-100"
                    >
                        <X className="w-5 h-5" />
                    </Button>
                </div>
                <div className="py-4">{children}</div>
            </div>
        </div>
    );
};
