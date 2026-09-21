<?php

namespace FluentSupport\App\Modules\Reporting;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\AgentGroup;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Person;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\TagPivot;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Tickets\AgentTicketAccess;
use FluentSupport\Framework\Database\Orm\Builder;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\Framework\Support\DateTime;

/**
 * Reporting class is responsible for getting data related to report
 * @package FluentSupport\App\Modules\Reporting
 *
 * @version 1.0.0
 */
class Reporting
{
    use ReportingHelperTrait;

    /**
     * getTicketsGrowth will generate tickets statistics and return
     * @param false $from
     * @param false $to
     * @param array $filters
     * @return array
     */
    public function getTicketsGrowth($from = false, $to = false, $filters = [])
    {
        //Generate report period
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),//Date from
            $to = $this->makeToDate($to),//Date to
            $frequency = $this->getFrequency($from, $to)// frequency P1D, P1W, P1M
        );

        //Get group by and order by i.e date,week, month
        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        //get all tickets statistics within the date range
        $query = $this->db()->table('fs_tickets')
            ->select($this->prepareSelect($frequency))
            ->whereBetween('created_at', $this->prepareBetween($frequency, $from, $to))
            ->groupBy($groupBy)
            ->oldest($orderBy);

        // Bound first: a caller-supplied mailbox_id below is not proof of access.
        (new AgentTicketAccess())->applyMailboxRestrictionScope($query);

        //If filter by product or agent or status selected
        if ($filters) {
            if (!empty($filters['statuses'])) {
                $query->whereIn('status', $filters['statuses']);
            }

            if (!empty($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            }

            if (!empty($filters['agent_id'])) {
                $query->where('agent_id', $filters['agent_id']);
            }

            if (!empty($filters['mailbox_id'])) {
                $query->where('mailbox_id', $filters['mailbox_id']);
            }

            if (!empty($filters['agent_ids'])) {
                $query->whereIn('agent_id', $filters['agent_ids']);
            }
        }

        $items = $query->get();

        return $this->getResult($period, $items);
    }

    /**
     * getTicketResolveGrowth method will get the statistics for resolved/closed tickets
     * @param false $from
     * @param false $to
     * @param array $filters
     * @return array
     */
    public function getTicketResolveGrowth($from = false, $to = false, $filters = [], $type = '')
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),//Date from
            $to = $this->makeToDate($to),//date to
            $frequency = $this->getFrequency($from, $to)// frequency P1D, P1W, P1M
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);//Get group by and order by i.e date,week, month

        $filterColumn = (!empty($type)) ? $type.'_id' : 'id';

        //get the closed ticket statistics within the date range
        $query = $this->db()->table('fs_tickets')
            ->select($this->prepareSelect($frequency, 'resolved_at'))
            ->whereBetween('resolved_at', $this->prepareBetween($frequency, $from, $to))
            ->where('status', 'closed')
            ->where($filterColumn, '>', 0)
            ->groupBy($groupBy)
            ->oldest($orderBy);

        (new AgentTicketAccess())->applyMailboxRestrictionScope($query);

        //If filter by product or agent is selected
        if ($filters) {
            if (!empty($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            }

            if (!empty($filters['agent_id'])) {
                $query->where('agent_id', $filters['agent_id']);
            }

            if (!empty($filters['mailbox_id'])) {
                $query->where('mailbox_id', $filters['mailbox_id']);
            }

            if (!empty($filters['agent_ids'])) {
                $query->whereIn('agent_id', $filters['agent_ids']);
            }
        }

        $items = $query->get();

        return $this->getResult($period, $items);
    }

    /**
     * getResponseGrowth method will generate the statistics for response
     * @param false $from
     * @param false $to
     * @param array $filters
     * @return array
     */
    public function getResponseGrowth($from = false, $to = false, $filters = [])
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),
            $to = $this->makeToDate($to),
            $frequency = $this->getFrequency($from, $to)
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        $query = (new AgentTicketAccess())->applyMailboxRestrictionScopeViaTicket(
            Conversation::query()
                ->select($this->prepareSelect($frequency))
                ->whereBetween('created_at', $this->prepareBetween($frequency, $from, $to))
                ->where('conversation_type', 'response')
                ->whereIn('person_id', Agent::getAgentIds())
                ->groupBy($groupBy)
                ->oldest($orderBy)
        );

        if ($filters) {
            if (!empty($filters['person_id'])) {
                $query->where('person_id', $filters['person_id']);
            }

            if (!empty($filters['person_ids'])) {
                $query->whereIn('person_id', $filters['person_ids']);
            }

            if (!empty($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            }
        }

        $items = $query->get();

        return $this->getResult($period, $items);
    }

    public function getResponseGrowthChart($from = false, $to = false, $filters = [], $type = ''): array
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),
            $to = $this->makeToDate($to),
            $frequency = $this->getFrequency($from, $to)
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        $filterColumn = (!empty($type)) ? $type.'_id' : 'id';

        $query = $this->db()->table('fs_tickets')
              ->select($this->prepareSelect($frequency,'created_at','response_count'))
              ->whereBetween('created_at', $this->prepareBetween($frequency, $from, $to))
              ->havingRaw('COUNT(response_count)> 0')
              ->where($filterColumn, '>', 0)
              ->groupBy($groupBy)
              ->oldest($orderBy);

        (new AgentTicketAccess())->applyMailboxRestrictionScope($query);

        if ($filters) {
            if (!empty($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            }

            if (!empty($filters['mailbox_id'])) {
                $query->where('mailbox_id', $filters['mailbox_id']);
            }
        }

        $items = $query->get();

        return $this->getResult($period, $items);
    }

    // One grouped query instead of one per agent; shared by the two summaries.
    private function interactionCountsByAgent($from, $to, $access)
    {
        return $access->applyMailboxRestrictionScopeViaTicket(
            Conversation::select([
                $this->db()->raw('person_id as agent_id'),
                $this->db()->raw('COUNT(DISTINCT ticket_id) as count')
            ])
                ->whereIn('person_id', Agent::getAgentIds())
                ->whereBetween('created_at', [$from, $to])
                ->where('conversation_type', 'response')
                ->groupBy('person_id')
        )->get();
    }

    /**
     * agentSummary method will prepare ticket summary with responses by agent
     * @param false $from
     * @param false $to
     * @param false $agent
     * @return mixed
     */
    public function agentSummary($from = false, $to = false, $agent = false)
    {
        if(!$from) {
            $from = current_time('Y-m-d');
        }

        if(!$to) {
            $to = current_time('Y-m-d');
        }

        $from .= ' 00:00:00';
        $to .= ' 23:59:59';
        $reports = [];

        $access = new AgentTicketAccess();

        //Get tickets statistics that are closed
        $resolves = $this->db()->table('fs_tickets')
            ->select([
                $this->db()->raw('COUNT(id) AS count'),
                'agent_id',
            ])
            ->groupBy('agent_id')
            ->where('status', 'closed')
            ->whereBetween('resolved_at', [$from, $to]);

        $resolves = $access->applyMailboxRestrictionScope($resolves)->get();

        $reports = $this->pushReportData('closed', $resolves, $reports, 'agent_id');

        //get statistics for all except closed ticket
        $openTickets = $this->db()->table('fs_tickets')
            ->select([
                $this->db()->raw('COUNT(id) AS count'),
                'agent_id'
            ])
            ->groupBy('agent_id')
            ->where('status', '!=', 'closed');

        $openTickets = $access->applyMailboxRestrictionScope($openTickets)->get();

        $reports = $this->pushReportData('opens', $openTickets, $reports, 'agent_id');
        //Get response by agent
        $responses = $access->applyMailboxRestrictionScopeViaTicket(
            Conversation::select([
                $this->db()->raw('COUNT(id) AS count'),
                $this->db()->raw('person_id as agent_id'),
                $this->db()->raw('created_at')
            ])
                ->whereIn('person_id', Agent::getAgentIds())
                ->whereBetween('created_at', [$from, $to])
                ->where('conversation_type', 'response')
                ->groupBy('agent_id')
        )->get();

        $reports = $this->pushReportData('responses', $responses, $reports, 'agent_id');
        //Get interactions/responses by individual agents
        foreach ($this->interactionCountsByAgent($from, $to, $access) as $row) {
            if (isset($reports[$row->agent_id])) {
                $reports[$row->agent_id]['interactions'] = (int) $row->count;
            }
        }

        $agentIds = array_keys($reports);

        if ($agent) {
            $agentIds = array_map('intval', explode(',', $agent));
        }

        // get agent feedback statistics
        $agentFeedbackRatingEnabled = Helper::getBusinessSettings('agent_feedback_rating') === 'yes';
        if (defined('FLUENTSUPPORTPRO_PLUGIN_VERSION') && $agentFeedbackRatingEnabled) {
            $agentConversations = $access->applyMailboxRestrictionScopeViaTicket(
                Conversation::select([
                    $this->db()->raw('person_id as agent_id'),
                    $this->db()->raw('GROUP_CONCAT(id) as conversation_ids')
                ])
                    ->whereIn('person_id', $agentIds)
                    ->whereIn('person_id', Agent::getAgentIds())
                    ->where('conversation_type', 'response')
                    ->groupBy('agent_id')
            )->get();

            foreach ($agentConversations as $conversation) {
                $conversationIds = array_map('intval', explode(',', $conversation->conversation_ids));

                $feedbackMeta = Meta::whereIn('object_id', $conversationIds)
                    ->where('key', 'agent_feedback_ratings')
                    ->whereBetween('created_at', [$from, $to])
                    ->get();

                $likeCount = 0;
                $dislikeCount = 0;

                foreach ($feedbackMeta as $feedback) {
                    $feedbackStatus = $feedback->value;

                    if ($feedbackStatus === 'like') {
                        $likeCount++;
                    } elseif ($feedbackStatus === 'dislike') {
                        $dislikeCount++;
                    }
                }

                $agentId = $conversation->agent_id;
                $reports[$agentId]['likes'] = $likeCount;
                $reports[$agentId]['dislikes'] = $dislikeCount;
            }
        }

        // Without an explicit agent filter, report on every agent — $reports
        // only holds the ones with activity in the range, so restricting to its
        // keys would silently drop agents who simply had a quiet period.
        $agentsQuery = Agent::select(['id', 'first_name', 'last_name', 'email']);

        if ($agent) {
            $agentsQuery->whereIn('id', $agentIds);
        }

        $agents = $agentsQuery->get();

        $reportFields = [
            'interactions'    => 0,
            'responses'       => 0,
            'opens'           => 0,
            'closed'          => 0,
            'waiting_tickets' => 0,
        ];

        $additionalFields = defined('FLUENTSUPPORTPRO_PLUGIN_VERSION') && $agentFeedbackRatingEnabled ? ['likes' => 0, 'dislikes' => 0] : [];
        $reportFields = $reportFields + $additionalFields;

        // One grouped query instead of one per agent — this summary lists every
        // agent, so a per-agent call scales with the whole agent table.
        $waitStats = $this->getWaitStatsByAgents($agents->pluck('id')->toArray());
        $emptyActiveStat = ['average_waiting' => 0, 'max_waiting' => 0, 'waiting_tickets' => 0];

        foreach ($agents as $agent) {
            $agent->stats = wp_parse_args(isset($reports[$agent->id]) ? $reports[$agent->id] : [], $reportFields);
            $agent->active_stat = isset($waitStats[$agent->id])
                ? $this->formatActiveStat($waitStats[$agent->id])
                : $emptyActiveStat;
        }
        return $agents;
    }

    public function getSummary($type, $from = null, $to = null)
    {
        global $wpdb;
        $tablePrefix = $wpdb->prefix;

        if (!$from) {
            $from = current_time('Y-m-d');
        }

        if (!$to) {
            $to = current_time('Y-m-d');
        }

        $from .= ' 00:00:00';
        $to .= ' 23:59:59';
        $reports = [];

        $groupByField = $type == 'product' ? 'product_id' : 'mailbox_id';

        $access = new AgentTicketAccess();

        $resolves = $this->db()->table('fs_tickets')
                         ->select([
                             $this->db()->raw('COUNT(id) AS count'),
                             $groupByField,
                         ])
                         ->groupBy($groupByField)
                         ->where('status', 'closed')
                         ->whereBetween('resolved_at', [$from, $to]);

        // Aggregates count tickets too, so the same mailbox boundary applies.
        $resolves = $access->applyMailboxRestrictionScope($resolves)->get();

        $reports = $this->pushReportData('closed', $resolves, $reports, $groupByField);

        $openTickets = $this->db()->table('fs_tickets')
                            ->select([
                                $this->db()->raw('COUNT(id) AS count'),
                                $groupByField
                            ])
                            ->groupBy($groupByField)
                            ->where('status', '!=', 'closed')
                            ->whereBetween('created_at', [$from, $to]);

        $openTickets = $access->applyMailboxRestrictionScope($openTickets)->get();

        $reports = $this->pushReportData('opens', $openTickets, $reports, $groupByField);

        $responses = $this->db()->table('fs_conversations')
            ->join('fs_tickets', 'fs_tickets.id', '=', 'fs_conversations.ticket_id')
            ->select([
                $this->db()->raw('COUNT(' . $tablePrefix . 'fs_conversations.id) AS count'),
                'fs_tickets.' . $groupByField,
            ])
            ->groupBy('fs_tickets.' . $groupByField)
            ->whereBetween('fs_conversations.created_at', [$from, $to]);

        $responses = $access->applyMailboxRestrictionScope($responses)->get();

        $reports = $this->pushReportData('responses', $responses, $reports, $groupByField);

        // Interactions = how many distinct tickets were replied to in the range.
        // This used to GROUP_CONCAT every ticket id per group and feed them back
        // in a whereIn, one query per group. group_concat_max_len is 1024 bytes
        // by default, so any group past ~170 tickets had its id list silently
        // truncated and the count came out far too low. One grouped
        // COUNT(DISTINCT) has no such limit and drops the per-group queries.
        $interactions = $this->db()->table('fs_conversations')
            ->join('fs_tickets', 'fs_tickets.id', '=', 'fs_conversations.ticket_id')
            ->select([
                'fs_tickets.' . $groupByField,
                $this->db()->raw('COUNT(DISTINCT ' . $tablePrefix . 'fs_conversations.ticket_id) AS count'),
            ])
            ->where('fs_conversations.conversation_type', 'response')
            ->whereBetween('fs_conversations.created_at', [$from, $to])
            ->groupBy('fs_tickets.' . $groupByField);

        $interactions = $access->applyMailboxRestrictionScope($interactions)->get();

        $reports = $this->pushReportData('interactions', $interactions, $reports, $groupByField);

        $ids = array_keys($reports);

        $types = [
            'product' => [
                'model' => Product::class,
                'fields' => ['id', 'title'],
            ],
            'mailbox' => [
                'model' => MailBox::class,
                'fields' => ['id', 'name'],
            ],
        ];

        $model = $types[$type]['model'];
        $fields = $types[$type]['fields'];

        $items = $model::select($fields)
                       ->whereIn('id', $ids)
                       ->get();

        foreach ($items as $item) {
            $report = isset($reports[$item->id]) ? $reports[$item->id] : [];

            $report = wp_parse_args($report, [
                'responses' => 0,
                'opens' => 0,
                'closed' => 0,
                'interactions' => 0
            ]);
            $item->stats = $report;
            $item->active_stat = '';
        }

        return $items;
    }

    /**
     * agentGroupSummary method will prepare ticket summary aggregated by agent group
     * @param false $from
     * @param false $to
     * @return mixed
     */
    public function agentGroupSummary($from = false, $to = false)
    {
        if (!$from) {
            $from = current_time('Y-m-d');
        }

        if (!$to) {
            $to = current_time('Y-m-d');
        }

        $from .= ' 00:00:00';
        $to .= ' 23:59:59';

        // Get per-agent stats using the same queries as agentSummary
        $reports = [];

        $access = new AgentTicketAccess();

        $resolves = $this->db()->table('fs_tickets')
            ->select([
                $this->db()->raw('COUNT(id) AS count'),
                'agent_id',
            ])
            ->groupBy('agent_id')
            ->where('status', 'closed')
            ->whereBetween('resolved_at', [$from, $to]);

        $resolves = $access->applyMailboxRestrictionScope($resolves)->get();

        $reports = $this->pushReportData('closed', $resolves, $reports, 'agent_id');

        $openTickets = $this->db()->table('fs_tickets')
            ->select([
                $this->db()->raw('COUNT(id) AS count'),
                'agent_id'
            ])
            ->groupBy('agent_id')
            ->where('status', '!=', 'closed');

        $openTickets = $access->applyMailboxRestrictionScope($openTickets)->get();

        $reports = $this->pushReportData('opens', $openTickets, $reports, 'agent_id');

        $responses = $access->applyMailboxRestrictionScopeViaTicket(
            Conversation::select([
                $this->db()->raw('COUNT(id) AS count'),
                $this->db()->raw('person_id as agent_id'),
            ])
                ->whereIn('person_id', Agent::getAgentIds())
                ->whereBetween('created_at', [$from, $to])
                ->where('conversation_type', 'response')
                ->groupBy('agent_id')
        )->get();

        $reports = $this->pushReportData('responses', $responses, $reports, 'agent_id');

        foreach ($this->interactionCountsByAgent($from, $to, $access) as $row) {
            if (isset($reports[$row->agent_id])) {
                $reports[$row->agent_id]['interactions'] = (int) $row->count;
            }
        }

        // Get group -> agent_id mappings
        $groupAgentMap = TagPivot::where('source_type', 'agent_group')
            ->select(['tag_id', 'source_id'])
            ->get()
            ->groupBy('tag_id');

        // Get all agent groups
        $groups = AgentGroup::select(['id', 'title'])->get();

        $defaultStats = [
            'responses'    => 0,
            'interactions' => 0,
            'opens'        => 0,
            'closed'       => 0,
        ];

        foreach ($groups as $group) {
            $agentIds = isset($groupAgentMap[$group->id])
                ? array_map('intval', $groupAgentMap[$group->id]->pluck('source_id')->toArray())
                : [];

            $group->agents_count = count($agentIds);

            $groupStats = $defaultStats;

            foreach ($agentIds as $agentId) {
                if (isset($reports[$agentId])) {
                    $agentStats = wp_parse_args($reports[$agentId], $defaultStats);
                    $groupStats['responses']    += (int) $agentStats['responses'];
                    $groupStats['interactions'] += (int) $agentStats['interactions'];
                    $groupStats['opens']        += (int) $agentStats['opens'];
                    $groupStats['closed']       += (int) $agentStats['closed'];
                }
            }

            $group->stats = $groupStats;
        }

        return $groups;
    }

    /**
     * pushReportData method will format the ticket summary report
     * @param $type
     * @param $tickets
     * @param $reports
     * @param $groupByField
     * @return array
     */
    private function pushReportData($type, $tickets, $reports, $groupByField): array
    {
        foreach ($tickets as $ticket) {
            $groupKey = $ticket->{$groupByField};

            if (!$groupKey) {
                continue;
            }

            if (!isset($reports[$groupKey])) {
                $reports[$groupKey] = [];
            }

            $reports[$groupKey][$type] = $ticket->count;
        }

        return $reports;
    }

    /**
     * getActiveStats method will return the statistics for active tickets
     * This method will get the list of open tickets calculate the wait times and return results
     * @param int|null $productId Narrow to one product's tickets
     * @return array|false
     */
    public function getActiveStats($productId = null)
    {
        // We will calculate the wait times for open waiting tickets
        $query = (new AgentTicketAccess())->applyMailboxRestrictionScope(
            Ticket::waitingOnly()
        )
            ->where('status', '!=', 'closed')
            ->whereNotNull('waiting_since');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $waitStat = $query
            ->select([
                $this->db()->raw('avg(UNIX_TIMESTAMP(waiting_since)) as avg_waiting'),
                $this->db()->raw('MIN(UNIX_TIMESTAMP(waiting_since)) as max_waiting'),
                $this->db()->raw('COUNT(*) as total_tickets')
            ])
            ->first();

        if(!$waitStat) {
            return false;
        }

        $waitStat->avg_waiting = intval($waitStat->avg_waiting);
        if($waitStat->avg_waiting > 0) {
            $waitSeconds = time() -  $waitStat->avg_waiting;
            if( $waitSeconds < 172800 && $waitSeconds > 7200) {
                $avgWait = ceil($waitSeconds / 3600) . ' hours';
            } else {
                $avgWait = human_time_diff($waitStat->avg_waiting, time());
            }
        } else {
            $avgWait = 0;
        }

        return [
            'average_waiting' => $avgWait,
            'max_waiting' => (intval($waitStat->max_waiting)) ? human_time_diff(intval($waitStat->max_waiting), time()) : 0,
            'waiting_tickets' => $waitStat->total_tickets
        ];
    }

    /**
     * getActiveStatByAgent method will return the statistics of active tickets for an agent
     * This method will get  agent id as parameter, fetch the list of open tickets by agent id, calculate the wait times and return results
     * @param $agentId
     * @return array|false
     */
    public function getActiveStatByAgent($agentId)
    {
        $waitStat = (new AgentTicketAccess())->applyMailboxRestrictionScope(Ticket::waitingOnly())
            ->where('status', '!=', 'closed')
            ->whereNotNull('waiting_since')
            ->where('agent_id', $agentId)
            ->select([
                $this->db()->raw('avg(UNIX_TIMESTAMP(waiting_since)) as avg_waiting'),
                $this->db()->raw('MIN(UNIX_TIMESTAMP(waiting_since)) as max_waiting'),
                $this->db()->raw('COUNT(*) as total_tickets')
            ])
            ->first();

        if(!$waitStat) {
            return false;
        }

        return $this->formatActiveStat($waitStat);
    }

    /**
     * getWaitStatsByAgents is the batched form of getActiveStatByAgent's aggregate, keyed by agent id
     * Agents with no waiting tickets get no row, so callers supply the zeroed default
     * @param array $agentIds
     * @return \FluentSupport\Framework\Database\Orm\Collection|array
     */
    public function getWaitStatsByAgents($agentIds)
    {
        if (empty($agentIds)) {
            return [];
        }

        return (new AgentTicketAccess())->applyMailboxRestrictionScope(Ticket::waitingOnly())
            ->where('status', '!=', 'closed')
            ->whereNotNull('waiting_since')
            ->whereIn('agent_id', $agentIds)
            ->select([
                'agent_id',
                $this->db()->raw('avg(UNIX_TIMESTAMP(waiting_since)) as avg_waiting'),
                $this->db()->raw('MIN(UNIX_TIMESTAMP(waiting_since)) as max_waiting'),
                $this->db()->raw('COUNT(*) as total_tickets')
            ])
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');
    }

    /**
     * formatActiveStat converts a waiting-ticket aggregate row into the display shape
     * @param $waitStat
     * @return array
     */
    private function formatActiveStat($waitStat)
    {
        $waitStat->avg_waiting = intval($waitStat->avg_waiting);
        if($waitStat->avg_waiting > 0) {
            $waitSeconds = time() -  $waitStat->avg_waiting;
            if( $waitSeconds < 172800 && $waitSeconds > 7200) {
                $avgWait = ceil($waitSeconds / 3600) . ' hours';
            } else {
                $avgWait = human_time_diff($waitStat->avg_waiting, time());
            }
        } else {
            $avgWait = 0;
        }

        return [
            'average_waiting' => $avgWait,
            'max_waiting' => (intval($waitStat->max_waiting)) ? human_time_diff(intval($waitStat->max_waiting), time()) : 0,
            'waiting_tickets' => $waitStat->total_tickets
        ];
    }

    public function getQueryResults($from, $to, $filter)
    {
        switch ($filter['report_type']) {
            case 'ticket':
                return $this->getTicketStats($from, $to);
            case 'agent_response':
                return $this->getResponseStats($from, $to, 'agent', $filter['agent_id'] ?? null);
            case 'customer_response':
                return $this->getResponseStats($from, $to, 'customer');
            default:
                return [];
        }
    }

    public function getTicketStats($from, $to, $productId = null)
    {
        global $wpdb;

        $whereClause = '';
        $queryParams = [];

        if ($from && $to) {
            $start_date = gmdate('Y-m-d 00:00:00', strtotime($from));
            $end_date = gmdate('Y-m-d 23:59:59', strtotime($to));
            $whereClause = 'WHERE created_at BETWEEN %s AND %s';
            $queryParams[] = $start_date;
            $queryParams[] = $end_date;
        }

        if ($productId) {
            $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'product_id = %d';
            $queryParams[] = intval($productId);
        }

        // Raw SQL, so the query-builder helper cannot be used.
        $restrictedMailboxIds = PermissionManager::getRestrictedMailboxIds();

        if ($restrictedMailboxIds) {
            $restrictedList = implode(',', array_map('intval', $restrictedMailboxIds));
            $whereClause .= ($whereClause ? ' AND ' : 'WHERE ')
                . "(mailbox_id NOT IN ({$restrictedList}) OR mailbox_id IS NULL)";
        }

        // SQL query to count tickets by day of week and hour within the specified date range.
        // $wpdb->prefix is WordPress-internal; $whereClause is built from hardcoded literals
        // only — user-supplied date values are bound via %s placeholders in prepare() below.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = "SELECT DAYNAME(created_at) AS weekday, HOUR(created_at) AS hour, COUNT(*) AS count
        FROM {$wpdb->prefix}fs_tickets
        {$whereClause}
        GROUP BY DAYNAME(created_at), HOUR(created_at)
        ORDER BY FIELD(DAYNAME(created_at), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), HOUR(created_at)";

        // Execute the query — prepared when date params are present, plain otherwise.
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- $query structure is hardcoded; values are bound via $wpdb->prepare() when $queryParams is non-empty.
        $results = $queryParams
            ? $wpdb->get_results($wpdb->prepare($query, $queryParams)) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            : $wpdb->get_results($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $fillData = array_fill(0, 24, 0);
        // add :00 on the suffix
        $fillData = array_combine(array_map(function ($hour) {
            return sprintf('%1d:00', $hour);
        }, array_keys($fillData)), $fillData);

        // Prepare the report
        // Prepare the report
        $report = [
            'Mon' => $fillData,
            'Tue' => $fillData,
            'Wed' => $fillData,
            'Thu' => $fillData,
            'Fri' => $fillData,
            'Sat' => $fillData,
            'Sun' => $fillData
        ];

        foreach ($results as $result) {
            $weekdayName = substr($result->weekday, 0, 3);
            if (!isset($report[$weekdayName])) {
                $report[$weekdayName] = $fillData;
            }
            $report[$weekdayName][sprintf('%1d:00', $result->hour)] = (int)$result->count;
        }

        return $report;
    }

    public function getResponseStats($from, $to, $reportType, $agentId = null, $productId = null)
    {
        global $wpdb;

        $whereClause = ' AND p.person_type = %s';
        $queryParams = [$reportType];

        if ($from && $to) {
            $start_date = gmdate('Y-m-d 00:00:00', strtotime($from));
            $end_date = gmdate('Y-m-d 23:59:59', strtotime($to));
            $whereClause .= ' AND c.created_at BETWEEN %s AND %s';
            $queryParams[] = $start_date;
            $queryParams[] = $end_date;
        }

        if ($agentId) {
            $whereClause .= ' AND c.person_id = %d';
            $queryParams[] = intval($agentId);
        }

        // As getTicketStats(), but reaching the mailbox and product through the parent ticket.
        $ticketConditions = [];
        $restrictedMailboxIds = PermissionManager::getRestrictedMailboxIds();

        if ($restrictedMailboxIds) {
            $restrictedList = implode(',', array_map('intval', $restrictedMailboxIds));
            $ticketConditions[] = "(t.mailbox_id NOT IN ({$restrictedList}) OR t.mailbox_id IS NULL)";
        }

        if ($productId) {
            $ticketConditions[] = 't.product_id = %d';
            $queryParams[] = intval($productId);
        }

        if ($ticketConditions) {
            $whereClause .= " AND EXISTS (SELECT 1 FROM {$wpdb->prefix}fs_tickets t"
                . " WHERE t.id = c.ticket_id AND " . implode(' AND ', $ticketConditions) . ')';
        }

        // SQL query to count customer responses by day of week and hour within the specified date range
        $query = $wpdb->prepare(
            "SELECT DAYNAME(c.created_at) AS weekday, HOUR(c.created_at) AS hour, COUNT(*) AS count
            FROM {$wpdb->prefix}fs_conversations AS c
            JOIN {$wpdb->prefix}fs_persons AS p ON c.person_id = p.id
            WHERE c.conversation_type = 'response'
              {$whereClause}
            GROUP BY DAYNAME(c.created_at), HOUR(c.created_at)
            ORDER BY FIELD(DAYNAME(c.created_at), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), HOUR(c.created_at)",
            $queryParams
        );

        // Execute the query
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- $query is the output of $wpdb->prepare() above; checker incorrectly traces back through the prepared parameters.
        $results = $wpdb->get_results($query);

        $fillData = array_fill(0, 24, 0);
        // add :00 on the suffix
        $fillData = array_combine(array_map(function ($hour) {
            return sprintf('%1d:00', $hour);
        }, array_keys($fillData)), $fillData);

        // Prepare the report
        $report = [
            'Mon' => $fillData,
            'Tue' => $fillData,
            'Wed' => $fillData,
            'Thu' => $fillData,
            'Fri' => $fillData,
            'Sat' => $fillData,
            'Sun' => $fillData
        ];

        foreach ($results as $result) {
            $weekdayName = substr($result->weekday, 0, 3);
            if (!isset($report[$weekdayName])) {
                $report[$weekdayName] = $fillData;
            }
            $report[$weekdayName][sprintf('%01d:00', $result->hour)] = (int)$result->count;
        }

        return $report;
    }

    public function getTicketResponseStats($from, $to, $filter)
    {
        $query = (new AgentTicketAccess())->applyMailboxRestrictionScopeViaTicket(
            Conversation::query()
        )
            ->select('id', 'ticket_id', 'person_id', 'created_at', 'content')
            ->addSelect([
                'person_type' => Person::select('person_type')
                    ->whereColumn('id', 'fs_conversations.person_id'),
                'full_name' => Person::selectRaw("CONCAT(first_name, ' ', last_name)")
                    ->whereColumn('id', 'fs_conversations.person_id')
            ])
            ->where('conversation_type', 'response')
            ->when(Arr::get($filter, 'person_type'), function (Builder $q, $personType) {
                return $q->whereHas('person', function ($q) use ($personType) {
                    return $q->where('person_type', $personType);
                });
            })
            ->when(($from && $to), function ($q) use ($from, $to) {
                return $q->whereBetween('created_at', [
                    DateTime::parse($from)->startOfDay(),
                    DateTime::parse($to)->endOfDay()
                ]);
            })
            ->when(Arr::get($filter, 'person_id'), function ($q, $personId) {
                return $q->where('person_id', $personId);
            });

        return $query->get();
    }

    public function applyDateFilter($query, $from, $to)
    {
        if ($from && $to) {
            $from .= ' 00:00:00';
            $to .= ' 23:59:59';

            $query->whereBetween('created_at', [$from, $to]);
        }
    }

    public function finalizeQuery($query)
    {
        return $query->groupByRaw('DAYNAME(created_at), HOUR(created_at)')
            ->orderByRaw("FIELD(DAYNAME(created_at), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), HOUR(created_at)")
            ->get();
    }

    public function formatResults($results)
    {
        $dataItems = [
            'Mon' => [], 'Tue' => [], 'Wed' => [], 'Thu' => [], 'Fri' => [], 'Sat' => [], 'Sun' => []
        ];

        $hours = array_map(function ($hour) {
            return $hour . ":00";
        }, range(0, 23));

        foreach ($dataItems as $day => $data) {
            $dataItems[$day] = array_fill_keys($hours, 0);
        }

        foreach ($results as $row) {
            $day = substr($row['day_of_week'], 0, 3);
            $hour = $row['hour_of_day'] . ":00";
            $dataItems[$day][$hour] = (int) $row['count'];
        }

        return $dataItems;
    }
}
