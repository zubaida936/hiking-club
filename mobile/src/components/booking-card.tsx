import { Link } from 'expo-router';
import { Pressable } from 'react-native';

import type { Booking } from '@/api/types';
import { Card } from '@/components/card';
import { ThemedText } from '@/components/themed-text';
import { formatDay } from '@/format';
import { strings } from '@/strings';

/** What the member still has to do about paying, in one line (or nothing). */
function paymentLine(booking: Booking): string | null {
  switch (booking.payment.state) {
    case 'paid':
      return strings.myBookings.paid;
    case 'due':
      return strings.myBookings.toPay(booking.amount_due.formatted);
    case 'after_confirmation':
      return strings.myBookings.payLater;
    default:
      return null;
  }
}

/** One of my bookings; opens the booking screen (payment, WhatsApp, cancel). */
export function BookingCard({ booking }: { booking: Booking }) {
  const payment = paymentLine(booking);

  return (
    <Link href={`/bookings/${booking.token}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={`${booking.event.title}, ${formatDay(booking.event.starts_on)}`}>
        <Card>
          <ThemedText type="small" themeColor="textSecondary">
            {formatDay(booking.event.starts_on)} · {booking.status.label}
          </ThemedText>
          <ThemedText type="subtitle">{booking.event.title}</ThemedText>
          {booking.pickup?.point ? <ThemedText type="small">{strings.booking.pickup(booking.pickup.point, booking.pickup.time)}</ThemedText> : null}
          {booking.earned_points !== undefined ? (
            <ThemedText type="smallBold">{strings.myBookings.earned(booking.earned_points)}</ThemedText>
          ) : payment ? (
            <ThemedText type="smallBold">{payment}</ThemedText>
          ) : null}
        </Card>
      </Pressable>
    </Link>
  );
}
