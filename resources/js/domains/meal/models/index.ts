export interface IMealIngredient {
    product_id: number;
    name: string;
    quantity: number;
    unit: string;
}

export interface Label {
    id: number;
    name: string;
    color: string;
}

export interface Meal {
    [x: string]: any;
    id: number;
    name: string;
    is_liked: boolean;
    labels: Label[]
}
