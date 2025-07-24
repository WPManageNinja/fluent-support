<?php

namespace FluentSupport\App\Services\Integrations\FluentCart;
use FluentCart\App\Models\Order;

class FluentCart
{
    public function boot()
    {
        add_filter('fluent_support/customer_extra_widgets', array($this, 'getFluentCartPurchaseWidgets'), 120, 2);
    }

    public function getFluentCartPurchaseWidgets($widgets, $customer)
    {
        $wpUserId = $customer->user_id;

        $formattedOrders = Order::whereHas('customer', function($query) use ($wpUserId) {
            $query->where('user_id', $wpUserId);
        })
            ->with([
                'shipping_address',
                'billing_address',
                'order_items'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $widgets['fct_purchases'] = [
            'title' => __('Fluent Cart Purchases', 'fluent-support'),
            'orders'  => $formattedOrders,
        ];
        return $widgets;
    }
}
