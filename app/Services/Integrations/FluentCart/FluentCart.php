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

        $orders = Order::whereHas('customer', function($query) use ($wpUserId) {
            $query->where('user_id', $wpUserId);
        })
            ->with(['shipping_address', 'billing_address', 'order_items'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedOrders = $orders->map(function($order) {
            $orderArray = $order->toArray();
            $orderArray['currency'] = CurrenciesHelper::getCurrencySign($order->currency);
            return $orderArray;
        })->toArray();

        $widgets['fct_purchases'] = [
            'title' => __('Fluent Cart Purchases', 'fluent-support'),
            'orders'  => $formattedOrders,
        ];
        return $widgets;
    }
}
