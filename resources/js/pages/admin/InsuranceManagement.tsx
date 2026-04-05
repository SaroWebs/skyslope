import React, { useState, useEffect } from 'react';
import ModernCard, { CardContent, CardHeader, CardTitle } from '@/components/ui/ModernCard';
import {
  Shield,
  Users,
  DollarSign,
  FileText,
  Eye,
  Edit,
  Trash2,
  Search,
  Filter,
  Plus,
  CheckCircle,
  XCircle,
  Clock
} from 'lucide-react';
import { Badge, Button } from '@mantine/core';

interface InsurancePolicy {
  id: number;
  policy_number: string;
  user: {
    name: string;
    email: string;
  };
  insurance_type: string;
  coverage_amount: number;
  premium_amount: number;
  start_date: string;
  end_date: string;
  status: string;
  payment_status: string;
  created_at: string;
}

interface Claim {
  id: number;
  claim_number: string;
  policy: {
    policy_number: string;
    user: {
      name: string;
    };
  };
  incident_date: string;
  incident_description: string;
  claim_amount: number;
  status: string;
  approved_by?: string;
  approved_at?: string;
  created_at: string;
}

const PolicyStats: React.FC<{ policies: InsurancePolicy[] }> = ({ policies }) => {
  const totalPolicies = policies.length;
  const activePolicies = policies.filter(p => p.status === 'active').length;
  const expiredPolicies = policies.filter(p => p.status === 'expired').length;
  const totalCoverage = policies.reduce((sum, p) => sum + p.coverage_amount, 0);
  const totalPremium = policies.reduce((sum, p) => sum + p.premium_amount, 0);

  return (
    <ModernCard>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <Shield className="h-5 w-5" />
          <span>Policy Statistics</span>
        </CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="text-center p-4 bg-blue-50 rounded-lg">
          <Shield className="mx-auto h-8 w-8 text-blue-600 mb-2" />
          <div className="text-2xl font-bold text-blue-900">{totalPolicies}</div>
          <div className="text-sm text-blue-600">Total Policies</div>
        </div>
        <div className="text-center p-4 bg-green-50 rounded-lg">
          <CheckCircle className="mx-auto h-8 w-8 text-green-600 mb-2" />
          <div className="text-2xl font-bold text-green-900">{activePolicies}</div>
          <div className="text-sm text-green-600">Active Policies</div>
        </div>
        <div className="text-center p-4 bg-red-50 rounded-lg">
          <XCircle className="mx-auto h-8 w-8 text-red-600 mb-2" />
          <div className="text-2xl font-bold text-red-900">{expiredPolicies}</div>
          <div className="text-sm text-red-600">Expired Policies</div>
        </div>
        <div className="text-center p-4 bg-purple-50 rounded-lg">
          <DollarSign className="mx-auto h-8 w-8 text-purple-600 mb-2" />
          <div className="text-2xl font-bold text-purple-900">₹{totalPremium.toLocaleString()}</div>
          <div className="text-sm text-purple-600">Total Premium</div>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const ClaimStats: React.FC<{ claims: Claim[] }> = ({ claims }) => {
  const totalClaims = claims.length;
  const pendingClaims = claims.filter(c => c.status === 'pending').length;
  const approvedClaims = claims.filter(c => c.status === 'approved').length;
  const rejectedClaims = claims.filter(c => c.status === 'rejected').length;
  const totalClaimAmount = claims.reduce((sum, c) => sum + c.claim_amount, 0);

  return (
    <ModernCard>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <FileText className="h-5 w-5" />
          <span>Claim Statistics</span>
        </CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="text-center p-4 bg-yellow-50 rounded-lg">
          <FileText className="mx-auto h-8 w-8 text-yellow-600 mb-2" />
          <div className="text-2xl font-bold text-yellow-900">{totalClaims}</div>
          <div className="text-sm text-yellow-600">Total Claims</div>
        </div>
        <div className="text-center p-4 bg-orange-50 rounded-lg">
          <Clock className="mx-auto h-8 w-8 text-orange-600 mb-2" />
          <div className="text-2xl font-bold text-orange-900">{pendingClaims}</div>
          <div className="text-sm text-orange-600">Pending Claims</div>
        </div>
        <div className="text-center p-4 bg-green-50 rounded-lg">
          <CheckCircle className="mx-auto h-8 w-8 text-green-600 mb-2" />
          <div className="text-2xl font-bold text-green-900">{approvedClaims}</div>
          <div className="text-sm text-green-600">Approved Claims</div>
        </div>
        <div className="text-center p-4 bg-red-50 rounded-lg">
          <XCircle className="mx-auto h-8 w-8 text-red-600 mb-2" />
          <div className="text-2xl font-bold text-red-900">{rejectedClaims}</div>
          <div className="text-sm text-red-600">Rejected Claims</div>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const RevenueStats: React.FC<{ policies: InsurancePolicy[]; claims: Claim[] }> = ({ policies, claims }) => {
  const totalRevenue = policies.reduce((sum, p) => sum + p.premium_amount, 0);
  const totalPayouts = claims.filter(c => c.status === 'paid').reduce((sum, c) => sum + c.claim_amount, 0);
  const netRevenue = totalRevenue - totalPayouts;

  return (
    <ModernCard>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <DollarSign className="h-5 w-5" />
          <span>Revenue Statistics</span>
        </CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="text-center p-4 bg-green-50 rounded-lg">
          <DollarSign className="mx-auto h-8 w-8 text-green-600 mb-2" />
          <div className="text-2xl font-bold text-green-900">₹{totalRevenue.toLocaleString()}</div>
          <div className="text-sm text-green-600">Total Revenue</div>
        </div>
        <div className="text-center p-4 bg-red-50 rounded-lg">
          <DollarSign className="mx-auto h-8 w-8 text-red-600 mb-2" />
          <div className="text-2xl font-bold text-red-900">₹{totalPayouts.toLocaleString()}</div>
          <div className="text-sm text-red-600">Total Payouts</div>
        </div>
        <div className="text-center p-4 bg-blue-50 rounded-lg">
          <DollarSign className="mx-auto h-8 w-8 text-blue-600 mb-2" />
          <div className="text-2xl font-bold text-blue-900">₹{netRevenue.toLocaleString()}</div>
          <div className="text-sm text-blue-600">Net Revenue</div>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const PolicyManagement: React.FC<{ policies: InsurancePolicy[] }> = ({ policies }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  const filteredPolicies = policies.filter(policy => {
    const matchesSearch = policy.policy_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         policy.user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         policy.user.email.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || policy.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'expired': return 'bg-red-100 text-red-800';
      case 'cancelled': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <ModernCard>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>Policy Management</CardTitle>
          <Button variant="outline" onClick={() => {}}>
            <Plus className="mr-2 h-4 w-4" />
            Add Policy
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col md:flex-row gap-4 mb-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <input
              type="text"
              placeholder="Search policies by number, name, or email..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full table-auto">
            <thead>
              <tr className="border-b">
                <th className="text-left p-2">Policy Number</th>
                <th className="text-left p-2">User</th>
                <th className="text-left p-2">Type</th>
                <th className="text-left p-2">Coverage</th>
                <th className="text-left p-2">Premium</th>
                <th className="text-left p-2">Status</th>
                <th className="text-left p-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredPolicies.map((policy) => (
                <tr key={policy.id} className="border-b hover:bg-gray-50">
                  <td className="p-2">{policy.policy_number}</td>
                  <td className="p-2">
                    <div>
                      <div className="font-semibold">{policy.user.name}</div>
                      <div className="text-sm text-gray-600">{policy.user.email}</div>
                    </div>
                  </td>
                  <td className="p-2 capitalize">{policy.insurance_type}</td>
                  <td className="p-2">₹{policy.coverage_amount.toLocaleString()}</td>
                  <td className="p-2">₹{policy.premium_amount.toLocaleString()}</td>
                  <td className="p-2">
                    <Badge className={getStatusColor(policy.status)}>
                      {policy.status}
                    </Badge>
                  </td>
                  <td className="p-2">
                    <div className="flex space-x-2">
                      <Button variant="outline" size="sm" onClick={() => {}}>
                        <Eye className="h-4 w-4" />
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => {}}>
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => {}}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const ClaimManagement: React.FC<{ claims: Claim[] }> = ({ claims }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  const filteredClaims = claims.filter(claim => {
    const matchesSearch = claim.claim_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         claim.policy.policy_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         claim.policy.user.name.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || claim.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'approved': return 'bg-green-100 text-green-800';
      case 'rejected': return 'bg-red-100 text-red-800';
      case 'paid': return 'bg-blue-100 text-blue-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const approveClaim = (claimId: number) => {
    // Implement approval logic
    console.log('Approving claim:', claimId);
  };

  const rejectClaim = (claimId: number) => {
    // Implement rejection logic
    console.log('Rejecting claim:', claimId);
  };

  return (
    <ModernCard>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>Claim Management</CardTitle>
        </div>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col md:flex-row gap-4 mb-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <input
              type="text"
              placeholder="Search claims by number, policy, or user..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="paid">Paid</option>
          </select>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full table-auto">
            <thead>
              <tr className="border-b">
                <th className="text-left p-2">Claim Number</th>
                <th className="text-left p-2">Policy Number</th>
                <th className="text-left p-2">User</th>
                <th className="text-left p-2">Incident Date</th>
                <th className="text-left p-2">Amount</th>
                <th className="text-left p-2">Status</th>
                <th className="text-left p-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredClaims.map((claim) => (
                <tr key={claim.id} className="border-b hover:bg-gray-50">
                  <td className="p-2">{claim.claim_number}</td>
                  <td className="p-2">{claim.policy.policy_number}</td>
                  <td className="p-2">{claim.policy.user.name}</td>
                  <td className="p-2">{new Date(claim.incident_date).toLocaleDateString()}</td>
                  <td className="p-2">₹{claim.claim_amount.toLocaleString()}</td>
                  <td className="p-2">
                    <Badge className={getStatusColor(claim.status)}>
                      {claim.status}
                    </Badge>
                  </td>
                  <td className="p-2">
                    <div className="flex space-x-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => approveClaim(claim.id)}
                        disabled={claim.status !== 'pending'}
                        className="bg-green-500 hover:bg-green-600 text-white"
                      >
                        Approve
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => rejectClaim(claim.id)}
                        disabled={claim.status !== 'pending'}
                        className="bg-red-500 hover:bg-red-600 text-white"
                      >
                        Reject
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const InsuranceManagement: React.FC = () => {
  const [policies, setPolicies] = useState<InsurancePolicy[]>([]);
  const [claims, setClaims] = useState<Claim[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Fetch insurance data
    const fetchInsuranceData = async () => {
      try {
        // Mock data for now - replace with actual API calls
        const mockPolicies: InsurancePolicy[] = [
          {
            id: 1,
            policy_number: 'POL-2025-000001',
            user: { name: 'John Doe', email: 'john@example.com' },
            insurance_type: 'comprehensive',
            coverage_amount: 500000,
            premium_amount: 20000,
            start_date: '2025-01-01',
            end_date: '2026-01-01',
            status: 'active',
            payment_status: 'paid',
            created_at: '2025-01-01T10:00:00Z'
          },
          {
            id: 2,
            policy_number: 'POL-2025-000002',
            user: { name: 'Jane Smith', email: 'jane@example.com' },
            insurance_type: 'third_party',
            coverage_amount: 1000000,
            premium_amount: 15000,
            start_date: '2025-06-01',
            end_date: '2026-06-01',
            status: 'expired',
            payment_status: 'paid',
            created_at: '2025-06-01T10:00:00Z'
          }
        ];

        const mockClaims: Claim[] = [
          {
            id: 1,
            claim_number: 'CLM-2025-000001',
            policy: {
              policy_number: 'POL-2025-000001',
              user: { name: 'John Doe' }
            },
            incident_date: '2025-12-15',
            incident_description: 'Minor collision at intersection',
            claim_amount: 15000,
            status: 'pending',
            created_at: '2025-12-15T14:30:00Z'
          },
          {
            id: 2,
            claim_number: 'CLM-2025-000002',
            policy: {
              policy_number: 'POL-2025-000002',
              user: { name: 'Jane Smith' }
            },
            incident_date: '2025-11-20',
            incident_description: 'Vehicle breakdown on highway',
            claim_amount: 8000,
            status: 'approved',
            approved_by: 'Admin User',
            approved_at: '2025-11-21T10:00:00Z',
            created_at: '2025-11-20T16:45:00Z'
          }
        ];

        setPolicies(mockPolicies);
        setClaims(mockClaims);
      } catch (error) {
        console.error('Error fetching insurance data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchInsuranceData();
  }, []);

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-gray-900">Insurance Management</h1>
        <div className="flex space-x-2">
          <Button variant="outline" onClick={() => {}}>
            <Shield className="mr-2 h-4 w-4" />
            Export Reports
          </Button>
          <Button onClick={() => {}}>
            <Plus className="mr-2 h-4 w-4" />
            Add Policy
          </Button>
        </div>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <PolicyStats policies={policies} />
        <ClaimStats claims={claims} />
        <RevenueStats policies={policies} claims={claims} />
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <PolicyManagement policies={policies} />
        <ClaimManagement claims={claims} />
      </div>
    </div>
  );
};

export default InsuranceManagement;