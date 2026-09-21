<?php

namespace FluentSupport\App\Models\Traits;

use Exception;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\App\Services\ProfileInfoService;

trait CustomerTrait
{
    /**
     * Identity columns that must never be mass-assigned on update.
     *
     * On create these are forced by the model's `creating` hook, but `update()`
     * bypasses that hook, so they have to be filtered out here instead.
     *
     * @var array
     */
    private static $immutableUpdateKeys = [
        'hash',        // portal identity token
        'user_id',     // WordPress user linkage
        'person_type', // customer/agent record type, enforces the global scope
    ];

    /**
     * Whether this customer is barred from support altogether.
     *
     * Status is read as an allow list rather than a test for 'inactive'. The
     * codebase previously mixed `!== 'active'` and `== 'inactive'` across eight
     * call sites, so a status added later would have blocked some paths and
     * silently passed others. With an allow list, any new status blocks
     * everywhere until it is explicitly permitted.
     *
     * @return bool
     */
    public function isBlocked()
    {
        // Some imported rows carry an empty status. The column default is
        // 'active', so read empty the same way instead of locking them out.
        $status = $this->status ?: 'active';

        return $status !== 'active';
    }

    /**
     * Records that no WordPress account is linked to.
     *
     * Asked in several places -- adopting records at registration, finding the
     * records an address change stranded -- so the definition lives here rather
     * than being spelled out at each call site. Unlinked reads as NULL or 0:
     * the column is nullable, but data assembled from an integration can carry
     * a zero, and a record that answered "unlinked" in one place and "linked"
     * in another is how identity bugs start.
     *
     * @param \FluentSupport\Framework\Database\Orm\Builder $query
     * @return \FluentSupport\Framework\Database\Orm\Builder
     */
    public function scopeUnclaimed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('user_id')->orWhere('user_id', 0);
        });
    }

    /**
     * Records that belong to a WordPress account.
     *
     * @param \FluentSupport\Framework\Database\Orm\Builder $query
     * @return \FluentSupport\Framework\Database\Orm\Builder
     */
    public function scopeClaimed($query)
    {
        return $query->whereNotNull('user_id')->where('user_id', '!=', 0);
    }

    /**
     * Whether this customer may use the customer portal.
     *
     * @return bool
     */
    public function canAccessPortal()
    {
        return !$this->isBlocked();
    }

    /**
     * Whether Fluent Support may send this customer a notification.
     *
     * Deliberately a separate question from portal access even though both
     * currently reduce to the same status check. Reaching the portal is about
     * who is asking; sending mail is about whether the address on file is one
     * we should be writing to. An email verification policy would change this
     * one without touching the other.
     *
     * @return bool
     */
    public function canReceiveNotifications()
    {
        return !$this->isBlocked();
    }

    /**
     * This getCustomers method will return all customers
     * @param string $search
     * @param string $status
     * @return object
     */
    public function getCustomers($search, $status=false)
    {
        $customersQuery = static::latest();

        if ( $search ) {
            $customersQuery = $customersQuery->searchBy($search);
        }

        if ($status && $status != 'all') {
            $customersQuery->filterByStatues([$status]);
        }

        $customers = $customersQuery->paginate();

        if ( defined( 'FLUENTCRM' ) && $customers->isEmpty() && !$status ) {
            $customers = $this->findInCrmAndMakeCustomer ( $search );
            ! is_null( $customers ) ? $customers : $customers = $customersQuery->paginate();
        }

        if ( defined('FLUENT_SUPPORT_PRO_DIR_FILE') && $customers->isEmpty() && !$status ) {
            $customers = $this->findUserInWPAndMakeCustomer( $search );
            ! is_null( $customers ) ? $customers : $customers = $customersQuery->paginate();
        }

        return $this->attachCustomersMetaData( $customers );
    }

    public function getCustomerField($customer_id,$userID = null)
    {
        $basicFields = [
            'first_name' => [
                'label' => __('First Name', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('First Name', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field required',
            ],
            'last_name' => [
                'label' => __('Last Name', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('Last Name', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'email' => [
                'label' => __('Email', 'fluent-support'),
                'data_type' => 'email',
                'placeholder' => __('Email', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field required',
                'disabled' => !empty($customer_id),
            ],
            'title' => [
                'label' => __('Job Title', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('Job Title', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'note' => [
                'label' => __('Note', 'fluent-support'),
                'data_type' => 'textarea',
                'placeholder' => __('Note', 'fluent-support'),
                'type' => 'input-text',
            ],
            'status' => [
                'label' => __('Status', 'fluent-support'),
                'data_type' => 'text',
                'type' => 'input-radio',
                'options' => [
                    'active' => ['id' => 'active', 'value' => 'active', 'label' => __('Active', 'fluent-support')],
                    'inactive' => ['id' => 'inactive', 'value' => 'inactive', 'label' => __('Blocked', 'fluent-support')],
                ],
            ],
            'status_html' => [
                'dependency' => [
                    'depends_on' => 'status',
                    'operator' => '=',
                    'value' => 'inactive',
                ],
                'type' => 'html-viewer',
                'wrapper_class' => 'fs_warn_alert_wrapper',
                'html' => __('If you select the <b>Blocked</b> status, this customer will not be able to submit a ticket or any response.', 'fluent-support'),
            ],
        ];

        $addressFields = [
            'address_line_1' => [
                'label' => __('Address Line 1', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('Address Line 1', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'address_line_2' => [
                'label' => __('Address Line 2', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('Address Line 2', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'city' => [
                'label' => __('City', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('City', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'state' => [
                'label' => __('State', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('State', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'zip' => [
                'label' => __('Zip Code', 'fluent-support'),
                'data_type' => 'text',
                'placeholder' => __('Zip Code', 'fluent-support'),
                'type' => 'input-text',
                'wrapper_class' => 'fs_half_field',
            ],
            'country' => [
                'label' => __('Country', 'fluent-support'),
                'placeholder' => __('Country', 'fluent-support'),
                'type' => 'country-selector',
                'wrapper_class' => 'fs_half_field',
            ],
        ];

        if ($userID) {
            $addressFields =  apply_filters('fluent_support/custom_registration_form_fields',$addressFields);

            foreach ($addressFields as $key => $field) {
                if (isset($field['type']) && $field['type'] === 'country-selector') {
                    continue; // Skip conversion for country-selector
                }

                //To create a custom field using _formBuilder.vue, the 'type' should be 'input-text', and the 'data_type' may vary, such as (text, number, date...).
                $addressFields[$key]['data_type'] = $addressFields[$key]['type'];
                $addressFields[$key]['type'] = 'input-text';
            }
        }

        $loading = false;

        $state = [
            'basic_fields' => $basicFields,
            'address_fields' => $addressFields,
            'loading' => $loading,
        ];

        return $state;

    }

    /**
     * This `findUserInWPAndMakeCustomer` method will find customer in WP Users and make it as a customer
     * @param string $search
     * @return object
     */
    public function findUserInWPAndMakeCustomer ( $search )
    {
        if ( is_email( $search ) ){
            $user = get_user_by( 'email', $search );

            if( $user ) {
                unset( $user->user_pass );
                $customerData = [
                    'user_id' => $user->ID,
                    'email' => $user->user_email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                ];
                $customer = static::maybeCreateCustomer( $customerData );
                return static::where('email', $user->user_email)->paginate();
            }
        }
    }

    /**
     * This `findInCrmAndMakeCustomer` method will find customer in Fluent CRM and make it as a customer
     * @param string $search
     * @return object
     */
    public function findInCrmAndMakeCustomer ( $search )
    {
        $customer = \FluentCrm\App\Models\Subscriber::where( 'email', $search )->first();
        if( $customer ) {
            $customerData = [
                'email' => $customer->email,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name
            ];
            $customer = static::maybeCreateCustomer( $customerData );
            return static::where('email', $customer->email)->paginate();
        }
    }

    /**
     * This getCustomer method will return a single customer
     * @param int $id
     * @param array $with
     * @return object
     */

    public function getCustomer($id, $with)
    {
        $customer = static::find($id);

        $data = [
            'customer' => $customer
        ];

        if (!$with) {
            return $data;
        }

        return $this->getCustomerAdditionalData($with, $customer, $data);
    }

    /**
     * This createCustomer method will create a new customer
     * @param array $data
     * @return object
     */

    public function createCustomer($data)
    {
        $data = Arr::only($data, $this->getFillable());

        $user = get_user_by('email', $data['email']);

        if ($user) {
            $data['user_id'] = $user->ID;
            if (empty($data['first_name'])) {
                $data['first_name'] = $user->first_name;
            }
            if (empty($data['last_name'])) {
                $data['last_name'] = $user->last_name;
            }
        }

        return static::create($data);
    }

    /**
     * This updateCustomer method will update a customer
     * @since 1.5.7
     * @param int $customerId
     * @param array $data
     * @return object
     */

    public function updateCustomer($customerId, $data)
    {
        $customFieldsKeys = apply_filters('fluent_support/custom_registration_form_fields_key', Helper::getBusinessSettings('custom_registration_form_field'));
        $customFieldsKeys = is_array($customFieldsKeys) ? $customFieldsKeys : [];
        $customFormValue = Arr::only($data, $customFieldsKeys);

        $data = $this->takeValidKeysForUpdate($data);

        if (isset($data['last_response_at']) && empty($data['last_response_at'])) {
            unset($data['last_response_at']);
        }

        $customer = static::findOrFail($customerId);

        if($this->customerExists($customerId, $data['email']))
        {
            throw new \Exception('Another Customer has same email address');
        }

        $user = get_user_by('email', $data['email']);

        if ($user && !empty($customFieldsKeys)) {
            // Never trust the submitted email to pick the account: an email edit
            // must not silently re-bind this record to whoever owns that address.
            $linkedUserId = $this->resolveUserLinkage($customer, (int) $user->ID);

            if ($linkedUserId) {
                $data['user_id'] = $linkedUserId;

                //Update Custom field data for user
                foreach ($customFieldsKeys as $key) {
                    if (isset($customFormValue[$key])) {
                        $fieldValue = $customFormValue[$key];
                        update_user_meta($linkedUserId, $key, $fieldValue);
                    }
                }
            }
        }

        $previousEmail = (string) $customer->email;

        static::where('id', $customer->id)->update($data);

        $updated = static::findOrFail($customerId);

        // An agent moving the address has the same consequence as any other
        // proven move, and this was the one path that skipped it. Signed ticket
        // links already delivered to the previous inbox keep authorising read,
        // reply, close and reopen until the hash they carry stops matching, so
        // correcting a customer's address left whoever holds the old inbox with
        // working access to every one of their tickets.
        //
        // The update above is a query-builder write, which fires no model
        // events, so this cannot be left to Ticket::updating either.
        if (strtolower(trim($previousEmail)) !== strtolower(trim((string) $updated->email))) {
            ProfileInfoService::onProvenEmailChange($updated, $previousEmail, 'agent');
        }

        return $updated;
    }

    /**
     * deleteCustomer method will delete a customer and all tickets by that customer
     * @since 1.5.7
     * @param int $customerId
     * @return array
     */

    public function deleteCustomer($customerId)
    {
        $customer = static::findOrFail($customerId);

        $tickets = Ticket::where('customer_id', $customer->id)->get();

        foreach ($tickets as $ticket) {
            $ticket->deleteTicket();
        }

        $customer->delete();

        return [
            'message' => __('Customer Deleted Successfully', 'fluent-support')
        ];
    }

    /**
     * bulkDeleteCustomers method will delete multiple customers and all their tickets
     * @since 1.9.3
     * @param array $customerIds
     * @return array
     */
    public function bulkDeleteCustomers($customerIds)
    {
        if (empty($customerIds) || !is_array($customerIds)) {
            return [
                'message' => __('No customers selected for deletion', 'fluent-support')
            ];
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($customerIds as $customerId) {
            try {
                $customer = static::findOrFail($customerId);

                $tickets = Ticket::where('customer_id', $customer->id)->get();

                foreach ($tickets as $ticket) {
                    $ticket->deleteTicket();
                }

                $customer->delete();
                $deletedCount++;

            } catch (\Exception $e) {
                /* translators: %1$d: customer ID, %2$s: error message */
                $errors[] = sprintf(__('Failed to delete customer ID %1$d: %2$s', 'fluent-support'), $customerId, $e->getMessage());
            }
        }

        if ($deletedCount > 0) {
            /* translators: %1$d: number of customers deleted */
            $message = $deletedCount === 1
                ? __('Customer deleted successfully', 'fluent-support')
                : sprintf(__('%1$d customers deleted successfully', 'fluent-support'), $deletedCount);
        } else {
            $message = __('No customers were deleted', 'fluent-support');
        }

        $response = [
            'message' => $message,
            'deleted_count' => $deletedCount,
            'total_requested' => count($customerIds)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * This attachCustomersMetaData method will attach meta data to customers
     * @since 1.5.7
     * @param object $customers
     * @return object
     */
    private function attachCustomersMetaData($customers)
    {
        foreach ($customers as $customer) {
            $customer->total_tickets = $customer->getTicketCounts();
            $customer->total_responses = $customer->getResponseCounts();
            if ($customer->user_id) {
                //Get profile link, if they are WP user
                $customer->user_profile = admin_url('user-edit.php?user_id=' . $customer->user_id);
            }
        }

        return $customers;
    }

    /**
     * This getCustomerAdditionalData method will return additional data for a customer
     * @since 1.5.7
     * @param array $with
     * @param object $customer
     * @param array $data
     * @return array
     */

    private function getCustomerAdditionalData($with, $customer, $data)
    {
        $customFieldKeys = apply_filters('fluent_support/custom_registration_form_fields_key', []);

        $userMetaData = get_user_meta($customer['user_id']);

        //Custom field data from wp user_meta
        foreach ($customFieldKeys as $fieldKey) {
            if (isset($userMetaData[$fieldKey][0])) {
                $customer[$fieldKey] = $userMetaData[$fieldKey][0];
            }
        }

        if (in_array('widgets', $with)) {
            $data['widgets'] = ProfileInfoService::getProfileExtraWidgets($customer);
        }

        if (in_array('tickets', $with)) {
            /*
             * Filter ticket limit to show ticket in customer page sidebar
             * @since 1.5.6
             * @param int $limit
             */
            $limit = apply_filters('fluent_support/customer_page_ticket_widgets_limit', 20);

            $data['tickets'] = Ticket::select(['id', 'title', 'status', 'customer_id', 'created_at'])
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->limit($limit)
                ->get();
        }

        if(in_array('fluentcrm_profile', $with)) {
            $data['fluentcrm_profile'] = Helper::getFluentCrmContactData($customer);
        }

        return $data;
    }

    /**
     * This customerExists method will check if customer exists
     * @since 1.5.7
     * @param int $customerId
     * @param string $email
     * @return mixed
     */

    private function customerExists($customerId, $email)
    {
        $customer = static::where('id','!=', $customerId)->where('email', $email)->first();
        if ($customer) {
            return $customer;
        }
        return false;
    }


    /**
     * Decide which WordPress account this customer record may be linked to.
     *
     * Binding a support record to a WP account is an identity change: the portal
     * resolves the logged-in customer by `user_id` (see Helper::getCurrentCustomer),
     * so a re-bind hands this record's tickets to the newly bound account. It is
     * therefore restricted to administrators and refused when the account already
     * belongs to another customer. When a re-bind is not allowed the existing
     * linkage is kept, so ordinary email corrections still go through.
     *
     * @param object $customer
     * @param int $targetUserId WP user matching the submitted email
     * @return int user id to link, or 0 for none
     */
    private function resolveUserLinkage($customer, $targetUserId)
    {
        $currentUserId = (int) $customer->user_id;

        // Already linked to this account, nothing to authorize.
        if ($targetUserId === $currentUserId) {
            return $currentUserId;
        }

        if (!current_user_can('manage_options')) {
            return $currentUserId;
        }

        // Conflict: that account is already bound to a different customer record.
        $alreadyLinked = static::where('id', '!=', $customer->id)
            ->where('user_id', $targetUserId)
            ->first();

        if ($alreadyLinked) {
            return $currentUserId;
        }

        return $targetUserId;
    }

    /**
     * This takeValidKeysForUpdate method will take valid keys data for update
     * @since 1.5.7
     * @param array $data
     * @return array
     */
    private function takeValidKeysForUpdate($data)
    {
        // $fillable is a numerically indexed list, so the immutable columns are
        // values rather than keys and have to be removed with array_diff().
        $validKeys = array_diff($this->getFillable(), self::$immutableUpdateKeys);

        return Arr::only($data, $validKeys);
    }
}
