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
    Search,
    MapPin,
    Star,
    Wifi,
    Tv,
    Coffee,
    Plus,
    CheckCircle2,
    AlertCircle,
    Navigation,
    Phone,
    Users,
    Clock
} from 'lucide-react';

interface Venue {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string;
    city: string;
    address: string;
    map_link?: string;
    capacity: number;
    facilities: string[];
    opening_hours?: Record<string, string>;
    contact_details?: {
        phone?: string;
        email?: string;
        telegram?: string;
    };
    pricing_details?: {
        hourly_rate?: number;
        day_pass?: number;
        currency?: string;
    };
    photos?: string[];
    is_verified: boolean;
    is_featured: boolean;
    average_rating: number;
    reviews_count: number;
    distance_km?: number;
}

export default function VenuesIndex() {
    const { auth } = usePage().props as any;
    const [venues, setVenues] = useState<Venue[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [city, setCity] = useState('');
    const [selectedFacility, setSelectedFacility] = useState('');
    const [locating, setLocating] = useState(false);
    const [coords, setCoords] = useState<{ lat: number; lng: number } | null>(null);

    // Create Modal state
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [createForm, setCreateForm] = useState({
        name: '',
        description: '',
        city: 'Addis Ababa',
        address: '',
        map_link: '',
        capacity: 50,
        facilities: ['fiber_internet', 'high_refresh_monitors'],
        hourly_rate: 60,
        phone: '',
        email: '',
    });
    const [createError, setCreateError] = useState<string | null>(null);
    const [createLoading, setCreateLoading] = useState(false);

    const availableFacilities = [
        { key: 'fiber_internet', label: 'Fiber Internet' },
        { key: 'high_refresh_monitors', label: '144Hz+ Monitors' },
        { key: 'rtx_gaming_pcs', label: 'RTX Gaming PCs' },
        { key: 'ps5_consoles', label: 'PS5 Consoles' },
        { key: 'streaming_studio', label: 'Broadcast Studio' },
        { key: 'spectator_area', label: 'Spectator Seating' },
        { key: 'cafeteria', label: 'Café & Snacks' },
        { key: 'backup_generator', label: 'Backup Generator' },
    ];

    const fetchVenues = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (city) params.append('city', city);
            if (selectedFacility) params.append('facilities', selectedFacility);
            if (coords) {
                params.append('latitude', coords.lat.toString());
                params.append('longitude', coords.lng.toString());
                params.append('radius_km', '50');
            }

            const res = await fetch(`/api/v1/venues?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                setVenues(data.data);
            }
        } catch (e) {
            console.error('Failed to load venues', e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchVenues();
    }, [search, city, selectedFacility, coords]);

    const handleNearMe = () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        setLocating(true);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                setCoords({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude
                });
                setLocating(false);
            },
            (err) => {
                console.error(err);
                setLocating(false);
                alert('Could not retrieve your location.');
            }
        );
    };

    const handleCreateVenue = async (e: React.FormEvent) => {
        e.preventDefault();
        setCreateLoading(true);
        setCreateError(null);

        try {
            const res = await fetch('/api/v1/venues', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: createForm.name,
                    description: createForm.description,
                    city: createForm.city,
                    address: createForm.address,
                    map_link: createForm.map_link,
                    capacity: createForm.capacity,
                    facilities: createForm.facilities,
                    pricing_details: {
                        hourly_rate: createForm.hourly_rate,
                        currency: 'ETB',
                    },
                    contact_details: {
                        phone: createForm.phone,
                        email: createForm.email,
                    },
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Failed to create venue');
            }

            setIsCreateOpen(false);
            fetchVenues();
        } catch (err: any) {
            setCreateError(err.message);
        } finally {
            setCreateLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Gaming Venues & LAN Arenas - Campus Esports Ethiopia" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <Building2 className="w-6 h-6" />
                            </span>
                            <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                Esports Venues & Arenas
                            </h1>
                        </div>
                        <p className="text-slate-400 text-sm">
                            Discover verified university gaming lounges, LAN cafes, and tournament arenas across Ethiopia.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            onClick={handleNearMe}
                            disabled={locating}
                            className="flex items-center gap-2"
                        >
                            <Navigation className={`w-4 h-4 text-cyan-400 ${locating ? 'animate-spin' : ''}`} />
                            {coords ? 'Near Me (Active)' : 'Find Near Me'}
                        </Button>

                        {auth.user && (
                            <Button
                                variant="primary"
                                onClick={() => setIsCreateOpen(true)}
                                className="flex items-center gap-2 shadow-lg shadow-emerald-950/40"
                            >
                                <Plus className="w-4 h-4" />
                                Register Venue
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 mb-8 backdrop-blur-sm">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div className="relative">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                            <Input
                                placeholder="Search by venue name or location..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 bg-slate-950/70 border-slate-800 text-sm"
                            />
                        </div>

                        <select
                            value={city}
                            onChange={(e) => setCity(e.target.value)}
                            className="bg-slate-950/70 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">All Cities</option>
                            <option value="Addis Ababa">Addis Ababa</option>
                            <option value="Hawassa">Hawassa</option>
                            <option value="Adama">Adama</option>
                            <option value="Bahir Dar">Bahir Dar</option>
                            <option value="Mekelle">Mekelle</option>
                            <option value="Jimma">Jimma</option>
                            <option value="Dire Dawa">Dire Dawa</option>
                        </select>

                        <select
                            value={selectedFacility}
                            onChange={(e) => setSelectedFacility(e.target.value)}
                            className="bg-slate-950/70 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">All Facilities</option>
                            {availableFacilities.map((f) => (
                                <option key={f.key} value={f.key}>
                                    {f.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Venues Grid */}
                {loading ? (
                    <div className="flex flex-col items-center justify-center py-20">
                        <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                        <p className="mt-4 text-slate-400 text-sm">Searching venues...</p>
                    </div>
                ) : venues.length === 0 ? (
                    <Card className="text-center py-16 bg-slate-900/40 border-dashed border-slate-800">
                        <Building2 className="w-12 h-12 text-slate-600 mx-auto mb-3" />
                        <h3 className="text-lg font-bold text-slate-300">No Venues Found</h3>
                        <p className="text-sm text-slate-500 max-w-sm mx-auto mt-1">
                            We couldn't find any gaming venues matching your search. Try adjusting filters or register your gaming space!
                        </p>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {venues.map((venue) => (
                            <Card
                                key={venue.id}
                                className="group overflow-hidden border-slate-800/80 bg-slate-900/60 hover:border-emerald-500/50 hover:shadow-xl hover:shadow-emerald-950/20 transition-all flex flex-col justify-between"
                            >
                                <div>
                                    {/* Cover Image / Placeholder */}
                                    <div className="relative h-48 bg-gradient-to-tr from-slate-950 via-slate-900 to-emerald-950/40 overflow-hidden">
                                        {venue.photos && venue.photos.length > 0 ? (
                                            <img
                                                src={venue.photos[0]}
                                                alt={venue.name}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-slate-700">
                                                <Building2 className="w-16 h-16 opacity-30" />
                                            </div>
                                        )}

                                        <div className="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                            {venue.is_verified && (
                                                <Badge variant="success" className="bg-emerald-950/90 text-emerald-400 border border-emerald-500/40 text-[11px] backdrop-blur-sm">
                                                    Verified Hub
                                                </Badge>
                                            )}
                                            {venue.is_featured && (
                                                <Badge variant="warning" className="bg-amber-950/90 text-amber-400 border border-amber-500/40 text-[11px] backdrop-blur-sm">
                                                    Featured Arena
                                                </Badge>
                                            )}
                                        </div>

                                        {venue.distance_km !== undefined && (
                                            <div className="absolute bottom-3 right-3 px-2 py-1 rounded-md bg-slate-950/90 text-cyan-300 text-xs font-semibold backdrop-blur-sm border border-cyan-500/30 flex items-center gap-1">
                                                <Navigation className="w-3 h-3" />
                                                {venue.distance_km.toFixed(1)} km away
                                            </div>
                                        )}
                                    </div>

                                    {/* Body */}
                                    <div className="p-5">
                                        <div className="flex items-start justify-between gap-2 mb-2">
                                            <h3 className="font-bold text-lg text-white group-hover:text-emerald-400 transition-colors line-clamp-1">
                                                {venue.name}
                                            </h3>
                                            <div className="flex items-center gap-1 text-amber-400 text-xs font-bold shrink-0">
                                                <Star className="w-3.5 h-3.5 fill-amber-400" />
                                                <span>{venue.average_rating > 0 ? venue.average_rating.toFixed(1) : 'New'}</span>
                                            </div>
                                        </div>

                                        <p className="text-xs text-slate-400 flex items-center gap-1.5 mb-3">
                                            <MapPin className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                            <span className="line-clamp-1">{venue.address}, {venue.city}</span>
                                        </p>

                                        {venue.description && (
                                            <p className="text-xs text-slate-400 line-clamp-2 mb-4 leading-relaxed">
                                                {venue.description}
                                            </p>
                                        )}

                                        {/* Facility Badges */}
                                        <div className="flex flex-wrap gap-1.5 mb-4">
                                            {(venue.facilities || []).slice(0, 3).map((f) => (
                                                <span
                                                    key={f}
                                                    className="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700/60"
                                                >
                                                    {f.replace(/_/g, ' ')}
                                                </span>
                                            ))}
                                            {(venue.facilities || []).length > 3 && (
                                                <span className="px-1.5 py-0.5 rounded text-[10px] text-slate-500 bg-slate-800/50">
                                                    +{venue.facilities.length - 3} more
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Card Footer */}
                                <div className="px-5 py-3 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                                    <div className="text-xs text-slate-400 flex items-center gap-2">
                                        <Users className="w-3.5 h-3.5 text-slate-500" />
                                        <span>Cap: <strong className="text-slate-200">{venue.capacity}</strong></span>
                                    </div>

                                    <Link href={`/venues/${venue.id}`}>
                                        <Button size="sm" variant="outline" className="text-xs">
                                            View Venue
                                        </Button>
                                    </Link>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Create Venue Modal */}
            <Modal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                title="Register New Gaming Venue"
            >
                <form onSubmit={handleCreateVenue} className="space-y-4">
                    {createError && (
                        <div className="p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0" />
                            <span>{createError}</span>
                        </div>
                    )}

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Venue / Arena Name</label>
                        <Input
                            required
                            placeholder="e.g. Nexus LAN & Esports Lounge"
                            value={createForm.name}
                            onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">City</label>
                            <Input
                                required
                                value={createForm.city}
                                onChange={(e) => setCreateForm({ ...createForm, city: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Capacity (Players/Guests)</label>
                            <Input
                                type="number"
                                min={5}
                                value={createForm.capacity}
                                onChange={(e) => setCreateForm({ ...createForm, capacity: parseInt(e.target.value) || 20 })}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Address / Landmark</label>
                        <Input
                            required
                            placeholder="e.g. Bole Medhanialem, Behind Edna Mall, 2nd Floor"
                            value={createForm.address}
                            onChange={(e) => setCreateForm({ ...createForm, address: e.target.value })}
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Google Maps Link</label>
                        <Input
                            placeholder="https://maps.google.com/?q=..."
                            value={createForm.map_link}
                            onChange={(e) => setCreateForm({ ...createForm, map_link: e.target.value })}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Contact Phone</label>
                            <Input
                                placeholder="+251 91 123 4567"
                                value={createForm.phone}
                                onChange={(e) => setCreateForm({ ...createForm, phone: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Hourly Rate (ETB)</label>
                            <Input
                                type="number"
                                value={createForm.hourly_rate}
                                onChange={(e) => setCreateForm({ ...createForm, hourly_rate: parseInt(e.target.value) || 50 })}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                        <textarea
                            rows={3}
                            placeholder="Describe PCs, consoles, tournament stages, internet speed..."
                            className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                            value={createForm.description}
                            onChange={(e) => setCreateForm({ ...createForm, description: e.target.value })}
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="ghost" onClick={() => setIsCreateOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" disabled={createLoading}>
                            {createLoading ? 'Registering...' : 'Register Venue'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
