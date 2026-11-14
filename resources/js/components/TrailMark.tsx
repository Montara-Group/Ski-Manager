/** Trail-sign difficulty marks, the identity motif in DESIGN.md. The shape always comes with its color */
export type Difficulty = 'green' | 'blue' | 'black' | 'double_black';

const MARKS: Record<Difficulty, { mark: string; label: string; className: string }> = {
  green: { mark: '●', label: 'Green, easy', className: 'text-[var(--sign-green)]' },
  blue: { mark: '■', label: 'Blue, intermediate', className: 'text-[var(--sign-blue)]' },
  black: { mark: '◆', label: 'Black, advanced', className: 'text-foreground' },
  double_black: { mark: '◆◆', label: 'Double black, expert', className: 'text-foreground' },
};

export function trailLabel(d: Difficulty): string {
  return MARKS[d].label;
}

/** The mark alone. Pass `labelled` when no text next to it says the difficulty */
export function TrailMark({ difficulty, labelled = false }: { difficulty: Difficulty; labelled?: boolean }) {
  const m = MARKS[difficulty];
  return (
    <span className={`leading-none ${m.className}`} {...(labelled ? { role: 'img', 'aria-label': m.label } : { 'aria-hidden': true })}>
      {m.mark}
    </span>
  );
}
