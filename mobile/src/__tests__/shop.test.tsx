import AsyncStorage from '@react-native-async-storage/async-storage';
import { act, fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Alert } from 'react-native';

import { appConfigRoute, fakeApi, quietTabs, sampleProduct } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

beforeEach(async () => {
  await AsyncStorage.clear();
  jest.clearAllMocks();
});
afterEach(() => jest.restoreAllMocks());

const ok = (data: unknown) => ({ status: 200, body: { data } });

it('lists the products by category, with sale price and stock', async () => {
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /shop/categories': ok([{ slug: 'clothing', name: 'Clothing' }, { slug: 'footwear', name: 'Footwear' }]),
    'GET /shop/products': ok([sampleProduct(), sampleProduct({ id: 6, slug: 'trail-cap', name: 'Trail Cap', price: { cents: 1200, formatted: '$12' }, regular_price: null, stock: { total: 0, sold_out: true, low: false } })]),
  });
  await renderRouter('./src/app', { initialUrl: '/shop' });

  expect(await screen.findByText('Club T-shirt')).toBeOnTheScreen();
  expect(screen.getByText('$15')).toBeOnTheScreen();
  expect(screen.getByText('$20')).toBeOnTheScreen(); // regular price, struck through
  expect(screen.getByText('Only 3 left')).toBeOnTheScreen();
  expect(screen.getByText('Sold out')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Footwear' }));
  await screen.findByText('Club T-shirt');
  expect(calls.filter((c) => c.path === '/shop/products').at(-1)?.query).toEqual({ category: 'footwear' });
});

it('asks for a size, adds to the cart, and the cart is priced by the server', async () => {
  const alert = jest.spyOn(Alert, 'alert');
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /shop/products/club-t-shirt': ok(sampleProduct()),
    'POST /shop/cart': ok({
      lines: [{ variant_id: 52, variant_name: 'M', product: { slug: 'club-t-shirt', name: 'Club T-shirt', image: null }, quantity: 2, stock: 3, in_stock: true, unit_price: { cents: 1500, formatted: '$15' }, line_total: { cents: 3000, formatted: '$30' } }],
      unavailable: [],
      subtotal: { cents: 3000, formatted: '$30' },
      can_checkout: true,
    }),
  });
  await renderRouter('./src/app', { initialUrl: '/shop/club-t-shirt' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Add to cart' }));
  expect(screen.getByText('Choose a size.')).toBeOnTheScreen();
  // Sold-out sizes aren't offered.
  expect(screen.queryByRole('radio', { name: 'S' })).not.toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('radio', { name: 'M' }));
  await fireEvent.press(screen.getByRole('button', { name: 'One more' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Add to cart' }));

  expect(alert).toHaveBeenCalledWith('Added to your cart', 'Club T-shirt (M) × 2', expect.any(Array));
  await act(async () => (alert.mock.calls[0][2] ?? []).find((button) => button.text === 'Go to cart')?.onPress?.());

  expect(await screen.findByText('$15 · $30')).toBeOnTheScreen();
  expect(screen).toHavePathname('/cart');
  expect(calls.find((c) => c.path === '/shop/cart')?.body).toEqual({ items: [{ variant_id: 52, quantity: 2 }] });
  // Saved on the phone.
  expect(JSON.parse((await AsyncStorage.getItem('hiking.cart')) ?? '[]')).toEqual([{ variant_id: 52, quantity: 2 }]);
});

it('says when a product is no longer in the shop', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs });
  await renderRouter('./src/app', { initialUrl: '/shop/gone' });

  expect(await screen.findByText("This product isn't in the shop any more.")).toBeOnTheScreen();
});
