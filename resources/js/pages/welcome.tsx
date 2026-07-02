import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Activity, ArrowRight, BarChart3, Car, Clock, LayoutDashboard, MapPin, Shield, TrendingUp, Users } from 'lucide-react';
import type { ElementType } from 'react';
import { useEffect, useRef, useState } from 'react';

interface PageProps {
    name?: string;
    auth: {
        user: {
            name: string;
            email: string;
        } | null;
    };
    landing: {
        stats: {
            active_bookings: number;
            today_revenue: number;
            registered_accounts: number;
            pickup_locations: number;
            vehicles: number;
            tours: number;
        };
        recent_bookings: RecentBooking[];
    };
}

interface RecentBooking {
    type: string;
    title: string;
    detail: string | null;
    status: string;
    created_at: string | null;
}

const dashboardHref = '/admin/dashboard';
const loginHref = '/login';

const formatStatus = (status: string) =>
    status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const formatTime = (value: string | null) => {
    if (!value) return 'Recently';

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
};

const Counter = ({ value, prefix = '', suffix = '' }: { value: number; prefix?: string; suffix?: string }) => {
    const [display, setDisplay] = useState(0);
    const prev = useRef(0);

    useEffect(() => {
        const start = prev.current;
        const diff = value - start;

        if (diff === 0) return;

        const steps = 20;
        let index = 0;
        const id = window.setInterval(() => {
            index += 1;
            setDisplay(Math.round(start + (diff * index) / steps));

            if (index >= steps) {
                window.clearInterval(id);
                prev.current = value;
            }
        }, 16);

        return () => window.clearInterval(id);
    }, [value]);

    return (
        <span>
            {prefix}
            {display.toLocaleString()}
            {suffix}
        </span>
    );
};

const ActionCard = ({ icon: Icon, label, sub, delay, href }: { icon: ElementType; label: string; sub: string; delay: number; href: string }) => (
    <motion.a
        href={href}
        initial={{ opacity: 0, y: 24 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.55, delay, ease: [0.22, 1, 0.36, 1] }}
        whileHover={{ y: -4, transition: { duration: 0.2 } }}
        className="group relative flex min-h-[132px] cursor-pointer flex-col justify-between overflow-hidden rounded-lg border border-white/[0.07] bg-white/[0.03] p-5 backdrop-blur-sm"
    >
        <div className="pointer-events-none absolute inset-0 rounded-lg bg-gradient-to-br from-amber-500/10 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
        <div className="flex items-center justify-between">
            <div className="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg border border-amber-500/20 bg-amber-500/10 text-amber-400">
                <Icon size={20} />
            </div>
            <ArrowRight size={16} className="text-white/20 transition-all duration-200 group-hover:translate-x-1 group-hover:text-amber-400" />
        </div>
        <div>
            <p className="text-sm font-semibold tracking-wide text-white/90">{label}</p>
            <p className="mt-0.5 text-xs text-white/40">{sub}</p>
        </div>
    </motion.a>
);

const StatPill = ({
    icon: Icon,
    label,
    value,
    prefix,
    suffix,
    delay,
}: {
    icon: ElementType;
    label: string;
    value: number;
    prefix?: string;
    suffix?: string;
    delay: number;
}) => (
    <motion.div
        initial={{ opacity: 0, scale: 0.92 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.5, delay, ease: [0.22, 1, 0.36, 1] }}
        className="flex min-h-[82px] items-center gap-3 rounded-lg border border-white/[0.07] bg-white/[0.03] px-5 py-4 backdrop-blur-sm"
    >
        <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-amber-500/15 bg-amber-500/10 text-amber-400">
            <Icon size={18} />
        </div>
        <div className="min-w-0">
            <p className="truncate text-xs text-white/40">{label}</p>
            <p className="mt-0.5 text-lg leading-none font-bold text-white tabular-nums">
                <Counter value={value} prefix={prefix} suffix={suffix} />
            </p>
        </div>
    </motion.div>
);

const PulseDot = () => (
    <span className="relative inline-flex h-2 w-2">
        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60" />
        <span className="relative inline-flex h-2 w-2 rounded-full bg-amber-400" />
    </span>
);

const timeGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
};

