<?php
if (!defined('ABSPATH'))
    exit;
// ============================================================
// GLOBAL STYLES
// ============================================================

function ch_global_styles()
{
    ob_start(); ?>
    <style>
        :root {
            --ch-accent: #FF7551;
            --ch-accent-dark: #FF6640;
            --ch-accent-light: #FFE3DA;
            --ch-accent-mid: #FF9A7F;
            --ch-bg: #FFFFFF;
            --ch-surface: #FFF8F5;
            --ch-border: #EFEBE9;
            --ch-border-soft: #F8F6F5;
            --ch-text: #1E1E22;
            --ch-text-muted: #5F616B;
            --ch-text-subtle: #8A8E99;
            --ch-radius-sm: 6px;
            --ch-radius: 8px;
            --ch-radius-md: 12px;
            --ch-radius-lg: 16px;
            --ch-radius-xl: 24px;
            --ch-shadow-sm: 0 2px 8px rgba(17, 24, 39, 0.04);
            --ch-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
            --ch-shadow-md: 0 12px 32px rgba(17, 24, 39, 0.08);
            --ch-scroll-track: #f8fafc;
            --ch-scroll-thumb: #cbd5e1;
            --ch-scroll-thumb-hover: #94a3b8;
            --ch-font: 'Satoshi', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        html {
            scroll-padding-top: 80px;
        }

        /* Hide third-party debug overlay that can appear on frontend pages */
        body > div[style*="position:fixed"][style*="bottom:10px"][style*="right:10px"][style*="font-family:monospace"],
        body > div[style*="position:fixed"][style*="bottom:10px"][style*="right:10px"][style*="max-height:30vh"][style*="overflow:auto"] {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }




        .ch-dashboard-wrap,
        .ch-feed-wrap,
        .ch-post-view-wrap,
        .ch-auth-wrap,
        .ch-my-feed-wrap,
        .ch-public-profile-wrap,
        .ch-guest-landing-wrap,
        .ch-mf-page-wrap {
            font-family: var(--ch-font);
            color: var(--ch-text);
            line-height: 1.55;
            background: var(--ch-bg);
        }

        /* TOP NAV */
        .ch-burger-menu-btn {
            display: none; /* hidden on desktop; shown via mobile media query */
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
            color: var(--ch-text);
            flex-shrink: 0;
            outline: none;
            border-radius: var(--ch-radius-sm);
            transition: background 0.2s;
            -webkit-tap-highlight-color: transparent;
        }

        .ch-burger-menu-btn:hover {
            background: color-mix(in srgb, var(--ch-text) 8%, transparent);
        }

        .ch-top-nav {
            background: rgba(255, 255, 255, 0.88);
            border-bottom: 1px solid var(--ch-border);
            /* Grid: [logo] [nav links] [user actions] — prevents overlap at all widths */
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            padding: 0 28px;
            height: 64px;
            box-sizing: border-box;
            transition: background 0.25s ease, box-shadow 0.25s ease;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06), 0 4px 24px rgba(17, 24, 39, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .ch-dark .ch-top-nav {
            background: rgba(15, 23, 42, 0.92);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.06), 0 4px 24px rgba(0, 0, 0, 0.3);
        }

        /* Desktop nav center cluster — static flow, no absolute positioning */
        .ch-top-nav-center {
            position: static;
            left: auto;
            transform: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
            min-width: 0;
        }

        .ch-nav-links {
            display: flex;
            gap: 2px;
            height: 100%;
            align-items: center;
            flex-wrap: nowrap;
        }

        .ch-top-nav-notifications {
            display: flex;
            align-items: center;
        }

        .ch-nav-label {
            display: inline;
        }

        .ch-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--ch-radius);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--ch-text-muted);
            transition: background 0.15s, color 0.15s;
            height: 38px;
            letter-spacing: -0.1px;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .ch-nav-link:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-nav-link.active {
            color: var(--ch-accent);
            font-weight: 600;
            position: relative;
            background: color-mix(in srgb, var(--ch-accent) 10%, transparent);
        }

        .ch-nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -13px;
            left: 50%;
            transform: translateX(-50%);
            width: 28px;
            height: 3px;
            background: var(--ch-accent);
            border-radius: 3px 3px 0 0;
        }

        .ch-user-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Hide desktop-only bars on mobile; show them on desktop */
        .ch-nav-user-desktop,
        .ch-nav-guest-desktop {
            display: none;
        }

        /* ── Desktop layout (≥781px) ── */
        @media (min-width: 781px) {
            .ch-top-nav {
                grid-template-columns: auto 1fr auto;
                padding: 0 28px;
            }

            /* Drawer becomes a layout passthrough on desktop */
            .ch-top-nav .ch-mobile-drawer-wrap {
                display: contents !important;
            }

            /* For auth page, hide the user-bar inside drawer on desktop to avoid duplication */
            .ch-auth-drawer .ch-user-bar {
                display: none !important;
            }

            .ch-top-nav .ch-mobile-drawer-wrap > .ch-user-bar-mobile-profile {
                display: none !important;
            }

            .ch-nav-user-desktop,
            .ch-nav-guest-desktop {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-left: 0;
            }

            .ch-top-nav .ch-mobile-drawer-wrap > .ch-nav-links {
                display: flex;
                align-items: center;
                height: 100%;
                grid-column: 2;
                grid-row: 1;
                width: 100%;
                min-width: 0;
                justify-self: stretch;
                align-self: center;
            }

            .ch-top-nav > .ch-nav-user-desktop,
            .ch-top-nav > .ch-nav-guest-desktop {
                grid-column: 3;
                grid-row: 1;
                justify-self: end;
                align-self: center;
            }

            .ch-top-nav .ch-top-nav-notifications {
                margin-left: 0;
                margin-right: 0;
            }

            .ch-user-bar {
                gap: 8px;
            }

            /* Separator between bell and avatar */
            .ch-user-bar::before {
                content: '';
                display: block;
                width: 1px;
                height: 22px;
                background: var(--ch-border);
                margin-right: 4px;
                flex-shrink: 0;
            }

            .ch-burger-menu-btn {
                display: none !important;
            }

            .ch-top-drawer-close {
                display: none !important;
            }

            .ch-post-media-preview-feed .ch-post-media-thumb,
            .ch-post-media-preview-feed .ch-post-media-thumb-img,
            .ch-post-media-preview-feed .ch-post-media-thumb-video {
                max-height: none;
            }
        }

        /* ── Tablet: collapse nav labels when space is tight (781–880px) ── */
        @media (min-width: 781px) and (max-width: 880px) {
            .ch-nav-label {
                display: none !important;
            }

            .ch-nav-link {
                padding: 8px 10px;
                gap: 0;
            }
        }

        .ch-icon-action-btn {
            width: 38px;
            height: 38px;
            border-radius: var(--ch-radius);
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: var(--ch-text-muted);
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            flex-shrink: 0;
        }

        .ch-icon-action-btn:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-avatar-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: white;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid transparent;
            padding: 0;
            line-height: 0;
            box-sizing: border-box;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
            box-shadow: 0 2px 8px rgba(255, 117, 81, 0.25);
            flex-shrink: 0;
            overflow: hidden;
        }

        .ch-avatar-btn:hover {
            transform: scale(1.06);
            box-shadow: 0 4px 14px rgba(255, 117, 81, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .ch-avatar-btn-lg {
            width: 40px;
            height: 40px;
            font-size: 15px;
            flex-shrink: 0;
        }

        .ch-avatar-btn-inner,
        .ch-avatar-btn-inner-lg,
        .ch-composer-trigger-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: inherit;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ch-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: inherit;
        }

        .ch-avatar-btn > img,
        .ch-avatar-btn-inner > img,
        .ch-avatar-btn-inner-lg > img,
        .ch-composer-trigger-avatar-inner > img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: inherit;
        }

        .ch-avatar-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .ch-notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: #fff;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            border: 2px solid var(--ch-surface);
            pointer-events: none;
            z-index: 1;
        }

        /* DROPDOWNS */
        .ch-dropdown-panel {
            position: fixed;
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-md);
            box-shadow: var(--ch-shadow-md);
            min-width: 300px;
            max-width: 360px;
            z-index: 9999;
            overflow-x: hidden;
            overflow-y: auto;
            max-height: calc(100dvh - 24px);
            overscroll-behavior: contain;
        }

        .ch-dropdown-panel-sm {
            min-width: 210px;
            max-width: 240px;
        }

        .ch-dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--ch-border-soft);
            font-size: 13px;
            font-weight: 600;
            color: var(--ch-text);
        }

        .ch-dropdown-action {
            background: none;
            border: none;
            color: var(--ch-accent);
            font-size: 12px;
            cursor: pointer;
            padding: 3px 8px;
            border-radius: var(--ch-radius-sm);
            font-weight: 500;
        }

        .ch-dropdown-action:hover {
            background: var(--ch-accent-light);
        }

        .ch-dropdown-footer {
            padding: 10px 16px;
            border-top: 1px solid var(--ch-border-soft);
            text-align: center;
        }

        .ch-dropdown-footer a {
            color: var(--ch-accent);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 500;
        }

        .ch-dropdown-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
        }

        .ch-dropdown-username {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ch-text);
        }

        .ch-dropdown-usermeta {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            margin-top: 1px;
        }

        .ch-dropdown-divider {
            height: 1px;
            background: var(--ch-border-soft);
            margin: 3px 0;
        }

        .ch-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px;
            font-size: 13px;
            color: var(--ch-text-muted);
            text-decoration: none;
            transition: background 0.1s;
        }

        .ch-dropdown-item:hover {
            background: var(--ch-bg);
            color: var(--ch-text);
        }

        .ch-dropdown-item-danger {
            color: #ef4444;
        }

        .ch-dropdown-item-danger:hover {
            background: #fff5f5;
            color: #dc2626;
        }

        .ch-notifications-list {
            max-height: 360px;
            overflow-y: auto;
        }

        .ch-notification-item {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--ch-border-soft);
            cursor: pointer;
            transition: background 0.12s;
            position: relative;
            border-left: 3px solid transparent;
        }

        .ch-notification-item:last-child {
            border-bottom: none;
        }

        .ch-notification-item:hover {
            background: var(--ch-bg);
        }

        /* ── Unread state ── */
        .ch-notification-item.unread {
            background: color-mix(in srgb, var(--ch-accent) 5%, var(--ch-surface));
            border-left-color: var(--ch-accent);
        }

        .ch-notification-item.unread:hover {
            background: color-mix(in srgb, var(--ch-accent) 9%, var(--ch-surface));
        }

        .ch-notification-item.unread .ch-notification-message {
            font-weight: 600;
            color: var(--ch-text);
        }

        .ch-notification-item.unread .ch-notification-time {
            color: var(--ch-accent);
        }

        /* ── Unread dot ── */
        .ch-notification-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--ch-accent);
            flex-shrink: 0;
            margin-top: 5px;
            transition: opacity 0.2s;
        }

        .ch-notification-item:not(.unread) .ch-notification-dot {
            opacity: 0;
        }

        /* ── Icon ── */
        .ch-notification-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s;
        }

        .ch-notification-icon.type-reply {
            background: #ede9fe;
            color: #7c3aed;
        }

        .ch-notification-icon.type-mention {
            background: #fef3c7;
            color: #d97706;
        }

        .ch-notification-icon.type-vote {
            background: #dcfce7;
            color: #16a34a;
        }

        .ch-notification-icon.type-announcement {
            background: #dbeafe;
            color: #2563eb;
        }

        .ch-notification-icon.type-report_resolved {
            background: #fce7f3;
            color: #db2777;
        }

        .ch-notification-icon.type-default {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-notification-item:not(.unread) .ch-notification-icon {
            opacity: 0.55;
        }

        /* ── Content ── */
        .ch-notification-content {
            flex: 1;
            min-width: 0;
        }

        .ch-notification-message {
            font-size: 13px;
            line-height: 1.45;
            margin: 0 0 3px;
            color: var(--ch-text-muted);
        }

        .ch-notification-time {
            font-size: 11px;
            color: var(--ch-text-subtle);
        }

        .ch-no-notifications {
            padding: 32px 16px;
            text-align: center;
            color: var(--ch-text-subtle);
            font-size: 13px;
        }

        /* DASHBOARD LAYOUT */
        .ch-dashboard-wrap {
            display: flex;
            min-height: 600px;
        }

        .ch-sidebar {
            width: 210px;
            flex-shrink: 0;
            background: var(--ch-surface);
            border-right: 1px solid var(--ch-border);
            padding: 20px 0;
            box-shadow: inset -1px 0 0 rgba(255, 117, 81, 0.08);
        }

        .ch-sidebar-header {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 0 18px 18px;
            font-weight: 700;
            font-size: 15px;
            color: var(--ch-accent);
            border-bottom: 1px solid var(--ch-border-soft);
            margin-bottom: 10px;
            letter-spacing: -0.2px;
        }

        /* Logo container — CSS controls layout; no inline styles needed */
        .ch-top-nav-logo {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        /* Logo anchor wrapper (used when logo links back to feed) */
        .ch-brand-logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            line-height: 0;
        }

        .ch-brand-logo {
            height: 30px;
            width: auto;
            max-width: 160px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
            filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.08));
        }

        @media (max-width: 780px) {
            .ch-brand-logo {
                height: 26px;
                max-width: 130px;
            }
        }

        .ch-main-content {
            flex: 1;
            min-width: 0;
            padding: 28px 32px 48px;
            background: linear-gradient(180deg, color-mix(in srgb, var(--ch-bg) 94%, var(--ch-accent-light) 6%) 0%, var(--ch-bg) 180px);
        }

        .ch-nav {
            display: flex;
            flex-direction: column;
            gap: 1px;
            padding: 0 10px;
        }

        .ch-nav-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 12px;
            border-radius: var(--ch-radius-sm);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--ch-text-muted);
            transition: all 0.15s;
            background: none;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }

        .ch-nav-item svg {
            opacity: 0.7;
            flex-shrink: 0;
        }

        .ch-nav-item:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-nav-item:hover svg {
            opacity: 1;
        }

        .ch-nav-item.active {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            font-weight: 600;
            border-left: 3px solid var(--ch-accent);
            padding-left: 9px;
        }

        .ch-nav-item.active svg {
            opacity: 1;
        }

        /* PAGE HEADER */
        .ch-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--ch-border-soft);
        }

        .ch-page-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 4px;
            color: var(--ch-text);
            letter-spacing: -0.4px;
            line-height: 1.25;
        }

        .ch-page-header p {
            font-size: 14px;
            color: var(--ch-text-muted);
            margin: 0;
            line-height: 1.6;
        }

        /* STATS GRID */
        .ch-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .ch-stat-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--ch-shadow-sm);
            transition: box-shadow 0.15s;
        }

        .ch-stat-card:hover {
            box-shadow: var(--ch-shadow);
        }

        .ch-stat-card.ch-stat-alert {
            border-color: #fca5a5;
            background: #fffbfb;
        }

        .ch-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--ch-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ch-stat-body {
            display: flex;
            flex-direction: column;
        }

        .ch-stat-num {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .ch-stat-label {
            font-size: 11.5px;
            color: var(--ch-text-muted);
            margin-top: 2px;
            font-weight: 500;
        }

        /* CARDS */
        .ch-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-xl);
            overflow: hidden;
            margin-bottom: 24px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .ch-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--ch-border-soft);
            background: color-mix(in srgb, var(--ch-surface) 82%, var(--ch-bg) 18%);
        }

        .ch-card-header h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--ch-text);
        }

        .ch-card-body {
            padding: 0;
        }

        .ch-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        /* LISTS */
        .ch-list-scroll {
            max-height: 360px;
            overflow-y: auto;
        }

        .ch-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid var(--ch-border-soft);
            transition: background 0.1s;
        }

        .ch-list-item:hover {
            background: var(--ch-bg);
        }

        .ch-list-item:last-child {
            border-bottom: none;
        }

        .ch-list-item-main {
            flex: 1;
            min-width: 0;
        }

        .ch-list-title {
            font-size: 13.5px;
            font-weight: 600;
            margin: 3px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--ch-text);
        }

        .ch-list-meta {
            font-size: 11.5px;
            color: var(--ch-text-muted);
        }

        .ch-list-item-stats {
            display: flex;
            gap: 10px;
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            flex-shrink: 0;
            margin-left: 12px;
        }

        .ch-trending-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--ch-border-soft);
            transition: background 0.1s;
        }

        .ch-trending-item:hover {
            background: var(--ch-bg);
        }

        .ch-trending-item:last-child {
            border-bottom: none;
        }

        .ch-trending-rank {
            font-size: 18px;
            font-weight: 800;
            color: var(--ch-accent-mid);
            min-width: 26px;
            text-align: center;
        }

        .ch-trending-content {
            flex: 1;
            min-width: 0;
        }

        .ch-trending-score {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--ch-accent);
            background: var(--ch-accent-light);
            padding: 2px 8px;
            border-radius: 20px;
        }

        /* BADGES */
        .ch-cat-badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .ch-status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .ch-status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .ch-status-archived {
            background: var(--ch-bg);
            color: var(--ch-text-muted);
        }

        .ch-status-removed {
            background: #fee2e2;
            color: #991b1b;
        }

        .ch-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .ch-status-suspended {
            background: #fed7aa;
            color: #9a3412;
        }

        .ch-status-banned {
            background: #fee2e2;
            color: #991b1b;
        }

        .ch-status-reviewed {
            background: #dbeafe;
            color: #1e40af;
        }

        .ch-status-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .ch-status-dismissed {
            background: var(--ch-bg);
            color: var(--ch-text-muted);
        }

        .ch-pin-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            font-weight: 600;
            color: var(--ch-accent);
            background: var(--ch-accent-light);
            padding: 2px 6px;
            border-radius: var(--ch-radius-sm);
            margin-bottom: 3px;
        }

        .ch-anon-badge {
            font-size: 10px;
            background: var(--ch-bg);
            color: var(--ch-text-subtle);
            padding: 1px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .ch-type-badge {
            padding: 2px 8px;
            border-radius: var(--ch-radius-sm);
            font-size: 11px;
            font-weight: 600;
            background: var(--ch-bg);
            color: var(--ch-text-muted);
        }

        /* TABLE */
        .bntm-table-wrapper {
            overflow-x: auto;
        }

        .bntm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .bntm-table th {
            background: var(--ch-bg);
            padding: 10px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: var(--ch-text-subtle);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--ch-border);
        }

        .bntm-table td {
            padding: 11px 16px;
            border-bottom: 1px solid var(--ch-border-soft);
            vertical-align: middle;
        }

        .bntm-table tr:hover td {
            background: var(--ch-bg);
        }

        .bntm-table tr:last-child td {
            border-bottom: none;
        }

        .ch-table th {
            background: var(--ch-bg);
        }

        .ch-table-empty {
            text-align: center;
            padding: 40px;
            color: var(--ch-text-subtle);
            font-size: 13px;
        }

        .ch-post-cell {
            max-width: 280px;
        }

        .ch-ann-title {
            color: var(--ch-text);
        }

        .ch-ann-excerpt {
            color: var(--ch-text-subtle);
        }

        .ch-post-excerpt {
            font-size: 12px;
            color: var(--ch-text-muted);
            margin: 2px 0 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ch-mini-stat {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 12px;
            color: var(--ch-text-muted);
            margin-right: 8px;
        }

        .ch-date {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
        }

        .ch-action-code {
            font-size: 11px;
            background: var(--ch-bg);
            padding: 2px 6px;
            border-radius: var(--ch-radius-sm);
            font-family: monospace;
            color: var(--ch-accent);
        }

        .ch-info-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 14px 0;
        }

        .ch-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 0 20px;
        }

        .ch-info-label {
            color: var(--ch-text-muted);
            font-size: 12.5px;
        }

        .ch-user-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* AVATAR */
        .ch-avatar-sm {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: white;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ch-avatar-xs {
            width: 24px;
            height: 24px;
            font-size: 10px;
        }

        /* BUTTONS */
        .ch-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: var(--ch-radius);
            font-size: 14px;
            font-weight: 600;
            font-family: var(--ch-font);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
            white-space: nowrap;
        }

        .ch-btn-primary {
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(255, 117, 81, 0.25);
        }

        .ch-btn-primary:hover {
            box-shadow: 0 6px 16px rgba(255, 117, 81, 0.35);
            transform: translateY(-1.5px);
        }

        .ch-btn:active {
            transform: translateY(1px) scale(0.98) !important;
            box-shadow: none !important;
        }

        .ch-btn-secondary {
            background: var(--ch-surface);
            color: var(--ch-text);
            border: 1px solid var(--ch-border);
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-btn-secondary:hover {
            background: var(--ch-bg);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
            transform: translateY(-1px);
            box-shadow: var(--ch-shadow);
        }

        .ch-btn-outline {
            background: transparent;
            color: var(--ch-accent);
            border: 1px solid var(--ch-accent-mid);
        }

        .ch-btn-outline:hover {
            background: var(--ch-accent-light);
        }

        .ch-btn-danger {
            background: #ef4444;
            color: white;
        }

        .ch-btn-danger:hover {
            background: #dc2626;
        }

        .ch-btn-full {
            width: 100%;
            justify-content: center;
        }

        .ch-btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }

        .ch-btn-xs {
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: var(--ch-radius-sm);
            border: none;
            cursor: pointer;
            transition: all 0.15s;
        }

        .ch-btn-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .ch-btn-warning:hover {
            background: #fde68a;
        }

        .ch-btn-success {
            background: #d1fae5;
            color: #065f46;
        }

        .ch-btn-success:hover {
            background: #a7f3d0;
        }

        .ch-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--ch-radius);
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            color: var(--ch-text-muted);
        }

        .ch-icon-btn:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-icon-btn-danger:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #ef4444;
        }

        .ch-link-btn {
            font-size: 13px;
            color: var(--ch-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .ch-link-btn:hover {
            text-decoration: underline;
        }

        .ch-actions-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: flex-end;
        }

        /* FORM */
        .ch-composer-media-label {
            display: block;
            border: 1.5px dashed var(--ch-border);
            border-radius: var(--ch-radius-md);
            background: color-mix(in srgb, var(--ch-surface) 60%, var(--ch-bg) 40%);
            color: var(--ch-text-subtle);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            padding: 24px;
        }

        .ch-composer-media-label:hover {
            border-color: var(--ch-accent-mid);
            background: var(--ch-surface);
            color: var(--ch-accent);
        }

        .ch-composer-tags-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--ch-surface);
            border-radius: var(--ch-radius-md);
            border: 1px solid var(--ch-border);
        }

        .ch-composer-title-input {
            font-size: 20px;
            font-weight: 700;
            padding: 12px 16px;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.2s;
            border-radius: var(--ch-radius-md);
            color: var(--ch-text);
        }

        .ch-composer-title-input::placeholder {
            color: var(--ch-text-subtle);
            font-weight: 600;
        }

        .ch-composer-textarea {
            font-size: 15px;
            line-height: 1.6;
            padding: 16px;
            min-height: 140px;
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            border-radius: var(--ch-radius-md);
            resize: vertical;
            color: var(--ch-text);
        }

        .ch-composer-textarea:focus {
            border-color: var(--ch-accent);
            background: var(--ch-bg);
            box-shadow: 0 0 0 4px rgba(255, 117, 81, 0.1);
        }

        /* Lightweight motion (GPU-friendly transforms/opacity only) */
        .ch-card,
        .ch-cat-card,
        .ch-stat-card {
            animation: ch-fade-up 0.2s ease-out;
        }

        .ch-notification-item.unread .ch-notification-dot {
            animation: ch-dot-pulse 1.8s ease-in-out infinite;
        }

        @keyframes ch-fade-up {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes ch-dot-pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.25);
                opacity: .8;
            }
        }

        .ch-input::placeholder {
            color: var(--ch-text-subtle);
        }

        .ch-textarea {
            resize: vertical;
            min-height: 80px;
            line-height: 1.55;
        }

        .ch-textarea-lg {
            min-height: 130px;
        }

        .ch-color-input {
            height: 40px;
            padding: 3px 6px;
            cursor: pointer;
        }

        .ch-select-sm {
            padding: 6px 10px;
            font-size: 13px;
            border-radius: var(--ch-radius-sm);
        }

        .ch-field-group {
            margin-bottom: 14px;
        }

        .ch-field-row {
            display: flex;
            gap: 14px;
        }

        .ch-field-half {
            flex: 1;
        }

        .ch-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ch-text);
            margin-bottom: 5px;
        }

        .ch-checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            cursor: pointer;
            color: var(--ch-text-muted);
        }

        .ch-terms-consent {
            align-items: flex-start;
            line-height: 1.5;
        }

        .ch-terms-consent input {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .ch-terms-consent span {
            display: inline;
            min-width: 0;
        }

        /* TOOLBAR */
        .ch-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ch-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ch-toolbar-filters {
            display: flex;
            gap: 8px;
        }

        .ch-toolbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .ch-filter-btn {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: var(--ch-text-muted);
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            transition: all 0.15s;
        }

        .ch-filter-btn:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-filter-btn.active {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            font-weight: 600;
            border-color: var(--ch-accent-mid);
        }

        .ch-guidelines-link {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--ch-text-muted);
            text-decoration: none;
            padding: 5px 10px;
            border-radius: var(--ch-radius-sm);
            transition: all 0.15s;
        }

        .ch-guidelines-link:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-search-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .ch-search-input {
            min-width: 180px;
        }

        /* MODAL */
        .ch-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .ch-modal {
            background: var(--ch-bg);
            width: 100%;
            max-width: 500px;
            border-radius: var(--ch-radius-xl);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            box-shadow: var(--ch-shadow-md);
            overflow: hidden;
            margin: 0 16px;
            border: 1px solid var(--ch-border);
        }

        .ch-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--ch-border-soft);
            background: var(--ch-surface);
        }

        .ch-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--ch-text);
            letter-spacing: -0.2px;
        }

        .ch-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--ch-text-subtle);
            line-height: 1;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: all 0.15s;
        }

        .ch-modal-close:hover {
            background: var(--ch-bg);
            color: var(--ch-text);
        }

        .ch-modal-body {
            padding: 24px;
            overflow-y: auto;
            background: var(--ch-bg);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .ch-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--ch-border-soft);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: var(--ch-surface);
        }

        .ch-reason-textarea {
            min-height: 90px;
        }

        /* ── FACEBOOK-STYLE COMPOSER MODAL ── */
        .ch-composer-modal {
            max-width: 548px;
            max-height: min(88vh, 760px);
            border-radius: var(--ch-radius-xl);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .ch-composer-header {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px 52px;
        }

        .ch-composer-header h3 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--ch-text);
            letter-spacing: -0.2px;
        }

        .ch-composer-close {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            background: var(--ch-bg);
            color: var(--ch-text-muted);
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
        }

        .ch-composer-close {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            background: var(--ch-bg);
            color: var(--ch-text-muted);
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
        }

        .ch-composer-close:hover {
            background: var(--ch-border);
            color: var(--ch-text);
        }

        .ch-composer-divider {
            height: 1px;
            background: var(--ch-border-soft);
            margin: 0;
        }

        .ch-composer-body {
            padding: 20px 24px 12px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
            min-height: 0;
        }

        /* Author row with avatar */
        .ch-composer-author-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .ch-composer-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ch-composer-author-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .ch-composer-author-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--ch-text);
            line-height: 1.2;
        }

        .ch-composer-meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ch-composer-cat-select {
            font-size: 13px;
            font-weight: 600;
            font-family: var(--ch-font);
            color: var(--ch-text);
            background: var(--ch-bg);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-sm);
            padding: 6px 10px;
            cursor: pointer;
            outline: none;
            max-width: 200px;
            transition: border-color 0.15s;
            min-height: 38px;
        }

        .ch-composer-cat-select:focus {
            border-color: var(--ch-accent);
        }

        .ch-composer-anon-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            color: var(--ch-text-muted);
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        .ch-composer-anon-toggle input {
            accent-color: var(--ch-accent);
            cursor: pointer;
        }

        /* Guest name */
        .ch-composer-guest-name {
            width: 100%;
            box-sizing: border-box;
            border: none;
            border-bottom: 1px solid var(--ch-border-soft);
            padding: 6px 2px;
            font-size: 13px;
            font-family: var(--ch-font);
            color: var(--ch-text);
            background: transparent;
            outline: none;
        }

        .ch-composer-guest-name:focus {
            border-bottom-color: var(--ch-accent);
        }

        .ch-composer-guest-name::placeholder {
            color: var(--ch-text-subtle);
        }

        /* Title field */
        .ch-composer-title {
            width: 100%;
            box-sizing: border-box;
            border: none;
            padding: 6px 2px;
            font-size: 18px;
            font-weight: 700;
            font-family: var(--ch-font);
            color: var(--ch-text);
            background: transparent;
            outline: none;
            border-bottom: 1px solid var(--ch-border-soft);
        }

        .ch-composer-title:focus {
            border-bottom-color: var(--ch-accent);
        }

        .ch-composer-title::placeholder {
            color: var(--ch-text-subtle);
            font-weight: 400;
        }

        .ch-composer-title,
        .ch-composer-title:focus,
        .ch-composer-title:not(:placeholder-shown),
        .ch-composer-guest-name,
        .ch-composer-tags-input,
        .ch-composer-textarea {
            -webkit-text-fill-color: var(--ch-text);
        }

        .ch-composer-title:-webkit-autofill,
        .ch-composer-guest-name:-webkit-autofill,
        .ch-composer-tags-input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px transparent inset;
            -webkit-text-fill-color: var(--ch-text);
            transition: background-color 9999s ease-in-out 0s;
        }

        /* Main textarea */
        .ch-composer-textarea {
            width: 100%;
            box-sizing: border-box;
            border: none;
            resize: none;
            font-size: 16px;
            font-family: var(--ch-font);
            color: var(--ch-text);
            background: transparent;
            outline: none;
            line-height: 1.55;
            min-height: 120px;
        }

        .ch-composer-textarea::placeholder {
            color: var(--ch-text-subtle);
        }

        /* Tags row */
        .ch-composer-tags-row {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 10px 16px;
            background: var(--ch-bg);
            border-radius: var(--ch-radius-sm);
            border: 1px solid var(--ch-border-soft);
        }

        .ch-composer-tags-row svg {
            flex-shrink: 0;
            color: var(--ch-text-subtle);
        }

        .ch-composer-tags-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14px;
            font-family: var(--ch-font);
            color: var(--ch-text);
            outline: none;
        }

        .ch-composer-tags-input::placeholder {
            color: var(--ch-text-subtle);
        }

        /* Media upload area */
        .ch-composer-media-area {
            display: block;
            border: 2px dashed var(--ch-border);
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            text-decoration: none;
            padding: 10px;
        }

        .ch-composer-media-area:hover {
            border-color: var(--ch-accent-mid);
            background: var(--ch-accent-light);
        }

        .ch-composer-media-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 16px 12px;
            color: var(--ch-text-muted);
            min-height: 120px;
        }

        .ch-composer-media-inner svg {
            color: var(--ch-text-subtle);
        }

        .ch-composer-media-inner span {
            font-size: 13.5px;
            font-weight: 600;
        }

        .ch-composer-media-sub {
            font-size: 11.5px !important;
            font-weight: 400 !important;
            color: var(--ch-text-subtle);
        }

        .ch-composer-media-area.has-media .ch-composer-media-inner {
            min-height: 56px;
            padding: 8px 10px 2px;
            gap: 2px;
        }

        .ch-composer-media-area.has-media .ch-composer-media-inner svg {
            width: 16px;
            height: 16px;
        }

        .ch-composer-media-area.has-media .ch-composer-media-inner span {
            font-size: 12px;
        }

        .ch-composer-media-area.has-media .ch-composer-media-sub {
            font-size: 10.5px !important;
        }

        .ch-composer-media-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(72px, 84px));
            gap: 8px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed var(--ch-border-soft);
            justify-content: flex-start;
        }

        .ch-composer-media-preview:empty {
            display: none;
        }

        .ch-composer-media-chip {
            position: relative;
            width: 84px;
            height: 84px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--ch-border);
            background: var(--ch-bg);
            box-shadow: 0 4px 12px rgba(17, 24, 39, 0.08);
        }

        .ch-composer-media-chip img,
        .ch-composer-media-chip video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .ch-composer-media-chip-audio {
            width: 100%;
            height: 100%;
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            color: var(--ch-text-muted);
            background: var(--ch-surface);
        }

        .ch-composer-media-chip-audio-name {
            font-size: 11px;
            line-height: 1.25;
            word-break: break-word;
        }

        .ch-composer-media-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border: none;
            border-radius: 999px;
            background: rgba(17, 24, 39, 0.78);
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
        }

        .ch-composer-media-remove:hover {
            background: rgba(239, 68, 68, 0.9);
        }

        .ch-confirm-modal {
            max-width: 420px;
        }

        .ch-confirm-copy {
            font-size: 14px;
            line-height: 1.65;
            color: var(--ch-text-muted);
            margin: 0;
        }

        .ch-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .ch-warning-modal {
            max-width: 420px;
        }

        .ch-warning-copy {
            font-size: 14px;
            line-height: 1.65;
            color: var(--ch-text-muted);
            margin: 0;
        }

        .ch-warning-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .ch-loading-modal {
            max-width: 320px;
            text-align: center;
        }

        .ch-loading-modal-body {
            padding: 28px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .ch-loading-modal-spinner {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 3px solid rgba(255, 117, 81, 0.18);
            border-top-color: var(--ch-accent);
            animation: ch-spin 0.8s linear infinite;
        }

        .ch-loading-modal-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--ch-text);
        }

        .ch-loading-modal-copy {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            color: var(--ch-text-muted);
        }

        /* Footer */
        .ch-composer-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            border-top: 1px solid var(--ch-border-soft);
            gap: 12px;
            flex-wrap: wrap;
        }

        .ch-composer-footer-hint {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--ch-text-subtle);
            flex: 1;
            min-width: 0;
        }

        .ch-composer-footer-hint svg {
            flex-shrink: 0;
            color: var(--ch-text-subtle);
        }

        .ch-composer-submit {
            padding: 10px 28px;
            font-size: 14px;
            border-radius: var(--ch-radius);
            min-height: 44px;
        }

        /* Dark mode tweaks */
        .ch-dark .ch-composer-modal {
            background: var(--ch-surface);
        }

        .ch-dark .ch-composer-cat-select {
            background: var(--ch-bg);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-composer-media-area {
            border-color: var(--ch-border);
        }

        .ch-dark .ch-composer-close {
            background: rgba(255, 255, 255, 0.08);
        }

        .ch-dark .ch-composer-close:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--ch-text);
        }

        /* Scrollbars */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--ch-scroll-thumb) var(--ch-scroll-track);
        }

        *::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        *::-webkit-scrollbar-track {
            background: var(--ch-scroll-track);
        }

        *::-webkit-scrollbar-thumb {
            background: var(--ch-scroll-thumb);
            border-radius: 999px;
            border: 2px solid var(--ch-scroll-track);
        }

        *::-webkit-scrollbar-thumb:hover {
            background: var(--ch-scroll-thumb-hover);
        }

        /* Responsive */
        @media (max-width: 520px) {
            .ch-composer-modal {
                border-radius: 0;
                max-height: 100dvh;
            }

            .ch-composer-body {
                padding: 12px 12px 6px;
            }

            .ch-composer-footer {
                padding: 10px 12px;
            }

            .ch-composer-submit {
                padding: 8px 18px;
            }

            .ch-composer-footer-hint {
                display: none;
            }
        }

        /* ── COMPOSER TRIGGER CARD (Feed) ── */
        .ch-onboarding-modal {
            max-width: 480px;
            overflow: hidden;
        }

        .ch-onboarding-progress {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 18px;
        }

        .ch-onboarding-progress-bar {
            height: 6px;
            border-radius: 999px;
            background: var(--ch-border-soft);
            overflow: hidden;
        }

        .ch-onboarding-progress-bar span {
            display: block;
            width: 100%;
            height: 100%;
            transform: scaleX(0);
            transform-origin: left;
            background: linear-gradient(90deg, var(--ch-accent), var(--ch-accent-dark));
            transition: transform 0.2s ease;
        }

        .ch-onboarding-progress-bar.is-active span,
        .ch-onboarding-progress-bar.is-done span {
            transform: scaleX(1);
        }

        .ch-onboarding-stage-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ch-text-subtle);
            margin-bottom: 8px;
        }

        .ch-onboarding-slide {
            display: none;
        }

        .ch-onboarding-slide.is-active {
            display: block;
        }

        .ch-onboarding-hero {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px;
            border-radius: var(--ch-radius-lg);
            border: 1px solid var(--ch-border);
            background: linear-gradient(145deg, color-mix(in srgb, var(--ch-accent-light) 82%, var(--ch-surface) 18%), var(--ch-surface));
            margin-bottom: 16px;
        }

        .ch-onboarding-hero-icon {
            width: 46px;
            height: 46px;
            border-radius: var(--ch-radius-md);
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 12px 24px rgba(255, 117, 81, 0.24);
        }

        .ch-onboarding-hero h4 {
            margin: 0 0 4px;
            font-size: 18px;
            line-height: 1.25;
            color: var(--ch-text);
        }

        .ch-onboarding-hero p {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.65;
            color: var(--ch-text-muted);
        }

        .ch-onboarding-checklist {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ch-onboarding-point {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: var(--ch-radius-md);
            background: color-mix(in srgb, var(--ch-surface) 75%, var(--ch-bg) 25%);
            border: 1px solid var(--ch-border-soft);
        }

        .ch-onboarding-point strong {
            display: block;
            font-size: 14px;
            color: var(--ch-text);
            margin-bottom: 4px;
        }

        .ch-onboarding-point span {
            display: block;
            font-size: 12.5px;
            color: var(--ch-text-muted);
            line-height: 1.55;
        }

        .ch-onboarding-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            margin-top: 5px;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            flex-shrink: 0;
        }

        .ch-onboarding-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ch-onboarding-footer-left,
        .ch-onboarding-footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ch-composer-trigger-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(180deg, color-mix(in srgb, var(--ch-surface) 82%, var(--ch-bg) 18%) 0%, var(--ch-surface) 100%);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 10px 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .ch-composer-trigger-card:hover {
            border-color: var(--ch-accent-mid);
            box-shadow: 0 2px 10px rgba(99, 102, 241, 0.08);
        }

        .ch-composer-trigger-card:focus-visible {
            border-color: var(--ch-accent);
            box-shadow: 0 0 0 3px var(--ch-accent-light);
        }

        .ch-composer-trigger-avatar {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ch-composer-trigger-guest {
            background: var(--ch-bg);
            border: 1.5px solid var(--ch-border);
            color: var(--ch-text-subtle);
        }

        .ch-composer-trigger-input {
            flex: 1;
            min-width: 0;
            background: var(--ch-bg);
            border: 1px solid var(--ch-border-soft);
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            color: var(--ch-text-subtle);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: border-color 0.15s;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ch-composer-trigger-card:hover .ch-composer-trigger-input {
            border-color: var(--ch-accent-mid);
            color: var(--ch-text-muted);
        }

        .ch-composer-trigger-locked {
            color: var(--ch-text-subtle);
            cursor: default;
        }

        .ch-composer-trigger-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .ch-composer-trigger-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ch-text-muted);
            transition: background 0.13s, color 0.13s;
            white-space: nowrap;
        }

        .ch-composer-trigger-btn:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-composer-trigger-btn-tag:hover {
            background: #fef3c7;
            color: #92400e;
        }

        @media (max-width: 600px) {
            .ch-composer-trigger-actions {
                display: none;
            }

            .ch-composer-trigger-input {
                font-size: 13px;
                padding: 7px 14px;
            }
        }

        @media (max-width: 400px) {
            .ch-composer-trigger-card {
                padding: 8px 10px;
                gap: 8px;
            }

            .ch-composer-trigger-avatar {
                width: 32px;
                height: 32px;
                min-width: 32px;
                font-size: 13px;
            }
        }

        /* GUIDELINES */
        .ch-guidelines-content h4 {
            margin: 0 0 14px;
            font-size: 15px;
            font-weight: 700;
            color: var(--ch-text);
        }

        .ch-guidelines-content h5 {
            margin: 18px 0 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ch-accent);
        }

        .ch-guidelines-content ul {
            margin: 6px 0 14px;
            padding-left: 18px;
        }

        .ch-guidelines-content li {
            margin-bottom: 4px;
            font-size: 13.5px;
            color: var(--ch-text-muted);
            line-height: 1.55;
        }

        .ch-guidelines-content p {
            margin: 10px 0;
            font-size: 13.5px;
            color: var(--ch-text-muted);
            line-height: 1.6;
        }

        .ch-guidelines-content strong {
            font-weight: 600;
            color: var(--ch-text);
        }

        /* CATEGORIES */
        .ch-page-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            justify-content: flex-end;
        }

        .ch-page-header-actions>.ch-btn {
            height: 36px;
        }

        .ch-categories-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
            margin: 0;
        }

        .ch-categories-filter-form .ch-field-group {
            flex: 1;
            min-width: 160px;
            margin: 0;
        }

        .ch-categories-filter-sidebar {
            flex-direction: column;
            align-items: stretch;
        }

        .ch-categories-filter-sidebar .ch-field-group {
            width: 100%;
            margin-bottom: 6px;
        }

        .ch-categories-filter-sidebar button {
            width: 100%;
        }

        .ch-filter-actions {
            display: flex;
            gap: 6px;
            align-items: flex-end;
        }

        .ch-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }

        .ch-cat-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            overflow: hidden;
            transition: box-shadow 0.15s, transform 0.15s;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-cat-card:hover {
            box-shadow: var(--ch-shadow);
            transform: translateY(-1px);
        }

        .ch-cat-card-color {
            height: 3px;
        }

        .ch-cat-card-body {
            padding: 14px 16px;
        }

        .ch-cat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 7px;
        }

        .ch-cat-card-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--ch-text);
        }

        .ch-cat-desc {
            font-size: 12.5px;
            color: var(--ch-text-muted);
            margin: 0 0 10px;
            line-height: 1.5;
        }

        .ch-cat-stats {
            display: flex;
            gap: 10px;
            align-items: center;
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            flex-wrap: wrap;
        }

        /* PAGINATION */
        .ch-pagination {
            display: flex;
            gap: 4px;
            padding: 14px 20px;
            justify-content: center;
        }

        .ch-page-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--ch-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12.5px;
            font-weight: 500;
            text-decoration: none;
            color: var(--ch-text-muted);
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            transition: all 0.15s;
        }

        .ch-page-btn:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-page-btn.active {
            background: var(--ch-accent);
            color: white;
            border-color: var(--ch-accent);
        }

        /* EMPTY STATE */
        .ch-empty {
            text-align: center;
            padding: 28px;
            color: var(--ch-text-subtle);
            font-size: 13px;
        }

        .ch-empty-state {
            text-align: center;
            padding: 56px 20px;
            color: var(--ch-text-subtle);
        }

        .ch-empty-state p {
            margin-top: 12px;
            font-size: 13.5px;
        }

        /* NOTICES */
        .bntm-notice {
            padding: 10px 14px;
            border-radius: var(--ch-radius);
            font-size: 13px;
            margin-bottom: 14px;
            font-weight: 500;
        }

        .bntm-notice-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 3px solid #10b981;
        }

        .bntm-notice-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 3px solid #ef4444;
        }

        .bntm-notice-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 3px solid #f59e0b;
        }

        /* FEED LAYOUT */
        .ch-feed-wrap {
            display: flex;
            gap: 24px;
            max-width: 100%;
            max-height: 100%;
            margin: 0 auto;
            padding: 24px 20px;
            align-items: flex-start;
        }

        .ch-feed-shell {
            position: relative;
        }

        .ch-feed-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            pointer-events: none;
        }

        body.ch-feed-loading-active .ch-feed-loading-overlay {
            display: flex;
            pointer-events: auto;
        }

        .ch-feed-loading-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 22px;
            background: rgba(255, 255, 255, 0.94);
            border-radius: 16px;
            border: 1px solid var(--ch-border);
            box-shadow: var(--ch-shadow-md);
            backdrop-filter: none;
        }

        .ch-feed-loading-spinner {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid rgba(255, 117, 81, 0.22);
            border-top-color: var(--ch-accent);
            animation: ch-feed-spin 0.9s linear infinite;
        }

        .ch-feed-loading-copy {
            font-size: 14px;
            font-weight: 700;
            color: var(--ch-text);
        }

        @keyframes ch-feed-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .ch-feed-shell-loading .ch-feed-wrap,
        .ch-feed-shell-loading .ch-special-page-wrap,
        .ch-feed-shell-loading .ch-mf-page-wrap,
        .ch-feed-shell-loading .ch-post-view-wrap {
            filter: blur(3px);
            opacity: 0.45;
            pointer-events: none;
            transition: opacity 0.2s ease, filter 0.2s ease;
        }

        .ch-feed-sidebar {
            width: 220px;
            flex-shrink: 0;
        }

        .ch-feed-main {
            flex: 1;
            min-width: 0;
        }

        .ch-sidebar-widget {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 14px;
            margin-bottom: 14px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-sidebar-widget h4 {
            margin: 0 0 10px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: var(--ch-text-subtle);
        }

        .ch-cat-link {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 6px 8px;
            border-radius: var(--ch-radius-sm);
            text-decoration: none;
            font-size: 13px;
            color: var(--ch-text-muted);
            transition: all 0.15s;
            justify-content: space-between;
        }

        .ch-cat-name {
            flex: 1 1 auto;
            min-width: 0;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .ch-cat-link:hover {
            background: var(--ch-bg);
            color: var(--ch-text);
        }

        .ch-cat-link.active {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            font-weight: 600;
        }

        .ch-cat-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ch-cat-count {
            font-size: 11px;
            background: var(--ch-bg);
            color: var(--ch-text-subtle);
            padding: 1px 6px;
            border-radius: 10px;
        }

        .ch-sidebar-action-row {
            margin-top: 10px;
            display: flex;
        }

        .ch-view-all-btn {
            width: 100%;
            min-height: 44px;
            padding: 10px 14px;
            border-radius: var(--ch-radius);
            font-size: 13px;
            font-weight: 600;
            line-height: normal;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--ch-text-subtle);
            background: transparent;
            border: 1px solid var(--ch-border);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 8px;
        }

        .ch-view-all-btn:hover {
            color: var(--ch-accent);
            background: var(--ch-accent-light, rgba(255, 117, 81, 0.1));
            border-color: var(--ch-accent-mid, var(--ch-accent));
        }

        .ch-view-all-btn:active {
            transform: scale(0.98);
        }

        .ch-view-all-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ch-accent) 22%, transparent);
            border-color: var(--ch-accent);
        }

        .ch-category-modal-overlay {
            padding: 24px;
            animation: ch-category-modal-overlay-in 0.2s ease-out forwards;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
        }

        .ch-modal.ch-modal-category-browser {
            width: min(520px, calc(100vw - 32px));
            max-width: 520px;
            max-height: min(85vh, 700px);
            margin: auto;
            display: flex;
            flex-direction: column;
            will-change: transform, opacity;
            animation: ch-category-modal-pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.1) forwards;
            border-radius: var(--ch-radius-lg, 12px);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.15);
            background: var(--ch-bg, #fff);
            border: 1px solid var(--ch-border, #e5e7eb);
            overflow: hidden;
        }

        .ch-category-modal-header {
            padding: 16px 20px;
            background: var(--ch-bg-card, #f9fafb);
            border-bottom: 1px solid var(--ch-border, #e5e7eb);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            border-radius: calc(var(--ch-radius-lg, 12px) - 1px) calc(var(--ch-radius-lg, 12px) - 1px) 0 0;
        }

        .ch-category-modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--ch-text, #111827);
            letter-spacing: -0.1px;
            line-height: 1.4;
        }

        .ch-category-modal-body {
            padding: 20px;
            gap: 16px;
            overflow-y: auto;
            flex-grow: 1;
            overscroll-behavior: contain;
            /* smooth scroll */
            scroll-behavior: smooth;
        }

        .ch-category-browser-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 0;
        }

        .ch-category-browser-section h4 {
            margin: 0;
            font-size: 12px;
            font-weight: 600;
            color: var(--ch-text-subtle, #6b7280);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ch-category-browser-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-height: none;
            overflow: visible;
        }

        .ch-category-browser-list .ch-cat-link {
            min-height: 44px;
            padding: 10px 12px;
            border-radius: var(--ch-radius-sm, 6px);
            background: var(--ch-bg-card, transparent);
            border: 1px solid transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--ch-text, #111827);
            text-decoration: none;
        }
        
        .ch-category-browser-list .ch-cat-link:hover,
        .ch-category-browser-list .ch-cat-link:focus-visible {
            background: var(--ch-hover, #f3f4f6);
            border-color: var(--ch-border-hover, #e5e7eb);
            outline: none;
        }

        .ch-category-modal-footer {
            padding: 14px 20px;
            background: var(--ch-bg-card, #f9fafb);
            border-top: 1px solid var(--ch-border, #e5e7eb);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-shrink: 0;
            gap: 12px;
            border-radius: 0 0 calc(var(--ch-radius-lg, 12px) - 1px) calc(var(--ch-radius-lg, 12px) - 1px);
        }

        .ch-category-modal-close-btn {
            min-width: 90px;
            min-height: 40px;
            border-radius: var(--ch-radius-sm, 6px);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        @keyframes ch-category-modal-overlay-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes ch-category-modal-pop-in {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ch-category-browser-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .ch-category-browser-section h4 {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--ch-text-subtle);
        }

        .ch-category-browser-list {
            display: grid;
            gap: 6px;
            max-height: 60vh;
            overflow: auto;
            padding-right: 2px;
        }

        .ch-modal.ch-modal-category-browser .ch-category-browser-list {
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }

        .ch-cat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
        }

        .ch-cat-owner-actions {
            display: none;
            align-items: center;
            gap: 2px;
            flex-shrink: 0;
        }

        .ch-cat-item:hover .ch-cat-owner-actions {
            display: flex;
        }

        .ch-cat-action-btn {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ch-text-subtle);
            transition: background 0.15s, color 0.15s;
            padding: 0;
        }

        .ch-cat-action-btn:hover {
            background: var(--ch-bg);
            color: var(--ch-text);
        }

        .ch-cat-action-delete:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        /* CAT HERO */
        .ch-cat-hero {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 24px 24px;
            margin-bottom: 20px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-cat-hero h1,
        .ch-cat-hero h2 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.4px;
            line-height: 1.25;
            color: var(--ch-text);
        }

        .ch-cat-hero p {
            margin: 0;
            font-size: 14px;
            color: var(--ch-text-muted);
            line-height: 1.6;
        }

        .ch-cat-hero-main {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .ch-cat-hero-text {
            flex: 1;
        }

        .ch-cat-hero-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ch-cat-meta-item {
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .ch-cat-meta-num {
            font-size: 16px;
            font-weight: 700;
            color: var(--ch-text);
        }

        .ch-cat-meta-label {
            font-size: 11px;
            color: var(--ch-text-subtle);
            font-weight: 500;
        }

        .ch-btn-sm {
            height: 32px;
            padding: 5px 12px;
            font-size: 12.5px;
        }

        /* FEED TOOLBAR */
        .ch-feed-header {
            margin-bottom: 20px;
        }

        .ch-feed-header h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 16px;
            letter-spacing: -0.3px;
            color: var(--ch-text);
        }

        .ch-feed-toolbar-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 14px 16px;
            margin-bottom: 16px;
            box-shadow: var(--ch-shadow-sm);
            flex-wrap: wrap;
            gap: 12px;
        }

        .ch-filter-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .ch-location-form {
            flex-shrink: 0;
        }

        .ch-location-select {
            width: 150px;
            padding: 5px 9px;
            border-radius: var(--ch-radius-sm);
            font-size: 12.5px;
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            font-family: var(--ch-font);
            color: var(--ch-text-muted);
        }

        .ch-sort-tabs {
            display: flex;
            gap: 3px;
        }

        .ch-sort-tab {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 500;
            text-decoration: none;
            color: var(--ch-text-muted);
            background: var(--ch-bg);
            transition: all 0.15s;
            border: 1px solid var(--ch-border);
        }

        .ch-sort-tab:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            border-color: var(--ch-accent-mid);
        }

        .ch-sort-tab.active {
            background: var(--ch-accent);
            color: #fff;
            border-color: var(--ch-accent);
            font-weight: 600;
        }

        /* POST CARDS */
        .ch-posts-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .ch-post-card {
            background: color-mix(in srgb, var(--ch-surface) 60%, var(--ch-bg) 40%);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 24px;
            display: flex;
            gap: 18px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .ch-post-card:hover {
            box-shadow: var(--ch-shadow-md);
            border-color: var(--ch-accent);
            transform: translateY(-3px) scale(1.005);
        }

        .ch-post-pinned-ribbon {
            position: absolute;
            top: -1px;
            right: 16px;
            background: var(--ch-accent);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 9px 4px;
            border-radius: 0 0 7px 7px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .ch-post-vote-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
            padding-top: 2px;
            min-width: 32px;
        }

        .ch-vote-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--ch-radius);
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ch-text-subtle);
            transition: all 0.15s;
        }

        .ch-vote-btn:hover {
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
            background: var(--ch-accent-light);
        }

        .ch-vote-btn.active-up {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent);
            color: var(--ch-accent);
        }

        .ch-vote-btn.active-down {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #ef4444;
        }

        .ch-vote-btn-lg.active-up {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent);
            color: var(--ch-accent);
        }

        .ch-vote-btn-lg.active-down {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #ef4444;
        }

        .ch-comment-action.active-up {
            color: var(--ch-accent);
            font-weight: 700;
        }

        .ch-comment-action.active-down {
            color: #ef4444;
            font-weight: 700;
        }

        .ch-vote-count {
            font-size: 12px;
            font-weight: 700;
            color: var(--ch-text);
            min-width: 20px;
            text-align: center;
        }

        .ch-post-body {
            flex: 1;
            min-width: 0;
        }

        .ch-post-meta-row {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
            margin-bottom: 7px;
        }

        .ch-post-author {
            font-size: 12px;
            font-weight: 600;
            color: var(--ch-text-muted);
        }

        .ch-post-location {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11.5px;
            color: var(--ch-text-subtle);
        }

        .ch-post-time {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            margin-left: auto;
        }

        .ch-post-views {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
        }

        .ch-post-title-link {
            text-decoration: none;
            color: var(--ch-text);
            display: block;
        }

        .ch-post-title-link:visited {
            color: var(--ch-text);
        }

        .ch-post-title-link:hover {
            color: var(--ch-accent);
        }

        .ch-post-title-link:hover .ch-post-title {
            color: var(--ch-accent);
        }

        .ch-post-title {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.4;
            letter-spacing: -0.3px;
            color: inherit;
        }

        .ch-post-title a {
            text-decoration: none;
            color: var(--ch-text);
        }

        .ch-post-title a:hover {
            color: var(--ch-accent);
        }

        .ch-post-preview {
            font-size: 14px;
            color: var(--ch-text-muted);
            margin: 0 0 12px;
            line-height: 1.6;
        }

        .ch-post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .ch-tag {
            font-size: 11.5px;
            background: var(--ch-bg);
            color: var(--ch-text-subtle);
            padding: 4px 10px;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.1s;
            border: 1px solid var(--ch-border);
            font-weight: 500;
        }

        .ch-tag:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            border-color: var(--ch-accent-mid);
        }

        .ch-post-actions-row {
            display: flex;
            gap: 2px;
            align-items: center;
            padding-top: 8px;
            border-top: 1px solid var(--ch-border-soft);
            flex-wrap: wrap;
        }

        .ch-post-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--ch-text-subtle);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 9px;
            border-radius: var(--ch-radius-sm);
            text-decoration: none;
            transition: all 0.15s;
            font-weight: 500;
            font-family: var(--ch-font);
        }

        .ch-post-action:hover {
            color: var(--ch-accent);
            background: var(--ch-accent-light);
        }

        .ch-post-action.ch-bookmarked {
            color: var(--ch-accent);
        }

        .ch-share-dropdown {
            position: relative;
            margin-left: auto;
        }

        .ch-share-menu {
            position: fixed;
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            box-shadow: var(--ch-shadow-md);
            min-width: 155px;
            display: none;
            z-index: 10000;
            overflow: hidden;
        }

        .ch-share-menu.show {
            display: block;
        }

        .ch-share-option {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 9px 13px;
            font-size: 12.5px;
            color: var(--ch-text-muted);
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
            transition: background 0.1s;
            font-family: var(--ch-font);
        }

        .ch-share-option:hover {
            background: var(--ch-bg);
            color: var(--ch-accent);
        }

        /* MODERATION SETTINGS */
        .ch-settings-grid {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .ch-setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid var(--ch-border-soft);
        }

        .ch-setting-row:last-child {
            border-bottom: none;
        }

        .ch-setting-info {
            flex: 1;
            min-width: 0;
        }

        .ch-setting-label {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ch-text);
            margin-bottom: 3px;
        }

        .ch-setting-desc {
            font-size: 12.5px;
            color: var(--ch-text-muted);
            line-height: 1.5;
        }

        .ch-setting-control {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .ch-setting-number {
            width: 76px;
            text-align: center;
        }

        .ch-setting-unit {
            font-size: 12.5px;
            color: var(--ch-text-subtle);
            white-space: nowrap;
        }

        .ch-toggle {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .ch-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .ch-toggle-track {
            display: block;
            width: 42px;
            height: 22px;
            border-radius: 11px;
            background: var(--ch-border);
            transition: background 0.2s;
            position: relative;
        }

        .ch-toggle input:checked+.ch-toggle-track {
            background: var(--ch-accent);
        }

        .ch-toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18);
            transition: transform 0.2s;
        }

        .ch-toggle input:checked+.ch-toggle-track .ch-toggle-thumb {
            transform: translateX(20px);
        }

        /* POST VIEW */
        .ch-post-view-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 22px 16px;
        }

        .ch-post-view-header {
            margin-bottom: 18px;
        }

        .ch-back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: var(--ch-accent);
            font-size: 13.5px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: var(--ch-radius-sm);
            border: 1px solid var(--ch-accent-mid);
            background: var(--ch-accent-light);
            transition: all 0.15s;
        }

        .ch-back-link:hover {
            background: var(--ch-accent);
            color: white;
        }

        .ch-post-view-grid {
            display: grid;
            grid-template-columns: 1fr 250px;
            gap: 22px;
        }

        .ch-post-full {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-xl);
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-post-full-title {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 16px;
            line-height: 1.35;
            letter-spacing: -0.5px;
            color: var(--ch-text);
        }

        .ch-post-full-content {
            font-size: 16px;
            line-height: 1.75;
            color: var(--ch-text-muted);
            margin-bottom: 24px;
        }

        .ch-post-media {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 14px;
        }

        .ch-post-media-preview {
            display: grid;
            gap: 8px;
            margin: 8px 0 12px;
        }

        .ch-post-media-preview-feed {
            grid-template-columns: repeat(auto-fit, minmax(128px, 1fr));
        }

        .ch-post-media-gallery {
            grid-auto-rows: minmax(120px, 1fr);
        }

        .ch-post-media-gallery-1 {
            grid-template-columns: 1fr;
        }

        .ch-post-media-gallery-2,
        .ch-post-media-gallery-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ch-post-media-gallery-3 {
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        }

        .ch-post-media-gallery-3 .ch-post-media-thumb:first-child {
            grid-row: span 2;
            min-height: 248px;
        }

        .ch-post-media-gallery-4,
        .ch-post-media-gallery-more {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ch-post-media-preview-post.ch-post-media-gallery-1 .ch-post-media-thumb {
            aspect-ratio: 1.45 / 1;
            max-height: 420px;
        }

        .ch-post-media-preview-feed.ch-post-media-gallery-1 .ch-post-media-thumb {
            aspect-ratio: 1.08 / 1;
            max-height: 320px;
        }

        @media (min-width: 769px) {
            .ch-post-media-preview-feed.ch-post-media-gallery-1 .ch-post-media-thumb {
                aspect-ratio: auto;
                min-height: 0;
                max-height: 420px;
                background: color-mix(in srgb, var(--ch-bg) 90%, var(--ch-surface) 10%);
            }

            .ch-post-media-preview-feed.ch-post-media-gallery-1 .ch-post-media-thumb-img,
            .ch-post-media-preview-feed.ch-post-media-gallery-1 .ch-post-media-thumb-video {
                height: auto;
                min-height: 0;
                max-height: 420px;
                object-fit: contain;
            }
        }

        .ch-post-media-thumb {
            display: block;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--ch-border);
            background: color-mix(in srgb, var(--ch-surface) 72%, var(--ch-bg) 28%);
            min-height: 120px;
            aspect-ratio: 1.1 / 1;
            max-height: 240px;
            position: relative;
        }

        .ch-post-media-thumb-button {
            padding: 0;
            cursor: pointer;
            width: 100%;
            text-align: left;
            appearance: none;
            -webkit-appearance: none;
        }

        .ch-post-media-thumb-button:hover .ch-post-media-thumb-img {
            transform: scale(1.03);
        }

        .ch-post-media-thumb-img,
        .ch-post-media-thumb-video {
            width: 100%;
            height: 100%;
            min-height: 120px;
            max-height: 240px;
            object-fit: cover;
            display: block;
            transition: transform 0.2s ease;
        }

        .ch-post-media-more {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.46);
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .ch-post-media-audio-wrap {
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            border-radius: 12px;
            padding: 10px;
        }

        .ch-post-media-audio-inline {
            width: 100%;
        }

        .ch-post-media-preview-mixed {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .ch-media-item {
            border-radius: var(--ch-radius);
            overflow: hidden;
            border: 1px solid var(--ch-border);
            background: color-mix(in srgb, var(--ch-bg) 90%, var(--ch-surface) 10%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ch-media-image {
            width: 100%;
            max-height: min(460px, 55vh);
            object-fit: contain;
            display: block;
            background: color-mix(in srgb, var(--ch-bg) 90%, var(--ch-surface) 10%);
        }

        .ch-media-video {
            width: 100%;
            max-height: min(460px, 55vh);
            object-fit: contain;
            display: block;
            background: color-mix(in srgb, var(--ch-bg) 90%, var(--ch-surface) 10%);
        }

        .ch-media-audio {
            width: 100%;
            display: block;
        }

        .ch-media-lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.94);
            z-index: 100001;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(8px);
        }

        .ch-media-lightbox {
            position: relative;
            width: min(96vw, 1120px);
            height: min(90vh, 820px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ch-media-lightbox-stage {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px 56px;
        }

        .ch-media-lightbox-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.36);
            image-rendering: auto;
        }

        .ch-media-lightbox-close,
        .ch-media-lightbox-nav {
            position: absolute;
            border: none;
            width: 46px;
            height: 46px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(12px);
        }

        .ch-media-lightbox-close {
            top: 8px;
            right: 8px;
        }

        .ch-media-lightbox-nav {
            top: 50%;
            transform: translateY(-50%);
        }

        .ch-media-lightbox-prev {
            left: 8px;
        }

        .ch-media-lightbox-next {
            right: 8px;
        }

        .ch-media-lightbox-close:hover,
        .ch-media-lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .ch-media-lightbox-counter {
            position: absolute;
            left: 50%;
            bottom: 8px;
            transform: translateX(-50%);
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.55);
            color: #fff;
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .ch-post-vote-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            row-gap: 8px;
            padding-top: 14px;
            border-top: 1px solid var(--ch-border-soft);
            flex-wrap: wrap;
        }

        .ch-vote-btn-lg {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: var(--ch-radius);
            border: 1px solid var(--ch-border);
            background: var(--ch-surface);
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ch-text-muted);
            transition: all 0.15s;
            font-family: var(--ch-font);
            min-height: 38px;
            white-space: nowrap;
        }

        .ch-vote-btn-lg:hover {
            background: var(--ch-bg);
        }

        .ch-vote-btn-lg.ch-vote-up:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-vote-btn-lg.ch-vote-down:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #ef4444;
        }

        .ch-vote-btn-lg.ch-danger {
            color: #ef4444;
            border-color: #fca5a5;
        }

        .ch-vote-btn-lg.ch-danger:hover {
            background: #fee2e2;
            border-color: #ef4444;
        }

        .ch-vote-score {
            display: inline-flex;
            align-items: center;
            font-size: 15px;
            font-weight: 700;
            color: var(--ch-accent);
            min-height: 38px;
        }

        /* COMMENTS */
        .ch-comments-section {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-xl);
            padding: 32px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-comments-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 20px;
            color: var(--ch-text);
        }

        .ch-comment-form {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            align-items: flex-start;
        }

        .ch-comment-input-wrap {
            flex: 1;
            min-width: 0;
        }

        .ch-comment-input-wrap .ch-input {
            width: 100%;
            box-sizing: border-box;
        }

        .ch-comment-form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ch-comment-form-footer .ch-btn {
            margin-left: auto;
        }

        .ch-comments-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ch-comment {
            display: flex;
            gap: 12px;
            padding: 16px 0;
            border-top: 1px solid var(--ch-border-soft);
        }

        .ch-comment:first-child {
            border-top: none;
            padding-top: 0;
        }

        .ch-comment-reply {
            padding-left: 10px;
        }

        .ch-comment-body {
            flex: 1;
        }

        .ch-comment-header {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 5px;
            flex-wrap: wrap;
        }

        .ch-comment-header strong {
            font-size: 13px;
            color: var(--ch-text);
        }

        .ch-comment-time {
            font-size: 11px;
            color: var(--ch-text-subtle);
            white-space: nowrap;
        }

        .ch-comment-body p {
            font-size: 13.5px;
            color: var(--ch-text-muted);
            margin: 0 0 7px;
            line-height: 1.6;
        }

        .ch-comment-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            row-gap: 6px;
        }

        .ch-comment-action {
            font-size: 12px;
            color: var(--ch-text-subtle);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 2px;
            transition: color 0.15s;
            font-family: var(--ch-font);
            min-height: 32px;
            display: inline-flex;
            align-items: center;
        }

        .ch-comment-action:hover {
            color: var(--ch-accent);
        }

        .ch-danger-action:hover {
            color: #ef4444;
        }

        .ch-mention {
            background-color: var(--ch-accent-light);
            color: var(--ch-accent-dark);
            padding: 1px 5px;
            border-radius: var(--ch-radius-sm);
            font-weight: 500;
            font-size: 0.95em;
        }

        .ch-mention-dropdown {
            position: absolute;
            z-index: 9999;
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            min-width: 200px;
            max-width: 280px;
            overflow: hidden;
        }

        .ch-mention-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13.5px;
            transition: background 0.1s;
        }

        .ch-mention-item:hover,
        .ch-mention-item.active {
            background: var(--ch-accent-light);
        }

        .ch-mention-item-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--ch-accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .ch-mention-item-name {
            font-weight: 600;
            color: var(--ch-text);
            line-height: 1.2;
        }

        .ch-mention-item-handle {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
        }

        .ch-mention-no-results {
            padding: 10px 14px;
            font-size: 13px;
            color: var(--ch-text-subtle);
        }

        .ch-replies {
            margin-top: 2px;
            padding-left: 14px;
            border-left: 2px solid var(--ch-border-soft);
        }

        .ch-reply-form {
            margin-top: 8px;
            padding: 10px;
            background: var(--ch-bg);
            border-radius: var(--ch-radius);
        }

        .ch-login-prompt {
            font-size: 13.5px;
            color: var(--ch-text-muted);
            line-height: 1.6;
        }

        .ch-login-prompt a {
            color: var(--ch-accent);
        }

        .ch-post-view-sidebar>.ch-sidebar-widget {
            margin-bottom: 14px;
        }

        /* AUTH PAGE */
        .ch-auth-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            background: linear-gradient(135deg, var(--ch-surface), var(--ch-bg));
            font-family: var(--ch-font);
        }

        .ch-auth-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(17, 24, 39, 0.08);
            border: 1px solid var(--ch-border);
            padding: 42px;
            width: 100%;
            max-width: 460px;
            transition: transform 0.3s ease;
        }

        .ch-auth-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
            margin-bottom: 32px;
            padding-bottom: 0;
            border-bottom: none;
        }

        .ch-auth-logo {
            width: auto;
            max-width: 180px;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-bottom: 8px;
        }

        .ch-auth-logo img {
            width: 100%;
            height: auto;
            max-height: 80px;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.06));
        }

        .ch-auth-brand-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--ch-text);
            letter-spacing: -0.3px;
        }

        .ch-auth-brand-tagline {
            font-size: 12px;
            color: var(--ch-text-subtle);
            margin-top: 1px;
        }

        .ch-auth-tabs {
            display: flex;
            background: var(--ch-bg);
            border-radius: var(--ch-radius);
            padding: 4px;
            margin-bottom: 24px;
            border: 1px solid var(--ch-border);
        }

        .ch-auth-tab {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            border-radius: var(--ch-radius-sm);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: var(--ch-text-muted);
            transition: all 0.2s;
        }

        .ch-auth-tab.active {
            background: var(--ch-surface);
            color: var(--ch-accent);
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-auth-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .ch-auth-form .ch-field-group {
            margin-bottom: 0;
        }

        .ch-auth-form .ch-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ch-text);
            margin-bottom: 6px;
            display: block;
            line-height: 1.35;
        }

        .ch-auth-form .ch-input {
            width: 100%;
            box-sizing: border-box;
            min-height: 44px;
        }

        .ch-password-wrap {
            position: relative;
        }

        .ch-password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ch-text-subtle);
            display: flex;
            align-items: center;
            padding: 4px;
            transition: color 0.15s;
        }

        .ch-password-toggle:hover {
            color: var(--ch-accent);
        }

        .ch-password-strength {
            height: 3px;
            border-radius: 2px;
            margin-top: 7px;
            background: var(--ch-border);
            overflow: hidden;
        }

        .ch-password-strength::after {
            content: '';
            display: block;
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
        }

        .ch-password-strength[data-strength="0"]::after {
            width: 0%;
        }

        .ch-password-strength[data-strength="1"]::after {
            width: 25%;
            background: #ef4444;
        }

        .ch-password-strength[data-strength="2"]::after {
            width: 50%;
            background: #f59e0b;
        }

        .ch-password-strength[data-strength="3"]::after {
            width: 75%;
            background: #3b82f6;
        }

        .ch-password-strength[data-strength="4"]::after {
            width: 100%;
            background: #10b981;
        }

        .ch-auth-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 7px;
        }

        .ch-auth-submit {
            height: 42px;
            font-size: 14px;
            border-radius: var(--ch-radius);
            margin-top: 4px;
            width: 100%;
            min-height: 44px;
        }

        .ch-auth-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .ch-auth-switch {
            text-align: center;
            font-size: 12.5px;
            color: var(--ch-text-muted);
            margin: 14px 0 0;
        }

        .ch-auth-link {
            color: var(--ch-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .ch-auth-link:hover {
            text-decoration: underline;
        }

        .ch-required {
            color: #ef4444;
        }

        .ch-optional {
            color: var(--ch-text-subtle);
            font-weight: 400;
        }

        .ch-auth-footer {
            text-align: center;
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            margin-top: 18px;
            max-width: 380px;
            line-height: 1.5;
        }

        #ch-auth-msg {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }

        #ch-auth-msg:empty {
            display: none;
        }

        #ch-auth-msg .bntm-notice-success {
            background: #d1fae5;
            color: #065f46;
            padding: 9px 13px;
            border-radius: var(--ch-radius);
            font-size: 12.5px;
            margin-bottom: 0;
        }

        #ch-auth-msg .bntm-notice-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 9px 13px;
            border-radius: var(--ch-radius);
            font-size: 12.5px;
            margin-bottom: 0;
        }

        #ch-post-msg,
        #ch-edit-post-msg {
            display: grid;
            gap: 8px;
            padding: 0 24px 10px;
        }

        #ch-post-msg:empty,
        #ch-edit-post-msg:empty {
            display: none;
        }

        #ch-post-msg .bntm-notice,
        #ch-edit-post-msg .bntm-notice {
            margin-bottom: 0;
        }

        /* GUEST LANDING */
        .ch-guest-landing-wrap {
            display: flex;
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 20px;
        }

        .ch-guest-sidebar {
            width: 220px;
            flex-shrink: 0;
        }

        .ch-guest-main {
            flex: 1;
            min-width: 0;
        }

        .ch-guest-right {
            width: 250px;
            flex-shrink: 0;
        }

        .ch-guest-helper {
            margin: -4px 0 12px;
            font-size: 12.5px;
            color: var(--ch-text-subtle);
            line-height: 1.5;
        }

        .ch-guest-cta {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid var(--ch-border-soft);
        }

        /* MY FEED */
        .ch-my-feed-wrap {
            max-width: 840px;
            margin: 0 auto;
            padding: 26px 20px;
        }

        .ch-mf-profile-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-xl);
            padding: 24px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 14px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-mf-avatar-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
        }

        .ch-mf-avatar-img {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--ch-border);
        }

        .ch-mf-avatar-initials {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: #fff;
            font-size: 30px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ch-mf-profile-info {
            flex: 1;
            min-width: 0;
        }

        .ch-mf-name-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .ch-mf-name {
            font-size: 21px;
            font-weight: 800;
            color: var(--ch-text);
            margin: 0;
            letter-spacing: -0.3px;
        }

        .ch-mf-bio {
            font-size: 13.5px;
            color: var(--ch-text-muted);
            margin: 5px 0 8px;
            line-height: 1.6;
        }

        .ch-mf-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ch-mf-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12.5px;
            color: var(--ch-text-subtle);
        }

        .ch-mf-stats-bar {
            display: flex;
            gap: 9px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .ch-mf-stat {
            flex: 1;
            min-width: 95px;
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 14px 10px;
            text-align: center;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-mf-stat-num {
            display: block;
            font-size: 20px;
            font-weight: 800;
            color: var(--ch-accent);
            letter-spacing: -0.3px;
        }

        .ch-mf-stat-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            font-size: 10.5px;
            color: var(--ch-text-subtle);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 600;
        }

        .ch-mf-subnav {
            display: flex;
            gap: 3px;
            background: var(--ch-bg);
            border-radius: var(--ch-radius);
            padding: 3px;
            margin-bottom: 18px;
            border: 1px solid var(--ch-border);
        }

        .ch-mf-subnav-item {
            flex: 1;
            text-align: center;
            padding: 7px 0;
            border-radius: var(--ch-radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: var(--ch-text-muted);
            transition: all .18s;
            background: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .ch-mf-subnav-item.active {
            background: var(--ch-surface);
            color: var(--ch-accent);
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-mf-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ch-mf-post-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 16px 18px;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-mf-post-card:hover {
            border-color: var(--ch-accent-mid);
            box-shadow: var(--ch-shadow);
        }

        .ch-mf-post-top {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .ch-mf-time {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            margin-left: auto;
        }

        .ch-mf-pinned-badge {
            font-size: 11px;
            color: var(--ch-accent);
            font-weight: 600;
        }

        .ch-mf-post-actions {
            display: flex;
            gap: 5px;
            margin-left: 6px;
        }

        .ch-mf-post-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--ch-text);
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
            line-height: 1.4;
            letter-spacing: -0.2px;
        }

        .ch-mf-post-title:hover {
            color: var(--ch-accent);
        }

        .ch-mf-post-excerpt {
            font-size: 13px;
            color: var(--ch-text-muted);
            margin: 0 0 10px;
            line-height: 1.55;
        }

        .ch-mf-post-footer {
            display: flex;
            gap: 14px;
            font-size: 12px;
            color: var(--ch-text-subtle);
        }

        .ch-mf-comment-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 14px 18px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-mf-comment-post-ref {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--ch-text-subtle);
            margin-bottom: 7px;
        }

        .ch-mf-ref-link {
            color: var(--ch-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .ch-mf-comment-content {
            font-size: 13.5px;
            color: var(--ch-text-muted);
            margin: 0 0 8px;
            line-height: 1.55;
        }

        .ch-mf-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 52px 20px;
            text-align: center;
            background: var(--ch-surface);
            border: 1px dashed var(--ch-border);
            border-radius: var(--ch-radius-lg);
        }

        .ch-mf-empty p {
            font-size: 14px;
            color: var(--ch-text-subtle);
            margin: 0;
        }

        /* PUBLIC PROFILE */
        .ch-public-profile-wrap {
            max-width: 780px;
            margin: 0 auto;
            padding: 24px 20px;
        }

        .ch-public-profile-header {
            margin-bottom: 18px;
        }

        .ch-public-profile-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-xl);
            padding: 24px;
            display: flex;
            gap: 22px;
            align-items: flex-start;
            margin-bottom: 16px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-public-profile-avatar img {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--ch-border);
        }

        .ch-avatar-lg {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ch-public-profile-name {
            font-size: 20px;
            font-weight: 800;
            color: var(--ch-text);
            margin: 0 0 6px;
            letter-spacing: -0.3px;
        }

        .ch-public-profile-meta {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            color: var(--ch-text-muted);
            margin-bottom: 3px;
        }

        .ch-public-profile-bio {
            font-size: 13.5px;
            color: var(--ch-text-muted);
            margin: 8px 0;
        }

        .ch-public-profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 22px;
        }

        .ch-public-stat-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 18px;
            text-align: center;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-public-stat-card .ch-stat-num {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: var(--ch-accent);
            letter-spacing: -0.4px;
        }

        .ch-public-stat-card .ch-stat-label {
            display: block;
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            margin-top: 3px;
            font-weight: 500;
        }

        .ch-section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--ch-text);
            margin: 0 0 14px;
        }

        .ch-public-post-card {
            display: block;
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 16px;
            margin-bottom: 10px;
            text-decoration: none;
            color: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-public-post-card:hover {
            border-color: var(--ch-accent-mid);
            box-shadow: var(--ch-shadow);
        }

        .ch-public-post-top {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 7px;
        }

        .ch-public-post-time {
            font-size: 11.5px;
            color: var(--ch-text-subtle);
            margin-left: auto;
        }

        .ch-public-post-title {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--ch-text);
            margin: 0 0 5px;
            letter-spacing: -0.2px;
        }

        .ch-public-post-excerpt {
            font-size: 13px;
            color: var(--ch-text-muted);
            margin: 0 0 9px;
            line-height: 1.55;
        }

        .ch-public-post-footer {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--ch-text-subtle);
        }

        /* VISIBILITY TOGGLE */
        .ch-visibility-toggle {
            display: flex;
            gap: 8px;
        }

        .ch-vis-option {
            flex: 1;
            border: 1.5px solid var(--ch-border);
            border-radius: var(--ch-radius);
            padding: 10px 12px;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .ch-vis-option:has(input:checked) {
            border-color: var(--ch-accent);
            background: var(--ch-accent-light);
        }

        .ch-vis-option input {
            display: none;
        }

        .ch-vis-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ch-text);
        }

        .ch-vis-desc {
            font-size: 11px;
            color: var(--ch-text-subtle);
        }

        /* ANNOUNCEMENTS */
        .ch-announcement-card {
            background: linear-gradient(135deg, #fefce8, #fef9c3);
            border: 1px solid #fde68a;
            border-radius: var(--ch-radius-lg);
            padding: 18px 20px;
            margin-bottom: 14px;
            position: relative;
            overflow: hidden;
        }

        .ch-announcement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .ch-announcement-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .ch-announcement-icon {
            color: #d97706;
            flex-shrink: 0;
        }

        .ch-announcement-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .ch-announcement-label {
            font-weight: 700;
            font-size: 11px;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ch-announcement-author {
            font-size: 12.5px;
            color: #a16207;
        }

        .ch-announcement-time {
            font-size: 11.5px;
            color: #a16207;
            opacity: 0.8;
        }

        .ch-announcement-body {
            margin-left: 30px;
        }

        .ch-announcement-title {
            font-size: 16px;
            font-weight: 700;
            color: #92400e;
            margin: 0 0 7px;
        }

        .ch-announcement-content {
            color: #78350f;
            line-height: 1.6;
            font-size: 13.5px;
        }

        .ch-announcement-content p {
            margin: 0 0 6px 0;
        }

        .ch-announcement-content p:last-child {
            margin-bottom: 0;
        }

        /* ── SPECIAL PAGES (Trending / Bookmarks) ───────────────────── */
        .ch-special-page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px 48px;
        }

        .ch-special-hero {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 32px 0 24px;
            border-bottom: 1px solid var(--ch-border);
            margin-bottom: 28px;
        }

        .ch-special-hero-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--ch-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ch-trending-hero .ch-special-hero-icon {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .ch-dark .ch-trending-hero .ch-special-hero-icon {
            background: #451a03;
            color: #fbbf24;
        }

        .ch-bookmarks-hero .ch-special-hero-icon {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
        }

        .ch-dark .ch-bookmarks-hero .ch-special-hero-icon {
            background: #1e1b4b;
            color: #a78bfa;
        }

        .ch-special-hero-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--ch-text);
            margin: 0 0 4px;
            letter-spacing: -0.4px;
        }

        .ch-special-hero-sub {
            font-size: 14px;
            color: var(--ch-text-muted);
            margin: 0;
        }

        .ch-special-layout {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }

        .ch-special-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ch-special-sidebar {
            width: 220px;
            flex-shrink: 0;
        }

        /* Trending post card */
        .ch-trending-post-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 16px 18px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
            box-shadow: var(--ch-shadow-sm);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .ch-trending-post-card:hover {
            border-color: var(--ch-accent-mid);
            box-shadow: var(--ch-shadow);
        }

        .ch-trending-rank {
            font-size: 22px;
            font-weight: 900;
            color: var(--ch-accent);
            min-width: 32px;
            line-height: 1;
            padding-top: 2px;
            opacity: 0.7;
        }

        .ch-trending-body {
            flex: 1;
            min-width: 0;
        }

        .ch-trending-post-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--ch-text);
            text-decoration: none;
            line-height: 1.4;
            display: block;
            margin-bottom: 5px;
        }

        .ch-trending-post-title:hover {
            color: var(--ch-accent);
        }

        .ch-trending-post-excerpt {
            font-size: 13px;
            color: var(--ch-text-muted);
            margin: 0 0 10px;
            line-height: 1.5;
        }

        .ch-trending-stats {
            display: flex;
            gap: 14px;
        }

        .ch-trending-stat {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: var(--ch-text-subtle);
        }

        /* Profile standalone nav */
        .ch-profile-standalone {
            font-family: var(--ch-font);
            background: var(--ch-bg);
            min-height: 100vh;
            color: var(--ch-text);
        }

        /* ── DARK MODE: special pages sidebar ────────────────────────── */
        .ch-dark .ch-trending-post-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-card,
        .ch-post-card,
        .ch-popular-cats-card,
        .ch-post-full,
        .ch-comments-section,
        .ch-composer-trigger-card,
        .ch-dropdown-panel,
        .ch-share-menu {
            backdrop-filter: blur(10px);
        }

        .ch-composer-trigger-card,
        .ch-popular-cats-card,
        .ch-post-full,
        .ch-comments-section {
            box-shadow: 0 12px 28px rgba(255, 117, 81, 0.08);
        }

        .ch-post-meta-row,
        .ch-post-actions-row,
        .ch-toolbar,
        .ch-card-header {
            row-gap: 8px;
        }

        /* ============================================================
            RESPONSIVE — comprehensive mobile/tablet fixes
            ============================================================ */

        /* Global overflow prevention — stops horizontal scroll on mobile */
        html,
        body {
            overflow-x: hidden;
            max-width: 100%;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        /* Prevent containers from breaking viewport */
        @media (max-width: 780px) {

            html,
            body {
                overflow-x: hidden;
            }

            .ch-feed-wrap,
            .ch-dashboard-wrap,
            .ch-my-feed-wrap,
            .ch-auth-wrap,
            .ch-public-profile-wrap,
            .ch-guest-landing-wrap,
            .ch-special-page-wrap,
            .ch-profile-standalone {
                max-width: 100vw;
                overflow-x: hidden;
            }

            /* Force flexible widths */
            img,
            video,
            iframe,
            svg {
                max-width: 100%;
                height: auto;
            }

            /* Prevent pre/code blocks from breaking layout */
            pre,
            code,
            .bntm-table-wrapper {
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Filter form helper classes */
        .ch-categories-filter-row {
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .ch-filter-search-group {
            flex: 1;
            min-width: 0;
        }

        .ch-filter-sort-group {
            min-width: 140px;
        }

        .ch-filter-btn-group {
            display: flex;
            gap: 6px;
            align-items: flex-end;
            flex-shrink: 0;
        }

        /* Touch targets — 44px minimum for tappable elements */
        @media (hover: none) and (pointer: coarse) {

            .ch-btn,
            .ch-nav-link,
            .ch-cat-link,
            .ch-filter-btn,
            .ch-vote-btn,
            .ch-icon-btn,
            .ch-icon-action-btn,
            .ch-avatar-btn,
            .ch-burger-menu-btn,
            .ch-page-btn,
            .ch-post-action,
            .ch-comment-action,
            .ch-vote-btn-lg,
            .ch-share-option,
            .ch-auth-submit,
            .ch-composer-submit {
                min-height: 44px;
            }

            .ch-vote-btn,
            .ch-icon-btn,
            .ch-icon-action-btn,
            .ch-avatar-btn,
            .ch-burger-menu-btn {
                min-width: 44px;
            }

            .ch-input,
            .ch-textarea,
            .ch-search-input {
                font-size: 16px; /* prevents iOS zoom on focus */
            }

            /* Disable flicker-on-tap hover scale for avatar */
            .ch-avatar-btn:hover {
                transform: none;
            }
        }

        /* ── Tablet: 1024px ───────────────────────────────────────── */
        @media (max-width: 1024px) {
            .ch-feed-wrap {
                gap: 18px;
                padding: 18px 16px;
            }

            .ch-feed-sidebar {
                width: 200px;
            }

            .ch-post-view-grid {
                grid-template-columns: 1fr 220px;
                gap: 16px;
            }

            .ch-stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .ch-two-col {
                gap: 14px;
            }

            .ch-guest-landing-wrap {
                gap: 18px;
            }

            .ch-guest-right {
                width: 220px;
            }

            .ch-guest-sidebar {
                width: 200px;
            }

            .ch-main-content {
                padding: 22px 20px 40px;
            }
        }

        /* ── Small tablet / phablet: 780px ───────────────────────── */
        @media (max-width: 780px) {
            .ch-dashboard-wrap {
                flex-direction: column;
            }

            .ch-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--ch-border);
                padding: 10px 0;
            }

            .ch-burger-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .ch-nav {
                display: none;
                width: 100%;
                flex-direction: column;
                padding: 12px 16px;
                animation: ch-fade-up 0.2s ease;
                margin-top: 16px;
                border-top: 1px solid var(--ch-border-soft);
                gap: 4px;
            }

            .ch-nav-item {
                font-size: 14px;
                padding: 10px 16px;
                border-radius: var(--ch-radius);
            }

            .ch-nav-item.active {
                border-left: none;
                background: color-mix(in srgb, var(--ch-accent) 10%, transparent);
                color: var(--ch-accent);
            }

            .ch-sidebar-header {
                padding: 8px 16px 12px;
                display: flex !important;
                justify-content: flex-start !important;
                align-items: center;
                gap: 8px;
            }

            .ch-brand-logo {
                height: 26px;
                width: auto;
                max-width: 130px;
                object-fit: contain;
                display: block;
                flex-shrink: 0;
            }

            .ch-sidebar-header .ch-burger-menu-btn {
                order: 3;
                margin-left: auto;
                margin-right: 0 !important;
            }

            /* Mobile top-nav: revert to flex row, safe-area aware */
            .ch-top-nav {
                display: flex !important;
                grid-template-columns: none;
                padding: 0 16px;
                height: 60px;
                padding-left: max(16px, env(safe-area-inset-left));
                padding-right: max(16px, env(safe-area-inset-right));
            }

            .ch-top-nav-logo {
                order: 1;
                flex-shrink: 0;
                margin-right: 0 !important;
            }

            /* Mobile drawer: occupies the flex middle but is visually offscreen */
            .ch-top-nav .ch-mobile-drawer-wrap {
                order: 2;
                flex: 1 1 auto;
            }

            /* Notification bell stays visible in the mobile header (logged-in only) */
            .ch-top-nav .ch-nav-user-desktop {
                order: 3;
                display: flex !important;
                align-items: center;
                margin-left: auto;
                margin-right: 56px;
            }

            .ch-top-nav .ch-top-nav-notifications {
                display: flex !important;
                align-items: center;
                margin-left: 0;
                margin-right: 0;
            }

            /* Burger: far right, full tap target */
            .ch-top-nav {
                position: relative;
            }

            .ch-top-nav .ch-burger-menu-btn {
                order: 4;
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                margin-left: 0;
                position: absolute;
                right: max(16px, env(safe-area-inset-right));
                top: 50%;
                transform: translateY(-50%);
            }

            /* Nav links are inside the drawer on mobile — not shown in the bar */
            .ch-nav-links {
                display: none;
            }

            /* Hide user bars by default on mobile */
            .ch-user-bar {
                display: none;
            }

            /* Keep only the logged-in top-nav user bar visible for the notifications bell */
            .ch-top-nav .ch-nav-user-desktop {
                display: flex !important;
            }

            .ch-top-nav .ch-nav-user-desktop::before {
                display: none !important;
            }

            .ch-top-nav .ch-nav-user-desktop .ch-profile-dropdown {
                display: none !important;
            }

            .ch-nav-links.ch-nav-open,
            .ch-user-bar.ch-nav-open,
            .ch-nav.ch-nav-open {
                display: flex;
                animation: ch-fade-up 0.2s ease;
            }

            .ch-nav-link {
                width: 100%;
                padding: 12px;
                font-size: 15px;
                border-radius: var(--ch-radius);
            }

            .ch-dark .ch-nav-links,
            .ch-dark .ch-user-bar {
                background: var(--ch-surface);
                border-color: var(--ch-border-soft);
            }

            .ch-main-content {
                padding: 16px 14px 28px;
            }

            .ch-two-col {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .ch-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .ch-categories-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .ch-page-header {
                flex-direction: column;
                gap: 12px;
            }

            .ch-page-header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .ch-page-header-actions .ch-btn {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }

            .ch-toolbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .ch-toolbar-right {
                width: 100%;
            }

            .ch-toolbar-filters {
                flex-wrap: wrap;
            }

            .ch-categories-filter-row {
                flex-direction: column;
                gap: 8px;
            }

            .ch-filter-search-group,
            .ch-filter-sort-group {
                width: 100%;
                min-width: unset;
            }

            .ch-filter-btn-group {
                width: 100%;
            }

            .ch-filter-btn-group .ch-btn {
                flex: 1;
                justify-content: center;
            }

            .ch-search-form {
                width: 100%;
            }

            .ch-search-form input {
                flex: 1;
                min-width: 0;
            }

            .ch-feed-wrap {
                flex-direction: column;
                padding: 14px 12px;
                gap: 12px;
            }

            .ch-feed-sidebar {
                width: 100%;
            }

            .ch-post-card {
                flex-direction: column;
                padding: 18px 16px;
                gap: 12px;
            }

            .ch-post-vote-col {
                flex-direction: row;
                align-items: center;
                gap: 8px;
                min-width: 0;
                padding-top: 0;
            }

            .ch-post-card .ch-post-time {
                margin-left: 0;
                width: 100%;
            }

            .ch-post-media-preview-feed {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ch-post-media-gallery-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ch-post-media-gallery-3 .ch-post-media-thumb:first-child {
                grid-row: auto;
                min-height: 120px;
            }

            .ch-feed-toolbar-card {
                padding: 10px 12px;
            }

            .ch-filter-row {
                flex-wrap: wrap;
                gap: 8px;
            }

            .ch-sort-tabs {
                flex-wrap: wrap;
            }

            .ch-location-form {
                width: 100%;
            }

            .ch-location-select {
                width: 100%;
            }

            .ch-post-view-grid {
                grid-template-columns: 1fr;
            }

            .ch-post-view-main {
                order: 1;
            }

            .ch-post-view-sidebar {
                order: 2;
                display: block;
                margin-top: 12px;
            }

            .ch-post-view-sidebar > .ch-sidebar-widget {
                margin-bottom: 0;
            }

            .ch-post-view-wrap {
                padding: 14px 12px;
            }

            .ch-post-full {
                padding: 18px 16px;
            }

            .ch-post-full-title {
                font-size: 18px;
            }

            .ch-post-full-content {
                font-size: 15px;
                line-height: 1.7;
                overflow-wrap: anywhere;
            }

            .ch-post-meta-row {
                align-items: flex-start;
                row-gap: 6px;
            }

            .ch-post-full .ch-post-meta-row {
                width: 100%;
            }

            .ch-post-full .ch-post-time {
                margin-left: 0;
                width: auto;
            }

            .ch-post-full .ch-cat-badge {
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .ch-post-views {
                margin-left: 0;
            }

            .ch-post-vote-bar {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .ch-post-vote-bar .ch-vote-btn-lg {
                padding: 8px 10px;
                font-size: 12px;
                min-height: 44px;
                justify-content: center;
                width: auto;
                flex: 1 1 calc(50% - 4px);
            }

            .ch-post-vote-bar .ch-vote-score {
                flex: 1 1 100%;
                justify-content: center;
                min-height: 0;
                order: -1;
                text-align: center;
            }

            .ch-comments-section {
                padding: 16px 14px;
            }

            .ch-comment-form {
                gap: 8px;
                align-items: stretch;
            }

            .ch-comment-form .ch-avatar-sm {
                width: 36px;
                height: 36px;
                min-width: 36px;
            }

            .ch-comment-input-wrap .ch-textarea {
                min-height: 96px;
            }

            .ch-comment-form-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .ch-comment-form-footer .ch-btn {
                width: 100%;
                margin-left: 0;
            }

            .ch-mf-profile-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 18px 16px;
            }

            .ch-mf-profile-info {
                width: 100%;
                text-align: center;
            }

            .ch-mf-name-row {
                justify-content: center;
            }

            .ch-mf-meta-row {
                justify-content: center;
            }

            .ch-mf-stats-bar {
                gap: 6px;
            }

            .ch-mf-stat {
                min-width: 70px;
                padding: 10px 8px;
            }

            .ch-mf-stat-num {
                font-size: 17px;
            }

            .ch-mf-subnav-item {
                font-size: 12px;
                padding: 6px 0;
            }

            .ch-auth-card {
                padding: 32px 24px;
                border-radius: 20px;
            }

            .ch-auth-wrap {
                padding: 20px 12px;
            }

            .ch-auth-form {
                gap: 14px;
            }

            .ch-auth-row {
                margin-bottom: 0;
            }

            .ch-field-row {
                flex-direction: column;
                gap: 0;
            }

            .ch-guest-landing-wrap {
                flex-direction: column;
                padding: 14px 12px;
            }

            .ch-guest-sidebar,
            .ch-guest-right {
                width: 100%;
            }

            .ch-popular-cats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .ch-welcome-card {
                padding: 24px 18px;
            }

            .ch-welcome-title {
                font-size: 19px;
            }

            .ch-setting-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .ch-setting-control {
                width: 100%;
            }

            .ch-setting-number {
                width: 100%;
            }

            .ch-visibility-toggle {
                flex-direction: column;
            }

            .ch-cat-hero-meta {
                flex-wrap: wrap;
                gap: 10px;
            }

            .ch-cat-hero {
                padding: 14px 16px;
            }

            .ch-onboarding-hero {
                flex-direction: column;
            }

            .ch-onboarding-footer,
            .ch-onboarding-footer-left,
            .ch-onboarding-footer-right {
                width: 100%;
            }

            .ch-onboarding-footer-left .ch-btn,
            .ch-onboarding-footer-right .ch-btn {
                flex: 1;
                justify-content: center;
            }

            .bntm-table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .bntm-table {
                min-width: 560px;
                font-size: 12px;
            }

            .bntm-table th,
            .bntm-table td {
                padding: 9px 12px;
            }

            .ch-post-actions-row {
                flex-wrap: wrap;
            }

            .ch-share-dropdown {
                margin-left: 0;
            }

            /* Special pages (trending / bookmarks) */
            .ch-special-page-wrap {
                padding: 0 14px 32px;
            }

            .ch-special-hero {
                padding: 20px 0 18px;
                gap: 12px;
                margin-bottom: 20px;
            }

            .ch-special-hero-title {
                font-size: 20px;
            }

            .ch-special-layout {
                flex-direction: column;
            }

            .ch-special-sidebar {
                width: 100%;
            }
        }

        /* ── Mobile: 480px ────────────────────────────────────────── */
        @media (max-width: 480px) {
            .ch-top-nav {
                padding: 0 16px;
                height: 56px;
            }

            .ch-nav-link {
                padding: 5px 9px;
                font-size: 12px;
                gap: 5px;
            }

            .ch-nav-label {
                display: none;
            }

            .ch-stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .ch-stat-card {
                padding: 12px 14px;
                gap: 10px;
            }

            .ch-stat-num {
                font-size: 19px;
            }

            .ch-stat-icon {
                width: 36px;
                height: 36px;
            }

            .ch-categories-grid {
                grid-template-columns: 1fr;
            }

            .ch-post-card {
                padding: 12px 12px 10px;
                gap: 8px;
            }

            .ch-post-media-preview-feed {
                grid-template-columns: 1fr;
            }

            .ch-post-media-gallery-2,
            .ch-post-media-gallery-3,
            .ch-post-media-gallery-4,
            .ch-post-media-gallery-more {
                grid-template-columns: 1fr 1fr;
            }

            .ch-post-media-preview-post.ch-post-media-gallery-1 .ch-post-media-thumb,
            .ch-post-media-preview-feed.ch-post-media-gallery-1 .ch-post-media-thumb {
                aspect-ratio: 1.1 / 1;
                max-height: 280px;
            }

            .ch-post-title {
                font-size: 14px;
            }

            .ch-post-preview {
                font-size: 12.5px;
            }

            .ch-post-action {
                padding: 3px 7px;
                font-size: 11.5px;
            }

            .ch-post-full-title {
                font-size: 16px;
            }

            .ch-post-full-content {
                font-size: 14px;
            }

            .ch-post-meta-row {
                gap: 6px;
            }

            .ch-post-full .ch-post-meta-row > * {
                min-width: 0;
                max-width: 100%;
            }

            .ch-post-author,
            .ch-post-time,
            .ch-post-views {
                font-size: 11px;
            }

            .ch-post-view-wrap {
                padding: 12px 10px;
            }

            .ch-post-full {
                padding: 14px 12px;
            }

            .ch-comments-section {
                padding: 14px 12px;
            }

            .ch-post-vote-bar .ch-vote-btn-lg {
                flex-basis: 100%;
            }

            .ch-comment {
                gap: 8px;
            }

            .ch-comment-body p {
                font-size: 13px;
            }

            .ch-composer-media-preview {
                grid-template-columns: repeat(auto-fill, minmax(64px, 72px));
            }

            .ch-composer-media-chip {
                width: 72px;
                height: 72px;
            }

            .ch-media-lightbox-overlay {
                padding: 12px;
            }

            .ch-media-lightbox {
                width: 100%;
                height: min(82vh, 560px);
            }

            .ch-media-lightbox-stage {
                padding: 12px 36px 26px;
            }

            .ch-media-lightbox-close,
            .ch-media-lightbox-nav {
                width: 40px;
                height: 40px;
            }

            .ch-media-lightbox-prev {
                left: 0;
            }

            .ch-media-lightbox-next {
                right: 0;
            }

            .ch-media-lightbox-counter {
                bottom: 0;
            }

            .ch-modal {
                border-radius: 16px;
                margin: 8px;
                width: calc(100% - 16px);
            }

            .ch-modal-body {
                padding: 14px 16px;
            }

            .ch-modal-footer {
                padding: 10px 16px;
                gap: 6px;
            }

            .ch-modal-header {
                padding: 13px 16px;
            }

            .ch-modal-footer .ch-btn {
                flex: 1;
                justify-content: center;
            }

            .ch-auth-card {
                padding: 24px 16px;
                border-radius: 16px;
            }

            .ch-auth-tabs {
                margin-bottom: 18px;
            }

            .ch-auth-tab {
                padding: 11px 0;
                font-size: 13px;
            }

            .ch-terms-consent {
                gap: 8px;
                font-size: 12.5px;
            }

            .ch-terms-consent input {
                margin-top: 1px;
            }

            #ch-post-msg,
            #ch-edit-post-msg {
                padding: 0 16px 8px;
            }

            .ch-dropdown-panel {
                min-width: unset !important;
                width: calc(100vw - 24px) !important;
                right: 12px !important;
                left: 12px !important;
            }

            .ch-popular-cats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }

            .ch-pop-cat-chip {
                padding: 6px 8px;
                font-size: 11.5px;
            }

            .ch-pop-cat-count {
                display: none;
            }

            .ch-mf-stats-bar {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
            }

            .ch-mf-stat {
                min-width: unset;
            }

            .ch-mf-profile-card {
                padding: 16px 14px;
            }

            .ch-welcome-card {
                padding: 24px 18px 20px;
                border-radius: 24px;
                max-width: 360px;
            }

            .ch-welcome-icon {
                width: auto;
                height: auto;
                margin-bottom: 14px;
            }

            .ch-welcome-logo {
                width: 68px;
                height: 68px;
            }

            .ch-welcome-title {
                font-size: 17px;
            }

            .ch-welcome-subtitle {
                font-size: 12.5px;
                margin-bottom: 16px;
            }

            .ch-welcome-feature {
                padding: 12px;
                gap: 12px;
            }

            .ch-welcome-feature::before {
                width: 44px;
                height: 44px;
                border-radius: 14px;
            }

            .ch-welcome-feature-copy strong {
                font-size: 12.5px;
            }

            .ch-welcome-feature-copy span {
                font-size: 11.5px;
            }

            .ch-welcome-actions {
                flex-direction: column;
                gap: 8px;
            }

            .ch-welcome-btn {
                width: 100%;
                justify-content: center;
                min-height: 48px;
            }

            .ch-auth-form .ch-field-row {
                flex-direction: column;
                gap: 0;
            }

            .ch-auth-card {
                padding: 20px 16px;
            }

            .ch-feed-toolbar-card {
                padding: 10px;
            }

            .ch-search-form {
                flex-direction: column;
                gap: 6px;
            }

            .ch-search-form button {
                width: 100%;
                justify-content: center;
            }

            .ch-filter-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .ch-sort-tabs {
                width: 100%;
                justify-content: space-between;
            }

            .ch-sort-tab {
                flex: 1;
                text-align: center;
                padding: 5px 4px;
                font-size: 12px;
            }

            .ch-page-header h1 {
                font-size: 17px;
            }

            .ch-page-header p {
                font-size: 12px;
            }

            .ch-comment .ch-avatar-sm,
            .ch-comment-reply .ch-avatar-sm {
                display: none;
            }

            .ch-pagination {
                gap: 2px;
                padding: 10px 12px;
            }

            .ch-page-btn {
                width: 28px;
                height: 28px;
                font-size: 11.5px;
            }

            .ch-color-row {
                flex-wrap: wrap;
                gap: 6px;
            }

            .ch-color-hex-input {
                width: 100%;
            }

            .ch-card-header {
                flex-wrap: wrap;
                gap: 6px;
            }

            .ch-sidebar-widget {
                padding: 12px;
            }

            .ch-post-view-sidebar {
                display: block;
                margin-top: 10px;
            }

            .ch-announcement-body {
                margin-left: 0;
                margin-top: 8px;
            }

            .ch-announcement-title {
                font-size: 14px;
            }

            /* Special pages on mobile */
            .ch-special-hero {
                flex-direction: column;
                text-align: center;
                padding: 16px 0 14px;
            }

            .ch-special-hero-icon {
                width: 46px;
                height: 46px;
            }

            .ch-special-hero-title {
                font-size: 18px;
            }

            .ch-special-hero-sub {
                font-size: 12.5px;
            }

            .ch-trending-post-card {
                padding: 12px 14px;
                gap: 10px;
            }

            .ch-trending-rank {
                font-size: 18px;
                min-width: 24px;
            }

            /* Profile standalone nav on mobile */
            .ch-profile-standalone .ch-top-nav {
                padding: 0 12px;
            }
        }

        /* ── Very small: 360px ────────────────────────────────────── */
        @media (max-width: 360px) {
            .ch-top-nav {
                padding: 0 8px;
                height: 48px;
            }

            .ch-nav-link {
                padding: 4px 7px;
            }

            .ch-post-card {
                padding: 10px 10px 8px;
            }

            .ch-mf-stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .ch-welcome-actions .ch-btn {
                padding: 9px 12px;
            }

            .ch-auth-card {
                padding: 18px 12px;
            }

            .ch-stats-grid {
                grid-template-columns: 1fr;
            }

            .ch-popular-cats-grid {
                grid-template-columns: 1fr;
            }

            .ch-filter-btn-group {
                flex-direction: column;
            }

            .ch-filter-btn-group .ch-btn {
                width: 100%;
            }
        }

        @media (max-width: 680px) {
            .ch-special-layout {
                flex-direction: column;
            }

            .ch-special-sidebar {
                width: 100%;
            }
        }

        /* ============================================================
               POPULAR CATEGORIES CARD
               ============================================================ */
        .ch-popular-cats-card {
            background: var(--ch-surface);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-lg);
            padding: 14px 16px 16px;
            margin-bottom: 14px;

        }

        .ch-popular-cats-header {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: var(--ch-text-subtle);
            margin-bottom: 12px;
        }

        .ch-popular-cats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .ch-pop-cat-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: var(--ch-radius);
            background: var(--ch-bg);
            border: 1px solid var(--ch-border);
            text-decoration: none;
            color: var(--ch-text-muted);
            font-size: 12.5px;
            font-weight: 500;
            transition: all 0.15s;
            overflow: hidden;
        }

        .ch-pop-cat-chip:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-pop-cat-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ch-pop-cat-name {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ch-pop-cat-count {
            font-size: 10.5px;
            background: var(--ch-surface);
            color: var(--ch-text-subtle);
            padding: 1px 5px;
            border-radius: 10px;
            border: 1px solid var(--ch-border);
            flex-shrink: 0;
            line-height: 1.6;
        }

        /* ============================================================
               WELCOME POPUP
               ============================================================ */
        .ch-welcome-overlay {
            position: fixed;
            inset: 0;
            background: rgba(26, 26, 46, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            padding: 20px;
            backdrop-filter: blur(8px);
        }

        .ch-welcome-card {
            background: linear-gradient(180deg, #fffdfa 0%, #fdf6f0 100%);
            border-radius: 28px;
            padding: 34px 24px 22px;
            width: 100%;
            max-width: 372px;
            box-shadow: 0 26px 64px rgba(23, 29, 38, 0.18), 0 6px 20px rgba(255, 122, 89, 0.08);
            border: 1px solid rgba(223, 205, 191, 0.8);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        @media (min-width: 781px) {
            .ch-top-nav .ch-top-nav-notifications {
                margin-left: 0;
                margin-right: 0;
            }

            .ch-top-nav .ch-mobile-drawer-wrap>.ch-nav-links {
                order: 1;
            }

            .ch-top-nav .ch-mobile-drawer-wrap>.ch-user-bar {
                position: absolute;
                right: 0px;
                padding: 10px;
            }

            .ch-top-nav .ch-mobile-drawer-wrap>.ch-user-bar .ch-profile-dropdown {
                display: flex;
                align-items: center;
            }
        }

        .ch-welcome-glow {
            position: absolute;
            top: -48px;
            left: 50%;
            transform: translateX(-50%);
            width: 220px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 122, 89, 0.13) 0%, rgba(255, 194, 163, 0.08) 38%, transparent 74%);
            pointer-events: none;
        }

        .ch-welcome-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: var(--ch-bg);
            border: 1px solid var(--ch-border);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            color: var(--ch-text-subtle);
            line-height: 1;
            transition: all 0.15s;
        }

        .ch-welcome-close:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            border-color: var(--ch-accent-mid);
        }

        .ch-welcome-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: auto;
            margin: 0 auto 16px;
        }

        .ch-welcome-logo {
            width: 84px;
            height: 84px;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 10px 24px rgba(255, 122, 89, 0.14));
        }

        .ch-welcome-title {
            font-size: 27px;
            font-weight: 800;
            color: #1f2530;
            margin: 0 0 10px;
            letter-spacing: -0.6px;
            line-height: 1.08;
        }

        .ch-welcome-subtitle {
            font-size: 14px;
            color: #6d7280;
            margin: 0 auto 20px;
            line-height: 1.65;
            max-width: 280px;
        }

        .ch-welcome-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 22px;
            text-align: left;
        }

        .ch-welcome-feature {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(220, 209, 200, 0.92);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72), 0 8px 18px rgba(34, 40, 49, 0.05);
            position: relative;
        }

        .ch-welcome-feat-icon {
            display: none;
        }

        .ch-welcome-feature>span:not(.ch-welcome-feat-icon) {
            display: none;
        }

        .ch-welcome-feature::before {
            content: "";
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            border-radius: 16px;
            background-color: #fff6f1;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 28px 28px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 8px 18px rgba(255, 122, 89, 0.12);
        }

        .ch-welcome-feature:nth-child(1)::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 28 28' fill='none'%3E%3Crect x='2' y='5' width='14' height='11' rx='3' fill='%23FF7A59'/%3E%3Cpath d='M6 16l-1 3 3-3' fill='%23FF7A59'/%3E%3Crect x='11' y='10' width='15' height='11' rx='3' fill='%231D3557'/%3E%3Cpath d='M18 21l4 2-1-2' fill='%231D3557'/%3E%3Cpath d='M6 9h5M6 12h7M14 13h6M14 16h5' stroke='white' stroke-linecap='round' stroke-width='1.6'/%3E%3C/svg%3E");
        }

        .ch-welcome-feature:nth-child(2)::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 28 28' fill='none'%3E%3Cpath d='M14 3.5l3.2 6.1 6.8 1.1-4.9 4.8 1.1 6.8-6.2-3.2-6.2 3.2 1.1-6.8L4 10.7l6.8-1.1L14 3.5z' fill='%23FFB347'/%3E%3Ccircle cx='14' cy='14' r='3.4' fill='%23FFF4E7'/%3E%3Cpath d='M14 11.2v5.6M11.2 14h5.6' stroke='%231D3557' stroke-linecap='round' stroke-width='1.8'/%3E%3C/svg%3E");
        }

        .ch-welcome-feature:nth-child(3)::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 28 28' fill='none'%3E%3Crect x='5' y='11' width='18' height='12' rx='3' fill='%23FFD9C7'/%3E%3Cpath d='M8 11V8.5A1.5 1.5 0 019.5 7h4.8A1.7 1.7 0 0116 8.7V11' stroke='%231D3557' stroke-width='1.6'/%3E%3Cpath d='M14 10l4.7-3.9' stroke='%23FF7A59' stroke-linecap='round' stroke-width='2'/%3E%3Cpath d='M18.7 5.8l2-.3-.7 1.8' stroke='%23FF7A59' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3Cpath d='M9.6 15.3l2.4 2.3 5.3-5.3' stroke='%231D3557' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.1'/%3E%3C/svg%3E");
        }

        .ch-welcome-feature-copy {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .ch-welcome-feature-copy strong {
            display: block;
            font-size: 13px;
            line-height: 1.2;
            color: #1f2530;
            font-weight: 700;
        }

        .ch-welcome-feature-copy span {
            display: block;
            font-size: 12px;
            line-height: 1.5;
            color: #6d7280;
        }

        .ch-welcome-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .ch-welcome-btn {
            flex: 1;
            min-height: 50px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            font-family: var(--ch-font);
            transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease, border-color 0.16s ease;
        }

        .ch-welcome-btn:hover {
            transform: translateY(-1px);
        }

        .ch-welcome-btn-primary {
            color: white;
            background: linear-gradient(180deg, #ff8b6f 0%, #ff6f56 100%);
            box-shadow: 0 10px 24px rgba(255, 111, 86, 0.28);
        }

        .ch-welcome-btn-primary:hover {
            box-shadow: 0 14px 28px rgba(255, 111, 86, 0.34);
        }

        .ch-welcome-btn-secondary {
            color: #202632;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(220, 209, 200, 0.95);
            box-shadow: 0 6px 16px rgba(34, 40, 49, 0.05);
        }

        .ch-welcome-skip {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 12px;
            color: #7a7f8a;
            font-family: var(--ch-font);
            transition: color 0.15s;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .ch-welcome-skip:hover {
            color: #4c5563;
        }

        /* ============================================================
               SETTINGS MODAL
               ============================================================ */
        .ch-settings-section {
            padding: 6px 0;
            border-bottom: 1px solid var(--ch-border-soft);
        }

        .ch-settings-section-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--ch-text-subtle);
            padding: 12px 22px 6px;
        }

        .ch-settings-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 11px 22px;
            transition: background 0.1s;
        }

        .ch-settings-row-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .ch-settings-row-link:hover {
            background: var(--ch-bg);
        }

        .ch-settings-row-info {
            flex: 1;
            min-width: 0;
        }

        .ch-settings-row-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ch-text);
            margin-bottom: 2px;
        }

        .ch-settings-row-desc {
            font-size: 12px;
            color: var(--ch-text-subtle);
        }

        /* ============================================================
               DARK MODE
               ============================================================ */
        /* Variables work whether ch-dark is on <html> (feed/post pages)
               or on .ch-dashboard-wrap (admin panel — scoped to avoid
               darkening the BNTM universal container wrapper) */
        .ch-dark,
        .ch-dashboard-wrap.ch-dark {
            --ch-accent: #FF7551;
            --ch-accent-dark: #FF6640;
            --ch-accent-light: #2A1B17;
            --ch-accent-mid: #8A4B3A;
            --ch-bg: #121214;
            --ch-surface: #1A1A1E;
            --ch-border: #31323A;
            --ch-border-soft: #2A2B31;
            --ch-text: #F6F7FB;
            --ch-text-muted: #B5B8C2;
            --ch-text-subtle: #8E93A3;
            --ch-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.4);
            --ch-shadow: 0 8px 24px rgba(0, 0, 0, 0.55);
            --ch-shadow-md: 0 12px 32px rgba(0, 0, 0, 0.7);
            --ch-scroll-track: #111827;
            --ch-scroll-thumb: #4b5563;
            --ch-scroll-thumb-hover: #6b7280;
        }

        /* Admin panel: apply dark backgrounds to the wrap itself */
        .ch-dashboard-wrap.ch-dark {
            background: var(--ch-bg);
            color: var(--ch-text);
        }

        .ch-dark .ch-top-nav {
            background: rgba(18, 18, 20, 0.75);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom-color: var(--ch-border-soft);
        }

        .ch-dashboard-wrap.ch-dark .ch-top-nav {
            background: rgba(18, 18, 20, 0.75);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom-color: var(--ch-border-soft);
        }

        .ch-dark .ch-post-card:hover {
            border-color: var(--ch-accent-mid);
        }

        .ch-dashboard-wrap.ch-dark .ch-post-card:hover {
            border-color: var(--ch-accent-mid);
        }

        .ch-dark .ch-welcome-card {
            background: var(--ch-surface);
        }

        .ch-dashboard-wrap.ch-dark .ch-welcome-card {
            background: var(--ch-surface);
        }

        .ch-dark .ch-pop-cat-count {
            background: var(--ch-bg);
            border-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark .ch-pop-cat-count {
            background: var(--ch-bg);
            border-color: var(--ch-border);
        }

        .ch-dark .bntm-notice-success {
            background: #064e3b;
            color: #6ee7b7;
        }

        .ch-dark .bntm-notice-error {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .ch-dashboard-wrap.ch-dark .bntm-notice-success {
            background: #064e3b;
            color: #6ee7b7;
        }

        .ch-dashboard-wrap.ch-dark .bntm-notice-error {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .ch-dark .ch-announcement-card {
            background: linear-gradient(135deg, #1f1a08, #2a2210);
            border-color: #5c3d0a;
        }

        .ch-dark .ch-announcement-card::before {
            background: linear-gradient(90deg, #b45309, #92400e);
        }

        .ch-dashboard-wrap.ch-dark .ch-announcement-card {
            background: linear-gradient(135deg, #1f1a08, #2a2210);
            border-color: #5c3d0a;
        }

        .ch-dashboard-wrap.ch-dark .ch-announcement-card::before {
            background: linear-gradient(90deg, #b45309, #92400e);
        }

        .ch-dark .ch-announcement-label {
            color: #fbbf24;
        }

        .ch-dark .ch-announcement-author,
        .ch-dark .ch-announcement-time {
            color: #d97706;
        }

        .ch-dark .ch-announcement-icon {
            color: #f59e0b;
        }

        .ch-dark .ch-announcement-title {
            color: #fcd34d;
        }

        .ch-dark .ch-announcement-content {
            color: #e5c97a;
        }

        .ch-dashboard-wrap.ch-dark .ch-announcement-content {
            color: #e5c97a;
        }

        /* TABLE — dark mode fix: td has no background set so it bleeds white from the card */
        .ch-dark .bntm-table tr td,
        .ch-dashboard-wrap.ch-dark .bntm-table tr td {
            background: var(--ch-surface);
            color: var(--ch-text);
        }

        .ch-dark .bntm-table tr:hover td,
        .ch-dashboard-wrap.ch-dark .bntm-table tr:hover td {
            background: var(--ch-bg);
        }

        .ch-dark .bntm-table th,
        .ch-dashboard-wrap.ch-dark .bntm-table th {
            background: var(--ch-bg);
            color: var(--ch-text-subtle);
            border-bottom-color: var(--ch-border);
        }

        .ch-dark .bntm-table td,
        .ch-dashboard-wrap.ch-dark .bntm-table td {
            border-bottom-color: var(--ch-border-soft);
        }

        /* Status badges — dark mode */
        .ch-dark .ch-status-active,
        .ch-dashboard-wrap.ch-dark .ch-status-active {
            background: #064e3b;
            color: #6ee7b7;
        }

        .ch-dark .ch-status-removed,
        .ch-dashboard-wrap.ch-dark .ch-status-removed {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .ch-dark .ch-status-pending,
        .ch-dashboard-wrap.ch-dark .ch-status-pending {
            background: #451a03;
            color: #fcd34d;
        }

        .ch-dark .ch-status-suspended,
        .ch-dashboard-wrap.ch-dark .ch-status-suspended {
            background: #431407;
            color: #fdba74;
        }

        .ch-dark .ch-status-banned,
        .ch-dashboard-wrap.ch-dark .ch-status-banned {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .ch-dark .ch-status-reviewed,
        .ch-dashboard-wrap.ch-dark .ch-status-reviewed {
            background: #1e3a5f;
            color: #93c5fd;
        }

        .ch-dark .ch-status-resolved,
        .ch-dashboard-wrap.ch-dark .ch-status-resolved {
            background: #064e3b;
            color: #6ee7b7;
        }

        .ch-dark .ch-status-archived,
        .ch-dashboard-wrap.ch-dark .ch-status-archived {
            background: var(--ch-bg);
            color: var(--ch-text-muted);
        }

        .ch-dark .ch-status-dismissed,
        .ch-dashboard-wrap.ch-dark .ch-status-dismissed {
            background: var(--ch-bg);
            color: var(--ch-text-muted);
        }

        /* Announcement admin table — JS-rendered rows use hardcoded colors */
        .ch-dark #ch-ann-tbody td,
        .ch-dashboard-wrap.ch-dark #ch-ann-tbody td {
            background: var(--ch-surface);
            color: var(--ch-text);
        }

        .ch-dark #ch-ann-tbody tr:hover td,
        .ch-dashboard-wrap.ch-dark #ch-ann-tbody tr:hover td {
            background: var(--ch-bg);
        }

        .ch-dark #ch-ann-tbody [style*="color:#111827"],
        .ch-dashboard-wrap.ch-dark #ch-ann-tbody [style*="color:#111827"] {
            color: var(--ch-text) !important;
        }

        .ch-dark #ch-ann-tbody [style*="color:#9ca3af"],
        .ch-dashboard-wrap.ch-dark #ch-ann-tbody [style*="color:#9ca3af"] {
            color: var(--ch-text-subtle) !important;
        }

        /* Button variants — dark mode */
        .ch-dark .ch-btn-secondary,
        .ch-dashboard-wrap.ch-dark .ch-btn-secondary {
            background: var(--ch-bg);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-btn-secondary:hover,
        .ch-dashboard-wrap.ch-dark .ch-btn-secondary:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-dark .ch-btn-warning,
        .ch-dashboard-wrap.ch-dark .ch-btn-warning {
            background: #451a03;
            color: #fcd34d;
        }

        .ch-dark .ch-btn-warning:hover,
        .ch-dashboard-wrap.ch-dark .ch-btn-warning:hover {
            background: #78350f;
        }

        .ch-dark .ch-btn-success,
        .ch-dashboard-wrap.ch-dark .ch-btn-success {
            background: #064e3b;
            color: #6ee7b7;
        }

        .ch-dark .ch-btn-success:hover,
        .ch-dashboard-wrap.ch-dark .ch-btn-success:hover {
            background: #065f46;
        }

        /* Filter buttons — dark mode */
        .ch-dark .ch-filter-btn,
        .ch-dashboard-wrap.ch-dark .ch-filter-btn {
            background: var(--ch-surface);
            color: var(--ch-text-muted);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-filter-btn:hover,
        .ch-dashboard-wrap.ch-dark .ch-filter-btn:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
            color: var(--ch-accent);
        }

        .ch-dark .ch-filter-btn.active,
        .ch-dashboard-wrap.ch-dark .ch-filter-btn.active {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
            border-color: var(--ch-accent-mid);
        }

        /* Notification unread item — dark mode */
        .ch-dark .ch-notification-item.unread,
        .ch-dashboard-wrap.ch-dark .ch-notification-item.unread {
            background: color-mix(in srgb, var(--ch-accent) 10%, var(--ch-surface));
        }

        .ch-dark .ch-notification-item.unread:hover,
        .ch-dashboard-wrap.ch-dark .ch-notification-item.unread:hover {
            background: color-mix(in srgb, var(--ch-accent) 15%, var(--ch-surface));
        }

        .ch-dark .ch-notification-icon.type-reply,
        .ch-dashboard-wrap.ch-dark .ch-notification-icon.type-reply {
            background: #2e1065;
            color: #a78bfa;
        }

        .ch-dark .ch-notification-icon.type-mention,
        .ch-dashboard-wrap.ch-dark .ch-notification-icon.type-mention {
            background: #451a03;
            color: #fbbf24;
        }

        .ch-dark .ch-notification-icon.type-vote,
        .ch-dashboard-wrap.ch-dark .ch-notification-icon.type-vote {
            background: #052e16;
            color: #4ade80;
        }

        .ch-dark .ch-notification-icon.type-announcement,
        .ch-dashboard-wrap.ch-dark .ch-notification-icon.type-announcement {
            background: #1e3a5f;
            color: #60a5fa;
        }

        .ch-dark .ch-notification-icon.type-report_resolved,
        .ch-dashboard-wrap.ch-dark .ch-notification-icon.type-report_resolved {
            background: #4a044e;
            color: #f472b6;
        }

        /* Dropdown danger hover — dark mode */
        .ch-dark .ch-dropdown-item-danger:hover,
        .ch-dashboard-wrap.ch-dark .ch-dropdown-item-danger:hover {
            background: #3b0a0a;
            color: #f87171;
        }

        /* Stat alert card — dark mode */
        .ch-dark .ch-stat-card.ch-stat-alert,
        .ch-dashboard-wrap.ch-dark .ch-stat-card.ch-stat-alert {
            border-color: #7f1d1d;
            background: #1a0a0a;
        }

        /* ── DARK MODE: PAGE & BODY BACKGROUND ──────────────────────── */
        /* Only apply to body/html when NOT in admin panel (dashboard wrap scopes it there) */
        .ch-dark body,
        html.ch-dark body {
            background: #121214 !important;
        }

        /* ── DARK MODE: FEED PAGE ELEMENTS ──────────────────────────── */
        /* Feed search bar area */
        .ch-dark .ch-feed-header {
            background: transparent;
        }

        .ch-dark .ch-location-select {
            background: var(--ch-surface);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-location-select option {
            background: var(--ch-surface);
            color: var(--ch-text);
        }

        .ch-dark select,
        .ch-dark .ch-select-sm {
            background: var(--ch-surface);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        .ch-dark select option {
            background: var(--ch-surface);
            color: var(--ch-text);
        }

        .ch-dashboard-wrap.ch-dark select,
        .ch-dashboard-wrap.ch-dark .ch-select-sm {
            background: var(--ch-surface);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark select option {
            background: var(--ch-surface);
            color: var(--ch-text);
        }

        /* Sidebar widgets on feed */
        .ch-dark .ch-sidebar-widget,
        .ch-dashboard-wrap.ch-dark .ch-sidebar-widget {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        /* ── DARK MODE: POST CARDS ───────────────────────────────────── */
        .ch-dark .ch-vote-btn.active-down {
            background: #3b0a0a;
            border-color: #7f1d1d;
            color: #f87171;
        }

        .ch-dark .ch-vote-btn-lg.active-up {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent);
            color: var(--ch-accent);
        }

        .ch-dark .ch-vote-btn-lg.active-down {
            background: #3b0a0a;
            border-color: #7f1d1d;
            color: #f87171;
        }

        .ch-dark .ch-vote-btn-lg.ch-vote-down:hover {
            background: #3b0a0a;
            border-color: #7f1d1d;
            color: #f87171;
        }

        .ch-dark .ch-vote-btn-lg.ch-danger:hover {
            background: #3b0a0a;
            border-color: #ef4444;
        }

        .ch-dark .ch-icon-btn-danger:hover {
            background: #3b0a0a;
            border-color: #7f1d1d;
            color: #f87171;
        }

        /* ── DARK MODE: NOTICES ──────────────────────────────────────── */
        .ch-dark .bntm-notice-warning,
        .ch-dashboard-wrap.ch-dark .bntm-notice-warning {
            background: #451a03;
            color: #fcd34d;
            border-left-color: #f59e0b;
        }

        /* ── DARK MODE: PROFILE (MY FEED) ───────────────────────────── */
        .ch-dark .ch-mf-profile-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-mf-stat {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-mf-subnav {
            background: var(--ch-bg);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-mf-subnav-item.active {
            background: var(--ch-surface);
        }

        .ch-dark .ch-mf-post-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-mf-post-card:hover {
            border-color: var(--ch-accent-mid);
        }

        /* Profile avatar image border */
        .ch-dark .ch-mf-avatar-img {
            border-color: var(--ch-border);
        }

        /* ── DARK MODE: PROFILE EDIT FORM (inline style overrides) ─── */
        .ch-dark .ch-profile-page-wrap {
            background: var(--ch-bg);
            color: var(--ch-text);
        }

        .ch-dark .ch-profile-page-wrap .ch-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-profile-page-wrap .ch-card-body {
            background: var(--ch-surface);
        }

        .ch-dark .ch-stat-item,
        .ch-dashboard-wrap.ch-dark .ch-stat-item {
            background: var(--ch-bg);
            border-color: var(--ch-border);
            color: var(--ch-text);
        }

        /* ── DARK MODE: MODAL ────────────────────────────────────────── */
        .ch-dark .ch-modal,
        .ch-dashboard-wrap.ch-dark .ch-modal {
            background: var(--ch-surface);
        }

        .ch-dark .ch-modal-header,
        .ch-dashboard-wrap.ch-dark .ch-modal-header {
            border-bottom-color: var(--ch-border-soft);
        }

        .ch-dark .ch-modal-footer,
        .ch-dashboard-wrap.ch-dark .ch-modal-footer {
            border-top-color: var(--ch-border-soft);
        }

        /* ── DARK MODE: GUEST LANDING / AUTH ─────────────────────────── */
        .ch-dark .ch-auth-wrap {
            background: var(--ch-bg);
        }

        .ch-dark .ch-auth-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-auth-tab {
            color: var(--ch-text-muted);
        }

        .ch-dark .ch-auth-tab.active {
            color: var(--ch-accent);
            border-bottom-color: var(--ch-accent);
        }

        /* ── DARK MODE: POPULAR CATEGORY CHIPS ──────────────────────── */
        .ch-dark .ch-pop-cat-chip {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-pop-cat-chip:hover {
            background: var(--ch-accent-light);
            border-color: var(--ch-accent-mid);
        }

        .ch-dark .ch-pop-cat-name {
            color: var(--ch-text);
        }

        /* ── DARK MODE: TOP NAV ELEMENTS ─────────────────────────────── */
        .ch-dark .ch-nav-link,
        .ch-dashboard-wrap.ch-dark .ch-nav-link {
            color: var(--ch-text-muted);
        }

        .ch-dark .ch-icon-action-btn,
        .ch-dashboard-wrap.ch-dark .ch-icon-action-btn {
            background: var(--ch-surface);
            border-color: var(--ch-border);
            color: var(--ch-text-muted);
        }

        /* Notification badge border matches dark nav */
        .ch-dark .ch-notification-badge,
        .ch-dashboard-wrap.ch-dark .ch-notification-badge {
            border-color: var(--ch-surface);
        }

        /* ── DARK MODE: ADMIN SIDEBAR & MAIN CONTENT ─────────────────── */
        .ch-dashboard-wrap.ch-dark .ch-sidebar {
            background: var(--ch-surface);
            border-right-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark .ch-nav-item {
            color: var(--ch-text-muted);
        }

        .ch-dashboard-wrap.ch-dark .ch-nav-item:hover {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-dashboard-wrap.ch-dark .ch-nav-item.active {
            background: var(--ch-accent-light);
            color: var(--ch-accent);
        }

        .ch-dashboard-wrap.ch-dark .ch-main-content {
            background: linear-gradient(180deg, color-mix(in srgb, var(--ch-bg) 94%, var(--ch-accent-light) 6%) 0%, var(--ch-bg) 180px);
        }

        .ch-dashboard-wrap.ch-dark .ch-page-header {
            border-bottom-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark .ch-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark .ch-stat-card {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark .ch-stat-num {
            color: var(--ch-text);
        }

        .ch-dashboard-wrap.ch-dark .ch-stat-label {
            color: var(--ch-text-muted);
        }

        .ch-dashboard-wrap.ch-dark .ch-section-title {
            color: var(--ch-text);
        }

        .ch-dashboard-wrap.ch-dark input,
        .ch-dashboard-wrap.ch-dark textarea {
            background: var(--ch-bg);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        .ch-dashboard-wrap.ch-dark .ch-input {
            background: var(--ch-bg);
            color: var(--ch-text);
            border-color: var(--ch-border);
        }

        /* ============================================================
               COLOR PICKER — wheel + hex input + swatches
               ============================================================ */
        .ch-color-picker-wrap {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ch-color-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ch-color-wheel {
            width: 38px;
            height: 38px;
            border-radius: var(--ch-radius);
            border: 1px solid var(--ch-border);
            cursor: pointer;
            padding: 2px;
            background: var(--ch-surface);
            flex-shrink: 0;
            appearance: none;
            -webkit-appearance: none;
            overflow: hidden;
        }

        .ch-color-wheel::-webkit-color-swatch-wrapper {
            padding: 0;
            border-radius: 6px;
        }

        .ch-color-wheel::-webkit-color-swatch {
            border: none;
            border-radius: 6px;
        }

        .ch-color-wheel::-moz-color-swatch {
            border: none;
            border-radius: 6px;
        }

        .ch-color-hex-preview {
            width: 36px;
            height: 36px;
            border-radius: var(--ch-radius);
            border: 1px solid var(--ch-border);
            flex-shrink: 0;
            transition: background 0.12s, opacity 0.12s;
            display: block;
        }

        .ch-color-hex-input {
            flex: 1;
            font-family: monospace;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .ch-color-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .ch-swatch {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: transform 0.12s, box-shadow 0.12s;
            padding: 0;
        }

        .ch-swatch:hover {
            transform: scale(1.18);

        }

        .ch-dark .ch-color-wheel {
            background: var(--ch-surface);
            border-color: var(--ch-border);
        }

        .ch-dark .ch-color-hex-preview {
            border-color: var(--ch-border);
        }

        .ch-dark .ch-swatch:hover {
            box-shadow: 0 0 0 2px var(--ch-bg), 0 0 0 4px var(--ch-accent);
        }

        /* ============================================================
               BUTTON LOADING STATES & SPINNER
               ============================================================ */
        @keyframes ch-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .ch-btn-spinner {
            animation: ch-spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        .ch-btn-loading {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            pointer-events: none;
            opacity: 0.82;
        }

        .ch-btn:disabled {
            cursor: not-allowed;
            opacity: 0.72;
        }

        .ch-icon-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* ============================================================
               NAV ITEM ACTIVE TRANSITION (tab switching feel)
               ============================================================ */
        .ch-nav-item {
            transition: background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
        }

        .ch-nav-link {
            transition: background 0.12s ease, color 0.12s ease;
        }

        /* Subtle click feedback on nav items */
        .ch-nav-item:active,
        .ch-nav-link:active {
            opacity: 0.7;
            transform: scale(0.98);
        }

        /* ============================================================
               MODAL OPEN / CLOSE ANIMATIONS
               ============================================================ */
        .ch-modal-overlay {
            animation: ch-overlay-in 0.18s ease;
        }

        @keyframes ch-overlay-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .ch-modal {
            animation: ch-modal-in 0.2s cubic-bezier(0.34, 1.3, 0.64, 1);
        }

        @keyframes ch-modal-in {
            from {
                opacity: 0;
                transform: scale(0.94) translateY(8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Mobile feed sidebar: stack it in-layout instead of hiding it */
        @media (max-width: 780px) {

            .ch-feed-sidebar {
                display: block !important;
                width: 100%;
                order: 2;
            }

            .ch-guest-sidebar {
                display: none !important;
            }

            .ch-input {
                width: 100% !important;
                box-sizing: border-box !important;
            }
        }


        /* ── MOBILE DRAWER (.ch-mobile-drawer-wrap) ── */
        @media (max-width: 780px) {
            body.ch-drawer-locked {
                overflow: hidden;
                /* Prevent iOS momentum-scroll bleed through */
                position: fixed;
                width: 100%;
            }

            .ch-mobile-drawer-wrap {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                width: 300px;
                max-width: 88vw;
                background: var(--ch-surface);
                z-index: 99999;
                transform: translateX(110%); /* slightly beyond edge avoids 1px flash on Android */
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex !important;
                flex-direction: column;
                /* Clear the close button at top, respect notch at bottom */
                padding-top: 64px;
                padding-left: 20px;
                padding-right: 20px;
                padding-bottom: max(24px, env(safe-area-inset-bottom));
                box-shadow: -12px 0 48px rgba(0, 0, 0, 0.12);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain;
            }

            .ch-dark .ch-mobile-drawer-wrap {
                box-shadow: -12px 0 48px rgba(0, 0, 0, 0.65);
                border-left: 1px solid var(--ch-border-soft);
            }

            .ch-mobile-drawer-wrap.ch-nav-open {
                transform: translateX(0);
            }

            /* Nav links inside the drawer */
            .ch-mobile-drawer-wrap .ch-nav-links,
            .ch-mobile-drawer-wrap .ch-user-bar {
                position: static !important;
                display: flex !important;
                flex-direction: column;
                width: 100% !important;
                background: transparent !important;
                box-shadow: none !important;
                border-bottom: none !important;
                padding: 0 !important;
                gap: 4px;
                margin-top: 0;
                height: auto !important;
            }

            .ch-mobile-drawer-wrap .ch-nav-link {
                width: 100%;
                height: auto !important;
                min-height: 50px;
                justify-content: flex-start;
                font-size: 15.5px;
                font-weight: 500;
                padding: 13px 14px;
                border-radius: var(--ch-radius);
                gap: 12px;
            }

            .ch-mobile-drawer-wrap .ch-nav-label {
                display: inline !important;
            }

            .ch-mobile-drawer-wrap .ch-nav-link svg {
                width: 20px;
                height: 20px;
                flex-shrink: 0;
            }

            .ch-mobile-drawer-wrap .ch-nav-link.active {
                background: var(--ch-accent-light);
                color: var(--ch-accent);
                font-weight: 600;
            }

            .ch-mobile-drawer-wrap .ch-nav-link.active::after {
                display: none !important;
            }

            /* Guest auth buttons row inside drawer */
            .ch-mobile-drawer-wrap .ch-user-bar {
                flex-direction: row !important;
                flex-wrap: wrap;
                align-items: center;
                justify-content: stretch;
                gap: 12px;
                margin-top: auto;
                padding-top: 24px !important;
                border-top: 1px solid var(--ch-border-soft) !important;
                border-radius: 0;
            }

            /* Suppress separator inside drawer */
            .ch-mobile-drawer-wrap .ch-user-bar::before {
                display: none !important;
            }

            .ch-mobile-drawer-wrap .ch-user-bar .ch-btn {
                width: 100%;
                flex: 1;
                min-width: 110px;
                min-height: 48px;
                justify-content: center;
                font-size: 14px;
            }

            .ch-mobile-drawer-wrap .ch-user-bar-mobile-profile {
                justify-content: flex-start;
                align-items: stretch;
            }

            .ch-mobile-drawer-wrap .ch-mobile-profile-trigger {
                width: 100%;
                min-height: 52px;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 0;
                background: transparent;
                border: 0;
                color: var(--ch-text);
                cursor: pointer;
                text-align: left;
            }

            .ch-mobile-drawer-wrap .ch-mobile-profile-label {
                font-size: 15px;
                font-weight: 600;
            }

            .ch-mobile-drawer-wrap .ch-mobile-profile-menu {
                display: none;
                width: 100%;
                margin-top: 10px;
                border-top: 1px solid var(--ch-border-soft);
                padding-top: 10px;
            }

            .ch-mobile-drawer-wrap .ch-mobile-profile-menu .ch-dropdown-item {
                width: 100%;
                border-radius: var(--ch-radius);
            }

            /* Drawer close button — top-right, mirrors burger position */
            .ch-top-drawer-close {
                position: absolute;
                top: 14px;
                right: 16px;
                left: auto;
                width: 38px;
                height: 38px;
                background: var(--ch-bg);
                border: 1px solid var(--ch-border);
                border-radius: var(--ch-radius-sm);
                font-size: 22px;
                line-height: 1;
                color: var(--ch-text-muted);
                cursor: pointer;
                padding: 0;
                display: flex !important;
                align-items: center;
                justify-content: center;
                transition: color 0.15s, background 0.15s;
                z-index: 1;
            }

            .ch-top-drawer-close:hover {
                color: var(--ch-text);
                background: var(--ch-surface);
            }

            /* Backdrop */
            .ch-menu-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.38);
                z-index: 99998;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
                backdrop-filter: blur(3px);
                -webkit-backdrop-filter: blur(3px);
            }

            .ch-menu-backdrop.ch-backdrop-visible {
                opacity: 1;
                pointer-events: auto;
            }
        }

        @media (min-width: 781px) {
            .ch-mobile-drawer-wrap {
                display: contents;
            }

            .ch-top-drawer-close {
                display: none !important;
            }
        }

        /* ── GLOBAL UNIFORM MODERNIZATION (Derived from feed search bar) ── */
        .ch-input,
        .ch-select-sm,
        .ch-composer-cat-select,
        .ch-color-input,
        .ch-composer-title,
        .ch-composer-guest-name,
        .ch-composer-tags-input {
            border-radius: 999px !important;
            padding: 12px 20px !important;
            font-size: 14px !important;
            background: color-mix(in srgb, var(--ch-surface) 40%, var(--ch-bg) 60%) !important;
            border: 1px solid var(--ch-border) !important;
            color: var(--ch-text) !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02) !important;
            box-sizing: border-box !important;
            max-width: 100%;
        }

        .ch-input:focus,
        .ch-select-sm:focus,
        .ch-composer-cat-select:focus,
        .ch-color-input:focus,
        .ch-composer-title:focus,
        .ch-composer-guest-name:focus,
        .ch-composer-tags-input:focus {
            background: var(--ch-surface) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06), 0 0 0 3px rgba(255, 117, 81, 0.15) !important;
            border-color: var(--ch-accent) !important;
            transform: translateY(-1px) !important;
            outline: none !important;
        }

        /* Textarea uniform protection (Glassy but rectangular) */
        .ch-textarea,
        .ch-composer-textarea,
        .ch-reason-textarea,
        textarea.ch-input {
            border-radius: var(--ch-radius-lg) !important;
            padding: 16px 20px !important;
            resize: vertical;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            flex: 1 !important;
            min-height: 120px;
        }

        /* Remove ugly native browser up/down spin buttons on number inputs */
        /* WebKit/Blink browsers */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }

        /* Firefox */
        input[type="number"] {
            -moz-appearance: textfield !important;
        }

        /* File inputs (Avatar upload text and button) */
        input[type="file"] {
            color: var(--ch-text) !important;
        }

        input[type="file"]::file-selector-button {
            color: var(--ch-text);
            background: color-mix(in srgb, var(--ch-surface) 60%, var(--ch-bg) 40%);
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius-md);
            padding: 8px 14px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            margin-right: 12px;
            font-family: var(--ch-font);
            font-weight: 500;
        }

        input[type="file"]::file-selector-button:hover {
            background: var(--ch-surface);
            border-color: var(--ch-accent);
        }

        /* Ensure parents of textareas let them flex correctly */
        .ch-field-group,
        .ch-comment-form,
        .ch-reply-form {
            flex: 1;
            width: 100%;
        }

        /* Nuanced restores */
        .ch-color-input {
            width: auto;
            padding: 4px 12px !important;
        }

        .ch-composer-title {
            font-size: 18px !important;
            font-weight: 700 !important;
        }

        .ch-composer-tags-input {
            border-radius: 999px !important;
        }

        .ch-composer-tags-row {
            padding: 4px;
            background: transparent;
            border: none;
        }

        .ch-composer-tags-row svg {
            margin-left: 12px;
        }

        .ch-composer-author-info {
            gap: 12px;
        }

        /* Form expansion specifically for desktop feed search to make it prominent */
        .ch-search-form {
            flex: 1;
            min-width: 280px;
        }

        .ch-search-form .ch-search-input {
            width: 100%;
            flex: 1;
        }

        /* ── MOBILE LAYOUT REFINEMENT FOR UNIFORM INPUTS ── */
        @media (max-width: 768px) {

            /* Put search field and button on the same line to save vertical space */
            .ch-search-form {
                flex-direction: row !important;
                flex-wrap: nowrap !important;
            }

            .ch-search-form .ch-search-input {
                min-width: 0 !important;
                flex: 1 !important;
            }

            .ch-category-browser-grid {
                grid-template-columns: 1fr;
            }

            .ch-category-browser-list {
                flex-direction: column;
            }

            .ch-view-all-btn {
                width: 100%;
                min-height: 48px; /* Larger tap target for mobile */
                margin-top: 12px;
            }

            .ch-category-modal-overlay {
                padding: 0;
                align-items: flex-end;
            }

            .ch-modal.ch-modal-category-browser {
                width: 100vw;
                max-width: none;
                height: min(92dvh, 100dvh);
                max-height: 100dvh;
                border-radius: 16px 16px 0 0;
                position: relative;
                margin: 0;
                animation: ch-category-modal-pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.1) forwards;
            }

            .ch-category-modal-header {
                position: sticky;
                top: 0;
                z-index: 10;
                padding: 16px 20px;
            }

            .ch-category-modal-header .ch-modal-close {
                width: 44px;
                height: 44px;
                font-size: 28px;
            }

            .ch-category-modal-body {
                padding: 16px 20px 20px;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .ch-category-browser-list .ch-cat-link {
                min-height: 48px; /* Bigger touch targets */
                padding: 12px 14px;
            }

            .ch-modal.ch-modal-category-browser .ch-category-browser-list {
                max-height: none;
                overflow: visible;
                padding-right: 0;
            }

            .ch-category-modal-footer {
                position: sticky;
                bottom: 0;
                z-index: 10;
                padding: 14px 20px calc(14px + env(safe-area-inset-bottom));
                justify-content: stretch;
            }
            .ch-category-modal-footer .ch-category-modal-close-btn {
                width: 100%;
                min-height: 48px;
            }

            .ch-category-modal-close-btn {
                width: 100%;
                min-height: 44px;
            }

            .ch-search-form button {
                width: auto !important;
                padding: 12px 20px !important;
                border-radius: 999px !important;
            }

            /* Arrange filter row intelligently */
            .ch-filter-row {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 12px 8px !important;
                width: 100%;
            }

            /* Make dropdown full width for easier tapping */
            .ch-location-form {
                flex: 1 1 100% !important;
                margin: 0 !important;
            }

            .ch-location-form .ch-select-sm {
                width: 100% !important;
                max-width: none !important;
            }

            /* Let tabs and guidelines sit side-by-side on the next row */
            .ch-sort-tabs {
                flex: 1 !important;
                justify-content: flex-start !important;
                gap: 8px !important;
                width: auto !important;
                margin-bottom: 0 !important;
            }

            .ch-sort-tab {
                flex: 0 1 auto !important;
                padding: 8px 14px !important;
                border-radius: 999px !important;
            }

            .ch-guidelines-link {
                margin: 0 !important;
                padding: 8px 0 !important;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}


// ============================================================
// GLOBAL SCRIPTS (shared modals & utilities)
// ============================================================

function ch_global_scripts()
{
    ob_start(); ?>
    <script>
        (function () {
            if (window.__chGlobalScriptsInitialized) return;
            window.__chGlobalScriptsInitialized = true;

            window.chAjaxUrl = window.ajaxurl || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            window.chMediaUploadLimit = <?php echo (int) get_option('ch_media_upload_limit', 6); ?>;
            const chAjaxUrl = window.chAjaxUrl;
            window.ajaxurl = chAjaxUrl;

            function chGetDarkModeTarget() {
                return document.querySelector('.ch-dashboard-wrap') || document.documentElement;
            }

            function chIsDarkModeEnabled() {
                return chGetDarkModeTarget().classList.contains('ch-dark');
            }
            window.chIsDarkModeEnabled = chIsDarkModeEnabled;

            function chApplyDarkMode(enabled) {
                const target = chGetDarkModeTarget();
                const isDashboardScoped = target !== document.documentElement;

                target.classList.toggle('ch-dark', !!enabled);
                if (isDashboardScoped) {
                    document.documentElement.classList.remove('ch-dark');
                    if (document.body) {
                        document.body.classList.remove('ch-dark');
                    }
                }
            }
            window.chToggleDarkMode = function (enabled) {
                chApplyDarkMode(enabled);
                try {
                    if (enabled) localStorage.setItem('ch_dark_mode', '1');
                    else localStorage.removeItem('ch_dark_mode');
                } catch (e) { }
            };

            function chCloseAllMobileMenus() {
                document.querySelectorAll('.ch-nav-links.ch-nav-open, .ch-user-bar.ch-nav-open, .ch-nav.ch-nav-open').forEach((el) => {
                    el.classList.remove('ch-nav-open');
                });
                document.querySelectorAll('.ch-mobile-drawer-wrap.ch-nav-open').forEach((el) => {
                    el.classList.remove('ch-nav-open');
                });
                const mobileProfileMenu = document.getElementById('ch-mobile-profile-menu');
                if (mobileProfileMenu) mobileProfileMenu.style.display = 'none';
                const backdrop = document.getElementById('ch-menu-backdrop');
                if (backdrop) backdrop.classList.remove('ch-backdrop-visible');
                document.body.classList.remove('ch-drawer-locked');
                document.querySelectorAll('.ch-burger-menu-btn[aria-expanded="true"]').forEach((btn) => {
                    btn.setAttribute('aria-expanded', 'false');
                });
            }
            window.chCloseAllMobileMenus = chCloseAllMobileMenus;

            window.chCloseMobileProfileMenu = function () {
                const menu = document.getElementById('ch-mobile-profile-menu');
                if (menu) menu.style.display = 'none';
            };

            window.chToggleMobileProfileMenu = function (event) {
                if (event) event.stopPropagation();
                const menu = document.getElementById('ch-mobile-profile-menu');
                if (!menu) return;
                const isVisible = menu.style.display === 'block';
                menu.style.display = isVisible ? 'none' : 'block';
            };

            window.chToggleMobileMenu = function (button, primarySelector, secondarySelector) {
                if (!button || !primarySelector) return;
                const scope = button.closest('.ch-top-nav, .ch-sidebar, .ch-dashboard-wrap') || document;
                const primary = scope.querySelector(primarySelector) || document.querySelector(primarySelector);
                const secondary = secondarySelector ? (scope.querySelector(secondarySelector) || document.querySelector(secondarySelector)) : null;
                if (!primary) return;

                // Keep the mobile drawer outside sticky/backdrop-filter nav stacking contexts
                // so it can render above the body-level backdrop.
                if (primary.classList.contains('ch-mobile-drawer-wrap') && primary.parentElement !== document.body) {
                    document.body.appendChild(primary);
                }

                const shouldOpen = !primary.classList.contains('ch-nav-open');
                chCloseAllMobileMenus();

                primary.classList.toggle('ch-nav-open', shouldOpen);

                // Drawer Backdrop Logic
                if (primary.classList.contains('ch-mobile-drawer-wrap')) {
                    let backdrop = document.getElementById('ch-menu-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.id = 'ch-menu-backdrop';
                        backdrop.className = 'ch-menu-backdrop';
                        document.body.appendChild(backdrop);
                        backdrop.addEventListener('click', chCloseAllMobileMenus);
                    }

                    if (shouldOpen) {
                        requestAnimationFrame(() => backdrop.classList.add('ch-backdrop-visible'));
                        document.body.classList.add('ch-drawer-locked');
                    } else {
                        backdrop.classList.remove('ch-backdrop-visible');
                        document.body.classList.remove('ch-drawer-locked');
                    }
                }
                if (secondary) {
                    secondary.classList.toggle('ch-nav-open', shouldOpen);
                }
                button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            };

            function chEscapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function chSetNotice(targetId, type, message) {
                const target = document.getElementById(targetId);
                if (!target) return;
                target.innerHTML = '<div class="bntm-notice bntm-notice-' + type + '">' + chEscapeHtml(message) + '</div>';
            }
            window.chSetNotice = chSetNotice;

            function chResetButton(btn, label) {
                if (!btn) return;
                btn.disabled = false;
                btn.textContent = label;
            }
            window.chResetButton = chResetButton;

            async function chFetchJson(endpoint, options) {
                const response = await fetch(endpoint, options);
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    throw new Error(text || ('HTTP ' + response.status));
                }
            }
            window.chFetchJson = chFetchJson;

            async function chFetchDocument(url) {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const text = await response.text();
                if (!response.ok) {
                    throw new Error(text || ('HTTP ' + response.status));
                }
                return new DOMParser().parseFromString(text, 'text/html');
            }

            function chRunInlineScripts(root) {
                if (!root) return;
                root.querySelectorAll('script').forEach(function (script) {
                    var next = document.createElement('script');
                    Array.from(script.attributes || []).forEach(function (attr) {
                        next.setAttribute(attr.name, attr.value);
                    });
                    next.textContent = script.textContent;
                    document.body.appendChild(next);
                    next.remove();
                });
            }

            function chCanSoftNavigate(link) {
                if (!link || !link.href) return false;
                if (link.target && link.target !== '_self') return false;
                if (link.hasAttribute('download')) return false;
                try {
                    var url = new URL(link.href, window.location.href);
                    return url.origin === window.location.origin;
                } catch (err) {
                    return false;
                }
            }

            window.chFetchDocument = chFetchDocument;
            window.chRunInlineScripts = chRunInlineScripts;
            window.chCanSoftNavigate = chCanSoftNavigate;

            function chUpdateCurrentUserAvatars(avatarUrl) {
                if (!avatarUrl) return;
                const freshUrl = avatarUrl + '?t=' + Date.now();

                document.querySelectorAll('[data-ch-current-user-avatar="1"]').forEach(el => {
                    const name = el.dataset.avatarName || 'U';
                    const innerClass = el.classList.contains('ch-avatar-btn-lg') ? 'ch-avatar-btn-inner ch-avatar-btn-inner-lg' : 'ch-avatar-btn-inner';
                    el.innerHTML = '<div class="' + innerClass + '"><img src="' + freshUrl + '" alt="' + chEscapeHtml(name) + '" class="ch-avatar-img ch-current-user-avatar-img"></div>';
                });

                document.querySelectorAll('.ch-mf-avatar-img, #ch-avatar-preview-img').forEach(el => {
                    el.src = freshUrl;
                });
            }

            // Dark mode persistence — apply on every page load
            try {
                chApplyDarkMode(localStorage.getItem('ch_dark_mode') === '1');
            } catch (e) { }

            document.addEventListener('click', function (event) {
                if (event.target.closest('.ch-burger-menu-btn')) return;
                if (event.target.closest('.ch-mobile-profile-trigger, #ch-mobile-profile-menu')) return;
                if (event.target.closest('.ch-mobile-drawer-wrap.ch-nav-open, .ch-nav-links.ch-nav-open, .ch-user-bar.ch-nav-open, .ch-nav.ch-nav-open')) return;
                chCloseAllMobileMenus();
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth > 780) {
                    chCloseAllMobileMenus();
                }
            });

            // ---- Composer media label updater ----
            window.chUpdateMediaLabel = function (input, labelId) {
                const label = document.getElementById(labelId);
                if (!label) return;
                const inner = label.querySelector('.ch-composer-media-inner');
                if (!inner) return;
                const count = input.files.length;
                const limit = parseInt(window.chMediaUploadLimit || 6, 10);
                if (count === 0) {
                    inner.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>Add photos / videos</span><span class="ch-composer-media-sub">or drag and drop</span>';
                } else {
                    inner.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><span>' + count + ' of ' + limit + ' files</span><span class="ch-composer-media-sub">Click to change</span>';
                }
            };
            window.chComposerFiles = window.chComposerFiles || {};
            window.chComposerExistingMedia = window.chComposerExistingMedia || {};

            window.chGetFirstExistingId = function (ids) {
                for (const id of ids) {
                    if (document.getElementById(id)) return id;
                }
                return ids[0] || '';
            };

            window.chSyncComposerInput = function (inputId, files) {
                const input = document.getElementById(inputId);
                if (!input) return;
                const dt = new DataTransfer();
                files.forEach(file => dt.items.add(file));
                input.files = dt.files;
            };

            window.chRenderComposerMediaPreview = function (inputId, previewId, labelId) {
                const preview = document.getElementById(previewId);
                if (!preview) return;
                const files = window.chComposerFiles[inputId] || [];
                const existingMedia = window.chComposerExistingMedia[inputId] || [];

                const existingMarkup = existingMedia.map((item, index) => {
                    const url = item && item.url ? item.url : '';
                    const type = item && item.type ? item.type : 'file';
                    if (!url) return '';
                    if (type === 'image') {
                        return '<div class="ch-composer-media-chip"><img src="' + url + '" alt="Existing image"><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-existing-index="' + index + '" aria-label="Remove image">&times;</button></div>';
                    }
                    if (type === 'video') {
                        return '<div class="ch-composer-media-chip"><video src="' + url + '" muted playsinline></video><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-existing-index="' + index + '" aria-label="Remove video">&times;</button></div>';
                    }
                    if (type === 'audio') {
                        return '<div class="ch-composer-media-chip"><div class="ch-composer-media-chip-audio"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg><div class="ch-composer-media-chip-audio-name">Existing audio</div></div><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-existing-index="' + index + '" aria-label="Remove audio">&times;</button></div>';
                    }
                    return '<div class="ch-composer-media-chip"><div class="ch-composer-media-chip-audio"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg><div class="ch-composer-media-chip-audio-name">Existing file</div></div><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-existing-index="' + index + '" aria-label="Remove file">&times;</button></div>';
                }).join('');

                const fileMarkup = files.map((file, index) => {
                    const objectUrl = URL.createObjectURL(file);
                    const escapedName = (file.name || 'Audio file').replace(/"/g, '&quot;');
                    if ((file.type || '').startsWith('image/')) {
                        return '<div class="ch-composer-media-chip"><img src="' + objectUrl + '" alt="' + escapedName + '"><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-index="' + index + '" aria-label="Remove image">&times;</button></div>';
                    }
                    if ((file.type || '').startsWith('video/')) {
                        return '<div class="ch-composer-media-chip"><video src="' + objectUrl + '" muted playsinline></video><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-index="' + index + '" aria-label="Remove video">&times;</button></div>';
                    }
                    return '<div class="ch-composer-media-chip"><div class="ch-composer-media-chip-audio"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg><div class="ch-composer-media-chip-audio-name">' + escapedName + '</div></div><button type="button" class="ch-composer-media-remove" data-input-id="' + inputId + '" data-preview-id="' + previewId + '" data-label-id="' + labelId + '" data-index="' + index + '" aria-label="Remove audio">&times;</button></div>';
                }).join('');

                preview.innerHTML = existingMarkup + fileMarkup;
                if (labelId) {
                    const label = document.getElementById(labelId);
                    const inner = label ? label.querySelector('.ch-composer-media-inner') : null;
                    const totalCount = existingMedia.length + files.length;
                    if (label) {
                        label.classList.toggle('has-media', totalCount > 0);
                    }
                    const limit = parseInt(window.chMediaUploadLimit || 6, 10);
                    if (inner) {
                        if (totalCount === 0) {
                            inner.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>Add photos / videos</span><span class="ch-composer-media-sub">or drag and drop</span>';
                        } else {
                            inner.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><span>' + totalCount + ' of ' + limit + ' files</span><span class="ch-composer-media-sub">Click to change</span>';
                        }
                    }
                }
            };

            window.chHandleComposerMediaChange = function (input, labelId, previewId) {
                if (!input) return;
                const existingCount = (window.chComposerExistingMedia[input.id] || []).length;
                const limit = parseInt(window.chMediaUploadLimit || 6, 10);
                let selectedFiles = Array.from(input.files || []);
                if (selectedFiles.length + existingCount > limit) {
                    selectedFiles = selectedFiles.slice(0, Math.max(0, limit - existingCount));
                    window.chOpenWarningModal({
                        title: 'Upload Limit Reached',
                        message: 'You can upload up to ' + limit + ' files per post. Extra files were not added.',
                        buttonLabel: 'Got it'
                    });
                }
                window.chComposerFiles[input.id] = selectedFiles;
                window.chSyncComposerInput(input.id, window.chComposerFiles[input.id]);
                window.chUpdateMediaLabel(input, labelId);
                window.chRenderComposerMediaPreview(input.id, previewId, labelId);
            };

            window.chRemoveComposerMedia = function (inputId, previewId, labelId, index) {
                const files = window.chComposerFiles[inputId] || [];
                window.chComposerFiles[inputId] = files.filter((_, fileIndex) => fileIndex !== index);
                window.chSyncComposerInput(inputId, window.chComposerFiles[inputId]);
                const input = document.getElementById(inputId);
                if (input) {
                    window.chUpdateMediaLabel(input, labelId);
                }
                window.chRenderComposerMediaPreview(inputId, previewId, labelId);
            };

            window.chRemoveComposerExistingMedia = function (inputId, previewId, labelId, index) {
                const files = window.chComposerExistingMedia[inputId] || [];
                window.chComposerExistingMedia[inputId] = files.filter((_, fileIndex) => fileIndex !== index);
                window.chRenderComposerMediaPreview(inputId, previewId, labelId);
            };

            window.chOpenConfirmModal = function (options) {
                options = options || {};
                return new Promise((resolve) => {
                    let overlay = document.getElementById('ch-confirm-modal');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.id = 'ch-confirm-modal';
                        overlay.className = 'ch-modal-overlay';
                        overlay.style.display = 'none';
                        overlay.innerHTML = `
                        <div class="ch-modal ch-confirm-modal">
                            <div class="ch-modal-header">
                                <h3 id="ch-confirm-title">Confirm Action</h3>
                                <button type="button" class="ch-modal-close" id="ch-confirm-close" aria-label="Close">&times;</button>
                            </div>
                            <div class="ch-modal-body">
                                <p id="ch-confirm-message" class="ch-confirm-copy"></p>
                                <div class="ch-confirm-actions">
                                    <button type="button" class="ch-btn ch-btn-secondary" id="ch-confirm-cancel">Cancel</button>
                                    <button type="button" class="ch-btn ch-btn-danger" id="ch-confirm-accept">Confirm</button>
                                </div>
                            </div>
                        </div>`;
                        document.body.appendChild(overlay);
                    }

                    const titleEl = document.getElementById('ch-confirm-title');
                    const messageEl = document.getElementById('ch-confirm-message');
                    const acceptBtn = document.getElementById('ch-confirm-accept');
                    const cancelBtn = document.getElementById('ch-confirm-cancel');
                    const closeBtn = document.getElementById('ch-confirm-close');
                    let settled = false;

                    const finish = (value) => {
                        if (settled) return;
                        settled = true;
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                        overlay.removeEventListener('click', onOverlayClick);
                        acceptBtn.removeEventListener('click', onAccept);
                        cancelBtn.removeEventListener('click', onCancel);
                        closeBtn.removeEventListener('click', onCancel);
                        resolve(value);
                    };

                    const onAccept = () => finish(true);
                    const onCancel = () => finish(false);
                    const onOverlayClick = (event) => {
                        if (event.target === overlay) finish(false);
                    };

                    titleEl.textContent = options.title || 'Confirm Action';
                    messageEl.textContent = options.message || 'Are you sure you want to continue?';
                    acceptBtn.textContent = options.confirmLabel || 'Confirm';
                    cancelBtn.textContent = options.cancelLabel || 'Cancel';
                    acceptBtn.className = 'ch-btn ' + (options.confirmClass || 'ch-btn-danger');

                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    overlay.addEventListener('click', onOverlayClick);
                    acceptBtn.addEventListener('click', onAccept);
                    cancelBtn.addEventListener('click', onCancel);
                    closeBtn.addEventListener('click', onCancel);
                });
            };

            window.chOpenWarningModal = function (options) {
                options = options || {};
                return new Promise((resolve) => {
                    let overlay = document.getElementById('ch-warning-modal');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.id = 'ch-warning-modal';
                        overlay.className = 'ch-modal-overlay';
                        overlay.style.display = 'none';
                        overlay.innerHTML = `
                        <div class="ch-modal ch-warning-modal">
                            <div class="ch-modal-header">
                                <h3 id="ch-warning-title">Upload Limit Reached</h3>
                                <button type="button" class="ch-modal-close" id="ch-warning-close" aria-label="Close">&times;</button>
                            </div>
                            <div class="ch-modal-body">
                                <p id="ch-warning-message" class="ch-warning-copy"></p>
                                <div class="ch-warning-actions">
                                    <button type="button" class="ch-btn ch-btn-primary" id="ch-warning-ok">Okay</button>
                                </div>
                            </div>
                        </div>`;
                        document.body.appendChild(overlay);
                    }

                    const titleEl = document.getElementById('ch-warning-title');
                    const messageEl = document.getElementById('ch-warning-message');
                    const okBtn = document.getElementById('ch-warning-ok');
                    const closeBtn = document.getElementById('ch-warning-close');
                    let settled = false;

                    const finish = () => {
                        if (settled) return;
                        settled = true;
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                        overlay.removeEventListener('click', onOverlayClick);
                        okBtn.removeEventListener('click', finish);
                        closeBtn.removeEventListener('click', finish);
                        resolve(true);
                    };

                    const onOverlayClick = (event) => {
                        if (event.target === overlay) finish();
                    };

                    titleEl.textContent = options.title || 'Upload Limit Reached';
                    messageEl.textContent = options.message || 'You have selected too many files.';
                    okBtn.textContent = options.buttonLabel || 'Okay';

                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    overlay.addEventListener('click', onOverlayClick);
                    okBtn.addEventListener('click', finish);
                    closeBtn.addEventListener('click', finish);
                });
            };

            window.chSystemAlert = function (message, title) {
                return window.chOpenWarningModal({
                    title: title || 'Notice',
                    message: String(message || ''),
                    buttonLabel: 'Okay'
                });
            };

            window.alert = function (message) {
                window.chSystemAlert(message, 'Notice');
            };

            window.chMediaGalleryState = window.chMediaGalleryState || { items: [], index: 0 };

            function chEnsureMediaLightbox() {
                let overlay = document.getElementById('ch-media-lightbox');
                if (overlay) return overlay;

                overlay = document.createElement('div');
                overlay.id = 'ch-media-lightbox';
                overlay.className = 'ch-media-lightbox-overlay';
                overlay.innerHTML = `
                <div class="ch-media-lightbox">
                    <button type="button" class="ch-media-lightbox-close" aria-label="Close image viewer">&times;</button>
                    <button type="button" class="ch-media-lightbox-nav ch-media-lightbox-prev" aria-label="Previous image">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div class="ch-media-lightbox-stage">
                        <img src="" alt="Selected post image" class="ch-media-lightbox-image">
                    </div>
                    <button type="button" class="ch-media-lightbox-nav ch-media-lightbox-next" aria-label="Next image">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <div class="ch-media-lightbox-counter">1 / 1</div>
                </div>`;
                document.body.appendChild(overlay);

                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) {
                        window.chCloseMediaGallery();
                    }
                });
                overlay.querySelector('.ch-media-lightbox-close')?.addEventListener('click', window.chCloseMediaGallery);
                overlay.querySelector('.ch-media-lightbox-prev')?.addEventListener('click', function () {
                    window.chStepMediaGallery(-1);
                });
                overlay.querySelector('.ch-media-lightbox-next')?.addEventListener('click', function () {
                    window.chStepMediaGallery(1);
                });

                return overlay;
            }

            function chRenderMediaGallery() {
                const overlay = chEnsureMediaLightbox();
                const image = overlay.querySelector('.ch-media-lightbox-image');
                const counter = overlay.querySelector('.ch-media-lightbox-counter');
                const prevBtn = overlay.querySelector('.ch-media-lightbox-prev');
                const nextBtn = overlay.querySelector('.ch-media-lightbox-next');
                const items = window.chMediaGalleryState.items || [];
                const total = items.length;
                const index = Math.max(0, Math.min(window.chMediaGalleryState.index || 0, Math.max(total - 1, 0)));
                window.chMediaGalleryState.index = index;

                if (!total) return;

                if (image) {
                    image.decoding = 'async';
                    image.loading = 'eager';
                    image.fetchPriority = 'high';
                    if (image.src !== items[index]) {
                        image.src = items[index];
                    }
                }
                if (counter) counter.textContent = (index + 1) + ' / ' + total;
                if (prevBtn) prevBtn.style.display = total > 1 ? 'inline-flex' : 'none';
                if (nextBtn) nextBtn.style.display = total > 1 ? 'inline-flex' : 'none';

                if (total > 1) {
                    const nextIndex = (index + 1) % total;
                    const prevIndex = (index - 1 + total) % total;
                    [items[nextIndex], items[prevIndex]].forEach(src => {
                        if (!src) return;
                        const preloadImage = new Image();
                        preloadImage.decoding = 'async';
                        preloadImage.src = src;
                    });
                }
            }

            window.chOpenMediaGallery = function (items, startIndex) {
                if (!Array.isArray(items) || !items.length) return;
                window.chMediaGalleryState.items = items.filter(Boolean);
                window.chMediaGalleryState.index = Math.max(0, Math.min(parseInt(startIndex, 10) || 0, window.chMediaGalleryState.items.length - 1));
                const overlay = chEnsureMediaLightbox();
                chRenderMediaGallery();
                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            };

            window.chCloseMediaGallery = function () {
                const overlay = document.getElementById('ch-media-lightbox');
                if (!overlay) return;
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            };

            window.chStepMediaGallery = function (direction) {
                const items = window.chMediaGalleryState.items || [];
                if (!items.length) return;
                const nextIndex = window.chMediaGalleryState.index + (direction > 0 ? 1 : -1);
                const normalized = (nextIndex + items.length) % items.length;
                window.chMediaGalleryState.index = normalized;
                chRenderMediaGallery();
            };

            window.chShowLoadingModal = function (message, title) {
                let overlay = document.getElementById('ch-loading-modal');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'ch-loading-modal';
                    overlay.className = 'ch-modal-overlay';
                    overlay.style.display = 'none';
                    overlay.innerHTML = `
                    <div class="ch-modal ch-loading-modal">
                        <div class="ch-loading-modal-body">
                            <div class="ch-loading-modal-spinner" aria-hidden="true"></div>
                            <h3 id="ch-loading-modal-title" class="ch-loading-modal-title">Please wait</h3>
                            <p id="ch-loading-modal-message" class="ch-loading-modal-copy">Processing your request...</p>
                        </div>
                    </div>`;
                    document.body.appendChild(overlay);
                }

                const titleEl = document.getElementById('ch-loading-modal-title');
                const messageEl = document.getElementById('ch-loading-modal-message');
                if (titleEl) titleEl.textContent = title || 'Please wait';
                if (messageEl) messageEl.textContent = message || 'Processing your request...';
                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (window.chNavBarStart) window.chNavBarStart();
            };

            window.chHideLoadingModal = function () {
                const overlay = document.getElementById('ch-loading-modal');
                if (!overlay) return;
                overlay.style.display = 'none';
                document.body.style.overflow = '';
                if (window.chNavBarFinish) window.chNavBarFinish();
            };

            window.chIsSubmittingPost = window.chIsSubmittingPost || false;

            // ---- Color picker helpers (wheel + hex text + preview, all synced) ----
            window.chSyncColorWheel = function (wheelId, hexId, previewId) {
                const wheel = document.getElementById(wheelId);
                const hex = document.getElementById(hexId);
                const prev = document.getElementById(previewId);
                if (!wheel) return;
                const val = wheel.value;
                if (hex) hex.value = val.toUpperCase();
                if (prev) { prev.style.background = val; prev.style.opacity = '1'; }
            };
            window.chSyncColorHex = function (wheelId, hexId, previewId) {
                const wheel = document.getElementById(wheelId);
                const hex = document.getElementById(hexId);
                const prev = document.getElementById(previewId);
                if (!hex) return;
                const val = hex.value.trim();
                if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                    if (wheel) wheel.value = val;
                    if (prev) { prev.style.background = val; prev.style.opacity = '1'; }
                } else {
                    if (prev) prev.style.opacity = '0.4';
                }
            };
            window.chPickSwatch = function (wheelId, hexId, previewId, color) {
                const wheel = document.getElementById(wheelId);
                const hex = document.getElementById(hexId);
                const prev = document.getElementById(previewId);
                if (wheel) wheel.value = color;
                if (hex) { hex.value = color.toUpperCase(); }
                if (prev) { prev.style.background = color; prev.style.opacity = '1'; }
            };

            // ============================================================
            // PAGE NAVIGATION LOADING BAR
            // ============================================================
            function chInitNavLoadingBar() {
                // Create progress bar element
                const bar = document.createElement('div');
                bar.id = 'ch-page-loading-bar';
                bar.style.cssText = [
                    'position:fixed', 'top:0', 'left:0', 'height:5px', 'width:0%',
                    'background:linear-gradient(90deg,#FF6640,#FF7551,#FF9A7F)',
                    'z-index:99999', 'transition:width 0.28s ease,opacity 0.45s ease',
                    'border-radius:0 3px 3px 0', 'box-shadow:0 0 12px rgba(255,117,81,0.7)',
                    'pointer-events:none'
                ].join(';');
                document.body.appendChild(bar);

                let _navTimer = null;
                let _barActive = false;

                function chStartNavBar() {
                    _barActive = true;
                    bar.style.transition = 'width 0.25s ease, opacity 0.1s ease';
                    bar.style.opacity = '1';
                    bar.style.width = '0%';
                    // Animate to 85% quickly then slow down
                    requestAnimationFrame(() => {
                        bar.style.transition = 'width 6s cubic-bezier(0.1,0.4,0.3,1), opacity 0.1s ease';
                        bar.style.width = '88%';
                    });
                }

                function chFinishNavBar() {
                    if (!_barActive) return;
                    _barActive = false;
                    bar.style.transition = 'width 0.2s ease, opacity 0.5s ease 0.2s';
                    bar.style.width = '100%';
                    setTimeout(() => { bar.style.opacity = '0'; setTimeout(() => { bar.style.width = '0%'; }, 500); }, 200);
                }

                function chSyncNavBarWithFullPageLoader() {
                    const hasPendingClass = document.documentElement.classList.contains('ch-ui-pending');
                    const pending = hasPendingClass || window.__chUiPending === true;
                    if (pending) {
                        chStartNavBar();
                    } else {
                        chFinishNavBar();
                    }
                }

                // Hook all same-page navigation links (tab navigation)
                document.addEventListener('click', function (e) {
                    const link = e.target.closest('a[href]');
                    if (!link) return;
                    const href = link.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript') || link.target === '_blank') return;
                    // Only intercept internal navigation (tab switches, same-origin)
                    try {
                        const url = new URL(href, window.location.href);
                        if (url.origin !== window.location.origin) return;
                    } catch (err) { return; }

                    chStartNavBar();
                    // Fallback: clear bar if navigation stalls
                    clearTimeout(_navTimer);
                    _navTimer = setTimeout(chFinishNavBar, 10000);
                });

                // Finish bar when page is about to unload
                window.addEventListener('pagehide', chFinishNavBar);

                // If page was loaded (e.g. from back/forward), finish any lingering bar
                window.addEventListener('pageshow', chFinishNavBar);
                window.addEventListener('ch:ui-pending', chStartNavBar);
                window.addEventListener('ch:ui-ready', chFinishNavBar);
                chSyncNavBarWithFullPageLoader();

                // Expose globally for AJAX-triggered reloads
                window.chNavBarStart = chStartNavBar;
                window.chNavBarFinish = chFinishNavBar;
            }
            if (document.readyState === 'complete') {
                (window.requestIdleCallback || function (cb) { setTimeout(cb, 120); })(chInitNavLoadingBar);
            } else {
                window.addEventListener('load', function () {
                    (window.requestIdleCallback || function (cb) { setTimeout(cb, 120); })(chInitNavLoadingBar);
                }, { once: true });
            }

            // ============================================================
            // BUTTON LOADING STATE HELPERS
            // ============================================================
            const _CH_SPINNER_SVG = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>';

            /**
             * Set a button into loading state. Returns a restore function.
             * Usage:  const restore = chBtnLoading(btn, 'Saving...');
             *         ...on error: restore();
             */
            window.chBtnLoading = function (btn, loadingText) {
                if (!btn) return () => { };
                const originalHTML = btn.innerHTML;
                const originalDisabled = btn.disabled;
                btn.disabled = true;
                btn.innerHTML = _CH_SPINNER_SVG + '<span>' + (loadingText || 'Loading...') + '</span>';
                btn.classList.add('ch-btn-loading');
                return function restore(newText) {
                    btn.disabled = originalDisabled;
                    btn.innerHTML = newText !== undefined ? newText : originalHTML;
                    btn.classList.remove('ch-btn-loading');
                };
            };

            /**
             * Helper: after AJAX success, trigger quick-reload with bar animation.
             * Delay defaults to 600ms (faster than old 1000-1200ms).
             */
            window.chReloadAfterSuccess = function (delay) {
                delay = delay !== undefined ? delay : 600;
                if (window.chNavBarStart) window.chNavBarStart();
                setTimeout(() => location.reload(), delay);
            };

            /**
             * AJAX content-only reload: refreshes specific tab/section without full page reload.
             * Usage: chAjaxReloadContent('categories') — reloads categories tab content
             *        chAjaxReloadContent('posts', {filter: 'all', s: '', paged: 1})
             *        chAjaxReloadContent('profile') — special case for profile section
             */
            window.chAjaxReloadContent = function (tabName, extraParams) {
                if (window.chNavBarStart) window.chNavBarStart();

                var fd = new FormData();
                fd.append('action', 'ch_admin_tab');
                fd.append('nonce', window.chAdminTabNonce || '');
                fd.append('tab', tabName);
                fd.append('mode', 'content');

                // Merge extra params
                if (extraParams && typeof extraParams === 'object') {
                    for (var key in extraParams) {
                        if (extraParams.hasOwnProperty(key)) {
                            fd.append(key, extraParams[key]);
                        }
                    }
                }

                var targetId = 'ch-' + tabName + '-content';
                var target = document.getElementById(targetId);
                if (!target) {
                    // Fallback to full reload if target not found
                    if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    return;
                }

                target.style.opacity = '0.45';
                target.style.pointerEvents = 'none';

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            target.innerHTML = json.data.html !== undefined ? json.data.html : '';
                            chRunEmbeddedScripts(target);
                        } else {
                            // Fallback to full reload on error
                            if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                        }
                    })
                    .catch(function () {
                        // Fallback to full reload on network error
                        if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    })
                    .finally(function () {
                        target.style.opacity = '';
                        target.style.pointerEvents = '';
                        if (window.chNavBarFinish) window.chNavBarFinish();
                    });
            };

            /**
             * AJAX content-only reload for feed posts list (special case for feed operations).
             * Usage: chAjaxReloadFeed() — refreshes #ch-posts-list
             */
            window.chAjaxReloadFeed = function () {
                if (!window.chFeedState) {
                    if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    return;
                }

                if (window.chNavBarStart) window.chNavBarStart();

                var list = document.getElementById('ch-posts-list');
                if (!list) {
                    if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    return;
                }

                list.style.opacity = '0.45';
                list.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_feed_sort');
                fd.append('nonce', window.chFeedState.nonce);
                fd.append('sort', window.chFeedState.sort || 'new');
                fd.append('cat', window.chFeedState.cat || '');
                fd.append('s', window.chFeedState.s || '');
                fd.append('location', window.chFeedState.location || '');
                fd.append('paged', window.chFeedState.paged || 1);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            list.innerHTML = json.data.html !== undefined ? json.data.html : '';
                            var headerInner = document.getElementById('ch-feed-header-inner');
                            if (headerInner && json.data.header !== undefined) {
                                headerInner.innerHTML = json.data.header;
                            }

                            // Update pagination
                            var paginationWrap = document.getElementById('ch-feed-pagination-wrap');
                            if (paginationWrap) {
                                paginationWrap.innerHTML = json.data.pagination !== undefined ? json.data.pagination : '';
                            }
                        } else {
                            if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                        }
                    })
                    .catch(function () {
                        if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    })
                    .finally(function () {
                        list.style.opacity = '';
                        list.style.pointerEvents = '';
                        if (window.chNavBarFinish) window.chNavBarFinish();
                    });
            };

            /**
             * AJAX reload for category sidebar only (used by category operations in admin).
             * Usage: chAjaxReloadCategoriesSidebar()
             */
            window.chAjaxReloadCategoriesSidebar = function () {
                if (window.chNavBarStart) window.chNavBarStart();

                var fd = new FormData();
                fd.append('action', 'ch_admin_tab');
                fd.append('nonce', window.chAdminTabNonce || '');
                fd.append('tab', 'categories');
                fd.append('mode', 'content');

                var target = document.getElementById('ch-categories-content');
                if (!target) {
                    if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    return;
                }

                target.style.opacity = '0.45';
                target.style.pointerEvents = 'none';

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            target.innerHTML = json.data.html;
                            chRunEmbeddedScripts(target);
                        } else {
                            if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                        }
                    })
                    .catch(function () {
                        if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                    })
                    .finally(function () {
                        target.style.opacity = '';
                        target.style.pointerEvents = '';
                        if (window.chNavBarFinish) window.chNavBarFinish();
                    });
            };

            // Scripts inside HTML assigned via innerHTML do not run automatically.
            // Recreate them so AJAX-loaded tabs can register their handlers and boot logic.
            window.chRunEmbeddedScripts = function (root) {
                if (!root) return;
                root.querySelectorAll('script').forEach(function (oldScript) {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes || []).forEach(function (attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent || '';
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            };

            window.chOpenModal = function (id) {
                const el = document.getElementById(id);
                if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
            };
            window.chCloseModal = function (id) {
                const el = document.getElementById(id);
                if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
            };
            window.chPromptModerationReason = function (title, placeholder, onSubmit) {
                const existing = document.getElementById('ch-modal-reason');
                if (existing) existing.remove();
                const overlay = document.createElement('div');
                overlay.id = 'ch-modal-reason';
                overlay.className = 'ch-modal-overlay';
                overlay.innerHTML = `
                <div class="ch-modal" style="max-width:520px;">
                    <div class="ch-modal-header">
                        <h3>${title || 'Provide a reason'}</h3>
                        <button class="ch-modal-close" type="button">&times;</button>
                    </div>
                    <div class="ch-modal-body">
                        <textarea id="ch-reason-input" class="ch-input ch-textarea ch-reason-textarea" placeholder="${placeholder || 'Enter reason...'}"></textarea>
                    </div>
                    <div class="ch-modal-footer">
                        <button type="button" class="ch-btn ch-btn-secondary" id="ch-reason-cancel">Cancel</button>
                        <button type="button" class="ch-btn ch-btn-primary" id="ch-reason-submit">Submit</button>
                    </div>
                </div>`;
                document.body.appendChild(overlay);
                const close = () => overlay.remove();
                overlay.querySelector('.ch-modal-close')?.addEventListener('click', close);
                overlay.querySelector('#ch-reason-cancel')?.addEventListener('click', close);
                overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
                overlay.querySelector('#ch-reason-submit')?.addEventListener('click', () => {
                    const reason = (overlay.querySelector('#ch-reason-input')?.value || '').trim();
                    if (!reason) { alert('A reason is required.'); return; }
                    close();
                    if (typeof onSubmit === 'function') onSubmit(reason);
                });
                const ta = overlay.querySelector('#ch-reason-input');
                if (ta) ta.focus();
            };
            // Close on overlay click
            document.addEventListener('click', function (e) {
                const galleryThumb = e.target.closest('.ch-post-media-thumb[data-ch-gallery-index]');
                if (galleryThumb) {
                    const gallery = galleryThumb.closest('[data-ch-gallery-items]');
                    if (gallery) {
                        e.preventDefault();
                        try {
                            const items = JSON.parse(gallery.getAttribute('data-ch-gallery-items') || '[]');
                            window.chOpenMediaGallery(items, parseInt(galleryThumb.getAttribute('data-ch-gallery-index') || '0', 10));
                        } catch (err) { }
                    }
                    return;
                }
                if (e.target.classList.contains('ch-modal-overlay')) {
                    e.target.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
            document.addEventListener('keydown', function (e) {
                const overlay = document.getElementById('ch-media-lightbox');
                if (!overlay || overlay.style.display !== 'flex') return;
                if (e.key === 'Escape') window.chCloseMediaGallery();
                if (e.key === 'ArrowLeft') window.chStepMediaGallery(-1);
                if (e.key === 'ArrowRight') window.chStepMediaGallery(1);
            });
            window.chSharePost = function (url) {
                if (navigator.share) {
                    navigator.share({ url: url }).catch(() => { });
                } else {
                    navigator.clipboard.writeText(url).then(() => alert('Link copied to clipboard!'));
                }
            };

            window.chToggleShareMenu = function (btn) {
                // Close other menus
                document.querySelectorAll('.ch-share-menu.show').forEach(menu => {
                    if (menu !== btn.nextElementSibling) menu.classList.remove('show');
                });

                const menu = btn.nextElementSibling;
                const isVisible = menu.classList.contains('show');

                if (!isVisible) {
                    // Position the menu below the button
                    const rect = btn.getBoundingClientRect();
                    menu.style.position = 'fixed';
                    menu.style.top = (rect.bottom + 8) + 'px';
                    menu.style.left = (rect.right - menu.offsetWidth) + 'px'; // Align to right edge
                    menu.style.zIndex = '10000';
                    menu.classList.add('show');
                } else {
                    menu.classList.remove('show');
                }
            };

            window.chShareToSocial = function (platform, url, title = '') {
                let shareUrl = '';
                const encodedUrl = encodeURIComponent(url);
                const encodedTitle = encodeURIComponent(title || 'Check this out');

                switch (platform) {
                    case 'twitter':
                        shareUrl = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
                        break;
                    case 'facebook':
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
                        break;
                    case 'linkedin':
                        shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
                        break;
                    case 'copy':
                        navigator.clipboard.writeText(url).then(() => alert('Link copied to clipboard!'));
                        return;
                }

                if (shareUrl) {
                    window.open(shareUrl, '_blank', 'width=600,height=400');
                }

                // Close the menu
                document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
            };

            window.chOpenFeedShareModal = function (url, title) {
                const modal = document.getElementById('ch-modal-share-feed-post');
                if (!modal) return;
                modal.dataset.shareUrl = url || '';
                modal.dataset.shareTitle = title || '';
                if (typeof window.chOpenModal === 'function') {
                    window.chOpenModal('ch-modal-share-feed-post');
                } else {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            };

            window.chShareFeedPost = function (platform) {
                const modal = document.getElementById('ch-modal-share-feed-post');
                if (!modal) return;
                const shareUrl = modal.dataset.shareUrl || '';
                const shareTitle = modal.dataset.shareTitle || '';
                window.chShareToSocial(platform, shareUrl, shareTitle);
                if (typeof window.chCloseModal === 'function') {
                    window.chCloseModal('ch-modal-share-feed-post');
                } else {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            };

            // Close share menus when clicking outside
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ch-share-dropdown')) {
                    document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
                }
            });

            // Close profile and notifications menus when clicking outside
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ch-profile-dropdown') && !e.target.closest('#ch-profile-btn')) {
                    const menu = document.getElementById('ch-profile-menu');
                    if (menu) menu.style.display = 'none';
                }
                if (!e.target.closest('.ch-notifications-dropdown') && !e.target.closest('#ch-notif-btn')) {
                    const menu = document.getElementById('ch-notifications-menu');
                    if (menu) menu.style.display = 'none';
                }
            });

            function chPositionDropdown(menu, btn) {
                if (!menu || !btn) return;

                const viewportPadding = window.innerWidth <= 480 ? 12 : 16;
                const gap = 6;
                const rect = btn.getBoundingClientRect();
                const previousDisplay = menu.style.display;
                const previousVisibility = menu.style.visibility;

                menu.style.visibility = 'hidden';
                menu.style.display = 'block';
                menu.style.left = 'auto';
                menu.style.right = 'auto';
                menu.style.top = '0';

                const menuRect = menu.getBoundingClientRect();
                const menuHeight = menuRect.height;
                const spaceBelow = window.innerHeight - rect.bottom - viewportPadding;
                const spaceAbove = rect.top - viewportPadding;
                const maxHeight = Math.max(160, window.innerHeight - (viewportPadding * 2));

                let top = rect.bottom + gap;
                if (menuHeight > spaceBelow && spaceAbove > spaceBelow) {
                    top = Math.max(viewportPadding, rect.top - menuHeight - gap);
                }
                top = Math.min(top, window.innerHeight - viewportPadding - menuHeight);
                top = Math.max(viewportPadding, top);

                const right = Math.max(viewportPadding, window.innerWidth - rect.right);

                menu.style.top = top + 'px';
                menu.style.right = right + 'px';
                menu.style.left = 'auto';
                menu.style.maxHeight = maxHeight + 'px';
                menu.style.visibility = previousVisibility;
                menu.style.display = previousDisplay || 'none';
            }

            window.chToggleProfileMenu = function (e) {
                e && e.stopPropagation();
                const menu = document.getElementById('ch-profile-menu');
                const btn = document.getElementById('ch-profile-btn');
                if (!menu || !btn) return;
                const notifMenu = document.getElementById('ch-notifications-menu');
                if (notifMenu) notifMenu.style.display = 'none';
                const isVisible = menu.style.display === 'block';
                if (!isVisible) {
                    chPositionDropdown(menu, btn);
                }
                menu.style.display = isVisible ? 'none' : 'block';
            };

            window.chToggleNotifications = function (e) {
                e && e.stopPropagation();
                const menu = document.getElementById('ch-notifications-menu');
                const btn = document.getElementById('ch-notif-btn');
                if (!menu || !btn) return;
                const profileMenu = document.getElementById('ch-profile-menu');
                if (profileMenu) profileMenu.style.display = 'none';
                const isVisible = menu.style.display === 'block';
                if (!isVisible) {
                    chPositionDropdown(menu, btn);
                    chLoadNotifications();
                }
                menu.style.display = isVisible ? 'none' : 'block';
            };

            window.chNotificationCache = window.chNotificationCache || {
                fetchedAt: 0,
                payload: null
            };

            window.chSetNotificationCount = function (unreadCount) {
                const countEl = document.getElementById('ch-notification-count');
                if (!countEl) return;
                if (unreadCount > 0) {
                    countEl.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    countEl.style.display = 'block';
                } else {
                    countEl.style.display = 'none';
                }
            };

            window.chRenderNotifications = function (data) {
                const list = document.getElementById('ch-notifications-list');
                if (!list) return;

                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = '<div class="ch-no-notifications">You\'re all caught up!</div>';
                    return;
                }

                const typeIcons = {
                    reply: {
                        cls: 'type-reply',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
                    },
                    mention: {
                        cls: 'type-mention',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/></svg>'
                    },
                    vote: {
                        cls: 'type-vote',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>'
                    },
                    announcement: {
                        cls: 'type-announcement',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>'
                    },
                    report_resolved: {
                        cls: 'type-report_resolved',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                    },
                };

                list.innerHTML = data.notifications.map(n => {
                    const postUrl = n.post_rand_id
                        ? (chFeedUrl + '?view_post=' + encodeURIComponent(n.post_rand_id))
                        : null;
                    const clickAttr = postUrl
                        ? `onclick="chMarkNotificationRead(${n.id}, '${postUrl}', this)"`
                        : `onclick="chMarkNotificationRead(${n.id}, null, this)"`;
                    const icon = typeIcons[n.type] || { cls: 'type-default', svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>' };
                    return `
                <div class="ch-notification-item ${n.is_read ? '' : 'unread'}" ${clickAttr} data-id="${n.id}">
                    <div class="ch-notification-dot"></div>
                    <div class="ch-notification-icon ${icon.cls}">${icon.svg}</div>
                    <div class="ch-notification-content">
                        <div class="ch-notification-message">${n.message}</div>
                        <div class="ch-notification-time">${n.created_at}</div>
                    </div>
                </div>`;
                }).join('');
            };

            window.chLoadNotifications = function () {
                const now = Date.now();
                if (window.chNotificationCache.payload && (now - window.chNotificationCache.fetchedAt) < 15000) {
                    chRenderNotifications(window.chNotificationCache.payload);
                    chSetNotificationCount(window.chNotificationCache.payload.unread_count || 0);
                    return Promise.resolve(window.chNotificationCache.payload);
                }

                fetch(ajaxurl + '?action=ch_get_notifications')
                    .then(r => r.json())
                    .then(json => {
                        if (!json.success) return;
                        window.chNotificationCache = {
                            fetchedAt: Date.now(),
                            payload: json.data
                        };
                        chRenderNotifications(json.data);
                        chSetNotificationCount(json.data.unread_count || 0);
                        return json.data;
                    })
                    .catch(error => {
                        console.error('Failed to load notifications:', error);
                    });
            };

            window.chMarkNotificationRead = function (notificationId, postUrl, el) {
                // Instantly update UI — remove unread state and decrement badge
                const item = el?.closest('.ch-notification-item') || document.querySelector(`.ch-notification-item[data-id="${notificationId}"]`);
                if (item && item.classList.contains('unread')) {
                    item.classList.remove('unread');
                    if (window.chNotificationCache.payload) {
                        const currentUnread = parseInt(window.chNotificationCache.payload.unread_count || 0, 10);
                        window.chNotificationCache.payload.unread_count = Math.max(0, currentUnread - 1);
                        const cachedItem = (window.chNotificationCache.payload.notifications || []).find(n => parseInt(n.id, 10) === parseInt(notificationId, 10));
                        if (cachedItem) cachedItem.is_read = 1;
                    }
                    chSetNotificationCount(window.chNotificationCache.payload?.unread_count || 0);
                }

                // Fire mark-read in background — don't block navigation
                const fd = new FormData();
                fd.append('action', 'ch_mark_notifications');
                fd.append('notification_ids[]', notificationId);
                fetch(chAjaxUrl, { method: 'POST', body: fd }).catch(() => { });

                // Navigate if there's a destination
                if (postUrl) {
                    window.location.href = postUrl;
                }
            };

            window.chMarkAllNotificationsRead = function () {
                // Instantly clear all unread states in UI
                document.querySelectorAll('.ch-notification-item.unread').forEach(el => el.classList.remove('unread'));
                if (window.chNotificationCache.payload) {
                    window.chNotificationCache.payload.unread_count = 0;
                    (window.chNotificationCache.payload.notifications || []).forEach(n => {
                        n.is_read = 1;
                    });
                }
                chSetNotificationCount(0);

                const fd = new FormData();
                fd.append('action', 'ch_mark_notifications');
                fetch(chAjaxUrl, { method: 'POST', body: fd }).catch(() => { });
            };

            window.chLoadNotificationCount = function () {
                if (window.chNotificationCache.payload) {
                    chSetNotificationCount(window.chNotificationCache.payload.unread_count || 0);
                    return Promise.resolve(window.chNotificationCache.payload.unread_count || 0);
                }

                fetch(ajaxurl + '?action=ch_get_notification_count')
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            chSetNotificationCount(json.data.unread_count || 0);
                        }
                    })
                    .catch(error => {
                        console.error('Failed to load notification count:', error);
                    });
            };

            // Load notification count on page load
            if (document.getElementById('ch-notification-count') && !window.__chNotificationCountInitialized) {
                window.__chNotificationCountInitialized = true;
                chLoadNotificationCount();
            }

            window.chPreviewAvatar = function (input) {
                if (!input.files || !input.files[0]) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrap = document.getElementById('ch-avatar-preview-wrap');
                    if (!wrap) return;
                    let img = document.getElementById('ch-avatar-preview-img');
                    const initials = document.getElementById('ch-avatar-preview-initials');
                    if (initials) initials.style.display = 'none';
                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'ch-avatar-preview-img';
                        img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                        wrap.appendChild(img);
                    }
                    img.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            };

            function chResizeImageToBlob(file, maxPx, quality, callback) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = new Image();
                    img.onload = function () {
                        let w = img.width, h = img.height;
                        if (w > maxPx || h > maxPx) {
                            if (w > h) { h = Math.round(h * maxPx / w); w = maxPx; }
                            else { w = Math.round(w * maxPx / h); h = maxPx; }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = w; canvas.height = h;
                        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                        canvas.toBlob(callback, 'image/jpeg', quality || 0.8);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }

            window.chUpdateProfile = function (nonce) {
                const displayName = document.getElementById('ch-profile-display-name').value.trim();
                const bio = document.getElementById('ch-profile-bio').value.trim();
                const location = document.getElementById('ch-profile-location').value.trim();
                const isAnonymous = document.getElementById('ch-profile-anonymous').checked ? 1 : 0;
                const avatarFile = document.getElementById('ch-profile-avatar').files[0];

                const profileMsg = document.getElementById('ch-profile-msg');
                const fallbackCard = profileMsg ? profileMsg.closest('.ch-card') : null;
                const btn = (window.event && window.event.target) ? window.event.target : fallbackCard?.querySelector('button[onclick*=\"chUpdateProfile\"]');
                if (!btn) return;
                btn.disabled = true;
                btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';

                function doSave(blob) {
                    const fd = new FormData();
                    fd.append('action', 'ch_update_profile');
                    fd.append('display_name', displayName);
                    fd.append('bio', bio);
                    fd.append('location', location);
                    fd.append('is_anonymous', isAnonymous);
                    fd.append('nonce', nonce);
                    if (blob) fd.append('avatar', blob, 'avatar.jpg');

                    chFetchJson(chAjaxUrl, { method: 'POST', body: fd })
                        .then(json => {
                            chSetNotice('ch-profile-msg', json.success ? 'success' : 'error', json.data?.message || '');
                            if (json.success) {
                                btn.textContent = 'Saved!';
                                if (json.data?.avatar_url) {
                                    chUpdateCurrentUserAvatars(json.data.avatar_url);
                                }
                                setTimeout(() => { btn.disabled = false; btn.textContent = 'Save Profile'; }, 2000);
                            } else {
                                chResetButton(btn, 'Save Profile');
                            }
                        })
                        .catch(() => {
                            chSetNotice('ch-profile-msg', 'error', 'Network error. Please try again.');
                            chResetButton(btn, 'Save Profile');
                        });
                }

                if (avatarFile) {
                    // Fast path: avoid client-side resize for already-small uploads.
                    const isCommonImage = /^image\/(jpeg|jpg|png|webp)$/i.test(avatarFile.type || '');
                    const smallEnough = avatarFile.size <= 450 * 1024;
                    if (isCommonImage && smallEnough) {
                        doSave(avatarFile);
                        return;
                    }
                    btn.textContent = 'Optimizing image…';
                    chResizeImageToBlob(avatarFile, 320, 0.8, function (blob) {
                        btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';
                        doSave(blob);
                    });
                } else {
                    doSave(null);
                }
            };
        })();
    </script>
    <script>
        (function () {
            var state = window.chFeedState;
            var list = document.getElementById('ch-posts-list');
            var tabs = document.getElementById('ch-sort-tabs');
            if (!state || !list || !tabs) return;

            function loadFeedResults(nextState, callback) {
                list.style.opacity = '0.45';
                list.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_feed_sort');
                fd.append('nonce', state.nonce);
                fd.append('sort', nextState.sort || 'new');
                fd.append('cat', nextState.cat || '');
                fd.append('s', nextState.s || '');
                fd.append('location', nextState.location || '');
                fd.append('paged', 1);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!json.success) {
                            if (typeof callback === 'function') callback(false);
                            return;
                        }

                        list.innerHTML = json.data.html !== undefined ? json.data.html : '';
                        var headerInner = document.getElementById('ch-feed-header-inner');
                        if (headerInner && json.data.header !== undefined) headerInner.innerHTML = json.data.header;

                        var paginationWrap = document.getElementById('ch-feed-pagination-wrap');
                        if (paginationWrap) {
                            paginationWrap.innerHTML = json.data.pagination !== undefined ? json.data.pagination : '';
                        }

                        state.sort = nextState.sort || 'new';
                        state.cat = nextState.cat || '';
                        state.s = nextState.s || '';
                        state.location = nextState.location || '';
                        state.paged = 1;

                        var url = new URL(window.location.href);
                        if (state.sort !== 'new') url.searchParams.set('sort', state.sort);
                        else url.searchParams.delete('sort');
                        if (state.cat) url.searchParams.set('cat', state.cat);
                        else url.searchParams.delete('cat');
                        if (state.s) url.searchParams.set('s', state.s);
                        else url.searchParams.delete('s');
                        if (state.location) url.searchParams.set('location', state.location);
                        else url.searchParams.delete('location');
                        url.searchParams.delete('paged');
                        history.replaceState(null, '', url.toString());

                        if (typeof callback === 'function') callback(true);
                    })
                    .catch(function () {
                        if (typeof callback === 'function') callback(false);
                    })
                    .finally(function () {
                        list.style.opacity = '';
                        list.style.pointerEvents = '';
                    });
            }

            tabs.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-sort]');
                if (!btn || btn.classList.contains('active')) return;
                e.preventDefault();
                e.stopImmediatePropagation();

                var currentActive = tabs.querySelector('.ch-sort-tab.active');
                loadFeedResults({
                    sort: btn.dataset.sort,
                    cat: state.cat,
                    s: state.s,
                    location: state.location
                }, function (success) {
                    if (!success) return;
                    if (currentActive) currentActive.classList.remove('active');
                    btn.classList.add('active');
                });
            }, true);

            var searchForm = document.getElementById('ch-feed-search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var input = searchForm.querySelector('input[name="s"]');
                    loadFeedResults({
                        sort: state.sort,
                        cat: state.cat,
                        s: input ? input.value.trim() : '',
                        location: state.location
                    });
                }, true);
            }

            var locationForm = document.getElementById('ch-feed-location-form');
            var locationSelect = locationForm ? locationForm.querySelector('select[name="location"]') : null;
            if (locationSelect) {
                locationSelect.addEventListener('change', function (e) {
                    e.stopImmediatePropagation();
                    loadFeedResults({
                        sort: state.sort,
                        cat: state.cat,
                        s: state.s,
                        location: locationSelect.value || ''
                    });
                }, true);
            }
        })();
    </script>
    <?php
    return ob_get_clean();
}

