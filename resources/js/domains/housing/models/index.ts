export interface LogerField {
    name: string;
    rules: Record<string, string>[];
    [key: string]: string | string[];
}

export interface FieldValue {
    field_name: string;
    rules: Record<string, string>[];
    value: string;
    [key: string]: string;
}

export interface Plan {
    fields: LogerField[];
    stages: PlanStage[];
}

export interface PlanStage {
    items: PlanItem[]
}

export interface PlanItem {
    fields: FieldValue[];
    [key: string]: string;
    type: string;
}

export interface OccurrenceItem {
    id: number;
    name: string;
}

export interface IOccurrenceCheck {
    id: number;
    name: string;
    last_date: string;
    avg_days_passed: number;
    current_days_count: number,
    previous_days_count: number,
    status: string;
}
