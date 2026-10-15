import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import {
    Ticket as TicketIcon,
    QrCode,
    Calendar,
    Clock,
    MapPin,
    Building2,
    Send,
    RotateCcw,
    CheckCircle2,
    AlertCircle,
    Copy,
    Share2,
    ShieldCheck
} from 'lucide-react';

interface TicketItem {
    id: number;
    reference_number: string;
    qr_payload: string;
    qr_signature: string;
    status: string;
    holder_name?: string;
    created_at: string;
    ticket_type?: {
        id: number;
        name: string;
        type: string;
        price: number;
        currency: string;
    };
    event?: {
        id: number;
        title: string;
        start_time: string;
        venue?: {
            id: number;
            name: string;
            address: string;
            city: string;
        };
    };
    tournament?: {
        id: number;
        title: string;
    };
}

export default function MyTickets() {
    const [tickets, setTickets] = useState<TicketItem[]>([]);
    const [loading, setLoading] = useState(true);

    // Active QR Modal
    const [selectedTicket, setSelectedTicket] = useState<TicketItem | null>(null);

    // Transfer Modal
    const [transferTicket, setTransferTicket] = useState<TicketItem | null>(null);
    const [recipientEmail, setRecipientEmail] = useState('');
    const [transferLoading, setTransferLoading] = useState(false);
    const [transferMessage, setTransferMessage] = useState<string | null>(null);
    const [transferError, setTransferError] = useState<string | null>(null);

    // Refund Modal
    const [refundTicket, setRefundTicket] = useState<TicketItem | null>(null);
    const [refundReason, setRefundReason] = useState('');
    const [refundLoading, setRefundLoading] = useState(false);
    const [refundMessage, setRefundMessage] = useState<string | null>(null);

    const fetchTickets = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/tickets/my-tickets');
            const data = await res.json();
            if (data.success) {
                setTickets(data.data);
            }
        } catch (e) {
            console.error('Failed to load tickets', e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTickets();
    }, []);

    const handleTransfer = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!transferTicket) return;

        setTransferLoading(true);
        setTransferError(null);
        setTransferMessage(null);

        try {
            const res = await fetch(`/api/v1/tickets/${transferTicket.id}/transfer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    recipient_email: recipientEmail,
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Transfer failed');
            }

            setTransferMessage(data.message);
            fetchTickets();
            setTimeout(() => {
                setTransferTicket(null);
                setRecipientEmail('');
                setTransferMessage(null);
            }, 2000);
        } catch (err: any) {
            setTransferError(err.message);
        } finally {
            setTransferLoading(false);
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge variant="success">Active Pass</Badge>;
            case 'checked_in':
                return <Badge variant="info">Checked In</Badge>;
            case 'used':
                return <Badge variant="secondary">Used</Badge>;
            case 'transferred':
                return <Badge variant="warning">Transferred</Badge>;
            case 'refunded':
            case 'revoked':
                return <Badge variant="danger">{status.toUpperCase()}</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    return (
        <AppLayout>
            <Head title="My Tickets & Passes - Campus Esports Ethiopia" />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="p-2 rounded-xl bg-amber-500/10 text-amber-400">
                                <TicketIcon className="w-6 h-6" />
                            </span>
                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                My Tickets & Entry Passes
                            </h1>
                        </div>
                        <p className="text-slate-400 text-sm">
                            Access signed QR tokens for entry gates, transfer passes to teammates, or review event details.
                        </p>
                    </div>

                    <Link href="/venues">
                        <Button variant="outline" className="text-xs">
                            Browse Upcoming Events &rarr;
                        </Button>
                    </Link>
                </div>

                {/* Tickets List */}
                {loading ? (
                    <div className="flex flex-col items-center justify-center py-24">
                        <div className="w-10 h-10 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
                        <p className="mt-4 text-slate-400 text-sm">Retrieving your passes...</p>
                    </div>
                ) : tickets.length === 0 ? (
                    <Card className="text-center py-16 bg-slate-900/40 border-dashed border-slate-800">
                        <TicketIcon className="w-12 h-12 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-lg font-bold text-slate-300">No Tickets Found</h3>
                        <p className="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                            You haven't registered or purchased tickets for any upcoming gaming tournaments or venue events.
                        </p>
                        <Link href="/venues">
                            <Button variant="primary" size="sm">
                                Find Events to Attend
                            </Button>
                        </Link>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {tickets.map((t) => (
                            <Card
                                key={t.id}
                                className={`overflow-hidden border transition-all ${
                                    t.status === 'active'
                                        ? 'bg-slate-900/80 border-slate-800 hover:border-amber-500/50'
                                        : 'bg-slate-900/40 border-slate-800/60 opacity-80'
                                }`}
                            >
                                <div className="p-6">
                                    <div className="flex items-start justify-between gap-3 mb-4">
                                        <div>
                                            <span className="text-[11px] font-bold text-emerald-400 uppercase tracking-wider block mb-1">
                                                {t.ticket_type?.name || 'General Admission'}
                                            </span>
                                            <h3 className="text-lg font-black text-white leading-tight">
                                                {t.event?.title || t.tournament?.title || 'Campus Esports Event'}
                                            </h3>
                                        </div>
                                        <div>{getStatusBadge(t.status)}</div>
                                    </div>

                                    {/* Event Details */}
                                    <div className="space-y-1.5 text-xs text-slate-400 mb-5">
                                        {t.event?.start_time && (
                                            <div className="flex items-center gap-2">
                                                <Calendar className="w-3.5 h-3.5 text-slate-500" />
                                                <span>{new Date(t.event.start_time).toLocaleString()}</span>
                                            </div>
                                        )}
                                        {t.event?.venue && (
                                            <div className="flex items-center gap-2">
                                                <MapPin className="w-3.5 h-3.5 text-slate-500" />
                                                <span>{t.event.venue.name}, {t.event.venue.city}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Ticket Reference & Mini QR Preview */}
                                    <div className="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between gap-4">
                                        <div>
                                            <span className="text-[10px] text-slate-500 uppercase tracking-widest block font-mono">
                                                Ticket Ref
                                            </span>
                                            <span className="text-xs font-mono font-bold text-slate-200">
                                                {t.reference_number}
                                            </span>
                                        </div>

                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => setSelectedTicket(t)}
                                            className="text-xs flex items-center gap-1.5 border-amber-500/30 text-amber-300 hover:bg-amber-950/30"
                                        >
                                            <QrCode className="w-4 h-4" />
                                            View Pass QR
                                        </Button>
                                    </div>

                                    {/* Action Buttons for Active Tickets */}
                                    {t.status === 'active' && (
                                        <div className="flex items-center justify-between gap-2 mt-4 pt-4 border-t border-slate-800/80">
                                            <button
                                                type="button"
                                                onClick={() => setTransferTicket(t)}
                                                className="text-xs text-slate-400 hover:text-white flex items-center gap-1 transition-colors"
                                            >
                                                <Send className="w-3.5 h-3.5 text-cyan-400" />
                                                Transfer Pass
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => {
                                                    alert('Please contact the tournament organizer or venue manager to initiate a ticket refund.');
                                                }}
                                                className="text-xs text-slate-500 hover:text-rose-400 transition-colors"
                                            >
                                                Request Refund
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Signed QR Code Modal (Contains ONLY reference & signature) */}
            <Modal
                isOpen={!!selectedTicket}
                onClose={() => setSelectedTicket(null)}
                title="Signed Entry Pass"
            >
                {selectedTicket && (
                    <div className="flex flex-col items-center text-center space-y-4">
                        <div className="p-5 rounded-2xl bg-white text-slate-950 shadow-2xl flex flex-col items-center">
                            {/* Stylized QR Visual Representation */}
                            <div className="w-48 h-48 bg-slate-950 p-2 rounded-xl flex items-center justify-center relative overflow-hidden">
                                <QrCode className="w-40 h-40 text-emerald-400" />
                                <div className="absolute inset-0 border-2 border-emerald-500/40 rounded-xl pointer-events-none"></div>
                            </div>
                            <span className="text-[11px] font-mono font-bold text-slate-800 mt-3">
                                {selectedTicket.reference_number}
                            </span>
                        </div>

                        <div className="max-w-sm">
                            <h4 className="font-bold text-white text-base">
                                {selectedTicket.event?.title || selectedTicket.tournament?.title}
                            </h4>
                            <p className="text-xs text-slate-400 mt-1">
                                Present this QR code at the venue gate for check-in. The scanner validates the cryptographic signature offline or online.
                            </p>
                        </div>

                        {/* Privacy & Security Notice */}
                        <div className="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400 text-left w-full space-y-1">
                            <div className="flex items-center gap-1.5 text-emerald-400 font-bold">
                                <ShieldCheck className="w-3.5 h-3.5" /> Privacy Protected Payload
                            </div>
                            <p className="text-[10px] text-slate-500">
                                Payload contains only ticket reference token and HMAC-SHA256 signature. No personal identity or payment details are embedded.
                            </p>
                            <code className="text-[10px] text-slate-400 block font-mono break-all pt-1">
                                {selectedTicket.qr_payload}
                            </code>
                        </div>

                        <Button variant="ghost" onClick={() => setSelectedTicket(null)} className="w-full">
                            Close Pass
                        </Button>
                    </div>
                )}
            </Modal>

            {/* Transfer Pass Modal */}
            <Modal
                isOpen={!!transferTicket}
                onClose={() => setTransferTicket(null)}
                title="Transfer Ticket to Teammate"
            >
                {transferTicket && (
                    <form onSubmit={handleTransfer} className="space-y-4">
                        {transferMessage && (
                            <div className="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                                <CheckCircle2 className="w-4 h-4 shrink-0" />
                                <span>{transferMessage}</span>
                            </div>
                        )}
                        {transferError && (
                            <div className="p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                <span>{transferError}</span>
                            </div>
                        )}

                        <p className="text-xs text-slate-300">
                            Transferring this pass will revoke your QR token and issue a newly signed QR token to the recipient's account. This action cannot be reversed.
                        </p>

                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">
                                Recipient User Email
                            </label>
                            <Input
                                type="email"
                                required
                                placeholder="teammate@university.edu.et"
                                value={recipientEmail}
                                onChange={(e) => setRecipientEmail(e.target.value)}
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="ghost" onClick={() => setTransferTicket(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary" disabled={transferLoading}>
                                {transferLoading ? 'Transferring...' : 'Confirm Transfer'}
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>
        </AppLayout>
    );
}
