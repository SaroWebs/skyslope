import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { Card, Badge, Table, Select, Button, Loader } from '@mantine/core';
import {
  DollarSign,
  TrendingUp,
  Calendar,
  Download,
  Filter,
  BarChart3,
  ArrowUpRight,
  Clock,
} from 'lucide-react';
import axios from '@/lib/axios';

interface CommissionStats {
  total_commission: number;
  today_commission: number;
  month_commission: number;
  pending_commission: number;
  commission_count: number;
  average_commission: number;
  by_service_type: {
    type: string;
    count: number;
    total: number;
    percentage: number;
  }[];
  by_driver: {
    driver_id: number;
    driver_name: string;
    total_rides: number;
    total_commission: number;
    total_earnings: number;
  }[];
  daily_trend: {
    date: string;
    commission: number;
    rides: number;
  }[];
}

interface CommissionTransaction {
  id: number;
  booking_number: string;
  driver_name: string;
  service_type: string;
  total_fare: number;
  commission_amount: number;
  driver_share: number;
  status: string;
  created_at: string;
}

const CommissionAnalytics: React.FC = () => {
  const [stats, setStats] = useState<CommissionStats | null>(null);
  const [transactions, setTransactions] = useState<CommissionTransaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dateRange, setDateRange] = useState<{ start: string; end: string }>({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    end: new Date().toISOString().split('T')[0]
  });
  const [serviceType, setServiceType] = useState<string>('all');

  useEffect(() => {
    fetchCommissionData();
  }, [dateRange, serviceType]);

  const fetchCommissionData = async () => {
    setLoading(true);
    setError(null);
    try {
      const [statsRes, transactionsRes] = await Promise.all([
        axios.get('/admin/api/commission-stats', {
          baseURL: '',
          params: {
            start_date: dateRange.start,
            end_date: dateRange.end,
            service_type: serviceType
          }
        }),
        axios.get('/admin/api/commission-transactions', {
          baseURL: '',
          params: {
            start_date: dateRange.start,
            end_date: dateRange.end,
            service_type: serviceType
          }
        })
      ]);

      if (statsRes.data.success) {
        setStats(statsRes.data.data);
      }
      if (transactionsRes.data.success) {
        setTransactions(transactionsRes.data.data);
      }
    } catch (error) {
      console.error('Error fetching commission data:', error);
      setStats(null);
      setTransactions([]);
      setError('Unable to load commission analytics right now.');
    } finally {
      setLoading(false);
    }
  };

  const exportToCsv = () => {
    if (!transactions.length) return;

    const headers = ['Booking', 'Driver', 'Service Type', 'Total Fare', 'Commission', 'Driver Share', 'Status', 'Date'];
    const rows = transactions.map(t => [
      t.booking_number,
      t.driver_name,
      t.service_type,
      t.total_fare,
      t.commission_amount,
      t.driver_share,
      t.status,
      new Date(t.created_at).toLocaleDateString()
    ]);

    const csv = [headers, ...rows].map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `commission-report-${dateRange.start}-to-${dateRange.end}.csv`;
    a.click();
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const getServiceTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
      'point_to_point': 'Point to Point',
      'hourly_rental': 'Hourly Rental',
      'round_trip': 'Round Trip'
    };
    return labels[type] || type;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader size="lg" />
      </div>
    );
  }

  return (
    <>
      <Head title="Commission Analytics" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Commission Analytics</h1>
            <p className="text-gray-500">Track and analyze platform commission earnings</p>
          </div>
          <div className="flex items-center gap-3">
            <Button variant="outline" onClick={exportToCsv}>
              <Download className="w-4 h-4 mr-2" />
              Export CSV
            </Button>
          </div>
        </div>

        {/* Filters */}
        <Card className="p-4">
          <div className="flex flex-wrap items-center gap-4">
            <div className="flex items-center gap-2">
              <Calendar className="w-5 h-5 text-gray-400" />
              <input
                type="date"
                value={dateRange.start}
                onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
                className="border rounded-lg px-3 py-2"
              />
              <span className="text-gray-500">to</span>
              <input
                type="date"
                value={dateRange.end}
                onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
                className="border rounded-lg px-3 py-2"
              />
            </div>
            <Select
              value={serviceType}
              onChange={(value) => setServiceType(value || 'all')}
              data={[
                { value: 'all', label: 'All Service Types' },
                { value: 'point_to_point', label: 'Point to Point' },
                { value: 'hourly_rental', label: 'Hourly Rental' },
                { value: 'round_trip', label: 'Round Trip' }
              ]}
              className="w-48"
            />
            <Button onClick={fetchCommissionData}>
              <Filter className="w-4 h-4 mr-2" />
              Apply
            </Button>
          </div>
        </Card>

        {error && (
          <Card className="border border-red-200 bg-red-50 p-4 text-red-700">
            {error}
          </Card>
        )}

        {/* Stats Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">Total Commission</p>
                <p className="text-2xl font-bold text-gray-900">
                  {formatCurrency(stats?.total_commission || 0)}
                </p>
              </div>
              <div className="p-3 bg-green-100 rounded-full">
                <DollarSign className="w-6 h-6 text-green-600" />
              </div>
            </div>
            <div className="mt-2 flex items-center text-sm text-green-600">
              <ArrowUpRight className="w-4 h-4 mr-1" />
              <span>+12.5% from last period</span>
            </div>
          </Card>

          <Card className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">Today's Commission</p>
                <p className="text-2xl font-bold text-gray-900">
                  {formatCurrency(stats?.today_commission || 0)}
                </p>
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <TrendingUp className="w-6 h-6 text-blue-600" />
              </div>
            </div>
            <div className="mt-2 flex items-center text-sm text-gray-500">
              <Clock className="w-4 h-4 mr-1" />
              <span>{stats?.commission_count || 0} rides today</span>
            </div>
          </Card>

          <Card className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">This Month</p>
                <p className="text-2xl font-bold text-gray-900">
                  {formatCurrency(stats?.month_commission || 0)}
                </p>
              </div>
              <div className="p-3 bg-purple-100 rounded-full">
                <Calendar className="w-6 h-6 text-purple-600" />
              </div>
            </div>
            <div className="mt-2 flex items-center text-sm text-green-600">
              <ArrowUpRight className="w-4 h-4 mr-1" />
              <span>+8.3% from last month</span>
            </div>
          </Card>

          <Card className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">Avg Commission</p>
                <p className="text-2xl font-bold text-gray-900">
                  {formatCurrency(stats?.average_commission || 0)}
                </p>
              </div>
              <div className="p-3 bg-orange-100 rounded-full">
                <BarChart3 className="w-6 h-6 text-orange-600" />
              </div>
            </div>
            <div className="mt-2 flex items-center text-sm text-gray-500">
              <span>Per ride average</span>
            </div>
          </Card>
        </div>

        {/* Charts Row */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Service Type Breakdown */}
          <Card className="p-4">
            <h3 className="text-lg font-semibold mb-4">Commission by Service Type</h3>
            <div className="space-y-4">
              {stats?.by_service_type.map((item) => (
                <div key={item.type}>
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-sm font-medium">{getServiceTypeLabel(item.type)}</span>
                    <span className="text-sm text-gray-500">{formatCurrency(item.total)}</span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-orange-500 h-2 rounded-full"
                      style={{ width: `${item.percentage}%` }}
                    />
                  </div>
                  <div className="flex items-center justify-between text-xs text-gray-500 mt-1">
                    <span>{item.count} rides</span>
                    <span>{item.percentage}%</span>
                  </div>
                </div>
              ))}
            </div>
          </Card>

          {/* Daily Trend */}
          <Card className="p-4">
            <h3 className="text-lg font-semibold mb-4">Daily Commission Trend</h3>
            <div className="space-y-2">
              {stats?.daily_trend.map((day) => (
                <div key={day.date} className="flex items-center gap-4">
                  <div className="w-20 text-sm text-gray-500">
                    {new Date(day.date).toLocaleDateString('en-IN', { weekday: 'short' })}
                  </div>
                  <div className="flex-1">
                    <div className="w-full bg-gray-200 rounded-full h-4 relative">
                      <div
                        className="bg-gradient-to-r from-orange-400 to-orange-600 h-4 rounded-full"
                        style={{ width: `${(day.commission / 10000) * 100}%` }}
                      />
                    </div>
                  </div>
                  <div className="w-24 text-right">
                    <span className="text-sm font-medium">{formatCurrency(day.commission)}</span>
                    <span className="text-xs text-gray-500 ml-1">({day.rides})</span>
                  </div>
                </div>
              ))}
            </div>
          </Card>
        </div>

        {/* Top Drivers */}
        <Card className="p-4">
          <h3 className="text-lg font-semibold mb-4">Top Drivers by Commission</h3>
          <div className="overflow-x-auto">
            <Table>
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>Driver</Table.Th>
                  <Table.Th>Total Rides</Table.Th>
                  <Table.Th>Total Commission</Table.Th>
                  <Table.Th>Driver Earnings</Table.Th>
                  <Table.Th>Performance</Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                {stats?.by_driver.map((driver, index) => (
                  <Table.Tr key={driver.driver_id}>
                    <Table.Td>
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 font-semibold">
                          {index + 1}
                        </div>
                        <span className="font-medium">{driver.driver_name}</span>
                      </div>
                    </Table.Td>
                    <Table.Td>{driver.total_rides}</Table.Td>
                    <Table.Td className="font-semibold text-green-600">
                      {formatCurrency(driver.total_commission)}
                    </Table.Td>
                    <Table.Td>{formatCurrency(driver.total_earnings)}</Table.Td>
                    <Table.Td>
                      <Badge color="green">
                        {((driver.total_commission / driver.total_earnings) * 100).toFixed(1)}% rate
                      </Badge>
                    </Table.Td>
                  </Table.Tr>
                ))}
              </Table.Tbody>
            </Table>
          </div>
        </Card>

        {/* Recent Transactions */}
        <Card className="p-4">
          <h3 className="text-lg font-semibold mb-4">Recent Commission Transactions</h3>
          <div className="overflow-x-auto">
            <Table>
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>Booking</Table.Th>
                  <Table.Th>Driver</Table.Th>
                  <Table.Th>Service Type</Table.Th>
                  <Table.Th>Total Fare</Table.Th>
                  <Table.Th>Commission</Table.Th>
                  <Table.Th>Driver Share</Table.Th>
                  <Table.Th>Status</Table.Th>
                  <Table.Th>Date</Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                {transactions.map((transaction) => (
                  <Table.Tr key={transaction.id}>
                    <Table.Td className="font-medium">{transaction.booking_number}</Table.Td>
                    <Table.Td>{transaction.driver_name}</Table.Td>
                    <Table.Td>
                      <Badge variant="outline">
                        {getServiceTypeLabel(transaction.service_type)}
                      </Badge>
                    </Table.Td>
                    <Table.Td>{formatCurrency(transaction.total_fare)}</Table.Td>
                    <Table.Td className="font-semibold text-orange-600">
                      {formatCurrency(transaction.commission_amount)}
                    </Table.Td>
                    <Table.Td>{formatCurrency(transaction.driver_share)}</Table.Td>
                    <Table.Td>
                      <Badge color={transaction.status === 'completed' ? 'green' : 'yellow'}>
                        {transaction.status}
                      </Badge>
                    </Table.Td>
                    <Table.Td>
                      {new Date(transaction.created_at).toLocaleDateString()}
                    </Table.Td>
                  </Table.Tr>
                ))}
              </Table.Tbody>
            </Table>
          </div>
        </Card>
      </div>
    </>
  );
};

export default CommissionAnalytics;
