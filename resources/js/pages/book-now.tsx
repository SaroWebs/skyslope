import WebsiteLayout from '@/layoutes/WebsiteLayout'
import { Head } from '@inertiajs/react'
import React from 'react'

type Props = {}

const BookNow = (props: Props) => {
  return (
    <WebsiteLayout page='book-now'>
        <Head>
          <title>Book Now - SkySlope</title>
          <meta name="description" content="Ready to start your adventure? Book your tour now and secure your spot on an unforgettable journey with SkySlope." />
          <meta name="keywords" content="book tour, reserve travel, book vacation, travel booking, tour reservation" />
        </Head>
        <div className="py-16">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12">
              <h1 className="text-4xl font-bold text-gray-900 mb-4">Book Your Adventure</h1>
              <p className="text-xl text-gray-600">
                Ready to turn your travel dreams into reality? Start your booking process now.
              </p>
            </div>

            <div className="bg-white rounded-lg shadow-lg p-8">
              <form className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label htmlFor="firstName" className="block text-sm font-medium text-gray-700 mb-2">
                      First Name *
                    </label>
                    <input
                      type="text"
                      id="firstName"
                      name="firstName"
                      required
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00695C] focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label htmlFor="lastName" className="block text-sm font-medium text-gray-700 mb-2">
                      Last Name *
                    </label>
                    <input
                      type="text"
                      id="lastName"
                      name="lastName"
                      required
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00695C] focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                    Email Address *
                  </label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00695C] focus:border-transparent"
                  />
                </div>

                <div>
                  <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                    Phone Number *
                  </label>
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00695C] focus:border-transparent"
                  />
                </div>

                <div>
                  <label htmlFor="tour" className="block text-sm font-medium text-gray-700 mb-2">
                    Preferred Tour/Destination
                  </label>
                  <select
                    id="tour"
                    name="tour"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00695C] focus:border-transparent"
                  >
                    <option value="">Select a tour...</option>
                    <option value="adventure">Adventure Tours</option>
                    <option value="cultural">Cultural Experiences</option>
                    <option value="luxury">Luxury Getaways</option>
                    <option value="mountains">Mountain Adventures</option>
                    <option value="coastal">Coastal Paradises</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="message" className="block text-sm font-medium text-gray-700 mb-2">
                    Special Requests or Questions
                  </label>
                  <textarea
                    id="message"
                    name="message"
                    rows={4}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00695C] focus:border-transparent"
                    placeholder="Tell us about your preferences, group size, dates, or any special requirements..."
                  ></textarea>
                </div>

                <div className="text-center">
                  <button
                    type="submit"
                    className="bg-[#F57C00] hover:bg-[#E65100] text-white px-8 py-3 rounded-md text-lg font-medium transition-colors duration-200"
                  >
                    Submit Booking Request
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
    </WebsiteLayout>
  )
}

export default BookNow