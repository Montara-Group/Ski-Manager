import React from 'react';
import { parkCityHasWebp } from '@/lib/webp';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';
import { TrailMark, trailLabel, type Difficulty } from '@/components/TrailMark';

interface HomeWeather {
  condition: string;
  temp: string;
  snowBase: string;
  newSnow: string;
  wind: string;
  snowGuns: boolean;
}

interface HomeSector {
  name: string;
  green: number;
  blue: number;
  black: number;
  doubleBlack: number;
  lifts: number;
  parks: number;
}

interface HomeSeason {
  number: number;
  name: string;
  start: string;
  state: 'done' | 'live' | 'next';
}

interface HomeStanding {
  rank: number;
  username: string;
  resort: string;
  runs: number;
  lifts: number;
  profit: string;
  positive: boolean;
}

interface HomeBuild {
  username: string;
  kind: 'run' | 'lift';
  difficulty: Difficulty;
  name: string | null;
  ago: string;
}

interface HomePageProps {
  isLoggedIn: boolean;
  players: string;
  runsBuilt: string;
  buildersThisWeek: string;
  season: { number: number; name: string; day: number; length: number };
  weather: HomeWeather | null;
  sectors: HomeSector[];
  seasons: HomeSeason[];
  standings: HomeStanding[];
  recent: HomeBuild[];
}

/* Link styles. Navigation stays an <a>; only the demo, which creates an account, is a form button */
const primaryLink =
  'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-accent-foreground hover:bg-[var(--accent-hover)] active:translate-y-px transition-colors';
const heroSecondary =
  'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-white/40 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 active:bg-white/15 transition-colors';
const pageSecondary =
  'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-border bg-surface px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-default active:bg-surface-tertiary transition-colors';
const textLink = 'text-link underline-offset-4 hover:underline';

export default function HomePage(props: HomePageProps) {
  return (
    <div className="flex flex-col">
      <Hero {...props} />
      <Mountain sectors={props.sectors} isLoggedIn={props.isLoggedIn} />
      <GameDay />
      <ThisSeason standings={props.standings} recent={props.recent} season={props.season} isLoggedIn={props.isLoggedIn} />
      <Seasons seasons={props.seasons} season={props.season} />
      <Closing players={props.players} isLoggedIn={props.isLoggedIn} />
    </div>
  );
}

/* 1. What the game is, and today's snow report. The PHP view renders the same hero before this mounts */
function Hero({ isLoggedIn, players, runsBuilt, buildersThisWeek, season, weather }: HomePageProps) {
  return (
    <section className="relative isolate overflow-hidden bg-[#0a0e14] text-white" aria-labelledby="home-title">
      <HeroImage />
      {/* Phones: an even overlay, since the text sits over the bright snow of the map. Desktop: darker on the text side */}
      <div className="absolute inset-0 -z-10 bg-[#0a0e14]/85 lg:bg-transparent lg:bg-gradient-to-r lg:from-[#0a0e14]/95 lg:via-[#0a0e14]/85 lg:to-[#0a0e14]/45" />

      <div className="mx-auto grid max-w-6xl grid-cols-1 items-center gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:px-8 lg:py-20">
        <div className="space-y-6 lg:col-span-7">
          <p className="flex items-center gap-2 text-sm text-white/85">
            <span className="size-2 rounded-full bg-[oklch(72%_0.18_155)]" aria-hidden="true" />
            <span>
              Season {season.number}, day {season.day} of {season.length}
            </span>
          </p>

          <h1 id="home-title" className="max-w-xl text-[clamp(2.125rem,5vw,3.25rem)] font-bold leading-[1.1] tracking-tight">
            Run a ski resort on a <span className="text-[oklch(80%_0.11_245)]">real mountain.</span>
          </h1>

          <p className="max-w-lg text-base leading-relaxed text-white/85 sm:text-lg">
            Ski Manager is a free browser game. You get the Park City trail map, a starting budget and a {season.length}-day
            season. Build lifts and runs, hire staff, set ticket prices, and keep the snow good enough that skiers come back.
          </p>

          <div className="flex flex-wrap items-center gap-3">
            {isLoggedIn ? (
              <>
                <a href="/dashboard" className={primaryLink}>
                  Open your resort
                </a>
                <a href="/map" className={heroSecondary}>
                  <Icon name="fa-map" className="size-3.5" />
                  Trail map
                </a>
              </>
            ) : (
              <>
                <a href="/register" className={primaryLink}>
                  Play free
                </a>
                <DemoStartButton className={heroSecondary}>Try the demo mountain</DemoStartButton>
                <a href="/login" className="inline-flex min-h-11 items-center px-2 text-sm font-medium text-white/85 underline underline-offset-4 hover:text-white">
                  Log in
                </a>
              </>
            )}
          </div>

          <p className="text-sm text-white/75">
            <strong className="font-semibold text-white tabular-nums">{players}</strong> managers ·{' '}
            <strong className="font-semibold text-white tabular-nums">{buildersThisWeek}</strong> built something this week ·{' '}
            <strong className="font-semibold text-white tabular-nums">{runsBuilt}</strong> runs built so far
          </p>
        </div>

        <div className="lg:col-span-5">
          <SnowBoard weather={weather} season={season} />
        </div>
      </div>
    </section>
  );
}

