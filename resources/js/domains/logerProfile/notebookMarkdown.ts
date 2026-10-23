export const markdownBlocks = (source: string) => {
    const blocks: {
        tag: string;
        text?: string;
        items?: string[];
        start?: number;
    }[] = [];
    for (const line of source.replace(/\r\n/g, "\n").split("\n")) {
        const heading = line.match(/^(#{1,6})\s+(.+)$/);
        const list = line.match(/^\s*(?:([-*])\s+|(\d+)\.\s+)(.+)$/);
        if (heading) {
            blocks.push({ tag: `h${heading[1].length}`, text: heading[2] });
        } else if (list) {
            const tag = list[2] ? "ol" : "ul";
            const previous = blocks[blocks.length - 1];
            if (previous?.tag === tag) previous.items?.push(list[3]);
            else
                blocks.push({
                    tag,
                    items: [list[3]],
                    start: Number(list[2]) || undefined,
                });
        } else if (line.trim()) {
            blocks.push({ tag: "p", text: line });
        } else {
            blocks.push({ tag: "break" });
        }
    }
    return blocks.filter((block) => block.tag !== "break");
};

export const markdownInline = (text: string) =>
    text
        .split(/(\[\[[^\]\n]+\]\]|\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/g)
        .filter(Boolean)
        .map((part) => ({
            tag: part.startsWith("[[")
                ? "link"
                : part.startsWith("**")
                  ? "strong"
                  : part.startsWith("*")
                    ? "em"
                    : part.startsWith("`")
                      ? "code"
                      : "span",
            text: part.startsWith("[[")
                ? part.slice(2, -2)
                : part.startsWith("**")
                  ? part.slice(2, -2)
                  : /^[*`]/.test(part)
                    ? part.slice(1, -1)
                    : part,
        }));
