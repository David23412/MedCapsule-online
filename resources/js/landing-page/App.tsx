import { useEffect, useRef, useState } from 'react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import {
  MessageCircle,
  X,
} from 'lucide-react';

gsap.registerPlugin(ScrollTrigger);

const FEATURE_CARD_CLASS = "bg-white p-5 md:p-7 rounded-[2rem] md:rounded-[2.5rem] border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_50px_rgba(8,_112,_184,_0.1)] transition-all duration-700 relative overflow-hidden h-[22rem] sm:h-96 md:h-96 w-full max-w-[24rem] md:max-w-none md:w-auto mx-auto md:mx-0 flex flex-col cursor-default group";

// --- SUB-COMPONENTS FOR ANIMATED ARTIFACTS ---

// ─── CARD 1: MCQ QUESTION CARD ───────────────────────────────────────────────

const MCQ_QUESTIONS = [
  {
    num: 12,
    question: 'Which cell type produces myelin in the CNS?',
    options: ['Astrocyte', 'Oligodendrocyte', 'Microglia', 'Schwann cell'],
    correct: 1, wrong: 2,
    explanation: 'Oligodendrocytes myelinate multiple CNS axons. Schwann cells do the same in the PNS.',
  },
  {
    num: 27,
    question: 'Which enzyme is deficient in Phenylketonuria (PKU)?',
    options: ['Tyrosinase', 'Phenylalanine hydroxylase', 'Homogentisate oxidase', 'Fumarylacetoacetase'],
    correct: 1, wrong: 0,
    explanation: 'PKU results from deficiency of phenylalanine hydroxylase, causing phenylalanine accumulation.',
  },
  {
    num: 44,
    question: 'Which layer of the heart is most vulnerable to ischemia?',
    options: ['Epicardium', 'Myocardium', 'Subendocardium', 'Pericardium'],
    correct: 2, wrong: 0,
    explanation: 'The subendocardium receives blood last and at the lowest pressure, making it most ischemia-prone.',
  },
];

