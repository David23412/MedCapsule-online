# MedCapsule AI Chatbot — Comprehensive Implementation Plan

## 1) Vision
Build a **minimal, premium, highly responsive AI chatbot** for MedCapsule with:
- A floating **MedCapsule blue icon** launcher
- Smooth, interactive micro-animations
- Fast, mobile-first performance
- Safe medical-response guardrails
- Scalable architecture for future growth

## 2) Product Goals
### Core
1. Delightful lightweight UX
2. Minimal interface
3. Excellent responsiveness on all devices
4. Clinically safe response behavior

### Non-goals (v1)
- Autonomous diagnosis
- Replacing physician judgment
- Overloaded dashboard UI

## 3) UX/UI Concept
### Launcher
- Floating circular button bottom-right
- Size: 56px desktop, 52px tablet, 48px mobile
- Colors: `#1E6BFF` (primary), `#4C8CFF` (highlight)
- Gentle pulse every 8s (disabled for reduced motion)

### Bot icon states
- Idle: soft glow
- Hover: slight scale (1.04) + stronger glow
- Open: morph to close icon
- Thinking: animated orbit dots

### Chat panel
- Minimal rounded card
- Desktop width: 380–420px, max-height 70vh
- Mobile: bottom sheet, 88–92vh
- Sections: header, messages, composer, quick actions

## 4) Super Responsiveness Standards
### Breakpoints
- xs `<480`
- sm `480–767`
- md `768–1023`
- lg `1024–1439`
- xl `1440+`

### Behavior
- Mobile: full-width bottom sheet, keyboard-safe composer
- Tablet: compact floating panel with larger touch targets
- Desktop: hover interactions + floating panel

### Performance targets
- Widget TTI < 1.5s on mid 4G
- FPS >= 55 during animations
- Initial widget bundle < 120KB gzipped

## 5) Recommended Architecture (Hybrid)
1. **Frontend bot UI** for interactive chat
2. **Backend orchestration (n8n/server API)** for structured workflows, logging, moderation

Why hybrid:
- Better UX latency in frontend
- Better governance/auditability in backend
- Future-proof for QBank actions

## 6) Technical Stack
### Frontend
- React/Next.js
- Tailwind (or CSS Modules)
- Framer Motion
- SVG icon animation

### AI Layer
- Frontend chat option: Puter.js
- Backend automation: Gemini/DeepSeek via server-side API

### Observability
- PostHog (analytics)
- Sentry (errors)

## 7) Conversation & Safety Design
- Tone: concise, helpful, medically careful
- Structured answers: Summary → Key points → Next step
- Safety rules:
  - No unsafe dosing without context
  - No definitive diagnosis claims
  - Recommend clinical supervision when needed

## 8) Component List
1. `ChatLauncherButton`
2. `ChatPanel`
3. `ChatHeader`
4. `MessageList`
5. `MessageBubble`
6. `TypingIndicator`
7. `Composer`
8. `QuickActions`

## 9) Animation Spec
- Open/close: spring 220ms (`stiffness 280`, `damping 24`)
- Message appear: fade + y(8→0), 120ms stagger
- Typing indicator: 3-dot bounce
- Reduced motion: fade-only fallback

## 10) Accessibility
- WCAG AA contrast
- Full keyboard navigation
- `Esc` closes chat
- ARIA labels and live-region updates

## 11) Security & Privacy
- No backend secret exposure in frontend bundle
- Rate limiting + abuse controls
- PII-aware logging
- First-chat privacy notice

## 12) Data/Integration Model
### Frontend chat
- Session memory (browser)
- Optional account-bound memory later

### Backend tools (future)
- “Flag question for review”
- “Summarize explanation”
- “Generate quiz from topic”

## 13) Phased Roadmap
### Phase 1 (Week 1) — Foundation
- Build launcher, panel, composer, mock responses
- Responsive layout complete
- MedCapsule blue icon animation complete

### Phase 2 (Week 2) — AI integration
- Connect model endpoint (Puter.js or backend API)
- Add typing indicator, retry, error UX
- Add moderation prompt layer

### Phase 3 (Week 3) — Hardening
- Telemetry + error tracking
- Accessibility and cross-device QA
- Bundle/performance optimization

### Phase 4 (Week 4) — Product polish
- Quick actions (simplify/explain/quiz)
- Persistence and history controls
- UI polish + motion tuning

## 14) Acceptance Criteria
1. Chat opens <300ms after widget load
2. Mobile keyboard never hides input
3. No overflow/layout break at all breakpoints
4. >=95% response completion in pilot
5. Accessibility smoke tests pass
6. Smooth animation on target devices

## 15) QA Matrix
- Browsers: Chrome, Safari, Edge, Firefox
- Devices: iPhone small/large, Android mid-range, iPad, laptop
- Cases: long responses, offline errors, open/close stress, reduced-motion mode, keyboard-only navigation

## 16) MedCapsule Brand Specs
- Primary blue: `#1E6BFF`
- Accent blue: `#4C8CFF`
- Surface: `#0B1220` or white depending theme
- Radius: 16–20px panel, fully rounded launcher
- Typography: clean sans-serif, high readability

## 17) Immediate Next Steps
1. Finalize blue icon SVG and motion variants
2. Implement widget shell and responsive behavior
3. Integrate first AI chat endpoint
4. Run internal beta for 7 days
5. Ship v1 with analytics and error monitoring
