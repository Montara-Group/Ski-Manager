// Own-pace seasons (App\Libraries\Clock): the player's own season clock, Next day and autopilot
import { api } from './api';

export type ClockState =
  | { enabled: false }
  | {
      enabled: true;
      seasonNumber: number;
      day: number;
      length: number;
      autopilot: boolean;
      /** Next day presses allowed per real day, and how many are left today */
      cap: number;
      left: number;
      /** Last day reached and no next season defined yet */
      seasonComplete: boolean;
      /** Autopilot will add a day at the next nightly tick */
      autopilotTonight: boolean;
      /** Unix seconds of the next nightly tick */
      nextAutoAt: number;
    };

export function sendNextDay() {
  return api<ClockState>('day/next', { method: 'POST', body: {} });
}

export function sendAutopilot(on: boolean) {
  return api<ClockState>('day/autopilot', { method: 'POST', body: { on } });
}
