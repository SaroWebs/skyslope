import WebsiteLayout from '@/layouts/WebsiteLayout'
import { Head } from '@inertiajs/react'
import React from 'react'
import ContactSection from '@/components/ContactSection'

type Props = {}

const contact = (props: Props) => {
  return (
    <WebsiteLayout page='contact'>
        <Head>
          <title>Contact Us - SkySlope</title>
          <meta name="description" content="Get in touch with SkySlope for inquiries, bookings, or support. We're here to help you plan your perfect travel experience." />
          <meta name="keywords" content="contact skyslope, travel support, booking inquiries, travel assistance" />
        </Head>
        <div className="py-16">
          <ContactSection />
        </div>
    </WebsiteLayout>
  )
}

export default contact