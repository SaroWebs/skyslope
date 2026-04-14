import React from 'react';
import { Link } from '@inertiajs/react';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="relative bg-gradient-to-br from-neutral-900 via-neutral-800 to-neutral-900 text-white overflow-hidden">
      <div className="relative container-modern py-12">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
          <div>
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-10 h-10 bg-gradient-primary rounded-xl flex items-center justify-center">
                <span className="text-white font-bold text-lg">S</span>
              </div>
              <span className="text-2xl font-bold">SkySlope</span>
            </div>
            <p className="text-neutral-300 text-sm">
              A unified operations platform for tours, rentals, and mobility services.
            </p>
          </div>

          <div>
            <h4 className="text-lg font-semibold mb-4">Explore</h4>
            <div className="space-y-2 text-sm">
              <a href="#features" className="block text-neutral-300 hover:text-white">Features</a>
              <a href="#services" className="block text-neutral-300 hover:text-white">Services</a>
              <a href="#contact" className="block text-neutral-300 hover:text-white">Contact</a>
            </div>
          </div>

          <div>
            <h4 className="text-lg font-semibold mb-4">Admin</h4>
            <div className="space-y-2 text-sm">
              <Link href="/login" className="block text-neutral-300 hover:text-white">Login</Link>
              <Link href="/login" className="block text-neutral-300 hover:text-white">Dashboard Access</Link>
              <p className="text-neutral-400 pt-2">info@skyslope.com</p>
            </div>
          </div>
        </div>

        <div className="border-t border-neutral-800/50 mt-10 pt-6">
          <p className="text-neutral-400 text-sm">© {currentYear} SkySlope. All rights reserved.</p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
