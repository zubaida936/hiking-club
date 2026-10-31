import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { CheckoutForm, Order } from '@/api/types';
import { Button, CheckboxField, ChoiceField, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ErrorState, LoadingState } from '@/components/states';
import { formatMoney } from '@/format';
import { useCart } from '@/shop/cart';
import { useCartCheck } from '@/shop/use-cart-check';
import { strings } from '@/strings';

/** Checkout (GET /shop/checkout-form, POST /shop/orders): the website's fields and rules, plus the phone's cart. */
export default function CheckoutScreen() {
  const { data: form, error, isPending, refetch } = useQuery({
    queryKey: ['shop', 'checkout-form'],
    queryFn: () => apiFetch<{ data: CheckoutForm }>('/shop/checkout-form').then((answer) => answer.data),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => void refetch()} />;
  }

  return <CheckoutFields form={form} />;
}

function CheckoutFields({ form }: { form: CheckoutForm }) {
  const queryClient = useQueryClient();
  const { items, clear } = useCart();
  const { data: check } = useCartCheck();
  const [fields, setFields] = useState(() => ({
    name: form.member?.name ?? '',
    phone: form.member?.phone ?? '',
    email: form.member?.email ?? '',
    fulfilment: 'pickup',
    delivery_city: '',
    delivery_address: '',
    notes: '',
    payment_method: null as string | null,
    terms: false,
  }));
  const set = <K extends keyof typeof fields>(key: K) => (value: (typeof fields)[K]) => setFields((current) => ({ ...current, [key]: value }));

  const option = form.fulfilment.find((choice) => choice.key === fields.fulfilment);
  const totalCents = (check?.subtotal.cents ?? 0) + (option?.fee.cents ?? 0);
  const total = formatMoney(totalCents);

  const place = useMutation({
    mutationFn: () =>
      apiFetch<{ data: Order }>('/shop/orders', {
        method: 'POST',
        body: {
          ...fields,
          email: fields.email || null,
          notes: fields.notes || null,
          delivery_city: option?.needs_address ? fields.delivery_city : null,
          delivery_address: option?.needs_address ? fields.delivery_address : null,
          items,
        },
      }),
    onSuccess: async ({ data: order }) => {
      clear();
      // Stock changed, and the member has a new order.
      await queryClient.invalidateQueries({ queryKey: ['shop'] });
      await queryClient.invalidateQueries({ queryKey: ['my'] });
      router.dismissAll();
      router.push(`/orders/${order.token}`);
    },
  });
  const errors = fieldErrors(place.error);

  return (
    <ScreenScroll>
      <ThemedText type="subtitle">{strings.checkout.you}</ThemedText>
      <FormField label={strings.checkout.name} value={fields.name} onChangeText={set('name')} autoComplete="name" error={errors.name} />
      <FormField label={strings.checkout.phone} value={fields.phone} onChangeText={set('phone')} keyboardType="phone-pad" autoComplete="tel" placeholder={strings.login.phonePlaceholder} error={errors.phone} />
      <FormField label={strings.checkout.email} value={fields.email} onChangeText={set('email')} keyboardType="email-address" autoCapitalize="none" autoComplete="email" error={errors.email} />

      <ChoiceField
        label={strings.checkout.fulfilment}
        choices={form.fulfilment.map((choice) => ({ value: choice.key, label: `${choice.label} · ${choice.fee.formatted}`, hint: choice.note }))}
        value={fields.fulfilment}
        onChange={set('fulfilment')}
        error={errors.fulfilment}
      />
      {option?.needs_address ? (
        <>
          <FormField label={strings.checkout.city} value={fields.delivery_city} onChangeText={set('delivery_city')} autoComplete="postal-address-locality" error={errors.delivery_city} />
          <FormField label={strings.checkout.address} value={fields.delivery_address} onChangeText={set('delivery_address')} autoComplete="street-address" multiline error={errors.delivery_address} />
        </>
      ) : null}
      <FormField label={strings.checkout.notes} value={fields.notes} onChangeText={set('notes')} multiline maxLength={500} error={errors.notes} />

      {form.payment_methods.length > 0 ? (
        <ChoiceField
          label={strings.checkout.payment}
          choices={form.payment_methods.map((method) => ({ value: method.key, label: method.label }))}
          value={fields.payment_method}
          onChange={set('payment_method')}
          error={errors.payment_method}
        />
      ) : null}
      <CheckboxField label={form.terms} checked={fields.terms} onChange={set('terms')} error={errors.terms} />

      <View style={styles.total}>
        <ThemedText type="subtitle">{strings.checkout.total}</ThemedText>
        <ThemedText type="subtitle">{total}</ThemedText>
      </View>
      {/* Rules on the items (sold out meanwhile, too many) come back as a message here. */}
      <FormError message={formMessage(place.error) ?? errors.items?.[0] ?? null} />
      <Button title={strings.checkout.submit(total)} onPress={() => place.mutate()} loading={place.isPending} />
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  total: { flexDirection: 'row', justifyContent: 'space-between' },
});
