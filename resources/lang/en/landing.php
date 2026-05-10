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
        'headline_a' => 'Your household runs on chaos.',
        'headline_b' => 'Replace the chaos.',
        'subhead' => 'One place for the budget, the bills, the meal plan, and the family calendar — built for households whose bank doesn\'t sync with American budgeting apps.',
        'cta_primary' => 'Start free — under 5 min',
        'cta_secondary' => 'See it in action',
        'reassurance' => 'No credit card · Works with any bank · DOP, USD, EUR & more',
    ],

    'social_bar' => [
        'built_in' => 'Built in 🇩🇴 the Dominican Republic',
        'open_source' => 'Open-source engine — Atmosphere on GitHub',
        'license' => 'BSD-3 licensed · self-host it free',
        'made_by' => 'Made by a household, for households',
    ],

    'problem' => [
        'eyebrow' => 'Why Loger exists',
        'line_1' => 'Your bank isn\'t on Plaid.',
        'line_2' => 'Your spouse uses a different one.',
        'line_3' => 'The kids\' school charges in DOP.',
        'body_1' => 'Most household apps were built for a US family with one Chase account.',
        'body_2' => 'Loger was built for the rest of us.',
    ],

    'why_diff' => [
        'eyebrow' => 'Why Loger',
        'title' => 'The home isn\'t a budget. It\'s a system.',
        'body' => 'Most apps assume you have a US bank with automatic import — and they stop at money. Loger goes further.',
        'cells' => [
            'institution' => [
                'title' => 'Any institution',
                'body' => 'No Plaid, no US-only sync. PDF import, manual entry, CSV.',
            ],
            'currency' => [
                'title' => 'Multi-currency native',
                'body' => 'DOP, USD, EUR, MXN side-by-side. No conversion gymnastics.',
            ],
            'beyond' => [
                'title' => 'Beyond money',
                'body' => 'Meals, chores, bills, family — the actual work of running a home.',
            ],
            'oss' => [
                'title' => 'Open-source engine',
                'body' => 'Loger runs on Atmosphere. BSD-3 on GitHub. Self-host or let us host.',
            ],
        ],
    ],

    'plus_banner' => [
        'eyebrow' => 'For 🇩🇴 Dominican Republic',
        'title' => 'Bank-aware features for users in the DR',
        'body' => 'Loger Plus auto-categorizes BHD payees, parses bank statements, and handles multi-account credit-card billing cycles — RD$299/month, free for 30 days.',
        'cta' => 'Try Plus free for 30 days',
        'note_other' => 'Not in the DR? Plus is rolling out country by country — tell us where you bank to vote for the next country.',
        'cta_other' => 'Tell us where you bank',
    ],

    'atmosphere' => [
        'eyebrow' => 'How Loger is built',
        'title' => 'Loger is hosted Atmosphere.',
        'body_1' => 'Atmosphere is our open-source household OS — BSD-3, on GitHub. Anyone can fork it and self-host. Loger is the distribution we run for you, with bank-aware features and managed updates.',
        'body_2' => 'Same code, two ways to use it. Like Chromium and Chrome.',
        'cta_self_host' => 'Self-host on GitHub',
        'cta_signup' => 'Or just sign up free',
    ],

    'faq' => [
        'eyebrow' => 'FAQ',
        'title' => 'Common questions',
        'subtitle' => 'If yours isn\'t here, write to us.',
        'items' => [
            [
                'q' => 'What if my bank isn\'t supported?',
                'a' => 'All banks are supported. Loger doesn\'t depend on bank APIs — drop in a PDF statement or enter transactions manually. We don\'t connect to your bank, so we don\'t even see credentials.',
            ],
            [
                'q' => 'Is my financial data safe?',
                'a' => 'Your data stays in your account, encrypted at rest, never sold. The engine is open source on GitHub, so you can read exactly what Loger does with your data — or self-host it if you\'d rather not trust us at all.',
            ],
            [
                'q' => 'Can I use Loger on my phone?',
                'a' => 'Yes — Loger is a responsive web app that works on any device. You can add it to your home screen on iOS and Android for an app-like experience.',
            ],
            [
                'q' => 'What happens if I cancel Plus?',
                'a' => 'You keep all your data and downgrade to Free. Nothing is locked behind the paywall except the DR-specific bank parsing and a few automation features. Your budget, history, and accounts stay yours.',
            ],
            [
                'q' => 'Do I need all five pillars?',
                'a' => 'No — enable only what your household uses. Most people start with Finance and add Calendar. You can turn pillars on or off any time.',
            ],
            [
                'q' => 'Can my partner or family see this too?',
                'a' => 'Yes — household teams let you share access with the people you live with. Each member gets their own profile, and you choose who sees what.',
            ],
        ],
    ],

    'pillars_caveat' => 'Enable only the pillars your household needs.',

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
                'Equipment invento