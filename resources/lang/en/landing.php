<?php

return [
    'meta' => [
        'title' => 'Loger — The Family Operating System',
        'description' => 'Loger is one app to run the financial, logistical and relational machinery of a household. Budgets, meals, chores, family schedules, home maintenance — all the things you juggle across half a dozen apps, in one system.',
        'og_title' => 'Loger — The Family Operating System',
        'og_description' => 'Budgets, meals, chores, family, calendar. Run your household the way it actually works — as a system, not a spreadsheet.',
    ],

    'nav' => [
        'pricing' => 'Pricing',
        'demo' => 'Demo',
        'login' => 'Login',
        'sign_up' => 'Sign up free',
    ],

    'hero' => [
        'badge' => 'The Family Operating System',
        'headline_a' => 'Run your house',
        'headline_b' => 'like the system it is.',
        'subhead' => 'Budget, meals, chores, family schedules, home maintenance — all the things you juggle in your head, in spreadsheets, or across half a dozen apps. Loger pulls them into one system that actually works for households outside the US banking world.',
        'cta_primary' => 'Get started — it\'s free',
        'cta_secondary' => 'See it in action',
        'reassurance' => 'No credit card required · Free forever for personal use',
    ],

    'pillars' => [
        'eyebrow' => 'Five pillars, one system',
        'title' => 'Everything that runs your household',
        'subtitle' => 'Five concerns you can enable per team. Finance is the one most apps stop at — Loger keeps going.',
    ],

    'features' => [
        'finance' => [
            'title' => 'Finance',
            'description' => 'YNAB-style envelope budgeting with multi-currency support out of the box. Reconcile statements without depending on Plaid or US-only bank syncs.',
            'items' => [
                'Zero-based monthly budget',
                'Multi-currency accounts (native + converted)',
                'PDF statement import & reconciliation',
                'Net worth, savings goals, watchlists',
            ],
        ],
        'meals' => [
            'title' => 'Food',
            'description' => 'Plan what you eat, what to buy, and what\'s already in the pantry. Connect every grocery run directly to your household budget.',
            'items' => [
                'Recipes, ingredients & favorites',
                'Weekly meal planner & shopping lists',
                'Pantry inventory & expirations',
                'Cost per recipe & weekly food budget',
            ],
        ],
        'housing' => [
            'title' => 'Home',
            'description' => 'Run the house like the asset it is. Chores, maintenance, equipment, recurring bills and service contacts in one place.',
            'items' => [
                'Chores & "when did we last…?" checks',
                'Equipment inventory & warranties',
                'Maintenance schedule & service contacts',
                'Recurring bills, utilities, plans',
            ],
        ],
        'family' => [
            'title' => 'Family',
            'description' => 'The people side of the house. Profiles, important dates, health notes and the small things you keep meaning to remember.',
            'items' => [
                'Member profiles & important dates',
                'Health notes (allergies, meds, visits)',
                'School / work activity log',
                'Preferences, sizes, gift ideas',
            ],
        ],
        'calendar' => [
            'title' => 'Calendar & Routines',
            'description' => 'The integrating layer. Pulls dated items from every other pillar — meals, chores, bills, family events — into one weekly view.',
            'items' => [
                'Weekly schedule across all pillars',
                'Recurring routines & checklists',
                'Shared family agenda',
                'Reminders by push, email, WhatsApp',
            ],
        ],
    ],

    'social_proof' => [
        'title' => 'Loved by people who outgrew spreadsheets',
        'subtitle' => 'Real people, real budgets. Add yours to the list.',
        'placeholder_quote' => '"Your testimonial here — share what changed when you started budgeting in Loger."',
        'placeholder_attribution' => 'Be the first to share your story',
        'cta' => 'Submit your testimonial',
    ],

    'why' => [
        'title' => 'The home isn\'t a budget. It\'s a system.',
        'body' => 'Most apps assume you have a US bank with automatic import — and they stop at money. Loger goes further: it works with any institution, supports multiple currencies natively, and reaches beyond money into the relational and logistical work of running a household. One system, instead of half a dozen apps.',
    ],

    'final_cta' => [
        'title' => 'Ready to take control?',
        'subtitle' => 'Create a free account and start managing your finances today.',
        'cta' => 'Create free account',
    ],

    'footer' => [
        'tagline' => 'The Family Operating System',
        'product' => 'Product',
        'pricing' => 'Pricing',
        'demo' => 'Demo',
        'legal' => 'Legal',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'built_by' => 'Built by Jesus Guerrero — Released under the BSD-3-Clause License',
    ],

    'pricing' => [
        'meta_title' => 'Pricing — Loger',
        'meta_description' => 'Loger is free for personal use. The Plus plan is targeted at users in the Dominican Republic with localized banking features.',
        'title' => 'Simple, honest pricing',
        'subtitle' => 'Free for everyone, everywhere. Plus adds bank-aware features tuned for the Dominican Republic.',
        'free' => [
            'name' => 'Free',
            'price' => '$0',
            'cadence' => 'forever',
            'tagline' => 'Everything you need to budget — works anywhere in the world.',
            'cta' => 'Get started',
            'features' => [
                'Multi-currency budgets and accounts',
                'Manual transaction entry and PDF statement import',
                'Meal planning and recipes',
                'Household task and bill tracking',
                'Unlimited categories, payees, and goals',
            ],
        ],
        'plus' => [
            'name' => 'Plus',
            'price' => 'RD$299',
            'cadence' => '/ month',
            'tagline' => 'Built for the Dominican Republic — local banks, local context.',
            'cta' => 'Start Plus',
            'badge' => 'For 🇩🇴 Dominican Republic',
            'features' => [
                'Everything in Free',
                'Optimized BHD bank statement parsing',
                'Local payee auto-categorization',
                'Multi-account credit card billing cycles',
                'Priority support in Spanish',
                'Early access to new features',
            ],
        ],
        'note' => 'Plus is currently in early access for Dominican Republic users. Contact us if you\'d like local support added for your country.',
    ],

    'errors' => [
        '404_title' => 'Page not found',
        '404_body' => 'The page you\'re looking for doesn\'t exist or has moved.',
        '404_cta' => 'Back to home',
    ],
];
