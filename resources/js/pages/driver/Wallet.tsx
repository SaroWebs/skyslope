import React, { useState, useEffect, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
    Stack, 
    Group, 
    Text, 
    Title, 
    Paper, 
    Button, 
    ThemeIcon, 
    Badge, 
    ActionIcon, 
    Card, 
    Avatar, 
    Box, 
    rem,
    Divider,
    SimpleGrid,
    Modal,
    TextInput,
    NumberInput,
    ScrollArea,
    Indicator,
    Alert,
    Loader
} from '@mantine/core';
import { 
    Wallet as WalletIcon, 
    ArrowUpRight, 
    ArrowDownLeft, 
    Plus, 
    CreditCard, 
    History, 
    TrendingUp, 
    DollarSign, 
    Info, 
    CheckCircle2, 
    AlertCircle, 
    ChevronRight,
    Search,
    Download,
    ShieldCheck,
    Banknote,
    Clock
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAppNotifications } from '@/app';
import { loadRazorpayScript, openRazorpayCheckout, RazorpayConfig } from '@/lib/razorpay';
import axios from '@/lib/axios';

interface WalletData {
    id: number;
    balance: number;
    currency: string;
    status: string;
}

interface Transaction {
    id: number;
    transaction_type: string;
    amount: number;
    description: string;
    status: string;
    created_at: string;
}

interface CommissionStats {
    total_commission: number;
    commission_count: number;
    average_commission: number;
}

