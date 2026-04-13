import WebsiteLayout from '@/layouts/WebsiteLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import React from 'react'
import { Tour, Itinerary } from '@/types'
import { motion } from 'framer-motion'
import { Parallax } from 'react-parallax'
import {
  Container,
  Title,
  Text,
  Badge,
  Button,
  Group,
  Stack,
  Card,
  Grid,
  Box,
  Image,
  Timeline,
  Divider,
  List,
  ThemeIcon
} from '@mantine/core'

type Props = {
  tour: Tour
}

const TourView = ({ tour }: Props) => {
  const { data, setData, post, processing, errors } = useForm({
    tour_id: tour.id.toString(),
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    start_date: '',
    number_of_people: '1',
  })


  const formatPrice = (price: number, discount?: number) => {
    if (discount && discount > 0) {
      const discountedPrice = price - (price * discount / 100)
      return {
        original: price,
        discounted: discountedPrice,
        discount: discount
      }
    }
    return {
      original: price,
      discounted: price,
      discount: 0
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }

  const priceInfo = formatPrice(tour.price, tour.discount)

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post(`/api/book-tour`, {
      onSuccess: () => {
        // Handle success
      }
    })
  }

  const groupItinerariesByDay = (itineraries: Itinerary[]) => {
    return itineraries.reduce((groups: { [key: number]: Itinerary[] }, itinerary) => {
      if (!groups[itinerary.day_index]) {
        groups[itinerary.day_index] = []
      }
      groups[itinerary.day_index].push(itinerary)
      return groups
    }, {})
  }

  const itineraryGroups = tour.itineraries ? groupItinerariesByDay(tour.itineraries) : {}

  return (
    <WebsiteLayout page='tours'>
      <Head>
        <title>{tour.title ? `${tour.title} - SkySlope` : 'Tour - SkySlope'}</title>
        <meta name="description" content={tour.description || 'View tour details and book your trip'} />
        <meta name="keywords" content={`tour, ${tour.title || 'travel'}, northeast india`} />
      </Head>

      <Container size="xl" py="xl">
        {/* Hero Section */}
        <Parallax bgImage={tour.image_path ? `/storage/${tour.image_path}` : undefined} strength={300}>
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <Card shadow="lg" radius="md" mb="xl" withBorder style={{ background: 'rgba(255, 255, 255, 0.9)' }}>
              <Grid gutter="xl">
                <Grid.Col span={{ base: 12, md: 6 }}>
                  <Box style={{
                    height: '300px',
                    background: tour.image_path
                      ? `url(/storage/${tour.image_path})`
                      : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    borderRadius: '8px',
                    position: 'relative'
                  }}>
                    {!tour.image_path && (
                      <Text size="xl" c="white" ta="center" style={{ paddingTop: '120px' }} fw={500}>
                        {tour.title}
                      </Text>
                    )}
                    {tour.discount && (
                      <motion.div
                        initial={{ scale: 0 }}
                        animate={{ scale: 1 }}
                        transition={{ delay: 0.5, type: 'spring', stiffness: 260, damping: 20 }}
                      >
                        <Badge
                          color="red"
                          size="lg"
                          style={{
                            position: 'absolute',
                            top: '16px',
                            right: '16px',
                            fontSize: '14px',
                            fontWeight: 600
                          }}
                        >
                          {tour.discount}% OFF
                        </Badge>
                      </motion.div>
                    )}
                  </Box>
                </Grid.Col>

                <Grid.Col span={{ base: 12, md: 6 }}>
                  <Stack gap="md">
                    <div>
                      <Title order={1} size="h1" c="dark.9">
                        {tour.title}
                      </Title>
                      <Text size="lg" c="dimmed" mt="sm">
                        {tour.description}
                      </Text>
                    </div>

                    <Group gap="md">
                      <Badge size="lg" color="blue" variant="light">
                        Available
                      </Badge>
                      <Badge size="lg" color="green" variant="light">
                        {Math.max(...Object.keys(itineraryGroups).map(Number))} Days
                      </Badge>
                    </Group>

                    <div>
                      <Group gap="xs" mb="xs">
                        <Text size="sm" c="dimmed">Available Period:</Text>
                      </Group>
                      <Text size="md" c="dark.9">
                        {formatDate(tour.available_from)} - {formatDate(tour.available_to)}
                      </Text>
                    </div>

                    <div>
                      <Group gap="xs" mb="xs">
                        <Text size="sm" c="dimmed">Price:</Text>
                      </Group>
                      <div className="flex items-center gap-2">
                        <span className="text-2xl font-bold text-orange-600">
                          ₹{priceInfo.discounted.toLocaleString()}
                        </span>
                        {priceInfo.discount > 0 && (
                          <>
                            <span className="text-lg text-gray-500 line-through">
                              ₹{priceInfo.original.toLocaleString()}
                            </span>
                            <Badge color="green" size="md">
                              {priceInfo.discount}% OFF
                            </Badge>
                          </>
                        )}
                      </div>
                      <Text size="sm" c="dimmed">per person</Text>
                    </div>
                  </Stack>
                </Grid.Col>
              </Grid>
            </Card>
          </motion.div>
        </Parallax>

        <Grid gutter="xl">
          {/* Itinerary Section */}
          <Grid.Col span={{ base: 12, md: 8 }}>
            <Card shadow="md" radius="md" withBorder>
              <Title order={2} size="h2" mb="md" c="dark.9">
                Tour Itinerary
              </Title>

              {tour.itineraries && tour.itineraries.length > 0 ? (
                <Timeline bulletSize={24} lineWidth={2}>
                  {Object.entries(itineraryGroups)
                    .sort(([a], [b]) => Number(a) - Number(b))
                    .map(([day, dayItineraries]) => (
                      <Timeline.Item
                        key={day}
                        bullet={<Badge color="blue" size="sm">{day}</Badge>}
                        title={`Day ${day}`}
                      >
                        <Stack gap="xs">
                          {dayItineraries
                            .sort((a, b) => a.time.localeCompare(b.time))
                            .map((itinerary) => (
                              <motion.div
                                key={itinerary.id}
                                initial={{ opacity: 0, x: -50 }}
                                animate={{ opacity: 1, x: 0 }}
                                transition={{ duration: 0.5 }}
                              >
                                <IteneraryCard itinerary={itinerary} />

                              </motion.div>
                            ))}
                        </Stack>
                      </Timeline.Item>
                    ))}
                </Timeline>
              ) : (
                <Text c="dimmed" ta="center" py="xl">
                  No itinerary details available for this tour.
                </Text>
              )}
            </Card>
          </Grid.Col>

          {/* Booking Section */}
          <Grid.Col span={{ base: 12, md: 4 }}>
            <motion.div
              initial={{ opacity: 0, y: 50 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
            >
              <Card shadow="md" radius="md" withBorder>
                <Title order={3} size="h3" mb="md" c="dark.9">
                  Book This Tour
                </Title>

                <form onSubmit={handleSubmit}>
                  <Stack gap="md">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Number of People *
                      </label>
                      <select
                        value={data.number_of_people}
                        onChange={(e) => setData('number_of_people', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        required
                      >
                        {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(num => (
                          <option key={num} value={num.toString()}>{num}</option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Preferred Start Date *
                      </label>
                      <input
                        type="date"
                        value={data.start_date}
                        onChange={(e) => setData('start_date', e.target.value)}
                        min={tour.available_from}
                        max={tour.available_to}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        required
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Full Name *
                      </label>
                      <input
                        type="text"
                        value={data.customer_name}
                        onChange={(e) => setData('customer_name', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        placeholder="Enter your full name"
                        required
                      />
                      {errors.customer_name && <p className="text-red-500 text-sm mt-1">{errors.customer_name}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Email Address *
                      </label>
                      <input
                        type="email"
                        value={data.customer_email}
                        onChange={(e) => setData('customer_email', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        placeholder="your.email@example.com"
                        required
                      />
                      {errors.customer_email && <p className="text-red-500 text-sm mt-1">{errors.customer_email}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number *
                      </label>
                      <input
                        type="tel"
                        value={data.customer_phone}
                        onChange={(e) => setData('customer_phone', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        placeholder="+91 XXXXX XXXXX"
                        required
                      />
                      {errors.customer_phone && <p className="text-red-500 text-sm mt-1">{errors.customer_phone}</p>}
                    </div>

                    <Divider />

                    <div className="bg-gray-50 p-4 rounded-lg">
                      <div className="flex justify-between items-center mb-2">
                        <span className="text-sm text-gray-600">Total Amount:</span>
                        <span className="text-lg font-bold text-orange-600">
                          ₹{(priceInfo.discounted * parseInt(data.number_of_people)).toLocaleString()}
                        </span>
                      </div>
                      <div className="text-xs text-gray-500">
                        ₹{priceInfo.discounted.toLocaleString()} × {data.number_of_people} person(s)
                      </div>
                    </div>

                    <Button
                      type="submit"
                      size="lg"
                      fullWidth
                      loading={processing}
                      disabled={processing}
                      style={{
                        background: 'linear-gradient(135deg, #f57c00 0%, #e65100 100%)',
                        '&:hover': {
                          background: 'linear-gradient(135deg, #e65100 0%, #bf360c 100%)',
                        }
                      }}
                    >
                      {processing ? 'Processing...' : 'Confirm Booking'}
                    </Button>
                  </Stack>
                </form>
              </Card>
            </motion.div>
          </Grid.Col>
        </Grid>
      </Container>
    </WebsiteLayout>
  )
}

export default TourView


const IteneraryCard = ({ itinerary }: { itinerary: Itinerary }) => {

  const checkItem=()=>{
    console.log(itinerary)
  }

  const showPlace=()=>{
    // show the place details in a modal
    console.log(itinerary.place)
  }

  return (
    <Card padding="sm" radius="sm" withBorder={false} bg="gray.0" onClick={checkItem}>
      <Group justify="space-between" align="flex-start">
        <div style={{ flex: 1 }}>
          <Group gap="xs" mb="xs">
            <Text size="sm" c="dimmed">{itinerary.time}</Text>
            {itinerary.place && (
              <Badge variant="light" color="cyan" size="sm" onClick={showPlace} className='cursor-pointer'>
                {itinerary.place.name}
              </Badge>
            )}
          </Group>
          <Text size="sm" c="dark.9" mb="xs">
            {itinerary.details}
          </Text>
          {itinerary.place && itinerary.place.media && itinerary.place.media.length > 0 && (
            <Box mt="sm">
              <Image
                src={`/storage/${itinerary.place.media[0].file_path}`}
                alt={itinerary.place.media[0].description || itinerary.place?.name || 'Place Image'}
                w={100}
                h={100}
                radius="md"
                fit="cover"
                style={{ border: '2px solid #e0e0e0' }}
              />
            </Box>
          )}
        </div>
      </Group>
    </Card>
  )
}