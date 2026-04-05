import React, { useState, useEffect, useCallback } from 'react';
import ModernCard, { CardContent, CardHeader, CardTitle } from '@/components/ui/ModernCard';
import { Badge, Button, Modal, TextInput } from '@mantine/core';
import {
  CreditCard,
  Plus,
  TrendingUp,
  TrendingDown,
  DollarSign,
  Calendar,
  Clock,
  CheckCircle,
  XCircle,
  Loader2,
  Download,
  Upload,
  AlertCircle,
  Wallet as WalletIcon
} from 'lucide-react';
import { loadRazorpayScript, openRazorpayCheckout, RazorpayConfig } from '@/lib/razorpay';
import axios from '@/lib/axios';

interface Wallet {
  id: number;
  balance: number;
  currency: string;
  status: string;
}

interface WalletTransaction {
  id: number;
  transaction_type: string;
  amount: number;
  description: string;
  reference_id?: string;
  status: string;
  created_at: string;
}

interface CommissionStats {
  total_commission: number;
  commission_count: number;
  average_commission: number;
}

interface TopupOrderResponse {
  success: boolean;
  data?: {
    order_id: string;
    amount: number;
    currency: string;
    receipt: string;
    razorpay_config: RazorpayConfig;
  };
  message?: string;
}

interface WithdrawalFormData {
  amount: string;
  bank_account: string;
  ifsc_code: string;
  account_holder_name: string;
}

const WalletBalance: React.FC<{ balance: number; currency: string }> = ({ balance, currency }) => {
  return (
    <ModernCard>
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span>Wallet Balance</span>
          <Badge variant="outline">{currency}</Badge>
        </CardTitle>
      </CardHeader>
      <CardContent className="text-center py-8">
        <div className="text-4xl font-bold text-green-600 mb-2">
          ₹{balance.toLocaleString('en-IN')}
        </div>
        <div className="text-sm text-gray-600">Available Balance</div>
      </CardContent>
    </ModernCard>
  );
};

const TopupForm: React.FC<{
  amount: string;
  onChange: (amount: string) => void;
  onSubmit: () => void;
  loading: boolean;
  razorpayLoaded: boolean;
}> = ({ amount, onChange, onSubmit, loading, razorpayLoaded }) => {
  const quickAmounts = [100, 500, 1000, 2000, 5000];

  return (
    <ModernCard>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Plus className="h-5 w-5" />
          Top Up Wallet
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Amount (₹)</label>
          <input
            type="number"
            value={amount}
            onChange={(e) => onChange(e.target.value)}
            placeholder="Enter amount"
            min="100"
            max="100000"
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
          />
        </div>

        <div className="flex flex-wrap gap-2">
          {quickAmounts.map((amt) => (
            <button
              key={amt}
              type="button"
              onClick={() => onChange(amt.toString())}
              className="px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
            >
              ₹{amt.toLocaleString()}
            </button>
          ))}
        </div>

        <Button
          onClick={onSubmit}
          disabled={loading || !amount || parseFloat(amount) < 100 || !razorpayLoaded}
          className="w-full bg-green-600 hover:bg-green-700 text-white"
        >
          {loading ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Processing...
            </>
          ) : !razorpayLoaded ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Loading Payment...
            </>
          ) : (
            <>
              <CreditCard className="mr-2 h-4 w-4" />
              Pay with Razorpay
            </>
          )}
        </Button>

        {amount && parseFloat(amount) < 100 && (
          <p className="text-sm text-red-600 flex items-center gap-1">
            <AlertCircle className="h-4 w-4" />
            Minimum top-up amount is ₹100
          </p>
        )}

        <div className="flex items-center gap-2 text-xs text-gray-500">
          <img
            src="https://cdn.razorpay.com/logo.svg"
            alt="Razorpay"
            className="h-4"
          />
          <span>Secure payment powered by Razorpay</span>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const WithdrawalForm: React.FC<{
  opened: boolean;
  onClose: () => void;
  onSubmit: (data: WithdrawalFormData) => void;
  loading: boolean;
  maxAmount: number;
}> = ({ opened, onClose, onSubmit, loading, maxAmount }) => {
  const [formData, setFormData] = useState<WithdrawalFormData>({
    amount: '',
    bank_account: '',
    ifsc_code: '',
    account_holder_name: '',
  });

  const handleSubmit = () => {
    onSubmit(formData);
    setFormData({ amount: '', bank_account: '', ifsc_code: '', account_holder_name: '' });
  };

  return (
    <Modal opened={opened} onClose={onClose} title="Withdraw to Bank Account" size="md">
      <div className="space-y-4 p-4">
        <div className="bg-blue-50 p-3 rounded-lg mb-4">
          <p className="text-sm text-blue-800">
            Available balance: <strong>₹{maxAmount.toLocaleString('en-IN')}</strong>
          </p>
        </div>

        <TextInput
          label="Amount (₹)"
          placeholder="Enter amount"
          type="number"
          value={formData.amount}
          onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
          min="100"
          max={maxAmount}
        />

        <TextInput
          label="Account Holder Name"
          placeholder="As per bank records"
          value={formData.account_holder_name}
          onChange={(e) => setFormData({ ...formData, account_holder_name: e.target.value })}
        />

        <TextInput
          label="Bank Account Number"
          placeholder="Enter account number"
          value={formData.bank_account}
          onChange={(e) => setFormData({ ...formData, bank_account: e.target.value })}
        />

        <TextInput
          label="IFSC Code"
          placeholder="e.g., SBIN0001234"
          value={formData.ifsc_code}
          onChange={(e) => setFormData({ ...formData, ifsc_code: e.target.value.toUpperCase() })}
        />

        <div className="flex gap-3 pt-4">
          <Button variant="outline" onClick={onClose} className="flex-1">
            Cancel
          </Button>
          <Button
            onClick={handleSubmit}
            loading={loading}
            disabled={!formData.amount || parseFloat(formData.amount) < 100 || parseFloat(formData.amount) > maxAmount}
            className="flex-1 bg-orange-600 hover:bg-orange-700"
          >
            Withdraw
          </Button>
        </div>
      </div>
    </Modal>
  );
};

