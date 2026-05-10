<?php

return [
    'meta' => [
        'title' => 'Loger — El Sistema Operativo del Hogar',
        'description' => 'Loger es una sola app para correr la maquinaria financiera, logística y relacional de tu hogar. Presupuesto, comidas, tareas, agenda familiar, mantenimiento — todo lo que hoy llevas en la cabeza, en hojas de Excel o en seis apps distintas, en un solo sistema.',
        'og_title' => 'Loger — El Sistema Operativo del Hogar',
        'og_description' => 'Presupuesto, comidas, tareas, familia, calendario. Maneja tu casa como funciona de verdad — como un sistema, no como una hoja de cálculo.',
    ],

    'nav' => [
        'pricing' => 'Precios',
        'demo' => 'Demo',
        'login' => 'Iniciar sesión',
        'sign_up' => 'Crear cuenta',
    ],

    'hero' => [
        'badge' => 'El Sistema Operativo del Hogar',
        'headline_a' => 'Tu hogar funciona en caos.',
        'headline_b' => 'Reemplaza el caos.',
        'subhead' => 'Un solo lugar para el presupuesto, las facturas, el plan de comidas y la agenda familiar — pensado para hogares cuyo banco no se conecta con las apps de presupuesto gringas.',
        'cta_primary' => 'Empezar gratis — en menos de 5 min',
        'cta_secondary' => 'Ver el demo',
        'reassurance' => 'Sin tarjeta · Funciona con cualquier banco · DOP, USD, EUR y más',
    ],

    'social_bar' => [
        'built_in' => 'Hecho en 🇩🇴 República Dominicana',
        'open_source' => 'Motor open-source — Atmosphere en GitHub',
        'license' => 'Licencia BSD-3 · auto-alójalo gratis',
        'made_by' => 'Hecho por un hogar, para hogares',
    ],

    'problem' => [
        'eyebrow' => 'Por qué existe Loger',
        'line_1' => 'Tu banco no está en Plaid.',
        'line_2' => 'Tu pareja usa otro distinto.',
        'line_3' => 'El colegio de los niños cobra en DOP.',
        'body_1' => 'La mayoría de las apps fueron pensadas para una familia en EE.UU. con una sola cuenta en Chase.',
        'body_2' => 'Loger fue hecho para el resto de nosotros.',
    ],

    'why_diff' => [
        'eyebrow' => 'Por qué Loger',
        'title' => 'El hogar no es un presupuesto. Es un sistema.',
        'body' => 'La mayoría de las apps asumen que tienes un banco en EE.UU. con importación automática — y se quedan en el dinero. Loger va más allá.',
        'cells' => [
            'institution' => [
                'title' => 'Cualquier institución',
                'body' => 'Sin Plaid, sin sync exclusivo de EE.UU. Importación de PDF, entrada manual, CSV.',
            ],
            'currency' => [
                'title' => 'Multi-moneda nativo',
                'body' => 'DOP, USD, EUR, MXN lado a lado. Sin gimnasia de conversiones.',
            ],
            'beyond' => [
                'title' => 'Más allá del dinero',
                'body' => 'Comidas, tareas, facturas, familia — el trabajo real de manejar la casa.',
            ],
            'oss' => [
                'title' => 'Motor open-source',
                'body' => 'Loger corre sobre Atmosphere. BSD-3 en GitHub. Auto-alójalo o que lo hospedemos nosotros.',
            ],
        ],
    ],

    'plus_banner' => [
        'eyebrow' => 'Para 🇩🇴 República Dominicana',
        'title' => 'Funciones bancarias para usuarios en RD',
        'body' => 'Loger Plus auto-categoriza pagadores BHD, lee estados de cuenta y maneja ciclos de tarjeta de crédito multi-cuenta — RD$299/mes, gratis los primeros 30 días.',
        'cta' => 'Probar Plus gratis 30 días',
        'note_other' => '¿No estás en RD? Plus se va expandiendo país por país — dinos dónde tienes tu banco para votar por el próximo.',
        'cta_other' => 'Cuéntanos dónde tienes tu banco',
    ],

    'atmosphere' => [
        'eyebrow' => 'Cómo está hecho Loger',
        'title' => 'Loger es Atmosphere hospedado.',
        'body_1' => 'Atmosphere es nuestro motor open-source para el hogar — BSD-3, en GitHub. Cualquiera puede hacerle fork y auto-alojarlo. Loger es la distribución que corremos por ti, con funciones bancarias y actualizaciones manejadas.',
        'body_2' => 'Mismo código, dos formas de usarlo. Como Chromium y Chrome.',
        'cta_self_host' => 'Auto-alojar en GitHub',
        'cta_signup' => 'O simplemente regístrate gratis',
    ],

    'faq' => [
        'eyebrow' => 'FAQ',
        'title' => 'Preguntas frecuentes',
        'subtitle' => 'Si la tuya no está aquí, escríbenos.',
        'items' => [
            [
                'q' => '¿Y si mi banco no está soportado?',
                'a' => 'Todos los bancos están soportados. Loger no depende de APIs bancarias — sube un PDF del estado de cuenta o entra las transacciones a mano. No nos conectamos a tu banco, así que ni siquiera vemos tus credenciales.',
            ],
            [
                'q' => '¿Mis datos financieros están seguros?',
                'a' => 'Tus datos quedan en tu cuenta, encriptados, nunca se venden. El motor es open-source en GitHub, así que puedes leer exactamente qué hace Loger con tus datos — o auto-alojarlo si prefieres no confiar en nosotros del todo.',
            ],
            [
                'q' => '¿Puedo usar Loger en el celular?',
                'a' => 'Sí — Loger es una web app responsiva que funciona en cualquier dispositivo. Puedes añadirla a la pantalla de inicio en iOS y Android para una experiencia tipo app.',
            ],
            [
                'q' => '¿Qué pasa si cancelo Plus?',
                'a' => 'Te quedas con todos tus datos y bajas a Gratis. Nada queda atrás del paywall salvo el procesamiento bancario específico para RD y algunas automatizaciones. Tu presupuesto, historial y cuentas siguen siendo tuyos.',
            ],
            [
                'q' => '¿Necesito los cinco pilares?',
                'a' => 'No — activa solo lo que tu hogar necesita. Casi todos empiezan con Finanzas y añaden Calendario. Puedes prender o apagar pilares cuando quieras.',
            ],
            [
                'q' => '¿Mi pareja o familia pueden ver esto también?',
                'a' => 'Sí — los equipos del hogar te dejan compartir el acceso con quienes viven contigo. Cada quien tiene su perfil, y tú decides quién ve qué.',
            ],
        ],
    ],

    'pillars_caveat' => 'Activa solo los pilares que tu hogar necesita.',

    'pillars' => [
        'eyebrow' => 'Cinco pilares, un sistema',
        'title' => 'Todo lo que mueve a tu hogar',
        'subtitle' => 'Cinco áreas que activas por equipo. Las finanzas son donde la mayoría de apps se detienen — Loger sigue.',
    ],

    'features' => [
        'finance' => [
            'title' => 'Finanzas',
            'description' => 'Presupuesto por sobres estilo YNAB, multi-moneda de fábrica. Concilia estados de cuenta sin depender de Plaid ni de bancos exclusivos de EE.UU.',
            'items' => [
                'Presupuesto mensual base cero',
                'Cuentas multi-moneda (nativa y convertida)',
                'Importación y conciliación de PDF',
                'Patrimonio neto, metas de ahorro, watchlists',
            ],
        ],
        'meals' => [
            'title' => 'Comida',
            'description' => 'Planifica qué comen, qué hay que comprar y qué ya tienes en la despensa. Conecta cada compra directamente al presupuesto del hogar.',
            'items' => [
                'Recetas, ingredientes y favoritos',
                'Plan semanal y listas de compras',
                'Despensa con fechas de vencimiento',
                'Costo por receta y presupuesto semanal',
            ],
        ],
        'housing' => [
            'title' => 'Hogar',
            'description' => 'Maneja la casa como el activo que es. Tareas, mantenimiento,