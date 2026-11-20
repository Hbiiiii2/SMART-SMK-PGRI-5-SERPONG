<style>
    /* OpenDesign Airtable Design System Tokens (od://design-systems/airtable) */
    :root {
        --at-bg: #ffffff;
        --at-canvas: #f8fafc;
        --at-surface: #ffffff;
        --at-fg: #181d26;
        --at-fg-2: #333333;
        --at-muted: rgba(4, 14, 32, 0.69);
        --at-border: #e0e2e6;
        --at-border-soft: #eef0f3;
        --at-accent: #1b61c9;
        --at-accent-hover: #254fad;
        --at-accent-active: #15408e;
        --at-success: #006400;
        --at-success-tint: #e8f5ec;
        --at-warn: #eab308;
        --at-danger: #dc2626;
        --at-font-display: "Haas Groot Disp", "Outfit", -apple-system, system-ui, "Segoe UI", Roboto, sans-serif;
        --at-font-body: "Haas", "Outfit", -apple-system, system-ui, "Segoe UI", Roboto, sans-serif;
        --at-font-mono: ui-monospace, "SF Mono", "JetBrains Mono", Menlo, Monaco, Consolas, monospace;
        --at-radius-sm: 12px;
        --at-radius-md: 16px;
        --at-radius-lg: 24px;
        --at-radius-pill: 9999px;
        --at-elev-raised:
            0 0 1px rgba(0, 0, 0, 0.32),
            0 0 2px rgba(0, 0, 0, 0.08),
            0 1px 3px rgba(45, 127, 249, 0.28),
            inset 0 0 0 0.5px rgba(0, 0, 0, 0.06);
        --at-elev-soft: 0 0 20px rgba(15, 48, 106, 0.05);
        --at-focus-ring: 0 0 0 3px rgba(27, 97, 201, 0.28);
        --at-motion-fast: 150ms;
        --at-ease: cubic-bezier(0.2, 0, 0, 1);
    }

    html.dark {
        color-scheme: dark;
        --at-bg: #0b111c;
        --at-canvas: #0f172a;
        --at-surface: #131c2e;
        --at-fg: #f3f6fc;
        --at-fg-2: #cbd5e1;
        --at-muted: #94a3b8;
        --at-border: #263248;
        --at-border-soft: #1e293b;
        --at-success: #4ade80;
        --at-success-tint: rgba(22, 163, 74, 0.16);
        --at-elev-raised:
            0 0 1px rgba(0, 0, 0, 0.65),
            0 8px 24px rgba(2, 6, 23, 0.45),
            inset 0 0 0 1px rgba(148, 163, 184, 0.12);
    }

    .at-body {
        background-color: var(--at-canvas);
        color: var(--at-fg-2);
        font-family: var(--at-font-body);
        line-height: 1.35;
        letter-spacing: 0.08px;
    }

    .at-heading {
        font-family: var(--at-font-display);
        color: var(--at-fg);
        line-height: 1.2;
        letter-spacing: 0;
        text-wrap: balance;
    }

    .at-card {
        background-color: var(--at-surface);
        border: 1px solid var(--at-border);
        border-radius: var(--at-radius-md);
        box-shadow: var(--at-elev-raised);
    }

    .at-input {
        min-height: 46px;
        width: 100%;
        border-radius: var(--at-radius-sm);
        border: 1px solid var(--at-border);
        background-color: var(--at-surface);
        color: var(--at-fg);
        font-family: var(--at-font-body);
        font-size: 15px;
        letter-spacing: 0.08px;
        padding: 10px 14px;
        transition:
            border-color var(--at-motion-fast) var(--at-ease),
            box-shadow var(--at-motion-fast) var(--at-ease),
            background-color var(--at-motion-fast) var(--at-ease);
    }

    .at-input::placeholder {
        color: var(--at-muted);
    }

    .at-input:focus-visible {
        outline: none;
        border-color: var(--at-accent);
        box-shadow: var(--at-focus-ring);
    }

    .at-btn-primary {
        min-height: 48px;
        border-radius: var(--at-radius-sm);
        background-color: var(--at-accent);
        color: #ffffff;
        font-family: var(--at-font-body);
        font-size: 15px;
        font-weight: 500;
        letter-spacing: 0.08px;
        line-height: 1.25;
        transition:
            background-color var(--at-motion-fast) var(--at-ease),
            transform var(--at-motion-fast) var(--at-ease),
            box-shadow var(--at-motion-fast) var(--at-ease);
    }

    .at-btn-primary:hover {
        background-color: var(--at-accent-hover);
    }

    .at-btn-primary:active {
        background-color: var(--at-accent-active);
        transform: scale(0.99);
    }

    .at-btn-primary:focus-visible {
        outline: none;
        box-shadow: var(--at-focus-ring);
    }

    .at-base-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        border-radius: var(--at-radius-pill);
        border: 1px solid var(--at-border);
        background-color: var(--at-surface);
        color: var(--at-fg);
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.14px;
    }

    .at-grid-bg {
        background-image:
            linear-gradient(to right, rgba(224, 226, 230, 0.45) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(224, 226, 230, 0.45) 1px, transparent 1px);
        background-size: 32px 32px;
    }

    html.dark .at-grid-bg {
        background-image:
            linear-gradient(to right, rgba(51, 65, 85, 0.25) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(51, 65, 85, 0.25) 1px, transparent 1px);
    }
</style>
