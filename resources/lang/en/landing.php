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
                'a' => 'Your data is yours: never sold, never used for ads. The engine is open source on GitHub, so you can read exactly what Loger does with your data — or self-host it on your own server if you\'d rather not trust us at all.',
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
        'title' => 'A note from the founder',
        'subtitle' => 'Loger is a small, honest project. Here\'s why I\'m building it.',
        'founder_quote' => 'I built Loger because I was running my own household across four currencies, three banks, two languages, and a half-dozen apps. None of them worked together. Loger is what I wished existed — and Atmosphere, the open-source engine underneath, is yours to use too.',
        'founder_name' => 'Jesus Guerrero',
        'founder_role' => 'Solo founder · Built in the DR',
        'cta' => 'Share your story',
    ],

    'why' => [
        'title' => 'The home isn\'t a budget. It\'s a system.',
        'body' => 'Most apps assume you have a US bank with automatic import — and they stop at money. Loger goes further: it works with any institution, supports multiple currencies natively, and reaches beyond money into the relational and logistical work of running a household. One system, instead of half a dozen apps.',
    ],

    'final_cta' => [
        'title' => 'Ready to run your house like a system?',
        'subtitle' => 'Free forever for personal use. No card. No Plaid. No spreadsheets.',
        'cta' => 'Create my free Loger',
    ],

    'footer' => [
        'tagline' => 'The Family Operating System',
        'product' => 'Product',
        'pricing' => 'Pricing',
        'demo' => 'Demo',
        'legal' => 'Legal',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'open_source' => 'Open Source',
        'atmosphere' => 'Atmosphere on GitHub',
        'self_host' => 'Self-host it free',
        'open_source_page' => 'About our OSS',
        'powered_by' => 'Loger runs on Atmosphere — our open-source household OS engine.',
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

    'open_source' => [
        'meta' => [
            'title' => 'Open Source — Loger runs on Atmosphere',
            'description' => 'Loger is the hosted distribution of Atmosphere, our open-source household OS. BSD-3 licensed, on GitHub. Self-host the engine, or let us run it for you.',
        ],
        'hero' => [
            'eyebrow' => 'Open source',
            'title' => 'Loger runs on Atmosphere. Atmosphere is open source.',
            'subhead_1' => 'Atmosphere is the household OS engine — BSD-3, on GitHub, anyone can fork it and self-host.',
            'subhead_2' => 'Loger is the distribution we run for you, with bank-aware features and managed updates. Same code, two ways to use it.',
            'cta_signup' => 'Sign up for Loger',
            'cta_github' => 'Star Atmosphere on GitHub',
        ],
        'split' => [
            'eyebrow' => 'Why the split',
            'title' => 'Two products, one codebase.',
            'subtitle' => 'We took the WordPress.org / WordPress.com path on purpose. Here\'s what each side gives you.',
            'engine' => [
                'tag' => 'Atmosphere',
                'title' => 'The engine',
                'body' => 'BSD-3 licensed. Public on GitHub. Anyone can fork it, audit it, extend it, or run it on their own infrastructure — no permission needed, no fees, no lock-in.',
            ],
            'distribution' => [
                'tag' => 'Loger',
                'title' => 'The hosted distribution',
                'body' => 'We host Atmosphere for you, keep it patched, and add bank-aware features (BHD parsing, DOP/USD multi-currency billing cycles, managed backups). You sign up, we run it.',
            ],
            'same_code' => [
                'tag' => 'Same code',
                'title' => 'Two ways to use it',
                'body' => 'Like Chromium and Chrome. The engine is the open-source project. The distribution is the polished, managed product on top of it. You pick what fits.',
            ],
        ],
        'when' => [
            'eyebrow' => 'How to choose',
            'title' => 'Self-host or hosted — both are honest options.',
            'self_host' => [
                'title' => 'Self-host Atmosphere if…',
                'items' => [
                    'You\'re a developer comfortable running a Laravel app',
                    'You want full control of your data and infrastructure',
                    'You want to extend or fork the engine for your own household',
                    'You\'d rather not trust a hosted service with your finances — BSD-3 lets you walk away',
                ],
            ],
            'hosted' => [
                'title' => 'Use Loger if…',
                'items' => [
                    'You want it to just work — no servers, no deploys',
                    'You want managed updates, backups and uptime',
                    'You\'re in the DR and want the BHD bank-aware features',
                    'You\'d rather pay a small subscription than run a Laravel app yourself',
                ],
            ],
            'note' => 'Both run the same engine. You can move between them — your data is yours.',
        ],
        'getting_started' => [
            'eyebrow' => 'How to get started',
            'title' => 'Pick your path.',
            'self_host' => [
                'title' => 'Self-host Atmosphere',
                'body' => 'Clone the repo, follow the README, and run it on your own server. Free, BSD-3, no strings.',
                'code' => 'git clone https://github.com/jesusantguerrero/atmosphere.git',
                'cta' => 'Open the repo on GitHub',
            ],
            'hosted' => [
                'title' => 'Sign up for Loger',
                'body' => 'Skip the setup. Free for personal use, with optional Plus features for users in the DR.',
                'cta' => 'Create a free account',
            ],
        ],
        'closing' => [
            'title' => 'Our code is yours, whether you run it or we do.',
            'cta' => 'Sign up free',
        ],
    ],

    'errors' => [
        '404_title' => 'Page not found',
        '404_body' => 'The page you\'re looking for doesn\'t exist or has moved.',
        '404_cta' => 'Back to home',
    ],
];
