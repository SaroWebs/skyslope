import WebsiteLayout from '@/layoutes/WebsiteLayout'
import { Head, Link } from '@inertiajs/react'
import React, { useEffect } from 'react'
import { Place, PlaceMedia } from '@/types'
import { motion } from 'framer-motion'
import Slider from 'react-slick'
import { Parallax } from 'react-parallax'
import 'slick-carousel/slick/slick.css'
import 'slick-carousel/slick/slick-theme.css'
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
  Divider,
  AspectRatio
} from '@mantine/core'

type Props = {
  place: Place
}

const DestinationView = ({ place }: Props) => {
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'available':
        return 'green'
      case 'unavailable':
        return 'red'
      case 'restricted':
        return 'orange'
      default:
        return 'gray'
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }

  const sliderSettings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 3000,
  }

  useEffect(() => {
    if (place) {
      console.log(place);
    }
  }, [place])
  


  return (
    <WebsiteLayout page='destinations'>
      <Head>
        <title>{place.name ? `${place.name} - SkySlope` : 'Destination - SkySlope'}</title>
        <meta name="description" content={place.description || 'Explore this amazing destination'} />
        <meta name="keywords" content={`destination, ${place.name || 'travel'}, northeast india`} />
      </Head>

      <Container size="xl" py="xl">
        {/* Hero Section */}
        <Parallax bgImage={place.media && place.media.length > 0 ? `/storage/${place.media[0].file_path}` : undefined} strength={300}>
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <Card mb="xl" withBorder className='mx-4 my-3 shadow-lg rounded-md' style={{ backgroundColor: '#ffffffcc'}}>
              <Grid gutter="xl">
                <Grid.Col span={{ base: 12, md: 6 }}>
                  {place.media && place.media.length > 0 ? (
                    <AspectRatio ratio={16/9}>
                      <Image
                        src={`/storage/${place.media[0].file_path}`}
                        alt={place.media[0].description || place.name}
                        fit="cover"
                        radius="md"
                      />
                    </AspectRatio>
                  ) : (
                    <Box style={{
                      height: '300px',
                      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                      borderRadius: '8px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center'
                    }}>
                      <Text size="xl" c="white" fw={500}>
                        {place.name}
                      </Text>
                    </Box>
                  )}
                </Grid.Col>

            <Grid.Col span={{ base: 12, md: 6 }}>
              <Stack gap="md">
                <div>
                  <Group gap="xs" mb="xs">
                    <Title order={1} size="h1" c="dark.9">
                      {place.name}
                    </Title>
                    <Badge color={getStatusColor(place.status)} size="lg">
                      {place.status.charAt(0).toUpperCase() + place.status.slice(1)}
                    </Badge>
                  </Group>
                  <Text size="lg" c="dimmed" mt="sm">
                    {place.description}
                  </Text>
                </div>

                {place.lat && place.lng && typeof place.lat === 'number' && typeof place.lng === 'number' && (
                  <div>
                    <Group gap="xs" mb="xs">
                      <Text size="sm" c="dimmed">Location:</Text>
                    </Group>
                    <Text size="md" c="dark.9">
                      📍 {place.lat.toFixed(6)}, {place.lng.toFixed(6)}
                    </Text>
                  </div>
                )}

                <div>
                  <Group gap="xs" mb="xs">
                    <Text size="sm" c="dimmed">Added:</Text>
                  </Group>
                  <Text size="md" c="dark.9">
                    {formatDate(place.created_at)}
                  </Text>
                </div>

                {place.itineraries && place.itineraries.length > 0 && (
                  <div>
                    <Group gap="xs" mb="xs">
                      <Text size="sm" c="dimmed">Featured in tours:</Text>
                    </Group>
                    <Text size="md" c="dark.9">
                      {place.itineraries.length} tour{place.itineraries.length > 1 ? 's' : ''}
                    </Text>
                  </div>
                )}

                <Group gap="md" mt="lg">
                  <Button
                    size="lg"
                    style={{
                      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                      '&:hover': {
                        background: 'linear-gradient(135deg, #059669 0%, #047857 100%)',
                      }
                    }}
                  >
                    Plan Trip
                  </Button>
                  <Button variant="light" size="lg" component={Link} href="/destinations">
                    Back to Destinations
                  </Button>
                </Group>
              </Stack>
            </Grid.Col>
          </Grid>
        </Card>
          </motion.div>
        </Parallax>

        <Grid gutter="xl">
          {/* Media Gallery */}
          {place.media && place.media.length > 1 && (
            <Grid.Col span={12}>
              <motion.div
                initial={{ opacity: 0, y: 50 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 0.2 }}
              >
                <Card shadow="md" radius="md" withBorder>
                  <Title order={2} size="h2" mb="md" c="dark.9">
                    Photo Gallery
                  </Title>

                  <Slider {...sliderSettings}>
                    {place.media.map((media) => (
                      <div key={media.id}>
                        <AspectRatio ratio={16/9}>
                          <Image
                            src={`/storage/${media.file_path}`}
                            alt={media.description || place.name}
                            fit="cover"
                            radius="md"
                            style={{ cursor: 'pointer' }}
                          />
                        </AspectRatio>
                      </div>
                    ))}
                  </Slider>
                </Card>
              </motion.div>
            </Grid.Col>
          )}

          {/* Tours featuring this destination */}
          {place.itineraries && place.itineraries.length > 0 && (
            <Grid.Col span={{ base: 12, md: 6 }}>
              <motion.div
                initial={{ opacity: 0, x: -50 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.8, delay: 0.3 }}
              >
                <Card shadow="md" radius="md" withBorder>
                  <Title order={3} size="h3" mb="md" c="dark.9">
                    Tours Featuring This Destination
                  </Title>

                  <Stack gap="xs">
                    {place.itineraries && Array.from(new Set(place.itineraries.map(it => it.tour?.id).filter(Boolean)))
                      .slice(0, 5)
                      .map(tourId => {
                        const itinerary = place.itineraries?.find(it => it.tour?.id === tourId);
                        const tour = itinerary?.tour;
                        return tour ? (
                          <Card key={tour.id} padding="sm" radius="sm" withBorder={false} bg="gray.0">
                            <Group justify="space-between" align="flex-start">
                              <div style={{ flex: 1 }}>
                                <Text size="md" c="dark.9" fw={500} mb="xs">
                                  {tour.title}
                                </Text>
                                <Text size="sm" c="dimmed" lineClamp={2}>
                                  {tour.description}
                                </Text>
                                <Group gap="xs" mt="xs">
                                  <Badge size="sm" color="blue" variant="light">
                                    {place.itineraries && Math.max(...place.itineraries.filter(it => it.tour?.id === tour.id).map(it => it.day_index))} Days
                                  </Badge>
                                  <Text size="sm" c="dimmed">
                                    ₹{tour.price?.toLocaleString()} per person
                                  </Text>
                                </Group>
                              </div>
                            </Group>
                          </Card>
                        ) : null;
                      })}

                    {place.itineraries && Array.from(new Set(place.itineraries.map(it => it.tour?.id).filter(Boolean))).length > 5 && (
                      <Text size="sm" c="dimmed" ta="center" mt="xs">
                        +{Array.from(new Set(place.itineraries.map(it => it.tour?.id).filter(Boolean))).length - 5} more tours
                      </Text>
                    )}
                  </Stack>

                  <Button
                    variant="light"
                    fullWidth
                    mt="md"
                    component={Link}
                    href="/tours"
                  >
                    View All Tours
                  </Button>
                </Card>
              </motion.div>
            </Grid.Col>
          )}

          {/* Quick Info */}
          <Grid.Col span={{ base: 12, md: place.itineraries && place.itineraries.length > 0 ? 6 : 12 }}>
            <motion.div
              initial={{ opacity: 0, x: 50 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.4 }}
            >
              <Card shadow="md" radius="md" withBorder>
                <Title order={3} size="h3" mb="md" c="dark.9">
                  Quick Information
                </Title>

              <Stack gap="md">
                <div>
                  <Group gap="xs" mb="xs">
                    <Text size="sm" c="dimmed">Status:</Text>
                  </Group>
                  <Badge color={getStatusColor(place.status)} size="md">
                    {place.status.charAt(0).toUpperCase() + place.status.slice(1)}
                  </Badge>
                </div>

                <div>
                  <Group gap="xs" mb="xs">
                    <Text size="sm" c="dimmed">Coordinates:</Text>
                  </Group>
                  <Text size="sm" c="dark.9" style={{ fontFamily: 'monospace' }}>
                    {place.lat && place.lng && typeof place.lat === 'number' && typeof place.lng === 'number'
                      ? `${place.lat.toFixed(6)}, ${place.lng.toFixed(6)}`
                      : 'Not available'}
                  </Text>
                </div>

                <div>
                  <Group gap="xs" mb="xs">
                    <Text size="sm" c="dimmed">Added to collection:</Text>
                  </Group>
                  <Text size="sm" c="dark.9">
                    {formatDate(place.created_at)}
                  </Text>
                </div>

                <Divider />

                <div>
                  <Text size="sm" c="dimmed" mb="xs">
                    Ready to explore this destination?
                  </Text>
                  <Group gap="sm">
                    <Button
                      size="sm"
                      style={{
                        background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        '&:hover': {
                          background: 'linear-gradient(135deg, #059669 0%, #047857 100%)',
                        }
                      }}
                    >
                      Plan Visit
                    </Button>
                    <Button variant="light" size="sm" component={Link} href="/tours">
                      Find Tours
                    </Button>
                  </Group>
                </div>
              </Stack>
            </Card>
            </motion.div>
          </Grid.Col>
        </Grid>
      </Container>
    </WebsiteLayout>
  )
}

export default DestinationView