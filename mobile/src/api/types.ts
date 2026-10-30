import type { components, operations } from './schema';

/** The JSON body of an operation's successful answer (200 or 201), from the generated API types. */
export type ResponseOf<K extends keyof operations> = operations[K] extends {
  responses: { 200: { content: { 'application/json': infer R } } };
}
  ? R
  : operations[K] extends { responses: { 201: { content: { 'application/json': infer R } } } }
    ? R
    : never;

export type Event = components['schemas']['EventResource'];
export type EventList = ResponseOf<'v1.events.index'>;
export type EventDetail = components['schemas']['EventDetailResource'];
export type BookingForm = ResponseOf<'v1.events.booking-form'>['data'];
export type Booking = components['schemas']['BookingResource'];
export type BookingList = ResponseOf<'v1.me.bookings'>;
export type Product = components['schemas']['ProductResource'];
export type Category = ResponseOf<'v1.shop.categories'>['data'][number];
export type CartCheck = ResponseOf<'v1.shop.cart'>['data'];
export type CheckoutForm = ResponseOf<'v1.shop.checkout-form'>['data'];
export type Order = components['schemas']['OrderResource'];
export type OrderList = ResponseOf<'v1.me.orders'>;
export type PointLine = components['schemas']['PointTransactionResource'];
export type PointList = ResponseOf<'v1.me.points'>;
export type RewardState = ResponseOf<'v1.me.rewards'>['data'][number];
export type AppConfig = ResponseOf<'v1.app-config'>['data'];
export type Home = ResponseOf<'v1.home'>['data'];
export type Slide = Home['slides'][number];
export type Member = components['schemas']['MemberResource'];
/** Login, activation and password reset all answer with a new token and the member. */
export type LoginResult = ResponseOf<'v1.auth.login'>;
export type XEvents = ResponseOf<'v1.x-events.index'>['data'];
export type XApplicationForm = ResponseOf<'v1.x-events.application-form'>['data'];
export type XApplicationResult = ResponseOf<'v1.x-events.apply'>['data'];
export type OrganizerHike = ResponseOf<'v1.organizer.events'>['data'][number];
export type CheckIn = components['schemas']['CheckInResource'];
export type DifficultyLevels = ResponseOf<'v1.difficulty.levels'>['data'];
export type DifficultyResult = ResponseOf<'v1.difficulty.calculate'>['data'];
export type PerkState = ResponseOf<'v1.organizer.perks.show'>['data'];
export type RankSystem = ResponseOf<'v1.rank-system'>['data'];
export type LeaderboardList = ResponseOf<'v1.leaderboard'>;
export type LeaderboardRow = LeaderboardList['data'][number];