function HeroImage() {
  // Under the dark overlay the small map is enough on phones; wide screens get the sharp one
  const webp = parkCityHasWebp('low') && parkCityHasWebp('med');
  return (
    <picture>
      {webp && <source type="image/webp" media="(min-width: 1024px)" srcSet="/img/ParkCity_med.webp" />}
      <source media="(min-width: 1024px)" srcSet="/img/ParkCity_med.jpg" />
      {webp && <source type="image/webp" srcSet="/img/ParkCity_low.webp" />}
      <img
        src="/img/ParkCity_low.jpg"
        alt=""
        width={800}
        height={533}
        fetchPriority="high"
        decoding="async"
        className="absolute inset-0 -z-20 size-full object-cover"
      />
    </picture>
  );
}

/* The resort's snow report board: light paper, dark header strip, big numbers (DESIGN.md) */
function SnowBoard({ weather, season }: { weather: HomeWeather | null; season: HomePageProps['season'] }) {
  return (
    <div className="snow-board overflow-hidden rounded-xl">
      <div className="snow-board-strip flex items-baseline justify-between gap-3 px-4 py-2.5">
        <h2 className="text-sm font-bold uppercase tracking-wider">Snow report</h2>
        <span className="text-xs">Park City · day {season.day}</span>
      </div>
      {weather ? (
        <>
          <dl className="grid grid-cols-2">
            <div className="snow-board-cell px-4 py-3">
              <dt className="text-xs">Summit</dt>
              <dd className="text-3xl font-bold tabular-nums">{weather.temp}</dd>
              <dd className="text-sm">{weather.condition}</dd>
            </div>
            <div className="snow-board-cell px-4 py-3">
              <dt className="text-xs">Base depth</dt>
              <dd className="text-3xl font-bold tabular-nums">{weather.snowBase}</dd>
              <dd className="text-sm">New snow: {weather.newSnow}</dd>
            </div>
            <div className="snow-board-cell px-4 py-3">
              <dt className="text-xs">Wind</dt>
              <dd className="text-lg font-semibold tabular-nums">{weather.wind}</dd>
            </div>
            <div className="snow-board-cell px-4 py-3">
              <dt className="text-xs">Snow guns</dt>
              <dd className="text-lg font-semibold">{weather.snowGuns ? 'Cold enough' : 'Too warm'}</dd>
            </div>
          </dl>
          <p className="snow-board-note px-4 py-2.5 text-xs">Every resort in the game skis this weather. Temperature and wind change through the day.</p>
        </>
      ) : (
        <p className="px-4 py-6 text-sm">The first report is posted when the season's first day starts.</p>
      )}
    </div>
  );
}

