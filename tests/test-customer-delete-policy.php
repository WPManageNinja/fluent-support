<?php
/**
 * Customer deletion must not be reachable with the sensitive-data read grant.
 *
 * An agent holding only fst_sensitive_data (a read grant, labelled
 * "Access Private Data (Customers, Agents)" in the settings UI) must not be
 * able to delete customers. Customer deletion cascades into every ticket the
 * customer owns, plus the conversations and attachments on those tickets.
 *
 * @package Fluent_Support
 */

use FluentSupport\App\App;
use FluentSupport\App\Http\Policies\CustomerPolicy;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\Framework\Http\Request\Request;

class CustomerDeletePolicyTest extends WP_UnitTestCase
{
    /**
     * @var CustomerPolicy
     */
    protected $policy;

    public function set_up()
    {
        parent::set_up();
        $this->policy = new CustomerPolicy();
    }

    /**
     * Build a subscriber-level user carrying only the sensitive-data permission.
     *
     * @return int
     */
    protected function makeSensitiveDataAgent()
    {
        $userId = self::factory()->user->create(['role' => 'subscriber']);

        PermissionManager::attachPermissions($userId, ['fst_sensitive_data']);

        return $userId;
    }

    /**
     * Switch the current user.
     *
     * PermissionManager::currentUserPermissions() memoises its result in a
     * static, which is fine for a single WordPress request but sticky inside a
     * test process. Re-resolve it so each test sees its own actor.
     *
     * @param int $userId
     * @return void
     */
    protected function loginAs($userId)
    {
        wp_set_current_user($userId);

        PermissionManager::currentUserPermissions(false);
    }

    /**
     * @return Request
     */
    protected function makeRequest()
    {
        return App::make('request');
    }

    public function test_sensitive_data_agent_can_still_read_customers()
    {
        $this->loginAs($this->makeSensitiveDataAgent());

        $this->assertTrue(
            $this->policy->verifyRequest($this->makeRequest()),
            'The control leg must keep working: fst_sensitive_data still grants read access.'
        );
    }

    public function test_sensitive_data_agent_cannot_delete_a_customer()
    {
        $this->loginAs($this->makeSensitiveDataAgent());

        $this->expectException(\Exception::class);

        $this->policy->delete($this->makeRequest());
    }

    public function test_sensitive_data_agent_cannot_bulk_delete_customers()
    {
        $this->loginAs($this->makeSensitiveDataAgent());

        $this->expectException(\Exception::class);

        $this->policy->bulkDelete($this->makeRequest());
    }

    /**
     * The agent holds the module boundary, so the first check passes and the
     * administrator check is what stops them. Pinning the message proves the
     * request travelled through both layers rather than short-circuiting.
     */
    public function test_sensitive_data_agent_is_stopped_by_the_administrator_check()
    {
        $this->loginAs($this->makeSensitiveDataAgent());

        $this->expectExceptionMessage('Only administrators can delete customers.');

        $this->policy->delete($this->makeRequest());
    }

    public function test_agent_with_no_permission_cannot_delete()
    {
        $this->loginAs(self::factory()->user->create(['role' => 'subscriber']));

        $this->expectException(\Exception::class);

        $this->policy->delete($this->makeRequest());
    }

    /**
     * A user without fst_sensitive_data never reaches the administrator check:
     * deletion is a strict superset of read access, so the module boundary is
     * evaluated first.
     */
    public function test_user_without_sensitive_data_is_stopped_by_the_module_boundary()
    {
        $this->loginAs(self::factory()->user->create(['role' => 'subscriber']));

        $this->expectExceptionMessage('You do not have permission to manage customers.');

        $this->policy->bulkDelete($this->makeRequest());
    }

    public function test_administrator_can_delete_customers()
    {
        $this->loginAs(self::factory()->user->create(['role' => 'administrator']));

        $this->assertTrue($this->policy->delete($this->makeRequest()));
        $this->assertTrue($this->policy->bulkDelete($this->makeRequest()));
    }

    /**
     * Opting out of the administrator requirement must open BOTH delete routes.
     */
    public function test_filter_lets_a_sensitive_data_agent_delete_without_admin_rights()
    {
        $this->loginAs($this->makeSensitiveDataAgent());

        add_filter('fluent_support/customer_delete_requires_admin', '__return_false');

        try {
            $this->assertTrue($this->policy->delete($this->makeRequest()));
            $this->assertTrue($this->policy->bulkDelete($this->makeRequest()));
        } finally {
            remove_filter('fluent_support/customer_delete_requires_admin', '__return_false');
        }
    }

    /**
     * The filter drops only the administrator check, never the module boundary.
     */
    public function test_filter_does_not_open_deletion_to_users_without_sensitive_data()
    {
        $this->loginAs(self::factory()->user->create(['role' => 'subscriber']));

        add_filter('fluent_support/customer_delete_requires_admin', '__return_false');

        try {
            $this->expectExceptionMessage('You do not have permission to manage customers.');

            $this->policy->delete($this->makeRequest());
        } finally {
            remove_filter('fluent_support/customer_delete_requires_admin', '__return_false');
        }
    }

    /**
     * The agent-groups routes share AdminSensitivePolicy and also expose a
     * controller method named delete(). Guarding customers must not silently
     * change that boundary, which is why the customer guard lives on its own
     * policy class instead of on AdminSensitivePolicy.
     */
    public function test_agent_group_delete_boundary_is_unchanged()
    {
        $this->loginAs($this->makeSensitiveDataAgent());

        $adminSensitive = new \FluentSupport\App\Http\Policies\AdminSensitivePolicy();

        $this->assertTrue($adminSensitive->verifyRequest($this->makeRequest()));
        $this->assertFalse(method_exists($adminSensitive, 'delete'));
    }
}
