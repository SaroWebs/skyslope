import React, { ReactNode, useEffect, useState } from 'react';
import Navigation from '@/components/Navigation';
import Footer from '@/components/Footer';
import { Affix, Button, Transition, Box } from '@mantine/core';

type Props = {
  children?: ReactNode;
};

const WebsiteLayout = ({ children }: Props) => {
  const [scrollVisible, setScrollVisible] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setScrollVisible(window.scrollY > 400);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };

  return (
    <Box className="min-h-screen bg-gradient-to-br from-neutral-50 via-white to-neutral-50 text-neutral-900">
      {/* Background Pattern */}
      <div className="fixed inset-0 bg-grid-pattern opacity-[0.02] pointer-events-none" />

      {/* Navigation */}
      <Navigation />

      {/* Main Content */}
      <main className="relative">
        <div className="animate-fade-in">
          {children}
        </div>
      </main>

      {/* Footer */}
      <Footer />

      {/* Scroll to Top Button */}
      <Affix position={{ bottom: 20, right: 20 }}>
        <Transition transition="slide-up" mounted={scrollVisible}>
          {(transitionStyles) => (
            <Button
              onClick={scrollToTop}
              size="lg"
              style={{
                ...transitionStyles,
                borderRadius: '50%',
                width: '50px',
                height: '50px',
                padding: 0,
                background: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
                boxShadow: '0 4px 12px rgba(59, 130, 246, 0.4)',
              }}
              styles={{
                root: {
                  '&:hover': {
                    transform: 'scale(1.1)',
                    boxShadow: '0 6px 16px rgba(59, 130, 246, 0.5)',
                  }
                }
              }}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
            </Button>
          )}
        </Transition>
      </Affix>
    </Box>
  );
};

export default WebsiteLayout;
