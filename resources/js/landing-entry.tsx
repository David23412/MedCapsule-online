import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import '~/landing-page.css'
import App from './landing-page/App.tsx'

type LandingConfig = {
  isAuthenticated?: boolean
  appUrl?: string
  user?: {
    name: string
    profile_image_url: string
    profile_fallback_url: string
  } | null
}

declare global {
  interface Window {
    __MEDCAPSULE_LANDING__?: LandingConfig
  }
}

const landingConfig = window.__MEDCAPSULE_LANDING__ ?? {}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App
      appUrl={landingConfig.appUrl}
      isAuthenticated={landingConfig.isAuthenticated}
      user={landingConfig.user}
    />
  </StrictMode>,
)
