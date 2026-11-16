/**
 * Deterministic Device Fingerprint Collector
 * Captures Canvas, WebGL, screen metrics, timezone, and webdriver indicators
 * for anti-fraud validation.
 */

const STORAGE_KEY = 'coin_hunters_device_hash';

// Fast string hash helper (FNV-1a 32-bit to hex string)
function fnv1a(str: string): string {
  let hash = 0x811c9dc5;
  for (let i = 0; i < str.length; i++) {
    hash ^= str.charCodeAt(i);
    hash += (hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24);
  }
  return ('0000000' + (hash >>> 0).toString(16)).slice(-8);
}

// Generate Canvas 2D Hash
function getCanvasFingerprint(): string {
  try {
    const canvas = document.createElement('canvas');
    canvas.width = 200;
    canvas.height = 50;
    const ctx = canvas.getContext('2d');
    if (!ctx) return 'no-canvas';

    ctx.textBaseline = 'top';
    ctx.font = "14px 'Arial', sans-serif";
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = '#f60';
    ctx.fillRect(125, 1, 62, 20);
    ctx.fillStyle = '#069';
    ctx.fillText('CoinHunters#123', 2, 15);
    ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
    ctx.fillText('CoinHunters#123', 4, 17);

    return fnv1a(canvas.toDataURL());
  } catch {
    return 'canvas-err';
  }
}

// Generate WebGL Fingerprint
function getWebGLFingerprint(): string {
  try {
    const canvas = document.createElement('canvas');
    const gl = (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')) as WebGLRenderingContext | null;
    if (!gl) return 'no-webgl';

    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
    const vendor = debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : gl.getParameter(gl.VENDOR);
    const renderer = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER);

    return fnv1a(`${vendor}~${renderer}`);
  } catch {
    return 'webgl-err';
  }
}

// Check for automated browser / headless webdriver
export function isAutomatedEnvironment(): boolean {
  if (typeof window === 'undefined') return false;

  const nav = navigator as unknown as { webdriver?: boolean; __webdriver_script_fn?: unknown };
  if (nav.webdriver || nav.__webdriver_script_fn !== undefined) {
    return true;
  }

  // Check window driver flags
  const win = window as unknown as Record<string, unknown>;
  if (
    win._phantom !== undefined ||
    win.__nightmare !== undefined ||
    win.callPhantom !== undefined ||
    win.__selenium_evaluate !== undefined
  ) {
    return true;
  }

  return false;
}

// Async SHA-256 hasher
async function sha256(message: string): Promise<string> {
  if (typeof window !== 'undefined' && window.crypto && window.crypto.subtle) {
    try {
      const msgUint8 = new TextEncoder().encode(message);
      const hashBuffer = await window.crypto.subtle.digest('SHA-256', msgUint8);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
    } catch {
      // Fallback
    }
  }

  // Synchronous fallback
  let h1 = 0xdeadbeef;
  let h2 = 0x41c64e6d;
  for (let i = 0; i < message.length; i++) {
    const ch = message.charCodeAt(i);
    h1 = Math.imul(h1 ^ ch, 2654435761);
    h2 = Math.imul(h2 ^ ch, 1597334677);
  }
  h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
  h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);
  return (4294967296 * (2097151 & h2) + (h1 >>> 0)).toString(16).padStart(32, '0');
}

/**
 * Get or compute persistent device hash
 */
export async function getDeviceFingerprint(): Promise<string> {
  if (typeof window === 'undefined') {
    return 'ssr-unknown-device';
  }

  try {
    const cached = localStorage.getItem(STORAGE_KEY);
    if (cached && cached.length >= 16) {
      return cached;
    }
  } catch {
    // localStorage might be blocked
  }

  const screenMetrics = `${window.screen.width}x${window.screen.height}x${window.screen.colorDepth}@${window.devicePixelRatio || 1}`;
  const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
  const language = navigator.language || 'en';
  const concurrency = navigator.hardwareConcurrency || 4;
  const canvasHash = getCanvasFingerprint();
  const webglHash = getWebGLFingerprint();
  const isBot = isAutomatedEnvironment();

  const rawFingerprint = [
    screenMetrics,
    timezone,
    language,
    concurrency,
    canvasHash,
    webglHash,
    isBot ? 'bot-flag' : 'clean',
  ].join(':::');

  const hash = await sha256(rawFingerprint);

  try {
    localStorage.setItem(STORAGE_KEY, hash);
  } catch {
    // ignore quota/storage errors
  }

  return hash;
}
