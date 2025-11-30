import WebsiteLayout from '@/layoutes/WebsiteLayout'
import { Head } from '@inertiajs/react'
import React from 'react'
import AboutSection from '@/components/AboutSection'

type Props = {}

const about = (props: Props) => {
  return (
    <WebsiteLayout page='about'>
        <Head>
          <title>About Us - SkySlope</title>
          <meta name="description" content="Learn more about SkySlope's mission to provide premium travel experiences. Discover our story, values, and commitment to creating unforgettable journeys." />
          <meta name="keywords" content="about skyslope, travel company, mission, values, travel experts" />
        </Head>
        <div className="py-16">
          <AboutSection />
        </div>
    </WebsiteLayout>
  )
}

export default about