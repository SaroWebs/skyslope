import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  Button,
  Menu,
  Text,
  Group,
  Box,
  Burger,
  Drawer,
  Stack,
  Divider,
  Avatar,
  NavLink as MantineNavLink
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';

type Props = {
  currentPage?: string;
};

const Navigation = ({ currentPage = 'home' }: Props) => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [userMenuOpened, setUserMenuOpened] = useState(false);
  const [mobileOpened, { toggle: toggleMobile, close: closeMobile }] = useDisclosure(false);
  const { auth } = usePage().props as any;

  const navigationItems = [
    { name: 'Home', href: '/', current: currentPage === 'home' },
    { name: 'Tours', href: '/tours', current: currentPage === 'tours' },
    { name: 'Destinations', href: '/destinations', current: currentPage === 'destinations' },
    { name: 'Car Rental', href: '/car-rental', current: currentPage === 'car-rental' },
    { name: 'Ride Booking', href: '/ride-booking', current: currentPage === 'ride-booking' },
    { name: 'About', href: '/about', current: currentPage === 'about' },
    { name: 'Contact', href: '/contact', current: currentPage === 'contact' },
  ];

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 10);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (userMenuOpened && !(event.target as Element).closest('.user-menu-container')) {
        setUserMenuOpened(false);
      }
    };

    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  }, [userMenuOpened]);

  return (
    <>
      <nav className={`sticky top-0 z-50 transition-all duration-300 ${
        isScrolled
          ? 'bg-white/95 backdrop-blur-lg shadow-xl border-b border-neutral-200/50'
          : 'bg-white/90 backdrop-blur-sm shadow-lg'
      }`}>
        <div className="container-modern px-4">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <div className="flex-shrink-0">
              <Link
                href="/"
                className="group flex items-center space-x-2 animate-slide-in"
              >
                <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <span className="text-white font-bold text-sm">S</span>
                </div>
                <span className="text-2xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                  SkySlope
                </span>
              </Link>
            </div>

            {/* Desktop Navigation */}
            <div className="hidden md:flex items-center space-x-1">
              {navigationItems.map((item, index) => (
                <Link
                  key={item.name}
                  href={item.href}
                  className={`relative px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 animate-fade-in`}
                  style={{ animationDelay: `${index * 100}ms` }}
                >
                  <span className={`relative z-10 transition-colors duration-300 ${
                    item.current
                      ? 'text-blue-700'
                      : 'text-neutral-600 hover:text-blue-600'
                  }`}>
                    {item.name}
                  </span>
                  {item.current && (
                    <div className="absolute inset-0 bg-blue-100 rounded-xl border border-blue-200 animate-scale-in" />
                  )}
                  <div className={`absolute inset-0 rounded-xl transition-all duration-300 ${
                    item.current
                      ? 'bg-blue-100 border border-blue-200'
                      : 'bg-transparent hover:bg-neutral-100'
                  }`} />
                </Link>
              ))}
            </div>

            {/* CTA Buttons */}
            <div className="hidden md:flex items-center space-x-3">
              {auth?.user ? (
                <Menu
                  shadow="md"
                  width={200}
                  opened={userMenuOpened}
                  onChange={setUserMenuOpened}
                >
                  <Menu.Target>
                    <Button
                      variant="subtle"
                      className="user-menu-container"
                      rightSection={
                        <svg className={`w-4 h-4 transition-transform duration-200 ${userMenuOpened ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      }
                    >
                      <Group gap="xs">
                        <Avatar size="sm" color="blue">
                          {auth.user.name.charAt(0).toUpperCase()}
                        </Avatar>
                        <Text size="sm">Welcome, {auth.user.name}</Text>
                      </Group>
                    </Button>
                  </Menu.Target>

                  <Menu.Dropdown>
                    <Menu.Item component={Link} href="/dashboard">
                      Dashboard
                    </Menu.Item>
                    {auth.user.roles?.some((role: any) => role.name === 'admin') && (
                      <Menu.Item component={Link} href="/admin/dashboard">
                        Admin Panel
                      </Menu.Item>
                    )}
                    <Menu.Divider />
                    <Menu.Item component={Link} href="/logout" method="post" color="red">
                      Logout
                    </Menu.Item>
                  </Menu.Dropdown>
                </Menu>
              ) : (
                <Group gap="sm">
                  <Button component={Link} href="/login" variant="light" size="sm">
                    Login
                  </Button>
                  <Button component={Link} href="/book-now" size="sm">
                    Book Tour
                  </Button>
                </Group>
              )}
            </div>

            {/* Mobile Menu Button */}
            <div className="md:hidden">
              <Burger
                opened={mobileOpened}
                onClick={toggleMobile}
                size="sm"
                color={isScrolled ? 'dark' : 'dark'}
              />
            </div>
          </div>
        </div>
      </nav>

      {/* Mobile Menu Drawer */}
      <Drawer
        opened={mobileOpened}
        onClose={closeMobile}
        title={
          <Group gap="xs">
            <div className="w-6 h-6 bg-gradient-to-br from-blue-500 to-blue-700 rounded flex items-center justify-center">
              <span className="text-white font-bold text-xs">S</span>
            </div>
            <Text fw={600}>SkySlope</Text>
          </Group>
        }
        padding="md"
        size="sm"
        position="right"
        styles={{
          header: {
            backgroundColor: '#f8fafc',
            borderBottom: '1px solid #e2e8f0'
          },
          body: {
            backgroundColor: '#ffffff',
            padding: 0
          }
        }}
      >
        <Stack gap="xs" p="md">
          {navigationItems.map((item) => (
            <MantineNavLink
              key={item.name}
              component={Link}
              href={item.href}
              label={item.name}
              active={item.current}
              onClick={closeMobile}
              styles={{
                root: {
                  borderRadius: '8px',
                  '&[data-active="true"]': {
                    backgroundColor: '#dbeafe',
                    color: '#1d4ed8',
                    border: '1px solid #93c5fd'
                  }
                }
              }}
            />
          ))}

          <Divider my="md" />

          {auth?.user ? (
            <Stack gap="xs">
              <Button component={Link} href="/dashboard" variant="light" fullWidth onClick={closeMobile}>
                Dashboard
              </Button>
              {auth.user.roles?.some((role: any) => role.name === 'admin') && (
                <Button component={Link} href="/admin/dashboard" variant="light" fullWidth onClick={closeMobile}>
                  Admin Panel
                </Button>
              )}
              <Button component={Link} href="/logout" method="post" color="red" variant="light" fullWidth onClick={closeMobile}>
                Logout
              </Button>
            </Stack>
          ) : (
            <Stack gap="xs">
              <Button component={Link} href="/login" variant="light" fullWidth onClick={closeMobile}>
                Login
              </Button>
              <Button component={Link} href="/book-now" fullWidth onClick={closeMobile}>
                Book Tour
              </Button>
            </Stack>
          )}
        </Stack>
      </Drawer>
    </>
  );
};

export default Navigation;