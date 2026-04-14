import WebsiteLayout from '@/layouts/WebsiteLayout'
import { Head } from '@inertiajs/react'
import React from 'react'
import HeroSection from '@/components/HeroSection'
import FeaturesSection from '@/components/FeaturesSection'
import AboutSection from '@/components/AboutSection'
import ContactSection from '@/components/ContactSection'

const home = () => {
 return (
   <WebsiteLayout>
       <Head>
         <title>SkySlope - Premium Travel Experiences</title>
         <meta name="description" content="Discover extraordinary journeys with SkySlope. From mountain adventures to cultural explorations, we create unforgettable travel experiences tailored just for you." />
         <meta name="keywords" content="travel, tours, adventures, vacations, destinations, skyslope" />
       </Head>
       <>
         <HeroSection />
         <FeaturesSection />
         <AboutSection />
         <ContactSection />
       </>
   </WebsiteLayout>
 )
}

export default home
