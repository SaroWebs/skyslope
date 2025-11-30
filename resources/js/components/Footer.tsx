import React from 'react';
import { Link } from '@inertiajs/react';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  const footerLinks = {
    company: [
      { name: 'About Us', href: '/about' },
      { name: 'Our Story', href: '/story' },
      { name: 'Leadership', href: '/leadership' },
      { name: 'Careers', href: '/careers' },
      { name: 'Contact', href: '/contact' },
    ],
    services: [
      { name: 'Tour Packages', href: '/tours' },
      { name: 'Car Rentals', href: '/car-rental' },
      { name: 'Custom Trips', href: '/custom-trips' },
      { name: 'Group Tours', href: '/group-tours' },
      { name: 'Corporate Travel', href: '/corporate' },
    ],
    destinations: [
      { name: 'Assam', href: '/destinations/assam' },
      { name: 'Meghalaya', href: '/destinations/meghalaya' },
      { name: 'Arunachal Pradesh', href: '/destinations/arunachal' },
      { name: 'Northeast India', href: '/destinations/northeast' },
    ],
    support: [
      { name: 'Help Center', href: '/help' },
      { name: 'Booking Guide', href: '/booking-guide' },
      { name: 'Travel Tips', href: '/travel-tips' },
      { name: 'Terms & Conditions', href: '/terms' },
      { name: 'Privacy Policy', href: '/privacy' },
    ],
  };

  const socialLinks = [
    {
      name: 'Facebook',
      href: '#',
      icon: (
        <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
      ),
    },
    {
      name: 'Instagram',
      href: '#',
      icon: (
        <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.004 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.337-1.297-.889-.807-1.337-1.807-1.337-3.001 0-1.193.448-2.193 1.337-3.001.889-.807 2.04-1.297 3.337-1.297 1.297 0 2.448.49 3.337 1.297.889.808 1.337 1.808 1.337 3.001 0 1.194-.448 2.194-1.337 3.001-.889.807-2.04 1.297-3.337 1.297z"/>
        </svg>
      ),
    },
    {
      name: 'WhatsApp',
      href: '#',
      icon: (
        <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.516"/>
        </svg>
      ),
    },
  ];

  return (
    <footer className="relative bg-gradient-to-br from-neutral-900 via-neutral-800 to-neutral-900 text-white overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-5">
        <div className="absolute inset-0 bg-grid-pattern" />
      </div>

      <div className="relative">
        {/* Main Footer Content */}
        <div className="container-modern py-16">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-8 lg:gap-12">
            {/* Company Info */}
            <div className="lg:col-span-2 animate-fade-in">
              <div className="flex items-center space-x-3 mb-6">
                <div className="w-10 h-10 bg-gradient-primary rounded-xl flex items-center justify-center">
                  <span className="text-white font-bold text-lg">S</span>
                </div>
                <span className="text-2xl font-bold bg-gradient-to-r from-white to-neutral-300 bg-clip-text text-transparent">
                  SkySlope
                </span>
              </div>
              <p className="text-neutral-300 text-base leading-relaxed mb-6">
                Your trusted partner for unforgettable travel experiences across Northeast India.
                We create memories that last a lifetime with premium car rentals and curated tours.
              </p>

              {/* Contact Info */}
              <div className="space-y-3 mb-6">
                <div className="flex items-center space-x-3 text-neutral-300">
                  <div className="w-8 h-8 bg-primary-500/20 rounded-lg flex items-center justify-center">
                    <svg className="w-4 h-4 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <span className="text-sm">info@skyslope.com</span>
                </div>
                <div className="flex items-center space-x-3 text-neutral-300">
                  <div className="w-8 h-8 bg-secondary-500/20 rounded-lg flex items-center justify-center">
                    <svg className="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <span className="text-sm">+91 99544 97737</span>
                </div>
              </div>

              {/* Social Links */}
              <div className="flex space-x-4">
                {socialLinks.map((social) => (
                  <a
                    key={social.name}
                    href={social.href}
                    className="group w-10 h-10 bg-neutral-800 hover:bg-primary-500 rounded-xl flex items-center justify-center transition-all duration-300 hover:scale-110"
                  >
                    <div className="text-neutral-400 group-hover:text-white transition-colors duration-300">
                      {social.icon}
                    </div>
                  </a>
                ))}
              </div>
            </div>

            {/* Quick Links */}
            <div className="animate-fade-in" style={{ animationDelay: '100ms' }}>
              <h4 className="text-lg font-semibold mb-6 text-white">Company</h4>
              <ul className="space-y-3">
                {footerLinks.company.map((link) => (
                  <li key={link.name}>
                    <Link
                      href={link.href}
                      className="text-neutral-400 hover:text-white text-sm transition-all duration-300 hover:translate-x-1 inline-block"
                    >
                      {link.name}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Services */}
            <div className="animate-fade-in" style={{ animationDelay: '200ms' }}>
              <h4 className="text-lg font-semibold mb-6 text-white">Services</h4>
              <ul className="space-y-3">
                {footerLinks.services.map((link) => (
                  <li key={link.name}>
                    <Link
                      href={link.href}
                      className="text-neutral-400 hover:text-white text-sm transition-all duration-300 hover:translate-x-1 inline-block"
                    >
                      {link.name}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Destinations */}
            <div className="animate-fade-in" style={{ animationDelay: '300ms' }}>
              <h4 className="text-lg font-semibold mb-6 text-white">Destinations</h4>
              <ul className="space-y-3">
                {footerLinks.destinations.map((link) => (
                  <li key={link.name}>
                    <Link
                      href={link.href}
                      className="text-neutral-400 hover:text-white text-sm transition-all duration-300 hover:translate-x-1 inline-block"
                    >
                      {link.name}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Support */}
            <div className="animate-fade-in" style={{ animationDelay: '400ms' }}>
              <h4 className="text-lg font-semibold mb-6 text-white">Support</h4>
              <ul className="space-y-3">
                {footerLinks.support.map((link) => (
                  <li key={link.name}>
                    <Link
                      href={link.href}
                      className="text-neutral-400 hover:text-white text-sm transition-all duration-300 hover:translate-x-1 inline-block"
                    >
                      {link.name}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-neutral-800/50">
          <div className="container-modern py-6">
            <div className="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
              <p className="text-neutral-400 text-sm">
                © {currentYear} SkySlope. All rights reserved. Made with ❤️ for travelers.
              </p>

              {/* Newsletter Signup */}
              <div className="flex items-center space-x-4">
                <span className="text-neutral-400 text-sm">Stay updated:</span>
                <div className="flex items-center space-x-2">
                  <input
                    type="email"
                    placeholder="Enter email"
                    className="px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-white placeholder-neutral-400 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                  <button className="modern-btn modern-btn-primary px-4 py-2 text-sm">
                    Subscribe
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;