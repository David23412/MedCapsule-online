import { useEffect, useRef, useState } from 'react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import {
  BookOpen,
  Calendar,
  FileText,
} from 'lucide-react';

gsap.registerPlugin(ScrollTrigger);

// --- SUB-COMPONENTS FOR ANIMATED ARTIFACTS ---

// ─── CARD 1: DIAGNOSTIC SHUFFLER ────────────────────────────────────────────

const SHUFFLER_ITEMS = [
  { id: 1, label: 'Neuro-Radiology', tag: 'Pattern Recognition', badge: '↑ 18%', color: '#EEF4FF' },
  { id: 2, label: 'Clinical Pharmacology', tag: 'Drug Interaction Cycles', badge: 'Active', color: '#F0FDF4' },
  { id: 3, label: 'Histopathology', tag: 'Tissue-Level Mastery', badge: '94% Recall', color: '#FEF9C3' },
];

const DiagnosticShuffler = () => {
  const [items, setItems] = useState(SHUFFLER_ITEMS);
  const [animating, setAnimating] = useState(false);

  useEffect(() => {
    const id = setInterval(() => {
      setAnimating(true);
      setTimeout(() => {
        setItems(prev => {
          const next = [...prev];
          const last = next.pop()!;
          next.unshift(last);
          return next;
        });
        setAnimating(false);
      }, 120);
    }, 3000);
    return () => clearInterval(id);
  }, []);

  return (
    <div className="bg-white p-8 rounded-[2rem] border border-blue-50 shadow-clinical relative overflow-hidden h-96 group">
      {/* Header */}
      <div className="flex justify-between items-start mb-5">
        <div className="p-2 bg-blue-50 rounded-xl text-plasma">
          <BookOpen size={18} />
        </div>
        <span className="font-mono text-[10px] text-blue-200 tracking-widest">01 / 03</span>
      </div>

      <h5 className="font-heading font-bold text-lg mb-1 text-charcoal">Diagnostic Shuffler</h5>
      <p className="font-body text-charcoal-dim text-xs leading-relaxed mb-6">
        Systematic cycling of clinical categories for recall optimization.
      </p>

      {/* Stacked card deck */}
      <div className="absolute inset-x-6 bottom-6 h-40">
        {items.map((item, i) => (
          <div
            key={item.id}
            className="absolute inset-x-0 h-[68px] rounded-2xl border px-4 flex items-center justify-between"
            style={{
              transform: `translateY(${-i * 18}px) scale(${1 - i * 0.04})`,
              zIndex: 20 - i,
              opacity: i === 0 ? 1 : i === 1 ? 0.55 : 0.25,
              background: i === 0 ? item.color : '#F8F9FF',
              borderColor: i === 0 ? 'rgba(65,139,245,0.18)' : 'rgba(0,0,0,0.06)',
              boxShadow: i === 0 ? '0 8px 24px -4px rgba(65,139,245,0.12)' : 'none',
              transition: animating
                ? 'none'
                : 'transform 700ms cubic-bezier(0.34,1.56,0.64,1), opacity 700ms ease',
            }}
          >
            {i === 0 && (
              <>
                <div>
                  <p className="font-heading font-bold text-sm text-charcoal leading-tight">{item.label}</p>
                  <p className="font-mono text-[10px] text-charcoal-dim mt-0.5">{item.tag}</p>
                </div>
                <span
                  className="font-mono text-[10px] font-semibold px-2.5 py-1 rounded-full"
                  style={{ background: 'rgba(65,139,245,0.10)', color: '#418BF5' }}
                >
                  {item.badge}
                </span>
              </>
            )}
          </div>
        ))}
      </div>

      {/* Cycle indicator dots */}
      <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
        {items.map((_, i) => (
          <div
            key={i}
            className="rounded-full transition-all duration-500"
            style={{
              width: i === 0 ? 16 : 5,
              height: 5,
              background: i === 0 ? '#418BF5' : '#D1D5DB',
            }}
          />
        ))}
      </div>
    </div>
  );
};

// ─── CARD 2: TELEMETRY TYPEWRITER ────────────────────────────────────────────

const FEED_MESSAGES = [
  { prefix: 'XP', color: '#418BF5', text: 'Level Up — Systematic Mastery Path Unlocked. +250 XP earned.' },
  { prefix: 'SYNC', color: '#22C55E', text: 'Refinement cycle complete. Protocol retention rate: 94.2%.' },
  { prefix: 'ALERT', color: '#F59E0B', text: 'Neuro-Radiology pattern gap detected. Scheduling recall boost.' },
  { prefix: 'EXEC', color: '#A78BFA', text: 'Clinical execution score updated. Delta: +12 pts this session.' },
];

