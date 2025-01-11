export const PANEL_SIZES = {
    small: "md:w-5/12 lg:w-3/12",
    large: 'md:w-5/12'
}

export const priorityColors = {

}

export const matrixColors = {
    red: 'bg-red-500 text-white',
    blue: 'bg-blue-500 text-white',
    green: 'bg-green-500 text-white',
    yellow: 'bg-yellow-500 text-white'
}


export const URL = 'https://[hostname].laciadata.com'
export const URL_FRONT = 'https://[hostname].laciahub.com'
export const ENDPOINTS = {
  getTenantStyle: '/api/v01/look-and-feel/configurationStyleTenant',
  getFeatureFlags: '/api/v01/company/get-company-setting-value',
  validateApprovalToken: '/api/v01/task/mark-as-used-task-approvals-signature-token',
  requestAssistantToken: '/api/v01/assistant/request-token',
  readBarcode: '/api/v01/reader/barcode',
  readBraillecode: '/api/v01/reader/converter/img/brailletotext'
}

export const THEME_FINI = {
  primary: '#3450f9',
  userDefaultColor: '#3450f9',
  userDefaultColor_rgb: '',
  tertiary: '#feb4aa',
  brandGrayExtralightSecondary: '#ededed',
  brandGrayExtralightSecondary_rgb: '',
  brandGrayExtralight: '#a6a6a6',
  danger: '#d30022',
  primaryDark: '#1a287d',
  primaryLight: '#dfe4ff',
  secondary: '#e790e7',
  secondaryDark: '#b973b9',
  secondaryLight: '#f5d3f5',
  secondaryLight_rgb: '245, 211, 245',
}

export const mocks = [
  {
      "page": "1",
      "coords": {
          "x": "1",
          "y": "52"
      },
      "text": "gestion",
      "suggestion": "gestión",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "4",
          "y": "29"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "10",
          "y": "51"
      },
      "text": "rapido",
      "suggestion": "rápido",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "11",
          "y": "40"
      },
      "text": "proximos",
      "suggestion": "próximos",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "14",
          "y": "11"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "15",
          "y": "11"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "16",
          "y": "11"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "17",
          "y": "11"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "21",
          "y": "16"
      },
      "text": "Facturacion",
      "suggestion": "Facturación",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "23",
          "y": "16"
      },
      "text": "escolar,imprimir",
      "suggestion": "escolar, imprimir",
      "explanation": "falta espacio"
  },
  {
      "page": "1",
      "coords": {
          "x": "26",
          "y": "16"
      },
      "text": "hacia",
      "suggestion": "haría",
      "explanation": "error tipográfico"
  },
  {
      "page": "1",
      "coords": {
          "x": "27",
          "y": "16"
      },
      "text": "requerira",
      "suggestion": "requerirá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "27",
          "y": "28"
      },
      "text": "informacion",
      "suggestion": "información",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "27",
          "y": "33"
      },
      "text": "digitalizarse",
      "suggestion": "digitalizarse.",
      "explanation": "falta punto"
  },
  {
      "page": "1",
      "coords": {
          "x": "34",
          "y": "16"
      },
      "text": "Instalacion",
      "suggestion": "Instalación",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "34",
          "y": "30"
      },
      "text": "Configuracion",
      "suggestion": "Configuración",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "34",
          "y": "45"
      },
      "text": "capacitacion",
      "suggestion": "capacitación",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "37",
          "y": "16"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "38",
          "y": "16"
      },
      "text": "capacitacion",
      "suggestion": "capacitación",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "39",
          "y": "16"
      },
      "text": "taza",
      "suggestion": "tasa",
      "explanation": "error de palabra"
  },
  {
      "page": "1",
      "coords": {
          "x": "43",
          "y": "16"
      },
      "text": "Soporte Tecnico",
      "suggestion": "Soporte Técnico",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "43",
          "y": "34"
      },
      "text": "correxion",
      "suggestion": "corrección",
      "explanation": "error tipográfico"
  },
  {
      "page": "1",
      "coords": {
          "x": "45",
          "y": "16"
      },
      "text": "creacion",
      "suggestion": "creación",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "46",
          "y": "16"
      },
      "text": "implicaria",
      "suggestion": "implicaría",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "48",
          "y": "16"
      },
      "text": "implementacion",
      "suggestion": "implementación",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "50",
          "y": "16"
      },
      "text": "seria",
      "suggestion": "sería",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "54",
          "y": "16"
      },
      "text": "gestion",
      "suggestion": "gestión",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "58",
          "y": "16"
      },
      "text": "podra",
      "suggestion": "podrá",
      "explanation": "falta tilde"
  },
  {
      "page": "1",
      "coords": {
          "x": "60",
          "y": "16"
      },
      "text": "acordaran",
      "suggestion": "acordarán",
      "explanation": "falta tilde"
  }
]

