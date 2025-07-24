<?php

namespace FluentSupport\App\Services\Integrations\FluentCart;
use FluentCart\App\Models\Order;
use FluentCart\App\Helpers\CurrenciesHelper;

class FluentCart
{
    public function boot()
    {
        add_filter('fluent_support/customer_extra_widgets', array($this, 'getFluentCartPurchaseWidgets'), 120, 2);
    }

    public function getFluentCartPurchaseWidgets($widgets, $customer)
    {
        $wpUserId = $customer->user_id;

        $orders = Order::whereHas('customer', function ($query) use ($wpUserId) {
            $query->where('user_id', $wpUserId);
        })
            ->with(['shipping_address', 'billing_address', 'order_items'])
            ->orderByDesc('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return $widgets;
        }

        $formattedOrders = $orders->map(function ($order) {
            $orderData = $order->toArray();
            $orderData['currency'] = CurrenciesHelper::getCurrencySign($order->currency);

            if (!empty($orderData['total_amount'])) {
                // Format total_amount as float with 2 decimal places
                $orderData['total_amount'] = number_format($orderData['total_amount'] / 100, 2, '.', '');
            }

            return $orderData;
        });

        $widgets['fct_purchases'] = [
            'title'  => __('Fluent Cart Purchases', 'fluent-support'),
            'orders' => $formattedOrders->toArray(),
        ];

        return $widgets;
    }
}
