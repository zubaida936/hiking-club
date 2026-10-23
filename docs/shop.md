# Shop

Hiking gear and club apparel sold on the site: `/shop` → product page → cart → checkout → order page.
Staff manage products and orders in the CMS (**Shop products**, **Shop orders**; permission `shop.manage`:
Super Admin and Admin).

## How it works

- **Products** have a category, price, optional sale price (shown struck through, with a "Sale" badge), tags
  (shown as chips), photos (the first is the card picture; resized to max 1600 px JPEG) and one or more **sizes**
  (variants) with their own stock. Products without sizes keep a single "One size" row; the product page then
  shows no size picker.
- **Stock** is per size. Cards say "In stock", "Only N left" (≤ `shop.low_stock`, 5) or "Out of stock"; sold-out
  sizes are crossed out. Stock is taken when the order is placed (rows locked, so the last item is never sold
  twice) and put back when an order is cancelled. Nothing is reserved while it sits in a cart.
- **Cart** lives in the session (`shop.cart`, variant id ⇒ quantity, max 10 per line). The header cart icon shows
  the item count. Prices are always read again from the products: the browser never sends a price.
- **Checkout**: name, phone (links the order to the hiker with that phone, like bookings), optional email,
  **Pickup** (free, at any upcoming hike) or **Delivery** (city + address, flat fee `SHOP_DELIVERY_FEE_CENTS`,
  default $4), notes, payment method, terms. The customer lands on the order page `/orders/{secret}`; the link is
  also in the confirmation email.
- **Payment** reuses the event payment methods (`config/payments.php`): Whish / OMT transfer (account shown +
  transaction-number box), cash (on pickup / to the driver), or **online** through the payment gateway (locally
  the test gateway, see [payments.md](payments.md)). An online payment marks the order paid automatically; staff
  click **Mark paid** for the others.
- **Order steps** (CMS → Shop orders → an order): Received → Being prepared → Ready for pickup / Out for delivery →
  Completed. Steps can be skipped forward, never back. The customer is emailed when the order is ready and when it
  is cancelled (with the reason; "Email the customer" can be unticked).
- **Cancelling** puts the items back in stock. A paid order then shows **Refund due**: the money is returned by
  hand (Whish/OMT/cash), there is no refund button.
- **Emails** (Mailpit locally): "Order received" to the customer (if they gave an email), "New shop order" to
  `SHOP_NOTIFY_EMAIL` (defaults to `SITE_EMAIL`), "Ready / cancelled" to the customer.
- Deleting a product is a soft delete: it disappears from the shop, past orders keep the name and size they were
  sold with. A category can only be deleted once it has no products.

## Testing it locally

1. `docker compose exec app php artisan migrate` and, for sample products, `docker compose exec app php artisan db:seed --class=DemoShopSeeder`
   (also part of `migrate:fresh --seed`). The demo has a low-stock product, a sold-out size and a fully sold-out jacket.
2. `docker compose restart queue`. An old worker does not know the new routes and the order emails fail
   ("Route [orders.show] not defined"); they are retried after the restart.
3. http://localhost:8080/shop → a product → Add to cart → cart icon → Checkout.
4. Pick **Online payment** → "Pay $N now" → test payment page → Pay → back on the order page, paid.
   (Needs `PAYMENT_ONLINE_GATEWAY=test` in `.env`.)
5. CMS → Shop orders: move the order through its steps, Mark paid, Cancel (stock goes back).

## Config

| Setting | Where | Default |
|---|---|---|
| Delivery fee | `SHOP_DELIVERY_FEE_CENTS` | 400 ($4) |
| "New order" email address | `SHOP_NOTIFY_EMAIL` | `SITE_EMAIL` |
| Pickup / delivery texts | `config/shop.php` → `pickup_note`, `delivery_note` | placeholders |
| Low-stock threshold, max per line | `config/shop.php` → `low_stock`, `max_quantity` | 5, 10 |