const MCQCard = () => {
  const [qIdx, setQIdx] = useState(0);
  const [wrongSelected, setWrongSelected] = useState<number | null>(null);
  const [correctSelected, setCorrectSelected] = useState<number | null>(null);
  const [showExplanation, setShowExplanation] = useState(false);
  const [fading, setFading] = useState(false);

  const cardRef    = useRef<HTMLDivElement>(null);
  const cursorRef  = useRef<SVGSVGElement>(null);
  const optionRefs = useRef<(HTMLDivElement | null)[]>([]);
  const gsapCtxRef = useRef<gsap.Context | null>(null);
  const qRef       = useRef(qIdx);
  qRef.current = qIdx;

  const q = MCQ_QUESTIONS[qIdx];
  const optionLetters = ['A', 'B', 'C', 'D'];

  useEffect(() => {
    setWrongSelected(null);
    setCorrectSelected(null);
    setShowExplanation(false);
    setFading(false);

    const card   = cardRef.current;
    const cursor = cursorRef.current;
    if (!card || !cursor) return;
    if (gsapCtxRef.current) gsapCtxRef.current.revert();

    const ctx = gsap.context(() => {
      const getCenter = (el: HTMLElement) => {
        const cr = card.getBoundingClientRect();
        const er = el.getBoundingClientRect();
        return { x: er.left - cr.left + er.width / 2 - 5, y: er.top - cr.top + er.height / 2 - 5 };
      };

      const qi       = qRef.current;
      const wrongEl  = optionRefs.current[MCQ_QUESTIONS[qi].wrong];
      const rightEl  = optionRefs.current[MCQ_QUESTIONS[qi].correct];
      if (!wrongEl || !rightEl) return;

      const wp = getCenter(wrongEl);
      const rp = getCenter(rightEl);

      const tl = gsap.timeline({ delay: 0.8 });

      // enter from bottom-right
      tl.fromTo(cursor,
        { x: card.offsetWidth * 0.88, y: card.offsetHeight * 1.05, opacity: 0, scale: 0.7 },
        { x: wp.x, y: wp.y, opacity: 1, scale: 1, duration: 0.72, ease: 'power3.out' }
      );

      // click wrong
      tl.to(cursor, { scale: 0.75, duration: 0.08 })
        .add(() => setWrongSelected(MCQ_QUESTIONS[qRef.current].wrong))
        .to(cursor, { scale: 1, duration: 0.16, ease: 'elastic.out(1,0.4)' });

      // pause — viewer registers the mistake
      tl.to({}, { duration: 0.85 });

      // glide to correct
      tl.to(cursor, { x: rp.x, y: rp.y, duration: 0.62, ease: 'power2.inOut' });

      // click correct
      tl.to(cursor, { scale: 0.75, duration: 0.08 })
        .add(() => { setWrongSelected(null); setCorrectSelected(MCQ_QUESTIONS[qRef.current].correct); })
        .to(cursor, { scale: 1, duration: 0.16, ease: 'elastic.out(1,0.4)' });

      // explanation slides in
      tl.to({}, { duration: 0.45 }).add(() => setShowExplanation(true));

      // cursor drifts away
      tl.to(cursor, { y: '+=34', opacity: 0, duration: 0.48, ease: 'power2.in' }, '+=1.2');

      // advance question
      tl.add(() => {
        setFading(true);
        setTimeout(() => setQIdx(i => (i + 1) % MCQ_QUESTIONS.length), 360);
      }, '+=0.28');
    }, card);

    gsapCtxRef.current = ctx;
    return () => ctx.revert();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [qIdx]);

  return (
    <div
      ref={cardRef}
      className={FEATURE_CARD_CLASS}
      style={{ opacity: fading ? 0 : 1, transition: 'opacity 0.36s ease, transform 0.5s ease' }}
    >
      <div className="absolute top-0 right-0 w-32 h-32 bg-blue-50/30 rounded-full -mr-16 -mt-16 blur-3xl pointer-events-none" />

      <div className="mb-4 relative z-10">
        <span className="font-mono text-[11px] font-bold text-plasma/40 tracking-[0.2em]">01</span>
      </div>

      <h5 className="font-heading font-bold text-lg mb-0.5 text-charcoal">Course Question Bank</h5>
      <p className="font-body text-charcoal-dim text-[11px] leading-relaxed mb-3">
        Exam-style questions with instant feedback and explanations.
      </p>

      <div className="flex-1 flex flex-col">
        <div className="flex items-center gap-2 mb-2">
          <span className="font-mono text-[9px] text-plasma font-bold tracking-widest uppercase">Q {q.num}</span>
          <div className="flex-1 h-px bg-blue-50" />
        </div>
        <p className="font-body text-[12px] font-semibold text-charcoal mb-3 leading-snug">{q.question}</p>

        <div className="space-y-1.5">
          {q.options.map((opt, i) => {
            const isWrong = i === wrongSelected;
            const isRight = i === correctSelected;
            let bg = 'bg-[#F8F9FF]', border = 'border-transparent', textColor = 'text-charcoal', letterBg = 'bg-gray-100 text-gray-400';
            if (isWrong)  { bg = 'bg-red-50';     border = 'border-red-300';     textColor = 'text-red-700';     letterBg = 'bg-red-300 text-white'; }
            if (isRight)  { bg = 'bg-emerald-50'; border = 'border-emerald-400'; textColor = 'text-emerald-800'; letterBg = 'bg-emerald-400 text-white'; }
            return (
              <div
                key={i}
                ref={el => { optionRefs.current[i] = el; }}
                className={`flex items-center gap-2.5 px-3 py-2 rounded-xl border ${bg} ${border} transition-all duration-500`}
              >
                <span className={`w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-[10px] font-bold transition-all duration-500 ${letterBg}`}>
                  {optionLetters[i]}
                </span>
                <span className={`text-[11px] font-body transition-colors duration-500 ${textColor}`}>{opt}</span>
                {isRight && <span className="ml-auto text-emerald-500 text-[11px] font-bold">✓</span>}
                {isWrong && <span className="ml-auto text-red-400 text-[11px] font-bold">✗</span>}
              </div>
            );
          })}
        </div>

        <div
          className="mt-2 px-3 py-2 rounded-xl bg-blue-50/60 border border-blue-100 overflow-hidden transition-all duration-500"
          style={{ maxHeight: showExplanation ? 60 : 0, opacity: showExplanation ? 1 : 0, padding: showExplanation ? undefined : 0 }}
        >
          <p className="font-body text-[10px] text-plasma leading-relaxed">
            <span className="font-bold">Explanation: </span>{q.explanation}
          </p>
        </div>
      </div>

      {/* Animated cursor */}
      <svg
        ref={cursorRef}
        width="22" height="22" viewBox="0 0 20 20"
        className="absolute pointer-events-none z-50 origin-top-left drop-shadow-[0_2px_8px_rgba(0,0,0,0.28)]"
        style={{ top: 0, left: 0, opacity: 0 }}
      >
        <path d="M2 2L18 9L10.5 11L8 18L2 2Z" fill="#1A1A1A" stroke="white" strokeWidth="1.6" strokeLinejoin="round" />
      </svg>
    </div>
  );
};

// ─── CARD 2: WEAK TOPICS MASTERY BARS ────────────────────────────────────────

const TOPICS = [
  { label: 'Histology',     pct: 42, color: '#FDE047', warn: false },
  { label: 'Microbiology',  pct: 28, color: '#F87171', warn: true  },
  { label: 'Pathology',     pct: 76, color: '#34D399', warn: false },
  { label: 'Biochemistry',  pct: 55, color: '#FDE047', warn: false },
  { label: 'Pharmacology',  pct: 19, color: '#F87171', warn: true  },
];

const WeakTopicsPanel = () => {
  const [filled, setFilled] = useState(false);
  const [alertVisible, setAlertVisible] = useState(false);
  const [cycle, setCycle] = useState(0);

  useEffect(() => {
    const runCycle = () => {
      setFilled(false);
      setAlertVisible(false);
      const t1 = setTimeout(() => setFilled(true), 300);
      const t2 = setTimeout(() => setAlertVisible(true), 1200);
      const t3 = setTimeout(() => {
        setFilled(false);
        setAlertVisible(false);
        setTimeout(() => setCycle(c => c + 1), 400);
      }, 4000);
      return () => { clearTimeout(t1); clearTimeout(t2); clearTimeout(t3); };
    };
    return runCycle();
  }, [cycle]);

  return (
    <div className={FEATURE_CARD_CLASS}>
      <div className="absolute top-0 right-0 w-32 h-32 bg-amber-50/30 rounded-full -mr-16 -mt-16 blur-3xl pointer-events-none" />

      <div className="mb-4 relative z-10">
        <span className="font-mono text-[11px] font-bold text-plasma/40 tracking-[0.2em]">02</span>
      </div>

      <h5 className="font-heading font-bold text-lg mb-0.5 text-charcoal">Find Your Weak Spots</h5>
      <p className="font-body text-charcoal-dim text-[11px] leading-relaxed mb-4">
        Pinpoint where your mastery gaps are before the exam does.
      </p>

      {/* Topic bars */}
      <div className="flex-1 space-y-2.5">
        {TOPICS.map((t, i) => (
          <div key={i}>
            <div className="flex justify-between items-center mb-1">
              <span className="font-body text-[11px] font-semibold text-charcoal">
                {t.label}
              </span>
              <span
                className="font-mono text-[10px] font-bold transition-all duration-700"
                style={{ color: t.color }}
              >
                {filled ? `${t.pct}%` : '0%'}
              </span>
            </div>
            <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div
                className="h-full rounded-full transition-all duration-700"
                style={{
                  width: filled ? `${t.pct}%` : '0%',
                  background: t.color,
                  transitionDelay: `${i * 90}ms`,
                }}
              />
            </div>
          </div>
        ))}
      </div>

      {/* Alert banner */}
      <div
        className="mt-3 px-3 py-2 rounded-xl flex items-center gap-2 transition-all duration-500"
        style={{
          background: '#FEF2F2',
          border: '1px solid #FECACA',
          opacity: alertVisible ? 1 : 0,
          transform: alertVisible ? 'translateY(0)' : 'translateY(6px)',
        }}
      >
        <span className="text-red-500 text-[13px]">⚠</span>
        <p className="font-mono text-[10px] text-red-700 font-bold">
          ALERT: Retention gap in Microbiology & Pharmacology
        </p>
      </div>
    </div>
  );
};

// ─── CARD 3: PERFORMANCE CHART (replicates profile-page analytics) ─────────

// 12 quiz data points: score < 50 = red, 50-89 = yellow, ≥90 = green
const PERF_DATA = [
  { score: 38, label: 'Anatomy'       },
  { score: 45, label: 'Histology'     },
  { score: 52, label: 'Biochem'       },
  { score: 49, label: 'Physiology'    },
  { score: 63, label: 'Micro'         },
  { score: 71, label: 'Pathology'     },
  { score: 68, label: 'Pharmacology'  },
  { score: 80, label: 'Anatomy'       },
  { score: 88, label: 'Histology'     },
  { score: 85, label: 'Micro'         },
  { score: 92, label: 'Pathology'     },
  { score: 96, label: 'Biochem'       },
];

const scoreColor = (s: number) =>
  s >= 90 ? '#10B981' : s >= 50 ? '#F59E0B' : '#EF4444';

const PerformanceChart = () => {
  const svgRef    = useRef<SVGSVGElement>(null);
  const pathRef   = useRef<SVGPathElement>(null);
  const fillRef   = useRef<SVGPathElement>(null);
  const dotsRef   = useRef<SVGCircleElement[]>([]);
  const glowsRef  = useRef<SVGCircleElement[]>([]);
  const ctxRef    = useRef<gsap.Context | null>(null);
  const [cycle, setCycle] = useState(0);
  const [tooltip, setTooltip] = useState<{ label: string; score: number; color: string } | null>(null);

  // SVG canvas dimensions
  const W = 320, H = 140;
  const PAD = { l: 28, r: 12, t: 14, b: 24 };
  const innerW = W - PAD.l - PAD.r;
  const innerH = H - PAD.t - PAD.b;

  // Map data to SVG coords
  const pts = PERF_DATA.map((d, i) => ({
    x: PAD.l + (i / (PERF_DATA.length - 1)) * innerW,
    y: PAD.t + (1 - d.score / 100) * innerH,
    ...d,
  }));

  // Build a smooth monotone cubic path string
  const buildPath = (points: typeof pts) => {
    if (points.length < 2) return '';
    let d = `M ${points[0].x} ${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
      const cp1x = (points[i].x + points[i + 1].x) / 2;
      const cp2x = cp1x;
      d += ` C ${cp1x} ${points[i].y} ${cp2x} ${points[i + 1].y} ${points[i + 1].x} ${points[i + 1].y}`;
    }
    return d;
  };

  const linePath  = buildPath(pts);
  const fillPath  = linePath + ` L ${pts[pts.length - 1].x} ${H - PAD.b} L ${pts[0].x} ${H - PAD.b} Z`;

  useEffect(() => {
    const svg   = svgRef.current;
    const path  = pathRef.current;
    const fill  = fillRef.current;
    if (!svg || !path || !fill) return;
    if (ctxRef.current) ctxRef.current.revert();

    // Reset dots
    dotsRef.current.forEach(d => { if (d) gsap.set(d, { scale: 0, opacity: 0, transformOrigin: '50% 50%' }); });
    glowsRef.current.forEach(g => { if (g) gsap.set(g, { scale: 0, opacity: 0, transformOrigin: '50% 50%' }); });
    gsap.set(fill, { opacity: 0 });

    const totalLen = path.getTotalLength();
    gsap.set(path, { strokeDasharray: totalLen, strokeDashoffset: totalLen });

    const ctx = gsap.context(() => {
      const tl = gsap.timeline({ delay: 0.4, onComplete: () => {
        setTimeout(() => setCycle(c => c + 1), 1800);
      }});

      // 1. Draw the line left-to-right
      tl.to(path, { strokeDashoffset: 0, duration: 2.2, ease: 'power2.inOut' });

      // 2. Fill fades in
      tl.to(fill, { opacity: 1, duration: 0.6, ease: 'power1.in' }, '-=0.8');

      // 3. Dots pop in staggered
      tl.to(dotsRef.current.filter(Boolean), {
        scale: 1, opacity: 1, duration: 0.35, ease: 'back.out(2.5)',
        stagger: 0.12,
      }, '-=1.6');

      // 4. Glows pulse in behind dots
      tl.to(glowsRef.current.filter(Boolean), {
        scale: 1, opacity: 0.4, duration: 0.4, ease: 'power1.out',
        stagger: 0.12,
      }, '<');
    }, svg);

    ctxRef.current = ctx;
    return () => ctx.revert();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [cycle]);

  // Y-axis labels
  const yTicks = [100, 90, 50, 0];

  return (
    <div className={FEATURE_CARD_CLASS}>
      <div className="absolute top-0 right-0 w-32 h-32 bg-emerald-50/20 rounded-full -mr-16 -mt-16 blur-3xl pointer-events-none" />

      <div className="mb-4 relative z-10">
        <span className="font-mono text-[11px] font-bold text-plasma/40 tracking-[0.2em]">03</span>
      </div>

      <h5 className="font-heading font-bold text-lg mb-0.5 text-charcoal">Progress Over Time</h5>
      <p className="font-body text-charcoal-dim text-[11px] leading-relaxed mb-3">
        Your quiz accuracy across sessions — color-coded by performance zone.
      </p>

      {/* Legend */}
      <div className="flex items-center gap-3 mb-3">
        {[['#EF4444','< 50%'], ['#F59E0B','50–89%'], ['#10B981','≥90%']].map(([c, l]) => (
          <div key={l} className="flex items-center gap-1.5">
            <div className="w-2 h-2 rounded-full" style={{ background: c }} />
            <span className="font-mono text-[9px] text-charcoal-dim">{l}</span>
          </div>
        ))}
      </div>

      {/* SVG Chart */}
      <div className="flex-1 relative">
        <svg
          ref={svgRef}
          viewBox={`0 0 ${W} ${H}`}
          className="w-full h-full overflow-visible"
          onMouseLeave={() => setTooltip(null)}
        >
          <defs>
            {/* Segmented gradient fill matching the real chart */}
            <linearGradient id="perfFill" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0%"   stopColor="#EF4444" stopOpacity="0.25" />
              <stop offset="35%"  stopColor="#F59E0B" stopOpacity="0.22" />
              <stop offset="65%"  stopColor="#F59E0B" stopOpacity="0.22" />
              <stop offset="100%" stopColor="#10B981" stopOpacity="0.28" />
            </linearGradient>
            <linearGradient id="perfLine" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0%"   stopColor="#EF4444" />
              <stop offset="35%"  stopColor="#F59E0B" />
              <stop offset="65%"  stopColor="#F59E0B" />
              <stop offset="100%" stopColor="#10B981" />
            </linearGradient>
          </defs>

          {/* Subtle horizontal grid lines */}
          {[90, 50].map(y => {
            const cy = PAD.t + (1 - y / 100) * innerH;
            return (
              <line key={y}
                x1={PAD.l} x2={W - PAD.r} y1={cy} y2={cy}
                stroke={y === 90 ? '#10B98122' : '#F59E0B22'}
                strokeWidth="1"
                strokeDasharray="4 3"
              />
            );
          })}

          {/* Y-axis tick labels */}
          {yTicks.map(v => (
            <text key={v}
              x={PAD.l - 4}
              y={PAD.t + (1 - v / 100) * innerH + 4}
              textAnchor="end"
              className="font-mono"
              style={{ fontSize: 8, fill: '#9ca3af' }}
            >{v}%</text>
          ))}

          {/* Fill area */}
          <path ref={fillRef} d={fillPath} fill="url(#perfFill)" opacity={0} />

          {/* Main line */}
          <path
            ref={pathRef}
            d={linePath}
            fill="none"
            stroke="url(#perfLine)"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />

          {/* Glow halos behind dots */}
          {pts.map((p, i) => (
            <circle
              key={`g-${i}`}
              ref={el => { if (el) glowsRef.current[i] = el; }}
              cx={p.x} cy={p.y} r={9}
              fill={scoreColor(p.score)}
              opacity={0}
            />
          ))}

          {/* Data dots */}
          {pts.map((p, i) => {
            const r = 3.5 + (i / (pts.length - 1)) * 2.5; // bigger = more recent
            return (
              <circle
                key={`d-${i}`}
                ref={el => { if (el) dotsRef.current[i] = el; }}
                cx={p.x} cy={p.y} r={r}
                fill={scoreColor(p.score)}
                stroke="white"
                strokeWidth="2"
                opacity={0}
                style={{ cursor: 'default' }}
                onMouseEnter={() => setTooltip({ label: p.label, score: p.score, color: scoreColor(p.score) })}
              />
            );
          })}
        </svg>

        {/* Tooltip */}
        {tooltip && (
          <div
            className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 bg-white border border-gray-100 rounded-xl shadow-lg px-3 py-2 pointer-events-none z-20"
          >
            <p className="font-mono text-[10px] font-bold" style={{ color: tooltip.color }}>{tooltip.score}%</p>
            <p className="font-mono text-[9px] text-charcoal-dim">{tooltip.label}</p>
          </div>
        )}
      </div>
    </div>
  );
};

type AppProps = {
  isAuthenticated?: boolean;
  appUrl?: string;
  user?: {
    name: string;
    profile_image_url: string;
    profile_fallback_url: string;
  } | null;
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

const FloatingContact = () => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className="fixed bottom-8 right-8 z-[1000] flex flex-col items-end gap-4">
      {/* Contact Options */}
      <div 
        className={`flex flex-col gap-3 transition-all duration-300 transform origin-bottom ${
          isOpen ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4 pointer-events-none'
        }`}
      >
        <a 
          href="https://t.me/Medcapsule1" 
          target="_blank" 
          rel="noopener noreferrer"
          className="flex items-center gap-3 bg-white px-4 py-2.5 rounded-full shadow-lg border border-blue-50 group hover:bg-blue-50 transition-colors"
        >
          <span className="text-xs font-heading font-bold text-charcoal">Join Telegram</span>
          <div className="w-8 h-8 rounded-full bg-[#0088cc] flex items-center justify-center text-white">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.69-.88-.48-1.38-.78-2.23-1.34-1-.65-.35-1 .22-1.67.15-.17 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.18s-.21-.01-.3.01c-.13.02-2.13 1.34-6.02 2.69-.57.2-.91.2-.91.2s-1.05-.33-1.05-.33c-.5-.16-.9-.25-.86-.53.02-.15.21-.3.57-.45 2.21-.96 3.68-1.6 4.41-1.92 2.1-.91 2.53-1.07 2.82-1.07.06 0 .46.02.67.18.17.14.22.33.24.47z"/>
            </svg>
          </div>
        </a>
      </div>

      {/* Main Toggle Button */}
      <button 
        onClick={() => setIsOpen(!isOpen)}
        className={`w-14 h-14 rounded-full bg-white shadow-xl flex items-center justify-center text-plasma border border-blue-50 transition-all duration-300 transform hover:scale-110 active:scale-95 ${isOpen ? 'rotate-90' : 'rotate-0'}`}
        aria-label="Contact Us"
      >
        {isOpen ? <X size={24} strokeWidth={2.5} /> : <MessageCircle size={24} strokeWidth={2.5} />}
      </button>
    </div>
  );
};

export default function App({ isAuthenticated = false, appUrl, user }: AppProps) {
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
      gsap.set(featuresContentRef.current, { opacity: 0, y: 300, scale: 0.9 });
      splitTimeline.to(featuresContentRef.current,
        { opacity: 1, y: 0, scale: 1, duration: 0.5, ease: "power2.out" },
        0.2
      );

      // 2. THE PROTOCOL ARCHIVE (PINNED SCROLL)
      const protocolTimeline = gsap.timeline({
        scrollTrigger: {
          trigger: protocolRef.current,
          start: "top top",
          end: "+=450%", // 3 sections of scrolling with more breathing room
          scrub: 0.5,
          pin: true,
        }
      });

      // SECTION 1: SOLVE FASTER
      const solveRadius = 100;
      const solveCircumference = 2 * Math.PI * solveRadius;
      const solveEndOffset = 0;
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
          value: 100,
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

        // Initial state
        gsap.set(cells, { backgroundColor: "#F0EFF4", scale: 0.96 });
        gsap.set(dots, { opacity: 0.45, scale: 0.7 });

        // Cycle 1: Significant mistakes (organized spread)
        const mistakes1 = [cells[2], cells[11], cells[14], cells[22]];
        protocolTimeline.to(mistakes1, {
          backgroundColor: "#ef4444", 
          duration: 0.1,
          stagger: 0.05
        }, 0.3);

        // Cycle 2: Progressive correction (fewer red)
        protocolTimeline.to([cells[2], cells[14]], { backgroundColor: "#22c55e", duration: 0.1 }, 0.42);
        protocolTimeline.to([cells[11], cells[22]], { backgroundColor: "#ef4444", duration: 0.1 }, 0.42);

        // Cycle 3: One red box left (almost perfect)
        protocolTimeline.to(cells[11], { backgroundColor: "#22c55e", duration: 0.1 }, 0.5); 
        protocolTimeline.to(cells[22], { backgroundColor: "#ef4444", duration: 0.1 }, 0.5);

        // Final Cycle: Sweep to Full Mastery (starts slightly later now at 0.58)
        protocolTimeline.to(cells, {
          backgroundColor: "#22c55e",
          scale: 1,
          duration: 0.25,
          stagger: {
            grid: [5, 5],
            from: "start", // Sweep from top-left
            amount: 0.35
          }
        }, 0.58);

        protocolTimeline.to(dots, {
          opacity: 1,
          scale: 1,
          duration: 0.2,
          stagger: {
            grid: [5, 5],
            from: "start",
            amount: 0.25
          }
        }, 0.55);
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
        <linearGradient id="pillBlue" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#2563eb" />
          <stop offset="100%" stopColor="#1d4ed8" />
        </linearGradient>
        <linearGradient id="pillRed" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#ef4444" />
          <stop offset="100%" stopColor="#dc2626" />
        </linearGradient>
        <clipPath id="pillClip">
          <rect x="1" y="4" width="62" height="18" rx="9" />
        </clipPath>
      </defs>
      <g clipPath="url(#pillClip)">
        <rect x="1" y="4" width="31" height="18" fill="url(#pillBlue)" />
        <rect x="32" y="4" width="31" height="18" fill="url(#pillRed)" />
      </g>
      <line x1="32" y1="4" x2="32" y2="22" stroke="white" strokeWidth="1" />
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

            {isAuthenticated && user && (
              <a
                href={`${appUrl}/profile`}
                className="w-9 h-9 rounded-full overflow-hidden border border-gray-100 bg-gray-50 flex-shrink-0 transition-opacity duration-300 ml-1 mr-5 md:mr-0 hover:opacity-80"
                aria-label="Profile Settings"
              >
                <img
                  src={user.profile_image_url}
                  alt={user.name}
                  className="w-full h-full object-cover"
                  onError={(e) => {
                    (e.target as HTMLImageElement).src = user.profile_fallback_url;
                  }}
                />
              </a>
            )}
          </div>
        </div>
      </nav>

      {/* ////// 1. HERO SECTION ////// */}
      <section ref={heroRef} className="min-h-screen md:h-screen w-full relative flex flex-col items-center md:justify-center overflow-hidden bg-[radial-gradient(circle_at_center,#F0EFF4_0%,#FFFFFF_100%)] pt-20 md:pt-0">

        {/* Typography */}
        <div className="text-center z-10 max-w-5xl px-6 relative md:top-[-5vh] mt-24 md:mt-0 mb-4 md:mb-0">
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

        {/* Pill split animation */}
        <div className="relative md:absolute md:top-[68vh] md:left-1/2 md:-translate-x-1/2 flex justify-center items-center w-80 h-32 z-20 pointer-events-none mt-8 md:mt-0">
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

        {/* FEATURES OVERLAY (Revealed by GSAP on desktop; always visible on mobile) */}
        {/* On mobile: relative + auto-height so cards are naturally scrollable with the page */}
        {/* On desktop: absolute + opacity:0 initially, GSAP animates it in via scroll */}
        <div
          ref={featuresContentRef}
          className="relative md:absolute md:inset-0 z-30 bg-white flex items-start md:items-center justify-center overflow-visible md:overflow-hidden pt-6 md:pt-24 pb-8 md:pb-0"
          style={{ opacity: 1 }}
        >
          <div className="max-w-7xl mx-auto px-4 sm:px-6 w-full">
            <div className="text-center mb-8 md:mb-16">
              <h3 className="font-mono text-xs text-plasma uppercase tracking-widest mb-4">Core Study Tools</h3>
              <h4 className="font-heading text-3xl md:text-4xl text-charcoal font-bold font-heading">Master your medical curriculum.</h4>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 px-1 md:px-0 pb-8 md:pb-0">
              <MCQCard />
              <WeakTopicsPanel />
              <PerformanceChart />
            </div>
          </div>
        </div>
      </section>

      {/* ////// 2. THE PROTOCOL ARCHIVE ////// */}
      <section ref={protocolRef} className="h-screen bg-ghost relative overflow-hidden border-t border-gray-200">
        <div className="max-w-7xl mx-auto px-6 h-full flex flex-col justify-center">
          <div className="absolute top-20 left-6 lg:left-12 z-20">
            <h3 className="font-mono text-xs text-charcoal-dim tracking-widest uppercase">The MedCapsule Method</h3>
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
                </div>
              </div>
            </div>

            {/* STEP 3 */}
            <div className="protocol-step-3 absolute inset-0 flex flex-col md:flex-row items-center gap-12 w-full opacity-0 pointer-events-none">
              <div className="flex-1 text-center md:text-left">
                <div className="font-mono text-6xl text-plasma/20 font-bold mb-4">03</div>
                <h4 className="font-heading text-4xl font-bold text-charcoal mb-4">Track Progress.</h4>
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
      <section className="hidden py-32 bg-white relative overflow-hidden">
        
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

      <FloatingContact />
    </div>
  );
}
