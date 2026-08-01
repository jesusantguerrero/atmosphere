<?php

/*
|--------------------------------------------------------------------------
| Nombres por defecto de las categorías de presupuesto
|--------------------------------------------------------------------------
|
| Indexadas por `display_id` de config/journal.php. Usadas por
| TransactionCategoriesCreate al crear el team para sembrar el Budget en el
| idioma actual de la app.
|
| RESERVADO — no traducir: los siguientes display_ids se matchean por nombre
| literal inglés en el código (ver BudgetReservedNames): inflow, ready_to_assign.
|
*/
return [
    // Nombres de grupo (nivel superior)
    'immediate_obligations' => 'Obligaciones Inmediatas',
    'true_expenses' => 'Gastos Reales',
    'quality_of_life' => 'Metas de Calidad de Vida',
    'just_for_fun' => 'Solo por Diversión',
    'savings' => 'Ahorros',
    'personal' => 'Personal',

    // Descripciones de grupo (solo al sembrar)
    'immediate_obligations_desc' => 'Facturas con consecuencias serias si no se pagan este mes',
    'true_expenses_desc' => 'Predecibles pero irregulares — ahorra cada mes, gasta luego',
    'quality_of_life_desc' => 'Inversión en ti mismo y experiencias planeadas',
    'just_for_fun_desc' => 'Entretenimiento y pasatiempos',
    'savings_desc' => 'Dinero apartado para metas futuras',
    'personal_desc' => 'Gasto personal discrecional',

    // Obligaciones Inmediatas
    'rent_mortgage' => 'Alquiler / Hipoteca',
    'electricity' => 'Luz',
    'water' => 'Agua',
    'internet' => 'Internet',
    'cellphone' => 'Celular',
    'groceries' => 'Supermercado',
    'transportation' => 'Transporte',

    // Gastos Reales
    'auto_maintenance' => 'Mantenimiento de Auto',
    'home_maintenance' => 'Mantenimiento del Hogar',
    'medical' => 'Salud',
    'insurance' => 'Seguros',
    'clothing' => 'Ropa',
    'gifts' => 'Regalos',

    // Calidad de Vida
    'education' => 'Educación',
    'vacation' => 'Vacaciones',
    'fitness' => 'Ejercicio',

    // Solo por Diversión
    'dining_out' => 'Comer Fuera',
    'entertainment' => 'Entretenimiento',
    'subscriptions' => 'Suscripciones',
    'hobbies' => 'Pasatiempos',

    // Ahorros
    'emergency_fund' => 'Fondo de Emergencia',
    'savings_general' => 'Ahorro General',

    // Personal
    'personal_spending' => 'Gasto Personal',
];