/* 2. The real trail map, and what players can build on it */
function Mountain({ sectors: all, isLoggedIn }: { sectors: HomeSector[]; isLoggedIn: boolean }) {
  // A sector with nothing drawn on it yet has nothing to build
  const sectors = all.filter((s) => s.green + s.blue + s.black + s.doubleBlack + s.lifts + s.parks > 0);
  const webp = parkCityHasWebp('low') && parkCityHasWebp('med');
  const total = sectors.reduce(
    (t, s) => ({ runs: t.runs + s.green + s.blue + s.black + s.doubleBlack, lifts: t.lifts + s.lifts, parks: t.parks + s.parks }),
    { runs: 0, lifts: 0, parks: 0 },
  );
  const cols: { key: keyof HomeSector; d: Difficulty }[] = [
    { key: 'green', d: 'green' },
    { key: 'blue', d: 'blue' },
    { key: 'black', d: 'black' },
    { key: 'doubleBlack', d: 'double_black' },
  ];

  return (
    <section className="border-b border-border bg-background py-14 sm:py-20" aria-labelledby="home-mountain">
      <div className="mx-auto grid max-w-6xl grid-cols-1 items-start gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:px-8">
        <figure className="space-y-2 lg:col-span-7">
          <picture>
            {webp && <source type="image/webp" srcSet="/img/ParkCity_low.webp 800w, /img/ParkCity_med.webp 3150w" sizes="(min-width: 1024px) 640px, 100vw" />}
            <img
              src="/img/ParkCity_low.jpg"
              srcSet="/img/ParkCity_low.jpg 800w, /img/ParkCity_med.jpg 3150w"
              sizes="(min-width: 1024px) 640px, 100vw"
              alt="The Park City trail map that every resort in the game is built on"
              width={800}
              height={533}
              loading="lazy"
              decoding="async"
              className="w-full rounded-xl border border-border"
            />
          </picture>
          <figcaption className="text-xs text-muted">
            Trail map licensed from{' '}
            <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" className={textLink}>
              Mapsynergy / Skimap
            </a>
            .
          </figcaption>
        </figure>

        <div className="order-first space-y-5 lg:order-none lg:col-span-5">
          <div className="space-y-2">
            <h2 id="home-mountain" className="text-2xl font-bold text-foreground sm:text-3xl">
              The mountain is Park City
            </h2>
            <p className="text-sm leading-relaxed text-muted sm:text-base">
              Every run and lift comes from the real trail map. You pick which ones to build and in what order, with the money you
              have. Steeper terrain opens as the seasons go on.
            </p>
          </div>

          {sectors.length > 0 ? (
            <div className="space-y-2">
              <table className="w-full text-sm tabular-nums">
                <caption className="sr-only">Runs by difficulty and lifts in each open sector</caption>
                <thead>
                  <tr className="border-b border-border text-left text-xs text-muted">
                    <th scope="col" className="py-2 pr-2 font-medium">Sector</th>
                    {cols.map((c) => (
                      <th key={c.key} scope="col" className="px-1 py-2 text-right font-medium">
                        <span className="text-base" title={trailLabel(c.d)}>
                          <TrailMark difficulty={c.d} labelled />
                        </span>
                      </th>
                    ))}
                    <th scope="col" className="py-2 pl-2 text-right font-medium">Lifts</th>
                  </tr>
                </thead>
                <tbody>
                  {sectors.map((s) => (
                    <tr key={s.name} className="border-b border-border">
                      <th scope="row" className="py-2.5 pr-2 text-left font-semibold text-foreground">{s.name}</th>
                      {cols.map((c) => (
                        <td key={c.key} className="px-1 py-2.5 text-right text-foreground">{s[c.key]}</td>
                      ))}
                      <td className="py-2.5 pl-2 text-right text-foreground">{s.lifts}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <p className="text-xs text-muted">
                {total.runs} runs and {total.lifts} lifts open to build{total.parks > 0 ? `, plus ${total.parks} terrain ${total.parks === 1 ? 'park' : 'parks'}` : ''}.
              </p>
            </div>
          ) : (
            <p className="text-sm text-muted">No sector is open yet. The first one opens when the season starts.</p>
          )}

          <a href={isLoggedIn ? '/map' : '/register'} className={`${pageSecondary} w-full sm:w-auto`}>
            <Icon name="fa-map" className="size-3.5" />
            {isLoggedIn ? 'Open the trail map' : 'Sign up to start building'}
          </a>
        </div>
      </div>
    </section>
  );
}

/* 3. How a game day plays: what the player decides, then what the nightly tick works out */
function GameDay() {
  const decisions: { text: string; guide?: { href: string; label: string } }[] = [
    { text: 'Which lifts and runs to build, and where.', guide: { href: '/guides/lifts', label: 'Lifts guide' } },
    { text: 'Ticket prices. Higher prices earn more per skier and keep some at home.', guide: { href: '/guides/ticket-pricing', label: 'Pricing guide' } },
    { text: 'Who to hire: ski patrol, instructors, mechanics, groomers and snowmakers.', guide: { href: '/guides/staff', label: 'Staff guide' } },
    { text: 'When to groom, and when to run the snow guns.', guide: { href: '/guides/grooming', label: 'Grooming guide' } },
    { text: 'Hotels, restaurants and rentals around the base.' },
    { text: 'Whether to take a loan to build faster.', guide: { href: '/guides/money', label: 'Money guide' } },
  ];
  const night = [
    'Skiers come. How many depends on your runs and their snow, your staff, marketing and the weather. Your prices put some off, and your open lifts cap how many fit.',
    'Money settles: tickets, lessons, hotels and restaurants in; salaries, running costs and loan payments out.',
    'Runs wear down and lifts age, so grooming and repairs matter.',
    'The next day starts with new weather and maybe new snow.',
  ];

  return (
    <section className="border-b border-border bg-surface py-14 sm:py-20" aria-labelledby="home-day">
      <div className="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl space-y-2">
          <h2 id="home-day" className="text-2xl font-bold text-foreground sm:text-3xl">
            How a game day plays
          </h2>
          <p className="text-sm text-muted sm:text-base">
            During the day you make the calls. Each night the day ends, and the game works out how it went.
          </p>
        </div>

        <div className="grid grid-cols-1 gap-10 md:grid-cols-2">
          <div className="space-y-3">
            <h3 className="text-base font-semibold text-foreground">You decide</h3>
            <ul className="divide-y divide-border border-y border-border">
              {decisions.map((d) => (
                <li key={d.text} className="grid grid-cols-[1fr_auto] items-baseline gap-4 py-3 text-sm">
                  <span className="text-foreground">{d.text}</span>
                  {d.guide && (
                    <a href={d.guide.href} className={`${textLink} inline-flex min-h-11 items-center text-xs sm:min-h-0`}>
                      {d.guide.label}
                    </a>
                  )}
                </li>
              ))}
            </ul>
          </div>

          <div className="space-y-3">
            <h3 className="text-base font-semibold text-foreground">Overnight, the game works out</h3>
            <ol className="space-y-4">
              {night.map((t, i) => (
                <li key={t} className="grid grid-cols-[2rem_1fr] gap-3 text-sm">
                  <span className="flex size-7 items-center justify-center rounded-md bg-surface-secondary text-xs font-semibold text-foreground tabular-nums">
                    {i + 1}
                  </span>
                  <span className="pt-1 text-foreground">{t}</span>
                </li>
              ))}
            </ol>
            <p className="pt-1 text-sm">
              <a href="/guides/getting-started" className={textLink}>
                Read the first-winter guide
              </a>
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}

/* 4. Live: who leads this season, and what people built lately */
function ThisSeason({
  standings,
  recent,
  season,
  isLoggedIn,
}: {
  standings: HomeStanding[];
  recent: HomeBuild[];
  season: HomePageProps['season'];
  isLoggedIn: boolean;
}) {
  return (
    <section className="border-b border-border bg-background py-14 sm:py-20" aria-labelledby="home-live">
      <div className="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <h2 id="home-live" className="text-2xl font-bold text-foreground sm:text-3xl">
          Season {season.number} so far
        </h2>

        <div className="grid grid-cols-1 gap-10 lg:grid-cols-12">
          <div className="space-y-3 lg:col-span-7">
            <div className="flex items-baseline justify-between gap-3">
              <h3 className="text-base font-semibold text-foreground">Top resorts by profit</h3>
              {isLoggedIn && (
                <a href="/leaderboard" className={`${textLink} inline-flex min-h-11 items-center text-sm sm:min-h-0`}>
                  Full leaderboard
                </a>
              )}
            </div>
            {standings.length > 0 ? (
              <ol className="divide-y divide-border border-y border-border">
                {standings.map((s) => (
                  <li key={s.rank} className="grid grid-cols-[1.75rem_1fr_auto] items-center gap-3 py-3 text-sm">
                    <span className="font-semibold text-muted tabular-nums">{s.rank}</span>
                    <span className="min-w-0">
                      <span className="block truncate font-semibold text-foreground">{s.resort || s.username}</span>
                      <span className="block truncate text-xs text-muted">
                        {s.resort ? `${s.username} · ` : ''}
                        {s.runs} {s.runs === 1 ? 'run' : 'runs'} · {s.lifts} {s.lifts === 1 ? 'lift' : 'lifts'}
                      </span>
                    </span>
                    <span className={`font-semibold tabular-nums ${s.positive ? 'text-success' : 'text-danger'}`}>{s.profit}</span>
                  </li>
                ))}
              </ol>
            ) : (
              <p className="border-y border-border py-6 text-sm text-muted">No resort has built anything yet this season.</p>
            )}
          </div>

          <div className="space-y-3 lg:col-span-5">
            <div className="flex items-baseline justify-between gap-3">
              <h3 className="text-base font-semibold text-foreground">Built lately</h3>
              {isLoggedIn && (
                <a href="/activity" className={`${textLink} inline-flex min-h-11 items-center text-sm sm:min-h-0`}>
                  All activity
                </a>
              )}
            </div>
            {recent.length > 0 ? (
              <ul className="divide-y divide-border border-y border-border">
                {recent.map((r, i) => (
                  <li key={i} className="grid grid-cols-[1.5rem_1fr_auto] items-center gap-3 py-3 text-sm">
                    <span className="flex justify-center">
                      {r.kind === 'lift' ? (
                        <Icon name="fa-cable-car" className="size-3.5 text-muted" />
                      ) : (
                        <TrailMark difficulty={r.difficulty} />
                      )}
                    </span>
                    <span className="min-w-0 truncate text-foreground">
                      <span className="font-semibold">{r.username}</span>{' '}
                      <span className="text-muted">
                        built {r.kind === 'lift' ? 'a lift' : `a ${trailLabel(r.difficulty).split(',')[0].toLowerCase()} run`}
                        {r.name ? `: ${r.name}` : ''}
                      </span>
                    </span>
                    <span className="text-xs text-muted tabular-nums">{r.ago}</span>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="border-y border-border py-6 text-sm text-muted">Nothing built yet this season.</p>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

/* 5. Seasons that exist, the live one's progress, and where the next one is decided */
function Seasons({ seasons, season }: { seasons: HomeSeason[]; season: HomePageProps['season'] }) {
  const pct = Math.min(100, Math.round((season.day / season.length) * 100));
  return (
    <section className="border-b border-border bg-surface py-14 sm:py-20" aria-labelledby="home-seasons">
      <div className="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl space-y-2">
          <h2 id="home-seasons" className="text-2xl font-bold text-foreground sm:text-3xl">
            Seasons
          </h2>
          <p className="text-sm text-muted sm:text-base">
            A season lasts {season.length} game days. Each one opens more of the mountain, and the leaderboard starts again.
          </p>
        </div>

        <ol className="grid grid-cols-1 gap-6 md:grid-cols-3">
          {seasons.map((s) => (
            <li key={s.number} className="space-y-2 border-t-2 pt-4 border-border data-[live=true]:border-accent" data-live={s.state === 'live'}>
              <p className="text-xs text-muted">
                {s.state === 'done' ? 'Finished' : s.state === 'live' ? 'Now playing' : 'Coming'} · {s.state === 'next' ? 'starts' : 'started'} {s.start}
              </p>
              <h3 className="text-base font-semibold text-foreground">{s.name}</h3>
              {s.state === 'live' && (
                <div className="space-y-1.5 pt-1">
                  <div
                    className="h-2 overflow-hidden rounded-full bg-surface-tertiary"
                    role="progressbar"
                    aria-label={`${s.name} progress`}
                    aria-valuemin={1}
                    aria-valuemax={season.length}
                    aria-valuenow={season.day}
                  >
                    <div className="season-track h-full rounded-full" style={{ width: `${pct}%` }} />
                  </div>
                  <p className="text-xs text-muted tabular-nums">
                    Day {season.day} of {season.length}, {season.length - season.day} to go
                  </p>
                </div>
              )}
            </li>
          ))}
          <li className="space-y-2 border-t-2 border-dashed border-border pt-4">
            <p className="text-xs text-muted">Not scheduled yet</p>
            <h3 className="text-base font-semibold text-foreground">What comes next</h3>
            <p className="text-sm text-muted">
              Players suggest and vote on what gets built next.{' '}
              <a href="/roadmap" className={textLink}>
                See the roadmap
              </a>
            </p>
          </li>
        </ol>
      </div>
    </section>
  );
}

/* 6. The ask, with the one number that is true today */
function Closing({ players, isLoggedIn }: { players: string; isLoggedIn: boolean }) {
  return (
    <section className="bg-background py-14 sm:py-20" aria-labelledby="home-closing">
      <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col gap-6 border-l-4 border-accent pl-5 sm:flex-row sm:items-center sm:justify-between sm:pl-8">
          <div className="space-y-2">
            <h2 id="home-closing" className="text-2xl font-bold text-foreground sm:text-3xl">
              {isLoggedIn ? 'Your resort is waiting.' : <>{players} managers run a resort here.</>}
            </h2>
            <p className="text-sm text-muted sm:text-base">
              {isLoggedIn ? 'Check today’s report and make the calls before the day ends.' : 'Ski Manager is free and plays in the browser, on a phone too. Nothing to download.'}
            </p>
          </div>
          <div className="flex flex-wrap gap-3">
            {isLoggedIn ? (
              <a href="/dashboard" className={primaryLink}>
                Open your resort
              </a>
            ) : (
              <>
                <a href="/register" className={primaryLink}>
                  Play free
                </a>
                <DemoStartButton className={pageSecondary}>Try the demo mountain</DemoStartButton>
              </>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

/** Starting a demo creates an account, so it's a form post: crawlers and prefetch following a link can't trigger it */
function DemoStartButton({ className, children }: { className: string; children: React.ReactNode }) {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  return (
    <form action="/demo" method="post" className="contents">
      <input type="hidden" name={meta?.dataset.field || 'csrf_test_name'} value={meta?.content || ''} />
      <Button type="submit" variant="ghost" className={className}>
        <span>{children}</span>
      </Button>
    </form>
  );
}