export const mockFiles = [
    {
    "id": 351,
    "cloudflow_url": "cloudflow://CLIENTES/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
    "asset_file_thumbnail": "http://lacia.com/lf/faes/material-acondicionamiento-impreso.png",
    "asset_file_name": "2024-09-06-103628_render-multi.pdf",
    "asset_file_alias": "render_multi",
    "asset_file_extension": ".pdf",
    "asset_file_path": "/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
    "asset_file_type": "file",
    "asset_file_size": "405KB",
    "asset_file_version": "1.0",
    "asset_file_history": [
      {
        "id": 351,
        "cloudflow_url": "cloudflow://CLIENTES/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
        "asset_file_name": "2024-09-06-103628_render-multi.pdf",
        "asset_file_alias": "render_multi",
        "tags_files_id": "11",
        "asset_file_extension": ".pdf",
        "asset_file_version": "1.0",
        "version_type": "Major",
      }
    ],
    },
    {
        "id": 351,
        "tags_files_id": "11",
        "asset_cf_uuid": "af-53a99223350b1fbd188bb34ea5e05b86",
        "cloudflow_url": "cloudflow://CLIENTES/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
        "asset_file_thumbnail": "http://lacia.com/lf/faes/material-acondicionamiento-impreso.png",
        "asset_file_name": "2024-09-06-103628_render-multi.pdf",
        "asset_file_alias": "render_multi",
        "asset_file_tag": null,
        "asset_file_extension": ".pdf",
        "asset_file_path": "/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
        "asset_file_type": "file",
        "asset_file_size": "405KB",
        "asset_file_version": "1.0",
        "asset_file_history": [
          {
            "file_id": 351,
            "author": {
              "id": 78,
              "employee_name": "Admin",
              "employee_surname_1": "Data",
              "full_name": "Admin Data",
              "positions": [
                {
                  "position_name": "Technical T.I Applications"
                }
              ]
            },
            "uuid": "4c078791-1453-423b-8e49-a99bf10920f4",
            "cloudflow_url": "cloudflow://CLIENTES/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
            "asset_file_name": "2024-09-06-103628_render-multi.pdf",
            "asset_file_alias": "render_multi",
            "tags_files_id": "11",
            "asset_file_extension": ".pdf",
            "asset_file_path": "/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
            "asset_file_type": "file",
            "asset_file_size": "405KB",
            "asset_file_version": "1.0",
            "version_type": "Major",
            "created_date": "2024-09-06T10:36:35.696373Z",
            "file_event": "create",
            "event_dt": "2024-09-06 10:36:35",
            "event_by": 78
          }
        ],
        "created_at": "2024-09-06T10:36:35.000000Z",
        "updated_at": null,
        "author_id": "78",
        "transactions_id": null,
        "is_rendered": "0",
        "deleted_at": null,
        "assets_id": "195",
        "created_by": "78",
        "updated_by": null,
        "deleted_by": null,
        "motive": null,
        "comment": null,
        "original_file_id": null,
        "is_file_copy": "0",
        "is_published": true,
        "pivot": {
          "assets_id": "195",
          "assets_files_id": "351"
        },
        "tag_file": {
          "id": 11,
          "assets_types_id": "6",
          "name": "Aw Trazado",
          "tag_multiple": "0",
          "tag_mandatory": "0",
          "accept_thumbnail": null,
          "created_at": null,
          "updated_at": null,
          "deleted_at": null
        }
    },
    {
        "id": 351,
        "tags_files_id": "11",
        "asset_cf_uuid": "af-53a99223350b1fbd188bb34ea5e05b86",
        "cloudflow_url": "cloudflow://CLIENTES/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
        "asset_file_thumbnail": "http://lacia.com/lf/faes/material-acondicionamiento-impreso.png",
        "asset_file_name": "2024-09-06-103628_render-multi.pdf",
        "asset_file_alias": "render_multi",
        "asset_file_tag": null,
        "asset_file_extension": ".pdf",
        "asset_file_path": "/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
        "asset_file_type": "file",
        "asset_file_size": "405KB",
        "asset_file_version": "1.0",
        "asset_file_history": [
          {
            "file_id": 351,
            "author": {
              "id": 78,
              "employee_name": "Admin",
              "employee_surname_1": "Data",
              "full_name": "Admin Data",
              "positions": [
                {
                  "position_name": "Technical T.I Applications"
                }
              ]
            },
            "uuid": "4c078791-1453-423b-8e49-a99bf10920f4",
            "cloudflow_url": "cloudflow://CLIENTES/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
            "asset_file_name": "2024-09-06-103628_render-multi.pdf",
            "asset_file_alias": "render_multi",
            "tags_files_id": "11",
            "asset_file_extension": ".pdf",
            "asset_file_path": "/qa-oqotech/727970/2024-09-06-103628_render-multi.pdf",
            "asset_file_type": "file",
            "asset_file_size": "405KB",
            "asset_file_version": "1.0",
            "version_type": "Major",
            "created_date": "2024-09-06T10:36:35.696373Z",
            "file_event": "create",
            "event_dt": "2024-09-06 10:36:35",
            "event_by": 78
          }
        ],
        "created_at": "2024-09-06T10:36:35.000000Z",
        "updated_at": null,
        "author_id": "78",
        "transactions_id": null,
        "is_rendered": "0",
        "deleted_at": null,
        "assets_id": "195",
        "created_by": "78",
        "updated_by": null,
        "deleted_by": null,
        "motive": null,
        "comment": null,
        "original_file_id": null,
        "is_file_copy": "0",
        "is_published": true,
        "pivot": {
          "assets_id": "195",
          "assets_files_id": "351"
        },
        "tag_file": {
          "id": 11,
          "assets_types_id": "6",
          "name": "Aw Trazado",
          "tag_multiple": "0",
          "tag_mandatory": "0",
          "accept_thumbnail": null,
          "created_at": null,
          "updated_at": null,
          "deleted_at": null
        }
    }
]
