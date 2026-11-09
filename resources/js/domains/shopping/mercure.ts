/**
 * The Laravel Mercure broadcaster wraps every update as
 * `{ event: "App\\Events\\…", data: { …broadcastWith() } }`. Returns the inner
 * payload, or the message itself when it is already unwrapped.
 */
export const unwrapMercureMessage = <T = Record<string, any>>(raw: string): T | null => {
    const message = JSON.parse(raw);
    if (!message || typeof message !== "object") return null;

    return (message.data && typeof message.data === "object" ? message.data : message) as T;
};
