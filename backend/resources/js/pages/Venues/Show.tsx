import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../layouts/AppLayout';
import { Card, CardHeader, CardBody } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import {
    Building2,
    MapPin,
    Star,
    Clock,
    Phone,
    Mail,
    Globe,
    Calendar,
    Users,
    CheckCircle2,
    AlertCircle,
    UserCheck,
    BarChart3,
    ExternalLink,
    ShieldCheck
} from 'lucide-react';

interface VenueShowProps {
    venueId: number;
}

export default function VenueShow({ venueId }: VenueShowProps) {
    const { auth } = usePage().props as any;
    const [venue, setVenue] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [analytics, setAnalytics] = useState<any>(null);

    // Review Modal
    const [isReviewOpen, setIsReviewOpen] = useState(false);
    const [rating, setRating] = useState(5);
    const [reviewComment, setReviewComment] = useState('');
    const [reviewLoading, setReviewLoading] = useState(false);

    // Staff Modal
    const [isStaffOpen, setIsStaffOpen] = useState(false);
    const [staffUserId, setStaffUserId] = useState('');
    const [staffRole, setStaffRole] = useState('scanner');
    const [staffLoading, setStaffLoading] = useState(false);
    const [staffMessage, setStaffMessage] = useState<string | null>(null);

    const fetchVenue = async () => {
        try {
            const res = await fetch(`/api/v1/venues/${venueId}`);
            const data = await res.json();
            if (data.success) {
                setVenue(data.data);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    const fetchAnalytics = async () => {
        try {
            const res = await fetch(`/api/v1/venues/${venueId}/analytics`);
            const data = await res.json();
            if (data.success) {
                setAnalytics(data.data);
            }
        } catch (e) {
            console.error('Not authorized or error fetching analytics');
        }
    };

    useEffect(() => {
        fetchVenue();
    }, [venueId]);

    useEffect(() => {
        if (venue && auth.user) {
            fetchAnalytics();
        }
    }, [venue, auth.user]);

    const handleReviewSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setReviewLoading(true);
        try {
            const res = await fetch(`/api/v1/venues/${venueId}/reviews`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    rating,
                    comment: reviewComment,
                }),
            });
            const data = await res.json();
            if (res.ok) {
                setIsReviewOpen(false);
                setReviewComment('');
                fetchVenue();
            } else {
                alert(data.message || 'Failed to submit review');
            }
        } catch (e) {
            console.error(e);
        } finally {
            setReviewLoading(false);
        }
    };

    const handleAssignStaff = async (e: React.FormEvent) => {
        e.preventDefault();
        setStaffLoading(true);
        setStaffMessage(null);
        try {
            const res = await fetch(`/api/v1/venues/${venueId}/staff`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    user_id: parseInt(staffUserId),
                    role: staffRole,
                }),
            });
            const data = await res.json();
            if (res.ok) {
                setStaffMessage(data.message);
                setStaffUserId('');
            } else {
                alert(data.message || 'Failed to assign staff');
            }
        } catch (e) {
            console.error(e);
        } finally {
            setStaffLoading(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <div className="flex flex-col items-center justify-center py-24">
                    <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <p className="mt-4 text-slate-400 text-sm">Loading venue details...</p>
                </div>
            </AppLayout>
        );
    }

    if (!venue) {
        return (
            <AppLayout>
                <div className="max-w-3xl mx-auto py-16 text-center">
                    <h2 className="text-xl font-bold text-white">Venue Not Found</h2>
                    <Link href="/venues" className="mt-4 inline-block text-emerald-400 hover:underline">
                        &larr; Back to Venues
                    </Link>
                </div>
            </AppLayout>
        );
    }

    const isOwner = auth.user && (venue.owner?.id === auth.user.id || auth.user.roles?.includes('super_admin'));

    return (
        <AppLayout>
            <Head title={`${venue.name} - Gaming Venue Details`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Hero / Cover */}
                <div className="relative rounded-3xl overflow-hidden border border-slate-800 bg-slate-900/60 mb-8">
                    <div className="h-64 sm:h-80 bg-gradient-to-tr from-slate-950 via-slate-900 to-emerald-950/60 relative">
                        {venue.photos && venue.photos[0] ? (
                            <img src={venue.photos[0]} alt={venue.name} className="w-full h-full object-cover opacity-60" />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center">
                                <Building2 className="w-24 h-24 text-slate-800" />
                            </div>
                        )}
                        <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-transparent"></div>

                        {/* Badges */}
                        <div className="absolute top-4 left-4 flex gap-2">
                            {venue.is_verified && (
                                <Badge variant="success" className="bg-emerald-950/90 text-emerald-400 border border-emerald-500/40 backdrop-blur-sm">
                                    <ShieldCheck className="w-3.5 h-3.5 mr-1" />
                                    Verified Esports Venue
                                </Badge>
                            )}
                            {venue.is_featured && (
                                <Badge variant="warning" className="bg-amber-950/90 text-amber-400 border border-amber-500/40 backdrop-blur-sm">
                                    Featured Arena
                                </Badge>
                            )}
                        </div>

                        {/* Title & Info on Bottom of Hero */}
                        <div className="absolute bottom-6 left-6 right-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
                            <div>
                                <h1 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                                    {venue.name}
                                </h1>
                                <p className="text-slate-300 text-sm mt-1 flex items-center gap-2">
                                    <MapPin className="w-4 h-4 text-emerald-400" />
                                    {venue.address}, {venue.city}
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                {venue.map_link && (
                                    <a
                                        href={venue.map_link}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="px-4 py-2 rounded-xl bg-slate-900/90 border border-slate-700 text-slate-200 text-xs font-semibold hover:border-emerald-500 flex items-center gap-1.5 transition-colors"
                                    >
                                        <Globe className="w-3.5 h-3.5 text-cyan-400" />
                                        Google Maps
                                        <ExternalLink className="w-3 h-3 text-slate-500" />
                                    </a>
                                )}

                                {auth.user && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setIsReviewOpen(true)}
                                        className="flex items-center gap-1.5 text-xs"
                                    >
                                        <Star className="w-3.5 h-3.5 text-amber-400" />
                                        Rate Venue
                                    </Button>
                                )}

                                {isOwner && (
                                    <Button
                                        size="sm"
                                        variant="primary"
                                        onClick={() => setIsStaffOpen(true)}
                                        className="flex items-center gap-1.5 text-xs shadow-lg shadow-emerald-950/50"
                                    >
                                        <UserCheck className="w-3.5 h-3.5" />
                                        Assign Gate Staff
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Content Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left 2 Cols: Details & Events */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* About & Facilities */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader>
                                <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                    <Building2 className="w-5 h-5 text-emerald-400" />
                                    About Venue & Equipment
                                </h2>
                            </CardHeader>
                            <CardBody className="space-y-6">
                                <p className="text-sm text-slate-300 leading-relaxed">
                                    {venue.description || 'Welcome to one of Ethiopia’s dedicated competitive esports venues equipped with high-speed internet, gaming gear, and community events.'}
                                </p>

                                <div>
                                    <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                                        Facilities & Amenities
                                    </h4>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        {(venue.facilities || []).map((f: string) => (
                                            <div
                                                key={f}
                                                className="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center gap-2 text-xs text-slate-200"
                                            >
                                                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                                                <span className="capitalize">{f.replace(/_/g, ' ')}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </CardBody>
                        </Card>

                        {/* Upcoming Events / Tournaments */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader className="flex items-center justify-between">
                                <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                    <Calendar className="w-5 h-5 text-cyan-400" />
                                    Upcoming Events & Tournaments
                                </h2>
                            </CardHeader>
                            <CardBody>
                                {(!venue.events || venue.events.length === 0) ? (
                                    <div className="text-center py-8 text-slate-500 text-sm">
                                        No upcoming events scheduled at this venue currently.
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {venue.events.map((evt: any) => (
                                            <div
                                                key={evt.id}
                                                className="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-slate-700 transition-colors"
                                            >
                                                <div>
                                                    <span className="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider block mb-1">
                                                        {evt.event_type?.replace(/_/g, ' ')}
                                                    </span>
                                                    <h4 className="font-bold text-white text-base">
                                                        {evt.title}
                                                    </h4>
                                                    <p className="text-xs text-slate-400 mt-1 flex items-center gap-2">
                                                        <Clock className="w-3.5 h-3.5 text-slate-500" />
                                                        {new Date(evt.start_time).toLocaleString()}
                                                    </p>
                                                </div>

                                                <Link href={`/events/${evt.id}`}>
                                                    <Button size="sm" variant="primary" className="text-xs">
                                                        Get Tickets
                                                    </Button>
                                                </Link>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardBody>
                        </Card>

                        {/* Reviews & Ratings */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader className="flex items-center justify-between">
                                <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                    <Star className="w-5 h-5 text-amber-400" />
                                    Attendee Reviews ({venue.reviews_count || 0})
                                </h2>
                                <div className="text-sm font-bold text-amber-400">
                                    ★ {venue.average_rating ? venue.average_rating.toFixed(1) : 'New'} / 5.0
                                </div>
                            </CardHeader>
                            <CardBody>
                                {(!venue.reviews || venue.reviews.length === 0) ? (
                                    <p className="text-center py-6 text-slate-500 text-xs">
                                        No reviews yet. Be the first to rate this venue!
                                    </p>
                                ) : (
                                    <div className="space-y-4">
                                        {venue.reviews.map((rev: any) => (
                                            <div key={rev.id} className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                                                <div className="flex items-center justify-between mb-2">
                                                    <span className="font-bold text-slate-200 text-xs">
                                                        {rev.user?.name || 'Gamer'}
                                                    </span>
                                                    <div className="flex items-center text-amber-400 text-xs">
                                                        {'★'.repeat(rev.rating)}
                                                        <span className="text-slate-600">{'★'.repeat(5 - rev.rating)}</span>
                                                    </div>
                                                </div>
                                                <p className="text-xs text-slate-400">
                                                    {rev.comment}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardBody>
                        </Card>
                    </div>

                    {/* Right Col: Operating Info & Manager Analytics */}
                    <div className="space-y-6">
                        {/* Quick Specs */}
                        <Card className="bg-slate-900/60 border-slate-800">
                            <CardHeader>
                                <h3 className="font-bold text-white text-sm">Venue Information</h3>
                            </CardHeader>
                            <CardBody className="space-y-4 text-xs">
                                <div className="flex justify-between items-center py-1.5 border-b border-slate-800">
                                    <span className="text-slate-400">Capacity</span>
                                    <span className="font-bold text-white">{venue.capacity} attendees</span>
                                </div>
                                <div className="flex justify-between items-center py-1.5 border-b border-slate-800">
                                    <span className="text-slate-400">Hourly Rate</span>
                                    <span className="font-bold text-emerald-400">
                                        {venue.pricing_details?.hourly_rate || 50} ETB / hr
                                    </span>
                                </div>
                                <div className="flex justify-between items-center py-1.5 border-b border-slate-800">
                                    <span className="text-slate-400">Contact Phone</span>
                                    <span className="text-slate-200 font-medium">
                                        {venue.contact_details?.phone || 'Available on request'}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center py-1.5">
                                    <span className="text-slate-400">City & Region</span>
                                    <span className="text-slate-200 font-medium">{venue.city}, Ethiopia</span>
                                </div>
                            </CardBody>
                        </Card>

                        {/* Venue Analytics (Only for Staff / Manager) */}
                        {analytics && (
                            <Card className="bg-gradient-to-br from-slate-900 via-slate-900/80 to-emerald-950/30 border-emerald-500/30 shadow-lg">
                                <CardHeader>
                                    <h3 className="font-bold text-emerald-300 text-sm flex items-center gap-2">
                                        <BarChart3 className="w-4 h-4 text-emerald-400" />
                                        Manager Live Analytics
                                    </h3>
                                </CardHeader>
                                <CardBody className="space-y-4 text-xs">
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="p-3 rounded-xl bg-slate-950/70 border border-slate-800 text-center">
                                            <div className="text-lg font-black text-emerald-400">
                                                {analytics.total_event_attendance}
                                            </div>
                                            <div className="text-[10px] text-slate-400 uppercase mt-0.5">Total Check-Ins</div>
                                        </div>
                                        <div className="p-3 rounded-xl bg-slate-950/70 border border-slate-800 text-center">
                                            <div className="text-lg font-black text-amber-400">
                                                {analytics.repeat_attendee_rate}%
                                            </div>
                                            <div className="text-[10px] text-slate-400 uppercase mt-0.5">Repeat Visitors</div>
                                        </div>
                                    </div>

                                    <div className="p-3 rounded-xl bg-slate-950/70 border border-slate-800">
                                        <div className="flex justify-between text-slate-300 mb-1">
                                            <span>Ticketing Gross Revenue</span>
                                            <span className="font-bold text-white">{analytics.gross_ticket_revenue.toLocaleString()} ETB</span>
                                        </div>
                                        <div className="flex justify-between text-slate-400 text-[11px]">
                                            <span>Events Hosted</span>
                                            <span>{analytics.events_hosted}</span>
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Rate Venue Modal */}
            <Modal isOpen={isReviewOpen} onClose={() => setIsReviewOpen(false)} title={`Rate ${venue.name}`}>
                <form onSubmit={handleReviewSubmit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-2">Your Rating</label>
                        <div className="flex items-center gap-3">
                            {[1, 2, 3, 4, 5].map((s) => (
                                <button
                                    key={s}
                                    type="button"
                                    onClick={() => setRating(s)}
                                    className={`text-2xl transition-transform hover:scale-125 ${s <= rating ? 'text-amber-400' : 'text-slate-700'}`}
                                >
                                    ★
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Review Comment</label>
                        <textarea
                            rows={3}
                            required
                            placeholder="Share your experience regarding internet ping, PC setups, staff..."
                            value={reviewComment}
                            onChange={(e) => setReviewComment(e.target.value)}
                            className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={() => setIsReviewOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" disabled={reviewLoading}>
                            {reviewLoading ? 'Submitting...' : 'Submit Review'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Assign Staff Modal */}
            <Modal isOpen={isStaffOpen} onClose={() => setIsStaffOpen(false)} title="Assign Gate & Venue Staff">
                <form onSubmit={handleAssignStaff} className="space-y-4">
                    {staffMessage && (
                        <div className="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                            <CheckCircle2 className="w-4 h-4 shrink-0" />
                            <span>{staffMessage}</span>
                        </div>
                    )}

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Staff User ID</label>
                        <Input
                            type="number"
                            required
                            placeholder="e.g. 5"
                            value={staffUserId}
                            onChange={(e) => setStaffUserId(e.target.value)}
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Assigned Role</label>
                        <select
                            value={staffRole}
                            onChange={(e) => setStaffRole(e.target.value)}
                            className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="scanner">Gate Scanner Only (QR check-in)</option>
                            <option value="moderator">Venue Moderator</option>
                            <option value="manager">Venue Operations Manager</option>
                        </select>
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={() => setIsStaffOpen(false)}>Close</Button>
                        <Button type="submit" variant="primary" disabled={staffLoading}>
                            {staffLoading ? 'Assigning...' : 'Assign Staff'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
