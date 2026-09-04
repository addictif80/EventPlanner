<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --ep-primary: #3b56d9;
    --ep-primary-dark: #2a3fae;
    --ep-ink: #101828;
    --ep-muted: #5b6472;
    --ep-border: #e6e9f0;
    --ep-surface: #f7f8fc;
  }
  body { color: var(--ep-ink); font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
  h1, h2, h3, h4, .fw-bold, .fw-semibold { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; letter-spacing: -0.01em; }
  .text-secondary { color: var(--ep-muted) !important; }

  .navbar-landing { background: rgba(255,255,255,.92); backdrop-filter: saturate(180%) blur(8px); border-bottom: 1px solid var(--ep-border); }
  .navbar-landing .nav-link-custom { color: var(--ep-muted); font-weight: 500; font-size: .925rem; }
  .navbar-landing .nav-link-custom:hover { color: var(--ep-ink); }

  .btn-primary { background: var(--ep-primary); border-color: var(--ep-primary); font-weight: 600; box-shadow: 0 10px 22px -8px rgba(59,86,217,.45); }
  .btn-primary:hover, .btn-primary:focus { background: var(--ep-primary-dark); border-color: var(--ep-primary-dark); box-shadow: 0 12px 26px -8px rgba(59,86,217,.55); }
  .btn-outline-primary { color: var(--ep-primary); border-color: var(--ep-primary); font-weight: 600; }
  .btn-outline-secondary { font-weight: 500; }
  .btn-outline-secondary.btn-lg, .btn-outline-dark.btn-lg, .btn-outline-light.btn-lg { box-shadow: 0 8px 18px -8px rgba(16,24,40,.15); }
  .btn-light { box-shadow: 0 10px 22px -6px rgba(0,0,0,.25); }

  .hero { background:
      radial-gradient(1100px 480px at 85% -10%, rgba(59,86,217,.10), transparent 60%),
      radial-gradient(700px 400px at -5% 15%, rgba(255,180,0,.08), transparent 60%),
      #fbfbfe;
    border-bottom: 1px solid var(--ep-border);
  }
  .eyebrow { display:inline-flex; align-items:center; gap:.5rem; background:#eef1fd; color: var(--ep-primary); font-weight:600; font-size:.8rem; padding:.35rem .75rem; border-radius:999px; }
  .hero h1 { font-weight: 800; letter-spacing: -0.02em; color: var(--ep-ink); }

  .hero-mock { background:#fff; border:1px solid var(--ep-border); border-radius:1rem; box-shadow: 0 1.5rem 3rem -1rem rgba(16,24,40,.18), 0 4px 12px rgba(16,24,40,.06); }
  .hero-mock .dot { width:.55rem; height:.55rem; border-radius:50%; display:inline-block; }

  .trust-strip { border-top: 1px solid var(--ep-border); border-bottom: 1px solid var(--ep-border); background: #fff; }
  .trust-item { color: var(--ep-muted); font-weight: 600; font-size: .85rem; }
  .trust-item i { color: var(--ep-primary); }

  section { scroll-margin-top: 76px; }
  .section-eyebrow { color: var(--ep-primary); font-weight: 700; font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; }

  .feature-card { border: 1px solid var(--ep-border); border-radius: .9rem; height: 100%; background: #fff; box-shadow: 0 6px 16px -10px rgba(16,24,40,.14); transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease; }
  .feature-card:hover { box-shadow: 0 1.25rem 2.25rem -0.5rem rgba(16,24,40,.16); border-color: #d7dcf2; transform: translateY(-2px); }
  .feature-icon { width: 2.75rem; height: 2.75rem; border-radius: .65rem; background: #eef1fd; color: var(--ep-primary); }

  .diff-card { border: 1px solid var(--ep-border); border-radius: 1rem; background: #fff; height: 100%; box-shadow: 0 10px 24px -14px rgba(16,24,40,.18); }
  .diff-card .diff-icon { width: 3rem; height: 3rem; border-radius: .75rem; background: linear-gradient(135deg, var(--ep-primary), #6c7ff2); color: #fff; box-shadow: 0 8px 16px -6px rgba(59,86,217,.45); }
  .diff-badge { background: #fff7e6; color: #9a6b00; font-weight: 600; font-size: .72rem; padding: .2rem .55rem; border-radius: 999px; box-shadow: 0 2px 6px -2px rgba(154,107,0,.35); }

  .compare-table { border: 1px solid var(--ep-border); border-radius: 1rem; overflow: hidden; background: #fff; box-shadow: 0 12px 28px -16px rgba(16,24,40,.20); }
  .compare-table th { background: var(--ep-surface); font-weight: 700; }
  .compare-table td, .compare-table th { padding: .85rem 1.1rem; vertical-align: middle; }
  .compare-yes { color: #157347; font-size: 1.1rem; }
  .compare-no { color: #adb5bd; font-size: 1.1rem; }

  .step-num { width: 2.25rem; height: 2.25rem; border-radius: 50%; background: var(--ep-ink); color: #fff; font-weight: 700; flex: none; box-shadow: 0 8px 16px -6px rgba(16,24,40,.35); }

  .plan-card { border: 1px solid var(--ep-border); border-radius: 1rem; background: #fff; box-shadow: 0 8px 20px -14px rgba(16,24,40,.18); }
  .plan-card.plan-highlight { border-color: var(--ep-primary); box-shadow: 0 1.5rem 2.5rem -1rem rgba(59,86,217,.20); }

  .cta-band { background: linear-gradient(135deg, var(--ep-ink), #1d2b6b 60%, var(--ep-primary)); color: #fff; }

  .footer-landing { border-top: 1px solid var(--ep-border); background: #fff; }

  /* Bloc "aperçu" à onglets du hero — 4-5 fonctionnalités phares */
  .mock-tabs { border-bottom: 1px solid var(--ep-border); padding-bottom: .75rem; }
  .mock-tab { border: 1px solid var(--ep-border); background: #fff; color: var(--ep-muted); font-size: .78rem; font-weight: 600; padding: .35rem .7rem; border-radius: 999px; transition: all .15s ease; }
  .mock-tab:hover { border-color: #c7cff0; color: var(--ep-ink); }
  .mock-tab.active { background: var(--ep-primary); border-color: var(--ep-primary); color: #fff; box-shadow: 0 6px 14px -6px rgba(59,86,217,.5); }
  .mock-panel { min-height: 190px; }
  .shadow-soft { box-shadow: 0 8px 18px -10px rgba(16,24,40,.18); }
  .portal-preview-header { background: var(--ep-surface); }
  .portal-logo { width: 2.25rem; height: 2.25rem; border-radius: .5rem; background: linear-gradient(135deg, var(--ep-primary), #6c7ff2); color: #fff; font-weight: 700; font-size: .75rem; display: flex; align-items: center; justify-content: center; flex: none; box-shadow: 0 6px 14px -6px rgba(59,86,217,.45); }
  .slot-chip { border: 1px solid var(--ep-border); background: #fff; border-radius: .5rem; padding: .3rem .65rem; font-size: .8rem; font-weight: 600; color: var(--ep-ink); box-shadow: 0 4px 10px -6px rgba(16,24,40,.2); }

  /* Emphase (texte important) : légère ombre pour donner du relief aux chiffres/titres clés */
  .hero h1, .display-5, .display-6 { text-shadow: 0 1px 0 rgba(16,24,40,.04); }
  .fs-4.fw-bold, .fs-5.fw-bold { text-shadow: 0 1px 0 rgba(16,24,40,.03); }
  .eyebrow { box-shadow: 0 6px 14px -8px rgba(59,86,217,.35); }
</style>
