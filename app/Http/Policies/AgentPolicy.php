<?php

namespace FluentSupport\App\Http\Policies;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Foundation\Policy;

class AgentPolicy extends Policy
{
    /**
     * Check user permission for any method
     * @param  \FluentSupport\Framework\Http\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        // Read access (index) and avatar routes keep the existing boundary.
        return PermissionManager::currentUserCan('fst_sensitive_data');
    }

    public function addAgent(Request $request)
    {
        return $this->guardManageOptions();
    }

    public function updateAgent(Request $request)
    {
        return $this->guardManageOptions();
    }

    public function deleteAgent(Request $request)
    {
        return $this->guardManageOptions();
    }

    /**
     * Agent records carry the plugin's permission set, so mutating them is a
     * privilege-granting operation. Gate on a WordPress capability the plugin's
     * own permission system cannot mint. Throwing (not returning
     * false) surfaces a specific message instead of WordPress core's generic
     * "Sorry, you are not allowed to do that." The exception code is carried
     * through as the HTTP status by Route::permissionCallback(), so anonymous
     * callers get the canonical 401 rather than 403.
     *
     * @return Boolean
     * @throws \Exception
     */
    protected function guardManageOptions()
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        if (!is_user_logged_in()) {
            throw new \Exception(
                esc_html__('You must be logged in to perform this action.', 'fluent-support'),
                401
            );
        }

        throw new \Exception(
            esc_html__('Only administrators can add, edit, or delete support staff.', 'fluent-support'),
            403
        );
    }
}
