const activeLocks = new Set<symbol>();
let originalBodyOverflow = "";
let originalRootOverflow = "";

export const lockModalScroll = (token: symbol): void => {
    if (!activeLocks.size) {
        originalBodyOverflow = document.body.style.overflow;
        originalRootOverflow = document.documentElement.style.overflow;
    }
    activeLocks.add(token);
    document.body.style.overflow = "hidden";
    document.documentElement.style.overflow = "hidden";
};

export const unlockModalScroll = (token: symbol): void => {
    if (activeLocks.delete(token) && !activeLocks.size) {
        document.body.style.overflow = originalBodyOverflow;
        document.documentElement.style.overflow = originalRootOverflow;
    }
};
