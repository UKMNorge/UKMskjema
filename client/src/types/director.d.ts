declare module "ukm-spa/Director" {
    export class Director {
        addParam(name: string, value: string | number): void;
        getParam(key: string): string | null;
    }
}