export type PageKind = "note" | "task" | "event";
export type BoardView = "notebook" | "kanban" | "list";
export type ProfilePage = {
    id: string;
    title: string;
    body: string;
    kind: PageKind;
    done: boolean;
    when?: string;
};
export type ProfileStage = { id: string; name: string; items: ProfilePage[] };
export type ProfileBoard = {
    id: string;
    name: string;
    description: string;
    view: BoardView;
    color: string;
    stages: ProfileStage[];
};

export const pagesOf = (board: ProfileBoard): ProfilePage[] =>
    board.stages.flatMap((stage) => stage.items);
export const pendingPages = (boards: ProfileBoard[]): ProfilePage[] =>
    boards
        .flatMap(pagesOf)
        .filter((page) => page.kind === "task" && !page.done);
