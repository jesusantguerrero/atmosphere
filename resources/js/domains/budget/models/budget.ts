import { ICategory } from "@/domains/transactions/models";

export interface BudgetTarget {
    amount: number
    category_id: number;
    color: string;
    created_at: string;
    frequency:string;
    frequency_date?:null
    frequency_interval?:null
    frequency_interval_unit?:null
    frequency_month_date: number;
    frequency_week_day: string;
    icon?: string
    id: number;
    is_private: boolean;
    is_team_goal: number;
    name: string;
    note:null
    status: string;
    target_type:string;
    team_id:number;
    updated_at: string;
    completed_at: string;
    user_id: number;
}

export interface BudgetMonth {
    activity: number;
    available:number;
    budgeted:number;
    category_id: number;
    created_at:Date;
    currency_code: string;
    id: number;
    month:string;
    name: string;
    team_id: number;
    updated_at:number;
    user_id: number;
}

export interface BudgetCategory {
    id: number
    team_id: number
    user_id: number
    client_id?: number
    parent_id: number
    resource_type_id?: string
    color?: string
    icon?: string
    resource_type: string
    display_id: string
    number: number
    name:string
    alias?: string
    description?: string
    type: number;
    index: number;
    depth: number;
    status: string;
    meta_data?: Record<string, string>
    created_at: Date
    updated_at: Date
    budget: BudgetTarget
    budgets: BudgetMonth[]
    month:string
    budgeted: number;
    activity: number;
    available: number
    prevMonthLeftOver:number
    hasFunded: boolean
}

export interface BudgetItem {

}

export interface IBudgetCategory extends ICategory {
    hasOverspent: boolean;
    hasFunded: boolean;
    hasUnderFunded: boolean;
    overAssigned: boolean;
    subCategories: any[]
    activity: number;
}


const targetTypeNames = {
    saving_balance: 'saving balance'
}

export const getTargetName = (code: string) => {
    return targetTypeNames[code] ?? code;
}
