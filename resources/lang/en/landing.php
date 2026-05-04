<?php

return [
    'meta' => [
        'title' => 'Loger — Personal Finance Without US Banking',
        'description' => 'Loger is a personal finance and home management app built for people outside the US banking system. Multi-currency budgets, PDF statement import, meal planning and household tracking — all in one place.',
        'og_title' => 'Loger — Multi-currency personal finance for the rest of the world',
        'og_description' => 'Track budgets, reconcile statements, plan meals and manage your home. No US bank required.',
    ],

    'nav' => [
        'pricing' => 'Pricing',
        'demo' => 'Demo',
        'login' => 'Login',
        'sign_up' => 'Sign up free',
    ],

    'hero' => [
        'badge' => 'Personal finance, reimagined',
        'headline_a' => 'Personal finance built for',
        'headline_b' => 'the rest of the world',
        'subhead' => 'Most apps assume you have a US bank with automatic import. Loger works with any institution, supports multiple currencies natively, and keeps you in control of your data.',
        'cta_primary' => 'Get started — it\'s free',
        'cta_secondary' => 'See it in action',
        'reassurance' => 'No credit card required · Free forever for personal use',
    ],

    'features' => [
        'finance' => [
            'title' => 'Personal Finance',
            'description' => 'Full budget management with transaction tracking, account reconciliation, and multi-currency support. Works with any bank, anywhere in the world.',
            'items' => [
                'Budgets and spending categories',
                'Multi-currency account balances',
                'Bank statement reconciliation',
                'PDF statement import',
            ],
        ],
        'meals' => [
            'title' => 'Meal Planning',
            'description' => 'Plan weekly meals, manage recipes, and track ingredients. Connect food spending directly to your household budget for a complete picture.',
            'items' => [
                'Recipe and ingredient library',
                'Weekly meal calendar',
                'Grocery cost tracking',
                'Food budget integration',
            ],
        ],
        'housing' => [
            'title' => 'Housing Management',
            'description' => 'Keep your home organized. Track recurring bills, services, and household tasks all in one place alongside your finances.',
            'items' => [
                'Recurring bill tracking',
                'Home service records',
                'Household task management',
                'Utility expense history',
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
        'title' => 'Built for the rest of the world',
        'body' => 'Most personal finance apps assume you have a US bank account with automatic import. Loger is different — it works with any financial institution, supports multiple currencies natively, and puts you in control of your own data.',
    ],

    'final_cta' => [
        'title' => 'Ready to take control?',
        'subtitle' => 'Create a free account and start managing your finances today.',
        'cta' => 'Create free account',
    ],

    'footer' => [
        'tagline' => 'Personal finance for the rest of the world',
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
