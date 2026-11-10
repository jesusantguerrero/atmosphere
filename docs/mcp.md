# Loger MCP (Claude Code)

Loger exposes a remote MCP server at `POST /mcp` so Claude Code can read and record the finances of a user's current household. Access uses OAuth (Laravel Passport): no server access or manual tokens are needed.

## Connect (any Loger user)

```bash
claude mcp add --transport http loger https://<domain>/mcp
```

Claude Code opens the browser. Log into Loger, review what Claude Code asks for and press **Authorize**. To disconnect, go to **Settings → Security → Connected apps**.

| Scope | Lets the app |
| --- | --- |
| `mcp:read` | Read the household finances (default) |
| `mcp:write` | Create transactions, import bank statements and planner items, and mark payments as paid |

Tokens last 30 days and refresh for up to 90.

## Tools

| Tool | Scope | Answers |
| --- | --- | --- |
| `get_month_brief` | read | «¿Cómo voy este mes?» |
| `get_spending_by_category` | read | «¿Cuánto llevo en supermercado?» |
| `list_transactions` | read | «¿Cuándo pagué Claro?» |
| `get_upcoming_payments` | read | «¿Qué pagos vienen esta semana?» |
| `list_accounts_and_categories` | read | Turns names into ids |
| `create_transaction` | write | «Registra RD$2,400 de Claro en Teléfono» |
| `mark_payment_paid` | write | «Marca el alquiler como pagado» |
| `add_planner_items` | write | «Importa este calendario del colegio» |
| `import_bank_statement` | write | «Importa este estado de cuenta del BHD a Personal» (rows land as drafts in the Bandeja; duplicates are skipped) |

## How it works

- `/.well-known/oauth-protected-resource/mcp` and `/.well-known/oauth-authorization-server` describe the server (RFC 9728 / RFC 8414).
- Claude Code registers itself at `POST /oauth/register` (dynamic client registration, throttled) and runs the authorization-code flow with PKCE on `/oauth/authorize`.
- Mobile keeps its Sanctum tokens; they are not accepted on `/mcp`.

## Deploy (once)

Generate a key pair and set it in Coolify's environment for every service (`loger`, `task`, `queue`):

```bash
openssl genrsa -out oauth-private.key 4096
openssl rsa -in oauth-private.key -pubout -out oauth-public.key
```

```
PASSPORT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

Migrations run on deploy (`AUTORUN_ENABLED`) and create the `oauth_*` tables.