const WelcomePage = ({ auth, landing, name = 'SkySlope' }: PageProps) => {
    const user = auth.user;
    const isAuthenticated = Boolean(user);
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    const stats = landing.stats;
    const recentBookings = landing.recent_bookings;

    const primaryAction = isAuthenticated
        ? { href: dashboardHref, label: 'Open Dashboard', icon: LayoutDashboard }
        : { href: loginHref, label: 'Admin Login', icon: Shield };
    const PrimaryActionIcon = primaryAction.icon;

    const quickActions = isAuthenticated
        ? [
              { icon: LayoutDashboard, label: 'Dashboard', sub: 'Overview and KPIs', href: dashboardHref },
              { icon: Car, label: 'Fleet', sub: `${stats.vehicles} active vehicles`, href: '/admin/vehicles' },
              { icon: Users, label: 'Customers', sub: `${stats.registered_accounts} total accounts`, href: '/admin/customers' },
              { icon: MapPin, label: 'Places', sub: `${stats.pickup_locations} saved places`, href: '/admin/places' },
              { icon: BarChart3, label: 'Bookings', sub: 'Tours, rides and rentals', href: '/admin/tour-bookings' },
              { icon: Shield, label: 'Settings', sub: 'System controls', href: '/admin/settings' },
          ]
        : [
              { icon: Shield, label: 'Admin Access', sub: 'Protected operations', href: loginHref },
              { icon: LayoutDashboard, label: 'Live Dashboard', sub: `${stats.active_bookings} active bookings`, href: loginHref },
              { icon: Car, label: 'Fleet Desk', sub: `${stats.vehicles} vehicles tracked`, href: loginHref },
              { icon: Users, label: 'Accounts', sub: `${stats.registered_accounts} records`, href: loginHref },
              { icon: MapPin, label: 'Locations', sub: `${stats.pickup_locations} places`, href: loginHref },
              { icon: BarChart3, label: 'Revenue', sub: 'Daily paid totals', href: loginHref },
          ];

    return (
        <div
            className="relative min-h-screen overflow-x-hidden bg-[#080808] text-white"
            style={{ fontFamily: "'DM Sans', 'Helvetica Neue', sans-serif" }}
        >
            <Head title={`${name} Operations`} />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=Orbitron:wght@600;800&display=swap');

                .font-display { font-family: 'Orbitron', sans-serif; }

                @keyframes grid-shift {
                    0% { background-position: 0 0; }
                    100% { background-position: 40px 40px; }
                }

                .bg-grid {
                    background-image:
                        linear-gradient(rgba(251,191,36,0.04) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(251,191,36,0.04) 1px, transparent 1px);
                    background-size: 40px 40px;
                    animation: grid-shift 8s linear infinite;
                }
            `}</style>

            <div className="bg-grid pointer-events-none absolute inset-0" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-10%,rgba(251,191,36,0.08),transparent)]" />
            <div className="absolute top-0 left-1/2 h-px w-3/4 -translate-x-1/2 bg-gradient-to-r from-transparent via-amber-500/40 to-transparent" />

            <motion.header
                initial={{ opacity: 0, y: -16 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
                className="relative z-10 flex items-center justify-between border-b border-white/[0.06] px-6 py-5 backdrop-blur-sm md:px-10"
            >
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 shadow-[0_0_20px_rgba(251,191,36,0.4)]">
                        <Car size={18} className="text-black" strokeWidth={2.5} />
                    </div>
                    <span className="font-display text-sm tracking-widest text-white/80 uppercase">{name}</span>
                </div>

                <div className="flex items-center gap-3">
                    <div className="flex items-center gap-2 rounded-full border border-white/[0.08] bg-white/[0.04] px-3 py-1.5">
                        <PulseDot />
                        <span className="text-xs text-white/50">Live Data</span>
                    </div>
                    {user ? (
                        <div className="hidden items-center gap-2.5 rounded-full border border-white/[0.08] bg-white/[0.04] py-1 pr-3 pl-1 md:flex">
                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-black">
                                {user.name.charAt(0)}
                            </div>
                            <span className="text-xs text-white/60">{user.name}</span>
                        </div>
                    ) : (
                        <a
                            href={loginHref}
                            className="hidden items-center gap-2 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1.5 text-xs font-medium text-amber-300 transition-all duration-200 hover:bg-amber-500/15 hover:text-amber-200 md:inline-flex"
                        >
                            <Shield size={13} strokeWidth={1.8} />
                            Admin Login
                        </a>
                    )}
                </div>
            </motion.header>

            <main className="relative z-10 mx-auto max-w-6xl px-6 py-12 md:px-10 md:py-16">
                <div className="mb-14 grid grid-cols-1 items-center gap-10 lg:grid-cols-[1fr_0.9fr]">
                    <div>
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ duration: 0.5 }}
                            className="mb-3 flex items-center gap-2"
                        >
                            <Clock size={13} className="text-amber-400/70" />
                            <span className="text-xs tracking-widest text-white/40 uppercase">{dateStr}</span>
                        </motion.div>

                        <motion.h1
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.7, delay: 0.1, ease: [0.22, 1, 0.36, 1] }}
                            className="font-display text-4xl leading-[1.1] tracking-normal md:text-5xl lg:text-[3.4rem]"
                        >
                            {timeGreeting()},
                            <br />
                            <span className="text-amber-400">{user ? user.name.split(' ')[0] : 'Operations'}</span>
                        </motion.h1>

                        <motion.p
                            initial={{ opacity: 0, y: 16 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.6, delay: 0.22, ease: [0.22, 1, 0.36, 1] }}
                            className="mt-4 max-w-md text-base leading-relaxed text-white/50"
                        >
                            {user
                                ? `You have ${stats.active_bookings} active bookings across rides, rentals and tours.`
                                : `${name} is tracking ${stats.registered_accounts} accounts, ${stats.vehicles} active vehicles and ${stats.pickup_locations} operating places.`}
                        </motion.p>

                        <motion.div
                            initial={{ opacity: 0, y: 12 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.55, delay: 0.34, ease: [0.22, 1, 0.36, 1] }}
                            className="mt-8 flex flex-wrap items-center gap-3"
                        >
                            <a
                                href={primaryAction.href}
                                className="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-black shadow-[0_0_30px_rgba(251,191,36,0.3)] transition-all duration-200 hover:bg-amber-400 hover:shadow-[0_0_40px_rgba(251,191,36,0.5)]"
                            >
                                <PrimaryActionIcon size={16} strokeWidth={2} />
                                {primaryAction.label}
                            </a>
                            <a
                                href={isAuthenticated ? '/admin/car-rentals' : loginHref}
                                className="inline-flex items-center gap-2 rounded-lg border border-white/[0.12] bg-white/[0.04] px-5 py-3 text-sm font-medium text-white/70 transition-all duration-200 hover:bg-white/[0.07] hover:text-white"
                            >
                                {isAuthenticated ? 'View Rentals' : 'View Admin Areas'}
                                <ArrowRight size={14} />
                            </a>
                        </motion.div>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, x: 30 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.9, delay: 0.2, ease: [0.22, 1, 0.36, 1] }}
                        className="rounded-lg border border-white/[0.07] bg-white/[0.03] p-5 backdrop-blur-sm"
                    >
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs tracking-widest text-white/35 uppercase">Recent Operations</p>
                                <p className="mt-1 text-sm text-white/70">Latest bookings from the database</p>
                            </div>
                            <Activity size={18} className="text-amber-400" />
                        </div>
                        <div className="space-y-3">
                            {recentBookings.length > 0 ? (
                                recentBookings.map((booking, index) => (
                                    <motion.div
                                        key={`${booking.type}-${booking.title}`}
                                        initial={{ opacity: 0, y: 10 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        transition={{ delay: 0.42 + index * 0.08 }}
                                        className="rounded-lg border border-white/[0.06] bg-black/20 p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-white">{booking.title}</p>
                                                <p className="mt-1 truncate text-xs text-white/45">
                                                    {booking.type}
                                                    {booking.detail ? ` - ${booking.detail}` : ''}
                                                </p>
                                            </div>
                                            <span className="flex-shrink-0 rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[11px] text-amber-300">
                                                {formatStatus(booking.status)}
                                            </span>
                                        </div>
                                        <p className="mt-3 text-[11px] text-white/30">{formatTime(booking.created_at)}</p>
                                    </motion.div>
                                ))
                            ) : (
                                <div className="rounded-lg border border-dashed border-white/[0.1] p-5 text-sm text-white/40">
                                    No bookings have been created yet.
                                </div>
                            )}
                        </div>
                    </motion.div>
                </div>

                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.4, delay: 0.4 }}
                    className="mb-5 flex items-center gap-2"
                >
                    <Activity size={13} className="text-amber-400" />
                    <span className="text-xs tracking-widest text-white/40 uppercase">Live Overview</span>
                    <PulseDot />
                </motion.div>

                <div className="mb-10 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <StatPill icon={Car} label="Active Bookings" value={stats.active_bookings} delay={0.45} />
                    <StatPill icon={TrendingUp} label="Today's Paid Revenue" value={stats.today_revenue} prefix="$" delay={0.52} />
                    <StatPill icon={Users} label="Registered Accounts" value={stats.registered_accounts} delay={0.59} />
                    <StatPill icon={MapPin} label="Pickup Locations" value={stats.pickup_locations} delay={0.66} />
                </div>

                <motion.p
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 0.7 }}
                    className="mb-4 text-xs tracking-widest text-white/30 uppercase"
                >
                    Quick Actions
                </motion.p>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    {quickActions.map(({ icon, label, sub, href }, index) => (
                        <ActionCard key={label} icon={icon} label={label} sub={sub} href={href} delay={0.74 + index * 0.07} />
                    ))}
                </div>
            </main>

            <motion.footer
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ delay: 1.1, duration: 0.5 }}
                className="relative z-10 mt-8 flex items-center justify-between border-t border-white/[0.05] px-6 py-4 md:px-10"
            >
                <span className="text-xs text-white/20">
                    {name} &copy; {now.getFullYear()} - Operations Management
                </span>
                {user ? (
                    <span className="hidden text-xs text-white/20 md:block">
                        Signed in as <span className="text-white/40">{user.email}</span>
                    </span>
                ) : (
                    <span className="hidden text-xs text-white/20 md:block">
                        Guest view -{' '}
                        <a href={loginHref} className="text-amber-400/70 hover:text-amber-300">
                            admin login required
                        </a>
                    </span>
                )}
            </motion.footer>
        </div>
    );
};

export default WelcomePage;
