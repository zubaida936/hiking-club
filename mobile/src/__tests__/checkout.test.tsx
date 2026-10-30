import AsyncStorage from '@react-native-async-storage/async-storage';
import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { CartCheck } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleMember, sampleOrder } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

beforeEach(async () => {
  await AsyncStorage.clear();
  jest.clearAllMocks();
});

const ok = (data: unknown, status = 200) => ({ status, body: { data } });
const line = (overrides: Partial<CartCheck['lines'][number]> = {}): CartCheck['lines'][number] => ({
  variant_id: 52,
  variant_name: 'M',
  product: { slug: 'club-t-shirt', name: 'Club T-shirt', image: null },
  quantity: 2,
  stock: 3,
  in_stock: true,
  unit_price: { cents: 1500, formatted: '$15' },
  line_total: { cents: 3000, formatted: '$30' },
  ...overrides,
});
const priced = (overrides: Partial<CartCheck> = {}) => ok({ lines: [line()], unavailable: [], subtotal: { cents: 3000, formatted: '$30' }, can_checkout: true, ...overrides });
const checkoutForm = ok({
  fulfilment: [
    { key: 'pickup', label: 'Pickup at a hike', fee: { cents: 0, formatted: 'Free' }, note: 'Collect at a hike.', needs_address: false },
    { key: 'delivery', label: 'Delivery', fee: { cents: 400, formatted: '$4' }, note: 'Delivery in 3–5 working days.', needs_address: true },
  ],
  payment_methods: [{ key: 'omt', label: 'OMT Pay transfer' }, { key: 'cash', label: 'Cash on delivery / at pickup' }],
  terms: 'I agree that the club contacts me about this order.',
  max_quantity: 10,
  member: null,
});

async function withCart() {
  await AsyncStorage.setItem('hiking.cart', JSON.stringify([{ variant_id: 52, quantity: 2 }]));
}

it('changes quantities, warns about stock and removes items no longer for sale', async () => {
  await withCart();
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'POST /shop/cart': priced({ lines: [line({ stock: 1, in_stock: false })], unavailable: [99], can_checkout: false }),
  });
  await renderRouter('./src/app', { initialUrl: '/cart' });

  expect(await screen.findByText('Only 1 left: lower the quantity.')).toBeOnTheScreen();
  expect(screen.getByText('Some items are no longer in the shop and were removed from your cart.')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Remove Club T-shirt' }));
  expect(await screen.findByText('Your cart is empty.')).toBeOnTheScreen();
});

it('places a delivery order and shows it with the transfer instructions', async () => {
  await withCart();
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'POST /shop/cart': priced(),
    'GET /shop/checkout-form': checkoutForm,
    'POST /shop/orders': ok(sampleOrder(), 201),
    'GET /orders/secret-order-token': ok(sampleOrder()),
  });
  await renderRouter('./src/app', { initialUrl: '/cart' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Checkout' }));
  await fireEvent.changeText(await screen.findByLabelText('Full name'), 'Karim Saad');
  await fireEvent.changeText(screen.getByLabelText('Phone (WhatsApp)'), '+961 71 222 333');
  await fireEvent.press(screen.getByRole('radio', { name: 'Delivery · $4' }));
  await fireEvent.changeText(screen.getByLabelText('City'), 'Beirut');
  await fireEvent.changeText(screen.getByLabelText('Delivery address'), 'Hamra Street 12');
  await fireEvent.press(screen.getByRole('radio', { name: 'OMT Pay transfer' }));
  await fireEvent.press(screen.getByRole('checkbox', { name: 'I agree that the club contacts me about this order.' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Place order · $34' }));

  expect(await screen.findByText('ORD12345')).toBeOnTheScreen();
  expect(screen).toHavePathname('/orders/secret-order-token');
  expect(screen.getByText('Received')).toBeOnTheScreen();
  expect(screen.getByText('2 × Club T-shirt (M)')).toBeOnTheScreen();
  expect(screen.getByText('$34')).toBeOnTheScreen();
  expect(screen.getByText('Open OMT Pay and send $34 to:')).toBeOnTheScreen();
  expect(calls.find((c) => c.method === 'POST' && c.path === '/shop/orders')?.body).toEqual({
    name: 'Karim Saad',
    phone: '+961 71 222 333',
    email: null,
    fulfilment: 'delivery',
    delivery_city: 'Beirut',
    delivery_address: 'Hamra Street 12',
    notes: null,
    payment_method: 'omt',
    terms: true,
    items: [{ variant_id: 52, quantity: 2 }],
  });
  // The cart is emptied once the order is placed.
  expect(JSON.parse((await AsyncStorage.getItem('hiking.cart')) ?? '[]')).toEqual([]);
});

it('shows the API messages on the checkout form', async () => {
  await withCart();
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'POST /shop/cart': priced(),
    'GET /shop/checkout-form': checkoutForm,
    'POST /shop/orders': { status: 422, body: { message: 'Please accept the terms to place your order.', errors: { terms: ['Please accept the terms to place your order.'] } } },
  });
  await renderRouter('./src/app', { initialUrl: '/checkout' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Place order · $30' }));

  expect(await screen.findByText('Please accept the terms to place your order.')).toBeOnTheScreen();
  expect(JSON.parse((await AsyncStorage.getItem('hiking.cart')) ?? '[]')).toHaveLength(1);
});

it('lists my orders on the account tab', async () => {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue('1|secret');
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /me': ok(sampleMember),
    'GET /me/bookings': { status: 200, body: { data: [], links: {}, meta: { current_page: 1, last_page: 1, total: 0 } } },
    'GET /me/orders': { status: 200, body: { data: [sampleOrder()], links: {}, meta: { current_page: 1, last_page: 1, total: 1 } } },
  });
  await renderRouter('./src/app', { initialUrl: '/my-orders' });

  expect(await screen.findByText('ORD12345')).toBeOnTheScreen();
  expect(screen.getByText('Club T-shirt (2)')).toBeOnTheScreen();
  expect(screen.getByText('$34')).toBeOnTheScreen();
});