const TelemetryTypewriter = () => {
  const [msgIdx, setMsgIdx] = useState(0);
  const [charIdx, setCharIdx] = useState(0);
  const [displayed, setDisplayed] = useState('');
  const [phase, setPhase] = useState<'typing' | 'pause' | 'clear'>('typing');
  const logRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const msg = FEED_MESSAGES[msgIdx].text;

    if (phase === 'typing') {
      if (charIdx < msg.length) {
        const t = setTimeout(() => {
          setDisplayed(p => p + msg[charIdx]);
          setCharIdx(c => c + 1);
        }, 38);
        return () => clearTimeout(t);
      } else {
        const t = setTimeout(() => setPhase('pause'), 1800);
        return () => clearTimeout(t);
      }
    }

    if (phase === 'pause') {
      const t = setTimeout(() => setPhase('clear'), 400);
      return () => clearTimeout(t);
    }

    if (phase === 'clear') {
      setDisplayed('');
      setCharIdx(0);
      setMsgIdx(i => (i + 1) % FEED_MESSAGES.length);
      setPhase('typing');
    }
  }, [phase, charIdx, msgIdx]);

  // Auto-scroll log to bottom
  useEffect(() => {
    if (logRef.current) logRef.current.scrollTop = logRef.current.scrollHeight;
  }, [displayed]);

  const current = FEED_MESSAGES[msgIdx];

  return (
    <div className="bg-white p-8 rounded-[2rem] border border-blue-50 shadow-clinical relative overflow-hidden h-96">
      {/* Header */}
      <div className="flex justify-between items-start mb-5">
        <div className="p-2 bg-blue-50 rounded-xl text-plasma">
          <FileText size={18} />
        </div>
        <span className="font-mono text-[10px] text-blue-200 tracking-widest">02 / 03</span>
      </div>

      <h5 className="font-heading font-bold text-lg mb-1 text-charcoal">Telemetry Typewriter</h5>
      <p className="font-body text-charcoal-dim text-xs leading-relaxed mb-5">
        Real-time algorithmic feedback on performance progression.
      </p>

      {/* Terminal window */}
      <div className="bg-[#0F1117] rounded-2xl overflow-hidden border border-white/5 shadow-xl">
        {/* Terminal chrome */}
        <div className="flex items-center justify-between px-4 py-2.5 border-b border-white/5">
          <div className="flex gap-1.5">
            <div className="w-2.5 h-2.5 rounded-full bg-red-500/70" />
            <div className="w-2.5 h-2.5 rounded-full bg-yellow-500/70" />
            <div className="w-2.5 h-2.5 rounded-full bg-green-500/70" />
          </div>
          <div className="flex items-center gap-2">
            <span
              className="w-1.5 h-1.5 rounded-full animate-pulse"
              style={{ background: '#22C55E' }}
            />
            <span className="font-mono text-[9px] text-white/30 uppercase tracking-widest">Live Feed</span>
          </div>
        </div>

        {/* Output area */}
        <div ref={logRef} className="p-4 h-28 overflow-hidden">
          <p className="font-mono text-[11px] leading-relaxed text-white/60">
            <span
              className="font-bold mr-2 px-1.5 py-0.5 rounded text-[9px] uppercase"
              style={{ background: current.color + '22', color: current.color }}
            >
              {current.prefix}
            </span>
            <span className="text-white/90">{displayed}</span>
            <span
              className="inline-block w-[2px] h-[13px] ml-[2px] align-text-bottom animate-pulse"
              style={{ background: current.color }}
            />
          </p>
        </div>
      </div>
    </div>
  );
};

// ─── CARD 3: CURSOR PROTOCOL SCHEDULER ───────────────────────────────────────

