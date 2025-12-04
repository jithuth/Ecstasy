/**
 * Simple Website Analytics Tracking Script with Cookie Consent
 */

(function () {
    const API_URL = 'api/track.php'; // Adjust path if needed relative to root
    const CONSENT_KEY = 'analytics_consent';

    // 1. Inject CSS for Banner
    const style = document.createElement('style');
    style.innerHTML = `
        #cookie-consent-banner {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: rgba(10, 25, 47, 0.95);
            color: #ccd6f6;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(100, 255, 218, 0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 9999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            font-family: 'Inter', sans-serif;
            backdrop-filter: blur(10px);
            transform: translateY(150%);
            transition: transform 0.5s ease;
            max-width: 1200px;
            margin: 0 auto;
        }
        #cookie-consent-banner.show {
            transform: translateY(0);
        }
        .cookie-text {
            font-size: 14px;
            flex: 1;
            min-width: 250px;
        }
        .cookie-buttons {
            display: flex;
            gap: 10px;
        }
        .cookie-btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .cookie-accept {
            background: #64ffda;
            color: #0a192f;
        }
        .cookie-accept:hover {
            background: #4cdbb3;
        }
        .cookie-decline {
            background: transparent;
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
        }
        .cookie-decline:hover {
            background: rgba(255, 107, 107, 0.1);
        }
    `;
    document.head.appendChild(style);

    // 2. Create Banner HTML
    function createBanner() {
        const banner = document.createElement('div');
        banner.id = 'cookie-consent-banner';
        banner.innerHTML = `
            <div class="cookie-text">
                <strong>We value your privacy.</strong><br>
                We use cookies and similar technologies to analyze traffic and improve your experience. 
                By clicking "Accept", you agree to our use of analytics.
            </div>
            <div class="cookie-buttons">
                <button id="cookie-decline" class="cookie-btn cookie-decline">Decline</button>
                <button id="cookie-accept" class="cookie-btn cookie-accept">Accept</button>
            </div>
        `;
        document.body.appendChild(banner);

        // Add Event Listeners
        document.getElementById('cookie-accept').addEventListener('click', () => {
            localStorage.setItem(CONSENT_KEY, 'granted');
            hideBanner();
            initTracking(); // Start tracking immediately
        });

        document.getElementById('cookie-decline').addEventListener('click', () => {
            localStorage.setItem(CONSENT_KEY, 'denied');
            hideBanner();
        });

        // Show banner with animation
        setTimeout(() => {
            banner.classList.add('show');
        }, 500);
    }

    function hideBanner() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.classList.remove('show');
            setTimeout(() => banner.remove(), 500);
        }
    }

    // 3. Check Consent
    function hasConsent() {
        return localStorage.getItem(CONSENT_KEY) === 'granted';
    }

    function shouldShowBanner() {
        return !localStorage.getItem(CONSENT_KEY);
    }

    // 4. Tracking Logic
    function sendData(data) {
        if (!hasConsent()) return; // STOP if no consent

        fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        }).catch(err => console.error('Analytics Error:', err));
    }

    function trackPageView() {
        sendData({
            type: 'pageview',
            url: window.location.href,
            title: document.title,
            referrer: document.referrer
        });
    }

    window.trackEvent = function (category, action, label = '', value = 0) {
        sendData({
            type: 'event',
            category: category,
            action: action,
            label: label,
            value: value
        });
    };

    function initTracking() {
        trackPageView();

        // Track external links
        document.querySelectorAll('a[data-track]').forEach(el => {
            el.addEventListener('click', () => {
                const label = el.getAttribute('data-track-label') || el.innerText;
                trackEvent('link', 'click', label);
            });
        });
    }

    // 5. Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (hasConsent()) {
            initTracking();
        } else if (shouldShowBanner()) {
            createBanner();
        }
    });

})();
