export const SUPPORTED_CURRENCIES = [
  { code: 'USD', name: 'US Dollar', symbol: '$' },
  { code: 'DOP', name: 'Dominican Peso', symbol: 'RD$' },
  { code: 'EUR', name: 'Euro', symbol: '€' },
  { code: 'GBP', name: 'British Pound', symbol: '£' },
  { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$' },
  { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
  { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
  { code: 'CHF', name: 'Swiss Franc', symbol: 'CHF' },
  { code: 'CNY', name: 'Chinese Yuan', symbol: '¥' },
  { code: 'SEK', name: 'Swedish Krona', symbol: 'kr' },
  { code: 'NOK', name: 'Norwegian Krone', symbol: 'kr' },
  { code: 'MXN', name: 'Mexican Peso', symbol: '$' },
  { code: 'BRL', name: 'Brazilian Real', symbol: 'R$' },
  { code: 'INR', name: 'Indian Rupee', symbol: '₹' },
  { code: 'KRW', name: 'South Korean Won', symbol: '₩' }
];

export const getCurrencyByCode = (code: string) => {
  return SUPPORTED_CURRENCIES.find(currency => currency.code === code);
};

export const getCurrencyOptions = () => {
  return SUPPORTED_CURRENCIES.map(currency => ({
    value: currency.code,
    label: `${currency.code} - ${currency.name}`,
    symbol: currency.symbol
  }));
};

export const formatCurrency = (amount: number, currencyCode: string) => {
  const currency = getCurrencyByCode(currencyCode);
  if (!currency) return amount.toString();
  
  try {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: currencyCode,
      currencyDisplay: "symbol"
    }).format(amount);
  } catch (err) {
    return `${currency.symbol}${amount.toFixed(2)}`;
  }
};
// ---------------------------------------------------------------------------
// Locale-aware default currency
// ---------------------------------------------------------------------------
// Onboarding used to hardcode USD, so a Dominican family (the primary market)
// landed on US Dollar and had to fix it by hand — friction on the very first
// screen. We infer a sensible default from the browser instead: the region in
// the language tag (es-DO -> DO) first, then the IANA timezone as a fallback
// for locales that omit a region (plain "es"). Only currencies we actually
// support are returned; anything unknown falls back to USD so behaviour never
// regresses. This is a *default*, not a lock — the field stays editable.

const REGION_CURRENCY: Record<string, string> = {
  DO: 'DOP', US: 'USD', MX: 'MXN', GB: 'GBP', CA: 'CAD', JP: 'JPY',
  AU: 'AUD', CH: 'CHF', CN: 'CNY', SE: 'SEK', NO: 'NOK', BR: 'BRL',
  IN: 'INR', KR: 'KRW',
  // Eurozone members map to EUR
  ES: 'EUR', DE: 'EUR', FR: 'EUR', IT: 'EUR', PT: 'EUR', NL: 'EUR',
  IE: 'EUR', AT: 'EUR', BE: 'EUR', FI: 'EUR', GR: 'EUR', LU: 'EUR',
};

// Minimal IANA timezone -> region map, focused on the app's realistic markets.
// Used only when the locale carries no region.
const TIMEZONE_REGION: Record<string, string> = {
  'America/Santo_Domingo': 'DO',
  'America/New_York': 'US', 'America/Chicago': 'US', 'America/Denver': 'US',
  'America/Los_Angeles': 'US', 'America/Phoenix': 'US', 'America/Anchorage': 'US',
  'Pacific/Honolulu': 'US',
  'America/Mexico_City': 'MX', 'America/Monterrey': 'MX', 'America/Cancun': 'MX',
  'America/Tijuana': 'MX',
  'America/Sao_Paulo': 'BR', 'America/Bahia': 'BR', 'America/Fortaleza': 'BR',
  'America/Recife': 'BR',
  'America/Toronto': 'CA', 'America/Vancouver': 'CA',
  'Europe/London': 'GB',
  'Europe/Madrid': 'ES', 'Europe/Paris': 'FR', 'Europe/Berlin': 'DE',
  'Europe/Rome': 'IT', 'Europe/Lisbon': 'PT', 'Europe/Amsterdam': 'NL',
  'Europe/Dublin': 'IE', 'Europe/Zurich': 'CH', 'Europe/Stockholm': 'SE',
  'Europe/Oslo': 'NO',
  'Asia/Tokyo': 'JP', 'Asia/Shanghai': 'CN', 'Asia/Seoul': 'KR',
  'Asia/Kolkata': 'IN',
  'Australia/Sydney': 'AU', 'Australia/Melbourne': 'AU',
};

const detectRegion = (): string | null => {
  try {
    const langs =
      typeof navigator !== 'undefined'
        ? (navigator.languages && navigator.languages.length
            ? navigator.languages
            : [navigator.language])
        : [];
    for (const tag of langs) {
      if (!tag) continue;
      const region = new Intl.Locale(tag).region;
      if (region) return region.toUpperCase();
    }
  } catch {
    /* Intl.Locale unsupported — fall through to timezone */
  }
  try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (tz && TIMEZONE_REGION[tz]) return TIMEZONE_REGION[tz];
  } catch {
    /* noop */
  }
  return null;
};

// Best-guess default currency for first-run onboarding. Falls back to `fallback`
// (USD) whenever we can't confidently map the browser to a supported currency.
export const guessDefaultCurrency = (fallback = 'USD'): string => {
  const region = detectRegion();
  const code = region ? REGION_CURRENCY[region] : undefined;
  return code && getCurrencyByCode(code) ? code : fallback;
};