const CursorScheduler = () => {
  const wrapperRef = useRef<HTMLDivElement>(null);
  const cursorRef = useRef<SVGSVGElement>(null);
  const gridRef = useRef<HTMLDivElement>(null);
  const saveBtnRef = useRef<HTMLButtonElement>(null);
  const tooltipRef = useRef<HTMLDivElement>(null);
  const glowRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const ctx = gsap.context(() => {
      const cells = gridRef.current?.querySelectorAll<HTMLElement>('.cal-cell');
      if (!cells || cells.length < 14 || !saveBtnRef.current || !cursorRef.current || !wrapperRef.current) return;

      // pick three cells to activate: Mon(1), Wed(3), next-Thu(11)
      const targets = [cells[1], cells[3], cells[11]];
      const cursorHotspot = { x: 2, y: 2 };

      // ── reset helpers ──
      const resetCells = () => {
        gsap.set(cells, { backgroundColor: '#F0EFF4', borderColor: 'transparent', scale: 1 });
        gsap.set('.cal-check', { opacity: 0, scale: 0 });
      };
      const resetBtn = () => {
        gsap.set(saveBtnRef.current, { backgroundColor: '#ffffff', color: '#6B7280', scale: 1 });
      };
      const resetUI = () => { resetCells(); resetBtn(); };

      resetUI();
      gsap.set(cursorRef.current, { opacity: 0 });
      gsap.set(tooltipRef.current, { opacity: 0, y: 6, scale: 0.9 });
      gsap.set(glowRef.current, { opacity: 0 });

      // ── position helper ──
      const getCenterPoint = (el: HTMLElement) => {
        const wrapperRect = wrapperRef.current!.getBoundingClientRect();
        const rect = el.getBoundingClientRect();

        return {
          centerX: rect.left - wrapperRect.left + (rect.width / 2),
          centerY: rect.top - wrapperRect.top + (rect.height / 2),
        };
      };

      const cursorPos = (el: HTMLElement) => {
        const point = getCenterPoint(el);

        return {
          left: point.centerX - cursorHotspot.x,
          top: point.centerY - cursorHotspot.y,
        };
      };

      const glowPos = (el: HTMLElement) => {
        const point = getCenterPoint(el);

        return {
          left: point.centerX - 16,
          top: point.centerY - 16,
        };
      };

      // ── build repeating timeline ──
      const tl = gsap.timeline({ repeat: -1, repeatDelay: 1, onRepeat: resetUI });

      // Cursor enters from bottom-right
      tl.fromTo(cursorRef.current,
        { left: '90%', top: '110%', opacity: 0, scale: 0.7 },
        { left: '70%', top: '62%', opacity: 1, scale: 1, duration: 0.55, ease: 'power3.out' }
      );

      // Visit each target cell
      targets.forEach((cell) => {
        const p = cursorPos(cell);
        const glowPoint = glowPos(cell);
        tl
          // glide to cell
          .to(cursorRef.current, { left: p.left, top: p.top, duration: 0.55, ease: 'power2.inOut' })
          // cursor press
          .to(cursorRef.current, { scale: 0.82, duration: 0.09 })
          // cell press + highlight
          .to(cell, { scale: 0.88, duration: 0.09 }, '<')
          .to(cell, { backgroundColor: '#DBEAFE', borderColor: '#418BF5', duration: 0.18, ease: 'power1.out' })
          // checkmark pop in
          .to(cell.querySelector('.cal-check'), { opacity: 1, scale: 1, duration: 0.22, ease: 'back.out(2)' }, '<0.08')
          // release
          .to(cell, { scale: 1, duration: 0.2, ease: 'elastic.out(1,0.5)' })
          .to(cursorRef.current, { scale: 1, duration: 0.15 }, '<')
          // glow flash
          .fromTo(glowRef.current,
            { opacity: 0.5, left: glowPoint.left, top: glowPoint.top },
            { opacity: 0, duration: 0.45, ease: 'power2.out' }, '<'
          );
      });

      // Move to Save button
      const bp = cursorPos(saveBtnRef.current);
      tl
        .to(cursorRef.current, { left: bp.left, top: bp.top, duration: 0.65, ease: 'power2.inOut' })
        .to(cursorRef.current, { scale: 0.82, duration: 0.09 })
        .to(saveBtnRef.current, { scale: 0.96, duration: 0.09 }, '<')
        .to(saveBtnRef.current, { backgroundColor: '#418BF5', color: '#ffffff', duration: 0.22 })
        .to(saveBtnRef.current, { scale: 1, duration: 0.22, ease: 'elastic.out(1,0.5)' })
        .to(cursorRef.current, { scale: 1, duration: 0.15 }, '<')
        // tooltip pop
        .to(tooltipRef.current, { opacity: 1, y: 0, scale: 1, duration: 0.3, ease: 'back.out(2)' }, '+=0.12')
        // cursor drifts away
        .to(cursorRef.current, { top: '+=28', opacity: 0, duration: 0.5, ease: 'power2.in' }, '+=0.7')
        .to(tooltipRef.current, { opacity: 0, y: -4, duration: 0.25 }, '<0.2');
    });

    return () => ctx.revert();
  }, []);

  return (
    <div className="bg-white p-8 rounded-[2rem] border border-blue-50 shadow-clinical relative overflow-hidden h-96">
      {/* Header */}
      <div className="flex justify-between items-start mb-5">
        <div className="p-2 bg-blue-50 rounded-xl text-plasma">
          <Calendar size={18} />
        </div>
        <span className="font-mono text-[10px] text-blue-200 tracking-widest">03 / 03</span>
      </div>

      <h5 className="font-heading font-bold text-lg mb-1 text-charcoal">Protocol Scheduler</h5>
      <p className="font-body text-charcoal-dim text-xs leading-relaxed mb-4">
        Automated weekly mapping of your retention protocol.
      </p>

      {/* Calendar widget */}
      <div ref={wrapperRef} className="relative border border-gray-100 rounded-2xl p-4 bg-white shadow-sm overflow-visible">

        {/* Day headers */}
        <div className="grid grid-cols-7 text-center font-mono text-[9px] text-gray-400 tracking-widest pb-2 mb-2 border-b border-gray-100">
          {['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((d, i) => (
            <span key={i}>{d}</span>
          ))}
        </div>

        {/* Day cells */}
        <div ref={gridRef} className="grid grid-cols-7 gap-1.5 mb-4 relative">
          {Array.from({ length: 14 }).map((_, i) => (
            <div
              key={i}
              className="cal-cell h-7 rounded-[5px] border border-transparent flex items-center justify-center"
              style={{ backgroundColor: '#F0EFF4' }}
            >
              <svg
                className="cal-check w-3 h-3"
                viewBox="0 0 24 24" fill="none"
                stroke="#418BF5" strokeWidth="3.5"
                strokeLinecap="round" strokeLinejoin="round"
                style={{ opacity: 0 }}
              >
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </div>
          ))}
        </div>

        {/* Save button + tooltip */}
        <div className="relative">
          <button
            ref={saveBtnRef}
            className="w-full py-2 rounded-xl text-[11px] font-heading font-bold border border-gray-100 transition-all"
            style={{ backgroundColor: '#ffffff', color: '#6B7280' }}
          >
            CONFIRM PROTOCOL
          </button>

          <div
            ref={tooltipRef}
            className="absolute -top-9 left-1/2 -translate-x-1/2 bg-charcoal text-white text-[9px] font-mono px-3 py-1.5 rounded-lg shadow-lg whitespace-nowrap z-50 pointer-events-none"
          >
            ✓ Protocol Synced
            <div className="absolute -bottom-[5px] left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-charcoal rotate-45 rounded-sm" />
          </div>
        </div>

        {/* Glow dot */}
        <div
          ref={glowRef}
          className="absolute w-8 h-8 rounded-full pointer-events-none"
          style={{ background: 'radial-gradient(circle, rgba(65,139,245,0.6) 0%, transparent 70%)', opacity: 0 }}
        />

        {/* SVG Cursor */}
        <svg
          ref={cursorRef}
          width="20" height="20" viewBox="0 0 20 20"
          className="absolute pointer-events-none z-50 origin-top-left drop-shadow-[0_3px_8px_rgba(0,0,0,0.35)]"
          style={{ top: '62%', left: '70%', opacity: 0 }}
        >
          <path d="M2 2L18 9L10.5 11L8 18L2 2Z" fill="#1A1A1A" stroke="white" strokeWidth="1.8" strokeLinejoin="round" />
        </svg>
      </div>
    </div>
  );
};

