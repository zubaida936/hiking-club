import { useQuery } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { ActivityIndicator } from 'react-native';

import { apiFetch } from '@/api/client';
import type { BookingList } from '@/api/types';
import { BookingCard } from '@/components/booking-card';
import { Button } from '@/components/form';
import { SavedCopyNote } from '@/components/saved-copy-note';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/** The query behind "My upcoming hikes"; the account screen refreshes it on pull-down. */
export const upcomingBookingsKey = ['my', 'bookings', 'upcoming'];

/** The member's upcoming bookings and waitlist places, soonest first (GET /me/bookings). */
export function MyUpcomingBookings() {
  const { data, error, isPending, refetch, dataUpdatedAt } = useQuery({
    queryKey: upcomingBookingsKey,
    queryFn: () => apiFetch<BookingList>('/me/bookings'),
  });

  return (
    <>
      <ThemedText type="subtitle">{strings.myBookings.upcoming}</ThemedText>
      <SavedCopyNote error={error} updatedAt={dataUpdatedAt} />
      {isPending ? (
        <ActivityIndicator />
      ) : error && !data ? (
        <>
          <ThemedText>{error.message}</ThemedText>
          <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
        </>
      ) : data.data.length === 0 ? (
        <ThemedText themeColor="textSecondary">{strings.myBookings.upcomingEmpty}</ThemedText>
      ) : (
        data.data.map((booking) => <BookingCard key={booking.token} booking={booking} />)
      )}
      <Link href="/my-hikes">
        <ThemedText type="linkPrimary">{strings.myBookings.completedLink}</ThemedText>
      </Link>
    </>
  );
}