// Reusable settings modal (dark mode toggle + sign out) for standalone pages
function ch_settings_modal_html($logout_url = '')
{
    if (!$logout_url)
        $logout_url = wp_logout_url(home_url('/forum-feed/'));
    ob_start(); ?>
    <div id="ch-modal-settings" class="ch-modal-overlay" style="display:none;">
        <div class="ch-modal" style="max-width:380px;">
            <div class="ch-modal-header">
                <h3>Settings</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-settings')">&times;</button>
            </div>
            <div class="ch-modal-body" style="padding:0;">
                <div class="ch-settings-section">
                    <div class="ch-settings-section-title">Appearance</div>
                    <div class="ch-settings-row">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                                </svg>
                                Dark Mode
                            </div>
                            <div class="ch-settings-row-desc">Switch to a darker interface</div>
                        </div>
                        <label class="ch-toggle">
                            <input type="checkbox" id="ch-dark-mode-toggle" onchange="chToggleDarkMode(this.checked)">
                            <span class="ch-toggle-track"><span class="ch-toggle-thumb"></span></span>
                        </label>
                    </div>
                </div>
                <div class="ch-settings-section" style="border-bottom:none;">
                    <div class="ch-settings-section-title" style="color:#ef4444;">Account Actions</div>
                    <a href="<?php echo esc_url($logout_url); ?>" class="ch-settings-row ch-settings-row-link"
                        style="color:#ef4444;">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label" style="color:#ef4444;">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign Out
                            </div>
                        </div>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            window.chOpenSettingsModal = function () {
                const tog = document.getElementById('ch-dark-mode-toggle');
                if (tog) tog.checked = window.chIsDarkModeEnabled ? window.chIsDarkModeEnabled() : document.documentElement.classList.contains('ch-dark');
                if (typeof chOpenModal === 'function') chOpenModal('ch-modal-settings');
            };
            window.chCloseProfileMenu = window.chCloseProfileMenu || function () {
                const m = document.getElementById('ch-profile-menu');
                if (m) m.style.display = 'none';
            };
            window.chToggleProfileMenu = window.chToggleProfileMenu || function (e) {
                e.stopPropagation();
                const m = document.getElementById('ch-profile-menu');
                if (!m) return;
                const isShown = m.style.display !== 'none';
                m.style.display = isShown ? 'none' : 'block';
                if (!isShown) {
                    const btn = document.getElementById('ch-profile-btn');
                    const rect = btn.getBoundingClientRect();
                    const menuWidth = Math.min(m.offsetWidth || 240, window.innerWidth - 16);
                    const spaceRight = window.innerWidth - rect.right;
                    const spaceLeft = rect.left;

                    // Prefer right-aligned; flip to left if it would clip
                    let leftPx;
                    if (spaceRight >= menuWidth || spaceRight >= spaceLeft) {
                        // Align to right edge of button
                        leftPx = Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8);
                    } else {
                        // Align to left edge of button
                        leftPx = rect.left;
                    }
                    leftPx = Math.max(8, leftPx);

                    // Drop below button; if not enough room below, open upward
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const menuHeight = m.offsetHeight || 280;
                    let topPx;
                    if (spaceBelow >= menuHeight + 8 || spaceBelow >= window.innerHeight - rect.top) {
                        topPx = rect.bottom + 6;
                    } else {
                        topPx = Math.max(8, rect.top - menuHeight - 6);
                    }

                    m.style.position = 'fixed';
                    m.style.top = topPx + 'px';
                    m.style.left = leftPx + 'px';
                    m.style.right = 'auto';
                    m.style.maxWidth = (window.innerWidth - 16) + 'px';
                }
            };
            document.addEventListener('click', function (e) {
                const m = document.getElementById('ch-profile-menu');
                const btn = document.getElementById('ch-profile-btn');
                if (m && btn && !btn.contains(e.target) && !m.contains(e.target)) {
                    m.style.display = 'none';
                }
            });

            // ============================================================
            // ADMIN PANEL — tab switching via AJAX
            // ============================================================
            (function () {
                var adminRequestUrl = '';

                async function chLoadAdminShell(url, shouldPush) {
                    var currentWrap = document.querySelector('.ch-dashboard-wrap');
                    if (!currentWrap) return false;

                    currentWrap.style.opacity = '0.45';
                    currentWrap.style.pointerEvents = 'none';

                    try {
                        adminRequestUrl = url;
                        var doc = await chFetchDocument(url);
                        if (adminRequestUrl !== url) return true;

                        var nextWrap = doc.querySelector('.ch-dashboard-wrap');
                        if (!nextWrap) throw new Error('Missing admin dashboard markup');

                        currentWrap.replaceWith(nextWrap);
                        chRunInlineScripts(nextWrap);

                        if (shouldPush !== false) {
                            history.pushState({ chSoftNav: 'admin' }, '', url);
                        }
                        return true;
                    } finally {
                        adminRequestUrl = '';
                        var freshWrap = document.querySelector('.ch-dashboard-wrap');
                        if (freshWrap) {
                            freshWrap.style.opacity = '';
                            freshWrap.style.pointerEvents = '';
                        }
                    }
                }

                window.chLoadAdminShell = chLoadAdminShell;

                document.addEventListener('click', function (e) {
                    // Intercept all links inside .ch-dashboard-wrap that navigate to the same origin
                    var adminLink = e.target.closest('.ch-dashboard-wrap a[href]');
                    if (!adminLink || !chCanSoftNavigate(adminLink)) return;

                    try {
                        var adminUrl = new URL(adminLink.href, window.location.href);
                        // Only soft-navigate if URL has tab parameter or is on the same page
                        var hasTab = adminUrl.searchParams.get('tab');
                        var isCurrentPage = adminUrl.pathname === window.location.pathname;
                        if (!hasTab && !isCurrentPage) return; // Let regular links work normally

                        e.preventDefault();
                        chLoadAdminShell(adminUrl.toString(), true).catch(function () {
                            window.location.href = adminLink.href;
                        });
                    } catch (err) { }
                });

                window.addEventListener('popstate', function () {
                    if (!document.querySelector('.ch-dashboard-wrap')) return;
                    chLoadAdminShell(window.location.href, false).catch(function () {
                        window.location.reload();
                    });
                });
            })();

            // ============================================================
            // MY FEED — subtab switching via AJAX
            // ============================================================
            (function () {
                var myFeedRequestUrl = '';

                async function chLoadMyFeedShell(url, shouldPush) {
                    var currentWrap = document.querySelector('.ch-my-feed-wrap');
                    if (!currentWrap) return false;

                    currentWrap.style.opacity = '0.45';
                    currentWrap.style.pointerEvents = 'none';

                    try {
                        myFeedRequestUrl = url;
                        var doc = await chFetchDocument(url);
                        if (myFeedRequestUrl !== url) return true;

                        var nextWrap = doc.querySelector('.ch-my-feed-wrap');
                        if (!nextWrap) throw new Error('Missing My Feed markup');

                        currentWrap.replaceWith(nextWrap);
                        chRunInlineScripts(nextWrap);

                        if (shouldPush !== false) {
                            history.pushState({ chSoftNav: 'my_feed' }, '', url);
                        }
                        return true;
                    } finally {
                        myFeedRequestUrl = '';
                        var freshWrap = document.querySelector('.ch-my-feed-wrap');
                        if (freshWrap) {
                            freshWrap.style.opacity = '';
                            freshWrap.style.pointerEvents = '';
                        }
                    }
                }

                window.chLoadMyFeedShell = chLoadMyFeedShell;
            })();

            // ============================================================
            // FORUM FEED - shell switching via AJAX
            // ============================================================
            (function () {
                var feedRequestUrl = '';

                async function chLoadFeedShell(url, shouldPush) {
                    var currentWrap = document.querySelector('.ch-feed-shell');
                    if (!currentWrap) return false;

                    currentWrap.style.opacity = '0.45';
                    currentWrap.style.pointerEvents = 'none';
                    if (window.chNavBarStart) window.chNavBarStart();
                    if (window.chCloseAllMobileMenus) window.chCloseAllMobileMenus();

                    try {
                        feedRequestUrl = url;
                        var doc = await chFetchDocument(url);
                        if (feedRequestUrl !== url) return true;

                        var nextWrap = doc.querySelector('.ch-feed-shell');
                        if (!nextWrap) throw new Error('Missing forum feed markup');

                        currentWrap.replaceWith(nextWrap);
                        chRunInlineScripts(nextWrap);

                        if (doc.title) {
                            document.title = doc.title;
                        }

                        if (shouldPush !== false) {
                            history.pushState({ chSoftNav: 'feed' }, '', url);
                        }
                        return true;
                    } finally {
                        feedRequestUrl = '';
                        var freshWrap = document.querySelector('.ch-feed-shell');
                        if (freshWrap) {
                            freshWrap.style.opacity = '';
                            freshWrap.style.pointerEvents = '';
                        }
                        if (window.chNavBarFinish) window.chNavBarFinish();
                    }
                }

                function chCanSoftLoadFeedLink(link) {
                    if (!link || !link.matches('.ch-top-nav .ch-nav-link[href]')) return false;
                    if (!chCanSoftNavigate(link)) return false;

                    try {
                        var url = new URL(link.href, window.location.href);
                        var feedUrl = new URL(window.chFeedUrl || window.location.href, window.location.href);
                        var normalizePath = function (path) {
                            return path.replace(/\/+$/, '') || '/';
                        };
                        return url.origin === feedUrl.origin && normalizePath(url.pathname) === normalizePath(feedUrl.pathname);
                    } catch (err) {
                        return false;
                    }
                }

                function chCanAjaxLoadFeedLink(link) {
                    if (!link || !link.matches('.ch-top-nav .ch-nav-link[href]')) return false;
                    if (!window.chFeedState) return false;
                    var nav = link.dataset.chFeedNav || '';
                    return nav === 'home' || nav === 'trending' || nav === 'bookmarks';
                }

                function chAjaxLoadFeedLink(link, shouldPush) {
                    var nav = link.dataset.chFeedNav || '';
                    if (!nav) return Promise.reject();

                    var url = new URL(link.href, window.location.href);
                    var params = url.searchParams;

                    var nextState = {
                        sort: params.get('sort') || 'new',
                        cat: params.get('cat') || '',
                        s: params.get('s') || '',
                        location: params.get('location') || '',
                        paged: 1,
                        bookmarks: params.has('bookmarks') ? 1 : 0
                    };

                    var list = document.getElementById('ch-posts-list');
                    if (!list) return Promise.reject();

                    if (window.chNavBarStart) window.chNavBarStart();
                    if (window.chCloseAllMobileMenus) window.chCloseAllMobileMenus();
                    list.style.opacity = '0.45';
                    list.style.pointerEvents = 'none';

                    var fd = new FormData();
                    fd.append('action', 'ch_feed_sort');
                    fd.append('nonce', window.chFeedState.nonce);
                    fd.append('sort', nextState.sort);
                    fd.append('cat', nextState.cat);
                    fd.append('s', nextState.s);
                    fd.append('location', nextState.location);
                    fd.append('paged', nextState.paged);
                    if (nextState.bookmarks) fd.append('bookmarks', 1);

                    return fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (!json.success) {
                                throw new Error('Feed AJAX failed');
                            }

                            list.innerHTML = json.data.html !== undefined ? json.data.html : '';
                            var headerInner = document.getElementById('ch-feed-header-inner');
                            if (headerInner && json.data.header !== undefined) {
                                headerInner.innerHTML = json.data.header;
                            }

                            var paginationWrap = document.getElementById('ch-feed-pagination-wrap');
                            if (paginationWrap) {
                                paginationWrap.innerHTML = json.data.pagination !== undefined ? json.data.pagination : '';
                            }

                            window.chFeedState.sort = nextState.sort;
                            window.chFeedState.cat = nextState.cat;
                            window.chFeedState.s = nextState.s;
                            window.chFeedState.location = nextState.location;
                            window.chFeedState.paged = nextState.paged;
                            window.chFeedState.bookmarks = nextState.bookmarks;

                            if (shouldPush !== false) {
                                history.pushState({ chSoftNav: 'feed' }, '', link.href);
                            }

                            document.querySelectorAll('.ch-top-nav .ch-nav-link.active').forEach(function (el) {
                                el.classList.remove('active');
                            });
                            link.classList.add('active');

                            if (window.chNavBarFinish) window.chNavBarFinish();
                            return true;
                        })
                        .catch(function (err) {
                            if (window.chNavBarFinish) window.chNavBarFinish();
                            throw err;
                        })
                        .finally(function () {
                            list.style.opacity = '';
                            list.style.pointerEvents = '';
                        });
                }

                function chShowFeedLoadingOverlay() {
                    document.body.classList.add('ch-feed-loading-active');
                    var shell = document.querySelector('.ch-feed-shell');
                    if (shell) shell.classList.add('ch-feed-shell-loading');
                }

                function chHideFeedLoadingOverlay() {
                    document.body.classList.remove('ch-feed-loading-active');
                    var shell = document.querySelector('.ch-feed-shell');
                    if (shell) shell.classList.remove('ch-feed-shell-loading');
                }

                window.chShowFeedLoadingOverlay = chShowFeedLoadingOverlay;
                window.chHideFeedLoadingOverlay = chHideFeedLoadingOverlay;

                window.chLoadFeedShell = chLoadFeedShell;
                window.chHandleFeedNavClick = function (link, event) {
                    if (!chCanSoftLoadFeedLink(link)) return true;
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    if (chCanAjaxLoadFeedLink(link)) {
                        chShowFeedLoadingOverlay();
                        chAjaxLoadFeedLink(link, true).catch(function () {
                            window.location.href = link.href;
                        }).finally(function () {
                            chHideFeedLoadingOverlay();
                        });
                        return false;
                    }

                    if (link.dataset.chFeedNav === 'my_feed' && typeof window.chLoadMyFeedShell === 'function') {
                        chShowFeedLoadingOverlay();
                        window.chLoadMyFeedShell(link.href, true).catch(function () {
                            window.location.href = link.href;
                        }).finally(function () {
                            chHideFeedLoadingOverlay();
                        });
                        return false;
                    }

                    chShowFeedLoadingOverlay();
                    chLoadFeedShell(link.href, true).catch(function () {
                        window.location.href = link.href;
                    }).finally(function () {
                        chHideFeedLoadingOverlay();
                    });
                    return false;
                };

                document.addEventListener('click', function (e) {
                    if (e.defaultPrevented) return;
                    if (e.button !== 0) return;
                    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                    var feedLink = e.target.closest('.ch-top-nav .ch-nav-link[href]');
                    if (!feedLink) return;
                    if (!document.querySelector('.ch-feed-shell')) return;

                    if (chCanAjaxLoadFeedLink(feedLink)) {
                        e.preventDefault();
                        chAjaxLoadFeedLink(feedLink, true).catch(function () {
                            window.location.href = feedLink.href;
                        });
                        return;
                    }

                    if (!chCanSoftLoadFeedLink(feedLink)) return;

                    e.preventDefault();
                    chLoadFeedShell(feedLink.href, true).catch(function () {
                        window.location.href = feedLink.href;
                    });
                });

                window.addEventListener('popstate', function () {
                    if (!document.querySelector('.ch-feed-shell')) return;
                    chLoadFeedShell(window.location.href, false).catch(function () {
                        window.location.reload();
                    });
                });
            })();

        })();
    </script>
    <?php
    return ob_get_clean();
}

