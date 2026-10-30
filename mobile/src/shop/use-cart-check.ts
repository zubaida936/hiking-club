import { keepPreviousData, useQuery } from '@tanstack/react-query';

import { apiFetch } from '@/api/client';
import type { CartCheck } from '@/api/types';
import { useCart } from '@/shop/cart';

/**
 * The cart priced by the server (POST /shop/cart): current prices, stock per line, items no longer on sale.
 * Asked again whenever the cart changes; the previous answer stays on screen meanwhile.
 */
export function useCartCheck() {
  const { items } = useCart();

  return useQuery({
    queryKey: ['shop', 'cart-check', items],
    queryFn: () => apiFetch<{ data: CartCheck }>('/shop/cart', { method: 'POST', body: { items } }).then((answer) => answer.data),
    enabled: items.length > 0,
    placeholderData: keepPreviousData,
  });
}