type AppProps = {
  isAuthenticated?: boolean;
  appUrl?: string;
};

type LiveUserSnapshot = {
  count: number;
  visitors: number;
  users: number;
  fake_mode: boolean;
};

const resolveAppHref = (appUrl: string | undefined, path: string) => {
  const normalizedAppUrl = appUrl?.replace(/\/+$/, '') ?? '';
  return normalizedAppUrl ? `${normalizedAppUrl}${path}` : path;
};

const LiveUserCounter = ({ appUrl }: { appUrl?: string }) => {
  const [snapshot, setSnapshot] = useState<LiveUserSnapshot | null>(null);
  const [displayCount, setDisplayCount] = useState(1);
  const [isUpdating, setIsUpdating] = useState(false);
  const displayedCountRef = useRef(1);
  const animationTimerRef = useRef<number | null>(null);
  const clearUpdateStateRef = useRef<number | null>(null);

  useEffect(() => {
    displayedCountRef.current = displayCount;
  }, [displayCount]);

  useEffect(() => {
    return () => {
      if (animationTimerRef.current !== null) {
        window.clearInterval(animationTimerRef.current);
      }

      if (clearUpdateStateRef.current !== null) {
        window.clearTimeout(clearUpdateStateRef.current);
      }
    };
  }, []);

  useEffect(() => {
    const endpoint = resolveAppHref(appUrl, '/api/active-users');

    const animateCount = (targetCount: number) => {
      const safeTarget = Math.max(0, Math.round(targetCount));
      const currentCount = displayedCountRef.current;

      if (animationTimerRef.current !== null) {
        window.clearInterval(animationTimerRef.current);
        animationTimerRef.current = null;
      }

      if (clearUpdateStateRef.current !== null) {
        window.clearTimeout(clearUpdateStateRef.current);
        clearUpdateStateRef.current = null;
      }

      if (currentCount === safeTarget) {
        setDisplayCount(safeTarget);
        return;
      }

      setIsUpdating(true);

      const range = safeTarget - currentCount;
      const increment = range > 0 ? 1 : -1;
      const stepTime = Math.max(20, Math.floor(500 / Math.abs(range)));
      let nextCount = currentCount;

      animationTimerRef.current = window.setInterval(() => {
        nextCount += increment;
        displayedCountRef.current = nextCount;
        setDisplayCount(nextCount);

        if (nextCount === safeTarget) {
          if (animationTimerRef.current !== null) {
            window.clearInterval(animationTimerRef.current);
            animationTimerRef.current = null;
          }

          clearUpdateStateRef.current = window.setTimeout(() => {
            setIsUpdating(false);
            clearUpdateStateRef.current = null;
          }, 180);
        }
      }, stepTime);
    };

    const fetchSnapshot = async () => {
      try {
        const response = await fetch(endpoint, {
          headers: {
            Accept: 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error(`Request failed with status ${response.status}`);
        }

        const data = (await response.json()) as Partial<LiveUserSnapshot>;
        const normalizedSnapshot: LiveUserSnapshot = {
          count: Math.max(0, Number(data.count ?? 0)),
          visitors: Math.max(0, Number(data.visitors ?? 0)),
          users: Math.max(0, Number(data.users ?? 0)),
          fake_mode: Boolean(data.fake_mode),
        };

        setSnapshot(normalizedSnapshot);
        animateCount(normalizedSnapshot.count);
      } catch {
        setSnapshot((currentSnapshot) => currentSnapshot ?? {
          count: displayedCountRef.current,
          visitors: 0,
          users: displayedCountRef.current,
          fake_mode: false,
        });
      }
    };

    void fetchSnapshot();
    const intervalId = window.setInterval(() => {
      void fetchSnapshot();
    }, 30000);

    return () => {
      window.clearInterval(intervalId);
    };
  }, [appUrl]);

  return (
    <div
      className="live-users-badge"
      title={snapshot?.fake_mode ? 'Fake live users enabled from Manage Access' : 'Live users active now'}
    >
      <div className="live-users-indicator" aria-hidden="true">
        <div className="live-users-dot" />
        <div className="live-users-ripple" />
      </div>
      <div className="live-users-content">
        <span
          className={`live-users-count${isUpdating ? ' is-updating' : ''}`}
          aria-live="polite"
          aria-atomic="true"
        >
          {displayCount}
        </span>
        <span className="live-users-text">active now</span>
      </div>
    </div>
  );
};

export default function App({ isAuthenticated = false, appUrl }: AppProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const startHref = resolveAppHref(appUrl, isAuthenticated ? '/courses' : '/register');

  // Section refs
  const heroRef = useRef<HTMLElement>(null);
  const pillLeftRef = useRef<HTMLDivElement>(null);
  const pillRightRef = useRef<HTMLDivElement>(null);
  const protocolRef = useRef<HTMLElement>(null);

  // Animation targets
  const featuresContentRef = useRef<HTMLDivElement>(null);

  // Protocol targets
  const solveWidgetRef = useRef<HTMLDivElement>(null);
  const solveProgressRef = useRef<SVGCircleElement>(null);
  const solveGlowRef = useRef<SVGCircleElement>(null);
  const solvePulseRef = useRef<HTMLDivElement>(null);
  const solveMetricRef = useRef<HTMLSpanElement>(null);
  const gridCellsRef = useRef<HTMLDivElement>(null);
  const laserRef = useRef<HTMLDivElement>(null);
  const ekgPathRef = useRef<SVGPathElement>(null);

  useEffect(() => {
    // Basic GSAP context for easy cleanup
    const ctx = gsap.context(() => {

      // 1. THE CAPSULE SPLIT & HERO TRANSITION
      const splitTimeline = gsap.timeline({
        scrollTrigger: {
          trigger: heroRef.current,
          start: "top top",
          end: "+=150%", // Scroll duration for the split
          scrub: 1, // Smooth dragging
          pin: true,
        }
      });

      splitTimeline.to(
        pillLeftRef.current,
        { x: "-60vw", y: "-30vh", rotation: -45, ease: "power2.inOut" },
        0
      );

      splitTimeline.to(
        pillRightRef.current,
        { x: "60vw", y: "30vh", rotation: 45, ease: "power2.inOut" },
        0
      );

      // Reveal Features by moving them up into the viewport
      splitTimeline.fromTo(featuresContentRef.current,
        { opacity: 0, y: 300, scale: 0.9 },
        { opacity: 1, y: 0, scale: 1, duration: 0.5, ease: "power2.out" },
        0.2
      );

      // 2. THE PROTOCOL ARCHIVE (PINNED SCROLL)
      const protocolTimeline = gsap.timeline({
        scrollTrigger: {
          trigger: protocolRef.current,
          start: "top top",
          end: "+=300%", // 3 sections of scrolling
          scrub: 0.5,
          pin: true,
        }
      });

      // SECTION 1: SOLVE FASTER
      const solveRadius = 100;
      const solveCircumference = 2 * Math.PI * solveRadius;
      const solveEndOffset = solveCircumference * 0.08;
      const solveCounter = { value: 0 };

      if (solveProgressRef.current) {
        gsap.set(solveProgressRef.current, {
          strokeDasharray: solveCircumference,
          strokeDashoffset: solveCircumference,
        });
      }

      if (solveGlowRef.current) {
        gsap.set(solveGlowRef.current, {
          strokeDasharray: solveCircumference,
          strokeDashoffset: solveCircumference,
        });
      }

      if (solveMetricRef.current) {
        solveMetricRef.current.textContent = `${Math.round(solveCounter.value)}`;
      }

      protocolTimeline.fromTo(
        solveWidgetRef.current,
        { opacity: 0.7, y: 24, scale: 0.92 },
        { opacity: 1, y: 0, scale: 1, duration: 0.2, ease: "power2.out" },
        0
      );

      protocolTimeline.to(
        [solveGlowRef.current, solveProgressRef.current],
        {
          strokeDashoffset: solveEndOffset,
          ease: "none",
          duration: 0.2,
        },
        0
      );

      protocolTimeline.fromTo(
        solvePulseRef.current,
        { scale: 0.82, opacity: 0.34 },
        { scale: 1.1, opacity: 0.72, ease: "none", duration: 0.2 },
        0
      );

      protocolTimeline.to(
        solveCounter,
        {
          value: 96,
          ease: "none",
          duration: 0.2,
          onUpdate: () => {
            if (solveMetricRef.current) {
              solveMetricRef.current.textContent = `${Math.round(solveCounter.value)}`;
            }
          },
        },
        0
      );

      // SECTION 2: REFINEMENT
      protocolTimeline.to(".protocol-step-1", { opacity: 0 }, 0.2)
        .fromTo(".protocol-step-2", { opacity: 0, y: 50 }, { opacity: 1, y: 0 }, 0.3);

      if (gridCellsRef.current) {
        const cells = Array.from(gridCellsRef.current.children);
        const dots = cells.map((cell) => cell.firstElementChild);

        gsap.set(cells, {
          backgroundColor: "#F0EFF4",
          scale: 0.96,
        });

        gsap.set(dots, {
          opacity: 0.45,
          scale: 0.7,
        });

        protocolTimeline.fromTo(laserRef.current,
          { xPercent: -120 },
          { xPercent: 640, ease: "none", duration: 0.42 },
          0.38
        );

        protocolTimeline.to(cells, {
          backgroundColor: "#2563eb",
          scale: 1,
          duration: 0.16,
          ease: "power1.out",
          stagger: {
            grid: [5, 5],
            from: "start",
            axis: "x",
            amount: 0.22,
          },
        }, 0.4);

        protocolTimeline.to(dots, {
          opacity: 1,
          scale: 1,
          duration: 0.14,
          ease: "power1.out",
          stagger: {
            grid: [5, 5],
            from: "start",
            axis: "x",
            amount: 0.18,
          },
        }, 0.42);
      }

      // SECTION 3: EXECUTION
      protocolTimeline.to(".protocol-step-2", { opacity: 0 }, 0.7)
        .fromTo(".protocol-step-3", { opacity: 0, y: 50 }, { opacity: 1, y: 0 }, 0.8);

      const pathLength = ekgPathRef.current?.getTotalLength() || 1000;
      gsap.set(ekgPathRef.current, { strokeDasharray: pathLength, strokeDashoffset: pathLength });

      protocolTimeline.to(ekgPathRef.current, {
        strokeDashoffset: 0,
        ease: "power1.inOut",
        duration: 0.3
      }, 0.8);

    }, containerRef);

    return () => ctx.revert();
  }, []);

  const renderPill = () => (
    <svg width="100%" height="100%" viewBox="0 0 64 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="MedCapsule">
      <defs>
        <linearGradient id="pillBody" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#2563eb" />
          <stop offset="45%" stopColor="#3b82f6" />
          <stop offset="100%" stopColor="#1d4ed8" />
        </linearGradient>
        <linearGradient id="pillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
          <stop offset="0%" stopColor="rgba(255,255,255,0)" />
          <stop offset="30%" stopColor="rgba(255,255,255,0.6)" />
          <stop offset="70%" stopColor="rgba(255,255,255,0.5)" />
          <stop offset="100%" stopColor="rgba(255,255,255,0)" />
        </linearGradient>
        <linearGradient id="pillShadow" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="rgba(0,0,0,0)" />
          <stop offset="100%" stopColor="rgba(10,20,80,0.28)" />
        </linearGradient>
        <filter id="pillDrop" x="-8%" y="-15%" width="120%" height="150%">
          <feDropShadow dx="0" dy="2.5" stdDeviation="2" floodColor="#1d4ed8" floodOpacity="0.38" />
        </filter>
      </defs>
      <rect x="1" y="3" width="62" height="18" rx="9" fill="url(#pillBody)" filter="url(#pillDrop)" />
      <rect x="1" y="3" width="62" height="18" rx="9" fill="url(#pillShadow)" />
      <rect x="4" y="4" width="56" height="7" rx="3.5" fill="url(#pillGloss)" opacity="0.88" />
      <ellipse cx="12" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)" />
    </svg>
  );

  return (
    <div ref={containerRef} className="bg-white min-h-screen text-charcoal">

      {/* NAVBAR */}
      <nav className="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4">
        <div className="max-w-7xl mx-auto px-6 flex justify-between items-center">
          <div className="font-heading font-bold text-xl text-charcoal flex items-center gap-2">
            <span className="w-6 h-3 inline-block">
              {renderPill()}
            </span>
            MedCapsule
          </div>
          <div className="flex items-center gap-3">
            <LiveUserCounter appUrl={appUrl} />
            <a
              href={startHref}
              className="text-sm font-sans font-medium text-charcoal px-5 py-2 hover:bg-ghost rounded-full transition-colors whitespace-nowrap"
            >
              Start
            </a>
          </div>
        </div>
      </nav>

      {/* ////// 1. HERO SECTION ////// */}
      <section ref={heroRef} className="h-screen w-full relative flex flex-col items-center justify-center overflow-hidden bg-[radial-gradient(circle_at_center,#F0EFF4_0%,#FFFFFF_100%)]">

        {/* Typography */}
        <div className="text-center z-10 max-w-5xl px-6 relative top-[-5vh]">
          <div className="font-mono text-plasma text-xs tracking-[0.2em] font-bold mb-6 uppercase tracking-widest">
            &Delta; THE EGYPTIAN STANDARD
          </div>
          <h1 className="font-heading font-bold text-5xl md:text-7xl lg:text-8xl text-charcoal tracking-tight leading-[1] mb-2">
            Systematizing Medicine is the
          </h1>
          <h2 className="font-drama italic text-plasma text-6xl md:text-8xl lg:text-9xl leading-[0.8]">
            New Standard.
          </h2>
        </div>

        <div className="absolute top-[68vh] left-1/2 -translate-x-1/2 flex justify-center items-center w-80 h-32 z-20 pointer-events-none">
          <div ref={pillLeftRef} className="absolute inset-y-0 left-0 w-1/2 overflow-hidden">
            <div className="w-80 h-32 absolute left-0 origin-left">
              {renderPill()}
            </div>
          </div>
          <div ref={pillRightRef} className="absolute inset-y-0 right-0 w-1/2 overflow-hidden flex justify-end">
            <div className="w-80 h-32 absolute right-0 origin-right">
              {renderPill()}
            </div>
          </div>
        </div>

        {/* FEATURES OVERLAY (Revealed by GSAP) */}
        <div
          ref={featuresContentRef}
          className="absolute inset-0 z-30 bg-white flex items-center justify-center pt-24 pointer-events-none"
        >
          <div className="max-w-7xl mx-auto px-6 w-full pointer-events-auto">
            <div className="text-center mb-16">
              <h3 className="font-mono text-xs text-plasma uppercase tracking-widest mb-4">Organizational Artifacts</h3>
              <h4 className="font-heading text-4xl text-charcoal font-bold font-heading">Clinical Software Excellence.</h4>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              <DiagnosticShuffler />
              <TelemetryTypewriter />
              <CursorScheduler />
            </div>
          </div>
        </div>
      </section>

      {/* ////// 2. THE PROTOCOL ARCHIVE ////// */}
      <section ref={protocolRef} className="h-screen bg-ghost relative overflow-hidden border-t border-gray-200">
        <div className="max-w-7xl mx-auto px-6 h-full flex flex-col justify-center">
          <div className="absolute top-20 left-6 lg:left-12 z-20">
            <h3 className="font-mono text-xs text-charcoal-dim tracking-widest uppercase">Protocol Archive</h3>
          </div>

          <div className="relative h-[60vh] flex items-center">
            {/* STEP 1 */}
            <div className="protocol-step-1 absolute inset-0 flex flex-col md:flex-row items-center gap-12 w-full">
              <div className="flex-1 text-center md:text-left">
                <div className="font-mono text-6xl text-gray-200 font-bold mb-4">01</div>
                <h4 className="font-heading text-4xl font-bold text-charcoal mb-4">Solve Faster.</h4>
                <p className="font-body text-charcoal-dim text-lg">Train on the clinical patterns that matter most, so exam-style questions feel familiar under pressure.</p>
              </div>
              <div className="flex-1 flex justify-center items-center">
                <div
                  ref={solveWidgetRef}
                  className="relative w-[22rem] h-[22rem] md:w-[24rem] md:h-[24rem] flex items-center justify-center"
                >
                  <div className="absolute inset-0 rounded-full bg-[radial-gradient(circle_at_center,rgba(65,139,245,0.24)_0%,rgba(65,139,245,0.10)_36%,rgba(255,255,255,0)_72%)]" />
                  <div
                    ref={solvePulseRef}
                    className="absolute inset-[2.2rem] rounded-full bg-[radial-gradient(circle_at_center,rgba(37,99,235,0.22)_0%,rgba(37,99,235,0.1)_34%,rgba(255,255,255,0)_72%)]"
                  />
                  <div className="relative w-[18rem] h-[18rem] md:w-[19.5rem] md:h-[19.5rem] flex items-center justify-center">
                    <svg className="absolute inset-0 -rotate-90" viewBox="0 0 260 260" fill="none" aria-hidden="true">
                      <defs>
                        <linearGradient id="solveProgressGradient" x1="0" y1="0" x2="260" y2="260">
                          <stop offset="0%" stopColor="#4F86F2" />
                          <stop offset="58%" stopColor="#2563EB" />
                          <stop offset="100%" stopColor="#1D4ED8" />
                        </linearGradient>
                        <filter id="solveRingGlow" x="-20%" y="-20%" width="140%" height="140%">
                          <feGaussianBlur stdDeviation="5" result="blur" />
                          <feColorMatrix
                            in="blur"
                            type="matrix"
                            values="1 0 0 0 0.2549  0 1 0 0 0.5451  0 0 1 0 0.9608  0 0 0 0.85 0"
                          />
                        </filter>
                      </defs>
                      <circle
                        cx="130"
                        cy="130"
                        r="100"
                        stroke="rgba(37,99,235,0.16)"
                        strokeWidth="22"
                      />
                      <circle
                        ref={solveGlowRef}
                        cx="130"
                        cy="130"
                        r="100"
                        stroke="url(#solveProgressGradient)"
                        strokeWidth="22"
                        strokeLinecap="round"
                        filter="url(#solveRingGlow)"
                        opacity="0.5"
                      />
                       <circle
                        ref={solveProgressRef}
                        cx="130"
                        cy="130"
                        r="100"
                        stroke="url(#solveProgressGradient)"
                        strokeWidth="22"
                        strokeLinecap="round"
                      />
                    </svg>

                    <div className="relative w-44 h-44 md:w-48 md:h-48 rounded-full border border-[rgba(65,139,245,0.12)] bg-white shadow-[0_28px_60px_-34px_rgba(65,139,245,0.6),inset_0_1px_0_rgba(255,255,255,0.95)] flex flex-col items-center justify-center">
                      <span ref={solveMetricRef} className="font-heading text-6xl leading-none font-bold text-charcoal">
                        0
                      </span>
                      <span className="mt-3 font-mono text-[10px] tracking-[0.32em] text-plasma uppercase">
                        Readiness
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* STEP 2 */}
            <div className="protocol-step-2 absolute inset-0 flex flex-col md:flex-row items-center gap-12 w-full opacity-0 pointer-events-none">
              <div className="flex-1 text-center md:text-left">
                <div className="font-mono text-6xl text-gray-200 font-bold mb-4">02</div>
                <h4 className="font-heading text-4xl font-bold text-charcoal mb-4">Repeat Smarter.</h4>
                <p className="font-body text-charcoal-dim text-lg">Revisit weak spots with high-yield repetition that turns shaky recall into reliable exam memory.</p>
              </div>
              <div className="flex-1 flex justify-center items-center relative scale-75 md:scale-100">
                <div className="relative w-64 h-64 overflow-hidden bg-white border border-gray-200 p-4 rounded-xl shadow-clinical">
                  <div ref={gridCellsRef} className="grid grid-cols-5 gap-2 h-full">
                    {Array.from({ length: 25 }).map((_, i) => (
                      <div key={i} className="bg-ghost rounded flex items-center justify-center transition-colors">
                        <div className="w-1.5 h-1.5 rounded-full bg-white transition-opacity" />
                      </div>
                    ))}
                  </div>
                  <div ref={laserRef} className="absolute top-0 bottom-0 w-2 bg-plasma/90 z-10" />
                </div>
              </div>
            </div>

            {/* STEP 3 */}
            <div className="protocol-step-3 absolute inset-0 flex flex-col md:flex-row items-center gap-12 w-full opacity-0 pointer-events-none">
              <div className="flex-1 text-center md:text-left">
                <div className="font-mono text-6xl text-plasma/20 font-bold mb-4">03</div>
                <h4 className="font-heading text-4xl font-bold text-charcoal mb-4">Track Mastery.</h4>
                <p className="font-body text-charcoal-dim text-lg">See what is improving, what is slipping, and exactly where to focus next to keep your momentum compounding.</p>
              </div>
              <div className="flex-1 flex justify-center items-center h-64 relative bg-white border border-gray-100 rounded-2xl shadow-clinical overflow-hidden">
                <svg className="w-full h-full" viewBox="0 0 500 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                    <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#F0EFF4" strokeWidth="1" />
                  </pattern>
                  <rect width="100%" height="100%" fill="url(#grid)" />
                  <path
                    ref={ekgPathRef}
                    d="M 0 100 L 150 100 L 170 50 L 200 150 L 230 20 L 260 120 L 280 100 L 500 100"
                    stroke="#2563eb"
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="drop-shadow-[0_0_8px_rgba(37,99,235,0.6)]"
                  />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ////// 3. PRICING SECTION ////// */}
      <section className="py-32 bg-white relative">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-20">
            <h3 className="font-mono text-xs text-plasma uppercase tracking-widest mb-4">Pricing & Access</h3>
            <h4 className="font-heading text-4xl text-charcoal font-bold">Select Your Mastery Tier.</h4>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
            <div className="bg-white p-10 rounded-2xl border border-gray-100 shadow-sm hover:shadow-clinical transition-all duration-300">
              <h5 className="font-heading font-bold text-xl text-charcoal mb-2">Semester Pass</h5>
              <p className="font-body text-charcoal-dim text-sm mb-6">Full algorithmic access for the entire term.</p>
              <div className="mb-8">
                <span className="text-4xl font-heading font-bold">300</span>
                <span className="text-charcoal-dim font-sans ml-2">EGP</span>
              </div>
              <ul className="space-y-4 mb-10 text-sm text-charcoal-dim">
                {['All High-Yield Courses', 'AI Topic Shuffler', 'Mistake Tracking'].map((item, i) => (
                  <li key={i} className="flex items-center gap-2">
                    <div className="w-1.5 h-1.5 rounded-full bg-plasma" /> {item}
                  </li>
                ))}
              </ul>
              <button className="w-full py-3 rounded-full border border-gray-200 font-heading font-bold text-sm text-charcoal hover:bg-ghost transition-colors">
                Initialize Semester
              </button>
            </div>

            <div className="bg-plasma p-10 rounded-2xl shadow-2xl shadow-blue-500/20 scale-105 relative z-10 border border-plasma">
              <div className="absolute top-0 right-10 -translate-y-1/2 bg-white text-plasma text-[10px] font-mono font-bold px-3 py-1 rounded-full shadow-sm">BEST VALUE</div>
              <h5 className="font-heading font-bold text-xl text-white mb-2">Annual Mastery</h5>
              <p className="font-body text-white/80 text-sm mb-6">Master both terms with continuous protocol syncing.</p>
              <div className="mb-8">
                <span className="text-4xl font-heading font-bold text-white">550</span>
                <span className="text-white/60 font-sans ml-2">EGP</span>
              </div>
              <ul className="space-y-4 mb-10 text-white text-sm">
                {['Full Academic Year', 'Cumulative Sync', 'Early Access'].map((item, i) => (
                  <li key={i} className="flex items-center gap-2">
                    <div className="w-1.5 h-1.5 rounded-full bg-white" /> {item}
                  </li>
                ))}
              </ul>
              <button className="w-full py-3 rounded-full bg-white font-heading font-bold text-sm text-plasma hover:shadow-lg transition-all transform hover:-translate-y-1">
                Access Annual Pass
              </button>
            </div>

            <div className="bg-white p-10 rounded-2xl border border-gray-100 shadow-sm hover:shadow-clinical transition-all duration-300">
              <h5 className="font-heading font-bold text-xl text-charcoal mb-2">Collective</h5>
              <p className="font-body text-charcoal-dim text-sm mb-6">Optimized for study circle viral loops.</p>
              <div className="mb-8">
                <span className="text-4xl font-heading font-bold text-charcoal">220</span>
                <span className="text-charcoal-dim font-sans ml-2">EGP / person</span>
              </div>
              <ul className="space-y-4 mb-10 text-sm text-charcoal-dim">
                {['Circle (5+): 250 EGP', 'Batch (10+): 220 EGP', 'Batch Sync'].map((item, i) => (
                  <li key={i} className="flex items-center gap-2">
                    <div className="w-1.5 h-1.5 rounded-full bg-plasma" /> {item}
                  </li>
                ))}
              </ul>
              <button className="w-full py-3 rounded-full border border-gray-200 font-heading font-bold text-sm text-charcoal hover:bg-ghost transition-colors">
                Claim Group Rate
              </button>
            </div>
          </div>
        </div>
      </section>


    </div>
  );
}
