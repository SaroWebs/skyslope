import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  Button,
  Menu,
  Text,
  Group,
  Burger,
  Drawer,
  Stack,
  Divider,
  Avatar,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';

const Navigation = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [userMenuOpened, setUserMenuOpened] = useState(false);
  const [mobileOpened, { toggle: toggleMobile, close: closeMobile }] = useDisclosure(false);
  const { auth } = usePage<{ auth?: { user?: { name: string } } }>().props;

  const navigationItems = [
    { name: 'Features', href: '#features' },
    { name: 'Services', href: '#services' },
    { name: 'Contact', href: '#contact' },
  ];

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 10);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <>
      <nav className={`sticky top-0 z-50 transition-all duration-300 ${
        isScrolled
          ? 'bg-white/95 backdrop-blur-lg shadow-xl border-b border-neutral-200/50'
          : 'bg-white/90 backdrop-blur-sm shadow-lg'
      }`}>
        <div className="container-modern px-4">
          <div className="flex justify-between items-center h-16">
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

            <div className="hidden md:flex items-center space-x-1">
              {navigationItems.map((item, index) => (
                <a
                  key={item.name}
                  href={item.href}
                  className="relative px-4 py-2 rounded-xl text-sm font-medium text-neutral-600 hover:text-blue-600 hover:bg-neutral-100 transition-all duration-300 animate-fade-in"
                  style={{ animationDelay: `${index * 100}ms` }}
                >
                  {item.name}
                </a>
              ))}
            </div>

            <div className="hidden md:flex items-center space-x-3">
              {auth?.user ? (
                <Menu
                  shadow="md"
                  width={220}
                  opened={userMenuOpened}
                  onChange={setUserMenuOpened}
                >
                  <Menu.Target>
                    <Button
                      variant="subtle"
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
                        <Text size="sm">{auth.user.name}</Text>
                      </Group>
                    </Button>
                  </Menu.Target>

                  <Menu.Dropdown>
                    <Menu.Item component={Link} href="/admin/dashboard">
                      Admin Dashboard
                    </Menu.Item>
                    <Menu.Divider />
                    <Menu.Item component={Link} href="/admin/logout" method="post" color="red">
                      Logout
                    </Menu.Item>
                  </Menu.Dropdown>
                </Menu>
              ) : (
                <Group gap="sm">
                  <Button component={Link} href="/login" variant="light" size="sm">
                    Login
                  </Button>
                  <Button component={Link} href="/login" size="sm">
                    Admin Dashboard
                  </Button>
                </Group>
              )}
            </div>

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
      >
        <Stack gap="xs" p="md">
          {navigationItems.map((item) => (
            <a
              key={item.name}
              href={item.href}
              className="px-3 py-2 rounded-lg text-sm text-neutral-700 hover:bg-neutral-100"
              onClick={closeMobile}
            >
              {item.name}
            </a>
          ))}

          <Divider my="md" />

          {auth?.user ? (
            <Stack gap="xs">
              <Button component={Link} href="/admin/dashboard" variant="light" fullWidth onClick={closeMobile}>
                Admin Dashboard
              </Button>
              <Button component={Link} href="/admin/logout" method="post" color="red" variant="light" fullWidth onClick={closeMobile}>
                Logout
              </Button>
            </Stack>
          ) : (
            <Stack gap="xs">
              <Button component={Link} href="/login" variant="light" fullWidth onClick={closeMobile}>
                Login
              </Button>
              <Button component={Link} href="/login" fullWidth onClick={closeMobile}>
                Admin Dashboard
              </Button>
            </Stack>
          )}
        </Stack>
      </Drawer>
    </>
  );
};

export default Navigation;
