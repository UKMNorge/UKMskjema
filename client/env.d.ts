/// <reference types="vite/client" />
interface Element {
  style: CSSStyleDeclaration;
}

declare module 'xlsx' {
    export type WorkSheet = {
        '!cols'?: { wch: number }[];
    };

    export const utils: {
        book_new: () => unknown;
        json_to_sheet: (data: Record<string, unknown>[]) => WorkSheet;
        aoa_to_sheet: (data: string[][]) => WorkSheet;
        book_append_sheet: (wb: unknown, ws: WorkSheet, name: string) => void;
    };
    export function writeFileXLSX(wb: unknown, filename: string): void;
}