export default function Wallet() {
    const addNotification = useAppNotifications();
    const [wallet, setWallet] = useState<WalletData | null>(null);
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [stats, setStats] = useState<CommissionStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(false);
    
    // Payment/Withdrawal State
    const [topupOpened, setTopupOpened] = useState(false);
    const [withdrawOpened, setWithdrawOpened] = useState(false);
    const [topupAmount, setTopupAmount] = useState<number | string>(500);
    const [withdrawalData, setWithdrawalData] = useState({
        amount: '',
        bank_account: '',
        ifsc_code: '',
        account_holder_name: ''
    });

    const fetchData = useCallback(async () => {
        try {
            const [w, t, s] = await Promise.all([
                axios.get('/api/wallet'),
                axios.get('/api/wallet/transactions'),
                axios.get('/api/wallet/stats')
            ]);
            setWallet(w.data.data);
            setTransactions(t.data.data.data || []);
            setStats(s.data.data.commission_stats);
        } catch (error) {
            console.error('Failed to sync financial data');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
        loadRazorpayScript();
    }, [fetchData]);

    const handleTopup = async () => {
        const amount = Number(topupAmount);
        if (amount < 100) return addNotification('Minimum top-up is $100', 'error');
        
        setActionLoading(true);
        try {
            const orderRes = await axios.post('/api/wallet/topup/order', { amount });
            const { order_id, razorpay_config } = orderRes.data.data;
            
            const payment = await openRazorpayCheckout({
                key: razorpay_config.key,
                amount: amount * 100,
                currency: 'INR',
                name: 'Skyslope Wallet',
                order_id: order_id,
                prefill: razorpay_config.prefill,
                theme: razorpay_config.theme,
            });

            await axios.post('/api/wallet/topup/verify', payment);
            addNotification('Wallet infused successfully!', 'success');
            setTopupOpened(false);
            fetchData();
        } catch (error: any) {
            addNotification(error.message || 'Payment failed', 'error');
        } finally {
            setActionLoading(false);
        }
    };

    const handleWithdraw = async () => {
        const amount = Number(withdrawalData.amount);
        if (!wallet || amount > wallet.balance) return addNotification('Insufficient funds', 'error');
        
        setActionLoading(true);
        try {
            await axios.post('/api/wallet/withdraw', {
                amount,
                bank_account: `${withdrawalData.account_holder_name}|${withdrawalData.bank_account}|${withdrawalData.ifsc_code}`
            });
            addNotification('Withdrawal requested. Processing within 24h.', 'success');
            setWithdrawOpened(false);
            fetchData();
        } catch (error) {
            addNotification('Withdrawal failed', 'error');
        } finally {
            setActionLoading(false);
        }
    };

    const getTxIcon = (type: string) => {
        switch(type) {
            case 'credit': return <ArrowUpRight size={14} color="var(--mantine-color-green-6)" />;
            case 'debit': return <ArrowDownLeft size={14} color="var(--mantine-color-red-6)" />;
            default: return <Clock size={14} color="var(--mantine-color-gray-6)" />;
        }
    };

    return (
        <AppLayout title="Wallet" backPath="/driver/dashboard">
            <Head title="Financial Hub" />

            {loading ? (
                <Stack align="center" py="xl">
                    <Loader size="lg" type="dots" color="orange" />
                    <Text size="sm" color="dimmed">Syncing Ledger...</Text>
                </Stack>
            ) : (
                <Stack gap="lg">
                    {/* Premium Balance HUD */}
                    <Paper radius="md" p="xl" style={{ 
                        background: 'linear-gradient(135deg, var(--mantine-color-gray-9) 0%, var(--mantine-color-gray-8) 100%)',
                        color: '#fff',
                        boxShadow: '0 10px 30px rgba(0,0,0,0.1)'
                    }}>
                        <Stack align="center" gap={0}>
                            <Text size="xs" fw={700} tt="uppercase" ls={1} color="gray.5">Available Liquidity</Text>
                            <Title order={1} fw={900} fs="2.5rem" mt={4}>
                                ${wallet?.balance.toLocaleString()}
                            </Title>
                            <Group gap={8} mt="md">
                                <Badge variant="filled" color="green" size="sm">Active</Badge>
                                <Text size="xs" color="gray.5">Secure Escrow Storage</Text>
                            </Group>
                        </Stack>

                        <Group grow mt="xl" gap="sm">
                            <Button 
                                leftSection={<Download size={16} />} 
                                radius="md" 
                                color="orange"
                                variant="filled"
                                onClick={() => setWithdrawOpened(true)}
                            >
                                Cash Out
                            </Button>
                            <Button 
                                leftSection={<Plus size={16} />} 
                                radius="md" 
                                variant="white" 
                                color="gray.9"
                                onClick={() => setTopupOpened(true)}
                            >
                                Top Up
                            </Button>
                        </Group>
                    </Paper>

                    {/* Quick Stats Grid */}
                    <SimpleGrid cols={2} spacing="md">
                        <Card withBorder radius="md" p="md">
                            <Group justify="space-between" mb="xs">
                                <ThemeIcon variant="light" color="green">
                                    <TrendingUp size={18} />
                                </ThemeIcon>
                                <Text size={rem(10)} fw={700} color="dimmed">AVG/RIDE</Text>
                            </Group>
                            <Text size="lg" fw={900}>${stats?.average_commission.toFixed(1) || 0}</Text>
                        </Card>
                        <Card withBorder radius="md" p="md">
                            <Group justify="space-between" mb="xs">
                                <ThemeIcon variant="light" color="indigo">
                                    <Banknote size={18} />
                                </ThemeIcon>
                                <Text size={rem(10)} fw={700} color="dimmed">LIFETIME</Text>
                            </Group>
                            <Text size="lg" fw={900}>${stats?.total_commission.toLocaleString() || 0}</Text>
                        </Card>
                    </SimpleGrid>

                    {/* Transaction Audit Trail */}
                    <Box>
                        <Group justify="space-between" mb="sm">
                            <Title order={5} fw={900}>Audit Trail</Title>
                            <ActionIcon variant="subtle" color="gray"><Download size={16} /></ActionIcon>
                        </Group>
                        
                        <Stack gap="xs">
                            {transactions.length > 0 ? (
                                transactions.map(tx => (
                                    <Paper key={tx.id} p="sm" radius="md" withBorder shadow="none">
                                        <Group justify="space-between" wrap="nowrap">
                                            <Group gap="sm" wrap="nowrap">
                                                <ThemeIcon variant="light" color="gray" radius="sm" size="lg">
                                                    {getTxIcon(tx.transaction_type)}
                                                </ThemeIcon>
                                                <Stack gap={0} style={{ overflow: 'hidden' }}>
                                                    <Text size="sm" fw={700} truncate>{tx.description}</Text>
                                                    <Text size={rem(10)} color="dimmed">{new Date(tx.created_at).toLocaleDateString()} • {new Date(tx.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
                                                </Stack>
                                            </Group>
                                            <Box ta="right">
                                                <Text size="sm" fw={900} color={tx.transaction_type === 'credit' ? 'green.7' : 'red.7'}>
                                                    {tx.transaction_type === 'credit' ? '+' : '-'} ${tx.amount}
                                                </Text>
                                                <Badge size="xs" variant="dot" color={tx.status === 'completed' ? 'green' : 'yellow'}>
                                                    {tx.status}
                                                </Badge>
                                            </Box>
                                        </Group>
                                    </Paper>
                                ))
                            ) : (
                                <Paper p="xl" radius="md" withBorder style={{ borderStyle: 'dashed', textAlign: 'center' }}>
                                    <Text size="sm" color="dimmed">No transaction signals recorded.</Text>
                                </Paper>
                            )}
                        </Stack>
                    </Box>
                </Stack>
            )}

            {/* Topup Modal */}
            <Modal 
                opened={topupOpened} 
                onClose={() => setTopupOpened(false)} 
                title="Infuse Capital" 
                centered 
                radius="md"
            >
                <Stack gap="md">
                    <NumberInput 
                        label="Infusion Amount" 
                        prefix="$" 
                        radius="md" 
                        value={topupAmount} 
                        onChange={setTopupAmount} 
                        min={100}
                    />
                    <Alert icon={<Info size={16} />} color="blue" radius="md" size="xs">
                        Capital infusion is processed securely via Razorpay encryption protocols.
                    </Alert>
                    <Button 
                        fullWidth 
                        radius="md" 
                        color="orange" 
                        onClick={handleTopup}
                        loading={actionLoading}
                    >
                        Execute Infusion
                    </Button>
                </Stack>
            </Modal>

            {/* Withdrawal Modal */}
            <Modal 
                opened={withdrawOpened} 
                onClose={() => setWithdrawOpened(false)} 
                title="Liquidation Request" 
                centered 
                radius="md"
            >
                <Stack gap="md">
                    <NumberInput 
                        label="Amount to Liquidate" 
                        max={wallet?.balance || 0} 
                        prefix="$" 
                        radius="md"
                        value={withdrawalData.amount}
                        onChange={(val) => setWithdrawalData({...withdrawalData, amount: val.toString()})}
                    />
                    <TextInput 
                        label="Recepient Entity Name" 
                        placeholder="As per bank records" 
                        radius="md"
                        value={withdrawalData.account_holder_name}
                        onChange={(e) => setWithdrawalData({...withdrawalData, account_holder_name: e.target.value})}
                    />
                    <TextInput 
                        label="Account Identifier" 
                        placeholder="IBAN or Account Number" 
                        radius="md"
                        value={withdrawalData.bank_account}
                        onChange={(e) => setWithdrawalData({...withdrawalData, bank_account: e.target.value})}
                    />
                    <TextInput 
                        label="Institutional Routing Code" 
                        placeholder="SWIFT or IFSC" 
                        radius="md"
                        value={withdrawalData.ifsc_code}
                        onChange={(e) => setWithdrawalData({...withdrawalData, ifsc_code: e.target.value})}
                    />
                    <Button 
                        fullWidth 
                        radius="md" 
                        color="green" 
                        onClick={handleWithdraw}
                        loading={actionLoading}
                    >
                        Initialize Liquidation
                    </Button>
                </Stack>
            </Modal>
        </AppLayout>
    );
}
