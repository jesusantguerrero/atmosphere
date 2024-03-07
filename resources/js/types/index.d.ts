import { router } from '@inertiajs/vue3';
declare global {
  const router: typeof router;
  const route: Function
}

export {}

export interface ICard {
  label: string;
  value: [string, number];
  accent?: boolean;
  icon?: string;
}
