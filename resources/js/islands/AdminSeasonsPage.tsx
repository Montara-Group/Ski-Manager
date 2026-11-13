import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel, PostButton } from '@/components/Page';
import { Select } from '@/components/Select';

export type AdminSeasonRecord = {
  id: number;
  season_number: number;
  name: string;
  resort_map: string;
  start_date: string;
  duration_days: number;
  winter_days: number;
  active: boolean;
};

export type AdminSector = {
  id: number;
  name: string;
  resort_map: string;
  visible: boolean;
  /** Season the sector opens in, or null: the old global Released switch decides */
  release_season: number | null;
  runs: number;
  lifts: number;
};

export type AdminSeasonsPageProps = {
  seasons: AdminSeasonRecord[];
  resortMaps: Record<string, string>;
  nextSeasonNumber: number;
  sectors?: AdminSector[];
  ownPace?: boolean;
};

export default function AdminSeasonsPage({
  seasons,
  resortMaps,
  nextSeasonNumber,
  sectors = [],
  ownPace = false,
}: AdminSeasonsPageProps) {
  const [newSeason, setNewSeason] = useState({
    season_number: nextSeasonNumber,
    name: '',
    resort_map: Object.keys(resortMaps)[0] || 'ParkCity',
    start_date: '',
    duration_days: 135,
    winter_days: 100,
  });

  return (
    <Page
      title="Season Planner"
      icon="fa-calendar-plus"
      iconTone="text-accent"
      back="/admin"
      subtitle="Plan future seasons, configure calendars, and trigger season rollover."
    >
      <div className="space-y-6">
        {/* Seasons Table */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-calendar-days" className="text-accent" />
              <span>All Seasons ({seasons.length})</span>
            </div>
          }
        >
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-border text-[11px] font-semibold text-muted">
                <tr>
                  <th className="pb-2">#</th>
                  <th className="pb-2">Name</th>
                  <th className="pb-2">Resort Map</th>
                  <th className="pb-2">Start Date</th>
                  <th className="pb-2">Days</th>
                  <th className="pb-2">Winter</th>
                  <th className="pb-2">Status</th>
                  <th className="pb-2 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/60">
                {seasons.map((s) => (
                  <tr
                    key={s.id}
                    className={`hover:bg-surface-raised/40 transition-colors ${
                      s.active ? 'bg-emerald-600/5' : ''
                    }`}
                  >
                    <td className="py-2.5 font-mono font-bold text-foreground">
                      {s.season_number}
                    </td>
                    <td className="py-2.5 font-bold text-foreground">{s.name}</td>
                    <td className="py-2.5 font-mono text-muted">{s.resort_map}</td>
                    <td className="py-2.5 font-mono text-muted">{s.start_date}</td>
                    <td className="py-2.5 font-mono text-foreground">{s.duration_days}</td>
                    <td className="py-2.5 font-mono text-foreground">{s.winter_days}</td>
                    <td className="py-2.5">
                      <span
                        className={`rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${
                          s.active
                            ? 'bg-emerald-600/15 text-emerald-400'
                            : 'bg-surface-raised text-muted'
                        }`}
                      >
                        {s.active ? 'Active' : 'Planned'}
                      </span>
                    </td>
                    <td className="py-2.5 text-right">
                      {!s.active && (
                        <PostButton
                          action={`/admin/seasons/activate/${s.id}`}
                          variant="primary"
                          size="sm"
                          confirm={{
                            title: `Activate Season #${s.season_number}?`,
                            body: `This sets Season #${s.season_number} ("${s.name}") as the active live season, archives current leaderboards, and unlocks the next sector.`,
                            confirmLabel: 'Activate Season',
                          }}
                        >
                          Activate
                        </PostButton>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Panel>

        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-map" className="text-accent" />
              <span>What each season opens</span>
            </div>
          }
        >
          <p className="mb-4 text-sm text-muted">
            Each season uses one trail map and opens the sectors ticked here. Players on their own pace build in every sector opened by their season or an earlier one, so a sector opens for each player when they reach that season. Runs and lifts belong to a sector: move them between sectors in Map Studio.
            {!ownPace && ' Own-pace seasons are off, so for now the Released switch in Map Studio still decides for everyone.'}
          </p>
          <div className="space-y-4">
            {seasons.map((s) => (
              <SeasonContent key={s.id} season={s} resortMaps={resortMaps} sectors={sectors} />
            ))}
          </div>
        </Panel>

        {/* Plan Next Season Form */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-plus" className="text-accent" />
              <span>Plan Next Season</span>
            </div>
          }
        >
          <form action="/admin/seasons/create" method="post" className="space-y-4">
            <CsrfInput />
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Season Number</label>
                <input
                  type="number"
                  name="season_number"
                  value={newSeason.season_number}
                  onChange={(e) =>
                    setNewSeason({ ...newSeason, season_number: Number(e.target.value) })
                  }
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-mono font-bold text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>

              <div className="space-y-1 sm:col-span-2">
                <label className="text-xs font-semibold text-muted">Season Title</label>
                <input
                  type="text"
                  name="name"
                  placeholder="e.g. Season 2: Wasatch Peak Expansion"
                  value={newSeason.name}
                  onChange={(e) => setNewSeason({ ...newSeason, name: e.target.value })}
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Resort Map</label>
                <Select
                  name="resort_map"
                  value={newSeason.resort_map}
                  onChange={(val) => setNewSeason({ ...newSeason, resort_map: String(val) })}
                  aria-label="Resort Map"
                  options={Object.entries(resortMaps).map(([key, name]) => ({
                    id: key,
                    label: `${name} (${key})`,
                  }))}
                  className="w-full"
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Start Date</label>
                <input
                  type="date"
                  name="start_date"
                  value={newSeason.start_date}
                  onChange={(e) => setNewSeason({ ...newSeason, start_date: e.target.value })}
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Total Days</label>
                <input
                  type="number"
                  name="duration_days"
                  value={newSeason.duration_days}
                  onChange={(e) =>
                    setNewSeason({ ...newSeason, duration_days: Number(e.target.value) })
                  }
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-mono text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted">Winter Days</label>
                <input
                  type="number"
                  name="winter_days"
                  value={newSeason.winter_days}
                  onChange={(e) =>
                    setNewSeason({ ...newSeason, winter_days: Number(e.target.value) })
                  }
                  className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-mono text-foreground focus:border-accent focus:outline-hidden"
                  required
                />
              </div>
            </div>

            <Button type="submit" variant="primary" className="w-full text-xs font-bold">
              <Icon name="fa-calendar-check" className="mr-1.5" />
              Create Planned Season
            </Button>
          </form>
        </Panel>
      </div>
    </Page>
  );
}

/** One season's trail map and the sectors it opens (POST /admin/seasons/content/{id}) */
function SeasonContent({ season, resortMaps, sectors }: { season: AdminSeasonRecord; resortMaps: Record<string, string>; sectors: AdminSector[] }) {
  const [map, setMap] = useState(season.resort_map || 'ParkCity');
  const onMap = sectors.filter((x) => x.resort_map === map);

  return (
    <form action={`/admin/seasons/content/${season.id}`} method="post" className="rounded-xl border border-border p-4">
      <CsrfInput />
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h3 className="font-semibold">
          Season {season.season_number}: {season.name}
        </h3>
        <div className="w-full sm:w-64">
          <Select
            name="resort_map"
            value={map}
            onChange={(val) => setMap(String(val))}
            aria-label={`Trail map for season ${season.season_number}`}
            options={Object.entries(resortMaps).map(([key, name]) => ({ id: key, label: name }))}
          />
        </div>
      </div>
      {onMap.length === 0 ? (
        <p className="mt-3 text-sm text-muted">This map has no sectors yet. Draw them in Map Studio.</p>
      ) : (
        <ul className="mt-3 grid gap-2 sm:grid-cols-2">
          {onMap.map((x) => {
            const elsewhere = x.release_season !== null && x.release_season !== season.season_number;
            return (
              <li key={x.id}>
                <label className="flex min-h-11 cursor-pointer items-start gap-3 rounded-lg border border-border p-3 text-sm md:min-h-9">
                  <input
                    type="checkbox"
                    name="sectors[]"
                    value={x.id}
                    defaultChecked={x.release_season === season.season_number}
                    className="mt-0.5 size-5 accent-[var(--accent)]"
                  />
                  <span>
                    <span className="font-semibold">{x.name}</span>
                    <span className="block text-muted">
                      {x.runs} runs, {x.lifts} lifts{!x.visible && ' · hidden in Map Studio'}
                      {elsewhere && ` · opens in season ${x.release_season}; ticking moves it here`}
                    </span>
                  </span>
                </label>
              </li>
            );
          })}
        </ul>
      )}
      <Button type="submit" variant="secondary" className="mt-3 min-h-11 md:min-h-9">
        Save season {season.season_number}
      </Button>
    </form>
  );
}
