<?php

namespace FluentSupport\App\Services\Integrations\FluentCart;
use FluentCart\App\Models\Order;
use FluentCart\App\Helpers\CurrenciesHelper;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\App\Models\Customer;

class FluentCart
{
    public function boot()
    {
        add_filter('fluent_support/customer_extra_widgets', array($this, 'getFluentCartPurchaseWidgets'), 120, 2);
        add_filter('fluent_support/customer_extra_widgets', array($this, 'getFluentCartProLicenseWidget'), 125, 2);
        add_action('fluent_cart/order_created', [$this, 'addCustomer'], 10, 1);
    }

    public function getFluentCartPurchaseWidgets($widgets, $customer)
    {
        $wpUserId = $customer->user_id;

        $orders = Order::whereHas('customer', function ($query) use ($wpUserId) {
            $query->where('user_id', $wpUserId);
        })
            ->select(['id', 'status', 'created_at', 'total_amount', 'currency'])
            ->with([
                'shipping_address',
                'billing_address',
                'order_items'
            ])
            ->orderByDesc('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return $widgets;
        }

        $formattedOrders = $orders->map(function ($order) {
            $orderData = [
                'id' => $order->id,
                'status' => $order->status,
                'date' => $order->created_at->format('Y-m-d H:i:s'),
                'currency' => CurrenciesHelper::getCurrencySign($order->currency),
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'order_items' => $order->order_items
            ];

            if (!empty($order->total_amount)) {
                // Format total as float with 2 decimal places and standardize field name like WooCommerce
                $orderData['total'] = number_format($order->total_amount / 100, 2, '.', '');
            }

            return $orderData;
        });

        $widgets['fct_purchases'] = [
            'title'  => __('Fluent Cart Purchases', 'fluent-support'),
            'orders' => $formattedOrders->toArray(),
        ];

        return $widgets;
    }

    public function getFluentCartProLicenseWidget($widgets, $customer)
    {
        // Add customer's product licenses if available
        $licenses = $this->getCustomerProductLicenses($customer);

        if ($licenses) {
            $widgets['fct_license'] = [
                'header' => __('Fluent Cart Product Licenses', 'fluent-support'),
                'licenses' => $licenses
            ];
        }

        return $widgets;
    }

    public function addCustomer($param)
    {
        $fluentCartCustomer = Arr::get($param, 'customer');

        if (empty($fluentCartCustomer['email'])) {
            return;
        }

        $customerData = [
            'email' => $fluentCartCustomer['email'],
            'first_name' => $fluentCartCustomer['first_name'] ?? '',
            'last_name' => $fluentCartCustomer['last_name'] ?? '',
            'status' => 'active'
        ];

        // Add user_id if available
        if (!empty($fluentCartCustomer['user_id'])) {
            $customerData['user_id'] = $fluentCartCustomer['user_id'];
        }

        // Address field mappings for efficient processing
        $addressMappings = [
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'postcode' => 'zip'
        ];

        // Process primary address fields
        foreach ($addressMappings as $source => $target) {
            if (!empty($fluentCartCustomer[$source])) {
                $customerData[$target] = $fluentCartCustomer[$source];
            }
        }

        // Use billing address as fallback and add address lines
        $billingAddress = $fluentCartCustomer['primary_billing_address'] ?? [];
        if (!empty($billingAddress)) {
            foreach ($addressMappings as $source => $target) {
                if (empty($customerData[$target]) && !empty($billingAddress[$source])) {
                    $customerData[$target] = $billingAddress[$source];
                }
            }

            foreach (['address_1' => 'address_line_1', 'address_2' => 'address_line_2'] as $source => $target) {
                if (!empty($billingAddress[$source])) {
                    $customerData[$target] = $billingAddress[$source];
                }
            }
        }

        Customer::maybeCreateCustomer($customerData);
    }

    /**
     * Get customer's product licenses from FluentCart Pro
     *
     * @param Customer $customer
     * @return array|null Structured license data or null if not available
     */
    private function getCustomerProductLicenses($customer)
    {
        // Check if FluentCart Pro License model is available
        if (!class_exists('\FluentCartPro\App\Modules\Licensing\Models\License')) {
            return null;
        }

        try {
            $wpUserId = $customer->user_id;
            if (!$wpUserId) {
                return null;
            }

            // Get FluentCart customer ID
            $fluentCartCustomer = \FluentCart\App\Models\Customer::where('user_id', $wpUserId)->first();
            if (!$fluentCartCustomer) {
                return null;
            }

            // Get licenses for this customer
            $licenses = \FluentCartPro\App\Modules\Licensing\Models\License::where('customer_id', $fluentCartCustomer->id)
                ->with(['product'])
                ->orderByDesc('created_at')
                ->get()
                ->toArray();

            if (empty($licenses)) {
                return null;
            }

            $formattedLicenses = [];

            foreach ($licenses as $license) {
                // Get product name from the data structure
                $productName = 'Unknown Product';
                if (isset($license['product']['post_title'])) {
                    $productName = $license['product']['post_title'];
                }



                $status = $license['status'];
                $expirationDate = $license['expiration_date'];

                // Format expiration date for tooltip
                $expirationTooltip = '';
                if ($expirationDate) {
                    $expirationTooltip = 'Expires: ' . date('M d, Y', strtotime($expirationDate));
                } else {
                    $expirationTooltip = 'Expires: Lifetime';
                }

                $formattedLicenses[] = [
                    'id' => $license['id'],
                    'product_name' => $productName,
                    'status' => $status,
                    'expiration_tooltip' => $expirationTooltip,
                    'expiration_date' => $expirationDate
                ];
            }

            return $formattedLicenses;

        } catch (\Exception $e) {
            return null;
        }
    }

}
