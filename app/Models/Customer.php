<?php

namespace FluentSupport\App\Models;

use FluentSupport\Framework\Database\Orm\Builder;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\App\Models\Traits\CustomerTrait;

class Customer extends Person
{
    use CustomerTrait;

    protected static $type = 'customer';

    protected $searchable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'address_line_1',
        'address_line_2',
        'country'
    ];

    /**
     * @return array
     */
    public function getSearchableFields()
    {
        return $this->searchable;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->person_type = static::$type;
            $model->hash = md5(time() . wp_generate_uuid4());
        });

        static::addGlobalScope(function (Builder $builder) {
            $builder->where('person_type', 'customer');
        });

    }

    public static function mappables()
    {
        return [
            'title'          => __('Customer Title', 'fluent-support'),
            'address_line_1' => __('Address Line 1', 'fluent-support'),
            'address_line_2' => __('Address Line 2', 'fluent-support'),
            'city'           => __('City', 'fluent-support'),
            'state'          => __('State', 'fluent-support'),
            'zip'            => __('Zip Code', 'fluent-support'),
            'country'        => __('Country', 'fluent-support'),
        ];
    }

    /**
     * maybeCreateCustomer method will update existing customer or create new
     * This method will get request to create customer, this will check existence, if exist it will update otherwise it will create new.
     * @param $customerData
     * @return false|Customer
     */
    public static function maybeCreateCustomer($customerData)
    {
        $customer = self::getCustomerFromData($customerData);
        $email = Arr::get($customerData, 'email');
        $user = $email ? get_user_by('email', $email) : false;
        if ($user) {
            if ($user->first_name) {
                $customerData['first_name'] = $user->first_name;
            }
            if ($user->last_name) {
                $customerData['last_name'] = $user->last_name;
            }
            if (empty($customerData['first_name']) && empty($customerData['last_name'])) {
                $customerData['first_name'] = $user->display_name;
            }
            $customerData['user_id'] = $user->ID;
        }

        if (!$customer) {
            if (!empty($customerData['last_ip_address'])) {
                $customerData['ip_address'] = $customerData['last_ip_address'];
            }

            $customerData = self::explodeFullName($customerData);

            // we have to create customer
            $customer = self::create($customerData);

            /*
             * Action on customer create
             *
             * @since v1.0.0
             * @param object $customer
             */
            do_action('fluent_support/customer_created', $customer);

            return $customer;
        }

        // An existing row may never be claimed for a WordPress account it is not
        // already linked to. Binding happens when the row is first created, when
        // the account registers (ProfileInfoService::onWPUserRegister), or when
        // an agent sets it explicitly from the customer profile. Allowing an
        // email match to write user_id here is what made a portal takeover
        // persistent.
        $incomingUserId = (int) Arr::get($customerData, 'user_id');

        if ($incomingUserId && $incomingUserId !== (int) $customer->user_id) {
            unset($customerData['user_id']);
        }

        // Intake may not move the contact address of a record that belongs to a
        // WordPress account. Every caller here builds its payload from an
        // address it has not verified -- most of them from the signed-in
        // account's own email, which WordPress lets that account change with no
        // confirmation at all.
        //
        // Without this, ProfileInfoService::onWPProfileUpdate holding the
        // address is worth nothing: it refuses the unverified change, and then
        // the next POST to /customer-portal/tickets resolves the same record
        // through here and writes the new address anyway. It also produced
        // duplicate rows on an address belonging to somebody else, without the
        // collision check the profile_update path applies.
        //
        // The address moves through the two paths that prove something instead:
        // EmailClaimService, where the customer opens a link sent to the address
        // being claimed, and an agent editing the record directly.
        if ((int) $customer->user_id) {
            unset($customerData['email']);
        }

        if (!empty($customerData['user_id']) || !empty($customerData['remote_uid'])) {
            $customerData = array_filter($customerData);
            $customer->fill($customerData);
            $customer->save();
        }

        return $customer;
    }


    /**
     * One2Many: Customer has to many Click Tickets
     * @return Model Collection
     */
    public function tickets()
    {
        $foreign_key = self::$type . '_id';

        $class = __NAMESPACE__ . '\Ticket';

        return $this->hasMany(
            $class, $foreign_key, 'id'
        );
    }

    /**
     * getCustomerFromData method will return customer information by user id or email address
     * @param $customerData
     * @return false
     */
    public static function getCustomerFromData($customerData)
    {
        $remoteUid = Arr::get($customerData, 'remote_uid');
        $email     = Arr::get($customerData, 'email');
        $userId    = (int) Arr::get($customerData, 'user_id');

        if ($remoteUid) {
            $customer = self::where('remote_uid', $remoteUid)->first();

            if ($customer) {
                return $customer;
            }
        }

        // A WordPress user id is the only identity that can be trusted here. An
        // account email is editable by its own holder with no verification: the
        // REST users endpoint calls wp_update_user() directly and skips the
        // confirmation flow that profile.php runs. An email must therefore never
        // be able to reach a customer row that belongs to somebody else.
        // Both lookups order by id so the row that wins is always the oldest
        // match rather than whatever the storage engine returns first. The email
        // column carries a plain index, not a unique one, so duplicates are
        // possible and an inbound reply must keep threading onto the original
        // record. Helper::getCurrentPerson() orders for the same reason.
        if ($userId) {
            return self::where('user_id', $userId)->orderBy('id', 'ASC')->first() ?: false;
        }

        if ($email) {
            return self::where('email', $email)->orderBy('id', 'ASC')->first() ?: false;
        }

        return false;
    }

    /**
     * getTicketCounts method will return the number of tickets by a customer
     * @return mixed
     */
    public function getTicketCounts()
    {
        return Ticket::where('customer_id', $this->id)->count();
    }

    /**
     * getResponseCounts will return the number of responses by a customer
     * @return mixed
     */
    public function getResponseCounts()
    {
        return Conversation::where('person_id', $this->id)->count();
    }
}
