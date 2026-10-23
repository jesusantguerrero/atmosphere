const booleanValue = (value: unknown): boolean => value === undefined ? true : ![false, 0, '0', 'false', null, ''].includes(value as any);
export const occurrenceRuleFields = (input: Record<string, any> = {}) => ({
    description: (Array.isArray(input.description) ? input.description : []).map((condition: any) => ({ operator: condition.operator || 'contains', value: condition.value ?? '' })),
    category_id: [...(Array.isArray(input.category_id) ? input.category_id : [])],
    payee_id: [...(Array.isArray(input.payee_id) ? input.payee_id : [])],
    account_id: [...(Array.isArray(input.account_id) ? input.account_id : [])],
});
export const occurrenceFormData = (input: Record<string, any> | null = {}) => {
    const data = input ?? {}, conditions = data.conditions ?? {};
    return {
        id: data.id ?? null, name: data.name ?? '', type: data.type ?? 'transactions',
        conditions: {
            match: conditions.match === 'any' ? 'any' : 'all', ...occurrenceRuleFields(conditions),
            groups: (Array.isArray(conditions.groups) ? conditions.groups : []).map((group: any) => ({ match: group.match === 'any' ? 'any' : 'all', ...occurrenceRuleFields(group) })),
            exclude: occurrenceRuleFields(conditions.exclude ?? {}),
        },
        notify_on_avg: booleanValue(data.notify_on_avg), notify_on_last_count: booleanValue(data.notify_on_last_count), is_active: booleanValue(data.is_active),
    };
};
const hasFields = (conditions: Record<string, any>): boolean => Boolean(
    conditions?.category_id?.length || conditions?.payee_id?.length || conditions?.account_id?.length
    || conditions?.description?.some((condition: any) => ['contains', 'is'].includes(condition.operator) && String(condition.value ?? '').trim()),
);
export const hasOccurrenceRule = (conditions: Record<string, any>): boolean => hasFields(conditions) || Boolean(conditions?.groups?.some(hasFields));
