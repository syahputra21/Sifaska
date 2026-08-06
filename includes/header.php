<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#5B86A4">
    <title>SIFASKA - Sistem Pengelolaan Inventaris dan Peminjaman Fasilitas Akademik Universitas Muhammadiyah Sorong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Outfit"', 'sans-serif'],
                    },
                    colors: {
                        luxury: {
                            base: '#F9FAFB',
                            primary: '#5B86A4',
                            secondary: '#34495e',
                            dark: '#2c3e50',
                            accent: '#7FAAC6',   
                        }
                    },
                    boxShadow: {
                        'soft': '0 10px 40px -10px rgba(91, 134, 164, 0.15)',
                        'glow': '0 0 20px rgba(91, 134, 164, 0.3)',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'popup': 'scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'slide-in': 'slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        scaleUp: {
                            '0%': { transform: 'scale(0.95)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(100%)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        html {
            font-size: 100%; /* Responsive base scale for mobile */
            scroll-behavior: smooth;
        }
        @media (min-width: 768px) {
            html { font-size: 110%; } /* Tablets */
        }
        @media (min-width: 1024px) {
            html { font-size: 120%; } /* Desktop */
        }
        body {
            background-color: #F9FAFB;
            color: #2c3e50;
            overflow-x: hidden;
        }

        .hidden-section {
            display: none !important;
        }

        /* --- GLOBAL SMOOTH SCROLLBAR --- */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* --- GLOBAL SMOOTH FORM INPUTS (Anti-Kaku) --- */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }

        .input-luxury,
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        textarea,
        select {
            background: rgba(248, 250, 252, 0.85) !important;
            border: 1px solid rgba(226, 232, 240, 0.95) !important;
            border-radius: 1rem !important;
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;
            font-family: inherit;
        }
        .input-luxury:hover,
        input[type="text"]:hover,
        input[type="password"]:hover,
        input[type="email"]:hover,
        input[type="number"]:hover,
        input[type="date"]:hover,
        input[type="time"]:hover,
        textarea:hover,
        select:hover {
            background: #ffffff !important;
            border-color: rgba(91, 134, 164, 0.5) !important;
            box-shadow: 0 4px 12px -2px rgba(44, 62, 80, 0.06) !important;
        }
        .input-luxury:focus,
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus,
        textarea:focus,
        select:focus {
            background: #ffffff !important;
            border-color: #5B86A4 !important;
            box-shadow: 0 0 0 4px rgba(91, 134, 164, 0.15), 0 4px 12px -2px rgba(44, 62, 80, 0.08) !important;
            outline: none !important;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* --- STYLING NATIVE DATE & TIME PICKER ICONS --- */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            background-color: rgba(91, 134, 164, 0.1);
            padding: 6px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            filter: opacity(0.8);
        }
        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="time"]::-webkit-calendar-picker-indicator:hover {
            background-color: rgba(91, 134, 164, 0.22);
            filter: opacity(1);
            transform: scale(1.08);
        }

        /* --- GLOBAL SMOOTH MODALS & CARDS --- */
        .animate-popup {
            animation: smoothModalEnter 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        }
        @keyframes smoothModalEnter {
            0% {
                opacity: 0;
                transform: translateY(18px) scale(0.96);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* --- GLOBAL INTERACTIVE TABLE ROWS & BUTTONS --- */
        tbody tr {
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }
        tbody tr:hover {
            background-color: rgba(91, 134, 164, 0.05) !important;
            transform: translateX(3px);
        }
        button, a, input[type="submit"], input[type="button"] {
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        button:active, a:active {
            transform: scale(0.98);
        }

        /* Smooth Custom Select Styling */
        .custom-select-wrapper {
            position: relative;
            width: 100%;
            user-select: none;
        }
        .custom-select-native {
            position: absolute !important;
            opacity: 0 !important;
            pointer-events: none !important;
            width: 100% !important;
            height: 100% !important;
            top: 0 !important;
            left: 0 !important;
            z-index: -1 !important;
        }
        .custom-select-button {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: left;
        }
        .custom-select-button:hover {
            background: #ffffff;
            border-color: #7FAAC6;
            box-shadow: 0 4px 12px rgba(91, 134, 164, 0.08);
        }
        .custom-select-wrapper.open .custom-select-button {
            background: #ffffff;
            border-color: #5B86A4;
            box-shadow: 0 0 0 4px rgba(91, 134, 164, 0.12);
        }
        .custom-select-arrow {
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), color 0.3s ease;
        }
        .custom-select-wrapper.open .custom-select-arrow {
            transform: rotate(180deg);
            color: #5B86A4;
        }
        .custom-select-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            max-height: 220px;
            overflow-y: auto;
            overscroll-behavior: contain;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1rem;
            box-shadow: 0 20px 40px -10px rgba(44, 62, 80, 0.18), 0 4px 12px -2px rgba(44, 62, 80, 0.06);
            padding: 0.35rem 0.35rem 0.5rem 0.35rem;
            opacity: 0;
            transform: translateY(-8px) scale(0.97);
            pointer-events: none;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            transform-origin: top;
        }
        .custom-select-wrapper.drop-up .custom-select-dropdown {
            top: auto;
            bottom: calc(100% + 6px);
            transform: translateY(8px) scale(0.97);
            transform-origin: bottom;
            box-shadow: 0 -20px 40px -10px rgba(44, 62, 80, 0.22), 0 -4px 12px -2px rgba(44, 62, 80, 0.08);
        }
        .custom-select-wrapper.open .custom-select-dropdown {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .custom-select-option {
            padding: 0.65rem 0.85rem;
            margin: 0.15rem 0;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .custom-select-option:hover {
            background: rgba(91, 134, 164, 0.1);
            color: #5B86A4;
            transform: translateX(4px);
        }
        .custom-select-option.selected {
            background: rgba(91, 134, 164, 0.15);
            color: #5B86A4;
            font-weight: 700;
        }
        .custom-select-dropdown::-webkit-scrollbar {
            width: 5px;
        }
        .custom-select-dropdown::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-select-dropdown::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        .custom-select-dropdown::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="antialiased font-sans selection:bg-luxury-primary selection:text-white">
<?php
if (isset($_SESSION['user_id'])) {
    include_once __DIR__ . '/fcm_widget.php';
}
?>
<script>
(function() {
    function initSmoothSelects() {
        const selects = document.querySelectorAll('select.input-luxury:not([data-enhanced="true"])');
        selects.forEach(select => {
            enhanceSelectElement(select);
        });
    }

    function enhanceSelectElement(select) {
        if (select.dataset.enhanced === 'true') return;
        select.dataset.enhanced = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('custom-select-native');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-button';
        button.innerHTML = `
            <span class="custom-select-label truncate pr-2"></span>
            <svg class="custom-select-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        `;
        wrapper.appendChild(button);

        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';
        wrapper.appendChild(dropdown);

        const labelSpan = button.querySelector('.custom-select-label');

        function rebuildDropdown() {
            dropdown.innerHTML = '';
            const options = Array.from(select.options);
            
            let selectedOpt = options.find(opt => opt.selected) || options[0];
            if (selectedOpt) {
                labelSpan.textContent = selectedOpt.textContent;
                if (selectedOpt.value === '' || selectedOpt.textContent.includes('--')) {
                    labelSpan.classList.add('text-slate-400');
                    labelSpan.classList.remove('text-slate-700', 'font-semibold');
                } else {
                    labelSpan.classList.remove('text-slate-400');
                    labelSpan.classList.add('text-slate-700', 'font-semibold');
                }
            }

            options.forEach(opt => {
                const optDiv = document.createElement('div');
                optDiv.className = 'custom-select-option';
                if (opt.disabled) {
                    optDiv.classList.add('opacity-40', 'pointer-events-none');
                }
                if (opt.selected || opt.value === select.value) {
                    optDiv.classList.add('selected');
                    optDiv.innerHTML = `<span>${opt.textContent}</span><svg class="w-4 h-4 text-luxury-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
                } else {
                    optDiv.innerHTML = `<span>${opt.textContent}</span>`;
                }

                optDiv.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (opt.disabled) return;
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    rebuildDropdown();
                    wrapper.classList.remove('open');
                });

                dropdown.appendChild(optDiv);
            });
        }

        rebuildDropdown();

        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.contains('open');
            document.querySelectorAll('.custom-select-wrapper.open').forEach(el => {
                if (el !== wrapper) el.classList.remove('open');
            });
            if (!isOpen) {
                const rect = button.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                if (spaceBelow < 250) {
                    wrapper.classList.add('drop-up');
                } else {
                    wrapper.classList.remove('drop-up');
                }
                wrapper.classList.add('open');
            } else {
                wrapper.classList.remove('open');
            }
        });

        const observer = new MutationObserver(() => {
            rebuildDropdown();
        });
        observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['selected'] });

        select.addEventListener('change', rebuildDropdown);
        select.addEventListener('value-changed', rebuildDropdown);
    }

    const descriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    if (descriptor && descriptor.set) {
        const originalSet = descriptor.set;
        Object.defineProperty(HTMLSelectElement.prototype, 'value', {
            set: function(val) {
                originalSet.call(this, val);
                this.dispatchEvent(new CustomEvent('value-changed'));
            }
        });
    }

    document.addEventListener('click', (e) => {
        document.querySelectorAll('.custom-select-wrapper.open').forEach(el => {
            if (!el.contains(e.target)) {
                el.classList.remove('open');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        initSmoothSelects();
        const bodyObserver = new MutationObserver(() => {
            initSmoothSelects();
        });
        bodyObserver.observe(document.body, { childList: true, subtree: true });
    });
})();
</script>
