export function getGroupedAgentOptions(agents, groups, otherLabel = 'Other') {
    const result = [];
    const assignedAgentIds = new Set();

    for (const group of groups) {
        const groupAgentIds = (group.agent_ids || []).map(Number);
        const groupAgents = agents.filter(a => groupAgentIds.includes(Number(a.id)));
        if (groupAgents.length) {
            result.push({ label: group.title, agents: groupAgents });
            groupAgents.forEach(a => assignedAgentIds.add(Number(a.id)));
        }
    }

    const ungrouped = agents.filter(a => !assignedAgentIds.has(Number(a.id)));
    if (ungrouped.length) {
        result.push({ label: otherLabel, agents: ungrouped });
    }

    return result;
}
