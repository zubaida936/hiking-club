import AsyncStorage from '@react-native-async-storage/async-storage';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

/**
 * The shop cart, kept on the phone (the website's lives in the browser session): which variant (size) and how
 * many. Prices and stock always come from the server (POST /shop/cart), never from here. Saved in AsyncStorage
 * so it survives closing the app; not secret, unlike the login token.
 */
export type CartItem = { variant_id: number; quantity: number };

type CartState = {
  items: CartItem[];
  /** Number of pieces, for the cart badge. */
  count: number;
  add: (variantId: number, quantity: number) => void;
  setQuantity: (variantId: number, quantity: number) => void;
  remove: (variantId: number) => void;
  clear: () => void;
};

const KEY = 'hiking.cart';

/** Most pieces of one size in an order (config shop.max_quantity on the server, which checks it again). */
export const MAX_QUANTITY = 10;

const CartContext = createContext<CartState | null>(null);

export function CartProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<CartItem[]>([]);
  const [loaded, setLoaded] = useState(false);

  // Read the saved cart once; ignore the answer if the app closed in the meantime.
  useEffect(() => {
    let active = true;
    AsyncStorage.getItem(KEY)
      .then((saved) => {
        if (active && saved) setItems(JSON.parse(saved) as CartItem[]);
      })
      .catch(() => undefined)
      .finally(() => active && setLoaded(true));
    return () => {
      active = false;
    };
  }, []);

  // Save every change (after the saved cart was read, so it is never overwritten by the empty start).
  useEffect(() => {
    if (loaded) void AsyncStorage.setItem(KEY, JSON.stringify(items)).catch(() => undefined);
  }, [items, loaded]);

  const setQuantity = useCallback((variantId: number, quantity: number) => {
    setItems((current) => {
      const capped = Math.min(Math.max(0, quantity), MAX_QUANTITY);
      const others = current.filter((item) => item.variant_id !== variantId);
      if (capped === 0) return others;
      return current.some((item) => item.variant_id === variantId)
        ? current.map((item) => (item.variant_id === variantId ? { ...item, quantity: capped } : item))
        : [...others, { variant_id: variantId, quantity: capped }];
    });
  }, []);

  const add = useCallback(
    (variantId: number, quantity: number) =>
      setItems((current) => {
        const existing = current.find((item) => item.variant_id === variantId)?.quantity ?? 0;
        const capped = Math.min(existing + quantity, MAX_QUANTITY);
        return existing > 0
          ? current.map((item) => (item.variant_id === variantId ? { ...item, quantity: capped } : item))
          : [...current, { variant_id: variantId, quantity: capped }];
      }),
    [],
  );

  const value = useMemo<CartState>(
    () => ({
      items,
      count: items.reduce((sum, item) => sum + item.quantity, 0),
      add,
      setQuantity,
      remove: (variantId) => setQuantity(variantId, 0),
      clear: () => setItems([]),
    }),
    [items, add, setQuantity],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartState {
  const cart = useContext(CartContext);
  if (!cart) throw new Error('useCart() must be used inside <CartProvider>.');

  return cart;
}
