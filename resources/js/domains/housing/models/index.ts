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