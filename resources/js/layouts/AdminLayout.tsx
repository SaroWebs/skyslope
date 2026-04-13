import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { 
    AppShell, 
    Burger, 
    Group, 
    Text, 
    UnstyledButton, 
    ScrollArea, 
    Avatar, 
    Menu, 
    rem, 
    Box,
    Collapse,
    Tooltip,
    ActionIcon,
    useMantineTheme,
    Divider
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { 
    LayoutDashboard, 
    Users, 
    Map, 
    ClipboardList, 
    Car, 
    MapPin, 
    Settings, 
    LogOut, 
    ChevronRight, 
    ChevronDown, 
    Key, 
    Globe,
    Tags,
    Bell,
    Search,
    UserCircle
} from 'lucide-react';

interface AdminLayoutProps {
    children: React.ReactNode;
    title?: string;
}

interface NavigationItem {
    name: string;
    href: string;
    icon: React.ElementType;
    current?: boolean;
    children?: { name: string; href: string }[];
}

const AdminLayout = ({ children, title = 'Admin Panel' }: AdminLayoutProps) => {
    const [opened, { toggle }] = useDisclosure();
    const { url, props } = usePage();
    const theme = useMantineTheme();
    const user = (props.auth as any)?.user || { name: 'Admin', email: 'admin@skyslope.com' };

    const navigation: NavigationItem[] = [
        { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, current: url === '/admin/dashboard' },
        { 
            name: 'Management', 
            href: '#', 
            icon: Users, 
            current: url.startsWith('/admin/users') || url.startsWith('/admin/customers') || url.startsWith('/admin/drivers') || url.startsWith('/admin/guides'),
            children: [
                { name: 'System Users', href: '/admin/users' },
                { name: 'Customers', href: '/admin/customers' },
                { name: 'Drivers', href: '/admin/drivers' },
                { name: 'Guides', href: '/admin/guides' },
            ]
        },
        { name: 'Ride Bookings', href: '/admin/ride-bookings', icon: Car, current: url.startsWith('/admin/ride-bookings') },
        { name: 'Tour Packages', href: '/admin/tours', icon: Map, current: url.startsWith('/admin/tours') },
        { name: 'Tour Bookings', href: '/admin/tour-bookings', icon: ClipboardList, current: url.startsWith('/admin/tour-bookings') },
        { name: 'Car Rentals', href: '/admin/car-rentals', icon: Key, current: url.startsWith('/admin/car-rentals') },
        { 
            name: 'Master Data', 
            href: '#', 
            icon: MapPin, 
            current: url.startsWith('/admin/places') || url.startsWith('/admin/car-categories') || url.startsWith('/admin/destinations') || url.startsWith('/admin/vehicles'),
            children: [
                { name: 'Point of Interest', href: '/admin/places' },
                { name: 'Car Categories', href: '/admin/car-categories' },
                { name: 'Vehicles', href: '/admin/vehicles' },
                { name: 'Destinations', href: '/admin/destinations' },
            ]
        },
        { name: 'Settings', href: '/admin/settings', icon: Settings, current: url.startsWith('/admin/settings') },
    ];

    return (
        <AppShell
            header={{ height: 70 }}
            navbar={{
                width: 280,
                breakpoint: 'sm',
                collapsed: { mobile: !opened },
            }}
            padding="md"
            styles={{
                main: {
                    background: 'var(--mantine-color-gray-0)',
                },
            }}
        >
            <Head title={title} />

            <AppShell.Header px="md">
                <Group h="100%" justify="space-between">
                    <Group>
                        <Burger opened={opened} onClick={toggle} hiddenFrom="sm" size="sm" />
                        <Group gap="xs">
                            <Box 
                                w={34} h={34} 
                                bg="blue.6" 
                                style={{ borderRadius: '8px', display: 'flex', alignItems: 'center', justify: 'center' }}
                            >
                                <Globe size={20} color="white" />
                            </Box>
                            <Text fw={800} size="xl" style={{ letterSpacing: '-0.5px' }}>
                                SKY<span style={{ color: 'var(--mantine-color-blue-6)' }}>SLOPE</span>
                            </Text>
                        </Group>
                    </Group>

                    <Group gap="lg">
                        <Group gap="xs" visibleFrom="sm">
                            <ActionIcon variant="subtle" color="gray" size="lg" radius="md">
                                <Search size={20} strokeWidth={1.5} />
                            </ActionIcon>
                            <ActionIcon variant="subtle" color="gray" size="lg" radius="md">
                                <Bell size={20} strokeWidth={1.5} />
                            </ActionIcon>
                        </Group>

                        <Menu shadow="md" width={200} position="bottom-end" transitionProps={{ transition: 'pop-top-right' }}>
                            <Menu.Target>
                                <UnstyledButton>
                                    <Group gap="xs">
                                        <Avatar 
                                            src={null} 
                                            radius="xl" 
                                            size="md" 
                                            color="blue"
                                            variant="filled"
                                        >
                                            {user.name.charAt(0)}
                                        </Avatar>
                                        <div style={{ flex: 1 }} className="hidden sm:block">
                                            <Text size="sm" fw={600} mb={-2}>
                                                {user.name}
                                            </Text>
                                            <Text size="xs" color="dimmed">
                                                Administrator
                                            </Text>
                                        </div>
                                        <ChevronDown size={14} className="hidden sm:block" />
                                    </Group>
                                </UnstyledButton>
                            </Menu.Target>

                            <Menu.Dropdown>
                                <Menu.Label>Application</Menu.Label>
                                <Menu.Item leftSection={<UserCircle style={{ width: rem(14), height: rem(14) }} />}>
                                    Profile
                                </Menu.Item>
                                <Menu.Item leftSection={<Settings style={{ width: rem(14), height: rem(14) }} />}>
                                    Settings
                                </Menu.Item>
                                <Menu.Divider />
                                <Menu.Item 
                                    color="red" 
                                    leftSection={<LogOut style={{ width: rem(14), height: rem(14) }} />}
                                    component={Link}
                                    href="/logout"
                                    method="post"
                                    as="button"
                                >
                                    Logout
                                </Menu.Item>
                            </Menu.Dropdown>
                        </Menu>
                    </Group>
                </Group>
            </AppShell.Header>

            <AppShell.Navbar p="xs" bg="slate.9" style={{ borderRight: 'none' }}>
                <AppShell.Section grow component={ScrollArea}>
                    <Box py="md">
                        {navigation.map((item) => (
                            <NavItem key={item.name} item={item} />
                        ))}
                    </Box>
                </AppShell.Section>
                
                <AppShell.Section>
                    <Divider my="sm" color="slate.7" />
                    <Box px="xs" pb="xs">
                        <UnstyledButton
                            component={Link}
                            href="/logout"
                            method="post"
                            as="button"
                            style={{
                                width: '100%',
                                padding: '12px',
                                borderRadius: '8px',
                                color: 'var(--mantine-color-red-2)',
                                transition: 'all 0.2s ease',
                            }}
                            className="hover:bg-red-900/20"
                        >
                            <Group gap="sm">
                                <LogOut size={20} strokeWidth={1.5} />
                                <Text size="sm" fw={500}>Sign Out</Text>
                            </Group>
                        </UnstyledButton>
                    </Box>
                </AppShell.Section>
            </AppShell.Navbar>

            <AppShell.Main>
                <Box px={{ base: 0, sm: 'md' }} py="md">
                    <Group justify="space-between" mb="xl">
                        <Box>
                            <Text size="xs" color="dimmed" fw={500} tt="uppercase" mb={4}>
                                Admin Area
                            </Text>
                            <Text size="xl" fw={800} style={{ fontFamily: 'Greycliff CF, var(--mantine-font-family)' }}>
                                {title}
                            </Text>
                        </Box>
                        
                        <Group gap="sm">
                           {/* Action buttons could go here depending on the page */}
                        </Group>
                    </Group>
                    
                    {children}
                </Box>
            </AppShell.Main>
        </AppShell>
    );
};

const NavItem = ({ item }: { item: NavigationItem }) => {
    const { url } = usePage();
    const [opened, setOpened] = useState(item.current);
    const hasChildren = !!item.children;
    const isActive = item.current;

    const content = (
        <Group justify="space-between" gap={0}>
            <Group gap="sm">
                <item.icon 
                    size={20} 
                    strokeWidth={isActive ? 2 : 1.5} 
                    color={isActive ? 'var(--mantine-color-blue-4)' : 'var(--mantine-color-slate-3)'} 
                />
                <Text size="sm" fw={isActive ? 600 : 500}>
                    {item.name}
                </Text>
            </Group>
            {hasChildren && (
                <ChevronRight
                    size={14}
                    strokeWidth={2}
                    style={{
                        transform: opened ? 'rotate(90deg)' : 'none',
                        transition: 'transform 200ms ease',
                    }}
                />
            )}
        </Group>
    );

    return (
        <Box mb={4}>
            {hasChildren ? (
                <UnstyledButton
                    onClick={() => setOpened((o) => !o)}
                    style={{
                        width: '100%',
                        padding: '10px 12px',
                        borderRadius: '8px',
                        color: isActive ? 'white' : 'var(--mantine-color-slate-3)',
                        backgroundColor: isActive && !opened ? 'rgba(255, 255, 255, 0.05)' : 'transparent',
                        transition: 'all 0.2s ease',
                    }}
                    className="hover:bg-slate-800"
                >
                    {content}
                </UnstyledButton>
            ) : (
                <UnstyledButton
                    component={Link}
                    href={item.href}
                    style={{
                        width: '100%',
                        padding: '10px 12px',
                        borderRadius: '8px',
                        color: isActive ? 'white' : 'var(--mantine-color-slate-3)',
                        backgroundColor: isActive ? 'var(--mantine-color-blue-9)' : 'transparent',
                        transition: 'all 0.2s ease',
                    }}
                    className={isActive ? '' : 'hover:bg-slate-800'}
                >
                    {content}
                </UnstyledButton>
            )}

            {hasChildren && (
                <Collapse in={!!opened}>
                    <Box pt={4} pl={34}>
                        {item.children?.map((child) => {
                            const isChildActive = url.startsWith(child.href);
                            return (
                                <UnstyledButton
                                    key={child.name}
                                    component={Link}
                                    href={child.href}
                                    style={{
                                        display: 'block',
                                        width: '100%',
                                        padding: '8px 12px',
                                        borderRadius: '6px',
                                        fontSize: '13px',
                                        fontWeight: isChildActive ? 600 : 400,
                                        color: isChildActive ? 'var(--mantine-color-blue-4)' : 'var(--mantine-color-slate-4)',
                                        transition: 'all 0.2s ease',
                                    }}
                                    className="hover:bg-slate-800 hover:text-white"
                                >
                                    {child.name}
                                </UnstyledButton>
                            );
                        })}
                    </Box>
                </Collapse>
            )}
        </Box>
    );
};

export default AdminLayout;