const TransactionHistory: React.FC<{ transactions: WalletTransaction[] }> = ({ transactions }) => {
  const getTransactionIcon = (type: string) => {
    switch (type) {
      case 'credit': return <TrendingUp className="h-4 w-4 text-green-600" />;
      case 'debit': return <TrendingDown className="h-4 w-4 text-red-600" />;
      case 'commission': return <DollarSign className="h-4 w-4 text-blue-600" />;
      case 'topup': return <Plus className="h-4 w-4 text-green-600" />;
      case 'withdrawal': return <Download className="h-4 w-4 text-red-600" />;
      default: return <CreditCard className="h-4 w-4 text-gray-600" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed': return 'bg-green-100 text-green-800';
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'failed': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed': return <CheckCircle className="h-4 w-4 text-green-600" />;
      case 'pending': return <Clock className="h-4 w-4 text-yellow-600" />;
      case 'failed': return <XCircle className="h-4 w-4 text-red-600" />;
      default: return null;
    }
  };

  return (
    <ModernCard>
      <CardHeader>
        <CardTitle>Transaction History</CardTitle>
      </CardHeader>
      <CardContent>
        {transactions.length === 0 ? (
          <div className="text-center py-8 text-gray-500">
            No transactions found
          </div>
        ) : (
          <div className="space-y-3">
            {transactions.slice(0, 10).map((transaction) => (
              <div key={transaction.id} className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-gray-100 rounded-full">
                    {getTransactionIcon(transaction.transaction_type)}
                  </div>
                  <div>
                    <div className="font-semibold">{transaction.description}</div>
                    <div className="text-sm text-gray-600">
                      {new Date(transaction.created_at).toLocaleString('en-IN')}
                    </div>
                  </div>
                </div>
                <div className="text-right">
                  <div className={`font-semibold ${transaction.transaction_type === 'credit' || transaction.transaction_type === 'topup'
                      ? 'text-green-600'
                      : 'text-red-600'
                    }`}>
                    {transaction.transaction_type === 'credit' || transaction.transaction_type === 'topup'
                      ? `+₹${transaction.amount.toLocaleString('en-IN')}`
                      : `-₹${transaction.amount.toLocaleString('en-IN')}`}
                  </div>
                  <Badge className={getStatusColor(transaction.status)}>
                    {getStatusIcon(transaction.status)}
                    <span className="ml-1">{transaction.status}</span>
                  </Badge>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </ModernCard>
  );
};

const CommissionSummary: React.FC<{ stats: CommissionStats }> = ({ stats }) => {
  return (
    <ModernCard>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <DollarSign className="h-5 w-5" />
          <span>Commission Summary</span>
        </CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="text-center p-4 bg-blue-50 rounded-lg">
          <DollarSign className="mx-auto h-8 w-8 text-blue-600 mb-2" />
          <div className="text-2xl font-bold text-blue-900">₹{stats.total_commission.toLocaleString('en-IN')}</div>
          <div className="text-sm text-blue-600">Total Commission</div>
        </div>
        <div className="text-center p-4 bg-green-50 rounded-lg">
          <Calendar className="mx-auto h-8 w-8 text-green-600 mb-2" />
          <div className="text-2xl font-bold text-green-900">{stats.commission_count}</div>
          <div className="text-sm text-green-600">Commission Count</div>
        </div>
        <div className="text-center p-4 bg-purple-50 rounded-lg">
          <TrendingUp className="mx-auto h-8 w-8 text-purple-600 mb-2" />
          <div className="text-2xl font-bold text-purple-900">₹{stats.average_commission.toLocaleString('en-IN')}</div>
          <div className="text-sm text-purple-600">Avg Commission</div>
        </div>
      </CardContent>
    </ModernCard>
  );
};

const DriverWallet: React.FC = () => {
  const [wallet, setWallet] = useState<Wallet | null>(null);
  const [transactions, setTransactions] = useState<WalletTransaction[]>([]);
  const [commissionStats, setCommissionStats] = useState<CommissionStats>({
    total_commission: 0,
    commission_count: 0,
    average_commission: 0
  });
  const [topupAmount, setTopupAmount] = useState('');
  const [topupLoading, setTopupLoading] = useState(false);
  const [loading, setLoading] = useState(true);
  const [razorpayLoaded, setRazorpayLoaded] = useState(false);
  const [withdrawalOpened, setWithdrawalOpened] = useState(false);
  const [withdrawalLoading, setWithdrawalLoading] = useState(false);
  const [notification, setNotification] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Load Razorpay SDK
  useEffect(() => {
    loadRazorpayScript().then((loaded) => {
      setRazorpayLoaded(loaded);
      if (!loaded) {
        console.error('Failed to load Razorpay SDK');
      }
    });
  }, []);

  // Fetch wallet data
  const fetchWalletData = useCallback(async () => {
    try {
      const [walletRes, transactionsRes, statsRes] = await Promise.allSettled([
        axios.get('/wallet'),
        axios.get('/wallet/transactions'),
        axios.get('/wallet/stats')
      ]);

      if (walletRes.status === 'fulfilled' && walletRes.value.data.success) {
        setWallet(walletRes.value.data.data);
      }

      if (transactionsRes.status === 'fulfilled' && transactionsRes.value.data.success) {
        setTransactions(transactionsRes.value.data.data.data || []);
      }

      if (statsRes.status === 'fulfilled' && statsRes.value.data.success) {
        setCommissionStats(statsRes.value.data.data.commission_stats);
      }

      if (walletRes.status === 'rejected') {
        setNotification({ type: 'error', message: 'Failed to load wallet data' });
      }
    } catch (error) {
      console.error('Error fetching wallet data:', error);
      setNotification({ type: 'error', message: 'Failed to load wallet data' });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchWalletData();
  }, [fetchWalletData]);

  useEffect(() => {
    if (!notification) return;

    const timeout = window.setTimeout(() => setNotification(null), 5000);
    return () => window.clearTimeout(timeout);
  }, [notification]);

  const handleTopup = async () => {
    if (!topupAmount || parseFloat(topupAmount) < 100) {
      setNotification({ type: 'error', message: 'Please enter a valid amount (minimum ₹100)' });
      return;
    }

    if (!razorpayLoaded) {
      setNotification({ type: 'error', message: 'Payment system is loading. Please try again.' });
      return;
    }

    setTopupLoading(true);

    try {
      // Create order
      const orderResponse = await axios.post<TopupOrderResponse>('/wallet/topup/order', {
        amount: parseFloat(topupAmount)
      });

      if (!orderResponse.data.success || !orderResponse.data.data) {
        throw new Error(orderResponse.data.message || 'Failed to create order');
      }

      const { order_id, amount, razorpay_config } = orderResponse.data.data;

      // Open Razorpay checkout
      const paymentResponse = await openRazorpayCheckout({
        key: razorpay_config.key,
        amount: amount * 100, // Convert to paise
        currency: 'INR',
        name: razorpay_config.name,
        image: razorpay_config.image,
        order_id: order_id,
        prefill: razorpay_config.prefill,
        theme: razorpay_config.theme,
      });

      // Verify payment
      const verifyResponse = await axios.post('/wallet/topup/verify', {
        razorpay_order_id: paymentResponse.razorpay_order_id,
        razorpay_payment_id: paymentResponse.razorpay_payment_id,
        razorpay_signature: paymentResponse.razorpay_signature,
      });

      if (verifyResponse.data.success) {
        setNotification({ type: 'success', message: 'Wallet topped up successfully!' });
        setTopupAmount('');
        fetchWalletData(); // Refresh data
      } else {
        throw new Error(verifyResponse.data.message || 'Payment verification failed');
      }
    } catch (error: any) {
      console.error('Error topping up wallet:', error);
      if (error.message !== 'Payment cancelled by user') {
        setNotification({
          type: 'error',
          message: error.response?.data?.message || error.message || 'Failed to top up wallet'
        });
      }
    } finally {
      setTopupLoading(false);
    }
  };

  const handleWithdrawal = async (data: WithdrawalFormData) => {
    if (!wallet || parseFloat(data.amount) > wallet.balance) {
      setNotification({ type: 'error', message: 'Insufficient balance' });
      return;
    }

    setWithdrawalLoading(true);

    try {
      const response = await axios.post('/wallet/withdraw', {
        amount: parseFloat(data.amount),
        bank_account: `${data.account_holder_name}|${data.bank_account}|${data.ifsc_code}`,
      });

      if (response.data.success) {
        setNotification({ type: 'success', message: 'Withdrawal request submitted successfully!' });
        setWithdrawalOpened(false);
        fetchWalletData(); // Refresh data
      } else {
        throw new Error(response.data.message || 'Withdrawal failed');
      }
    } catch (error: any) {
      console.error('Error processing withdrawal:', error);
      setNotification({
        type: 'error',
        message: error.response?.data?.message || 'Failed to process withdrawal'
      });
    } finally {
      setWithdrawalLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>
    );
  }

  if (!wallet) {
    return (
      <div className="text-center py-8 text-gray-500">
        <WalletIcon className="h-12 w-12 mx-auto mb-4 text-gray-400" />
        <p>Wallet not found. Please contact support.</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Notification */}
      {notification && (
        <div className={`fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${notification.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
          }`}>
          <div className="flex items-center gap-2">
            {notification.type === 'success' ? (
              <CheckCircle className="h-5 w-5" />
            ) : (
              <AlertCircle className="h-5 w-5" />
            )}
            <span>{notification.message}</span>
            <button onClick={() => setNotification(null)} className="ml-auto">
              <XCircle className="h-4 w-4" />
            </button>
          </div>
        </div>
      )}

      {/* Header */}
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-gray-900">Driver Wallet</h1>
        <div className="flex space-x-2">
          <Button
            variant="outline"
            onClick={() => setWithdrawalOpened(true)}
            disabled={wallet.balance < 100}
          >
            <Upload className="mr-2 h-4 w-4" />
            Withdraw
          </Button>
        </div>
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <WalletBalance balance={wallet.balance} currency={wallet.currency} />
        <TopupForm
          amount={topupAmount}
          onChange={setTopupAmount}
          onSubmit={handleTopup}
          loading={topupLoading}
          razorpayLoaded={razorpayLoaded}
        />
        <CommissionSummary stats={commissionStats} />
      </div>

      {/* Transaction History */}
      <TransactionHistory transactions={transactions} />

      {/* Withdrawal Modal */}
      <WithdrawalForm
        opened={withdrawalOpened}
        onClose={() => setWithdrawalOpened(false)}
        onSubmit={handleWithdrawal}
        loading={withdrawalLoading}
        maxAmount={wallet.balance}
      />
    </div>
  );
};

export default DriverWallet;