function ch_feed_scripts()
{
    ob_start(); ?>
    <script>
        (function () {
            window.chOpenModal = window.chOpenModal || function (id) { document.getElementById(id).style.display = 'flex'; };
            window.chCloseModal = window.chCloseModal || function (id) { document.getElementById(id).style.display = 'none'; };

            window.chShowGuidelines = function () { chOpenModal('ch-modal-guidelines'); };

            window.chVote = function (btn, nonce) {
                const targetId = btn.dataset.id;
                const targetType = btn.dataset.type;
                const value = parseInt(btn.dataset.val);

                const fd = new FormData();
                fd.append('action', 'ch_vote');
                fd.append('target_id', targetId);
                fd.append('target_type', targetType);
                fd.append('value', value);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            // Update vote count displays
                            document.querySelectorAll('.ch-vote-count, .ch-vote-score').forEach(el => {
                                const inCard = el.closest('[data-id="' + targetId + '"]');
                                const isScore = el.id === 'ch-post-score' && targetType === 'post';
                                if (inCard || isScore) {
                                    el.textContent = json.data.vote_count + (isScore ? ' points' : '');
                                }
                            });
                            // Also update inline comment vote count (text node inside ch-vote-up button)
                            if (targetType === 'comment') {
                                const upBtn = document.querySelector('.ch-vote-up[data-id="' + targetId + '"]');
                                if (upBtn) {
                                    const textNode = [...upBtn.childNodes].find(n => n.nodeType === 3);
                                    if (textNode) textNode.textContent = ' ' + json.data.vote_count;
                                }
                            }

                            // Find the sibling up/down buttons for this target
                            const upBtn = document.querySelector('.ch-vote-up[data-id="' + targetId + '"]');
                            const downBtn = document.querySelector('.ch-vote-down[data-id="' + targetId + '"]');

                            // Clear both active states first
                            upBtn?.classList.remove('active-up');
                            downBtn?.classList.remove('active-down');

                            // Apply the correct active state based on what the server says
                            if (json.data.action === 'voted' || json.data.action === 'changed') {
                                if (value === 1) upBtn?.classList.add('active-up');
                                if (value === -1) downBtn?.classList.add('active-down');
                            }
                            // action === 'removed' → both stay cleared (already done above)
                        } else {
                            alert(json.data?.message || 'Vote failed');
                        }
                    })
                    .catch(error => {
                        console.error('Vote request failed:', error);
                        alert('Network error. Please try again.');
                    });
            };

            window.chBookmark = function (postId, btn, nonce) {
                if (btn) btn.disabled = true;
                const fd = new FormData();
                fd.append('action', 'ch_bookmark_post');
                fd.append('post_id', postId);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            const isBookmarked = json.data.bookmarked;
                            const iconSaved = '<svg width="14" height="14" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';
                            const iconDefault = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';
                            const html = isBookmarked ? (iconSaved + ' Saved') : (iconDefault + ' Save');

                            document.querySelectorAll('.ch-bookmark-btn[data-post-id="' + postId + '"]').forEach(el => {
                                el.classList.toggle('ch-bookmarked', isBookmarked);
                                el.innerHTML = html;
                            });
                            if (btn && !btn.classList.contains('ch-bookmark-btn')) {
                                btn.classList.toggle('ch-bookmarked', isBookmarked);
                                btn.innerHTML = html;
                            }

                            // In bookmarks view, removing bookmark should remove the card live.
                            if (!isBookmarked && document.querySelector('.ch-bookmarks-hero')) {
                                document.querySelectorAll('.ch-special-main .ch-post-card[data-id="' + postId + '"]').forEach(card => card.remove());

                                const remaining = document.querySelectorAll('.ch-special-main .ch-post-card').length;
                                const sub = document.querySelector('.ch-bookmarks-hero .ch-special-hero-sub');
                                if (sub) sub.textContent = remaining + ' saved post' + (remaining === 1 ? '' : 's');
                                if (remaining === 0) {
                                    const main = document.querySelector('.ch-special-main');
                                    if (main) {
                                        main.innerHTML = '<div class="ch-empty-state"><svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5"><path d="M19 21l-7-5-7 5V5a2 2 0 0 0-2 2h10a2 2 0 0 1 2 2z"/></svg><p>No bookmarks yet. Save posts you want to come back to!</p></div>';
                                    }
                                }
                            }
                        }
                    })
                    .finally(() => { if (btn) btn.disabled = false; });
            };

            window.chToggleBookmark = function (btn, nonce) {
                const postId = parseInt(btn?.dataset?.postId || '0', 10);
                if (!postId) return;
                window.chBookmark(postId, btn, nonce);
            };

            window.chToggleFollowCategory = function (categoryId, btn, nonce) {
                if (btn) btn.disabled = true;
                const followCategoryLimit = parseInt(window.chFollowedCategoryLimit || 8, 10);
                const fd = new FormData();
                fd.append('action', 'ch_follow_category');
                fd.append('category_id', categoryId);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (!json.success) {
                            alert(json.data?.message || 'Failed to update follow status');
                            return;
                        }

                        const following = json.data.following;
                        const toggleButtons = document.querySelectorAll('.ch-follow-toggle[data-follow-cat-id="' + categoryId + '"]');
                        toggleButtons.forEach(b => {
                            b.textContent = following ? 'Following' : 'Follow';
                            b.classList.toggle('ch-btn-outline', following);
                            b.classList.toggle('ch-btn-primary', !following);
                        });
                        if (btn && !btn.classList.contains('ch-follow-toggle')) {
                            btn.textContent = following ? 'Following' : 'Follow';
                            btn.classList.toggle('ch-btn-outline', following);
                            btn.classList.toggle('ch-btn-primary', !following);
                        }

                        document.querySelectorAll('.ch-cat-followers[data-cat-id="' + categoryId + '"]').forEach(countEl => {
                            const current = parseInt((countEl.textContent || '').replace(/[^\d]/g, ''), 10) || 0;
                            const next = following ? current + 1 : Math.max(0, current - 1);
                            countEl.textContent = String(next);
                        });

                        const followedWrap = document.getElementById('ch-followed-categories');
                        const followedList = document.getElementById('ch-followed-categories-list');
                        const sourceBtn = btn || document.querySelector('.ch-follow-toggle[data-follow-cat-id="' + categoryId + '"]');
                        const catSlug = sourceBtn?.dataset.followCatSlug || '';
                        const catName = sourceBtn?.dataset.followCatName || '';
                        const catColor = sourceBtn?.dataset.followCatColor || '#FF7551';

                        if (followedWrap && followedList && catSlug && catName) {
                            const selector = 'a[data-followed-cat-id="' + categoryId + '"]';
                            const existing = followedList.querySelector(selector);

                            if (following && !existing) {
                                const a = document.createElement('a');
                                a.href = '?cat=' + encodeURIComponent(catSlug);
                                a.className = 'ch-cat-link';
                                a.style.fontSize = '14px';
                                a.setAttribute('data-followed-cat-id', String(categoryId));
                                a.innerHTML = '<span class="ch-cat-dot" style="background:' + catColor + '"></span><span class="ch-cat-name">' + catName + '</span>';
                                followedList.insertBefore(a, followedList.firstChild || null);

                                while (followedList.querySelectorAll('a[data-followed-cat-id]').length > followCategoryLimit) {
                                    followedList.removeChild(followedList.lastElementChild);
                                }
                            }
                            if (!following && existing) {
                                existing.remove();
                            }
                            followedWrap.style.display = followedList.querySelectorAll('a[data-followed-cat-id]').length ? '' : 'none';
                        }
                    })
                    .finally(() => { if (btn) btn.disabled = false; });
            };

            // ---- @mention autocomplete ----
            (function () {
                let _mentionDropdown = null;
                let _mentionTextarea = null;
                let _mentionStart = -1;
                let _mentionActive = -1;
                let _debounceTimer = null;

                function createDropdown() {
                    if (_mentionDropdown) return;
                    _mentionDropdown = document.createElement('div');
                    _mentionDropdown.className = 'ch-mention-dropdown';
                    _mentionDropdown.style.display = 'none';
                    document.body.appendChild(_mentionDropdown);
                }

                function positionDropdown(textarea) {
                    // Approximate caret position using a mirror div
                    const style = window.getComputedStyle(textarea);
                    const mirror = document.createElement('div');
                    mirror.style.cssText = [
                        'position:absolute', 'visibility:hidden', 'overflow:auto',
                        'white-space:pre-wrap', 'word-wrap:break-word',
                        `width:${textarea.clientWidth}px`,
                        `font:${style.font}`,
                        `padding:${style.padding}`,
                        `border:${style.border}`,
                        `line-height:${style.lineHeight}`,
                    ].join(';');
                    const text = textarea.value.substring(0, _mentionStart);
                    mirror.textContent = text;
                    const cursor = document.createElement('span');
                    cursor.textContent = '|';
                    mirror.appendChild(cursor);
                    document.body.appendChild(mirror);

                    const taRect = textarea.getBoundingClientRect();
                    const mirrorRect = mirror.getBoundingClientRect();
                    const cursorRect = cursor.getBoundingClientRect();
                    document.body.removeChild(mirror);

                    const top = taRect.top + window.scrollY + (cursorRect.top - mirrorRect.top) + parseInt(style.lineHeight || 20);
                    const left = taRect.left + window.scrollX + (cursorRect.left - mirrorRect.left);

                    _mentionDropdown.style.top = Math.min(top, window.scrollY + window.innerHeight - 200) + 'px';
                    _mentionDropdown.style.left = Math.min(left, window.scrollX + window.innerWidth - 290) + 'px';
                }

                function showResults(users, query) {
                    _mentionActive = -1;
                    if (!users.length) {
                        _mentionDropdown.innerHTML = `<div class="ch-mention-no-results">No users found for "@${query}"</div>`;
                    } else {
                        _mentionDropdown.innerHTML = users.map((u, i) => `
                        <div class="ch-mention-item" data-username="${u.username}" data-index="${i}">
                            <div class="ch-mention-item-avatar">${u.display_name.charAt(0).toUpperCase()}</div>
                            <div>
                                <div class="ch-mention-item-name">${u.display_name}</div>
                                <div class="ch-mention-item-handle">@${u.username}</div>
                            </div>
                        </div>`).join('');
                        _mentionDropdown.querySelectorAll('.ch-mention-item').forEach(item => {
                            item.addEventListener('mousedown', e => {
                                e.preventDefault();
                                insertMention(item.dataset.username);
                            });
                        });
                    }
                    _mentionDropdown.style.display = 'block';
                }

                function hideDropdown() {
                    if (_mentionDropdown) _mentionDropdown.style.display = 'none';
                    _mentionStart = -1;
                    _mentionActive = -1;
                }

                function insertMention(username) {
                    if (!_mentionTextarea || _mentionStart < 0) return;
                    const val = _mentionTextarea.value;
                    const before = val.substring(0, _mentionStart);
                    const after = val.substring(_mentionTextarea.selectionStart);
                    _mentionTextarea.value = before + '@' + username + ' ' + after;
                    const pos = _mentionStart + username.length + 2;
                    _mentionTextarea.setSelectionRange(pos, pos);
                    _mentionTextarea.focus();
                    hideDropdown();
                }

                function fetchUsers(query) {
                    const fd = new FormData();
                    fd.append('action', 'ch_mention_search');
                    fd.append('query', query);
                    fetch(chAjaxUrl, { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(json => {
                            if (json.success && _mentionStart >= 0) {
                                showResults(json.data.users, query);
                            }
                        })
                        .catch(() => hideDropdown());
                }

                function onKeydown(e) {
                    if (_mentionDropdown?.style.display !== 'block') return;
                    const items = _mentionDropdown.querySelectorAll('.ch-mention-item');
                    if (!items.length) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        _mentionActive = Math.min(_mentionActive + 1, items.length - 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        _mentionActive = Math.max(_mentionActive - 1, 0);
                    } else if (e.key === 'Enter' || e.key === 'Tab') {
                        if (_mentionActive >= 0 && items[_mentionActive]) {
                            e.preventDefault();
                            insertMention(items[_mentionActive].dataset.username);
                            return;
                        }
                    } else if (e.key === 'Escape') {
                        hideDropdown();
                        return;
                    } else {
                        return;
                    }
                    items.forEach((item, i) => item.classList.toggle('active', i === _mentionActive));
                }

                function onInput(e) {
                    const ta = e.target;
                    const val = ta.value;
                    const pos = ta.selectionStart;

                    // Find the @ that starts the current mention token
                    let start = -1;
                    for (let i = pos - 1; i >= 0; i--) {
                        if (val[i] === '@') { start = i; break; }
                        if (/\s/.test(val[i])) break;
                    }

                    if (start < 0) { hideDropdown(); return; }

                    const query = val.substring(start + 1, pos);
                    if (query.length === 0) { hideDropdown(); return; }

                    _mentionTextarea = ta;
                    _mentionStart = start;
                    _mentionDropdown.style.display = 'block';
                    positionDropdown(ta);

                    clearTimeout(_debounceTimer);
                    _debounceTimer = setTimeout(() => fetchUsers(query), 180);
                }

                // Attach to existing and future textareas via delegation
                createDropdown();
                document.addEventListener('input', e => { if (e.target.matches('textarea')) onInput(e); });
                document.addEventListener('keydown', e => { if (e.target.matches('textarea')) onKeydown(e); });
                document.addEventListener('click', e => { if (!e.target.closest('.ch-mention-dropdown') && !e.target.matches('textarea')) hideDropdown(); });
                document.addEventListener('focusout', e => { if (e.target.matches('textarea')) setTimeout(hideDropdown, 150); });
            })();

            window.chReportPost = function (postId, nonce) {
                document.getElementById('ch-report-target-id').value = postId;
                document.getElementById('ch-report-target-type').value = 'post';
                chOpenModal('ch-modal-report');
            };

            window.chReportComment = function (commentId, nonce) {
                document.getElementById('ch-report-target-id').value = commentId;
                document.getElementById('ch-report-target-type').value = 'comment';
                chOpenModal('ch-modal-report');
            };

            window.chSubmitReport = function (nonce) {
                const reason = document.getElementById('ch-report-reason').value;
                const details = document.getElementById('ch-report-details').value;
                const tid = document.getElementById('ch-report-target-id').value;
                const ttype = document.getElementById('ch-report-target-type').value;

                if (!reason) { alert('Please select a reason'); return; }

                const fd = new FormData();
                fd.append('action', 'ch_report');
                fd.append('target_type', ttype);
                fd.append('target_id', tid);
                fd.append('reason', reason);
                fd.append('details', details);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        document.getElementById('ch-report-msg').innerHTML =
                            '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + (json.data?.message || '') + '</div>';
                        if (json.success) setTimeout(() => chCloseModal('ch-modal-report'), 1500);
                    });
            };

            // ---- Profile & Notifications dropdowns ----
            let _dropdownJustOpened = false;

            function positionDropdown(menu, btn) {
                const rect = btn.getBoundingClientRect();
                menu.style.top = (rect.bottom + 6) + 'px';
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                menu.style.left = 'auto';
            }

            // Close dropdowns on outside click
            document.addEventListener('click', function (e) {
                if (_dropdownJustOpened) { _dropdownJustOpened = false; return; }
                if (!e.target.closest('.ch-profile-dropdown')) {
                    const m = document.getElementById('ch-profile-menu');
                    if (m) m.style.display = 'none';
                }
                if (!e.target.closest('.ch-notifications-dropdown')) {
                    const m = document.getElementById('ch-notifications-menu');
                    if (m) m.style.display = 'none';
                }

                const removeBtn = e.target.closest('.ch-composer-media-remove');
                if (removeBtn) {
                    if (removeBtn.dataset.existingIndex !== undefined) {
                        window.chRemoveComposerExistingMedia(
                            removeBtn.dataset.inputId,
                            removeBtn.dataset.previewId,
                            removeBtn.dataset.labelId,
                            parseInt(removeBtn.dataset.existingIndex || '-1', 10)
                        );
                    } else {
                        window.chRemoveComposerMedia(
                            removeBtn.dataset.inputId,
                            removeBtn.dataset.previewId,
                            removeBtn.dataset.labelId,
                            parseInt(removeBtn.dataset.index || '-1', 10)
                        );
                    }
                }
            });

            // ---- Notification count on load ----

            // Load count on page load
            if (document.getElementById('ch-notification-count') && !window.__chNotificationCountInitialized) {
                window.__chNotificationCountInitialized = true;
                chLoadNotificationCount();
            }

            window.chSubmitPost = function (nonce) {
                if (window.chIsSubmittingPost) return;
                const title = document.getElementById('ch-post-title').value.trim();
                const content = document.getElementById('ch-post-content').value.trim();
                const catIdRaw = document.getElementById('ch-post-cat').value;
                const catId = parseInt(catIdRaw, 10);
                const tags = document.getElementById('ch-post-tags').value;
                const anonEl = document.getElementById('ch-post-anon');
                const isAnon = anonEl ? (anonEl.checked ? 1 : 0) : 1;
                const guestEl = document.getElementById('ch-post-guest-name');
                const guestName = guestEl ? guestEl.value.trim() : '';

                if (!title || !content || !Number.isInteger(catId) || catId <= 0) { alert('Please fill in all required fields'); return; }

                // Block guest from posting to private categories (client-side hint)
                const catSelect = document.getElementById('ch-post-cat');
                const selOpt = catSelect?.options[catSelect.selectedIndex];
                if (selOpt && selOpt.dataset.private === '1' && !nonce) {
                    alert('This is a private category. Please sign in and follow it to post.');
                    return;
                }

                const btn = document.getElementById('ch-submit-post-btn');
                window.chIsSubmittingPost = true;
                btn.disabled = true;
                window.chShowLoadingModal('Posting to the community feed...', 'Creating Post');

                const fd = new FormData();
                fd.append('action', 'ch_create_post');
                fd.append('title', title);
                fd.append('content', content);
                fd.append('category_id', String(catId));
                fd.append('tags', tags);
                fd.append('is_anonymous', isAnon);
                if (guestName) fd.append('guest_name', guestName);
                if (nonce) fd.append('nonce', nonce);

                const media = window.chComposerFiles['ch-post-media'] || Array.from(document.getElementById('ch-post-media').files || []);
                const mediaLimit = parseInt(window.chMediaUploadLimit || 6, 10);
                if (media.length > mediaLimit) {
                    window.chHideLoadingModal();
                    (window.chResetButton || chResetButton)(btn, 'Post to Community');
                    window.chIsSubmittingPost = false;
                    window.chOpenWarningModal({
                        title: 'Upload Limit Reached',
                        message: 'You can upload up to ' + mediaLimit + ' files per post.',
                        buttonLabel: 'Okay'
                    });
                    return;
                }
                for (let file of media) {
                    fd.append('media[]', file);
                }

                const endpoint = window.chAjaxUrl || window.ajaxurl;
                if (!endpoint) {
                    chSetNotice('ch-post-msg', 'error', 'Posting endpoint is unavailable. Please reload the page and try again.');
                    chResetButton(btn, 'Post to Community');
                    window.chHideLoadingModal();
                    window.chIsSubmittingPost = false;
                    return;
                }

                (window.chFetchJson || chFetchJson)(endpoint, { method: 'POST', body: fd })
                    .then(json => {
                        (window.chSetNotice || chSetNotice)('ch-post-msg', json.success ? 'success' : 'error', json.data?.message || '');
                        if (json.success) {
                            delete window.chComposerFiles['ch-post-media'];
                            window.chHideLoadingModal();
                            if (window.chCloseModal) window.chCloseModal('ch-modal-create-post');
                            (window.chResetButton || chResetButton)(btn, 'Post');
                            window.chIsSubmittingPost = false;
                            // AJAX reload feed to show new post
                            chAjaxReloadFeed();
                            return;
                        }
                        window.chHideLoadingModal();
                        (window.chResetButton || chResetButton)(btn, 'Post to Community');
                        window.chIsSubmittingPost = false;
                    })
                    .catch((err) => {
                        window.chHideLoadingModal();
                        (window.chSetNotice || chSetNotice)('ch-post-msg', 'error', err?.message || 'Failed to submit post.');
                        (window.chResetButton || chResetButton)(btn, 'Post to Community');
                        window.chIsSubmittingPost = false;
                    });
            };

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ch-share-dropdown')) {
                    document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
                }
            });

            window.chOpenEditPostModal = function (postId) {
                // Load post data via AJAX
                const fd = new FormData();
                fd.append('action', 'ch_get_post_detail');
                fd.append('post_id', postId);

                (window.chFetchJson || chFetchJson)(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(json => {
                        if (json.success) {
                            const post = json.data.post;
                            const mediaInputId = window.chGetFirstExistingId(['ch-edit-post-media']);
                            const mediaPreviewId = window.chGetFirstExistingId(['ch-edit-post-media-preview', 'ch-edit-post-media-preview2']);
                            const mediaLabelId = window.chGetFirstExistingId(['ch-edit-post-media-label', 'ch-edit-post-media-label2']);
                            const editIdEl = document.getElementById('ch-edit-post-id');
                            const catEl = document.getElementById('ch-edit-post-cat');
                            const titleEl = document.getElementById('ch-edit-post-title');
                            const contentEl = document.getElementById('ch-edit-post-content');
                            const tagsEl = document.getElementById('ch-edit-post-tags');
                            const anonEl = document.getElementById('ch-edit-post-anon');

                            if (!editIdEl || !catEl || !titleEl || !contentEl || !tagsEl) {
                                throw new Error('Edit modal fields are missing on this page.');
                            }

                            editIdEl.value = post.id;
                            catEl.value = post.category_id;
                            titleEl.value = post.title;
                            contentEl.value = post.content;
                            tagsEl.value = post.tags;
                            if (anonEl) anonEl.checked = !!post.is_anonymous;
                            window.chComposerFiles['ch-edit-post-media'] = [];
                            window.chSyncComposerInput('ch-edit-post-media', []);
                            try {
                                const parsedMedia = JSON.parse(post.media_urls || '[]');
                                window.chComposerExistingMedia['ch-edit-post-media'] = Array.isArray(parsedMedia)
                                    ? parsedMedia.filter(Boolean).map((url) => ({
                                        url: url,
                                        type: (function (path) {
                                            const clean = String(path || '').split('?')[0].toLowerCase();
                                            if (/\.(jpg|jpeg|png|gif|webp|bmp|svg)$/.test(clean)) return 'image';
                                            if (/\.(mp4|webm|ogg|mov|m4v)$/.test(clean)) return 'video';
                                            if (/\.(mp3|wav|ogg|m4a)$/.test(clean)) return 'audio';
                                            return 'file';
                                        })(url)
                                    }))
                                    : [];
                            } catch (e) {
                                window.chComposerExistingMedia['ch-edit-post-media'] = [];
                            }
                            window.chRenderComposerMediaPreview(mediaInputId, mediaPreviewId, mediaLabelId);
                            chOpenModal('ch-modal-edit-post');
                        } else {
                            alert(json.data?.message || 'Failed to load post data');
                        }
                    })
                    .catch(error => {
                        console.error('Failed to load post:', error);
                        alert(error?.message || 'Failed to load edit modal');
                    });
            };

            window.chUpdatePost = function (nonce) {
                const id = document.getElementById('ch-edit-post-id').value;
                const title = document.getElementById('ch-edit-post-title').value.trim();
                const content = document.getElementById('ch-edit-post-content').value.trim();
                const catId = document.getElementById('ch-edit-post-cat').value;
                const tags = document.getElementById('ch-edit-post-tags').value;
                const isAnon = document.getElementById('ch-edit-post-anon').checked ? 1 : 0;

                if (!title || !content || !catId) { alert('Please fill in all required fields'); return; }

                const btn = document.getElementById('ch-update-post-btn');
                btn.disabled = true;
                window.chShowLoadingModal('Saving your post changes...', 'Updating Post');

                const fd = new FormData();
                fd.append('action', 'ch_edit_post');
                fd.append('post_id', id);
                fd.append('title', title);
                fd.append('content', content);
                fd.append('category_id', catId);
                fd.append('tags', tags);
                fd.append('is_anonymous', isAnon);
                fd.append('nonce', nonce);

                const mediaInputId = window.chGetFirstExistingId(['ch-edit-post-media']);
                const media = window.chComposerFiles[mediaInputId] || Array.from(document.getElementById(mediaInputId)?.files || []);
                const existingMedia = (window.chComposerExistingMedia[mediaInputId] || []).map((item) => item.url).filter(Boolean);
                const mediaLimit = parseInt(window.chMediaUploadLimit || 6, 10);
                if (media.length + existingMedia.length > mediaLimit) {
                    window.chHideLoadingModal();
                    btn.disabled = false;
                    btn.textContent = 'Update Post';
                    document.getElementById('ch-edit-post-msg').innerHTML =
                        '<div class="bntm-notice bntm-notice-error">You can upload up to ' + mediaLimit + ' files per post.</div>';
                    window.chOpenWarningModal({
                        title: 'Upload Limit Reached',
                        message: 'You can upload up to ' + mediaLimit + ' files per post.',
                        buttonLabel: 'Okay'
                    });
                    return;
                }
                fd.append('existing_media_urls', JSON.stringify(existingMedia));
                for (let file of media) {
                    fd.append('media[]', file);
                }

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (!json.success) {
                            window.chHideLoadingModal();
                        }
                        document.getElementById('ch-edit-post-msg').innerHTML =
                            '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + (json.data?.message || '') + '</div>';
                        if (json.success) {
                            window.chHideLoadingModal();
                            if (window.chCloseModal) window.chCloseModal('ch-modal-edit-post');
                            btn.disabled = false;
                            btn.textContent = 'Save Changes';
                            // AJAX reload feed to show updated post
                            chAjaxReloadFeed();
                        }
                        else { btn.disabled = false; btn.textContent = 'Update Post'; }
                    })
                    .catch(() => {
                        window.chHideLoadingModal();
                        btn.disabled = false;
                        btn.textContent = 'Update Post';
                        document.getElementById('ch-edit-post-msg').innerHTML =
                            '<div class="bntm-notice bntm-notice-error">Failed to update post.</div>';
                    });
            };

            window.chDeletePost = async function (id, nonce) {
                const confirmed = await window.chOpenConfirmModal({
                    title: 'Delete Post',
                    message: 'This post will be removed from the feed. This action cannot be undone.',
                    confirmLabel: 'Delete Post',
                    confirmClass: 'ch-btn-danger'
                });
                if (!confirmed) return;

                const fd = new FormData();
                fd.append('action', 'ch_delete_post');
                fd.append('post_id', id);
                fd.append('nonce', nonce);
                window.chShowLoadingModal('Removing this post...', 'Deleting Post');

                (window.chFetchJson || chFetchJson)(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(json => {
                        if (json.success) {
                            window.chHideLoadingModal();
                            window.location.href = window.chFeedUrl || (typeof chFeedUrl !== 'undefined' ? chFeedUrl : '/forum-feed/');
                        } else {
                            window.chHideLoadingModal();
                            alert(json.data?.message || 'Error deleting post');
                        }
                    })
                    .catch(error => {
                        window.chHideLoadingModal();
                        alert(error?.message || 'Error deleting post');
                    });
            };

            // ---- Nav item instant active feedback on click ----
            document.addEventListener('click', function (e) {
                const navItem = e.target.closest('.ch-nav-item');
                if (navItem && !navItem.classList.contains('active')) {
                    // Optimistically mark clicked item as active for instant feel
                    document.querySelectorAll('.ch-nav-item').forEach(n => n.classList.remove('active'));
                    navItem.classList.add('active');
                }
            });

            // Close modals on overlay click
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('ch-modal-overlay')) {
                    e.target.style.display = 'none';
                }
            });

            window.chUpdateProfile = window.chUpdateProfile || function (nonce) {
                const displayName = (document.getElementById('ch-profile-display-name')?.value || '').trim();
                const bio = (document.getElementById('ch-profile-bio')?.value || '').trim();
                const location = (document.getElementById('ch-profile-location')?.value || '').trim();
                const isAnonymous = document.getElementById('ch-profile-anonymous')?.checked ? 1 : 0;
                const btn = document.querySelector('#ch-profile-form .ch-btn-primary') || event?.target;
                const msgEl = document.getElementById('ch-profile-msg');

                const fd = new FormData();
                fd.append('action', 'ch_update_profile');
                fd.append('display_name', displayName);
                fd.append('bio', bio);
                fd.append('location', location);
                fd.append('is_anonymous', isAnonymous);
                fd.append('nonce', nonce);

                const avatar = document.getElementById('ch-profile-avatar')?.files[0];
                if (avatar) fd.append('avatar', avatar);

                if (btn) { btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving…</span>'; }

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (msgEl) msgEl.innerHTML = '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + (json.data?.message || '') + '</div>';
                        if (json.success) {
                            // Update profile UI inline without reload
                            if (json.data.profile) {
                                const nameEl = document.getElementById('ch-profile-display-name');
                                if (nameEl) nameEl.value = json.data.profile.display_name || '';
                                const avatarInitials = document.getElementById('ch-avatar-preview-initials');
                                if (avatarInitials && json.data.profile.display_name) {
                                    avatarInitials.textContent = json.data.profile.display_name.charAt(0).toUpperCase();
                                }
                            }
                            // Update nav bar user info if exists
                            const navUserName = document.querySelector('.ch-nav-user-name');
                            if (navUserName && json.data.profile?.display_name) {
                                navUserName.textContent = json.data.profile.display_name;
                            }
                        } else {
                            if (btn) { btn.disabled = false; btn.textContent = 'Save Profile'; }
                        }
                    })
                    .catch(() => {
                        if (msgEl) msgEl.innerHTML = '<div class="bntm-notice bntm-notice-error">Network error.</div>';
                        if (btn) { btn.disabled = false; btn.textContent = 'Save Profile'; }
                    });
            };
        })();
    </script>

    <script>
        // ── Category sidebar AJAX (no full-page reload) ──────────────────────
        (function () {
            var state = window.chFeedState;
            if (!state) return;

            var list = document.getElementById('ch-posts-list');
            if (!list) return;

            function chFeedRequest(params, onSuccess) {
                if (window.chNavBarStart) window.chNavBarStart();
                list.style.opacity = '0.45';
                list.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_feed_sort');
                fd.append('nonce', state.nonce);
                fd.append('sort', params.sort);
                fd.append('cat', params.cat);
                fd.append('s', params.s);
                fd.append('location', params.location);
                if (params.bookmarks) fd.append('bookmarks', 1);
                fd.append('paged', params.paged || 1);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!json.success) {
                            if (typeof onSuccess === 'function') onSuccess(false);
                            return;
                        }
                        list.innerHTML = json.data.html !== undefined ? json.data.html : '';
                        var headerInner = document.getElementById('ch-feed-header-inner');
                        if (headerInner && json.data.header !== undefined) headerInner.innerHTML = json.data.header;

                        var paginationWrap = document.getElementById('ch-feed-pagination-wrap');
                        if (paginationWrap) {
                            paginationWrap.innerHTML = json.data.pagination !== undefined ? json.data.pagination : '';
                        }

                        if (typeof onSuccess === 'function') onSuccess(true);
                    })
                    .catch(function () {
                        if (typeof onSuccess === 'function') onSuccess(false);
                    })
                    .finally(function () {
                        list.style.opacity = '';
                        list.style.pointerEvents = '';
                        if (window.chNavBarFinish) window.chNavBarFinish();
                    });
            }

            function loadCat(slug) {
                // Update active state on all [data-cat-slug] links
                document.querySelectorAll('[data-cat-slug]').forEach(function (el) {
                    el.classList.toggle('active', el.dataset.catSlug === slug);
                });

                // Reset sort tabs to "new" when switching category
                var tabs = document.getElementById('ch-sort-tabs');
                if (tabs) {
                    tabs.querySelectorAll('.ch-sort-tab').forEach(function (t) {
                        t.classList.toggle('active', t.dataset.sort === 'new');
                    });
                }

                chFeedRequest({
                    sort: 'new',
                    cat: slug,
                    s: state.s,
                    location: state.location,
                    paged: 1
                }, function () {
                    state.cat = slug;
                    state.sort = 'new';
                    state.paged = 1;

                    var url = new URL(window.location.href);
                    if (slug) {
                        url.searchParams.set('cat', slug);
                    } else {
                        url.searchParams.delete('cat');
                    }
                    url.searchParams.delete('sort');
                    if (state.s) url.searchParams.set('s', state.s);
                    else url.searchParams.delete('s');
                    if (state.location) url.searchParams.set('location', state.location);
                    else url.searchParams.delete('location');
                    url.searchParams.delete('paged');
                    history.replaceState(null, '', url.toString());
                });
            }

            document.addEventListener('click', function (e) {
                var link = e.target.closest('[data-cat-slug]');
                if (!link) return;
                e.preventDefault();
                loadCat(link.dataset.catSlug);
            });
        })();
    </script>
    <script>
        // ── Sort-tab AJAX (no full-page reload) ──────────────────────────────
        (function () {
            var state = window.chFeedState;
            if (!state) return;

            var list = document.getElementById('ch-posts-list');
            var tabs = document.getElementById('ch-sort-tabs');
            if (!list || !tabs) return;

            function loadFeedResults(nextState) {
                if (window.chNavBarStart) window.chNavBarStart();
                list.style.opacity = '0.45';
                list.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_feed_sort');
                fd.append('nonce', state.nonce);
                fd.append('sort', nextState.sort);
                fd.append('cat', nextState.cat);
                fd.append('s', nextState.s);
                fd.append('location', nextState.location);
                if (nextState.bookmarks) fd.append('bookmarks', 1);
                fd.append('paged', 1);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!json.success) return;

                        list.innerHTML = json.data.html !== undefined ? json.data.html : '';
                        var headerInner = document.getElementById('ch-feed-header-inner');
                        if (headerInner && json.data.header !== undefined) headerInner.innerHTML = json.data.header;

                        // Update pagination
                        var paginationWrap = document.getElementById('ch-feed-pagination-wrap');
                        if (paginationWrap) {
                            paginationWrap.innerHTML = json.data.pagination !== undefined ? json.data.pagination : '';
                        }

                        state.sort = nextState.sort;
                        state.cat = nextState.cat;
                        state.s = nextState.s;
                        state.location = nextState.location;
                        state.paged = 1;

                        var url = new URL(window.location.href);
                        if (nextState.sort && nextState.sort !== 'new') url.searchParams.set('sort', nextState.sort);
                        else url.searchParams.delete('sort');
                        if (nextState.cat) url.searchParams.set('cat', nextState.cat);
                        else url.searchParams.delete('cat');
                        if (nextState.s) url.searchParams.set('s', nextState.s);
                        else url.searchParams.delete('s');
                        if (nextState.location) url.searchParams.set('location', nextState.location);
                        else url.searchParams.delete('location');
                        url.searchParams.delete('paged');
                        history.replaceState(null, '', url.toString());
                    })
                    .catch(function () {
                        // Silent fail - controls remain usable for retry.
                    })
                    .finally(function () {
                        list.style.opacity = '';
                        list.style.pointerEvents = '';
                        if (window.chNavBarFinish) window.chNavBarFinish();
                    });
            }

            tabs.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-sort]');
                if (!btn || btn.classList.contains('active')) return;

                var sort = btn.dataset.sort;
                var currentActive = tabs.querySelector('.ch-sort-tab.active');

                // Skeleton loading state
                list.style.opacity = '0.45';
                list.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_feed_sort');
                fd.append('nonce', state.nonce);
                fd.append('sort', sort);
                fd.append('cat', state.cat);
                fd.append('s', state.s);
                fd.append('location', state.location);
                if (state.bookmarks) fd.append('bookmarks', 1);
                fd.append('paged', 1);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!json.success) {
                            return;
                        }

                        if (currentActive) currentActive.classList.remove('active');
                        btn.classList.add('active');

                        list.innerHTML = json.data.html !== undefined ? json.data.html : '';
                        var headerInner = document.getElementById('ch-feed-header-inner');
                        if (headerInner && json.data.header !== undefined) headerInner.innerHTML = json.data.header;

                        var paginationWrap = document.getElementById('ch-feed-pagination-wrap');
                        if (paginationWrap) {
                            paginationWrap.innerHTML = json.data.pagination !== undefined ? json.data.pagination : '';
                        }

                        state.sort = sort;
                        state.paged = 1;

                        // Update URL without reload
                        var url = new URL(window.location.href);
                        url.searchParams.set('sort', sort);
                        url.searchParams.delete('paged');
                        history.replaceState(null, '', url.toString());
                    })
                    .catch(function () {
                        // Silent fail — controls still show active state, user can retry
                    })
                    .finally(function () {
                        list.style.opacity = '';
                        list.style.pointerEvents = '';
                    });
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}

