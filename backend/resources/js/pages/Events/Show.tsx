import React, { useState, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import {
    Calendar,
    Clock,
    MapPin,
    Building2,
    Ticket,
    CheckCircle2,
    AlertCircle,
    Trophy,
    CreditCard,
    Smartphone,
    Building,
    Tag,
    ChevronRight,
    ShieldCheck
} from 'lucide-react';

interface EventShowProps {
    eventId: number;
}

export default function EventShow({ eventId }: EventShowProps) {
    const { auth } = usePage().props as any;
    const [event, setEvent] = useState<any>(null);
    const [ticketTypes, setTicketTypes] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    // Cart / Selection state
    const [selectedQuantities, setSelectedQuantities] = useState<Record<number, number>>({});
    const [promoCode, setPromoCode] = useState('');
    const [promoDiscount, setPromoDiscount] = useState(0);

    // Checkout Modal
    const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
    const [selectedGateway, setSelectedGateway] = useState('telebirr');
    const [checkoutLoading, setCheckoutLoading] = useState(false);
    const [checkoutError, setCheckoutError] = useState<string | null>(null);

    const fetchEvent = async () => {
        try {
            const [eventRes, typesRes] = await Promise.all([
                fetch(`/api/v1/events/${eventId}`),
                fetch(`/api/v1/tickets/types?event_id=${eventId}`),
            ]);

            const eventData = await eventRes.json();
            const typesData = await typesRes.json();

            if (eventData.success) setEvent(eventData.data);
            if (typesData.success) setTicketTypes(typesData.data);
        } catch (e) {
            console.error('Failed to load event or ticket tiers', e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchEvent();
    }, [eventId]);

    const handleQuantityChange = (ticketTypeId: number, qty: number, maxAllowed: number) => {
        const validQty = Math.max(0, Math.min(qty, maxAllowed));
        setSelectedQuantities((prev) => ({
            ...prev,
            [ticketTypeId]: validQty,
        }));
    };

    const totalTicketsSelected = Object.values(selectedQuantities).reduce((acc, qty) => acc + qty, 0);

    const subtotal = ticketTypes.reduce((acc, type) => {
        const qty = selectedQuantities[type.id] || 0;
        return acc + qty * type.price;
    }, 0);

    const total = Math.max(0, subtotal - promoDiscount);

    const handleCheckout = async () => {
        if (!auth.user) {
            router.visit('/login');
            return;
        }

        const items = Object.entries(selectedQuantities)
            .filter(([_, qty]) => qty > 0)
            .map(([typeId, qty]) => ({
                ticket_type_id: parseInt(typeId),
                quantity: qty,
            }));

        if (items.length === 0) {
            alert('Please select at least 1 ticket to checkout.');
            return;
        }

        setCheckoutLoading(true);
        setCheckoutError(null);

        try {
            // 1. Create Ticket Order
            const orderRes = await fetch('/api/v1/tickets/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    items,
                    promo_code: promoCode || undefined,
                }),
            });

            const orderData = await orderRes.json();
            if (!orderRes.ok) {
                throw new Error(orderData.message || 'Failed to place ticket order');
            }

            const order = orderData.data;

            // 2. Initiate Payment
            const payRes = await fetch('/api/v1/payments/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    order_id: order.id,
                    provider: selectedGateway,
                }),
            });

            const payData = await payRes.json();
            if (!payRes.ok) {
                throw new Error(payData.message || 'Payment initiation failed');
            }

            setIsCheckoutOpen(false);
            // Redirect to attendee tickets page
            router.visit('/my-tickets');
        } catch (err: any) {
            setCheckoutError(err.message);
        } finally {
            setCheckoutLoading(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="flex flex-col items-center justify-center py-24">
                    <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <p className="mt-4 text-slate-400 text-sm">Loading event details & ticket inventory...</p>
                </div>
            </AppLayout>
        );
    }

    if (!event) {
        return (
            <AppLayout>
                <div className="max-w-3xl mx-auto py-16 text-center">
                    <h2 className="text-xl font-bold text-white">Event Not Found</h2>
                    <Link href="/venues" className="mt-4 inline-block text-emerald-400 hover:underline">
                        &larr; Back to Venues & Events
                    </Link>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`${event.title} - Tickets & Entry Pass`} />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Event Hero */}
                <div className="rounded-3xl overflow-hidden border border-slate-800 bg-slate-900/60 mb-8 relative">
                    <div className="h-60 sm:h-72 bg-gradient-to-r from-emerald-950/80 via-slate-950 to-slate-950 relative flex items-center p-6 sm:p-10">
                        <div className="max-w-2xl">
                            <Badge variant="success" className="mb-3 uppercase tracking-wider text-[11px]">
                                {event.event_type?.replace(/_/g, ' ')}
                            </Badge>
                            <h1 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
                                {event.title}
                            </h1>
                            <p className="text-slate-300 text-xs sm:text-sm mt-2 line-clamp-2">
                                {event.description || 'Join local university students and community gamers for intense competitive matches, prizes, and entertainment.'}
                            </p>

                            <div className="flex flex-wrap items-center gap-4 mt-4 text-xs text-slate-300">
                                <span className="flex items-center gap-1.5">
                                    <Calendar className="w-4 h-4 text-emerald-400" />
                                    {new Date(event.start_time).toLocaleDateString()}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <Clock className="w-4 h-4 text-cyan-400" />
                                    {new Date(event.start_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                </span>
                                {event.venue && (
                                    <span className="flex items-center gap-1.5">
                                        <MapPin className="w-4 h-4 text-amber-400" />
                                        {event.venue.name} ({event.venue.city})
                                    </span>
                                )}
                            </div>
                        </div>

                        {event.tournament && (
                            <div className="hidden md:block absolute right-8 top-8 p-4 rounded-2xl bg-slate-950/80 border border-emerald-500/30 text-right">
                                <div className="text-[10px] text-emerald-400 font-bold uppercase tracking-wider flex items-center justify-end gap-1 mb-1">
                                    <Trophy className="w-3.5 h-3.5" /> Official Tournament Event
                                </div>
                                <div className="text-sm font-bold text-white">{event.tournament.title}</div>
                                <Link href={`/tournaments/${event.tournament.id}`} className="text-xs text-cyan-400 hover:underline mt-1 inline-block">
                                    View Tournament Bracket &rarr;
                                </Link>
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Ticket Tiers (Left 2 cols) */}
                    <div className="lg:col-span-2 space-y-6">
                        <div>
                            <h2 className="text-xl font-bold text-white flex items-center gap-2 mb-1">
                                <Ticket className="w-5 h-5 text-emerald-400" />
                                Select Tickets & Passes
                            </h2>
                            <p className="text-slate-400 text-xs">
                                Choose passes for yourself or team members. All tickets come with cryptographically signed QR entry.
                            </p>
                        </div>

                        {ticketTypes.length === 0 ? (
                            <Card className="bg-slate-900/40 border-dashed border-slate-800 text-center py-12">
                                <Ticket className="w-12 h-12 text-slate-600 mx-auto mb-2" />
                                <h3 className="text-sm font-bold text-slate-300">No Tickets on Sale</h3>
                                <p className="text-xs text-slate-500 mt-1">
                                    Tickets for this event haven't been released yet or have sold out.
                                </p>
                            </Card>
                        ) : (
                            <div className="space-y-4">
                                {ticketTypes.map((type) => {
                                    const isSoldOut = type.remaining_quantity <= 0;
                                    const currentQty = selectedQuantities[type.id] || 0;

                                    return (
                                        <div
                                            key={type.id}
                                            className={`p-5 rounded-2xl border transition-all ${
                                                currentQty > 0
                                                    ? 'bg-slate-900/90 border-emerald-500 shadow-lg shadow-emerald-950/20'
                                                    : 'bg-slate-900/50 border-slate-800 hover:border-slate-700'
                                            }`}
                                        >
                                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <h3 className="font-bold text-white text-base">{type.name}</h3>
                                                        <span className="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                                                            {type.type}
                                                        </span>
                                                        {isSoldOut && (
                                                            <Badge variant="danger" className="text-[10px]">
                                                                Sold Out
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-slate-400 max-w-md">
                                                        {type.description || 'Standard entry pass including arena seat and LAN access.'}
                                                    </p>

                                                    {type.perks && type.perks.length > 0 && (
                                                        <div className="flex flex-wrap gap-1.5 pt-1">
                                                            {type.perks.map((p: string, i: number) => (
                                                                <span key={i} className="text-[10px] text-emerald-400 bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-900/60">
                                                                    ✓ {p}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="flex sm:flex-col items-center sm:items-end justify-between gap-3 shrink-0">
                                                    <div className="text-right">
                                                        <span className="text-xl font-black text-white">
                                                            {type.price === 0 ? 'FREE' : `${type.price.toLocaleString()} ${type.currency}`}
                                                        </span>
                                                        <span className="block text-[10px] text-slate-400">
                                                            {type.remaining_quantity} passes left
                                                        </span>
                                                    </div>

                                                    {!isSoldOut && (
                                                        <div className="flex items-center gap-2 bg-slate-950 rounded-xl border border-slate-800 p-1">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleQuantityChange(type.id, currentQty - 1, type.max_per_user)}
                                                                disabled={currentQty <= 0}
                                                                className="w-7 h-7 rounded-lg bg-slate-900 text-slate-200 hover:bg-slate-800 disabled:opacity-30 font-bold flex items-center justify-center text-sm"
                                                            >
                                                                -
                                                            </button>
                                                            <span className="w-8 text-center font-bold text-sm text-white">
                                                                {currentQty}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleQuantityChange(type.id, currentQty + 1, Math.min(type.max_per_user, type.remaining_quantity))}
                                                                disabled={currentQty >= Math.min(type.max_per_user, type.remaining_quantity)}
                                                                className="w-7 h-7 rounded-lg bg-slate-900 text-slate-200 hover:bg-slate-800 disabled:opacity-30 font-bold flex items-center justify-center text-sm"
                                                            >
                                                                +
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Order Summary Sidebar */}
                    <div className="space-y-6">
                        <Card className="bg-slate-900/70 border-slate-800 sticky top-24">
                            <CardHeader>
                                <h3 className="font-bold text-white text-base">Order Summary</h3>
                            </CardHeader>
                            <CardBody className="space-y-4 text-xs">
                                <div className="space-y-2 border-b border-slate-800 pb-3">
                                    {ticketTypes
                                        .filter((t) => (selectedQuantities[t.id] || 0) > 0)
                                        .map((t) => (
                                            <div key={t.id} className="flex justify-between items-center text-slate-300">
                                                <span>{t.name} x {selectedQuantities[t.id]}</span>
                                                <span className="font-semibold text-white">
                                                    {(t.price * selectedQuantities[t.id]).toLocaleString()} {t.currency}
                                                </span>
                                            </div>
                                        ))}

                                    {totalTicketsSelected === 0 && (
                                        <p className="text-slate-500 italic text-center py-2">
                                            No tickets selected yet.
                                        </p>
                                    )}
                                </div>

                                {/* Promo Code */}
                                <div>
                                    <label className="block text-[11px] font-semibold text-slate-400 mb-1">
                                        Promo / Discount Code
                                    </label>
                                    <div className="flex gap-2">
                                        <Input
                                            placeholder="e.g. STUDENT25"
                                            value={promoCode}
                                            onChange={(e) => setPromoCode(e.target.value.toUpperCase())}
                                            className="text-xs uppercase bg-slate-950 border-slate-800"
                                        />
                                    </div>
                                </div>

                                {/* Total Calculation */}
                                <div className="space-y-1.5 pt-2 border-t border-slate-800">
                                    <div className="flex justify-between text-slate-400">
                                        <span>Subtotal ({totalTicketsSelected} tickets)</span>
                                        <span>{subtotal.toLocaleString()} ETB</span>
                                    </div>
                                    {promoDiscount > 0 && (
                                        <div className="flex justify-between text-emerald-400">
                                            <span>Promo Discount</span>
                                            <span>-{promoDiscount.toLocaleString()} ETB</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between text-base font-black text-white pt-2 border-t border-slate-800">
                                        <span>Total Due</span>
                                        <span className="text-emerald-400">{total.toLocaleString()} ETB</span>
                                    </div>
                                </div>

                                <Button
                                    variant="primary"
                                    className="w-full py-3 text-sm font-bold shadow-lg shadow-emerald-950/60"
                                    disabled={totalTicketsSelected === 0}
                                    onClick={() => setIsCheckoutOpen(true)}
                                >
                                    Proceed to Checkout
                                </Button>

                                <div className="text-[10px] text-slate-500 text-center flex items-center justify-center gap-1.5">
                                    <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                                    Signed QR Ticket • Instant Verification • Fraud Proof
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Payment & Checkout Modal */}
            <Modal isOpen={isCheckoutOpen} onClose={() => setIsCheckoutOpen(false)} title="Checkout & Local Payment">
                <div className="space-y-5">
                    {checkoutError && (
                        <div className="p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0" />
                            <span>{checkoutError}</span>
                        </div>
                    )}

                    <div className="p-4 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center text-sm">
                        <span className="text-slate-300">Total Payment Amount</span>
                        <span className="font-extrabold text-lg text-emerald-400">{total.toLocaleString()} ETB</span>
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-200 mb-2">
                            Choose Payment Provider
                        </label>
                        <div className="grid grid-cols-2 gap-2.5">
                            {[
                                { id: 'telebirr', name: 'Telebirr', icon: Smartphone, color: 'text-amber-400' },
                                { id: 'chapa', name: 'Chapa (Cards/Banks)', icon: CreditCard, color: 'text-emerald-400' },
                                { id: 'mpesa', name: 'M-Pesa Safaricom', icon: Smartphone, color: 'text-red-400' },
                                { id: 'bank_transfer', name: 'Bank Transfer (CBE)', icon: Building, color: 'text-purple-400' },
                            ].map((gw) => (
                                <button
                                    key={gw.id}
                                    type="button"
                                    onClick={() => setSelectedGateway(gw.id)}
                                    className={`p-3 rounded-xl border text-left flex items-center gap-2.5 transition-all ${
                                        selectedGateway === gw.id
                                            ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm'
                                            : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'
                                    }`}
                                >
                                    <gw.icon className={`w-4 h-4 ${gw.color}`} />
                                    <span className="text-xs font-bold">{gw.name}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    <p className="text-[11px] text-slate-400">
                        After confirming, your ticket will be generated with a secure HMAC-SHA256 digital signature and placed in your account.
                    </p>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="ghost" onClick={() => setIsCheckoutOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            disabled={checkoutLoading}
                            onClick={handleCheckout}
                            className="flex items-center gap-2"
                        >
                            {checkoutLoading ? 'Processing Payment...' : `Pay ${total.toLocaleString()} ETB`}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
