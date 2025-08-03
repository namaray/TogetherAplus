/**
 * VoiceTracking.js - Secure & Robust Voice Recognition Module
 *
 * Version: 3.2.2 - Click Reliability Fixes
 * License: MIT
 * Fixed: Command processing state, click execution, cooldown issues
 */

(function() {
  'use strict';

  // Prevent multiple initializations with proper cleanup
  if (window.VoiceTrackingLoaded) {
    console.warn('VoiceTracking.js already loaded. Cleaning up previous instance.');
    if (window.VoiceTracking && typeof window.VoiceTracking.destroy === 'function') {
      window.VoiceTracking.destroy();
    }
  }

  // Secure namespace protection
  const NAMESPACE_KEY = Symbol('VoiceTracking');
  window[NAMESPACE_KEY] = true;

  // Enhanced configuration with validation
  const CONFIG = Object.freeze({
    enabled: false,
    maxDistance: 0.7,
    ignoreWords: Object.freeze(['click', 'press', 'select', 'open', 'go to', 'navigate', 'the', 'on', 'to', 'button']),
    scanInterval: 1000, // Reduced for better responsiveness
    autoRecover: true,
    errorRetryDelay: 1000,
    maxErrorRetries: 3,
    useEnhancedMatching: true,
    statusDisplayTimeout: 1500, // Reduced
    forceBrowserCompatibility: true,
    autoStart: false,
    widgetPosition: 'top-right',
    widgetSize: 'small',
    showVisualFeedback: true,
    highlightColor: 'rgba(255, 215, 0, 0.3)',
    highlightBorder: '2px solid orange',
    commandCooldown: 500, // Reduced from 1500ms to 500ms for better responsiveness
    persistSettings: true,
    maxElementsToShow: 50,
    maxElementsToScan: 1000,
    debounceDelay: 200, // Reduced
    cacheTimeout: 3000 // Reduced for fresher element detection
  });

  // Mutable configuration (deep clone for safety)
  let activeConfig = JSON.parse(JSON.stringify(CONFIG));

  // Secure clickable selectors with validation
  const CLICKABLE_SELECTORS = Object.freeze([
    'button',
    'a[href]',
    'input[type="button"]',
    'input[type="submit"]',
    'input[type="reset"]',
    '[role="button"]',
    'input[type="checkbox"]',
    'input[type="radio"]',
    'select',
    'textarea',
    'input[type="text"]',
    'input[type="email"]',
    'input[type="password"]',
    'input[type="number"]',
    'input[type="search"]',
    'input[type="tel"]',
    'input[type="url"]',
    'input[type="date"]',
    'input[type="time"]',
    'input[type="file"]',
    '[onclick]',
    '[tabindex]:not([tabindex="-1"])',
    '.btn',
    '.button',
    '.clickable',
    '[data-clickable]'
  ]);

  // State management with proper initialization
  let state = {
    listeningState: 'inactive',
    clickableElements: [],
    lastScanTime: 0,
    elementHighlights: new Map(),
    highlightTimeout: null,
    errorRetryCount: 0,
    autoRecoveryTimer: null,
    isInitialized: false,
    detectedBrowser: 'unknown',
    microphoneAccessGranted: false,
    hasSpeechRecognitionSupport: false,
    isProcessingCommand: false,
    lastCommandTime: 0,
    elements: {},
    eventListeners: new Map(),
    timers: new Set(),
    cache: new Map(),
    lastCacheTime: 0,
    mutationObserver: null,
    isDragging: false,
    dragOffset: { x: 0, y: 0 },
    libraryLoadAttempts: 0,
    maxLibraryLoadAttempts: 3,
    commandCounter: 0, // Added for debugging
    lastClickedElement: null // Track last clicked element
  };

  // Utility functions for security and validation
  const utils = {
    // Sanitize text content to prevent XSS
    sanitizeText: function(text) {
      if (typeof text !== 'string') return '';
      return text.replace(/[<>&"']/g, function(match) {
        const escapeMap = {
          '<': '&lt;',
          '>': '&gt;',
          '&': '&amp;',
          '"': '&quot;',
          "'": '&#x27;'
        };
        return escapeMap[match];
      });
    },

    // Validate configuration object
    validateConfig: function(config) {
      if (!config || typeof config !== 'object') return false;
      
      const validators = {
        enabled: (v) => typeof v === 'boolean',
        maxDistance: (v) => typeof v === 'number' && v >= 0 && v <= 1,
        scanInterval: (v) => typeof v === 'number' && v >= 100,
        maxElementsToScan: (v) => typeof v === 'number' && v > 0 && v <= 10000,
        commandCooldown: (v) => typeof v === 'number' && v >= 0
      };

      for (const [key, validator] of Object.entries(validators)) {
        if (key in config && !validator(config[key])) {
          console.warn(`VoiceTracking: Invalid config value for ${key}:`, config[key]);
          return false;
        }
      }
      return true;
    },

    // Debounce function to prevent excessive calls
    debounce: function(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        state.timers.add(timeout);
      };
    },

    // Throttle function for performance
    throttle: function(func, limit) {
      let inThrottle;
      return function executedFunction(...args) {
        if (!inThrottle) {
          func.apply(this, args);
          inThrottle = true;
          const timer = setTimeout(() => inThrottle = false, limit);
          state.timers.add(timer);
        }
      };
    },

    // Safe event listener management
    addEventListener: function(element, event, handler, options = {}) {
      try {
        element.addEventListener(event, handler, options);
        
        if (!state.eventListeners.has(element)) {
          state.eventListeners.set(element, new Map());
        }
        state.eventListeners.get(element).set(event, { handler, options });
      } catch (e) {
        console.error('VoiceTracking: Error adding event listener:', e);
      }
    },

    // Safe timer management
    setTimeout: function(callback, delay) {
      const timer = setTimeout(() => {
        state.timers.delete(timer);
        callback();
      }, delay);
      state.timers.add(timer);
      return timer;
    },

    // Feature detection instead of user agent sniffing
    detectFeatures: function() {
      const features = {
        speechRecognition: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
        mediaDevices: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
        secureContext: window.isSecureContext || location.protocol === 'https:' || 
                       location.hostname === 'localhost' || location.hostname === '127.0.0.1',
        touchEvents: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
        intersectionObserver: 'IntersectionObserver' in window,
        mutationObserver: 'MutationObserver' in window
      };

      // Set up Speech Recognition with fallbacks
      if (features.speechRecognition) {
        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        state.hasSpeechRecognitionSupport = true;
      }

      return features;
    },

    // Force reset command processing state
    resetCommandState: function() {
      state.isProcessingCommand = false;
      console.log('VoiceTracking: Command state forcefully reset');
    }
  };

  // Enhanced browser compatibility check
  function ensureBrowserCompatibility() {
    const features = utils.detectFeatures();
    state.hasSpeechRecognitionSupport = features.speechRecognition;

    if (!features.secureContext) {
      showNotification('Voice recognition requires HTTPS (or localhost)', 5000, 'warning');
      console.warn('VoiceTracking: Insecure context detected.');
    }

    if (!features.speechRecognition) {
      showNotification('Speech Recognition not supported in this browser', 5000, 'error');
    }

    if (!features.mediaDevices) {
      showNotification('Microphone access not supported', 5000, 'error');
    }

    return features;
  }

  // Secure CSS injection with nonce support
  function createStyles() {
    if (document.getElementById('voicetracking-styles')) return;

    const style = document.createElement('style');
    style.id = 'voicetracking-styles';
    
    // Add nonce if available for CSP compliance
    if (window.voiceTrackingNonce) {
      style.nonce = window.voiceTrackingNonce;
    }

    style.textContent = `
      .voicetracking-widget {
        position: fixed;
        z-index: 2147483647; /* Maximum safe z-index */
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.8);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        cursor: grab;
        user-select: none;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        touch-action: none; /* Prevent default touch behaviors */
      }
      .voicetracking-widget.small { width: 180px; height: 60px; }
      .voicetracking-widget.medium { width: 220px; height: 80px; }
      .voicetracking-widget.large { width: 260px; height: 100px; }
      .voicetracking-widget.top-right { top: 20px; right: 20px; }
      .voicetracking-widget.top-left { top: 20px; left: 20px; }
      .voicetracking-widget.bottom-right { bottom: 20px; right: 20px; }
      .voicetracking-widget.bottom-left { bottom: 20px; left: 20px; }

      .voicetracking-status {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #666;
        transition: all 0.3s ease;
        box-shadow: 0 0 8px rgba(0,0,0,0.5);
      }
      .voicetracking-status.inactive { background: #666; }
      .voicetracking-status.listening {
        background: #4caf50;
        box-shadow: 0 0 15px rgba(76,175,80,0.5);
        animation: voicetracking-pulse 1.5s infinite ease-in-out;
      }
      .voicetracking-status.processing {
        background: #ff9800;
        animation: voicetracking-spin 1s infinite linear;
      }
      .voicetracking-status.recognized {
        background: #2196f3;
        transform: scale(1.2);
      }
      .voicetracking-status.error {
        background: #f44336;
        animation: voicetracking-flash 0.5s infinite;
      }

      @keyframes voicetracking-pulse {
        0% { transform: scale(0.95); opacity: 0.7; }
        50% { transform: scale(1.05); opacity: 0.9; }
        100% { transform: scale(0.95); opacity: 0.7; }
      }

      @keyframes voicetracking-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }

      @keyframes voicetracking-flash {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
      }

      .voicetracking-content {
        padding: 8px 12px;
        color: white;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .voicetracking-text {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .voicetracking-controls {
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.3s ease;
      }
      .voicetracking-widget:hover .voicetracking-controls,
      .voicetracking-widget:focus-within .voicetracking-controls {
        opacity: 1;
      }

      .voicetracking-btn {
        padding: 2px 6px;
        font-size: 10px;
        border: none;
        border-radius: 3px;
        background: #4c6ef5;
        color: white;
        cursor: pointer;
        transition: background 0.2s;
      }
      .voicetracking-btn:hover,
      .voicetracking-btn:focus { 
        background: #364fc7; 
        outline: 2px solid rgba(255,255,255,0.5);
      }
      .voicetracking-btn:disabled { 
        background: #666; 
        cursor: not-allowed; 
      }

      .voicetracking-feedback {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 16px;
        z-index: 2147483646;
        transition: opacity 0.3s ease;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        pointer-events: none;
        opacity: 0;
        max-width: 300px;
        text-align: center;
        word-wrap: break-word;
      }

      .voicetracking-feedback.show {
        opacity: 1;
      }

      .voicetracking-notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        z-index: 2147483645;
        transition: opacity 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        max-width: 400px;
        text-align: center;
        word-wrap: break-word;
      }

      .voicetracking-notification.warning {
        background: rgba(255, 152, 0, 0.9);
        color: black;
      }

      .voicetracking-notification.error {
        background: rgba(244, 67, 54, 0.9);
      }

      .voicetracking-highlight {
        position: fixed;
        background: ${activeConfig.highlightColor};
        border: ${activeConfig.highlightBorder};
        border-radius: 4px;
        z-index: 2147483644;
        pointer-events: none;
        transition: opacity 0.5s ease;
        box-shadow: 0 0 12px rgba(255,165,0,0.6);
      }

      .voicetracking-element-label {
        position: fixed;
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-family: inherit;
        z-index: 2147483643;
        pointer-events: none;
        max-width: 200px;
        word-wrap: break-word;
      }

      /* Screen reader only class for accessibility */
      .voicetracking-sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
      }
    `;
    
    try {
      document.head.appendChild(style);
    } catch (e) {
      console.error('VoiceTracking: Failed to inject styles:', e);
    }
  }

  // Enhanced UI creation with accessibility
  function createUI() {
    const widget = document.createElement('div');
    widget.className = `voicetracking-widget ${activeConfig.widgetSize} ${activeConfig.widgetPosition}`;
    widget.setAttribute('role', 'application');
    widget.setAttribute('aria-label', 'Voice Control Widget');
    widget.setAttribute('tabindex', '0');

    const status = document.createElement('div');
    status.className = 'voicetracking-status inactive';
    status.setAttribute('aria-live', 'polite');
    status.setAttribute('aria-label', 'Voice control status');

    const content = document.createElement('div');
    content.className = 'voicetracking-content';

    const text = document.createElement('div');
    text.className = 'voicetracking-text';
    text.textContent = 'Voice Control';
    text.setAttribute('aria-live', 'polite');

    const controls = document.createElement('div');
    controls.className = 'voicetracking-controls';
    controls.setAttribute('role', 'group');
    controls.setAttribute('aria-label', 'Voice control buttons');

    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'voicetracking-btn';
    toggleBtn.textContent = 'Start';
    toggleBtn.setAttribute('aria-label', 'Toggle voice control');
    toggleBtn.onclick = toggle;

    const showBtn = document.createElement('button');
    showBtn.className = 'voicetracking-btn';
    showBtn.textContent = 'Show';
    showBtn.setAttribute('aria-label', 'Show clickable elements');
    showBtn.onclick = showClickableElements;

    // Add debug button for testing
    const debugBtn = document.createElement('button');
    debugBtn.className = 'voicetracking-btn';
    debugBtn.textContent = 'Reset';
    debugBtn.setAttribute('aria-label', 'Reset command state');
    debugBtn.onclick = () => {
      utils.resetCommandState();
      state.cache.clear();
      scanPageForClickableElements();
      showNotification('State reset', 1000);
    };

    controls.appendChild(toggleBtn);
    controls.appendChild(showBtn);
    controls.appendChild(debugBtn);
    content.appendChild(text);
    content.appendChild(controls);
    widget.appendChild(status);
    widget.appendChild(content);

    const feedback = document.createElement('div');
    feedback.className = 'voicetracking-feedback';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'assertive');

    // Screen reader announcement area
    const srAnnouncement = document.createElement('div');
    srAnnouncement.className = 'voicetracking-sr-only';
    srAnnouncement.setAttribute('aria-live', 'assertive');
    srAnnouncement.setAttribute('role', 'status');

    makeDraggable(widget);

    state.elements = { 
      widget, 
      status, 
      text, 
      controls, 
      toggleBtn, 
      showBtn, 
      debugBtn,
      feedback,
      srAnnouncement
    };

    try {
      document.body.appendChild(widget);
      document.body.appendChild(feedback);
      document.body.appendChild(srAnnouncement);
    } catch (e) {
      console.error('VoiceTracking: Failed to append UI elements:', e);
    }
  }

  // Enhanced draggable functionality with touch support
  function makeDraggable(element) {
    let startX = 0, startY = 0, initialX = 0, initialY = 0;

    function handleStart(e) {
      const target = e.target || e.touches?.[0]?.target;
      if (target && (target === element || target.closest('.voicetracking-content')) && 
          !target.closest('.voicetracking-controls')) {
        
        state.isDragging = true;
        
        const clientX = e.clientX || e.touches?.[0]?.clientX || 0;
        const clientY = e.clientY || e.touches?.[0]?.clientY || 0;
        
        startX = clientX;
        startY = clientY;
        initialX = element.offsetLeft;
        initialY = element.offsetTop;
        
        element.style.transition = 'none';
        element.style.cursor = 'grabbing';
        
        e.preventDefault();
      }
    }

    function handleMove(e) {
      if (!state.isDragging) return;
      
      e.preventDefault();
      
      const clientX = e.clientX || e.touches?.[0]?.clientX || 0;
      const clientY = e.clientY || e.touches?.[0]?.clientY || 0;
      
      const deltaX = clientX - startX;
      const deltaY = clientY - startY;
      
      const newX = Math.max(0, Math.min(window.innerWidth - element.offsetWidth, initialX + deltaX));
      const newY = Math.max(0, Math.min(window.innerHeight - element.offsetHeight, initialY + deltaY));
      
      element.style.left = newX + 'px';
      element.style.top = newY + 'px';
      element.style.right = 'auto';
      element.style.bottom = 'auto';
    }

    function handleEnd() {
      if (state.isDragging) {
        state.isDragging = false;
        element.style.transition = 'all 0.3s ease';
        element.style.cursor = 'grab';
        saveWidgetPosition();
      }
    }

    // Mouse events
    utils.addEventListener(element, 'mousedown', handleStart);
    utils.addEventListener(document, 'mousemove', handleMove);
    utils.addEventListener(document, 'mouseup', handleEnd);

    // Touch events
    if (utils.detectFeatures().touchEvents) {
      utils.addEventListener(element, 'touchstart', handleStart, { passive: false });
      utils.addEventListener(document, 'touchmove', handleMove, { passive: false });
      utils.addEventListener(document, 'touchend', handleEnd);
    }
  }

  // Enhanced notification system with types
  function showNotification(message, duration = 3000, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `voicetracking-notification ${type}`;
    notification.textContent = utils.sanitizeText(message);
    notification.setAttribute('role', 'alert');
    
    try {
      document.body.appendChild(notification);
      
      const timer = utils.setTimeout(() => {
        notification.style.opacity = '0';
        utils.setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 300);
      }, duration);
      
      // Announce to screen readers
      if (state.elements.srAnnouncement) {
        state.elements.srAnnouncement.textContent = message;
      }
    } catch (e) {
      console.error('VoiceTracking: Failed to show notification:', e);
    }
  }

  // Enhanced feedback with sanitization
  function showFeedback(message, duration = 1000) { // Reduced duration
    if (!activeConfig.showVisualFeedback || !state.elements.feedback) return;

    try {
      state.elements.feedback.textContent = utils.sanitizeText(message);
      state.elements.feedback.classList.add('show');

      utils.setTimeout(() => {
        state.elements.feedback.classList.remove('show');
      }, duration);
    } catch (e) {
      console.error('VoiceTracking: Failed to show feedback:', e);
    }
  }

  // Enhanced status updates with accessibility
  function updateListeningStatus(newState) {
    if (!state.elements.status || !state.elements.text || !state.elements.toggleBtn) {
      console.warn('VoiceTracking: UI elements not ready for status update.');
      return;
    }

    const validStates = ['inactive', 'listening', 'processing', 'recognized', 'error'];
    if (!validStates.includes(newState)) {
      console.warn('VoiceTracking: Invalid state:', newState);
      return;
    }

    state.listeningState = newState;
    
    // Update visual status
    state.elements.status.classList.remove(...validStates);
    state.elements.status.classList.add(newState);

    const statusTexts = {
      inactive: 'Voice Control',
      listening: 'Listening...',
      processing: 'Processing...',
      recognized: 'Recognized!',
      error: 'Error'
    };
    
    const statusText = statusTexts[newState] || 'Voice Control';
    state.elements.text.textContent = statusText;
    state.elements.toggleBtn.textContent = newState === 'inactive' ? 'Start' : 'Stop';
    
    // Update accessibility attributes
    state.elements.status.setAttribute('aria-label', `Voice control status: ${statusText}`);
    
    // Announce important state changes to screen readers
    if (['listening', 'error'].includes(newState) && state.elements.srAnnouncement) {
      state.elements.srAnnouncement.textContent = statusText;
    }
  }

  // Enhanced microphone access testing
  async function testMicrophoneAccess() {
    if (state.microphoneAccessGranted) return true;
    
    try {
      const constraints = {
        audio: {
          noiseSuppression: true,
          echoCancellation: true,
          autoGainControl: true,
          sampleRate: 44100
        }
      };
      
      const stream = await navigator.mediaDevices.getUserMedia(constraints);
      
      // Test that we actually have audio
      const tracks = stream.getAudioTracks();
      if (tracks.length === 0) {
        throw new Error('No audio tracks available');
      }
      
      // Stop all tracks immediately
      tracks.forEach(track => {
        track.stop();
        console.log('VoiceTracking: Audio track stopped:', track.label);
      });
      
      state.microphoneAccessGranted = true;
      return true;
    } catch (err) {
      state.microphoneAccessGranted = false;
      
      let errorMessage = 'Microphone access error';
      if (err.name === 'NotAllowedError') {
        errorMessage = 'Microphone access denied. Please allow microphone access in browser settings.';
      } else if (err.name === 'NotFoundError') {
        errorMessage = 'No microphone found. Please connect a microphone.';
      } else if (err.name === 'NotReadableError') {
        errorMessage = 'Microphone is being used by another application.';
      } else {
        errorMessage = `Microphone error: ${err.message}`;
      }
      
      console.error('VoiceTracking: Microphone access error:', err);
      showNotification(errorMessage, 5000, 'error');
      return false;
    }
  }

  // Optimized element scanning with caching
  function scanPageForClickableElements() {
    const currentTime = Date.now();
    
    // Always rescan if enabled for better reliability
    if (activeConfig.enabled) {
      // Force fresh scan when voice is active
      state.cache.delete('clickableElements');
    } else {
      // Check cache validity when not active
      if (currentTime - state.lastCacheTime < activeConfig.cacheTimeout && 
          state.cache.has('clickableElements')) {
        state.clickableElements = state.cache.get('clickableElements');
        return state.clickableElements.length;
      }
    }
    
    // Basic throttle for non-active scanning
    if (!activeConfig.enabled && currentTime - state.lastScanTime < activeConfig.scanInterval) {
      return state.clickableElements.length;
    }

    state.lastScanTime = currentTime;
    state.clickableElements = [];

    const selectorString = CLICKABLE_SELECTORS.join(',');
    if (!selectorString) {
      console.warn("VoiceTracking: No selectors available for scanning.");
      return 0;
    }

    try {
      const allElements = document.querySelectorAll(selectorString);
      let processedCount = 0;

      for (const element of allElements) {
        if (processedCount >= activeConfig.maxElementsToScan) {
          console.warn(`VoiceTracking: Element scan limit reached (${activeConfig.maxElementsToScan})`);
          break;
        }

        // Skip voice widget
        if (state.elements.widget && 
            (element === state.elements.widget || element.closest('.voicetracking-widget'))) {
          continue;
        }

        if (!isElementVisible(element)) continue;

        const text = extractElementText(element);
        if (!text || text.length < 1) continue;

        state.clickableElements.push({
          element: element,
          text: text,
          normalizedText: normalizeText(text),
          id: element.id || '',
          tagName: element.tagName.toLowerCase()
        });

        processedCount++;
      }

      // Cache results only when not actively using voice control
      if (!activeConfig.enabled) {
        state.cache.set('clickableElements', [...state.clickableElements]);
        state.lastCacheTime = currentTime;
      }

      if (activeConfig.enabled) {
        setupElementAwareCommands();
      }

      console.log(`VoiceTracking: Scanned ${state.clickableElements.length} clickable elements.`);
      return state.clickableElements.length;

    } catch (e) {
      console.error("VoiceTracking: Error during element scanning:", e);
      showNotification("Error scanning page elements. Voice commands might be limited.", 4000, 'warning');
      return 0;
    }
  }

  // Enhanced element visibility detection
  function isElementVisible(element) {
    try {
      const rect = element.getBoundingClientRect();
      const style = window.getComputedStyle(element);
      
      // Check basic visibility
      if (rect.width === 0 || rect.height === 0) return false;
      if (style.visibility === 'hidden' || style.display === 'none') return false;
      if (style.opacity === '0') return false;
      
      // Check if element is within reasonable bounds (not far off-screen)
      const margin = 50; // Allow some off-screen elements
      if (rect.bottom < -margin || rect.top > window.innerHeight + margin ||
          rect.right < -margin || rect.left > window.innerWidth + margin) {
        return false;
      }
      
      return true;
    } catch (e) {
      console.warn('VoiceTracking: Error checking element visibility:', e);
      return false;
    }
  }

  // Enhanced text extraction with better fallbacks
  function extractElementText(element) {
    try {
      let text = '';

      // Priority order for text extraction
      if (element.tagName === 'INPUT') {
        if (['button', 'submit', 'reset'].includes(element.type)) {
          text = element.value || element.getAttribute('aria-label') || element.title || element.name || element.id || '';
        } else if (element.placeholder) {
          text = element.placeholder;
        } else if (element.labels && element.labels.length > 0) {
          text = element.labels[0].textContent || '';
        }
      } else {
        // Try different text sources in order of preference
        text = element.getAttribute('aria-label') ||
               element.getAttribute('title') ||
               element.getAttribute('alt') ||
               (element.tagName === 'IMG' && element.alt) ||
               element.textContent ||
               element.innerText ||
               '';
      }

      // Clean and validate text
      text = text.trim().replace(/\s+/g, ' ');
      
      // If still no text, try child text nodes
      if (!text) {
        for (const node of element.childNodes) {
          if (node.nodeType === Node.TEXT_NODE) {
            text += node.textContent.trim() + ' ';
          }
        }
        text = text.trim();
      }

      return text.substring(0, 100); // Limit text length
    } catch (e) {
      console.warn('VoiceTracking: Error extracting element text:', e);
      return '';
    }
  }

  // Enhanced text normalization
  function normalizeText(text) {
    if (typeof text !== 'string') return '';
    
    try {
      let normalized = text.toLowerCase();
      
      // Remove punctuation but keep numbers and letters
      normalized = normalized.replace(/[^\w\s]/g, ' ');
      
      // Replace multiple spaces with single space
      normalized = normalized.replace(/\s+/g, ' ').trim();

      // Remove ignore words
      for (const word of activeConfig.ignoreWords) {
        const regex = new RegExp(`\\b${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
        normalized = normalized.replace(regex, '');
      }

      return normalized.replace(/\s+/g, ' ').trim();
    } catch (e) {
      console.warn('VoiceTracking: Error normalizing text:', e);
      return '';
    }
  }

  // Optimized Levenshtein distance with early termination
  function levenshteinDistance(a, b, maxDistance = Infinity) {
    if (!a || !b) return Math.max(a?.length || 0, b?.length || 0);
    if (a === b) return 0;
    
    const aLen = a.length;
    const bLen = b.length;
    
    // Early termination for obviously bad matches
    if (Math.abs(aLen - bLen) > maxDistance) return maxDistance + 1;
    
    if (aLen === 0) return bLen;
    if (bLen === 0) return aLen;

    const matrix = [];
    
    // Initialize matrix
    for (let i = 0; i <= bLen; i++) {
      matrix[i] = [i];
    }
    for (let j = 0; j <= aLen; j++) {
      matrix[0][j] = j;
    }

    // Fill matrix with early termination
    for (let i = 1; i <= bLen; i++) {
      let minRowValue = Infinity;
      for (let j = 1; j <= aLen; j++) {
        if (b.charAt(i-1) === a.charAt(j-1)) {
          matrix[i][j] = matrix[i-1][j-1];
        } else {
          matrix[i][j] = Math.min(
            matrix[i-1][j-1] + 1,
            matrix[i][j-1] + 1,
            matrix[i-1][j] + 1
          );
        }
        minRowValue = Math.min(minRowValue, matrix[i][j]);
      }
      
      // Early termination if this row exceeds max distance
      if (minRowValue > maxDistance) {
        return maxDistance + 1;
      }
    }

    return matrix[bLen][aLen];
  }

  // Enhanced similarity calculation
  function calculateSimilarity(str1, str2) {
    const maxLen = Math.max(str1.length, str2.length);
    if (maxLen === 0) return 0;
    
    const distance = levenshteinDistance(str1, str2, activeConfig.maxDistance * maxLen);
    return distance / maxLen;
  }

  // Enhanced element matching with multiple strategies
  function findBestElementMatch(spokenText) {
    if (!spokenText || typeof spokenText !== 'string') return null;
    
    const normalizedSpoken = normalizeText(spokenText);
    if (!normalizedSpoken) return null;

    // Always do a fresh scan when actively searching
    scanPageForClickableElements();

    console.log(`VoiceTracking: Looking for element matching: "${normalizedSpoken}" among ${state.clickableElements.length} elements`);

    // Strategy 1: Exact match
    for (const element of state.clickableElements) {
      if (element.normalizedText === normalizedSpoken) {
        console.log(`VoiceTracking: Exact match found: "${element.normalizedText}"`);
        return element;
      }
    }

    // Strategy 2: ID-based match (e.g., "button 1" -> "#btn1")
    const numberMatch = normalizedSpoken.match(/\b(\d+)\b/);
    if (numberMatch) {
      const number = numberMatch[1];
      
      // Try specific ID patterns
      const idPatterns = [`btn${number}`, `button${number}`, `item${number}`];
      for (const pattern of idPatterns) {
        const elementById = document.getElementById(pattern);
        if (elementById) {
          const foundElement = state.clickableElements.find(el => el.element === elementById);
          if (foundElement) {
            console.log(`VoiceTracking: ID-based match found: ${pattern}`);
            return foundElement;
          }
        }
      }
      
      // Try elements containing the number
      for (const element of state.clickableElements) {
        if (new RegExp(`\\b${number}\\b`).test(element.normalizedText)) {
          console.log(`VoiceTracking: Number match found: "${element.normalizedText}"`);
          return element;
        }
      }
    }

    // Strategy 3: Partial matching
    for (const element of state.clickableElements) {
      if (element.normalizedText.length < 2) continue;

      // Check if spoken text contains element text
      if (normalizedSpoken.includes(element.normalizedText)) {
        console.log(`VoiceTracking: Partial match (spoken contains element): "${element.normalizedText}"`);
        return element;
      }

      // Check if element text contains spoken text (for longer element names)
      if (normalizedSpoken.length >= 3 && element.normalizedText.includes(normalizedSpoken)) {
        console.log(`VoiceTracking: Partial match (element contains spoken): "${element.normalizedText}"`);
        return element;
      }
    }

    // Strategy 4: Fuzzy matching
    if (activeConfig.useEnhancedMatching) {
      let bestMatch = null;
      let bestScore = activeConfig.maxDistance;

      for (const element of state.clickableElements) {
        if (element.normalizedText.length < 2) continue;

        const similarity = calculateSimilarity(normalizedSpoken, element.normalizedText);
        if (similarity < bestScore) {
          bestMatch = element;
          bestScore = similarity;
        }
      }

      if (bestMatch) {
        console.log(`VoiceTracking: Fuzzy match found: "${bestMatch.normalizedText}" (similarity: ${bestScore.toFixed(3)})`);
      }
      return bestMatch;
    }

    console.log(`VoiceTracking: No match found for: "${normalizedSpoken}"`);
    return null;
  }

  // Enhanced element highlighting with cleanup
  function highlightElement(element) {
    if (!element) return;
    
    clearElementHighlights();

    try {
      const rect = element.getBoundingClientRect();
      const highlight = document.createElement('div');

      highlight.className = 'voicetracking-highlight';
      highlight.style.cssText = `
        position: fixed;
        top: ${rect.top}px;
        left: ${rect.left}px;
        width: ${rect.width}px;
        height: ${rect.height}px;
      `;

      document.body.appendChild(highlight);
      state.elementHighlights.set(element, highlight);

      // Auto-clear highlight
      if (state.highlightTimeout) {
        clearTimeout(state.highlightTimeout);
      }
      
      state.highlightTimeout = utils.setTimeout(() => {
        clearElementHighlights();
      }, 1000); // Reduced timeout
      
    } catch (e) {
      console.error('VoiceTracking: Error highlighting element:', e);
    }
  }

  // Enhanced highlight cleanup
  function clearElementHighlights() {
    try {
      for (const [element, highlight] of state.elementHighlights) {
        if (highlight && highlight.parentNode) {
          highlight.parentNode.removeChild(highlight);
        }
      }
      state.elementHighlights.clear();
      
      if (state.highlightTimeout) {
        clearTimeout(state.highlightTimeout);
        state.highlightTimeout = null;
      }
    } catch (e) {
      console.error('VoiceTracking: Error clearing highlights:', e);
    }
  }

  // FIXED: Enhanced click execution with guaranteed state reset
  function doClickElement(element, text) {
    const currentTime = Date.now();
    
    // Check cooldown (but more lenient)
    if (currentTime - state.lastCommandTime < activeConfig.commandCooldown) {
      console.log(`VoiceTracking: Command on cooldown (${currentTime - state.lastCommandTime}ms since last)`);
      return false;
    }

    // Force reset if stuck
    if (state.isProcessingCommand) {
      console.warn('VoiceTracking: Command already processing, forcing reset');
      state.isProcessingCommand = false;
    }

    state.commandCounter++;
    console.log(`VoiceTracking: Processing command #${state.commandCounter} for element:`, element.tagName, text);

    try {
      state.isProcessingCommand = true;
      state.lastCommandTime = currentTime;
      state.lastClickedElement = element;

      highlightElement(element);
      showFeedback(`Clicking: "${utils.sanitizeText(text)}"`);

      updateListeningStatus('recognized');

      // FIXED: Always reset processing state after a timeout
      const resetStateTimer = utils.setTimeout(() => {
        state.isProcessingCommand = false;
        updateListeningStatus('listening');
        console.log(`VoiceTracking: Command #${state.commandCounter} state reset via timeout`);
      }, 1000); // Guaranteed reset after 1 second

      // Eye tracking integration (if available)
      if (typeof window.mouseX !== 'undefined' && typeof window.cursor !== 'undefined') {
        try {
          const rect = element.getBoundingClientRect();
          const centerX = rect.left + rect.width / 2;
          const centerY = rect.top + rect.height / 2;

          window.mouseX = centerX;
          window.mouseY = centerY;
          window.stableMouseX = centerX;
          window.stableMouseY = centerY;

          if (window.cursor && window.cursor.style) {
            window.cursor.style.left = centerX + 'px';
            window.cursor.style.top = centerY + 'px';
            window.cursor.style.display = 'block';
          }
        } catch (e) {
          console.warn('VoiceTracking: Eye tracking integration failed:', e);
        }
      }

      // FIXED: Improved click execution with immediate execution
      let clickSuccess = false;

      try {
        // Strategy 1: Native click (immediate)
        element.click();
        clickSuccess = true;
        console.log(`VoiceTracking: Command #${state.commandCounter} - Native click succeeded`);
      } catch (e) {
        console.warn(`VoiceTracking: Command #${state.commandCounter} - Native click failed:`, e);
        
        try {
          // Strategy 2: Mouse event (immediate)
          const clickEvent = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window,
            detail: 1,
            clientX: element.getBoundingClientRect().left + element.getBoundingClientRect().width / 2,
            clientY: element.getBoundingClientRect().top + element.getBoundingClientRect().height / 2
          });
          element.dispatchEvent(clickEvent);
          clickSuccess = true;
          console.log(`VoiceTracking: Command #${state.commandCounter} - Mouse event click succeeded`);
        } catch (e2) {
          console.warn(`VoiceTracking: Command #${state.commandCounter} - Mouse event click failed:`, e2);
          
          try {
            // Strategy 3: Focus and activate (immediate)
            if (element.focus) element.focus();
            
            // Different strategies for different element types
            if (element.tagName === 'INPUT' || element.tagName === 'BUTTON') {
              // Try form submission or button activation
              if (element.form && element.type === 'submit') {
                element.form.submit();
              } else if (element.type === 'button' || element.tagName === 'BUTTON') {
                const activateEvent = new Event('activate', { bubbles: true });
                element.dispatchEvent(activateEvent);
              }
            } else if (element.tagName === 'A' && element.href) {
              // For links, try navigation
              if (element.target === '_blank') {
                window.open(element.href, '_blank');
              } else {
                window.location.href = element.href;
              }
            }
            
            clickSuccess = true;
            console.log(`VoiceTracking: Command #${state.commandCounter} - Focus/activate strategy succeeded`);
          } catch (e3) {
            console.error(`VoiceTracking: Command #${state.commandCounter} - All click strategies failed:`, e3);
            showNotification('Could not interact with element', 2000, 'error');
          }
        }
      }

      // FIXED: Always reset state immediately after click attempt
      utils.setTimeout(() => {
        state.isProcessingCommand = false;
        updateListeningStatus('listening');
        console.log(`VoiceTracking: Command #${state.commandCounter} completed, state reset`);
      }, 200); // Quick reset

      return clickSuccess;

    } catch (e) {
      console.error(`VoiceTracking: Command #${state.commandCounter} - Error in doClickElement:`, e);
      state.isProcessingCommand = false;
      updateListeningStatus('listening');
      return false;
    }
  }

  // FIXED: Enhanced command processing with better state management
  function findAndClickElementByName(spokenText) {
    console.log(`VoiceTracking: Processing voice command: "${spokenText}"`);
    console.log(`VoiceTracking: Current state - isProcessing: ${state.isProcessingCommand}, enabled: ${activeConfig.enabled}`);
    
    if (!spokenText || typeof spokenText !== 'string') {
      console.warn('VoiceTracking: Invalid spoken text:', spokenText);
      return false;
    }

    // FIXED: Don't block if already processing, just log and continue
    if (state.isProcessingCommand) {
      console.log('VoiceTracking: Already processing a command, but allowing this one through');
      // Don't return false, let it process
    }

    updateListeningStatus('processing');

    try {
      const bestMatch = findBestElementMatch(spokenText);

      if (bestMatch) {
        console.log(`VoiceTracking: Found match, attempting click:`, bestMatch);
        return doClickElement(bestMatch.element, bestMatch.text);
      } else {
        updateListeningStatus('listening');
        showFeedback(`No match: "${utils.sanitizeText(spokenText)}"`);
        console.log(`VoiceTracking: No element found for "${spokenText}"`);
        
        // Log available elements for debugging
        if (state.clickableElements.length > 0) {
          console.log('VoiceTracking: Available elements:', state.clickableElements.slice(0, 5).map(el => el.normalizedText));
        }
        
        return false;
      }
    } catch (e) {
      console.error('VoiceTracking: Error in findAndClickElementByName:', e);
      state.isProcessingCommand = false;
      updateListeningStatus('listening');
      return false;
    }
  }

  // Enhanced element display with better UX
  function showClickableElements() {
    clearElementHighlights();
    
    // Remove existing labels
    document.querySelectorAll('.voicetracking-element-label').forEach(el => {
      if (el.parentNode) el.parentNode.removeChild(el);
    });

    // Force fresh scan
    scanPageForClickableElements();

    const elementsToShow = state.clickableElements
      .filter(element => {
        const rect = element.element.getBoundingClientRect();
        return rect.top >= 0 && rect.top <= window.innerHeight &&
               rect.left >= 0 && rect.left <= window.innerWidth;
      })
      .slice(0, activeConfig.maxElementsToShow);

    elementsToShow.forEach((element, index) => {
      try {
        const el = element.element;
        const rect = el.getBoundingClientRect();

        // Create highlight
        const highlight = document.createElement('div');
        highlight.className = 'voicetracking-highlight';
        highlight.style.cssText = `
          position: fixed;
          top: ${rect.top}px;
          left: ${rect.left}px;
          width: ${rect.width}px;
          height: ${rect.height}px;
          background: rgba(100, 100, 255, 0.2);
          border: 1px solid rgba(100, 100, 255, 0.8);
        `;
        document.body.appendChild(highlight);
        state.elementHighlights.set(el, highlight);

        // Create label
        const label = document.createElement('div');
        label.className = 'voicetracking-element-label';
        label.textContent = `"${utils.sanitizeText(element.normalizedText)}"`;
        label.style.cssText = `
          position: fixed;
          top: ${Math.max(rect.top - 25, 5)}px;
          left: ${rect.left}px;
          max-width: ${Math.min(rect.width, 200)}px;
        `;

        document.body.appendChild(label);
      } catch (e) {
        console.warn('VoiceTracking: Error showing element:', e);
      }
    });

    // Auto-clear after timeout
    utils.setTimeout(() => {
      clearElementHighlights();
      document.querySelectorAll('.voicetracking-element-label').forEach(el => {
        if (el.parentNode) el.parentNode.removeChild(el);
      });
    }, 8000);

    const message = `Showing ${elementsToShow.length} clickable elements`;
    showNotification(message, 3000);
    
    // Announce to screen readers
    if (state.elements.srAnnouncement) {
      state.elements.srAnnouncement.textContent = message;
    }
  }

  // Enhanced command setup with better error handling
  function setupElementAwareCommands() {
    if (typeof annyang === 'undefined') {
      console.warn('VoiceTracking: annyang not available for command setup');
      return false;
    }

    try {
      const commands = {};

      // Wildcard command for element interaction
      commands['*element'] = function(element) {
        if (element) {
          console.log(`VoiceTracking: Wildcard command triggered with: "${element}"`);
          findAndClickElementByName(element);
        }
      };

      // Navigation commands
      commands['show elements'] = showClickableElements;
      commands['show buttons'] = showClickableElements;
      commands['what can I say'] = showClickableElements;
      commands['help'] = showClickableElements;

      // Scroll commands
      commands['scroll down'] = function() {
        try {
          window.scrollBy({ top: 300, behavior: 'smooth' });
          showFeedback("Scrolling down");
        } catch (e) {
          console.error('VoiceTracking: Scroll down failed:', e);
        }
      };

      commands['scroll up'] = function() {
        try {
          window.scrollBy({ top: -300, behavior: 'smooth' });
          showFeedback("Scrolling up");
        } catch (e) {
          console.error('VoiceTracking: Scroll up failed:', e);
        }
      };

      // Debug commands
      commands['reset state'] = function() {
        utils.resetCommandState();
        state.cache.clear();
        scanPageForClickableElements();
        showFeedback("State reset");
      };

      // External integration commands
      commands['enable clicks'] = function() {
        if (window.conf) {
          window.conf.enableClicks = true;
          showNotification("Clicks enabled via voice command");
        }
      };

      commands['disable clicks'] = function() {
        if (window.conf) {
          window.conf.enableClicks = false;
          showNotification("Clicks disabled via voice command");
        }
      };

      // Test command
      commands['test'] = function() {
        showFeedback("Test command recognized successfully!");
        showNotification("Speech recognition working correctly", 2000);
      };

      annyang.removeCommands();
      annyang.addCommands(commands);
      console.log('VoiceTracking: Commands registered successfully');
      return true;
    } catch (e) {
      console.error('VoiceTracking: Error setting up commands:', e);
      return false;
    }
  }

  // Enhanced voice recognition reset with better coordination
  function resetVoiceRecognition(force = false) {
    if (typeof annyang === 'undefined') return false;

    try {
      updateListeningStatus('inactive');

      if (force) {
        annyang.abort();

        if (state.autoRecoveryTimer) {
          clearTimeout(state.autoRecoveryTimer);
          state.autoRecoveryTimer = null;
        }

        if (activeConfig.enabled) {
          utils.setTimeout(() => {
            try {
              annyang.start({
                autoRestart: true,
                continuous: true
              });
              state.errorRetryCount = 0;
              updateListeningStatus('listening');
              console.log('VoiceTracking: Force restart successful');
            } catch (e) {
              console.error('VoiceTracking: Force restart failed:', e);
              showNotification("Voice recognition restart failed", 3000, 'error');
            }
          }, 500);
        }
      }
      return true;
    } catch (e) {
      console.error('VoiceTracking: Error in reset:', e);
      return false;
    }
  }

  // Enhanced error recovery with exponential backoff
  function attemptErrorRecovery(errorType) {
    if (!activeConfig.autoRecover) return false;

    // Prevent infinite loops by implementing session-based counting
    const sessionKey = `recovery_${Date.now().toString().slice(-6)}`;
    if (!state.recoverySession || Date.now() - state.recoverySession.start > 60000) {
      state.recoverySession = { start: Date.now(), count: 0, key: sessionKey };
    }

    state.recoverySession.count++;
    
    if (state.recoverySession.count > activeConfig.maxErrorRetries) {
      console.warn('VoiceTracking: Max recovery attempts reached for session');
      showNotification('Voice recognition needs manual restart', 5000, 'warning');
      return false;
    }

    if (state.autoRecoveryTimer) {
      clearTimeout(state.autoRecoveryTimer);
    }

    // Exponential backoff
    const baseDelay = activeConfig.errorRetryDelay;
    const backoffMultiplier = Math.pow(2, state.recoverySession.count - 1);
    const recoveryDelay = Math.min(baseDelay * backoffMultiplier, 10000); // Max 10 seconds

    let shouldRecover = true;

    switch(errorType) {
      case 'network':
        showNotification(`Network error. Retrying in ${recoveryDelay/1000}s...`, recoveryDelay);
        break;
      case 'not-allowed':
        showNotification("Microphone access denied", 5000, 'error');
        state.microphoneAccessGranted = false;
        return false;
      case 'aborted':
        showNotification('Voice recognition restarting...', recoveryDelay);
        break;
      case 'audio-capture':
        showNotification('Microphone issue. Retrying...', recoveryDelay, 'warning');
        break;
      case 'no-speech':
        console.log('VoiceTracking: No speech detected, continuing...');
        return false; // Don't trigger recovery for no-speech
      default:
        showNotification(`Voice error (${errorType}). Retrying...`, recoveryDelay, 'warning');
        break;
    }

    if (shouldRecover) {
      state.autoRecoveryTimer = utils.setTimeout(() => {
        resetVoiceRecognition(true);
      }, recoveryDelay);
    }

    return shouldRecover;
  }

  // Fixed annyang library loading with proper fallback handling
  async function loadAnnyangLibrary() {
    if (typeof annyang !== 'undefined') return true;

    state.libraryLoadAttempts++;
    if (state.libraryLoadAttempts > state.maxLibraryLoadAttempts) {
      console.error('VoiceTracking: Max library load attempts reached');
      showNotification('Failed to load voice recognition library after multiple attempts', 5000, 'error');
      return false;
    }

    showNotification('Loading voice recognition library...', 3000);
    console.log(`VoiceTracking: Loading annyang library (attempt ${state.libraryLoadAttempts})...`);

    try {
      const script = document.createElement('script');
      script.src = 'https://cdnjs.cloudflare.com/ajax/libs/annyang/2.6.1/annyang.min.js';
      script.crossOrigin = 'anonymous';
      script.setAttribute('referrerpolicy', 'no-referrer');

      // Try with integrity first, fallback without if it fails
      if (state.libraryLoadAttempts === 1) {
        script.integrity = 'sha512-/kn8vBLACe2I8hL6TKVDrKxqEhS7LKIEqGdCZTuoFGNPJYkClIuPVQ6zxUmB5A5xhbg/YN6lhQRKABp7+DOQ5Q==';
      }
      // For subsequent attempts, don't use integrity check to avoid blocking

      const loadPromise = new Promise((resolve, reject) => {
        const timeout = utils.setTimeout(() => {
          reject(new Error('Script loading timeout'));
        }, 15000); // Increased timeout

        script.onload = () => {
          clearTimeout(timeout);
          console.log('VoiceTracking: Annyang script loaded successfully');
          resolve();
        };
        
        script.onerror = (e) => {
          clearTimeout(timeout);
          console.error('VoiceTracking: Script loading failed:', e);
          
          // Remove integrity for next attempt if this was an integrity failure
          if (script.integrity && state.libraryLoadAttempts < state.maxLibraryLoadAttempts) {
            console.log('VoiceTracking: Retrying without integrity check...');
            script.remove();
            // Recursive call for retry without integrity
            utils.setTimeout(() => loadAnnyangLibrary(), 1000);
            return;
          }
          
          reject(new Error('Script loading failed'));
        };
      });

      document.head.appendChild(script);
      await loadPromise;

      // Wait for annyang to initialize
      await new Promise(resolve => utils.setTimeout(resolve, 200));
      
      const isLoaded = typeof annyang !== 'undefined';
      if (isLoaded) {
        console.log('VoiceTracking: Annyang is now available');
        state.libraryLoadAttempts = 0; // Reset on success
      }
      
      return isLoaded;
    } catch (error) {
      console.error('VoiceTracking: Failed to load annyang:', error);
      
      // Try again without integrity check if this was the first attempt
      if (state.libraryLoadAttempts < state.maxLibraryLoadAttempts) {
        console.log('VoiceTracking: Retrying library load...');
        return loadAnnyangLibrary();
      }
      
      showNotification('Failed to load voice recognition library', 5000, 'error');
      return false;
    }
  }

  // Enhanced annyang initialization with comprehensive callbacks
  function initializeAnnyang() {
    if (typeof annyang === 'undefined') {
      console.error('VoiceTracking: annyang not available');
      return false;
    }

    try {
      setupElementAwareCommands();

      // Set up callbacks with error handling
      const callbacks = {
        soundstart: () => {
          updateListeningStatus('listening');
          console.log('VoiceTracking: Sound detected');
        },

        result: (phrases) => {
          updateListeningStatus('processing');
          console.log('VoiceTracking: Speech result:', phrases);
        },

        resultMatch: (userSaid, commandText, phrases) => {
          console.log(`VoiceTracking: Command matched: "${userSaid}" -> "${commandText}"`);
          // Don't set processing flag here since the command handler will manage it
        },

        resultNoMatch: (phrases) => {
          console.log('VoiceTracking: No command match, trying element match:', phrases[0]);
          if (phrases && phrases.length > 0) {
            const matched = findAndClickElementByName(phrases[0]);
            if (!matched) {
              updateListeningStatus('listening');
              showFeedback(`Not recognized: "${utils.sanitizeText(phrases[0])}"`);
            }
          } else {
            updateListeningStatus('listening');
          }
        },

        error: (error) => {
          const errorType = error?.error || 'unknown';
          console.error('VoiceTracking: Speech error:', errorType, error);

          // Don't ignore errors during command processing - handle them
          if (state.isProcessingCommand) {
            console.log('VoiceTracking: Error during command processing, resetting state');
            state.isProcessingCommand = false;
          }

          // Attempt recovery for recoverable errors
          const recoverableErrors = ['network', 'audio-capture', 'aborted'];
          if (recoverableErrors.includes(errorType)) {
            attemptErrorRecovery(errorType);
          } else if (errorType === 'not-allowed') {
            attemptErrorRecovery(errorType);
          }

          // Show error status briefly
          if (!['aborted', 'no-speech'].includes(errorType)) {
            updateListeningStatus('error');
            utils.setTimeout(() => {
              updateListeningStatus('listening');
            }, 1000);
          }
        },

        start: () => {
          updateListeningStatus('listening');
          console.log('VoiceTracking: Speech recognition started');
          
          // Reset recovery session on successful start
          state.recoverySession = null;
          // Reset processing state on start
          state.isProcessingCommand = false;
        },

        end: () => {
          if (activeConfig.enabled && state.listeningState !== 'processing') {
            updateListeningStatus('listening');
          } else if (!activeConfig.enabled) {
            updateListeningStatus('inactive');
          }
          console.log('VoiceTracking: Speech recognition ended');
        }
      };

      // Register callbacks with error handling
      for (const [event, callback] of Object.entries(callbacks)) {
        try {
          annyang.addCallback(event, (...args) => {
            try {
              callback(...args);
            } catch (e) {
              console.error(`VoiceTracking: Error in ${event} callback:`, e);
            }
          });
        } catch (e) {
          console.error(`VoiceTracking: Failed to register ${event} callback:`, e);
        }
      }

      return true;
    } catch (e) {
      console.error('VoiceTracking: Error initializing annyang:', e);
      return false;
    }
  }

  // Enhanced start function with better error handling
  async function start() {
    if (activeConfig.enabled) {
      console.log('VoiceTracking: Already enabled');
      return true;
    }

    try {
      // Step 1: Load library
      if (!await loadAnnyangLibrary()) {
        showNotification('Failed to load voice recognition library', 5000, 'error');
        return false;
      }

      // Step 2: Test microphone
      if (!await testMicrophoneAccess()) {
        return false;
      }

      // Step 3: Initialize annyang
      if (!initializeAnnyang()) {
        showNotification('Failed to initialize voice recognition', 5000, 'error');
        return false;
      }

      // Step 4: Set up commands
      setupElementAwareCommands();

      // Step 5: Start recognition
      annyang.start({
        autoRestart: true,
        continuous: true
      });

      activeConfig.enabled = true;
      state.isProcessingCommand = false; // Ensure clean state
      updateListeningStatus('listening');

      showNotification("Voice control activated! Say 'show elements' to see options.", 4000);

      // Show elements after brief delay
      utils.setTimeout(() => {
        showClickableElements();
      }, 1500);

      saveSettings();
      console.log('VoiceTracking: Started successfully');
      return true;

    } catch (e) {
      console.error('VoiceTracking: Start failed:', e);
      showNotification(`Failed to start: ${e.message}`, 5000, 'error');
      return false;
    }
  }

  // Enhanced stop function
  function stop() {
    if (!activeConfig.enabled) {
      console.log('VoiceTracking: Already disabled');
      return true;
    }

    try {
      if (typeof annyang !== 'undefined') {
        annyang.abort();
      }

      activeConfig.enabled = false;
      state.isProcessingCommand = false; // Reset state
      updateListeningStatus('inactive');
      clearElementHighlights();

      // Clear any active timers
      if (state.autoRecoveryTimer) {
        clearTimeout(state.autoRecoveryTimer);
        state.autoRecoveryTimer = null;
      }

      showNotification("Voice control deactivated", 2000);
      saveSettings();
      console.log('VoiceTracking: Stopped successfully');
      return true;

    } catch (e) {
      console.error('VoiceTracking: Stop failed:', e);
      return false;
    }
  }

  // Enhanced toggle function
  function toggle() {
    try {
      return activeConfig.enabled ? stop() : start();
    } catch (e) {
      console.error('VoiceTracking: Toggle failed:', e);
      return false;
    }
  }

  // Enhanced mutation observer with better performance
  function setupMutationObserver() {
    if (!utils.detectFeatures().mutationObserver) {
      console.warn('VoiceTracking: MutationObserver not supported');
      return;
    }

    try {
      const debouncedRescan = utils.debounce(() => {
        if (activeConfig.enabled) {
          console.log('VoiceTracking: DOM changed, rescanning...');
          scanPageForClickableElements();
        }
      }, activeConfig.debounceDelay);

      state.mutationObserver = new MutationObserver(mutations => {
        const shouldRescan = mutations.some(mutation =>
          mutation.type === 'childList' ||
          (mutation.type === 'attributes' &&
           ['style', 'class', 'value', 'aria-label', 'title'].includes(mutation.attributeName))
        );

        if (shouldRescan) {
          // Invalidate cache
          state.cache.delete('clickableElements');
          debouncedRescan();
        }
      });

      state.mutationObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class', 'value', 'aria-label', 'title']
      });

      console.log('VoiceTracking: MutationObserver configured');
    } catch (e) {
      console.error('VoiceTracking: MutationObserver setup failed:', e);
    }
  }

  // Enhanced settings management with validation
  function saveSettings() {
    if (!activeConfig.persistSettings) return;

    try {
      const settings = {
        enabled: activeConfig.enabled,
        widgetPosition: activeConfig.widgetPosition,
        widgetSize: activeConfig.widgetSize,
        showVisualFeedback: activeConfig.showVisualFeedback,
        timestamp: Date.now()
      };

      if (state.elements.widget) {
        settings.widgetLeft = state.elements.widget.style.left;
        settings.widgetTop = state.elements.widget.style.top;
      }

      const settingsString = JSON.stringify(settings);
      localStorage.setItem('voicetracking-settings', settingsString);
      console.log('VoiceTracking: Settings saved');
    } catch (e) {
      console.error('VoiceTracking: Failed to save settings:', e);
    }
  }

  // Enhanced settings loading with validation
  function loadSettings() {
    if (!activeConfig.persistSettings) return;

    try {
      const saved = localStorage.getItem('voicetracking-settings');
      if (!saved) return;

      const settings = JSON.parse(saved);
      
      // Validate settings age (7 days)
      if (!settings.timestamp || Date.now() - settings.timestamp > 7 * 24 * 60 * 60 * 1000) {
        console.log('VoiceTracking: Saved settings too old, ignoring');
        return;
      }

      // Validate and apply settings
      if (utils.validateConfig(settings)) {
        Object.assign(activeConfig, settings);

        // Apply widget position if available
        if (state.elements.widget && settings.widgetLeft && settings.widgetTop) {
          state.elements.widget.style.left = settings.widgetLeft;
          state.elements.widget.style.top = settings.widgetTop;
          state.elements.widget.style.right = 'auto';
          state.elements.widget.style.bottom = 'auto';
        }

        console.log('VoiceTracking: Settings loaded');
      } else {
        console.warn('VoiceTracking: Invalid saved settings, using defaults');
      }
    } catch (e) {
      console.error('VoiceTracking: Failed to load settings:', e);
    }
  }

  // Enhanced widget position saving
  function saveWidgetPosition() {
    if (!activeConfig.persistSettings || !state.elements.widget) return;

    try {
      const current = JSON.parse(localStorage.getItem('voicetracking-settings') || '{}');
      current.widgetLeft = state.elements.widget.style.left;
      current.widgetTop = state.elements.widget.style.top;
      current.timestamp = Date.now();
      
      localStorage.setItem('voicetracking-settings', JSON.stringify(current));
      console.log('VoiceTracking: Widget position saved');
    } catch (e) {
      console.error('VoiceTracking: Failed to save widget position:', e);
    }
  }

  // Enhanced compatibility checking
  function checkCompatibility() {
    const features = utils.detectFeatures();
    const issues = [];

    if (!features.speechRecognition) {
      issues.push('Speech Recognition API not supported');
    }

    if (!features.mediaDevices) {
      issues.push('Microphone access not supported');
    }

    if (!features.secureContext) {
      issues.push('HTTPS required for microphone access');
    }

    return { features, issues };
  }

  // Enhanced cleanup function
  function cleanup() {
    try {
      // Stop voice recognition
      if (activeConfig.enabled) {
        stop();
      }

      // Clean up timers
      for (const timer of state.timers) {
        clearTimeout(timer);
      }
      state.timers.clear();

      // Clean up event listeners
      for (const [element, events] of state.eventListeners) {
        for (const [event, {handler, options}] of events) {
          try {
            element.removeEventListener(event, handler, options);
          } catch (e) {
            console.warn('VoiceTracking: Error removing event listener:', e);
          }
        }
      }
      state.eventListeners.clear();

      // Clean up mutation observer
      if (state.mutationObserver) {
        state.mutationObserver.disconnect();
        state.mutationObserver = null;
      }

      // Clean up highlights
      clearElementHighlights();

      // Clean up UI elements
      const elementsToRemove = [
        '#voicetracking-styles',
        '.voicetracking-widget',
        '.voicetracking-feedback',
        '.voicetracking-notification',
        '.voicetracking-sr-only'
      ];

      elementsToRemove.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
          if (el.parentNode) el.parentNode.removeChild(el);
        });
      });

      // Reset state
      Object.assign(state, {
        listeningState: 'inactive',
        clickableElements: [],
        elementHighlights: new Map(),
        isInitialized: false,
        isProcessingCommand: false,
        elements: {},
        cache: new Map(),
        libraryLoadAttempts: 0,
        commandCounter: 0
      });

      console.log('VoiceTracking: Cleanup completed');
    } catch (e) {
      console.error('VoiceTracking: Cleanup error:', e);
    }
  }

  // Enhanced initialization
  async function initialize() {
    console.log('VoiceTracking: Initializing v3.2.2...');

    try {
      const compatibility = checkCompatibility();
      
      if (compatibility.issues.length > 0) {
        showNotification(`Voice control may not work: ${compatibility.issues.join(', ')}`, 8000, 'warning');
        console.warn('VoiceTracking: Compatibility issues:', compatibility.issues);
      }

      createStyles();
      createUI();
      loadSettings();
      setupMutationObserver();
      scanPageForClickableElements();

      showNotification('Voice control ready! Click Start or press Ctrl+Shift+V', 5000);

      // Auto-start if configured
      if (activeConfig.autoStart && activeConfig.enabled) {
        console.log('VoiceTracking: Auto-starting...');
        utils.setTimeout(() => start(), 1000);
      }

      state.isInitialized = true;
      console.log('VoiceTracking: Initialization complete');

    } catch (e) {
      console.error('VoiceTracking: Initialization failed:', e);
      showNotification('Voice control initialization failed', 5000, 'error');
    }
  }

  // Public API with validation
  window.VoiceTracking = Object.freeze({
    start,
    stop,
    toggle,
    showClickableElements,
    scanPageForClickableElements,
    findBestElementMatch,
    resetVoiceRecognition,
    testMicrophoneAccess,
    destroy: cleanup,
    
    // Debug functions
    resetCommandState: utils.resetCommandState,
    getDebugInfo: () => ({
      commandCounter: state.commandCounter,
      isProcessingCommand: state.isProcessingCommand,
      lastCommandTime: state.lastCommandTime,
      lastClickedElement: state.lastClickedElement,
      clickableElementsCount: state.clickableElements.length
    }),
    
    // Configuration management
    getConfig: () => ({ ...activeConfig }),
    updateConfig: (newConfig) => {
      if (!utils.validateConfig(newConfig)) {
        console.error('VoiceTracking: Invalid configuration provided');
        return false;
      }
      Object.assign(activeConfig, newConfig);
      saveSettings();
      console.log('VoiceTracking: Configuration updated');
      return true;
    },
    
    // State getters
    isEnabled: () => activeConfig.enabled,
    getListeningState: () => state.listeningState,
    getState: () => ({
      enabled: activeConfig.enabled,
      listeningState: state.listeningState,
      clickableElementsCount: state.clickableElements.length,
      microphoneAccessGranted: state.microphoneAccessGranted,
      hasSpeechRecognitionSupport: state.hasSpeechRecognitionSupport,
      detectedBrowser: state.detectedBrowser,
      isInitialized: state.isInitialized,
      libraryLoadAttempts: state.libraryLoadAttempts,
      isProcessingCommand: state.isProcessingCommand,
      commandCounter: state.commandCounter
    })
  });

  // Set the loaded flag
  window.VoiceTrackingLoaded = true;

  // Auto-initialize when DOM is ready
  if (document.readyState === 'loading') {
    utils.addEventListener(document, 'DOMContentLoaded', initialize);
  } else {
    utils.setTimeout(initialize, 100);
  }

  // Enhanced keyboard shortcuts
  utils.addEventListener(document, 'keydown', (e) => {
    try {
      // Ctrl+Shift+V to toggle
      if (e.ctrlKey && e.shiftKey && e.key === 'V') {
        e.preventDefault();
        toggle();
      }

      // Ctrl+Shift+S to show elements
      if (e.ctrlKey && e.shiftKey && e.key === 'S') {
        e.preventDefault();
        showClickableElements();
      }

      // Ctrl+Shift+R to reset state (for debugging)
      if (e.ctrlKey && e.shiftKey && e.key === 'R') {
        e.preventDefault();
        utils.resetCommandState();
        state.cache.clear();
        scanPageForClickableElements();
        showNotification('Voice control state reset', 2000);
      }
    } catch (error) {
      console.error('VoiceTracking: Keyboard shortcut error:', error);
    }
  });

  // Enhanced cleanup on unload
  utils.addEventListener(window, 'beforeunload', cleanup);

  // Enhanced visibility change handling
  utils.addEventListener(document, 'visibilitychange', () => {
    if (typeof annyang === 'undefined' || !activeConfig.enabled) return;

    try {
      if (document.hidden) {
        if (annyang.isListening && annyang.isListening()) {
          annyang.pause();
          console.log('VoiceTracking: Paused due to page hidden');
        }
      } else {
        if (annyang.resume) {
          annyang.resume();
        } else {
          annyang.start({ autoRestart: true, continuous: true });
        }
        console.log('VoiceTracking: Resumed due to page visible');
      }
    } catch (e) {
      console.warn('VoiceTracking: Visibility change handling error:', e);
    }
  });

  console.log('VoiceTracking.js v3.2.2 - Click Reliability Fixes loaded successfully!');
  console.log('Fixed: Command processing state, click execution, cooldown issues');
  console.log('Shortcuts: Ctrl+Shift+V (toggle), Ctrl+Shift+S (show elements), Ctrl+Shift+R (reset state)');

})();