function ch_post_view_scripts()
{
    ob_start(); ?>
    <script>
        (function () {

            window.chSubmitComment = function (postId, parentId, nonce) {
                let contentEl;
                if (parentId > 0) {
                    contentEl = document.getElementById('ch-reply-content-' + parentId);
                } else {
                    contentEl = document.getElementById('ch-comment-content');
                }

                const content = contentEl ? contentEl.value.trim() : '';
                if (!content) { alert('Please enter your comment'); return; }

                const isAnon = document.getElementById('ch-comment-anon')?.checked ? 1 : 0;

                // Find the submit button closest to the textarea
                const btn = contentEl ? contentEl.closest('.ch-comment-form, .ch-reply-form')?.querySelector('.ch-btn-primary') : null;
                const restore = btn ? chBtnLoading(btn, 'Posting...') : () => { };

                const fd = new FormData();
                fd.append('action', 'ch_add_comment');
                fd.append('post_id', postId);
                fd.append('parent_id', parentId);
                fd.append('content', content);
                fd.append('is_anonymous', isAnon);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            chReloadAfterSuccess(0);
                        } else {
                            restore();
                            alert(json.data?.message || 'Failed to post comment');
                        }
                    })
                    .catch(() => { restore(); alert('Network error.'); });
            };

            window.chSubmitGuestComment = function (postId, parentId, nonce) {
                const nameEl = document.getElementById('ch-guest-comment-name');
                const contentEl = document.getElementById('ch-comment-content');
                const content = contentEl ? contentEl.value.trim() : '';
                const guestName = nameEl ? nameEl.value.trim() : '';
                if (!content) { alert('Please enter your comment'); return; }

                const fd = new FormData();
                fd.append('action', 'ch_add_comment');
                fd.append('post_id', postId);
                fd.append('parent_id', parentId);
                fd.append('content', content);
                fd.append('guest_name', guestName);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            chReloadAfterSuccess(0);
                        } else {
                            alert(json.data?.message || 'Failed to post comment');
                        }
                    })
                    .catch(() => alert('Network error.'));
            };

            window.chDeleteComment = async function (commentId, nonce, triggerBtn) {
                const confirmed = await window.chOpenConfirmModal({
                    title: 'Delete Comment',
                    message: 'This comment will be removed permanently.',
                    confirmLabel: 'Delete Comment',
                    confirmClass: 'ch-btn-danger'
                });
                if (!confirmed) return;
                if (triggerBtn) { triggerBtn.disabled = true; triggerBtn.textContent = 'Deleting...'; }
                const fd = new FormData();
                fd.append('action', 'ch_delete_comment');
                fd.append('comment_id', commentId);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            const el = document.getElementById('ch-comment-' + commentId);
                            if (el) {
                                el.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                                el.style.opacity = '0';
                                el.style.transform = 'translateX(-8px)';
                                setTimeout(() => el.remove(), 200);
                            }
                        } else {
                            if (triggerBtn) { triggerBtn.disabled = false; triggerBtn.textContent = 'Delete'; }
                            alert(json.data?.message || 'Failed to delete');
                        }
                    })
                    .catch(() => {
                        if (triggerBtn) { triggerBtn.disabled = false; triggerBtn.textContent = 'Delete'; }
                        alert('Network error.');
                    });
            };

            window.chToggleReplyForm = function (commentId) {
                const form = document.getElementById('ch-reply-form-' + commentId);
                if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
            };

            window.chEditComment = function (commentId) {
                const commentEl = document.getElementById('ch-comment-' + commentId);
                if (!commentEl) return;

                const contentEl = commentEl.querySelector('p');
                if (!contentEl) return;

                const originalContent = contentEl.textContent.trim();

                // Replace content with textarea
                contentEl.innerHTML = `
                <textarea class="ch-input ch-textarea" id="ch-edit-comment-${commentId}" rows="3">${originalContent}</textarea>
                <div style="margin-top:8px">
                    <button class="ch-btn ch-btn-primary ch-btn-sm" onclick="chSaveCommentEdit(${commentId}, '${window.chCurrentPost?.edit_comment_nonce || ''}')">Save</button>
                    <button class="ch-btn ch-btn-secondary ch-btn-sm" onclick="chCancelCommentEdit(${commentId}, '${originalContent}')">Cancel</button>
                </div>
            `;
            };

            window.chSaveCommentEdit = function (commentId, nonce) {
                const textarea = document.getElementById('ch-edit-comment-' + commentId);
                if (!textarea) return;

                const newContent = textarea.value.trim();
                if (!newContent) {
                    alert('Content cannot be empty');
                    return;
                }

                const fd = new FormData();
                fd.append('action', 'ch_edit_comment');
                fd.append('comment_id', commentId);
                fd.append('content', newContent);
                fd.append('nonce', nonce);

                fetch(chAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            const commentEl = document.getElementById('ch-comment-' + commentId);
                            const contentEl = commentEl.querySelector('p');
                            contentEl.innerHTML = newContent.replace(/\n/g, '<br>');
                        } else {
                            alert(json.data?.message || 'Failed to update comment');
                        }
                    });
            };

            window.chCancelCommentEdit = function (commentId, originalContent) {
                const commentEl = document.getElementById('ch-comment-' + commentId);
                const contentEl = commentEl.querySelector('p');
                contentEl.innerHTML = originalContent.replace(/\n/g, '<br>');
            };

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.ch-share-dropdown')) {
                    document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
                }
            });

            // Open/close modals
            window.chOpenModal = window.chOpenModal || function (id) { const el = document.getElementById(id); if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; } };
            window.chCloseModal = window.chCloseModal || function (id) { const el = document.getElementById(id); if (el) { el.style.display = 'none'; document.body.style.overflow = ''; } };
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('ch-modal-overlay')) {
                    e.target.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}

