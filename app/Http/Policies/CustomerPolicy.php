<?php

namespace FluentSupport\App\Http\Policies;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Foundation\Policy;

/**
 * Policy for /customers: reads keep the module boundary, deletes are gated below.
 */
class CustomerPolicy extends Policy
{
    /**
     * Check user permission for any method
     * @param  \FluentSupport\Framework\Http\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return PermissionManager::currentUserCan('fst_sensitive_data');
    }

    /**
     * Deleting a single customer.
     */
    public function delete(Request $request)
    {
        return $this->guardCustomerDeletion($request);
    }

    /**
     * Deleting multiple customers in one request.
     */
    public function bulkDelete(Request $request)
    {
        return $this->guardCustomerDeletion($request);
    }

    /**
     * Deletion cascades into tickets, conversations and attachments.
     */
    protected function guardCustomerDeletion(Request $request)
    {
        // The router runs EITHER the per-route method OR verifyRequest(), never both
        // (FoundationTrait.php:120-122), so re-apply the module boundary here.
        // The exception code is carried through as the HTTP status by
        // Route::permissionCallback(), which falls back to 403 when the code is 0.
        // Pass it explicitly so an unauthenticated caller gets the canonical 401.
        if (!$this->verifyRequest($request)) {
            throw new \Exception(
                esc_html__('You do not have permission to manage customers.', 'fluent-support'),
                is_user_logged_in() ? 403 : 401
            );
        }

        // By default this also needs manage_options, a capability the plugin cannot mint.
        // Sites can drop that requirement with the filter below; the boundary above still
        // applies either way. Throwing gives a specific 403.
        $requiresAdmin = apply_filters('fluent_support/customer_delete_requires_admin', true);

        if ($requiresAdmin && !current_user_can('manage_options')) {
            // Only reachable by a logged-in user who already cleared the module
            // boundary above, so 403 is always the right status here.
            throw new \Exception(
                esc_html__('Only administrators can delete customers.', 'fluent-support'),
                403
            );
        }

        return true;
    }
}
