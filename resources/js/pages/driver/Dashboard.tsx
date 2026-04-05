import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge, Button, Modal, Drawer } from '@mantine/core';
import {
  Car,
  MapPin,
  Clock,
  DollarSign,
  CheckCircle,
  XCircle,
  Navigation,
  Phone,
  MessageCircle,
  Wallet,
  TrendingUp,
  Calendar,
  User,
  Menu,
  Bell,
  Settings,
  LogOut,
  ChevronRight,
  Loader2,
  Play,
  Square,
  Users
} from 'lucide-react';
import { useDriverRidesChannel, trackingApi } from '@/lib/useWebSocket';
import axios from '@/lib/axios';

interface RideBooking {
  id: number;
  booking_number: string;
  status: string;
  pickup_location: string;
  pickup_lat: number;
  pickup_lng: number;
  dropoff_location: string;
  dropoff_lat: number;
  dropoff_lng: number;
  scheduled_at: string;
  total_fare: number;
  distance_km: number;
  customer_name: string;
  customer_phone: string;
  service_type: string;
  payment_status?: 'pending' | 'paid' | 'failed' | 'refunded';
  payment_method?: 'cash' | 'card' | 'wallet' | 'upi' | 'bank_transfer';
  vehicle_number?: string;
  start_pin_verified_at?: string | null;
}

interface DashboardProps {
  title: string;
  user: {
    id: number;
    name: string;
    email: string;
    phone: string;
  };
  my_tours: any[];
  upcoming_tours: any[];
  wallet?: {
    balance: number;
    currency: string;
  };
  stats?: {
    today_earnings: number;
    today_rides: number;
    total_rides: number;
    rating: number;
  };
}

