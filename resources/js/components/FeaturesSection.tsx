import React from 'react';
import { Link } from '@inertiajs/react';

const FeaturesSection = () => {
  const features = [
    {
      title: 'Mountain Adventures',
      description: 'Conquer majestic peaks and experience breathtaking alpine landscapes with our expert mountain guides.',
      image: '/storage/place_media/place4.jpg',
      link: '/',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      ),
    },
    {
      title: 'Cultural Expeditions',
      description: 'Immerse yourself in rich traditions and connect with local communities around the world.',
      image: '/storage/place_media/JG3gWm3J767nqfnEvs2dJjUKTxfQbXYJhDGRyEg4.jpg',
      link: '/',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      ),
    },
    {
      title: 'Beach Getaways',
      description: 'Relax on pristine beaches and discover hidden coastal gems with personalized itineraries.',
      image: '/storage/place_media/place2.jpg',
      link: '/tours/beach',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      ),
    },
    {
      title: 'Wildlife Safaris',
      description: 'Witness incredible wildlife in their natural habitats with conservation-focused expeditions.',
      image: '/storage/place_media/place3.jpg',
      link: '/tours/wildlife',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      ),
    },
  ];

  return (
    <section className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-16">
          <h2 className="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
            Discover Amazing Destinations
          </h2>
          <p className="text-lg text-gray-600 max-w-3xl mx-auto">
            From towering mountains to pristine beaches, cultural heartlands to wildlife sanctuaries -
            we offer curated experiences that create lifelong memories.
          </p>
        </div>

        {/* Features Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {features.map((feature, index) => (
            <div key={index} className="group">
              <Link href={feature.link} className="block">
                <div className="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
                  {/* Image Placeholder */}
                  <div className="relative overflow-hidden">
                    <img src={feature.image} alt="" />
                  </div>

                  {/* Content */}
                  <div className="p-6">
                    <h3 className="text-xl font-semibold text-gray-900 mb-3 group-hover:text-[var(--color-primary)] transition-colors duration-200">
                      {feature.title}
                    </h3>
                    <p className="text-gray-600 text-sm leading-relaxed mb-4">
                      {feature.description}
                    </p>
                    <div className="flex items-center text-blue-600 font-medium text-sm group-hover:translate-x-2 transition-transform duration-200">
                      Learn More
                      <svg className="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </div>
                </div>
              </Link>
            </div>
          ))}
        </div>

        {/* CTA Section */}
        <div className="text-center mt-16">
          <div className="bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-accent)] rounded-2xl p-8 lg:p-12">
            <h3 className="text-2xl sm:text-3xl font-bold mb-4 bg-gradient-to-r from-orange-400 to-red-200 bg-clip-text text-transparent">
              Ready for Your Next Adventure?
            </h3>
            <p className="text-lg text-orange-100 mb-8 max-w-2xl mx-auto">
              Join thousands of travelers who have discovered their perfect getaway with SkySlope.
              Let's make your dream vacation a reality.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                href="/tours"
                className="bg-[var(--color-secondary)] hover:bg-[var(--color-secondary-dark)] text-white px-8 py-3 rounded-lg font-semibold transition-colors duration-200"
              >
                View All Tours
              </Link>
              <Link
                href="/contact"
                className="border-2 border-[var(--color-primary)] text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-3 rounded-lg font-semibold transition-all duration-200"
              >
                Get Custom Quote
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default FeaturesSection;