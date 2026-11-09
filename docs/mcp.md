# Loger MCP (Claude Code)

Loger exposes a remote MCP server at `POST /mcp` so Claude Code can read and record the finances of the token owner's current household.

## Connect

1. On the server (Coolify → `loger` container → Terminal):

   ```bash
   php artisan loger:mcp-token you@example.com            # read + write
   php artisan loger:mcp-token you@example.com --read-only
   php artisan loger:mcp-token you@example.com --revoke
   ```

   The token is printed once, together with the `claude mcp add` line. Running the command again replaces the previous token.

2. On your machine:

   ```bash
   claude mcp add --transport http loger https://<domain>/mcp --header "Authorization: Bearer <token>"
   claude mcp list
   ```

Only tokens created by this command work (ability `mcp`). Mobile and API tokens are rejected, so revoking one never signs out the other.

## Tools

| Tool | Access | Answers |
| --- | --- | --- |
| `get_month_brief` | read | «¿Cómo voy este mes?» |
| `get_spending_by_category` | read | «¿Cuánto llevo en supermercado?» |
| `list_transactions` | read | «¿Cuándo pagué Claro?» |
| `get_upcoming_payments` | read | «¿Qué pagos vienen esta semana?» |
| `list_accounts_and_categories` | read | Turns names into ids |
| `create_transaction` | write (`mcp:write`) | «Registra RD$2,400 de Claro en Teléfono» |
| `mark_payment_paid` | write (`mcp:write`) | «Marca el alquiler como pagado» |

Everything is scoped to the household and time zone of the token owner. The server allows 60 requests per minute.