const DriverDashboard: React.FC<DashboardProps> = ({
  title,
  user,
  my_tours,
  upcoming_tours,
  wallet,
  stats
}) => {
  const [isAvailable, setIsAvailable] = useState(true);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [activeRide, setActiveRide] = useState<RideBooking | null>(null);
  const [pendingRides, setPendingRides] = useState<RideBooking[]>([]);
  const [loading, setLoading] = useState(true);
  const [locationTracking, setLocationTracking] = useState(true);
  const [menuOpen, setMenuOpen] = useState(false);
  const [selectedRide, setSelectedRide] = useState<RideBooking | null>(null);
  const [notification, setNotification] = useState<{ type: string; message: string } | null>(null);
  const [paymentMode, setPaymentMode] = useState<'cash' | 'card' | 'wallet' | 'upi' | 'bank_transfer'>('cash');

  // Subscribe to driver rides channel
  const { latestStatus } = useDriverRidesChannel(user.id, (status) => {
    setNotification({ type: 'info', message: `Ride ${status.booking_number}: ${status.status}` });
    fetchActiveRide();
  });

  // Fetch active ride
  const fetchActiveRide = useCallback(async () => {
    try {
      const response = await axios.get('/driver/active-ride');
      if (response.data.success) {
        setActiveRide(response.data.ride);
      }
    } catch (error) {
      console.error('Error fetching active ride:', error);
    }
  }, []);

  // Fetch pending ride requests
  const fetchPendingRides = useCallback(async () => {
    try {
      const response = await axios.get('/driver/pending-rides');
      if (response.data.success) {
        setPendingRides(response.data.rides);
      }
    } catch (error) {
      console.error('Error fetching pending rides:', error);
    }
  }, []);

  // Update driver location
  const updateLocation = useCallback(async () => {
    if (!currentLocation || !isAvailable) return;

    try {
      await trackingApi.updateDriverLocation(
        currentLocation.lat,
        currentLocation.lng,
        isAvailable
      );
    } catch (error) {
      console.error('Error updating location:', error);
    }
  }, [currentLocation, isAvailable]);

  // Start location tracking
  useEffect(() => {
    if (!locationTracking) return;

    if ('geolocation' in navigator) {
      const watchId = navigator.geolocation.watchPosition(
        (position) => {
          setCurrentLocation({
            lat: position.coords.latitude,
            lng: position.coords.longitude
          });
        },
        (error) => console.error('Geolocation error:', error),
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 }
      );

      return () => navigator.geolocation.clearWatch(watchId);
    }
  }, [locationTracking]);

  // Update location periodically
  useEffect(() => {
    if (!locationTracking || !currentLocation) return;

    const interval = setInterval(updateLocation, 10000); // Every 10 seconds
    return () => clearInterval(interval);
  }, [locationTracking, currentLocation, updateLocation]);

  // Initial data fetch
  useEffect(() => {
    const init = async () => {
      await Promise.all([fetchActiveRide(), fetchPendingRides()]);
      setLoading(false);
    };
    init();
  }, [fetchActiveRide, fetchPendingRides]);

  useEffect(() => {
    if (activeRide?.payment_method) {
      setPaymentMode(activeRide.payment_method);
    }
  }, [activeRide]);

  // Toggle availability
  const toggleAvailability = async () => {
    setIsAvailable(!isAvailable);
    setLocationTracking(!isAvailable);
    if (currentLocation) {
      await trackingApi.updateDriverLocation(
        currentLocation.lat,
        currentLocation.lng,
        !isAvailable
      );
    }
  };

  // Accept ride
  const acceptRide = async (rideId: number) => {
    try {
      const response = await axios.post(`/driver/rides/${rideId}/accept`);
      if (response.data.success) {
        setActiveRide(response.data.ride);
        setPendingRides(pendingRides.filter(r => r.id !== rideId));
        setNotification({ type: 'success', message: 'Ride accepted!' });
      }
    } catch (error: any) {
      setNotification({ type: 'error', message: error.response?.data?.message || 'Failed to accept ride' });
    }
  };

  // Update ride status
  const updateRideStatus = async (rideId: number, status: string) => {
    try {
      let startPin: string | undefined;
      if (status === 'in_transit') {
        const enteredPin = window.prompt('Enter 4-digit customer PIN to start ride');
        if (!enteredPin) return;

        const normalized = enteredPin.replace(/\D/g, '');
        if (!/^\d{4}$/.test(normalized)) {
          setNotification({ type: 'error', message: 'Please enter a valid 4-digit PIN.' });
          return;
        }
        startPin = normalized;
      }

      await trackingApi.updateRideStatus(rideId, status, undefined, startPin);
      if (status === 'completed' || status === 'cancelled') {
        setActiveRide(null);
        fetchPendingRides();
      } else if (activeRide) {
        setActiveRide({ ...activeRide, status });
      }
      setNotification({ type: 'success', message: `Ride ${status}` });
    } catch (error: any) {
      setNotification({ type: 'error', message: error.response?.data?.message || 'Failed to update status' });
    }
  };

  const updatePaymentStatus = async (rideId: number, paymentStatus: string) => {
    try {
      const response = await axios.post(`/driver/rides/${rideId}/payment-status`, {
        payment_status: paymentStatus,
      });

      if (response.data.success && activeRide) {
        setActiveRide({ ...activeRide, payment_status: paymentStatus as RideBooking['payment_status'] });
      }

      setNotification({ type: 'success', message: 'Payment status updated' });
    } catch (error: any) {
      setNotification({ type: 'error', message: error.response?.data?.message || 'Failed to update payment status' });
    }
  };

  const collectPayment = async (rideId: number) => {
    try {
      const response = await axios.post(`/driver/rides/${rideId}/payment-status`, {
        payment_status: 'paid',
        payment_method: paymentMode,
      });

      if (response.data.success && activeRide) {
        setActiveRide({
          ...activeRide,
          payment_status: 'paid',
          payment_method: paymentMode,
        });
      }

      setNotification({ type: 'success', message: `Payment received via ${paymentMode.toUpperCase()}.` });
    } catch (error: any) {
      setNotification({ type: 'error', message: error.response?.data?.message || 'Failed to update payment status' });
    }
  };

  // Get status button
  const getNextStatusButton = (ride: RideBooking) => {
    const statusButtons: Record<string, { status: string; label: string; color: string }> = {
      'driver_assigned': { status: 'driver_arriving', label: 'Start Journey', color: 'bg-blue-500' },
      'driver_arriving': { status: 'pickup', label: 'At Pickup', color: 'bg-orange-500' },
      'pickup': { status: 'in_transit', label: 'Start Ride', color: 'bg-green-500' },
      'in_transit': { status: 'completed', label: 'Complete Ride', color: 'bg-green-600' },
    };

    const button = statusButtons[ride.status];
    if (!button) return null;

    return (
      <button
        onClick={() => updateRideStatus(ride.id, button.status)}
        className={`w-full ${button.color} text-white py-3 rounded-lg font-semibold`}
      >
        {button.label}
      </button>
    );
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-100">
        <Loader2 className="w-8 h-8 animate-spin text-orange-500" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-100 pb-20">
      <Head title={title} />

      {/* Notification */}
      {notification && (
        <div className={`fixed top-0 left-0 right-0 z-50 p-4 ${
          notification.type === 'success' ? 'bg-green-500' : 
          notification.type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        } text-white`}>
          <div className="flex items-center justify-between max-w-lg mx-auto">
            <span>{notification.message}</span>
            <button onClick={() => setNotification(null)}>
              <XCircle className="w-5 h-5" />
            </button>
          </div>
        </div>
      )}

      {/* Header */}
      <div className="bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-40">
        <div className="px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <button onClick={() => setMenuOpen(true)} className="p-2">
                <Menu className="w-6 h-6" />
              </button>
              <div>
                <h1 className="text-xl font-bold">Driver Dashboard</h1>
                <p className="text-sm text-orange-100">Welcome, {user.name}</p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <button className="p-2 relative">
                <Bell className="w-6 h-6" />
                {pendingRides.length > 0 && (
                  <span className="absolute top-0 right-0 w-5 h-5 bg-red-500 rounded-full text-xs flex items-center justify-center">
                    {pendingRides.length}
                  </span>
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Availability Toggle */}
        <div className="px-4 pb-4">
          <div className="bg-white/10 rounded-lg p-3 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className={`w-3 h-3 rounded-full ${isAvailable ? 'bg-green-400' : 'bg-red-400'}`} />
              <span className="font-medium">{isAvailable ? 'Online' : 'Offline'}</span>
            </div>
            <button
              onClick={toggleAvailability}
              className={`px-4 py-2 rounded-full font-medium ${
                isAvailable 
                  ? 'bg-red-500 text-white' 
                  : 'bg-green-500 text-white'
              }`}
            >
              {isAvailable ? 'Go Offline' : 'Go Online'}
            </button>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="px-4 -mt-2">
        <div className="grid grid-cols-2 gap-3">
          <div className="bg-white rounded-xl p-4 shadow-sm">
            <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
              <DollarSign className="w-4 h-4" />
              Today's Earnings
            </div>
            <div className="text-2xl font-bold text-gray-900">
              ₹{stats?.today_earnings?.toLocaleString() || '0'}
            </div>
          </div>
          <div className="bg-white rounded-xl p-4 shadow-sm">
            <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
              <Car className="w-4 h-4" />
              Today's Rides
            </div>
            <div className="text-2xl font-bold text-gray-900">
              {stats?.today_rides || 0}
            </div>
          </div>
          <div className="bg-white rounded-xl p-4 shadow-sm">
            <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
              <Wallet className="w-4 h-4" />
              Wallet Balance
            </div>
            <div className="text-2xl font-bold text-green-600">
              ₹{wallet?.balance?.toLocaleString() || '0'}
            </div>
          </div>
          <div className="bg-white rounded-xl p-4 shadow-sm">
            <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
              <TrendingUp className="w-4 h-4" />
              Rating
            </div>
            <div className="text-2xl font-bold text-yellow-600">
              ⭐ {stats?.rating?.toFixed(1) || '5.0'}
            </div>
          </div>
        </div>
      </div>

      {/* Active Ride */}
      {activeRide && (
        <div className="px-4 mt-4">
          <div className="bg-white rounded-xl shadow-sm overflow-hidden">
            <div className="bg-green-500 text-white px-4 py-2 flex items-center justify-between">
              <span className="font-semibold">Active Ride</span>
              <Badge variant="filled" className="bg-white/20">
                {activeRide.booking_number}
              </Badge>
            </div>
            <div className="p-4">
              {/* Customer Info */}
              <div className="flex items-center justify-between mb-4 pb-4 border-b">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <User className="w-6 h-6 text-gray-400" />
                  </div>
                  <div>
                    <p className="font-semibold">{activeRide.customer_name}</p>
                    <p className="text-sm text-gray-500">{activeRide.service_type}</p>
                  </div>
                </div>
                <div className="flex gap-2">
                  <a 
                    href={`tel:${activeRide.customer_phone}`}
                    className="p-2 bg-green-100 rounded-full"
                  >
                    <Phone className="w-5 h-5 text-green-600" />
                  </a>
                </div>
              </div>

              {/* Locations */}
              <div className="space-y-3 mb-4">
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <div className="w-2 h-2 bg-green-500 rounded-full" />
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">PICKUP</p>
                    <p className="font-medium">{activeRide.pickup_location}</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <div className="w-2 h-2 bg-red-500 rounded-full" />
                  </div>
                  <div>
                    <p className="text-xs text-gray-500">DROP-OFF</p>
                    <p className="font-medium">{activeRide.dropoff_location}</p>
                  </div>
                </div>
              </div>

              {/* Fare */}
              <div className="bg-orange-50 rounded-lg p-3 mb-4">
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Fare</span>
                  <span className="text-2xl font-bold text-orange-600">
                    ₹{activeRide.total_fare}
                  </span>
                </div>
              </div>

              <div className="mb-4 rounded-lg border border-gray-200 p-3">
                <div className="mb-2 flex items-center justify-between">
                  <span className="text-sm font-medium text-gray-700">Payment Status</span>
                </div>
                {activeRide.payment_status !== 'paid' ? (
                  <div className="space-y-2">
                    <select
                      value={paymentMode}
                      onChange={(event) => setPaymentMode(event.target.value as typeof paymentMode)}
                      className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:outline-none"
                    >
                      <option value="cash">Cash</option>
                      <option value="card">Card</option>
                      <option value="upi">UPI</option>
                      <option value="wallet">Wallet</option>
                      <option value="bank_transfer">Bank Transfer</option>
                    </select>
                    <button
                      type="button"
                      onClick={() => collectPayment(activeRide.id)}
                      className="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                      Accept Payment & Mark Paid
                    </button>
                    <button
                      type="button"
                      onClick={() => updatePaymentStatus(activeRide.id, 'failed')}
                      className="w-full rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                    >
                      Mark as Failed
                    </button>
                  </div>
                ) : (
                  <div className="rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                    Paid via {(activeRide.payment_method || paymentMode).replace('_', ' ').toUpperCase()}
                  </div>
                )}
              </div>

              <div className="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <a
                  href={`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(
                    activeRide.pickup_lat && activeRide.pickup_lng
                      ? `${activeRide.pickup_lat},${activeRide.pickup_lng}`
                      : activeRide.pickup_location
                  )}`}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                  Navigate to Pickup
                </a>
                <a
                  href={`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(
                    activeRide.dropoff_lat && activeRide.dropoff_lng
                      ? `${activeRide.dropoff_lat},${activeRide.dropoff_lng}`
                      : activeRide.dropoff_location
                  )}`}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                  Navigate to Drop-off
                </a>
              </div>

              {/* Status Button */}
              {getNextStatusButton(activeRide)}
            </div>
          </div>
        </div>
      )}

      {/* Pending Ride Requests */}
      {!activeRide && pendingRides.length > 0 && (
        <div className="px-4 mt-4">
          <h2 className="text-lg font-semibold text-gray-900 mb-3">
            Ride Requests ({pendingRides.length})
          </h2>
          <div className="space-y-3">
            {pendingRides.map((ride) => (
              <div key={ride.id} className="bg-white rounded-xl shadow-sm p-4">
                <div className="flex items-center justify-between mb-3">
                  <Badge variant="filled" className="bg-orange-100 text-orange-700">
                    {ride.service_type}
                  </Badge>
                  <span className="text-lg font-bold text-gray-900">
                    ₹{ride.total_fare}
                  </span>
                </div>

                <div className="space-y-2 mb-3">
                  <div className="flex items-center gap-2 text-sm">
                    <MapPin className="w-4 h-4 text-green-500" />
                    <span className="text-gray-600 truncate">{ride.pickup_location}</span>
                  </div>
                  <div className="flex items-center gap-2 text-sm">
                    <MapPin className="w-4 h-4 text-red-500" />
                    <span className="text-gray-600 truncate">{ride.dropoff_location}</span>
                  </div>
                </div>

                <div className="flex items-center justify-between text-sm text-gray-500 mb-3">
                  <span>{ride.distance_km?.toFixed(1)} km</span>
                  <span>{new Date(ride.scheduled_at).toLocaleTimeString()}</span>
                </div>

                <div className="flex gap-2">
                  <button
                    onClick={() => acceptRide(ride.id)}
                    className="flex-1 bg-green-500 text-white py-2 rounded-lg font-semibold"
                  >
                    Accept
                  </button>
                  <button
                    onClick={() => setPendingRides(pendingRides.filter(r => r.id !== ride.id))}
                    className="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold"
                  >
                    Decline
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* No Active Ride */}
      {!activeRide && pendingRides.length === 0 && (
        <div className="px-4 mt-4">
          <div className="bg-white rounded-xl shadow-sm p-8 text-center">
            <Car className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-gray-900 mb-2">No Active Rides</h3>
            <p className="text-gray-500">
              {isAvailable 
                ? 'You\'ll be notified when new ride requests come in.' 
                : 'Go online to start receiving ride requests.'}
            </p>
          </div>
        </div>
      )}

      {/* Bottom Navigation */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-40">
        <div className="flex items-center justify-around py-2">
          <Link href="/driver" className="flex flex-col items-center p-2 text-orange-500">
            <Car className="w-6 h-6" />
            <span className="text-xs mt-1">Rides</span>
          </Link>
          <Link href="/driver/wallet" className="flex flex-col items-center p-2 text-gray-500">
            <Wallet className="w-6 h-6" />
            <span className="text-xs mt-1">Wallet</span>
          </Link>
          <Link href="/driver/history" className="flex flex-col items-center p-2 text-gray-500">
            <Clock className="w-6 h-6" />
            <span className="text-xs mt-1">History</span>
          </Link>
          <Link href="/driver/settings" className="flex flex-col items-center p-2 text-gray-500">
            <Settings className="w-6 h-6" />
            <span className="text-xs mt-1">Settings</span>
          </Link>
        </div>
      </div>

      {/* Side Menu Drawer */}
      <Drawer
        opened={menuOpen}
        onClose={() => setMenuOpen(false)}
        title={<span className="font-bold text-lg">Menu</span>}
        padding="md"
        size="md"
      >
        <div className="space-y-2">
          <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg mb-4">
            <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <User className="w-6 h-6 text-orange-500" />
            </div>
            <div>
              <p className="font-semibold">{user.name}</p>
              <p className="text-sm text-gray-500">{user.phone}</p>
            </div>
          </div>

          <Link href="/driver/profile" className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
            <div className="flex items-center gap-3">
              <User className="w-5 h-5 text-gray-500" />
              <span>Profile</span>
            </div>
            <ChevronRight className="w-5 h-5 text-gray-400" />
          </Link>

          <Link href="/driver/wallet" className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
            <div className="flex items-center gap-3">
              <Wallet className="w-5 h-5 text-gray-500" />
              <span>Wallet</span>
            </div>
            <ChevronRight className="w-5 h-5 text-gray-400" />
          </Link>

          <Link href="/driver/history" className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
            <div className="flex items-center gap-3">
              <Clock className="w-5 h-5 text-gray-500" />
              <span>Ride History</span>
            </div>
            <ChevronRight className="w-5 h-5 text-gray-400" />
          </Link>

          <Link href="/driver/tours" className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
            <div className="flex items-center gap-3">
              <Calendar className="w-5 h-5 text-gray-500" />
              <span>My Tours</span>
            </div>
            <ChevronRight className="w-5 h-5 text-gray-400" />
          </Link>

          <div className="border-t my-4"></div>

          <Link href="/logout" method="post" className="flex items-center gap-3 p-3 text-red-600 hover:bg-red-50 rounded-lg">
            <LogOut className="w-5 h-5" />
            <span>Logout</span>
          </Link>
        </div>
      </Drawer>
    </div>
  );
};

export default DriverDashboard;