function ch_announcements_tab()
{
    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Announcements</h1>
            <p>Create and manage community announcements shown in the forum feed</p>
        </div>
        <button class="ch-btn ch-btn-primary"
            onclick="if (typeof chResetCreateAnnouncementForm === 'function') chResetCreateAnnouncementForm(); chOpenModal('ch-modal-create-announcement')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            New Announcement
        </button>
    </div>

    <div class="ch-card">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table" id="ch-ann-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ch-ann-tbody">
                    <tr>
                        <td colspan="4" class="ch-table-empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="ch-modal-create-announcement" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Create Announcement</h3>
                <button class="ch-modal-close" onclick="chCloseCreateAnnouncementModal()">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Title <span class="ch-required">*</span></label>
                    <input type="text" id="ch-ann-create-title" class="ch-input" placeholder="Announcement title"
                        maxlength="255">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content <span class="ch-required">*</span></label>
                    <textarea id="ch-ann-create-content" class="ch-input ch-textarea ch-textarea-lg" rows="6"
                        placeholder="Write your announcement..."></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-ann-create-status" checked>
                        Publish immediately
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseCreateAnnouncementModal()">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-ann-create-btn" onclick="chAnnCreate()">Create
                    Announcement</button>
            </div>
            <div id="ch-ann-create-msg"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="ch-modal-edit-announcement" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Edit Announcement</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-edit-announcement')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-ann-edit-id">
                <div class="ch-field-group">
                    <label class="ch-label">Title <span class="ch-required">*</span></label>
                    <input type="text" id="ch-ann-edit-title" class="ch-input" placeholder="Announcement title"
                        maxlength="255">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content <span class="ch-required">*</span></label>
                    <textarea id="ch-ann-edit-content" class="ch-input ch-textarea ch-textarea-lg" rows="6"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-ann-edit-status">
                        Published
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-announcement')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-ann-edit-btn" onclick="chAnnEdit()">Update
                    Announcement</button>
            </div>
            <div id="ch-ann-edit-msg"></div>
        </div>
    </div>

    <script>
        (function () {
            // Define ajaxurl locally so this IIFE works regardless of whether
            // ch_global_scripts() has already run (compiled asset vs inline order).
            const ajaxUrl = window.chAjaxUrl || window.ajaxurl || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            const nonce = '<?php echo wp_create_nonce('ch_announcements_nonce'); ?>';

            function post(action, data) {
                const fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', nonce);
                Object.entries(data).forEach(([k, v]) => fd.append(k, v));
                return fetch(ajaxUrl, { method: 'POST', body: fd }).then(r => r.json());
            }

            function showMsg(elId, msg, ok) {
                const el = document.getElementById(elId);
                if (!el) return;
                el.innerHTML = '<div class="bntm-notice bntm-notice-' + (ok ? 'success' : 'error') + '">' + msg + '</div>';
                if (ok) setTimeout(() => { el.innerHTML = ''; }, 3000);
            }

            window.chResetCreateAnnouncementForm = function () {
                const titleEl = document.getElementById('ch-ann-create-title');
                const contentEl = document.getElementById('ch-ann-create-content');
                const statusEl = document.getElementById('ch-ann-create-status');
                const msgEl = document.getElementById('ch-ann-create-msg');
                const btn = document.getElementById('ch-ann-create-btn');

                if (titleEl) titleEl.value = '';
                if (contentEl) contentEl.value = '';
                if (statusEl) statusEl.checked = true;
                if (msgEl) msgEl.innerHTML = '';
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Create Announcement';
                }
            };

            window.chCloseCreateAnnouncementModal = function () {
                if (typeof chCloseModal === 'function') {
                    chCloseModal('ch-modal-create-announcement');
                }
                if (typeof window.chResetCreateAnnouncementForm === 'function') {
                    window.chResetCreateAnnouncementForm();
                }
            };

            function loadAnnouncements() {
                post('ch_get_announcements', {}).then(json => {
                    const tbody = document.getElementById('ch-ann-tbody');
                    if (!json.success || !json.data.length) {
                        tbody.innerHTML = '<tr><td colspan="4" class="ch-table-empty">No announcements yet.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.data.map(a => {
                        const statusLabel = a.is_active == 1 ? 'Published' : 'Draft';
                        const statusClass = a.is_active == 1 ? 'active' : 'pending';
                        const date = new Date(a.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        return `<tr id="ch-ann-row-${a.id}">
                        <td>
                            <div class="ch-ann-title" style="font-weight:600;font-size:14px;">${a.title}</div>
                            <div class="ch-ann-excerpt" style="font-size:12px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:340px;">${a.content.replace(/<[^>]+>/g, '').substring(0, 80)}…</div>
                        </td>
                        <td><span class="ch-status-badge ch-status-${statusClass}">${statusLabel}</span></td>
                        <td><span class="ch-date">${date}</span></td>
                        <td>
                            <div class="ch-actions-row">
                                <button class="ch-btn-xs ch-btn-secondary" onclick="chAnnOpenEdit(${a.id})">Edit</button>
                                <button class="ch-btn-xs ${a.is_active == 1 ? 'ch-btn-warning' : 'ch-btn-success'}" onclick="chAnnToggle(${a.id},${a.is_active})">
                                    ${a.is_active == 1 ? 'Unpublish' : 'Publish'}
                                </button>
                                <button class="ch-btn-xs ch-btn-danger" onclick="chAnnDelete(${a.id})">Delete</button>
                            </div>
                        </td>
                    </tr>`;
                    }).join('');
                });
            }

            window.chAnnCreate = function () {
                const title = document.getElementById('ch-ann-create-title').value.trim();
                const content = document.getElementById('ch-ann-create-content').value.trim();
                const status = document.getElementById('ch-ann-create-status').checked ? 1 : 0;
                if (!title || !content) { showMsg('ch-ann-create-msg', 'Title and content are required.', false); return; }
                const btn = document.getElementById('ch-ann-create-btn');
                btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Creating...</span>';
                post('ch_create_announcement', { title, content, status }).then(json => {
                    showMsg('ch-ann-create-msg', json.data?.message || (json.success ? 'Created!' : 'Failed'), json.success);
                    if (json.success) {
                        if (typeof window.chCloseCreateAnnouncementModal === 'function') {
                            window.chCloseCreateAnnouncementModal();
                        }
                        loadAnnouncements();
                    }
                    btn.disabled = false; btn.textContent = 'Create Announcement';
                });
            };

            document.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'ch-modal-create-announcement' && typeof window.chResetCreateAnnouncementForm === 'function') {
                    window.chResetCreateAnnouncementForm();
                }
            });

            window.chAnnOpenEdit = function (id) {
                post('ch_get_announcement', { announcement_id: id }).then(json => {
                    if (!json.success) { alert('Failed to load announcement'); return; }
                    const a = json.data;
                    document.getElementById('ch-ann-edit-id').value = a.id;
                    document.getElementById('ch-ann-edit-title').value = a.title;
                    document.getElementById('ch-ann-edit-content').value = a.content;
                    document.getElementById('ch-ann-edit-status').checked = a.is_active == 1;
                    chOpenModal('ch-modal-edit-announcement');
                });
            };

            window.chAnnEdit = function () {
                const id = document.getElementById('ch-ann-edit-id').value;
                const title = document.getElementById('ch-ann-edit-title').value.trim();
                const content = document.getElementById('ch-ann-edit-content').value.trim();
                const status = document.getElementById('ch-ann-edit-status').checked ? 1 : 0;
                if (!title || !content) { showMsg('ch-ann-edit-msg', 'Title and content are required.', false); return; }
                const btn = document.getElementById('ch-ann-edit-btn');
                btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';
                post('ch_edit_announcement', { announcement_id: id, title, content, status }).then(json => {
                    showMsg('ch-ann-edit-msg', json.data?.message || (json.success ? 'Updated!' : 'Failed'), json.success);
                    if (json.success) { chCloseModal('ch-modal-edit-announcement'); loadAnnouncements(); }
                    btn.disabled = false; btn.textContent = 'Update Announcement';
                });
            };

            window.chAnnToggle = function (id, currentStatus) {
                const newStatus = currentStatus == 1 ? 0 : 1;
                post('ch_toggle_announcement', { announcement_id: id, status: newStatus }).then(json => {
                    if (json.success) loadAnnouncements();
                    else alert(json.data?.message || 'Toggle failed');
                });
            };

            window.chAnnDelete = async function (id) {
                const confirmed = await window.chOpenConfirmModal({
                    title: 'Delete Announcement',
                    message: 'This announcement and its related notifications will be removed.',
                    confirmLabel: 'Delete Announcement',
                    confirmClass: 'ch-btn-danger'
                });
                if (!confirmed) return;
                post('ch_delete_announcement', { announcement_id: id }).then(json => {
                    if (json.success) {
                        const row = document.getElementById('ch-ann-row-' + id);
                        if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; setTimeout(() => row.remove(), 300); }
                    } else { alert(json.data?.message || 'Delete failed'); }
                });
            };

            loadAnnouncements();
        })();
    </script>
    <?php
    return ob_get_clean();
}
