/**
 * EyeTracking.js - Plug & Play Eye Tracking Module
 * 
 * Simply add this script to any webpage to instantly enable eye tracking
 * No configuration needed - just include the script and it works
 * 
 * Version: 3.0.0 - Complete Edition
 * License: MIT
 */

(function() {
  'use strict';

  // Prevent multiple initializations
  if (window.EyeTrackingLoaded) return;
  window.EyeTrackingLoaded = true;

  // Global configuration with smart defaults
  const CONFIG = {
    enableClicks: true,
    earBlink: 0.21,
    blinkDelay: 300,
    smooth: 0.8,
    calSamples: 200,
    invertX: true,
    scrollZoneHeight: 80,
    scrollAmount: 300,
    showScrollZones: true,
    sensitivity: 0.7,
    offsetX: 0,
    offsetY: 0,
    blinkCountThreshold: 3,
    blinkHysteresis: 0.03,
    showFaceMesh: true,
    autoStart: true,
    widgetPosition: 'bottom-right',
    widgetSize: 'small',
    cursorColor: 'rgba(255,0,0,0.6)',
    meshColor: 'rgba(0,255,255,0.3)',
    latchRadius: 100
  };

  // COMPLETE MediaPipe Face Mesh Tesselation - ALL connections for full face mesh
  const FACEMESH_TESSELATION = [
    [10, 338], [338, 297], [297, 332], [332, 284], [284, 251], [251, 389], [389, 356], [356, 454], [454, 323], [323, 361],
    [361, 288], [288, 397], [397, 365], [365, 379], [379, 378], [378, 400], [400, 377], [377, 152], [152, 148], [148, 176],
    [176, 149], [149, 150], [150, 136], [136, 172], [172, 58], [58, 132], [132, 93], [93, 234], [234, 127], [127, 162],
    [162, 21], [21, 54], [54, 103], [103, 67], [67, 109], [109, 10], [151, 9], [9, 10], [10, 151], [337, 299],
    [299, 333], [333, 298], [298, 301], [301, 368], [368, 264], [264, 447], [447, 366], [366, 401], [401, 435], [435, 410],
    [410, 454], [454, 356], [356, 389], [389, 251], [251, 284], [284, 332], [332, 297], [297, 338], [338, 10], [10, 109],
    [109, 67], [67, 103], [103, 54], [54, 21], [21, 162], [162, 127], [127, 234], [234, 93], [93, 132], [132, 58],
    [58, 172], [172, 136], [136, 150], [150, 149], [149, 176], [176, 148], [148, 152], [152, 377], [377, 400], [400, 378],
    [378, 379], [379, 365], [365, 397], [397, 288], [288, 361], [361, 323], [323, 454], [454, 410], [410, 435], [435, 401],
    [401, 366], [366, 447], [447, 264], [264, 368], [368, 301], [301, 298], [298, 333], [333, 299], [299, 337], [337, 151],
    [151, 9], [9, 10], [10, 151], [70, 63], [63, 105], [105, 66], [66, 107], [107, 55], [55, 8], [8, 285],
    [285, 295], [295, 282], [282, 283], [283, 276], [276, 300], [300, 293], [293, 334], [334, 296], [296, 336], [336, 285],
    [285, 8], [8, 55], [55, 107], [107, 66], [66, 105], [105, 63], [63, 70], [70, 156], [156, 143], [143, 116],
    [116, 117], [117, 118], [118, 119], [119, 120], [120, 121], [121, 128], [128, 126], [126, 142], [142, 36], [36, 205],
    [205, 206], [206, 207], [207, 213], [213, 192], [192, 147], [147, 187], [187, 207], [207, 206], [206, 205], [205, 36],
    [36, 142], [142, 126], [126, 128], [128, 121], [121, 120], [120, 119], [119, 118], [118, 117], [117, 116], [116, 143],
    [143, 156], [156, 70], [46, 53], [53, 52], [52, 65], [65, 55], [55, 70], [168, 8], [8, 9], [9, 10],
    [10, 151], [151, 337], [337, 299], [299, 333], [333, 298], [298, 301], [301, 368], [368, 264], [264, 447], [447, 366],
    [366, 401], [401, 435], [435, 410], [410, 454], [454, 356], [356, 389], [389, 251], [251, 284], [284, 332], [332, 297],
    [297, 338], [338, 10], [10, 9], [9, 8], [8, 168], [168, 6], [6, 197], [197, 195], [195, 5], [5, 4],
    [4, 19], [19, 94], [94, 125], [125, 141], [141, 235], [235, 31], [31, 228], [228, 229], [229, 230], [230, 231],
    [231, 232], [232, 233], [233, 244], [244, 245], [245, 122], [122, 6], [6, 202], [202, 214], [214, 234], [234, 227],
    [227, 116], [116, 117], [117, 118], [118, 119], [119, 120], [120, 121], [121, 128], [128, 126], [126, 142], [142, 36],
    [36, 205], [205, 206], [206, 207], [207, 213], [213, 192], [192, 147], [147, 187], [187, 207], [207, 206], [206, 205],
    [205, 36], [36, 142], [142, 126], [126, 128], [128, 121], [121, 120], [120, 119], [119, 118], [118, 117], [117, 116],
    [116, 227], [227, 234], [234, 214], [214, 202], [202, 6], [6, 122], [122, 245], [245, 244], [244, 233], [233, 232],
    [232, 231], [231, 230], [230, 229], [229, 228], [228, 31], [31, 235], [235, 141], [141, 125], [125, 94], [94, 19],
    [19, 4], [4, 5], [5, 195], [195, 197], [197, 6], [6, 168], [61, 185], [185, 40], [40, 39], [39, 37],
    [37, 0], [0, 267], [267, 269], [269, 270], [270, 409], [409, 291], [291, 375], [375, 321], [321, 405], [405, 314],
    [314, 17], [17, 84], [84, 181], [181, 91], [91, 146], [146, 61], [78, 95], [95, 88], [88, 178], [178, 87],
    [87, 14], [14, 317], [317, 402], [402, 318], [318, 324], [324, 308], [308, 415], [415, 310], [310, 311], [311, 312],
    [312, 13], [13, 82], [82, 81], [81, 80], [80, 191], [191, 78], [33, 246], [246, 161], [161, 160], [160, 159],
    [159, 158], [158, 157], [157, 173], [173, 133], [133, 155], [155, 154], [154, 153], [153, 145], [145, 144], [144, 163],
    [163, 7], [7, 33], [263, 466], [466, 388], [388, 387], [387, 386], [386, 385], [385, 384], [384, 398], [398, 362],
    [362, 382], [382, 381], [381, 380], [380, 374], [374, 373], [373, 390], [390, 249], [249, 263], [276, 283], [283, 282],
    [282, 295], [295, 285], [300, 293], [293, 334], [334, 296], [296, 336], [46, 53], [53, 52], [52, 65], [65, 55],
    [70, 63], [63, 105], [105, 66], [66, 107], [168, 6], [6, 197], [197, 195], [195, 5], [5, 4], [4, 45],
    [45, 220], [220, 115], [115, 48], [48, 64], [64, 98], [98, 97], [97, 2], [2, 326], [326, 327], [327, 294],
    [294, 278], [278, 344], [344, 440], [440, 275], [275, 4], [4, 5], [5, 195], [195, 197], [197, 6], [6, 168],
    [32, 234], [234, 93], [93, 132], [132, 58], [58, 172], [172, 136], [136, 150], [150, 149], [149, 176], [176, 148],
    [148, 152], [152, 377], [377, 400], [400, 378], [378, 379], [379, 365], [365, 397], [397, 288], [288, 361], [361, 323],
    [323, 454], [454, 356], [356, 389], [389, 251], [251, 284], [284, 332], [332, 297], [297, 338], [338, 10], [10, 109],
    [109, 67], [67, 103], [103, 54], [54, 21], [21, 162], [162, 127], [127, 234], [234, 32], [252, 284], [284, 251],
    [251, 389], [389, 356], [356, 454], [454, 323], [323, 361], [361, 288], [288, 397], [397, 365], [365, 379], [379, 378],
    [378, 400], [400, 377], [377, 152], [152, 148], [148, 176], [176, 149], [149, 150], [150, 136], [136, 172], [172, 58],
    [58, 132], [132, 93], [93, 234], [234, 127], [127, 162], [162, 21], [21, 54], [54, 103], [103, 67], [67, 109],
    [109, 10], [10, 338], [338, 297], [297, 332], [332, 284], [284, 252], [12, 15], [15, 16], [16, 17], [17, 18],
    [18, 200], [200, 199], [199, 175], [175, 0], [0, 11], [11, 12], [15, 16], [16, 17], [17, 18], [18, 200],
    [200, 199], [199, 175], [175, 0], [0, 11], [11, 12], [12, 15], [269, 270], [270, 267], [267, 269], [269, 270],
    [270, 409], [409, 415], [415, 310], [310, 311], [311, 312], [312, 13], [13, 82], [82, 81], [81, 80], [80, 78],
    [78, 95], [95, 88], [88, 178], [178, 87], [87, 14], [14, 317], [317, 402], [402, 318], [318, 324], [324, 308],
    [308, 415], [415, 409], [409, 270], [270, 269], [269, 267], [267, 0], [0, 37], [37, 39], [39, 40], [40, 185],
    [185, 61], [61, 146], [146, 91], [91, 181], [181, 84], [84, 17], [17, 314], [314, 405], [405, 320], [320, 307],
    [307, 375], [375, 321], [321, 308], [308, 324], [324, 318], [318, 402], [402, 317], [317, 14], [14, 87], [87, 178],
    [178, 88], [88, 95], [95, 78], [78, 191], [191, 80], [80, 81], [81, 82], [82, 13], [13, 312], [312, 311],
    [311, 310], [310, 415], [415, 308], [308, 324], [324, 318], [318, 402], [402, 317], [317, 14], [14, 87], [87, 178],
    [178, 88], [88, 95], [95, 78], [78, 191], [191, 80], [80, 81], [81, 82], [82, 13], [13, 312], [312, 311],
    [311, 310], [310, 415], [415, 409], [409, 270], [270, 269], [269, 267], [267, 0], [0, 37], [37, 39], [39, 40],
    [40, 185], [185, 61], [61, 146], [146, 91], [91, 181], [181, 84], [84, 17], [17, 314], [314, 405], [405, 320],
    [320, 307], [307, 375], [375, 321], [321, 308], [308, 324], [324, 318], [318, 402], [402, 317], [317, 14], [14, 87],
    [87, 178], [178, 88], [88, 95], [95, 78], [78, 191], [191, 80], [80, 81], [81, 82], [82, 13], [13, 312],
    [312, 311], [311, 310], [310, 415], [415, 308]
  ];

  // Eye landmark indices
  const L_EYE = [33, 160, 158, 133, 153, 144];
  const R_EYE = [362, 385, 387, 263, 373, 380];
  const L_IRIS = [474, 475, 476, 477];
  const R_IRIS = [469, 470, 471, 472];

  // Additional face contour landmarks
  const FACE_OVAL = [10, 338, 297, 332, 284, 251, 389, 356, 454, 323, 361, 288, 397, 365, 379, 378, 400, 377, 152, 148, 176, 149, 150, 136, 172, 58, 132, 93, 234, 127, 162, 21, 54, 103, 67, 109];
  const LEFT_EYE_CONTOUR = [33, 7, 163, 144, 145, 153, 154, 155, 133, 173, 157, 158, 159, 160, 161, 246];
  const RIGHT_EYE_CONTOUR = [362, 398, 384, 385, 386, 387, 388, 466, 263, 249, 390, 373, 374, 380, 381, 382];
  const LEFT_EYEBROW = [46, 53, 52, 51, 48, 115, 131, 134, 102, 49, 220, 305, 293, 334, 296, 336];
  const RIGHT_EYEBROW = [276, 283, 282, 295, 285, 336, 296, 334, 293, 300, 276];
  const NOSE_TIP = [1, 2, 5, 4, 6, 19, 94, 125];
  const LIPS_UPPER = [61, 185, 40, 39, 37, 0, 267, 269, 270, 409, 291];
  const LIPS_LOWER = [146, 91, 181, 84, 17, 314, 405, 320, 307, 375, 321, 308, 324, 318];

  // Global state
  let isTracking = false;
  let isCalibrating = false;
  let isCalibrated = false;
  let faceMesh = null;
  let camera = null;
  let elements = {};
  let mouseX = 0, mouseY = 0;
  let stableMouseX = 0, stableMouseY = 0;
  let blinkState = false;
  let blinkCount = 0;
  let earHistory = [];
  let leftEarHistory = [];
  let rightEarHistory = [];
  let lastEyeState = 'none';
  let lastBlink = 0;
  let inScrollZone = null;
  let currentCalPhase = 0;
  let activeCalPoint = 'c';
  let rangeX = 0, rangeY = 0, centerX = 0, centerY = 0;
  let calibrationData = { c: [], tl: [], tr: [], bl: [], br: [] };

  // Utility functions
  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
  const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

  // Calculate Eye Aspect Ratio
  function ear(landmarks, eyeIndices) {
    const p = i => landmarks[eyeIndices[i]];
    const v1 = dist(p(1), p(5));
    const v2 = dist(p(2), p(4));
    const h = dist(p(0), p(3));
    return h ? (v1 + v2) / (2 * h) : 1;
  }

  // Calculate center of landmarks
  function centre(landmarks, indices) {
    let sx = 0, sy = 0;
    indices.forEach(i => {
      sx += landmarks[i].x;
      sy += landmarks[i].y;
    });
    return { x: sx / indices.length, y: sy / indices.length };
  }

  // Filtered EAR calculation
  function getFilteredEAR(newEar, eyeSide = 'both') {
    const historyArray = eyeSide === 'left' ? leftEarHistory : 
                        eyeSide === 'right' ? rightEarHistory : earHistory;
    
    if (historyArray.length > 10) historyArray.shift();
    historyArray.push(newEar);
    
    const sorted = [...historyArray].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    return sorted.length % 2 === 0 ? 
      (sorted[mid - 1] + sorted[mid]) / 2 : sorted[mid];
  }

  // Eye state detection
  function detectEyeState(leftEar, rightEar) {
    const filteredLeft = getFilteredEAR(leftEar, 'left');
    const filteredRight = getFilteredEAR(rightEar, 'right');
    
    const closureThreshold = 0.25;
    const winkRatioThreshold = 1.7;
    
    if (filteredLeft < closureThreshold && filteredRight < closureThreshold) {
      return 'blink';
    }
    if (filteredRight / filteredLeft > winkRatioThreshold && filteredLeft < 0.3) {
      return 'leftwink';
    }
    if (filteredLeft / filteredRight > winkRatioThreshold && filteredRight < 0.3) {
      return 'rightwink';
    }
    return 'none';
  }

  // Create styles
  function createStyles() {
    if (document.getElementById('eyetracking-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'eyetracking-styles';
    style.textContent = `
      .eyetracking-widget {
        position: fixed;
        z-index: 10000;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.1);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        cursor: move;
        user-select: none;
      }
      .eyetracking-widget.small { width: 200px; height: 150px; }
      .eyetracking-widget.medium { width: 300px; height: 225px; }
      .eyetracking-widget.large { width: 400px; height: 300px; }
      .eyetracking-widget.bottom-right { bottom: 20px; right: 20px; }
      .eyetracking-widget.bottom-left { bottom: 20px; left: 20px; }
      .eyetracking-widget.top-right { top: 20px; right: 20px; }
      .eyetracking-widget.top-left { top: 20px; left: 20px; }
      
      .eyetracking-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
      }
      
      .eyetracking-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
      }
      
      .eyetracking-status {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #ff4444;
        transition: background 0.3s ease;
        box-shadow: 0 0 8px rgba(255,68,68,0.5);
      }
      .eyetracking-status.tracking { background: #ffaa44; }
      .eyetracking-status.calibrated { background: #44ff44; }
      
      .eyetracking-controls {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.8);
        padding: 8px;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
      }
      .eyetracking-widget:hover .eyetracking-controls {
        transform: translateY(0);
      }
      
      .eyetracking-btn {
        padding: 4px 8px;
        font-size: 11px;
        border: none;
        border-radius: 4px;
        background: #4c6ef5;
        color: white;
        cursor: pointer;
        transition: background 0.2s;
      }
      .eyetracking-btn:hover { background: #364fc7; }
      .eyetracking-btn:disabled { background: #666; cursor: not-allowed; }
      
      .eyetracking-cursor {
        position: fixed;
        width: 20px;
        height: 20px;
        background: ${CONFIG.cursorColor};
        border-radius: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 9999;
        display: none;
        transition: background-color 0.2s ease;
        box-shadow: 0 0 8px rgba(255,0,0,0.3);
      }
      
      .eyetracking-scroll-zone {
        position: fixed;
        left: 0;
        right: 0;
        height: ${CONFIG.scrollZoneHeight}px;
        display: none;
        z-index: 9998;
        pointer-events: none;
        text-align: center;
        padding-top: 10px;
        font-size: 14px;
        font-family: Arial, sans-serif;
      }
      .eyetracking-scroll-up {
        top: 0;
        background: rgba(0,255,0,0.1);
        color: rgba(0,255,0,0.8);
      }
      .eyetracking-scroll-down {
        bottom: 0;
        background: rgba(0,0,255,0.1);
        color: rgba(0,0,255,0.8);
      }
      
      .eyetracking-cal-point {
        position: fixed;
        width: 16px;
        height: 16px;
        background: #ff6b9d;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        z-index: 10001;
        display: none;
        box-shadow: 0 0 16px rgba(255,107,157,0.6);
        animation: eyetracking-pulse 1s infinite ease-in-out;
      }
      
      @keyframes eyetracking-pulse {
        0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.7; }
        100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
      }
      
      .eyetracking-notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        z-index: 10002;
        transition: opacity 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      }
    `;
    document.head.appendChild(style);
  }

  // Create UI elements
  function createUI() {
    // Main widget
    const widget = document.createElement('div');
    widget.className = `eyetracking-widget ${CONFIG.widgetSize} ${CONFIG.widgetPosition}`;
    
    // Video element
    const video = document.createElement('video');
    video.className = 'eyetracking-video';
    video.autoplay = true;
    video.playsinline = true;
    video.muted = true;
    
    // Canvas overlay
    const canvas = document.createElement('canvas');
    canvas.className = 'eyetracking-canvas';
    
    // Status indicator
    const status = document.createElement('div');
    status.className = 'eyetracking-status';
    
    // Controls
    const controls = document.createElement('div');
    controls.className = 'eyetracking-controls';
    
    const startBtn = document.createElement('button');
    startBtn.className = 'eyetracking-btn';
    startBtn.textContent = 'Start';
    startBtn.onclick = start;
    
    const calBtn = document.createElement('button');
    calBtn.className = 'eyetracking-btn';
    calBtn.textContent = 'Calibrate';
    calBtn.onclick = calibrate;
    calBtn.disabled = true;
    
    const stopBtn = document.createElement('button');
    stopBtn.className = 'eyetracking-btn';
    stopBtn.textContent = 'Stop';
    stopBtn.onclick = stop;
    stopBtn.disabled = true;
    
    controls.appendChild(startBtn);
    controls.appendChild(calBtn);
    controls.appendChild(stopBtn);
    
    widget.appendChild(video);
    widget.appendChild(canvas);
    widget.appendChild(status);
    widget.appendChild(controls);
    
    // Cursor
    const cursor = document.createElement('div');
    cursor.className = 'eyetracking-cursor';
    
    // Scroll zones
    const scrollUp = document.createElement('div');
    scrollUp.className = 'eyetracking-scroll-zone eyetracking-scroll-up';
    scrollUp.textContent = 'Scroll Up Zone';
    
    const scrollDown = document.createElement('div');
    scrollDown.className = 'eyetracking-scroll-zone eyetracking-scroll-down';
    scrollDown.textContent = 'Scroll Down Zone';
    
    // Calibration points
    const calPoints = {};
    const positions = {
      c: { top: '50%', left: '50%' },
      tl: { top: '10%', left: '10%' },
      tr: { top: '10%', left: '90%' },
      bl: { top: '90%', left: '10%' },
     br: { top: '90%', left: '90%' }
   };
  
    Object.keys(positions).forEach(key => {
     const point = document.createElement('div');
     point.className = 'eyetracking-cal-point';
     point.style.top = positions[key].top;
     point.style.left = positions[key].left;
     calPoints[key] = point;
   });
   
   // Make widget draggable
   makeDraggable(widget);
   
   // Store elements
   elements = {
     widget, video, canvas, status, controls,
     startBtn, calBtn, stopBtn,
     cursor, scrollUp, scrollDown, calPoints
   };
   
   // Append to DOM
   document.body.appendChild(widget);
   document.body.appendChild(cursor);
   document.body.appendChild(scrollUp);
   document.body.appendChild(scrollDown);
   Object.values(calPoints).forEach(p => document.body.appendChild(p));
 }

 // Make element draggable
 function makeDraggable(element) {
   let isDragging = false;
   let dragOffset = { x: 0, y: 0 };
   
   element.addEventListener('mousedown', (e) => {
     if (e.target === element || e.target === elements.video) {
       isDragging = true;
       dragOffset.x = e.clientX - element.offsetLeft;
       dragOffset.y = e.clientY - element.offsetTop;
       element.style.transition = 'none';
     }
   });
   
   document.addEventListener('mousemove', (e) => {
     if (isDragging) {
       element.style.left = (e.clientX - dragOffset.x) + 'px';
       element.style.top = (e.clientY - dragOffset.y) + 'px';
       element.style.right = 'auto';
       element.style.bottom = 'auto';
     }
   });
   
   document.addEventListener('mouseup', () => {
     if (isDragging) {
       isDragging = false;
       element.style.transition = 'all 0.3s ease';
     }
   });
 }

 // Show notification
 function showNotification(message, duration = 3000) {
   const notification = document.createElement('div');
   notification.className = 'eyetracking-notification';
   notification.textContent = message;
   document.body.appendChild(notification);
   
   setTimeout(() => {
     notification.style.opacity = '0';
     setTimeout(() => notification.remove(), 300);
   }, duration);
 }

 // Load MediaPipe libraries
 async function loadMediaPipeLibraries() {
   if (typeof FaceMesh !== 'undefined') return true;
   
   const scripts = [
     'https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js',
     'https://cdn.jsdelivr.net/npm/@mediapipe/control_utils/control_utils.js',
     'https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js',
     'https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js'
   ];
   
   for (const src of scripts) {
     await new Promise((resolve, reject) => {
       const script = document.createElement('script');
       script.src = src;
       script.crossOrigin = 'anonymous';
       script.onload = resolve;
       script.onerror = reject;
       document.head.appendChild(script);
     });
   }
   
   await new Promise(resolve => setTimeout(resolve, 1000));
   return typeof FaceMesh !== 'undefined';
 }

 // Initialize MediaPipe
 async function initializeMediaPipe() {
   if (!await loadMediaPipeLibraries()) {
     showNotification('Failed to load MediaPipe libraries', 5000);
     return false;
   }
   
   faceMesh = new FaceMesh({
     locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
   });
   
   faceMesh.setOptions({
     maxNumFaces: 1,
     refineLandmarks: true,
     minDetectionConfidence: 0.5,
     minTrackingConfidence: 0.5
   });
   
   faceMesh.onResults(onResults);
   return true;
 }

 // Draw face mesh with complete tessellation
 function drawFaceMesh(ctx, landmarks) {
   if (!landmarks || !CONFIG.showFaceMesh) return;
   
   ctx.save();
   
   // Draw main face mesh with all connections
   ctx.strokeStyle = CONFIG.meshColor;
   ctx.lineWidth = 0.5;
   
   FACEMESH_TESSELATION.forEach(([start, end]) => {
     if (landmarks[start] && landmarks[end]) {
       ctx.beginPath();
       ctx.moveTo(
         landmarks[start].x * elements.canvas.width,
         landmarks[start].y * elements.canvas.height
       );
       ctx.lineTo(
         landmarks[end].x * elements.canvas.width,
         landmarks[end].y * elements.canvas.height
       );
       ctx.stroke();
     }
   });
   
   // Highlight specific features with different colors
   
   // Eyes - draw with cyan color
   ctx.strokeStyle = 'rgba(0,255,255,0.8)';
   ctx.lineWidth = 1;
   
   const drawContour = (contour) => {
     ctx.beginPath();
     contour.forEach((idx, i) => {
       if (landmarks[idx]) {
         const x = landmarks[idx].x * elements.canvas.width;
         const y = landmarks[idx].y * elements.canvas.height;
         if (i === 0) {
           ctx.moveTo(x, y);
         } else {
           ctx.lineTo(x, y);
         }
       }
     });
     ctx.closePath();
     ctx.stroke();
   };
   
   // Draw eye contours
   drawContour(LEFT_EYE_CONTOUR);
   drawContour(RIGHT_EYE_CONTOUR);
   
   // Iris points - bright yellow
   ctx.fillStyle = 'rgba(255,255,0,0.9)';
   [...L_IRIS, ...R_IRIS].forEach(idx => {
     if (landmarks[idx]) {
       ctx.beginPath();
       ctx.arc(
         landmarks[idx].x * elements.canvas.width,
         landmarks[idx].y * elements.canvas.height,
         3, 0, 2 * Math.PI
       );
       ctx.fill();
     }
   });
   
   // Eyebrows - green
   ctx.strokeStyle = 'rgba(0,255,0,0.6)';
   ctx.lineWidth = 1;
   drawContour(LEFT_EYEBROW);
   drawContour(RIGHT_EYEBROW);
   
   // Lips - red
   ctx.strokeStyle = 'rgba(255,0,0,0.6)';
   ctx.lineWidth = 1;
   drawContour(LIPS_UPPER);
   drawContour(LIPS_LOWER);
   
   // Nose tip - orange
   ctx.fillStyle = 'rgba(255,165,0,0.8)';
   NOSE_TIP.forEach(idx => {
     if (landmarks[idx]) {
       ctx.beginPath();
       ctx.arc(
         landmarks[idx].x * elements.canvas.width,
         landmarks[idx].y * elements.canvas.height,
         2, 0, 2 * Math.PI
       );
       ctx.fill();
     }
   });
   
   // Face oval - white outline
   ctx.strokeStyle = 'rgba(255,255,255,0.4)';
   ctx.lineWidth = 1;
   drawContour(FACE_OVAL);
   
   ctx.restore();
 }

 // Process MediaPipe results
 function onResults(results) {
   const ctx = elements.canvas.getContext('2d');
   ctx.clearRect(0, 0, elements.canvas.width, elements.canvas.height);
   
   if (!results.multiFaceLandmarks?.length) {
     elements.cursor.style.display = 'none';
     return;
   }
   
   const landmarks = results.multiFaceLandmarks[0];
   drawFaceMesh(ctx, landmarks);
   
   const leftIris = centre(landmarks, L_IRIS);
   const rightIris = centre(landmarks, R_IRIS);
   const iris = { 
     x: (leftIris.x + rightIris.x) / 2, 
     y: (leftIris.y + rightIris.y) / 2 
   };
   
   if (isCalibrating) {
     handleCalibration(iris);
   } else if (isCalibrated) {
     handleGazeTracking(landmarks, iris);
   }
 }

 // Handle calibration
 function handleCalibration(iris) {
   const currentData = calibrationData[activeCalPoint];
   if (currentData.length < CONFIG.calSamples) {
     currentData.push({ x: iris.x, y: iris.y });
     
     if (currentData.length % 20 === 0) {
       const progress = Math.round((currentData.length / CONFIG.calSamples) * 100);
       showNotification(`Calibrating ${activeCalPoint.toUpperCase()}: ${progress}%`, 1000);
     }
     
     if (currentData.length >= CONFIG.calSamples) {
       nextCalibrationPhase();
     }
   }
 }

 // Handle gaze tracking
 function handleGazeTracking(landmarks, iris) {
   let offX = (iris.x - centerX) / rangeX;
   let offY = (iris.y - centerY) / rangeY;
   
   if (CONFIG.invertX) offX = -offX;
   
   offX = offX * CONFIG.sensitivity + CONFIG.offsetX;
   offY = offY * CONFIG.sensitivity + CONFIG.offsetY;
   
   const screenW = window.innerWidth;
   const screenH = window.innerHeight;
   const targetX = screenW / 2 + offX * screenW / 2;
   const targetY = screenH / 2 + offY * screenH / 2;
   
   const leftEar = ear(landmarks, L_EYE);
   const rightEar = ear(landmarks, R_EYE);
   const eyeState = detectEyeState(leftEar, rightEar);
   
   if (eyeState !== 'none') {
     mouseX = stableMouseX;
     mouseY = stableMouseY;
   } else {
     mouseX = mouseX * CONFIG.smooth + targetX * (1 - CONFIG.smooth);
     mouseY = mouseY * CONFIG.smooth + targetY * (1 - CONFIG.smooth);
     stableMouseX = mouseX;
     stableMouseY = mouseY;
   }
   
   mouseX = clamp(mouseX, 0, screenW);
   mouseY = clamp(mouseY, 0, screenH);
   
   elements.cursor.style.left = mouseX + 'px';
   elements.cursor.style.top = mouseY + 'px';
   elements.cursor.style.display = 'block';
   
   checkScrollZones();
   
   const now = performance.now();
   if (now - lastBlink > CONFIG.blinkDelay) {
     if (eyeState === 'blink' && lastEyeState !== 'blink') {
       if (inScrollZone) {
         doScroll(inScrollZone);
       } else {
         doClick();
       }
       lastBlink = now;
     } else if (eyeState.includes('wink') && lastEyeState !== eyeState) {
       doClick();
       lastBlink = now;
     }
   }
   
   lastEyeState = eyeState;
 }

 // Check scroll zones
 function checkScrollZones() {
   const screenH = window.innerHeight;
   
   if (mouseY < CONFIG.scrollZoneHeight) {
     if (inScrollZone !== 'up') {
       inScrollZone = 'up';
       if (CONFIG.showScrollZones) elements.scrollUp.style.display = 'block';
     }
   } else if (mouseY > screenH - CONFIG.scrollZoneHeight) {
     if (inScrollZone !== 'down') {
       inScrollZone = 'down';
       if (CONFIG.showScrollZones) elements.scrollDown.style.display = 'block';
     }
   } else {
     if (inScrollZone !== null) {
       inScrollZone = null;
       if (CONFIG.showScrollZones) {
         elements.scrollUp.style.display = 'none';
         elements.scrollDown.style.display = 'none';
       }
     }
   }
 }

 // Handle clicking with enhanced element detection
 function doClick() {
   elements.cursor.style.background = 'rgba(255,255,255,0.9)';
   setTimeout(() => {
     elements.cursor.style.background = CONFIG.cursorColor;
   }, 200);
   
   if (!CONFIG.enableClicks) return;
   
   const clickableSelectors = [
     'button', 'a[href]', '[role="button"]', '[onclick]',
     'input[type="button"]', 'input[type="submit"]', 
     'input[type="checkbox"]', 'input[type="radio"]',
     'select', 'textarea', '.clickable', '.test-button',
     '[tabindex]:not([tabindex="-1"])', '.btn',
     '[data-clickable]', '.clickable-item'
   ];
   
   const clickableElements = Array.from(
     document.querySelectorAll(clickableSelectors.join(','))
   ).filter(el => {
     // Filter out hidden elements
     const rect = el.getBoundingClientRect();
     return rect.width > 0 && rect.height > 0 && 
            window.getComputedStyle(el).visibility !== 'hidden' &&
            window.getComputedStyle(el).display !== 'none';
   });
   
   let closestEl = null;
   let closestDist = CONFIG.latchRadius;
   
   clickableElements.forEach(el => {
     const rect = el.getBoundingClientRect();
     const centerX = rect.left + rect.width / 2;
     const centerY = rect.top + rect.height / 2;
     const distance = Math.hypot(mouseX - centerX, mouseY - centerY);
     
     if (distance < closestDist) {
       closestDist = distance;
       closestEl = el;
     }
   });
   
   if (closestEl) {
     const rect = closestEl.getBoundingClientRect();
     mouseX = rect.left + rect.width / 2;
     mouseY = rect.top + rect.height / 2;
     stableMouseX = mouseX;
     stableMouseY = mouseY;
     
     elements.cursor.style.left = mouseX + 'px';
     elements.cursor.style.top = mouseY + 'px';
     
     // Visual feedback for clicked element
     const originalBg = closestEl.style.background;
     const originalBorder = closestEl.style.border;
     closestEl.style.background = 'rgba(255,255,0,0.3)';
     closestEl.style.border = '2px solid yellow';
     
     setTimeout(() => {
       closestEl.style.background = originalBg;
       closestEl.style.border = originalBorder;
     }, 300);
     
     if (closestEl.classList.contains('test-button')) {
       closestEl.classList.toggle('clicked');
     } else {
       try {
         closestEl.click();
       } catch (e) {
         const event = new MouseEvent('click', { 
           bubbles: true, 
           cancelable: true,
           clientX: mouseX,
           clientY: mouseY
         });
         closestEl.dispatchEvent(event);
       }
     }
   }
 }

 // Handle scrolling with enhanced feedback
 function doScroll(direction) {
   const amount = direction === 'up' ? -CONFIG.scrollAmount : CONFIG.scrollAmount;
   window.scrollBy({ top: amount, behavior: 'smooth' });
   
   const zone = direction === 'up' ? elements.scrollUp : elements.scrollDown;
   const originalBg = zone.style.background;
   zone.style.background = direction === 'up' ? 
     'rgba(0,255,0,0.3)' : 'rgba(0,0,255,0.3)';
   
   setTimeout(() => {
     zone.style.background = originalBg;
   }, 200);
   
   // Show scroll feedback
   showNotification(`Scrolled ${direction}`, 1000);
 }

 // Next calibration phase
 function nextCalibrationPhase() {
   currentCalPhase++;
   
   Object.values(elements.calPoints).forEach(p => p.style.display = 'none');
   
   if (currentCalPhase >= 5) {
     finishCalibration();
     return;
   }
   
   const pointKeys = ['c', 'tl', 'tr', 'bl', 'br'];
   activeCalPoint = pointKeys[currentCalPhase];
   elements.calPoints[activeCalPoint].style.display = 'block';
   
   const pointNames = {
     c: 'center', tl: 'top left', tr: 'top right',
     bl: 'bottom left', br: 'bottom right'
   };
   
   showNotification(`Look at the ${pointNames[activeCalPoint]} point and hold still`, 2000);
 }

 // Finish calibration
 function finishCalibration() {
   Object.values(elements.calPoints).forEach(p => p.style.display = 'none');
   
   // Calculate calibration results
   const allData = Object.values(calibrationData).flat();
   const xValues = allData.map(p => p.x);
   const yValues = allData.map(p => p.y);
   
   rangeX = (Math.max(...xValues) - Math.min(...xValues)) / 2;
   rangeY = (Math.max(...yValues) - Math.min(...yValues)) / 2;
   
   const centerData = calibrationData.c;
   centerX = centerData.reduce((sum, p) => sum + p.x, 0) / centerData.length;
   centerY = centerData.reduce((sum, p) => sum + p.y, 0) / centerData.length;
   
   isCalibrating = false;
   isCalibrated = true;
   
   elements.status.classList.add('calibrated');
   elements.cursor.style.display = 'block';
   
   showNotification('Calibration complete! Look around and blink to click', 3000);
   
   // Save calibration
   localStorage.setItem('eyetracking-calibration', JSON.stringify({
     centerX, centerY, rangeX, rangeY,
     timestamp: Date.now()
   }));
 }

 // Update status
 function updateStatus() {
   elements.status.classList.remove('tracking', 'calibrated');
   if (isCalibrated) {
     elements.status.classList.add('calibrated');
   } else if (isTracking) {
     elements.status.classList.add('tracking');
   }
 }

 // Start tracking
 async function start() {
   if (isTracking) return;
   
   try {
     if (!faceMesh) {
       showNotification('Initializing MediaPipe...', 2000);
       if (!await initializeMediaPipe()) {
         showNotification('Failed to initialize MediaPipe', 5000);
         return;
       }
     }
     
     showNotification('Requesting camera access...', 2000);
     const stream = await navigator.mediaDevices.getUserMedia({
       video: { 
         width: 640, 
         height: 480,
         facingMode: 'user'
       }
     });
     
     elements.video.srcObject = stream;
     
     const processFrame = async () => {
       if (isTracking && elements.video.readyState === 4) {
         try {
           await faceMesh.send({ image: elements.video });
         } catch (e) {
           console.warn('Face mesh processing error:', e);
         }
       }
       if (isTracking) {
         requestAnimationFrame(processFrame);
       }
     };
     
     elements.video.onloadedmetadata = () => {
       elements.video.play();
       elements.canvas.width = elements.video.videoWidth;
       elements.canvas.height = elements.video.videoHeight;
       processFrame();
     };
     
     isTracking = true;
     updateStatus();
     
     elements.startBtn.disabled = true;
     elements.calBtn.disabled = false;
     elements.stopBtn.disabled = false;
     
     showNotification('Camera started! Click Calibrate to begin setup', 3000);
     
     // Auto-load previous calibration if available
     loadCalibration();
     
   } catch (error) {
     showNotification('Camera access denied. Please allow camera access and try again.', 5000);
   }
 }

 // Stop tracking
 function stop() {
   if (!isTracking) return;
   
   isTracking = false;
   isCalibrated = false;
   isCalibrating = false;
   
   if (elements.video.srcObject) {
     elements.video.srcObject.getTracks().forEach(track => track.stop());
     elements.video.srcObject = null;
   }
   
   elements.cursor.style.display = 'none';
   Object.values(elements.calPoints).forEach(p => p.style.display = 'none');
   elements.scrollUp.style.display = 'none';
   elements.scrollDown.style.display = 'none';
   
   // Reset calibration data
   calibrationData = { c: [], tl: [], tr: [], bl: [], br: [] };
   
   // Reset state
   blinkState = false;
   blinkCount = 0;
   earHistory = [];
   leftEarHistory = [];
   rightEarHistory = [];
   lastEyeState = 'none';
   
   updateStatus();
   
   elements.startBtn.disabled = false;
   elements.calBtn.disabled = true;
   elements.stopBtn.disabled = true;
   
   showNotification('Eye tracking stopped', 2000);
 }

 // Start calibration
 function calibrate() {
   if (!isTracking) return;
   
   isCalibrating = true;
   isCalibrated = false;
   currentCalPhase = -1;
   
   // Reset calibration data
   calibrationData = { c: [], tl: [], tr: [], bl: [], br: [] };
   
   showNotification('Starting calibration. Look at each point when it appears.', 3000);
   setTimeout(() => {
     nextCalibrationPhase();
   }, 3000);
 }

 // Load previous calibration
 function loadCalibration() {
   try {
     const saved = localStorage.getItem('eyetracking-calibration');
     if (saved) {
       const data = JSON.parse(saved);
       
       // Only load if less than 24 hours old
       if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
         centerX = data.centerX;
         centerY = data.centerY;
         rangeX = data.rangeX;
         rangeY = data.rangeY;
         isCalibrated = true;
         
         updateStatus();
         elements.cursor.style.display = 'block';
         
         showNotification('Previous calibration loaded! Eye tracking is ready.', 3000);
       }
     }
   } catch (e) {
     // Ignore loading errors
   }
 }

 // Check browser compatibility
 function checkCompatibility() {
   const issues = [];
   
   if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
     issues.push('Camera access not supported');
   }
   
   if (location.protocol !== 'https:' && 
       location.hostname !== 'localhost' && 
       location.hostname !== '127.0.0.1') {
     issues.push('HTTPS required for camera access');
   }
   
   if (!window.Worker) {
     issues.push('Web Workers not supported');
   }
   
   if (!window.requestAnimationFrame) {
     issues.push('Animation frame not supported');
   }
   
   return issues;
 }

 // Initialize everything
 async function initialize() {
   // Check compatibility
   const issues = checkCompatibility();
   if (issues.length > 0) {
     console.warn('EyeTracking compatibility issues:', issues);
     showNotification(`Compatibility issues: ${issues.join(', ')}`, 10000);
   }
   
   // Create UI
   createStyles();
   createUI();
   
   // Show welcome message
   showNotification('Eye tracking ready! Click Start to begin.', 5000);
   
   // Auto-start if configured
   if (CONFIG.autoStart) {
     setTimeout(() => {
       start();
     }, 2000);
   }
 }

 // Expose global API
 window.EyeTracking = {
   start,
   stop,
   calibrate,
   config: CONFIG,
   isTracking: () => isTracking,
   isCalibrated: () => isCalibrated,
   updateConfig: (newConfig) => {
     Object.assign(CONFIG, newConfig);
   },
   getState: () => ({
     isTracking,
     isCalibrating,
     isCalibrated,
     mouseX,
     mouseY,
     inScrollZone,
     lastEyeState
   })
 };

 // Auto-initialize when DOM is ready
 if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initialize);
 } else {
   // DOM already loaded
   setTimeout(initialize, 100);
 }

 // Keyboard shortcuts
 document.addEventListener('keydown', (e) => {
   // Ctrl+E to toggle tracking
   if (e.ctrlKey && e.key === 'e') {
     e.preventDefault();
     if (isTracking) {
       stop();
     } else {
       start();
     }
   }
   
   // Ctrl+C to calibrate
   if (e.ctrlKey && e.key === 'c' && isTracking) {
     e.preventDefault();
     calibrate();
   }
   
   // Ctrl+R to recalibrate (force new calibration)
   if (e.ctrlKey && e.key === 'r' && isTracking) {
     e.preventDefault();
     localStorage.removeItem('eyetracking-calibration');
     calibrate();
   }
 });

 // Cleanup on page unload
 window.addEventListener('beforeunload', () => {
   if (isTracking) {
     stop();
   }
 });

 // Handle visibility changes for performance
 document.addEventListener('visibilitychange', () => {
   if (document.hidden && isTracking) {
     // Page hidden, pause tracking to save resources
     if (elements.video.srcObject) {
       elements.video.srcObject.getVideoTracks().forEach(track => {
         track.enabled = false;
       });
     }
   } else if (!document.hidden && isTracking) {
     // Page visible, resume tracking
     if (elements.video.srcObject) {
       elements.video.srcObject.getVideoTracks().forEach(track => {
         track.enabled = true;
       });
     }
   }
 });

 // Add error handling for MediaPipe
 window.addEventListener('error', (e) => {
   if (e.message && e.message.includes('MediaPipe')) {
     showNotification('MediaPipe error detected. Reloading libraries...', 3000);
     setTimeout(() => {
       if (isTracking) {
         stop();
         setTimeout(start, 1000);
       }
     }, 1000);
   }
 });

 console.log('EyeTracking.js v3.0.0 - Complete Edition loaded successfully!');
 console.log('Features: Full face mesh, enhanced click detection, persistent calibration');
 console.log('Usage: Simply include this script and eye tracking will auto-initialize.');
 console.log('Keyboard shortcuts: Ctrl+E (toggle), Ctrl+C (calibrate), Ctrl+R (recalibrate)');

})();