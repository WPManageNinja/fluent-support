<?php

namespace FluentSupport\App\Services\Tickets;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;

class AgentTicketAccess
{
    // Keyed by user id so wp_set_current_user() switches land on their own entry.
    private static $restrictedByUser = [];

    public function currentAgentCanAccess($ticket)
    {
        return $this->canAccess(Helper::getAgentByUserId(), $ticket);
    }

    public function canAccess($agent, $ticket, $restrictions = null)
    {
        if (!$agent || empty($agent->user_id) || !$ticket || empty($ticket->id)) {
            return false;
        }

        if ($this->isMailboxRestricted($agent, $ticket, $restrictions)) {
            return false;
        }

        $visibility = PermissionManager::getAgentTicketVisibility($agent->user_id);

        if ($visibility === PermissionManager::VISIBILITY_ALL) {
            return true;
        }

        if ((int) $ticket->agent_id === (int) $agent->id) {
            return true;
        }

        return !$ticket->agent_id && $visibility === PermissionManager::VISIBILITY_ASSIGNED_AND_UNASSIGNED;
    }

    public function applyAccessScope($query, $agent = null, $restrictions = null)
    {
        $agent = $agent ?: Helper::getAgentByUserId();

        if (!$agent || empty($agent->user_id)) {
            $query->where('id', 0);
            return $query;
        }

        $this->applyVisibilityScope($query, $agent);
        $this->applyMailboxRestrictionScope($query, $agent, $restrictions);

        return $query;
    }

    public function applyVisibilityScope($query, $agent = null)
    {
        $agent = $agent ?: Helper::getAgentByUserId();

        if (!$agent || empty($agent->user_id)) {
            $query->where('id', 0);
            return $query;
        }

        $visibility = PermissionManager::getAgentTicketVisibility($agent->user_id);

        if ($visibility === PermissionManager::VISIBILITY_ALL) {
            return $query;
        }

        if ($visibility === PermissionManager::VISIBILITY_ASSIGNED_ONLY) {
            $query->where('agent_id', $agent->id);
            return $query;
        }

        $query->where(function ($q) use ($agent) {
            $q->where('agent_id', $agent->id);
            $q->orWhereNull('agent_id');
        });

        return $query;
    }

    public function applyMailboxRestrictionScope($query, $agent = null, $restrictions = null)
    {
        $restrictedBoxes = $this->getRestrictedMailboxIds($agent, $restrictions);

        if ($restrictedBoxes) {
            $this->applyRestrictedMailboxFilter($query, $restrictedBoxes);
        }

        return $query;
    }

    // NOT IN is NULL, not TRUE, for a NULL mailbox — hence the explicit OR.
    protected function applyRestrictedMailboxFilter($query, $restrictedBoxes)
    {
        return $query->where(function ($q) use ($restrictedBoxes) {
            $q->whereNotIn('mailbox_id', $restrictedBoxes);
            $q->orWhereNull('mailbox_id');
        });
    }

    // Costs an agent + meta read, so the current-user answer is memoised for the
    // request; a request constructs many instances and they all share it.
    public function getRestrictedMailboxIds($agent = null, $restrictions = null)
    {
        if ($agent !== null || $restrictions !== null) {
            return $this->resolveRestrictedMailboxIds($agent, $restrictions);
        }

        $userId = (int) get_current_user_id();

        if (!array_key_exists($userId, self::$restrictedByUser)) {
            self::$restrictedByUser[$userId] = $this->resolveRestrictedMailboxIds(null, null);
        }

        return self::$restrictedByUser[$userId];
    }

    // Call after writing agent_restrictions so the same request sees the new value.
    public static function resetCache()
    {
        self::$restrictedByUser = [];
    }

    private function resolveRestrictedMailboxIds($agent, $restrictions)
    {
        $agent = $agent ?: Helper::getAgentByUserId();

        if (!$agent) {
            return [];
        }

        if ($restrictions === null) {
            $restrictions = $agent->getMeta('agent_restrictions', []);
        }

        if (!empty($restrictions['businessBoxRestrictions']) && !empty($restrictions['restrictedBusinessBoxes'])) {
            return array_values(array_unique(array_map('intval', $restrictions['restrictedBusinessBoxes'])));
        }

        return [];
    }

    // Same boundary, for rows that reach their mailbox through a `ticket` relation.
    public function applyMailboxRestrictionScopeViaTicket($query)
    {
        $restrictedBoxes = $this->getRestrictedMailboxIds();

        if (!$restrictedBoxes) {
            return $query;
        }

        return $query->whereHas('ticket', function ($q) use ($restrictedBoxes) {
            $this->applyRestrictedMailboxFilter($q, $restrictedBoxes);
        });
    }

    protected function isMailboxRestricted($agent, $ticket, $restrictions = null)
    {
        $restrictedBoxes = $this->getRestrictedMailboxIds($agent, $restrictions);

        return !empty($ticket->mailbox_id) && in_array((int) $ticket->mailbox_id, $restrictedBoxes, true);
    }
}
