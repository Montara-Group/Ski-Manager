// Own-pace seasons (App\Libraries\Clock): the player's own season clock, Next day and autopilot
import { api } from './api';

export type ClockState =
  | { enabled: false }
  | {
      enabled: true;
      seasonNumber: number;
      day: number;
      length: number;
      /** Past the season's last day: the season is over and the resort plays on until the next one opens */
      freePlay: boolean;
      /** On or past the last day with the next season released: the next day starts it */
      nextSeasonReady: boolean;
      nextSeasonName: string | null;
      autopilot: boolean;
      /** Saved days Next day can spend; one more every bankHours, up to bankMax */
      bank: number;
      bankMax: number;
      bankHours: number;
      /** Unix seconds when the next saved day arrives, or null with a full bank */
      nextBankAt: number | null;
      /** Autopilot will add a day at the next nightly tick */
      autopilotTonight: boolean;
      /** Unix seconds of the next nightly tick */
      nextAutoAt: number;
      /** Resort score out of 1,000 (App\\Libraries\\ResortScore) */
      score: number;
    };

/** What the day that just ran earned and cost (App\\Libraries\\Clock::advance) */
export interface DayReport {
  message: string;
  day?: number;
  income?: number;
  expenses?: number;
  visitors?: number;
}

export function sendNextDay() {
  return api<ClockState & { report?: DayReport }>('day/next', { method: 'POST', body: {} });
}

/* The page reloads after Next day, so the report waits in sessionStorage for the top bar to show it */
const REPORT_KEY = 'sm.dayReport';

export function keepDayReport(message: string) {
  try {
    sessionStorage.setItem(REPORT_KEY, message);
  } catch {
    // storage blocked: the activity log still has the report
  }
}

export function takeDayReport(): string | null {
  try {
    const message = sessionStorage.getItem(REPORT_KEY);
    sessionStorage.removeItem(REPORT_KEY);
    return message;
  } catch {
    return null;
  }
}

export function sendAutopilot(on: boolean) {
  return api<ClockState>('day/autopilot', { method: 'POST', body: { on } });
}
